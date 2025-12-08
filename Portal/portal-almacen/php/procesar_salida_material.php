<?php
/*
* Portal/portal-almacen/php/procesar_salida_material.php
* VERSIÓN FINAL BLINDADA: 
* - Validación de Almacén Correcto (Ubicación).
* - Validación Anti-Duplicados (Cubetas).
* - Validación de Tipos y Viscosidad.
* - Fotos Seguras + Hash.
*/
session_start();
header('Content-Type: application/json');
require_once '../../../php/db_connect.php';

// Validar Permisos (Almacén o Admin)
if (!isset($_SESSION['loggedin']) || ($_SESSION['role_id'] != 6 && $_SESSION['role_id'] != 1)) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']); exit;
}

// Función auxiliar para metadatos EXIF
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
    $id_almacen_origen = $_POST['id_almacen_origen'] ?? null; // EL ALMACÉN SELECCIONADO EN EL FRONTEND
    $id_recibe = $_POST['id_recibe'] ?? null; // PERSONAL QUE RECIBE
    $comentarios = "Entrega de Material en Almacén";
    
    // Inputs del formulario
    $viejo_aceite_input = trim($_POST['filtro_viejo_serie'] ?? '');
    $viejo_cent_input = trim($_POST['filtro_viejo_centrifugo_serie'] ?? '');
    
    $nuevo_aceite = trim($_POST['filtro_nuevo_serie'] ?? '');
    $nuevo_centrifugo = trim($_POST['filtro_nuevo_centrifugo'] ?? '');
    $cubeta1 = trim($_POST['cubeta_1'] ?? '');
    $cubeta2 = trim($_POST['cubeta_2'] ?? '');

    // VALIDACIONES BÁSICAS
    if (!$id_entrada || !$id_camion) throw new Exception("Faltan datos de la orden.");
    if (!$id_almacen_origen) throw new Exception("Debes seleccionar desde qué almacén estás despachando.");
    if (!$id_recibe) throw new Exception("Debes seleccionar quién recoge el material.");

    // =================================================================
    // 2. VALIDACIONES DE NEGOCIO Y LÓGICA
    // =================================================================

    // 2.1 Consultar Tipo de Servicio Requerido
    $sql_tipo = "SELECT proximo_servicio_tipo, lubricante_sugerido FROM tb_camiones WHERE id = ?";
    $stmt_tipo = $conn->prepare($sql_tipo);
    $stmt_tipo->bind_param("i", $id_camion);
    $stmt_tipo->execute();
    
    $datos_camion = $stmt_tipo->get_result()->fetch_assoc();
    $tipo_servicio = $datos_camion['proximo_servicio_tipo'] ?? 'Basico';
    $aceite_requerido_camion = strtoupper($datos_camion['lubricante_sugerido'] ?? '');

    // 2.2 Validar Obligatoriedad
    if (empty($nuevo_aceite)) throw new Exception("El filtro de aceite nuevo es obligatorio.");
    if (empty($cubeta1) || empty($cubeta2)) throw new Exception("Es obligatorio escanear las 2 cubetas de aceite.");
    
    if ($tipo_servicio === 'Completo' && empty($nuevo_centrifugo)) {
        throw new Exception("⛔ ERROR: Este camión requiere Servicio Completo. Falta escanear el Filtro Centrífugo.");
    }

    // 2.3 VALIDACIÓN DE DUPLICADOS (Cubetas repetidas)
    if ($cubeta1 === $cubeta2) {
        throw new Exception("⛔ ERROR: Estás escaneando la misma cubeta dos veces. Deben ser distintas.");
    }

    // --- FUNCIÓN MAESTRA DE VALIDACIÓN DE DISPONIBILIDAD Y UBICACIÓN ---
    function verificarDisponibilidad($conn, $serie, $tabla, $id_almacen_esperado, $tipo_filtro_esperado = null) {
        if (empty($serie)) return null;

        // Consultamos info del item + ubicación + tipo (si es filtro)
        if ($tabla === 'tb_inventario_filtros') {
            $sql = "SELECT i.id, i.estatus, i.id_ubicacion, u.nombre as nombre_ubicacion, c.tipo_filtro 
                    FROM tb_inventario_filtros i
                    JOIN tb_cat_ubicaciones u ON i.id_ubicacion = u.id
                    JOIN tb_cat_filtros c ON i.id_cat_filtro = c.id
                    WHERE i.numero_serie = ? LIMIT 1 FOR UPDATE";
        } else {
            // Cubetas
            $sql = "SELECT i.id, i.estatus, i.id_ubicacion, u.nombre as nombre_ubicacion, 
                           i.id_cat_lubricante, l.nombre_producto
                    FROM tb_inventario_lubricantes i
                    JOIN tb_cat_ubicaciones u ON i.id_ubicacion = u.id
                    JOIN tb_cat_lubricantes l ON i.id_cat_lubricante = l.id
                    WHERE i.numero_serie = ? LIMIT 1 FOR UPDATE";
        }

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $serie);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) {
            throw new Exception("❌ El ítem '$serie' NO EXISTE en el inventario.");
        }

        $item = $res->fetch_assoc();

        // A. Validar Estatus
        if ($item['estatus'] !== 'Disponible') {
            throw new Exception("⛔ El ítem '$serie' NO ESTÁ DISPONIBLE. (Estado actual: " . $item['estatus'] . ").");
        }

        // B. Validar Ubicación Correcta
        if ($item['id_ubicacion'] != $id_almacen_esperado) {
            throw new Exception("📍 ERROR DE UBICACIÓN:\nEl ítem '$serie' está en '" . $item['nombre_ubicacion'] . "',\npero estás intentando despachar desde otro almacén.");
        }

        // C. Validar Tipo de Filtro (Si aplica)
        if ($tipo_filtro_esperado !== null) {
            if (strtoupper($item['tipo_filtro']) !== strtoupper($tipo_filtro_esperado)) {
                throw new Exception("⚠️ ERROR DE TIPO: Escaneaste un filtro de " . strtoupper($item['tipo_filtro']) . " ($serie),\npero se esperaba uno de " . strtoupper($tipo_filtro_esperado) . ".");
            }
        }

        return $item; // Retornamos info para validaciones extra (viscosidad)
    }

    // 2.4 EJECUTAR VALIDACIONES DE MATERIAL NUEVO
    
    // Filtro Aceite
    verificarDisponibilidad($conn, $nuevo_aceite, 'tb_inventario_filtros', $id_almacen_origen, 'Aceite');
    
    // Filtro Centrífugo (si aplica)
    if (!empty($nuevo_centrifugo)) {
        verificarDisponibilidad($conn, $nuevo_centrifugo, 'tb_inventario_filtros', $id_almacen_origen, 'Centrifugo');
    }

    // Cubetas (Validamos existencia, ubicación y viscosidad)
    $info_cub1 = verificarDisponibilidad($conn, $cubeta1, 'tb_inventario_lubricantes', $id_almacen_origen);
    $info_cub2 = verificarDisponibilidad($conn, $cubeta2, 'tb_inventario_lubricantes', $id_almacen_origen);

    // 2.5 VALIDACIÓN DE VISCOSIDAD (Regla del Camión)
    if (!empty($aceite_requerido_camion)) {
        // Checar Cubeta 1
        if (strpos(strtoupper($info_cub1['nombre_producto']), $aceite_requerido_camion) === false) {
            throw new Exception("⛔ ACEITE INCORRECTO: El camión pide $aceite_requerido_camion, pero la cubeta 1 es " . $info_cub1['nombre_producto']);
        }
        // Checar Cubeta 2
        if (strpos(strtoupper($info_cub2['nombre_producto']), $aceite_requerido_camion) === false) {
            throw new Exception("⛔ ACEITE INCORRECTO: El camión pide $aceite_requerido_camion, pero la cubeta 2 es " . $info_cub2['nombre_producto']);
        }
    }
    
    // Validar que sean del mismo tipo entre ellas (redundante si validamos contra camión, pero seguro)
    if ($info_cub1['id_cat_lubricante'] != $info_cub2['id_cat_lubricante']) {
        throw new Exception("⛔ MEZCLA DETECTADA: Las cubetas son de tipos diferentes.");
    }


    // 2.6 VALIDACIÓN DE FILTROS VIEJOS (Coincidencia con el sistema)
    $sql_camion_actual = "SELECT serie_filtro_aceite_actual, serie_filtro_centrifugo_actual FROM tb_camiones WHERE id = ?";
    $stmt_c = $conn->prepare($sql_camion_actual);
    $stmt_c->bind_param("i", $id_camion);
    $stmt_c->execute();
    $actuales = $stmt_c->get_result()->fetch_assoc();

    if (!empty($actuales['serie_filtro_aceite_actual'])) {
        if (strtoupper($viejo_aceite_input) !== strtoupper($actuales['serie_filtro_aceite_actual'])) {
            throw new Exception("⛔ ERROR EN RETORNO: El filtro de aceite sucio no coincide con el del sistema.");
        }
    }
    if ($tipo_servicio === 'Completo' && !empty($actuales['serie_filtro_centrifugo_actual'])) {
        if (empty($viejo_cent_input) || strtoupper($viejo_cent_input) !== strtoupper($actuales['serie_filtro_centrifugo_actual'])) {
            throw new Exception("⛔ ERROR EN RETORNO: El filtro centrífugo sucio no coincide.");
        }
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
            
            // Hash Check
            $hash_archivo = hash_file('sha256', $tmp);
            $sql_dup = "SELECT id FROM tb_evidencias_entrada_taller WHERE hash_archivo = ? LIMIT 1";
            $stmt_dup = $conn->prepare($sql_dup);
            $stmt_dup->bind_param("s", $hash_archivo);
            $stmt_dup->execute();
            if ($stmt_dup->get_result()->num_rows > 0) throw new Exception("🚫 FOTO REPETIDA ($input_name).");

            // Metadatos Check
            $info_meta = extraerMetadatos($tmp);
            if (empty($info_meta['fecha'])) throw new Exception("⛔ FOTO RECHAZADA ($input_name): Sin fecha original.");

            // Antigüedad Check (24h)
            $fecha_limpia = preg_replace('/^(\d{4}):(\d{2}):(\d{2})/', '$1-$2-$3', $info_meta['fecha']);
            try {
                $fechaFoto = new DateTime($fecha_limpia);
                $horas = ((new DateTime())->getTimestamp() - $fechaFoto->getTimestamp()) / 3600;
                if ($horas > 24) throw new Exception("⛔ FOTO ANTIGUA ($input_name).");
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
    // 4. RESERVAR Y ASIGNAR (Ejecución Final)
    // =================================================================
    
    // Función simple de update ya que validamos todo antes
    function asignarMaterial($conn, $serie, $tabla, $campo_id_entrada, $id_entrada) {
        if (empty($serie)) return;
        
        // 1. Cambiar estado a 'Asignado'
        $sql_up = "UPDATE $tabla SET estatus = 'Asignado' WHERE numero_serie = ?";
        $stmt_up = $conn->prepare($sql_up);
        $stmt_up->bind_param("s", $serie);
        $stmt_up->execute();

        // 2. Guardar en el ticket
        $sql_ticket = "UPDATE tb_entradas_taller SET $campo_id_entrada = ? WHERE id = ?";
        $stmt_t = $conn->prepare($sql_ticket);
        $stmt_t->bind_param("si", $serie, $id_entrada);
        $stmt_t->execute();
    }

    asignarMaterial($conn, $nuevo_aceite, 'tb_inventario_filtros', 'filtro_aceite_entregado', $id_entrada);
    if (!empty($nuevo_centrifugo)) asignarMaterial($conn, $nuevo_centrifugo, 'tb_inventario_filtros', 'filtro_centrifugo_entregado', $id_entrada);
    asignarMaterial($conn, $cubeta1, 'tb_inventario_lubricantes', 'cubeta_1_entregada', $id_entrada);
    asignarMaterial($conn, $cubeta2, 'tb_inventario_lubricantes', 'cubeta_2_entregada', $id_entrada);

    // 5. REGISTRAR RESPONSABLES DE LA ENTREGA
    $id_almacenista = $_SESSION['id_usuario'] ?? 0; // Ojo: Verifica que guardes esto en login.php
    // Si no tienes id_usuario en sesión, usa una consulta con el nombre, pero mejor ajusta tu login.
    // Asumiré que quieres guardar los IDs que definimos al inicio.
    
    $sql_firmas = "UPDATE tb_entradas_taller SET 
                   id_almacenista_entrega = ?, 
                   id_personal_recibe = ? 
                   WHERE id = ?";
    // Usamos $id_almacenista (del usuario logueado) y $id_recibe (del select)
    $stmt_f = $conn->prepare($sql_firmas);
    // Nota: Si $_SESSION['id_usuario'] no existe, esto fallará. Ajusta según tu sistema de login (puedes usar id_empleado de sesión)
    $id_alm_final = $_SESSION['user_id'] ?? $_SESSION['id_empleado'] ?? 0; 
    
    $stmt_f->bind_param("iii", $id_alm_final, $id_recibe, $id_entrada);
    $stmt_f->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Material entregado correctamente. Inventario y responsables actualizados.']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>