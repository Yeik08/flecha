<?php
session_start();
header('Content-Type: application/json');
require_once '../../php/db_connect.php';

$id_usuario = $_SESSION['user_id']; // El recepcionista
$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Método inválido');

    $conn->begin_transaction();

    // 1. Datos del Formulario
    $id_camion = $_POST['id_camion_seleccionado'];
    $km_llegada = $_POST['kilometraje_entrada'];
    $combustible = $_POST['nivel_combustible'];
    $tipo_mto = $_POST['tipo_servicio'];
    $obs = $_POST['observaciones'];
    
    // Conductores
    $id_cond_asignado = $_POST['id_conductor_asignado_hidden'] ?: null;
    $id_cond_entrega = $_POST['id_conductor_entrega'] ?: null; // El que seleccionó en el autocompletado
    
    // Validación de Conductor (Alerta)
    $alerta_cond = ($id_cond_asignado != $id_cond_entrega) ? 'Si' : 'No';

    // 2. Cálculo de Tiempos (Temprano/Tarde)
    $sql_fechas = "SELECT fecha_estimada_mantenimiento FROM tb_camiones WHERE id = ?";
    $stmt = $conn->prepare($sql_fechas);
    $stmt->bind_param("i", $id_camion);
    $stmt->execute();
    $res_f = $stmt->get_result()->fetch_assoc();
    
    $clasificacion = 'No Programado';
    $dias_dif = 0;

    if ($res_f && !empty($res_f['fecha_estimada_mantenimiento'])) {
        $fecha_est = new DateTime($res_f['fecha_estimada_mantenimiento']);
        $hoy = new DateTime();
        $diferencia = $hoy->diff($fecha_est);
        $dias_dif = (int)$diferencia->format('%r%a') * -1; // Invertimos: + es tarde, - es temprano

        if ($dias_dif > 7) $clasificacion = 'Tarde';
        elseif ($dias_dif < -7) $clasificacion = 'Anticipado';
        else $clasificacion = 'A Tiempo';
    }

    // 3. Insertar Entrada
    $folio = "ENT-" . date('Y') . "-" . time(); // Folio simple
    $sql_insert = "INSERT INTO tb_entradas_taller 
        (folio, id_camion, id_recepcionista, kilometraje_llegada, nivel_combustible, 
         tipo_mantenimiento, id_conductor_asignado, id_conductor_entrega, alerta_conductor,
         clasificacion_tiempo, dias_diferencia, observaciones)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql_insert);
    $stmt->bind_param("siidssiiisis", $folio, $id_camion, $id_usuario, $km_llegada, $combustible, 
                      $tipo_mto, $id_cond_asignado, $id_cond_entrega, $alerta_cond, 
                      $clasificacion, $dias_dif, $obs);
    $stmt->execute();
    $id_entrada = $conn->insert_id;

    // 4. Actualizar Camión a "En Taller"
    $conn->query("UPDATE tb_camiones SET estatus = 'En Taller' WHERE id = $id_camion");

    // 5. Guardar Foto (Si existe)
    if (isset($_FILES['foto-camion']) && $_FILES['foto-camion']['error'] == 0) {
        $ext = pathinfo($_FILES['foto-camion']['name'], PATHINFO_EXTENSION);
        $nombre_archivo = "evidencia_" . $id_entrada . "_" . time() . "." . $ext;
        $ruta_destino = "../../uploads/evidencias/" . $nombre_archivo; // Asegúrate de crear esta carpeta
        
        if (move_uploaded_file($_FILES['foto-camion']['tmp_name'], $ruta_destino)) {
            $conn->query("INSERT INTO tb_entradas_evidencias (id_entrada, ruta_archivo) VALUES ($id_entrada, '$nombre_archivo')");
        }
    }

    $conn->commit();
    $response['success'] = true;
    $response['message'] = "Entrada registrada correctamente. Folio: " . $folio;

} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = "Error: " . $e->getMessage();
}

echo json_encode($response);
?>