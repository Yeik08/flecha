<?php
// Habilitar errores para depuración (quitar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/*
* Portal/portal-camiones/registrar_camion.php
* VERSIÓN CORREGIDA: Busca el ID numérico del conductor.
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

    // --- 3. OBTENER ID NUMÉRICO DEL CONDUCTOR --- // <<< CORRECCIÓN
    $id_conductor_numerico = null; // Empezamos en null
    
    if (!empty($_POST['id_conductor'])) {
        $id_conductor_interno = $_POST['id_conductor']; // Esto es "CON-007"
        
        // Preparamos una consulta para buscar el ID numérico
        $stmt_find_conductor = $conn->prepare("SELECT id_empleado FROM empleados WHERE id_interno = ? AND role_id = 7 LIMIT 1");
        $stmt_find_conductor->bind_param("s", $id_conductor_interno);
        $stmt_find_conductor->execute();
        $result_conductor = $stmt_find_conductor->get_result();
        
        if ($result_conductor->num_rows > 0) {
            $id_conductor_numerico = $result_conductor->fetch_assoc()['id_empleado']; // Esto es 7
        } else {
            // Si el usuario escribió un ID que no existe (ej. "CON-999")
            throw new Exception("El ID de conductor '{$id_conductor_interno}' no fue encontrado en la base de datos.");
        }
    }
    // Si el campo estaba vacío, $id_conductor_numerico se queda como null, lo cual es correcto.


    // --- 4. Registrar el Camión Principal ---
    $sql_camion = "INSERT INTO tb_camiones (
        numero_economico, condicion, placas, vin, id_conductor_asignado, marca, anio, id_tecnologia, estatus, 
        kilometraje_total, fecha_ult_mantenimiento, 
        marca_filtro_aceite_actual, serie_filtro_aceite_actual, fecha_ult_cambio_aceite,
        marca_filtro_centrifugo_actual, serie_filtro_centrifugo_actual, fecha_ult_cambio_centrifugo,
        lubricante_actual
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_camion = $conn->prepare($sql_camion);
    
    // Asignar variables
    $fecha_mant = empty($_POST['fecha_mantenimiento']) ? null : $_POST['fecha_mantenimiento'];
    $fecha_filtro_aceite = empty($_POST['fecha_cambio_filtro']) ? null : $_POST['fecha_cambio_filtro'];
    $fecha_filtro_cent = empty($_POST['fecha_cambio_filtro_centrifugo']) ? null : $_POST['fecha_cambio_filtro_centrifugo'];
    
    // Los 'name' del formulario
    // <<< CORRECCIÓN: El 5to tipo de dato cambió de 's' a 'i' (string a integer)
    $stmt_camion->bind_param("ssssisisisssssssss", 
        $_POST['identificador'],
        $_POST['condicion'],
        $_POST['placas'],
        $_POST['numero_serie'],
        $id_conductor_numerico, // <<< CORRECCIÓN: Usamos el ID numérico
        $_POST['marca'],
        $_POST['anio'],
        $_POST['tipo_unidad'],
        $_POST['estatus_inicial'],
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
    
    // --- 5. Registrar Filtros en el Inventario (Misma lógica que antes) ---

    // --- 5a. Filtro de Aceite ---
    if (!empty($_POST['numero_serie_filtro_aceite'])) {
        
        $id_cat_filtro_aceite = null;
        // CORRECCIÓN: Usamos el nombre 'marca_filtro' del formulario
        $stmt_find_aceite = $conn->prepare("SELECT id FROM tb_cat_filtros WHERE marca = ? AND tipo_filtro = 'Aceite' LIMIT 1");
        $stmt_find_aceite->bind_param("s", $_POST['marca_filtro']); 
        $stmt_find_aceite->execute();
        $result_aceite = $stmt_find_aceite->get_result();

        if ($result_aceite->num_rows > 0) {
            $id_cat_filtro_aceite = $result_aceite->fetch_assoc()['id'];
        } else {
            // Si la marca/tipo no existe, la creamos
            $stmt_new_cat_aceite = $conn->prepare("INSERT INTO tb_cat_filtros (marca, numero_parte, tipo_filtro) VALUES (?, 'N/A', 'Aceite')");
            $stmt_new_cat_aceite->bind_param("s", $_POST['marca_filtro']);
            $stmt_new_cat_aceite->execute();
            $id_cat_filtro_aceite = $conn->insert_id;
        }

        $sql_inv_aceite = "INSERT INTO tb_inventario_filtros 
            (id_cat_filtro, numero_serie, id_ubicacion, estatus, id_camion_instalado) 
            VALUES (?, ?, ?, 'Instalado', ?)";
        
        $ubicacion_taller = 1; // Asumimos ID 1 = 'Taller Magdalena'
        
        $stmt_inv_aceite = $conn->prepare($sql_inv_aceite);
        $stmt_inv_aceite->bind_param("isii", 
            $id_cat_filtro_aceite,
            $_POST['numero_serie_filtro_aceite'],
            $ubicacion_taller,
            $nuevo_camion_id
        );
        $stmt_inv_aceite->execute();
    }

    // --- 5b. Filtro Centrífugo ---
    if (!empty($_POST['numero_serie_filtro_centrifugo'])) {
        
        $id_cat_filtro_cent = null;
        // CORRECCIÓN: Usamos el nombre 'marca_filtro_centrifugo' del formulario
        $stmt_find_cent = $conn->prepare("SELECT id FROM tb_cat_filtros WHERE marca = ? AND tipo_filtro = 'Centrifugo' LIMIT 1");
        $stmt_find_cent->bind_param("s", $_POST['marca_filtro_centrifugo']);
        $stmt_find_cent->execute();
        $result_cent = $stmt_find_cent->get_result();

         if ($result_cent->num_rows > 0) {
            $id_cat_filtro_cent = $result_cent->fetch_assoc()['id'];
        } else {
            // Si la marca/tipo no existe, la creamos
            $stmt_new_cat_cent = $conn->prepare("INSERT INTO tb_cat_filtros (marca, numero_parte, tipo_filtro) VALUES (?, 'N/A', 'Centrifugo')");
            $stmt_new_cat_cent->bind_param("s", $_POST['marca_filtro_centrifugo']);
            $stmt_new_cat_cent->execute();
            $id_cat_filtro_cent = $conn->insert_id;
        }

        $sql_inv_cent = "INSERT INTO tb_inventario_filtros 
            (id_cat_filtro, numero_serie, id_ubicacion, estatus, id_camion_instalado) 
            VALUES (?, ?, ?, 'Instalado', ?)";
            
        $ubicacion_taller = 1; // Asumimos ID 1 = 'Taller Magdalena'
        $stmt_inv_cent = $conn->prepare($sql_inv_cent);
        $stmt_inv_cent->bind_param("isii", 
            $id_cat_filtro_cent,
            $_POST['numero_serie_filtro_centrifugo'],
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
    http_response_code(500);
    if ($conn->errno == 1062) {
        echo json_encode(['success' => false, 'message' => 'Error: Ya existe un camión con ese VIN, Placas o N° Económico.']);
    } else {
        // Devuelve el mensaje de error específico (ej. "Conductor no encontrado")
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

$conn->close();
?>