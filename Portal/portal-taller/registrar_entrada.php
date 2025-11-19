<?php
/*
* Portal/portal-taller/php/registrar_entrada.php
* VERSIÓN CORREGIDA Y ALINEADA CON DB (v2)
*/
session_start();
header('Content-Type: application/json');
require_once '../../php/db_connect.php';

$id_usuario = $_SESSION['user_id']; // El recepcionista
$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Método inválido');

    $conn->begin_transaction();

    // 1. Datos del Formulario
    // Nota: Usamos '??' para evitar errores si un campo viene vacío
    $id_camion = $_POST['id_camion_seleccionado'] ?? null;
    $km_llegada = $_POST['kilometraje_entrada'] ?? 0;
    $combustible = $_POST['nivel_combustible'] ?? '';
    $tipo_mto = $_POST['tipo_servicio'] ?? '';
    $obs = $_POST['observaciones'] ?? '';
    
    if (!$id_camion) throw new Exception("No se seleccionó ningún camión.");

    // Conductores
    $id_cond_asignado = !empty($_POST['id_conductor_asignado_hidden']) ? $_POST['id_conductor_asignado_hidden'] : null;
    $id_cond_entrega = !empty($_POST['id_conductor_entrega']) ? $_POST['id_conductor_entrega'] : null;
    
    // Validación de Conductor (Alerta)
    $alerta_cond = ($id_cond_asignado != $id_cond_entrega && $id_cond_entrega != null) ? 'Si' : 'No';

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
        // Invertimos lógica: diff da positivo si fecha es futuro. 
        // Si fecha estimada es futuro (falta), diff es "días que faltan". Llegó temprano (negativo para nosotros).
        // Si fecha estimada es pasado, diff es "días que pasaron". Llegó tarde.
        
        // Simplificación: Si hoy > estimada = Tarde. Si hoy < estimada = Temprano.
        if ($hoy > $fecha_est) {
            $dias_dif = $diferencia->days; // Tarde
        } else {
            $dias_dif = -1 * $diferencia->days; // Temprano
        }

        if ($dias_dif > 7) $clasificacion = 'Tarde';
        elseif ($dias_dif < -7) $clasificacion = 'Anticipado';
        else $clasificacion = 'A Tiempo';
    }

    // 3. Insertar Entrada
    // CORRECCIÓN: Usamos los nombres REALES de tu base de datos 'flecha_roja_db'
    $folio = "ENT-" . date('Y') . "-" . time(); 
    $id_taller = 1; // Default: Magdalena (o podrías recibirlo del form)

    $sql_insert = "INSERT INTO tb_entradas_taller 
        (folio_entrada, id_camion, id_recepcionista, id_taller, kilometraje_entrada, nivel_combustible, 
         tipo_mantenimiento_solicitado, id_conductor_asignado, id_conductor_entrega, alerta_conductor,
         entrada_vs_programada, dias_desviacion, observaciones_recepcion)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql_insert);
    // Tipos: s (string), i (int), i, i, d (double), s, s, i, i, s, s, i, s
    $stmt->bind_param("siiidssiissss", 
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
        $obs
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Error al guardar entrada: " . $stmt->error);
    }
    $id_entrada = $conn->insert_id;

    // 4. Actualizar Camión a "En Taller"
    $conn->query("UPDATE tb_camiones SET estatus = 'En Taller' WHERE id = $id_camion");

    // 5. Guardar Foto (Si existe)
    if (isset($_FILES['foto-camion']) && $_FILES['foto-camion']['error'] == 0) {
        $ext = pathinfo($_FILES['foto-camion']['name'], PATHINFO_EXTENSION);
        $nombre_archivo = "evidencia_entrada_" . $id_entrada . "_" . time() . "." . $ext;
        
        // CORRECCIÓN: Ruta relativa desde 'portal-taller/php/' hacia 'uploads/'
        $ruta_destino = "../../../uploads/evidencias/" . $nombre_archivo; 
        
        // Aseguramos que el directorio existe
        if (!is_dir(dirname($ruta_destino))) {
            mkdir(dirname($ruta_destino), 0777, true);
        }
        
        if (move_uploaded_file($_FILES['foto-camion']['tmp_name'], $ruta_destino)) {
            // CORRECCIÓN: Nombre de tabla y columna correctos (tb_evidencias_entrada_taller)
            $sql_foto = "INSERT INTO tb_evidencias_entrada_taller (id_entrada_taller, ruta_archivo, descripcion) VALUES (?, ?, 'Evidencia de Ingreso')";
            $stmt_foto = $conn->prepare($sql_foto);
            $stmt_foto->bind_param("is", $id_entrada, $nombre_archivo);
            $stmt_foto->execute();
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