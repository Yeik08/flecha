<?php
// Habilitar errores para depuración (quitar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/*
* Portal/portal-camiones/registrar_camion.php
* VERSIÓN MEJORADA (v3):
* - Añade validación proactiva para duplicados (N° Eco, VIN, Placas, Series Filtros).
* - Devuelve mensajes de error 409 (Conflict) específicos.
*/

session_start();
header('Content-Type: application/json');

// --- 1. Seguridad y Conexión ---
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

require_once '../../php/db_connect.php'; 

if ($conn === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

$id_empleado_registra = $_SESSION['user_id']; 
$conn->begin_transaction();

try {

    // =========================================
    // --- 2. VALIDACIÓN PROACTIVA DE DUPLICADOS ---
    // =========================================
    
    // Obtenemos todos los campos que deben ser únicos
    $numero_economico = $_POST['identificador'];
    $placas = $_POST['placas'];
    $vin = $_POST['numero_serie'];
    $serie_filtro_aceite = $_POST['numero_serie_filtro_aceite'];
    $serie_filtro_centrifugo = $_POST['numero_serie_filtro_centrifugo'];

    $errores_duplicados = [];

    // 2a. Validar Camión (N° Eco, VIN, Placas)
    // Hacemos subconsultas eficientes para verificar cada campo único
    $sql_check_camion = "SELECT 
        (SELECT 1 FROM tb_camiones WHERE numero_economico = ? LIMIT 1) as 'eco_dup',
        (SELECT 1 FROM tb_camiones WHERE vin = ? LIMIT 1) as 'vin_dup',
        (SELECT 1 FROM tb_camiones WHERE placas = ? LIMIT 1) as 'placas_dup'
    ";
    
    $stmt_check = $conn->prepare($sql_check_camion);
    $stmt_check->bind_param("sss", $numero_economico, $vin, $placas);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result()->fetch_assoc();

    if ($result_check['eco_dup']) { $errores_duplicados[] = "El N° Económico '{$numero_economico}' ya está registrado."; }
    if ($result_check['vin_dup']) { $errores_duplicados[] = "El VIN '{$vin}' ya está registrado."; }
    if ($result_check['placas_dup']) { $errores_duplicados[] = "Las placas '{$placas}' ya están registradas."; }

    // 2b. Validar N° de Serie de Filtros (si se proporcionaron)
    if (!empty($serie_filtro_aceite) || !empty($serie_filtro_centrifugo)) {
        $stmt_check_filtro = $conn->prepare("SELECT id FROM tb_inventario_filtros WHERE numero_serie = ? LIMIT 1");
        
        if (!empty($serie_filtro_aceite)) {
            $stmt_check_filtro->bind_param("s", $serie_filtro_aceite);
            $stmt_check_filtro->execute();
            if ($stmt_check_filtro->get_result()->num_rows > 0) {
                $errores_duplicados[] = "El N° de Serie '{$serie_filtro_aceite}' (Aceite) ya está registrado en el inventario.";
            }
        }
        if (!empty($serie_filtro_centrifugo)) {
            $stmt_check_filtro->bind_param("s", $serie_filtro_centrifugo);
            $stmt_check_filtro->execute();
            if ($stmt_check_filtro->get_result()->num_rows > 0) {
                $errores_duplicados[] = "El N° de Serie '{$serie_filtro_centrifugo}' (Centrífugo) ya está registrado en el inventario.";
            }
        }
    }

    // 2c. Si se encontraron errores, detener la ejecución y reportar.
    if (!empty($errores_duplicados)) {
        http_response_code(409); // 409 Conflict (el código HTTP correcto para duplicados)
        // No necesitamos rollback porque aún no hemos hecho ningún INSERT
        echo json_encode(['success' => false, 'message' => implode(" ", $errores_duplicados)]);
        exit; // Detenemos el script
    }
    // =========================================
    // --- FIN DE LA VALIDACIÓN PROACTIVA ---
    // =========================================


    // --- 3. OBTENER ID NUMÉRICO DEL CONDUCTOR ---
    $id_conductor_numerico = null;
    if (!empty($_POST['id_conductor'])) {
        $id_conductor_interno = $_POST['id_conductor'];
        $stmt_find_conductor = $conn->prepare("SELECT id_empleado FROM empleados WHERE id_interno = ? AND role_id = 7 LIMIT 1");
        $stmt_find_conductor->bind_param("s", $id_conductor_interno);
        $stmt_find_conductor->execute();
        $result_conductor = $stmt_find_conductor->get_result();
        
        if ($result_conductor->num_rows > 0) {
            $id_conductor_numerico = $result_conductor->fetch_assoc()['id_empleado'];
        } else {
            throw new Exception("El ID de conductor '{$id_conductor_interno}' no fue encontrado.");
        }
    }

    // --- TRADUCIR ESTATUS ---
    $estatus_form = $_POST['estatus_inicial'] ?? 'inactivo';
    $estatus_db = 'Inactivo'; 

    switch ($estatus_form) {
        case 'trabajando': $estatus_db = 'Activo'; break;
        case 'mantenimiento': $estatus_db = 'En Taller'; break;
        case 'inactivo': $estatus_db = 'Inactivo'; break;
    }

    // --- 4. Registrar el Camión Principal ---
    $sql_camion = "INSERT INTO tb_camiones (
        numero_economico, condicion, placas, vin, id_conductor_asignado, marca, anio, id_tecnologia, estatus, 
        kilometraje_total, fecha_ult_mantenimiento, 
        marca_filtro_aceite_actual, serie_filtro_aceite_actual, fecha_ult_cambio_aceite,
        marca_filtro_centrifugo_actual, serie_filtro_centrifugo_actual, fecha_ult_cambio_centrifugo,
        lubricante_actual
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_camion = $conn->prepare($sql_camion);
    
    $fecha_mant = empty($_POST['fecha_mantenimiento']) ? null : $_POST['fecha_mantenimiento'];
    $fecha_filtro_aceite = empty($_POST['fecha_cambio_filtro']) ? null : $_POST['fecha_cambio_filtro'];
    $fecha_filtro_cent = empty($_POST['fecha_cambio_filtro_centrifugo']) ? null : $_POST['fecha_cambio_filtro_centrifugo'];
    
    // (Tu corrección del bind_param ya estaba bien)
    $stmt_camion->bind_param("ssssisidsdssssssss", 
        $_POST['identificador'],
        $_POST['condicion'],
        $_POST['placas'],
        $_POST['numero_serie'],
        $id_conductor_numerico,
        $_POST['marca'],
        $_POST['anio'],
        $_POST['tipo_unidad'],
        $estatus_db,
        $_POST['kilometros'],
        $fecha_mant, 
        $_POST['marca_filtro'],
        $_POST['numero_serie_filtro_aceite'],
        $fecha_filtro_aceite,
        $_POST['marca_filtro_centrifugo'],
        $_POST['numero_serie_filtro_centrifugo'],
        $fecha_filtro_cent,
        $_POST['tipo_aceite']
    );
    
    $stmt_camion->execute();
    
    $nuevo_camion_id = $conn->insert_id;
    if ($nuevo_camion_id === 0) {
        // Esta comprobación es buena, pero si el INSERT falla, 
        // la línea anterior $stmt_camion->execute() ya habría lanzado una excepción.
        // La dejamos como doble seguridad.
        throw new Exception("El camión NO se registró. Error desconocido.");
    }

    // --- 5. Registrar Filtros en el Inventario ---
    $ubicacion_taller = 5; // ID 5 = 'camion' (Asumido)

    // --- 5a. Filtro de Aceite ---
    if (!empty($serie_filtro_aceite)) {
        
        $id_cat_filtro_aceite = null;
        $stmt_find_aceite = $conn->prepare("SELECT id FROM tb_cat_filtros WHERE marca = ? AND tipo_filtro = 'Aceite' LIMIT 1");
        $stmt_find_aceite->bind_param("s", $_POST['marca_filtro']); 
        $stmt_find_aceite->execute();
        $result_aceite = $stmt_find_aceite->get_result();
        if ($result_aceite->num_rows > 0) {
            $id_cat_filtro_aceite = $result_aceite->fetch_assoc()['id'];
        } else {
            // Si la marca no existe en el catálogo, la crea
            $stmt_new_cat_aceite = $conn->prepare("INSERT INTO tb_cat_filtros (marca, numero_parte, tipo_filtro) VALUES (?, 'N/A', 'Aceite')");
            $stmt_new_cat_aceite->bind_param("s", $_POST['marca_filtro']);
            $stmt_new_cat_aceite->execute();
            $id_cat_filtro_aceite = $conn->insert_id;
        }

        $sql_inv_aceite = "INSERT INTO tb_inventario_filtros 
            (id_cat_filtro, numero_serie, id_ubicacion, estatus, id_camion_instalado) 
            VALUES (?, ?, ?, 'Instalado', ?)";
        $stmt_inv_aceite = $conn->prepare($sql_inv_aceite);
        $stmt_inv_aceite->bind_param("isii", 
            $id_cat_filtro_aceite,
            $serie_filtro_aceite, // Usamos la variable validada
            $ubicacion_taller,
            $nuevo_camion_id
        );
        $stmt_inv_aceite->execute();
    }

    // --- 5b. Filtro Centrífugo ---
    if (!empty($serie_filtro_centrifugo)) {
        $id_cat_filtro_cent = null;
        $stmt_find_cent = $conn->prepare("SELECT id FROM tb_cat_filtros WHERE marca = ? AND tipo_filtro = 'Centrifugo' LIMIT 1");
        $stmt_find_cent->bind_param("s", $_POST['marca_filtro_centrifugo']);
        $stmt_find_cent->execute();
        $result_cent = $stmt_find_cent->get_result();
         if ($result_cent->num_rows > 0) {
            $id_cat_filtro_cent = $result_cent->fetch_assoc()['id'];
        } else {
            // Si la marca no existe, la crea
            $stmt_new_cat_cent = $conn->prepare("INSERT INTO tb_cat_filtros (marca, numero_parte, tipo_filtro) VALUES (?, 'N/A', 'Centrifugo')");
            $stmt_new_cat_cent->bind_param("s", $_POST['marca_filtro_centrifugo']);
            $stmt_new_cat_cent->execute();
            $id_cat_filtro_cent = $conn->insert_id;
        }

        $sql_inv_cent = "INSERT INTO tb_inventario_filtros 
            (id_cat_filtro, numero_serie, id_ubicacion, estatus, id_camion_instalado) 
            VALUES (?, ?, ?, 'Instalado', ?)";
        $stmt_inv_cent = $conn->prepare($sql_inv_cent);
        $stmt_inv_cent->bind_param("isii", 
            $id_cat_filtro_cent,
            $serie_filtro_centrifugo, // Usamos la variable validada
            $ubicacion_taller,
            $nuevo_camion_id
        );
        $stmt_inv_cent->execute();
    }

    // --- 6. Finalizar Transacción ---
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Camión y filtros iniciales registrados con éxito. ID Camión: ' . $nuevo_camion_id]);

} catch (Exception $e) {
    $conn->rollback();
    
    // Dejamos el chequeo del 1062 como un *seguro* por si algo se nos escapa,
    // pero la validación proactiva ya debería haber manejado esto.
    if (isset($conn->errno) && $conn->errno == 1062) {
        http_response_code(409); // 409 Conflict
        echo json_encode(['success' => false, 'message' => 'Error: Conflicto de duplicado (VIN, Placas o N° Económico).']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

$conn->close();
?>