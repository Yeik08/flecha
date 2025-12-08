<?php
/*
* Portal/portal-almacen/php/procesar_salida_material.php
* VERSIÓN FINAL BLINDADA:
* - Validación de Tipos de Filtro.
* - Validación de Mezcla de Aceites (NUEVO).
* - Fotos Seguras (Anti-WhatsApp) + Hash.
*/
session_start();
header('Content-Type: application/json');
require_once '../../../php/db_connect.php';

if (!isset($_SESSION['loggedin']) || ($_SESSION['role_id'] != 6 && $_SESSION['role_id'] != 1)) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']); exit;
}

// Función auxiliar para metadatos
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

try {
    // 1. RECIBIR DATOS
    $id_entrada = $_POST['id_entrada'] ?? null;
    $id_camion = $_POST['id_camion'] ?? null;
    $comentarios = "Entrega de Material en Almacén";
    
    // Inputs
    $viejo_aceite_input = trim($_POST['filtro_viejo_serie'] ?? '');
    $viejo_cent_input = trim($_POST['filtro_viejo_centrifugo_serie'] ?? '');
    
    $nuevo_aceite = trim($_POST['filtro_nuevo_serie'] ?? '');
    $nuevo_centrifugo = trim($_POST['filtro_nuevo_centrifugo'] ?? '');
    $cubeta1 = trim($_POST['cubeta_1'] ?? '');
    $cubeta2 = trim($_POST['cubeta_2'] ?? '');

    // Validación básica
    if (!$id_entrada || !$id_camion) {
        throw new Exception("Faltan datos de la orden.");
    }

    // =================================================================
    // 2. VALIDACIONES DE NEGOCIO
    // =================================================================

    // 2.1 Consultar Tipo de Servicio
    $sql_tipo = "SELECT proximo_servicio_tipo FROM tb_camiones WHERE id = ?";
    $stmt_tipo = $conn->prepare($sql_tipo);
    $stmt_tipo->bind_param("i", $id_camion);
    $stmt_tipo->execute();
    $datos_camion_tipo = $stmt_tipo->get_result()->fetch_assoc();
    $tipo_servicio = $datos_camion_tipo['proximo_servicio_tipo'] ?? 'Basico';

    // 2.2 Validación de Obligatoriedad
    if (empty($nuevo_aceite)) throw new Exception("El filtro de aceite es obligatorio.");
    if (empty($cubeta1) || empty($cubeta2)) throw new Exception("Es obligatorio escanear las 2 cubetas de aceite.");

    if ($tipo_servicio === 'Completo' && empty($nuevo_centrifugo)) {
        throw new Exception("⛔ ERROR: Este camión requiere Servicio Completo. Falta escanear el Filtro Centrífugo.");
    }
$sql_lub = "SELECT lubricante_sugerido FROM tb_camiones WHERE id = ?";
    $stmt_lub = $conn->prepare($sql_lub);
    $stmt_lub->bind_param("i", $id_camion);
    $stmt_lub->execute();
    $dato_lub = $stmt_lub->get_result()->fetch_assoc();
    $aceite_requerido = strtoupper($dato_lub['lubricante_sugerido'] ?? '');

    // Función para validar que la cubeta escaneada sea del tipo requerido
    function validarTipoCubetaContraCamion($conn, $serie_cubeta, $tipo_requerido) {
        $sql = "SELECT c.nombre_producto 
                FROM tb_inventario_lubricantes i
                JOIN tb_cat_lubricantes c ON i.id_cat_lubricante = c.id
                WHERE i.numero_serie = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $serie_cubeta);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($row = $res->fetch_assoc()) {
            $tipo_cubeta = strtoupper($row['nombre_producto']);
            
            // Comparación estricta (usamos strpos para flexibilidad si hay variantes de nombre)
            if (strpos($tipo_cubeta, $tipo_requerido) === false && strpos($tipo_requerido, $tipo_cubeta) === false) {
                throw new Exception("⛔ ERROR DE VISCOSIDAD:\n" .
                    "Este camión requiere: $tipo_requerido\n" .
                    "La cubeta escaneada ($serie_cubeta) es: $tipo_cubeta\n\n" .
                    "No puedes entregar este aceite.");
            }
        }
    }

    // Ejecutar validación contra el camión
    if (!empty($aceite_requerido)) {
        validarTipoCubetaContraCamion($conn, $cubeta1, $aceite_requerido);
        validarTipoCubetaContraCamion($conn, $cubeta2, $aceite_requerido);
    }










    
    // 2.3 VALIDACIÓN DE MEZCLA DE ACEITES (NUEVO CANDADO
    function validarMismoTipoAceite($conn, $serie1, $serie2) {
        $sql = "SELECT i.numero_serie, i.id_cat_lubricante, c.nombre_producto 
                FROM tb_inventario_lubricantes i 
                JOIN tb_cat_lubricantes c ON i.id_cat_lubricante = c.id 
                WHERE i.numero_serie = ?";
        
        $stmt = $conn->prepare($sql);
        
        // Obtener info Cubeta 1
        $stmt->bind_param("s", $serie1);
        $stmt->execute();
        $res1 = $stmt->get_result();
        if($res1->num_rows === 0) throw new Exception("La cubeta '$serie1' no existe en inventario.");
        $info1 = $res1->fetch_assoc();

        // Obtener info Cubeta 2
        $stmt->bind_param("s", $serie2);
        $stmt->execute();
        $res2 = $stmt->get_result();
        if($res2->num_rows === 0) throw new Exception("La cubeta '$serie2' no existe en inventario.");
        $info2 = $res2->fetch_assoc();

        // Comparar tipos (IDs de categoría)
        if ($info1['id_cat_lubricante'] != $info2['id_cat_lubricante']) {
            throw new Exception("⛔ ERROR CRÍTICO DE MEZCLA:\nEstás intentando entregar dos aceites diferentes.\n\n" . 
                "• Cubeta 1 ($serie1): " . $info1['nombre_producto'] . "\n" .
                "• Cubeta 2 ($serie2): " . $info2['nombre_producto'] . "\n\n" .
                "Deben ser del mismo tipo.");
        }
    }

    // Ejecutar validación de mezcla
    validarMismoTipoAceite($conn, $cubeta1, $cubeta2);


    // 2.4 Función para validar Tipos de Filtros
    function validarTipoFiltro($conn, $serie, $tipoEsperado, $esInventario = true) {
        if (empty($serie)) return;
        
        if ($esInventario) {
            $sql = "SELECT c.tipo_filtro 
                    FROM tb_inventario_filtros i
                    JOIN tb_cat_filtros c ON i.id_cat_filtro = c.id
                    WHERE i.numero_serie = ? LIMIT 1";
        } else { return; }

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

    // 2.5 Validar Coincidencia de Filtros Viejos
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
    
    if ($tipo_servicio === 'Completo' && !empty($datos_camion['serie_filtro_centrifugo_actual'])) {
        if (empty($viejo_cent_input) || strtoupper($viejo_cent_input) !== strtoupper($datos_camion['serie_filtro_centrifugo_actual'])) {
            throw new Exception("⛔ ERROR: El filtro centrífugo usado NO COINCIDE o no fue escaneado.");
        }
    }

    // 2.6 Validar Tipos Nuevos
    validarTipoFiltro($conn, $nuevo_aceite, 'Aceite');
    if (!empty($nuevo_centrifugo)) {
        validarTipoFiltro($conn, $nuevo_centrifugo, 'Centrifugo');
    }


    // =================================================================
    // 3. PROCESAMIENTO DE FOTOS (Hash + Anti-WhatsApp)
    // =================================================================
    $archivos = ['foto_viejos', 'foto_nuevos', 'foto_cubetas', 'foto_general'];
    $carpeta = "../../../uploads/evidencias_salidas/"; 
    if (!is_dir($carpeta)) mkdir($carpeta, 0777, true);

    foreach ($archivos as $input_name) {
        if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] === UPLOAD_ERR_OK) {
            
            $tmp = $_FILES[$input_name]['tmp_name'];
            
            // Hash
            $hash_archivo = hash_file('sha256', $tmp);
            $sql_dup = "SELECT id FROM tb_evidencias_entrada_taller WHERE hash_archivo = ? LIMIT 1";
            $stmt_dup = $conn->prepare($sql_dup);
            $stmt_dup->bind_param("s", $hash_archivo);
            $stmt_dup->execute();
            if ($stmt_dup->get_result()->num_rows > 0) {
                throw new Exception("🚫 FOTO REPETIDA ($input_name): Esta imagen ya existe en el sistema.");
            }

            // Metadatos
            $info_meta = extraerMetadatos($tmp);
            if (empty($info_meta['fecha'])) {
                throw new Exception("⛔ FOTO RECHAZADA ($input_name): Sin fecha original (Posible WhatsApp/Captura).");
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
    // 4. RESERVA Y ASIGNACIÓN
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
    
    if (!empty($nuevo_centrifugo)) {
        validarYReservar($conn, $nuevo_centrifugo, 'tb_inventario_filtros', 'filtro_centrifugo_entregado', $id_entrada);
    }
    
    validarYReservar($conn, $cubeta1, 'tb_inventario_lubricantes', 'cubeta_1_entregada', $id_entrada);
    validarYReservar($conn, $cubeta2, 'tb_inventario_lubricantes', 'cubeta_2_entregada', $id_entrada);

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Material validado correctamente. Se ha verificado que los aceites son del mismo tipo.']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>