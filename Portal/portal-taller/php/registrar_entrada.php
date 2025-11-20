<?php
/*
* Portal/portal-taller/php/registrar_entrada.php
* VERSIÓN DEFINITIVA V6:
* - Ruta de conexión corregida (../../../php/db_connect.php).
* - Tipos de datos en bind_param corregidos (Observaciones es 's', no 'i').
*/

// Apagar errores visuales para proteger JSON
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

session_start();
header('Content-Type: application/json');

function enviarRespuesta($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

try {
    // 1. Seguridad
    if (!isset($_SESSION['loggedin']) || ($_SESSION['role_id'] != 5 && $_SESSION['role_id'] != 1)) {
        enviarRespuesta(false, 'Acceso no autorizado.');
    }

    // --- CORRECCIÓN DE RUTA CRÍTICA ---
    // Subimos 3 niveles: php/ -> portal-taller/ -> Portal/ -> Raíz
    require_once '../../../php/db_connect.php'; 
    
    if ($conn === false) {
        enviarRespuesta(false, 'Error de conexión a la base de datos.');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        enviarRespuesta(false, 'Método inválido.');
    }

    $id_usuario = $_SESSION['user_id'];
    $conn->begin_transaction();

    // 2. Recibir Datos
    $id_camion = $_POST['id_camion_seleccionado'] ?? null;
    $km_llegada = floatval($_POST['kilometraje_entrada'] ?? 0);
    $combustible = $_POST['nivel_combustible'] ?? 'No especificado';
    $id_taller = $_POST['id_taller'] ?? 1; 
    $tipo_mto = $_POST['tipo_mantenimiento'] ?? 'General';
    $obs = $_POST['observaciones_recepcion'] ?? '';
    $fecha_ingreso = !empty($_POST['fecha_ingreso']) ? $_POST['fecha_ingreso'] : date('Y-m-d H:i:s');

    if (!$id_camion) {
        throw new Exception("No se seleccionó ningún camión.");
    }

    // 3. Lógica de Negocio y Conductores
    $id_cond_asignado = !empty($_POST['id_conductor_asignado_hidden']) ? $_POST['id_conductor_asignado_hidden'] : null;
    $id_cond_entrega = !empty($_POST['id_conductor_entrega']) ? $_POST['id_conductor_entrega'] : null;
    
    // Buscar ID si viene nombre
    if ($id_cond_entrega && !is_numeric($id_cond_entrega)) {
        $stmt_c = $conn->prepare("SELECT id_empleado FROM empleados WHERE id_interno = ? OR nombre LIKE ? LIMIT 1");
        $like_name = "%$id_cond_entrega%";
        $stmt_c->bind_param("ss", $id_cond_entrega, $like_name);
        $stmt_c->execute();
        $res_c = $stmt_c->get_result();
        if($row_c = $res_c->fetch_assoc()) {
            $id_cond_entrega = $row_c['id_empleado'];
        } else {
             $id_cond_entrega = null; 
        }
    }
    $alerta_cond = ($id_cond_asignado && $id_cond_entrega && $id_cond_asignado != $id_cond_entrega) ? 'Si' : 'No';

    // Tiempos
    $sql_fechas = "SELECT fecha_estimada_mantenimiento FROM tb_camiones WHERE id = ?";
    $stmt = $conn->prepare($sql_fechas);
    $stmt->bind_param("i", $id_camion);
    $stmt->execute();
    $res_f = $stmt->get_result()->fetch_assoc();
    
    $clasificacion = 'No Programado';
    $dias_dif = 0;

    if ($res_f && !empty($res_f['fecha_estimada_mantenimiento'])) {
        $fecha_est = new DateTime($res_f['fecha_estimada_mantenimiento']);
        $fecha_real = new DateTime($fecha_ingreso);
        $intervalo = $fecha_est->diff($fecha_real);
        $dias_dif = (int)$intervalo->format('%r%a');

        if ($dias_dif < -7) $clasificacion = 'Anticipado';
        elseif ($dias_dif > 7) $clasificacion = 'Tarde';
        else $clasificacion = 'A Tiempo';
    }

    // 4. Insertar Entrada
    $folio = "ENT-" . date('ymd') . "-" . rand(1000, 9999);


    $sql_insert = "INSERT INTO tb_entradas_taller (
        folio, id_camion, id_recepcionista, id_taller, kilometraje_entrada, nivel_combustible, 
        tipo_mantenimiento_solicitado, id_conductor_asignado, id_conductor_entrega, alerta_conductor,
        clasificacion_tiempo, dias_desviacion, observaciones_recepcion, fecha_ingreso
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql_insert);
    if (!$stmt) throw new Exception("Error preparando consulta: " . $conn->error);

    // --- CORRECCIÓN DE TIPOS DE DATO ---
    // Antes: "siiidssiisssis" (La penúltima 'i' causaba error en observaciones)
    // Ahora: "siiidssiisssss" (La penúltima es 's' para texto)
    $stmt->bind_param("siiidssiisssss", 
        $folio, 
        $id_camion, 
        $id_usuario, 
        $id_taller, 
        $km_llegada, 
        $combustible, 
        $tipo_mto, 
        $id_cond_asignado, 
        $id_cond_entrega, 
        $alerta_cond, 
        $clasificacion, 
        $dias_dif, 
        $obs,             // Este ahora es 's'
        $fecha_ingreso
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Error al guardar en BD: " . $stmt->error);
    }
    $id_entrada = $conn->insert_id;

    // 5. Actualizar Estatus Camión
    $stmt_up = $conn->prepare("UPDATE tb_camiones SET estatus = 'En Taller' WHERE id = ?");
    $stmt_up->bind_param("i", $id_camion);
    $stmt_up->execute();

    // 6. Guardar Foto
    if (isset($_FILES['foto_entrada']) && $_FILES['foto_entrada']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['foto_entrada']['name'], PATHINFO_EXTENSION);
        $nombre_foto = "EVIDENCIA_" . $folio . "_" . time() . "." . $ext;
        
        // Subimos 3 niveles para llegar a uploads/
        $carpeta = "../../../uploads/evidencias_entradas/";
        if (!is_dir($carpeta)) mkdir($carpeta, 0777, true);
        
        if (move_uploaded_file($_FILES['foto_entrada']['tmp_name'], $carpeta . $nombre_foto)) {
            $ruta_bd = "../uploads/evidencias_entradas/" . $nombre_foto;
            $stmt_foto = $conn->prepare("INSERT INTO tb_evidencias_entrada_taller (id_entrada, ruta_archivo) VALUES (?, ?)");
            $stmt_foto->bind_param("is", $id_entrada, $ruta_bd);
            $stmt_foto->execute();
        }
    }

    $conn->commit();
    enviarRespuesta(true, "Entrada registrada. Folio: " . $folio);

} catch (Exception $e) {
    $conn->rollback();
    enviarRespuesta(false, "Error del Sistema: " . $e->getMessage());
}
$conn->close();
?>