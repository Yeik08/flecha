<?php
/*
* Portal/portal-taller/php/registrar_entrada.php
* VERSIÓN SEGURA V3:
* - Valida el kilometraje pero NO actualiza el total maestro en tb_camiones
* para evitar conflicto de doble suma con la telemetría mensual.
*/

session_start();
header('Content-Type: application/json');
require_once '../../php/db_connect.php';

// Seguridad
if (!isset($_SESSION['loggedin']) || ($_SESSION['role_id'] != 5 && $_SESSION['role_id'] != 1)) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']); exit;
}

$id_usuario = $_SESSION['user_id'];
$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Método inválido');

    $conn->begin_transaction();

    // 1. Datos
    $id_camion = $_POST['id_camion_seleccionado'] ?? null;
    $km_llegada = floatval($_POST['kilometraje_entrada'] ?? 0);
    $combustible = $_POST['nivel_combustible'] ?? '';
    $tipo_mto = $_POST['tipo_servicio'] ?? '';
    $obs = $_POST['observaciones_recepcion'] ?? '';
    $fecha_ingreso = $_POST['fecha_ingreso'] ?? date('Y-m-d H:i:s');
    
    if (!$id_camion) throw new Exception("No se seleccionó ningún camión.");
    if ($km_llegada <= 0) throw new Exception("El kilometraje debe ser mayor a 0.");

    // --- VALIDACIÓN DE KILOMETRAJE ---
    // Consultamos el actual solo para validar que no sea menor (posible error de dedo o fraude)
    $sql_check = "SELECT kilometraje_total, fecha_estimada_mantenimiento FROM tb_camiones WHERE id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $id_camion);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result()->fetch_assoc();
    
    $km_actual_sistema = floatval($res_check['kilometraje_total']);

    if ($km_llegada < $km_actual_sistema) {
        throw new Exception("Error: El kilometraje ingresado ($km_llegada) es MENOR al registrado en sistema ($km_actual_sistema). Verifique el odómetro.");
    }
    // ----------------------------------------

    // Conductores
    $id_cond_asignado = !empty($_POST['id_conductor_asignado_hidden']) ? $_POST['id_conductor_asignado_hidden'] : null;
    $id_cond_entrega = !empty($_POST['id_conductor_entrega']) ? $_POST['id_conductor_entrega'] : null; 
    
    if ($id_cond_entrega && !is_numeric($id_cond_entrega)) {
        $stmt_c = $conn->prepare("SELECT id_empleado FROM empleados WHERE id_interno = ? LIMIT 1");
        $stmt_c->bind_param("s", $id_cond_entrega);
        $stmt_c->execute();
        $res_c = $stmt_c->get_result();
        if($row_c = $res_c->fetch_assoc()) {
            $id_cond_entrega = $row_c['id_empleado'];
        } else {
             $id_cond_entrega = null; 
        }
    }

    $alerta_cond = ($id_cond_asignado && $id_cond_entrega && $id_cond_asignado != $id_cond_entrega) ? 'Si' : 'No';

    // Lógica de Tiempo
    $clasificacion = 'No Programado';
    $dias_dif = 0;
    if ($res_check && !empty($res_check['fecha_estimada_mantenimiento'])) {
        $fecha_est = new DateTime($res_check['fecha_estimada_mantenimiento']);
        $fecha_real = new DateTime($fecha_ingreso);
        $intervalo = $fecha_est->diff($fecha_real);
        $dias = (int)$intervalo->format('%r%a');
        $dias_dif = $dias;

        if ($dias < -7) $clasificacion = 'Anticipado';
        elseif ($dias > 7) $clasificacion = 'Tarde';
        else $clasificacion = 'A Tiempo';
    }

    // 3. Insertar Entrada (Aquí SÍ guardamos el KM de llegada para el historial del ticket)
    $folio = "ENT-" . date('ymd') . "-" . rand(1000, 9999);
    $id_taller = 1; 

    $sql_insert = "INSERT INTO tb_entradas_taller 
        (folio, id_camion, id_recepcionista, id_taller, kilometraje_entrada, nivel_combustible, 
         tipo_mantenimiento_solicitado, id_conductor_asignado, id_conductor_entrega, alerta_conductor,
         clasificacion_tiempo, dias_desviacion, observaciones_recepcion, fecha_ingreso)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql_insert);
    $stmt->bind_param("siiidssiisssis", 
        $folio, $id_camion, $id_usuario, $id_taller, $km_llegada, $combustible, 
        $tipo_mto, $id_cond_asignado, $id_cond_entrega, $alerta_cond, 
        $clasificacion, $dias_dif, $obs, $fecha_ingreso
    );
    
    if (!$stmt->execute()) throw new Exception("Error al guardar entrada: " . $stmt->error);
    $id_entrada = $conn->insert_id;

    // 4. Actualizar Estatus del Camión
    // CORRECCIÓN CRÍTICA: Ya NO actualizamos 'kilometraje_total' aquí.
    // Solo cambiamos el estatus a 'En Taller'.
    $sql_up = "UPDATE tb_camiones SET estatus = 'En Taller' WHERE id = ?";
    $stmt_up = $conn->prepare($sql_up);
    $stmt_up->bind_param("i", $id_camion);
    $stmt_up->execute();

    // 5. Guardar Foto
    if (isset($_FILES['foto_entrada']) && $_FILES['foto_entrada']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['foto_entrada']['name'], PATHINFO_EXTENSION);
        $nombre_foto = "EVIDENCIA_" . $folio . "_" . time() . "." . $ext;
        $carpeta = "../../uploads/evidencias_entradas/";
        if (!is_dir($carpeta)) mkdir($carpeta, 0777, true);
        
        if (move_uploaded_file($_FILES['foto_entrada']['tmp_name'], $carpeta . $nombre_foto)) {
            $ruta_bd = "../uploads/evidencias_entradas/" . $nombre_foto;
            $conn->query("INSERT INTO tb_evidencias_entrada_taller (id_entrada, ruta_archivo) VALUES ($id_entrada, '$ruta_bd')");
        }
    }

    $conn->commit();
    $response['success'] = true;
    $response['message'] = "Entrada registrada correctamente. Folio: " . $folio;

} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>