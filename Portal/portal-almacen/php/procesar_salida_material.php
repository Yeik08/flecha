<?php
/*
* Portal/portal-almacen/php/procesar_salida_material.php
* VERSIÓN FINAL: Validación de Tipos + Fotos Seguras (Anti-WhatsApp) + Hash
*/
session_start();
header('Content-Type: application/json');
require_once '../../../php/db_connect.php';

if (!isset($_SESSION['loggedin']) || ($_SESSION['role_id'] != 6 && $_SESSION['role_id'] != 1)) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']); exit;
}

// Función auxiliar para metadatos (Igual que en mecánico)
function extraerMetadatos($ruta_temporal) {
    $meta = ['fecha' => null, 'json' => null];
    if (function_exists('exif_read_data')) {
        $exif = @exif_read_data($ruta_temporal);
        if ($exif) {
            if (isset($exif['DateTimeOriginal'])) $meta['fecha'] = $exif['DateTimeOriginal'];
            elseif (isset($exif['DateTimeDigitized'])) $meta['fecha'] = $exif['DateTimeDigitized'];
            elseif (isset($exif['DateTime'])) $meta['fecha'] = $exif['DateTime'];
            
            array_walk_recursive($exif, function(&$item){ if(!mb_detect_encoding($item, 'utf-8', true)) $item = utf8_encode($item); });
            $meta['json'] = json_encode($exif);
        }
    }
    return $meta;
}

$conn->begin_transaction();


// 1.1 Consultar qué tipo de servicio es
    $sql_tipo = "SELECT proximo_servicio_tipo FROM tb_camiones WHERE id = ?";
    $stmt_tipo = $conn->prepare($sql_tipo);
    $stmt_tipo->bind_param("i", $id_camion);
    $stmt_tipo->execute();
    $datos_camion_tipo = $stmt_tipo->get_result()->fetch_assoc();
    $tipo_servicio = $datos_camion_tipo['proximo_servicio_tipo'] ?? 'Basico';

 // 1.2 Validación de Obligatoriedad
    if (empty($nuevo_aceite)) throw new Exception("El filtro de aceite es obligatorio.");
    if (empty($cubeta1) || empty($cubeta2)) throw new Exception("Las cubetas son obligatorias.");

    // Si es COMPLETO, el centrífugo es obligatorio
    if ($tipo_servicio === 'Completo' && empty($nuevo_centrifugo)) {
        throw new Exception("⛔ ERROR: Este camión requiere Servicio Completo. Falta escanear el Filtro Centrífugo.");
    }


