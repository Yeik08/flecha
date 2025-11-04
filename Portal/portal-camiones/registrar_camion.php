<?php
/*
* Portal/portal-camiones/registrar_camion.php
* Recibe datos del formulario de 'camiones.php' y registra el camión
* y sus filtros iniciales en el inventario.
*/

session_start();
header('Content-Type: application/json');

// --- 1. Seguridad y Conexión ---
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

// Conexión a la BD (ajusta la ruta según sea necesario)
// Sube 2 niveles (desde portal-camiones/ y Portal/) para llegar a la raíz y entrar a php/
require_once '../../php/db_connect.php'; 

if ($conn === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

// El ID del empleado (Rol 2, Mesa de Mantenimiento) que está registrando
$id_empleado_registra = $_SESSION['user_id']; 

// --- 2. Iniciar Transacción (TODO O NADA) ---
// Como vamos a insertar en 3 o 4 tablas, si algo falla,
// revertimos todo para no dejar datos corruptos.
$conn->begin_transaction();

try {
    // --- 3. Registrar el Camión Principal ---
    $sql_camion = "INSERT INTO tb_camiones (
        numero_economico, condicion, placas, vin, id_conductor_asignado, marca, anio, id_tecnologia, estatus, 
        kilometraje_total, fecha_ult_mantenimiento, 
        
        -- Datos de filtros (que ahora existen gracias al Paso 0)
        marca_filtro_aceite_actual, serie_filtro_aceite_actual, fecha_ult_cambio_aceite,
        marca_filtro_centrifugo_actual, serie_filtro_centrifugo_actual, fecha_ult_cambio_centrifugo,
        lubricante_actual
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_camion = $conn->prepare($sql_camion);
    
    // Asignar variables (asegúrate que los 'name' del form coincidan)
    // NOTA: Debes validar y limpiar estos datos en un proyecto real.
    $id_conductor = empty($_POST['id_conductor']) ? null : $_POST['id_conductor'];
    $fecha_mant = empty($_POST['fecha_mantenimiento']) ? null : $_POST['fecha_mantenimiento'];
    $fecha_filtro_aceite = empty($_POST['fecha_cambio_filtro']) ? null : $_POST['fecha_cambio_filtro'];
    $fecha_filtro_cent = empty($_POST['fecha_cambio_filtro_centrifugo']) ? null : $_POST['fecha_cambio_filtro_centrifugo'];
    
    // Los 'name' del formulario
    $stmt_camion->bind_param("sssssssssissssssss", 
        $_POST['identificador'],
        $_POST['condicion'],
        $_POST['placas'],
        $_POST['numero_serie'],
        $id_conductor, // 'id_conductor'
        $_POST['marca'],
        $_POST['anio'],
        $_POST['tipo_unidad'], // 'tipo_unidad' (id_tecnologia)
        $_POST['estatus_inicial'],
        $_POST['kilometros'],
        $fecha_mant, // 'fecha_mantenimiento'
        
        $_POST['marca_filtro'],
        $_POST['numero_serie_filtro_aceite'],
        $fecha_filtro_aceite,
        
        $_POST['marca_filtro_centrifugo'],
        $_POST['numero_serie_filtro_centrifugo'],
        $fecha_filtro_cent,
        
        $_POST['tipo_aceite'] // 'tipo_aceite' (Lubricante)
    );
    
    $stmt_camion->execute();
    
    // Obtener el ID del camión que acabamos de crear
    $nuevo_camion_id = $conn->insert_id;
    
    // --- 4. Registrar Filtros en el Inventario ---
    // Este es el paso clave que pediste.
    // Como es un camión "usado" o "nuevo", sus filtros iniciales 
    // se agregan al inventario con estatus 'Instalado'.

    // --- 4a. Filtro de Aceite ---
    if (!empty($_POST['numero_serie_filtro_aceite'])) {
        
        // Buscamos el ID del tipo de filtro en el catálogo
        $id_cat_filtro_aceite = null;
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

        // Insertar en el inventario con el N° de Serie
        $sql_inv_aceite = "INSERT INTO tb_inventario_filtros 
            (id_cat_filtro, numero_serie, id_ubicacion, estatus, id_camion_instalado) 
            VALUES (?, ?, ?, 'Instalado', ?)";
        
        // Asumimos ID 1 = 'Taller Magdalena' (lugar donde se da de alta)
        $ubicacion_taller = 1; 
        
        $stmt_inv_aceite = $conn->prepare($sql_inv_aceite);
        $stmt_inv_aceite->bind_param("isii", 
            $id_cat_filtro_aceite,
            $_POST['numero_serie_filtro_aceite'],
            $ubicacion_taller,
            $nuevo_camion_id
        );
        $stmt_inv_aceite->execute();
        
        // (Opcional pero recomendado) Registrar en el historial
        // $filtro_inv_id_aceite = $conn->insert_id;
        // $conn->query("INSERT INTO tb_movimientos_historico_filtros (id_filtro_inventario, id_empleado_registra, tipo_movimiento) VALUES ($filtro_inv_id_aceite, $id_empleado_registra, 'Instalacion')");
    }

    // --- 4b. Filtro Centrífugo ---
    if (!empty($_POST['numero_serie_filtro_centrifugo'])) {
        
        // (Haríamos la misma lógica de buscar/crear en tb_cat_filtros)
        // Por simplicidad, asumimos que existe el ID 2 = 'SCANIA Centrifugo'
        $id_cat_filtro_cent = 2; // ID 2 de
        $ubicacion_taller = 1; // ID 1 de

        $sql_inv_cent = "INSERT INTO tb_inventario_filtros 
            (id_cat_filtro, numero_serie, id_ubicacion, estatus, id_camion_instalado) 
            VALUES (?, ?, ?, 'Instalado', ?)";
            
        $stmt_inv_cent = $conn->prepare($sql_inv_cent);
        $stmt_inv_cent->bind_param("isii", 
            $id_cat_filtro_cent,
            $_POST['numero_serie_filtro_centrifugo'],
            $ubicacion_taller,
            $nuevo_camion_id
        );
        $stmt_inv_cent->execute();
        
        // (Opcional pero recomendado) Registrar en el historial
        // $filtro_inv_id_cent = $conn->insert_id;
        // $conn->query("INSERT INTO tb_movimientos_historico_filtros (id_filtro_inventario, id_empleado_registra, tipo_movimiento) VALUES ($filtro_inv_id_cent, $id_empleado_registra, 'Instalacion')");
    }

    // --- 5. Finalizar Transacción ---
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Camión y filtros iniciales registrados con éxito. ID Camión: ' . $nuevo_camion_id]);

} catch (Exception $e) {
    // Si algo falló, revertir TODOS los cambios
    $conn->rollback();
    http_response_code(500);
    // Verificar si es un error de duplicado (ej. VIN o Placas)
    if ($conn->errno == 1062) {
        echo json_encode(['success' => false, 'message' => 'Error: Ya existe un camión con ese VIN, Placas o N° Económico.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
    }
}

$conn->close();
?>