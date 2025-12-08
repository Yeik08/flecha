<?php
/*
* Portal/portal-mecanico/php/finalizar_mantenimiento.php
* VERSIÓN FINAL: CUBETAS SERIADAS + FILTROS
*/
session_start();
header('Content-Type: application/json');
require_once '../../../php/db_connect.php'; 

// 1. Seguridad
if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

// Helper validación fotos
function validarFechaFoto($tmp_path) {
    if (!function_exists('exif_read_data')) return true; 
    $exif = @exif_read_data($tmp_path);
    if (!$exif || !isset($exif['DateTimeOriginal'])) return true; // Si no tiene fecha, pasa (política flexible)

    $fechaFoto = new DateTime($exif['DateTimeOriginal']);
    $ahora = new DateTime();
    $diff = $ahora->diff($fechaFoto);
    $horas = ($diff->days * 24) + $diff->h;

    // Permitimos hasta 24 horas de antigüedad
    if ($horas > 24) {
        throw new Exception("La evidencia es demasiado antigua (" . $fechaFoto->format('d/m/Y H:i') . ").");
    }
    return true;
}

$conn->begin_transaction();

try {
    // 2. Recibir Datos Básicos
    $id_entrada = $_POST['id_entrada'] ?? '';
    $id_camion = $_POST['id_camion_real'] ?? '';
    $comentarios = $_POST['comentarios'] ?? '';
    
    if(empty($id_entrada) || empty($id_camion)) {
        throw new Exception("Faltan identificadores del servicio.");
    }

    // =========================================================
    // 3. PROCESAMIENTO DE CUBETAS DE ACEITE (Por Serie)
    // =========================================================
    $cubetas = [
        trim($_POST['serie_cubeta_1'] ?? ''),
        trim($_POST['serie_cubeta_2'] ?? '')
    ];

    foreach ($cubetas as $i => $serie) {
        if (empty($serie)) continue; // Si dejó vacío, saltamos (pero debería ser required en HTML)

        // A. Verificar existencia
        $sql_check = "SELECT id, estatus FROM tb_inventario_lubricantes WHERE numero_serie = ? LIMIT 1 FOR UPDATE";
        $stmt = $conn->prepare($sql_check);
        $stmt->bind_param("s", $serie);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) {
            throw new Exception("La cubeta con serie '$serie' no existe en el sistema.");
        }

        $item = $res->fetch_assoc();
        if ($item['estatus'] !== 'Disponible') {
            throw new Exception("La cubeta '$serie' ya fue usada o dada de baja.");
        }

        // B. Consumir (Marcar como Usado)
        // Opcional: Podrías agregar una columna 'id_camion_uso' a esta tabla para historial
        $sql_use = "UPDATE tb_inventario_lubricantes SET estatus = 'Usado' WHERE id = ?";
        $stmt_use = $conn->prepare($sql_use);
        $stmt_use->bind_param("i", $item['id']);
        $stmt_use->execute();
    }

    // =========================================================
    // 4. PROCESAMIENTO DE FILTROS (Por Serie)
    // =========================================================
    $filtros = [
        'Aceite' => $_POST['nuevo_filtro_aceite'] ?? '',
        'Centrifugo' => $_POST['nuevo_filtro_centrifugo'] ?? ''
    ];

    foreach ($filtros as $tipo => $serie) {
        $serie = trim($serie);
        if (!empty($serie)) {
            $sql = "SELECT id, estatus FROM tb_inventario_filtros WHERE numero_serie = ? LIMIT 1 FOR UPDATE";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $serie);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res->num_rows === 0) throw new Exception("El filtro '$serie' ($tipo) no existe.");
            $item = $res->fetch_assoc();

            if ($item['estatus'] !== 'Disponible') throw new Exception("El filtro '$serie' no está disponible.");

            // Instalar en el camión (inventario)
            $sql_up = "UPDATE tb_inventario_filtros SET estatus = 'Instalado', id_camion_instalado = ? WHERE id = ?";
            $stmt_up = $conn->prepare($sql_up);
            $stmt_up->bind_param("ii", $id_camion, $item['id']);
            $stmt_up->execute();
        }
    }

    // =========================================================
    // 5. PROCESAR FOTOS (Evidencia Salida)
    // =========================================================
    $archivos = ['foto_viejos', 'foto_nuevos', 'foto_general'];
    $carpeta = "../../../uploads/evidencias_salidas/"; 
    if (!is_dir($carpeta)) mkdir($carpeta, 0777, true);

    foreach ($archivos as $input_name) {
        if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] === UPLOAD_ERR_OK) {
            
            $tmp = $_FILES[$input_name]['tmp_name'];
            validarFechaFoto($tmp); // Validación de tiempo

            $ext = pathinfo($_FILES[$input_name]['name'], PATHINFO_EXTENSION);
            $nombre_final = "SALIDA_" . $id_entrada . "_" . $input_name . "_" . time() . "." . $ext;
            
            if (move_uploaded_file($tmp, $carpeta . $nombre_final)) {
                $ruta_bd = "../uploads/evidencias_salidas/" . $nombre_final;
                $tipo_foto = "Salida - " . ucfirst(str_replace('foto_', '', $input_name));
                
                $sql_ins = "INSERT INTO tb_evidencias_entrada_taller (id_entrada, ruta_archivo, tipo_foto, descripcion) VALUES (?, ?, ?, ?)";
                $stmt_ins = $conn->prepare($sql_ins);
                $stmt_ins->bind_param("isss", $id_entrada, $ruta_bd, $tipo_foto, $comentarios);
                $stmt_ins->execute();
            }
        }
    }

    // =========================================================
    // 6. ACTUALIZAR CAMIÓN Y CERRAR TICKET
    // =========================================================
    $hoy = date('Y-m-d');
    
    // Actualizamos fechas y series en el camión
    $sql_camion = "UPDATE tb_camiones SET estatus = 'Activo', mantenimiento_requerido = 'No', fecha_ult_mantenimiento = ?";
    $params_types = "s";
    $params_vals = [$hoy];

    // Si cambió filtro aceite
    if (!empty($filtros['Aceite'])) {
        $sql_camion .= ", fecha_ult_cambio_aceite = ?, serie_filtro_aceite_actual = ?";
        $params_types .= "ss";
        $params_vals[] = $hoy;
        $params_vals[] = $filtros['Aceite'];
    }
    // Si cambió filtro centrífugo
    if (!empty($filtros['Centrifugo'])) {
        $sql_camion .= ", fecha_ult_cambio_centrifugo = ?, serie_filtro_centrifugo_actual = ?";
        $params_types .= "ss";
        $params_vals[] = $hoy;
        $params_vals[] = $filtros['Centrifugo'];
    }

    $sql_camion .= " WHERE id = ?";
    $params_types .= "i";
    $params_vals[] = $id_camion;

    $stmt_c = $conn->prepare($sql_camion);
    $stmt_c->bind_param($params_types, ...$params_vals);
    $stmt_c->execute();

    // Cambiar estatus de la entrada a 'Listo para Entrega' o 'Entregado'
    // Asumiremos 'Listo para Entrega' si hay un flujo de calidad, o 'Entregado' si el mecánico cierra todo.
    // Usaremos 'Entregado' para tu MVP.
    $sql_close = "UPDATE tb_entradas_taller SET estatus_entrada = 'Entregado' WHERE id = ?";
    $stmt_close = $conn->prepare($sql_close);
    $stmt_close->bind_param("i", $id_entrada);
    $stmt_close->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Mantenimiento registrado. Cubetas y filtros descontados.']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>