try {
    // 1. Recibir Datos
    $id_entrada = $_POST['id_entrada'] ?? null;
    $id_camion = $_POST['id_camion'] ?? null;
    $comentarios = "Entrega de Material en Almacén"; // Descripción automática para las fotos
    
    // Inputs
    $viejo_aceite_input = trim($_POST['filtro_viejo_serie'] ?? '');
    $viejo_cent_input = trim($_POST['filtro_viejo_centrifugo_serie'] ?? '');
    
    $nuevo_aceite = trim($_POST['filtro_nuevo_serie'] ?? '');
    $nuevo_centrifugo = trim($_POST['filtro_nuevo_centrifugo'] ?? '');
    $cubeta1 = trim($_POST['cubeta_1'] ?? '');
    $cubeta2 = trim($_POST['cubeta_2'] ?? '');

    if (!$id_entrada || !$id_camion) throw new Exception("Faltan datos de la orden.");
    
    if (empty($cubeta1) || empty($cubeta2)) {
        throw new Exception("⛔ FALTAN DATOS: Es obligatorio escanear las 2 cubetas de aceite para completar la entrega.");
    }
    // =================================================================
    // 2. VALIDACIONES DE LOGICA Y TIPO
    // =================================================================

    // Función para validar que la serie corresponda al TIPO correcto en la BD
    function validarTipoFiltro($conn, $serie, $tipoEsperado, $esInventario = true) {
        if (empty($serie)) return;
        
        // Si está en inventario, consultamos tb_inventario_filtros
        if ($esInventario) {
            $sql = "SELECT c.tipo_filtro 
                    FROM tb_inventario_filtros i
                    JOIN tb_cat_filtros c ON i.id_cat_filtro = c.id
                    WHERE i.numero_serie = ? LIMIT 1";
        } else {
            // Si ya fue usado (no está activo en inventario), podríamos buscar en logs, 
            // pero para simplificar validamos contra lo que dice el catálogo si existe
            // Ojo: Para filtros "viejos" que ya salieron, asumimos que eran correctos.
            // Esta validación es crucial para los NUEVOS que van a salir.
            return; 
        }

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $serie);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($row = $res->fetch_assoc()) {
            if (strtoupper($row['tipo_filtro']) !== strtoupper($tipoEsperado)) {
                throw new Exception("⛔ ERROR DE TIPO: El filtro '$serie' es de " . strtoupper($row['tipo_filtro']) . 
                                    ", pero lo estás ingresando como " . strtoupper($tipoEsperado) . ".");
            }
        }
    }

    // A. Validar Viejos (Contra lo que tiene el camión)
    $sql_camion = "SELECT serie_filtro_aceite_actual, serie_filtro_centrifugo_actual FROM tb_camiones WHERE id = ?";
    $stmt = $conn->prepare($sql_camion);
    $stmt->bind_param("i", $id_camion);
    $stmt->execute();
    $datos_camion = $stmt->get_result()->fetch_assoc();

    if (!empty($datos_camion['serie_filtro_aceite_actual'])) {
        if (strtoupper($viejo_aceite_input) !== strtoupper($datos_camion['serie_filtro_aceite_actual'])) {
            throw new Exception("⛔ ERROR: El filtro de aceite usado NO COINCIDE con el sistema.");
        }
    }
    if (!empty($datos_camion['serie_filtro_centrifugo_actual']) && !empty($viejo_cent_input)) {
        if (strtoupper($viejo_cent_input) !== strtoupper($datos_camion['serie_filtro_centrifugo_actual'])) {
            throw new Exception("⛔ ERROR: El filtro centrífugo usado NO COINCIDE con el sistema.");
        }
    }

    // B. Validar Tipos de los NUEVOS (Crucial para no dar aceite por centrífugo)
    validarTipoFiltro($conn, $nuevo_aceite, 'Aceite');
    validarTipoFiltro($conn, $nuevo_centrifugo, 'Centrifugo');


    // =================================================================
    // 3. PROCESAMIENTO DE FOTOS (Igual que mecánico)
    // =================================================================
    $archivos = ['foto_viejos', 'foto_nuevos', 'foto_cubetas', 'foto_general'];
    $carpeta = "../../../uploads/evidencias_salidas/"; // Usamos la misma carpeta central
    if (!is_dir($carpeta)) mkdir($carpeta, 0777, true);

    foreach ($archivos as $input_name) {
        if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] === UPLOAD_ERR_OK) {
            
            $tmp = $_FILES[$input_name]['tmp_name'];
            
            // Hash (Anti-Duplicado)
            $hash_archivo = hash_file('sha256', $tmp);
            $sql_dup = "SELECT id FROM tb_evidencias_entrada_taller WHERE hash_archivo = ? LIMIT 1";
            $stmt_dup = $conn->prepare($sql_dup);
            $stmt_dup->bind_param("s", $hash_archivo);
            $stmt_dup->execute();
            if ($stmt_dup->get_result()->num_rows > 0) {
                throw new Exception("🚫 FOTO REPETIDA ($input_name): Ya existe en el sistema.");
            }

            // Metadatos (Anti-WhatsApp)
            $info_meta = extraerMetadatos($tmp);
            if (empty($info_meta['fecha'])) {
                throw new Exception("⛔ FOTO RECHAZADA ($input_name): Sin fecha original (WhatsApp/Captura).");
            }

            // Antigüedad (24h)
            $fecha_limpia = preg_replace('/^(\d{4}):(\d{2}):(\d{2})/', '$1-$2-$3', $info_meta['fecha']);
            try {
                $fechaFoto = new DateTime($fecha_limpia);
                $ahora = new DateTime();
                $horas = ($ahora->getTimestamp() - $fechaFoto->getTimestamp()) / 3600;
                if ($horas > 24) throw new Exception("⛔ FOTO ANTIGUA ($input_name): Más de 24 horas.");
            } catch (Exception $e) { if(strpos($e->getMessage(), 'FOTO')!==false) throw $e; }

            // Guardar
            $ext = pathinfo($_FILES[$input_name]['name'], PATHINFO_EXTENSION);
            $nombre_final = "ALMACEN_ENTREGA_" . $id_entrada . "_" . $input_name . "_" . time() . "." . $ext;
            
            if (move_uploaded_file($tmp, $carpeta . $nombre_final)) {
                $ruta_bd = "../uploads/evidencias_salidas/" . $nombre_final;
                $tipo_foto = "Almacén - " . ucfirst(str_replace('foto_', '', $input_name));
                
                $sql_ins = "INSERT INTO tb_evidencias_entrada_taller 
                            (id_entrada, ruta_archivo, tipo_foto, descripcion, fecha_captura, hash_archivo, metadatos_json) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt_ins = $conn->prepare($sql_ins);
                $stmt_ins->bind_param("issssss", $id_entrada, $ruta_bd, $tipo_foto, $comentarios, $info_meta['fecha'], $hash_archivo, $info_meta['json']);
                $stmt_ins->execute();
            }
        }
    }

    // =================================================================
    // 4. RESERVA Y ASIGNACIÓN DE MATERIAL (Lógica anterior)
    // =================================================================
    
    function validarYReservar($conn, $serie, $tabla, $campo_id_entrada, $id_entrada) {
        if (empty($serie)) return;

        $sql = "SELECT id, estatus FROM $tabla WHERE numero_serie = ? LIMIT 1 FOR UPDATE";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $serie);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows === 0) throw new Exception("El item '$serie' no existe en inventario.");
        $item = $res->fetch_assoc();
        
        if ($item['estatus'] !== 'Disponible') throw new Exception("El item '$serie' no está disponible (Estatus: {$item['estatus']}).");

        $sql_up = "UPDATE $tabla SET estatus = 'Asignado' WHERE id = ?";
        $stmt_up = $conn->prepare($sql_up);
        $stmt_up->bind_param("i", $item['id']);
        $stmt_up->execute();

        $sql_ticket = "UPDATE tb_entradas_taller SET $campo_id_entrada = ? WHERE id = ?";
        $stmt_t = $conn->prepare($sql_ticket);
        $stmt_t->bind_param("si", $serie, $id_entrada);
        $stmt_t->execute();
    }

    validarYReservar($conn, $nuevo_aceite, 'tb_inventario_filtros', 'filtro_aceite_entregado', $id_entrada);
    validarYReservar($conn, $nuevo_centrifugo, 'tb_inventario_filtros', 'filtro_centrifugo_entregado', $id_entrada);
    validarYReservar($conn, $cubeta1, 'tb_inventario_lubricantes', 'cubeta_1_entregada', $id_entrada);
    validarYReservar($conn, $cubeta2, 'tb_inventario_lubricantes', 'cubeta_2_entregada', $id_entrada);

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Material validado, fotos guardadas y entrega registrada.']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>