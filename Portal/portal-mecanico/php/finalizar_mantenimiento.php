<?php
/*
* Portal/portal-mecanico/php/finalizar_mantenimiento.php
* Versión Corregida: Sin duplicados y sintaxis limpia.
*/
session_start();
header('Content-Type: application/json');
require_once '../../../php/db_connect.php'; 

// 1. Seguridad
if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

// Helper para validar fotos (Regla 4 horas)
function validarFechaFoto($tmp_path) {
    if (!function_exists('exif_read_data')) return true; 
    
    $exif = @exif_read_data($tmp_path);
    if (!$exif || !isset($exif['DateTimeOriginal'])) {
        return true; 
    }

    $fechaFoto = new DateTime($exif['DateTimeOriginal']);
    $ahora = new DateTime();
    $diff = $ahora->diff($fechaFoto);
    $horas = ($diff->days * 24) + $diff->h;

    if ($horas > 4) {
        throw new Exception("Una foto es antigua (" . $fechaFoto->format('d/m/Y H:i') . "). Máximo 4 horas.");
    }
    return true;
}

$conn->begin_transaction();

try {
    // 2. Recibir Datos
    $id_entrada = $_POST['id_entrada'] ?? '';
    $id_camion = $_POST['id_camion_real'] ?? '';
    
    // Variables para actualizar el camión más tarde
    $nuevo_filtro_aceite = $_POST['nuevo_filtro_aceite'] ?? '';
    $nuevo_filtro_cent = $_POST['nuevo_filtro_centrifugo'] ?? '';
    
    // 3. Validar y Consumir Inventario (Array de series)
    $items_a_consumir = [
        'Aceite 1' => $_POST['serie_cubeta_1'] ?? '',
        'Aceite 2' => $_POST['serie_cubeta_2'] ?? '',
        'Filtro Aceite' => $nuevo_filtro_aceite,
        'Filtro Centrifugo' => $nuevo_filtro_cent
    ];

    foreach ($items_a_consumir as $tipo => $serie) {
        $serie = trim($serie);
        if (empty($serie)) throw new Exception("Falta la serie de: $tipo");

        // Buscar item disponible
        $sql = "SELECT id, estatus FROM tb_inventario_filtros WHERE numero_serie = ? LIMIT 1 FOR UPDATE";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $serie);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) throw new Exception("La serie '$serie' ($tipo) no existe en el sistema.");
        $item = $res->fetch_assoc();

        if ($item['estatus'] !== 'Disponible') {
            throw new Exception("El item '$serie' ya fue usado o dado de baja.");
        }

        // Dar Salida (Asignar al camión)
        $sql_up = "UPDATE tb_inventario_filtros SET estatus = 'Instalado', id_camion_instalado = ? WHERE id = ?";
        $stmt_up = $conn->prepare($sql_up);
        $stmt_up->bind_param("ii", $id_camion, $item['id']);
        $stmt_up->execute();
    }

    // 4. Procesar Fotos (3 archivos)
    $archivos = ['foto_viejos', 'foto_nuevos', 'foto_general'];
    $carpeta = "../../../uploads/evidencias_salidas/"; 
    if (!is_dir($carpeta)) mkdir($carpeta, 0777, true);

    foreach ($archivos as $input_name) {
        if (!isset($_FILES[$input_name]) || $_FILES[$input_name]['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Falta subir una de las fotos requeridas.");
        }
        
        $tmp = $_FILES[$input_name]['tmp_name'];
        validarFechaFoto($tmp); 

        $ext = pathinfo($_FILES[$input_name]['name'], PATHINFO_EXTENSION);
        $nombre_final = "SALIDA_" . $id_entrada . "_" . $input_name . "_" . time() . "." . $ext;
        
        if (move_uploaded_file($tmp, $carpeta . $nombre_final)) {
            $ruta_bd = "../uploads/evidencias_salidas/" . $nombre_final;
            $tipo_foto = "Salida - " . ucfirst(str_replace('foto_', '', $input_name));
            
            $sql_ins = "INSERT INTO tb_evidencias_entrada_taller (id_entrada, ruta_archivo, tipo_foto) VALUES (?, ?, ?)";
            $stmt_ins = $conn->prepare($sql_ins);
            $stmt_ins->bind_param("iss", $id_entrada, $ruta_bd, $tipo_foto);
            $stmt_ins->execute();
        } else {
            throw new Exception("Error al guardar imagen en servidor.");
        }
    }

    // 5. Actualizar Camión (Datos Críticos: Fechas y Series Nuevas)
    $hoy = date('Y-m-d');
    
    $sql_camion = "UPDATE tb_camiones SET 
                    estatus = 'Activo',
                    fecha_ult_mantenimiento = ?,
                    fecha_ult_cambio_aceite = ?,
                    fecha_ult_cambio_centrifugo = ?,
                    serie_filtro_aceite_actual = ?,
                    serie_filtro_centrifugo_actual = ?,
                    mantenimiento_requerido = 'No'
                   WHERE id = ?";
    
    $stmt_c = $conn->prepare($sql_camion);
    // Tipos: s (fecha), s (fecha), s (fecha), s (serie), s (serie), i (id)
    $stmt_c->bind_param("sssssi", $hoy, $hoy, $hoy, $nuevo_filtro_aceite, $nuevo_filtro_cent, $id_camion);
    
    if (!$stmt_c->execute()) {
        throw new Exception("Error al actualizar el camión: " . $stmt_c->error);
    }

    // 6. Cerrar Ticket de Entrada
    $sql_close = "UPDATE tb_entradas_taller SET estatus_entrada = 'Entregado' WHERE id = ?";
    $stmt_close = $conn->prepare($sql_close);
    $stmt_close->bind_param("i", $id_entrada);
    $stmt_close->execute();

    // Confirmar todo
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Mantenimiento finalizado exitosamente.']);

} catch (Exception $e) {
    // Si algo falla, deshacer todo
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>