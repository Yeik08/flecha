<?php
/*
* Portal/portal-mecanico/php/finalizar_mantenimiento.php
* VERSIÓN TRAZABILIDAD TOTAL:
* - Vincula cubetas usadas al ID del camión.
* - Genera Hash SHA-256 para evitar fotos repetidas (Anti-Fraude).
* - Guarda metadatos EXIF completos.
*/
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../../php/db_connect.php'; 

// 1. Seguridad
if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

// Función auxiliar para extraer metadatos completos
function extraerMetadatos($ruta_temporal) {
    $meta = [
        'fecha' => null,
        'json' => null
    ];
    
    // Solo intentamos si es JPG/JPEG
    if (function_exists('exif_read_data')) {
        // Suprimimos errores con @ por si la imagen no tiene header EXIF válido
        $exif = @exif_read_data($ruta_temporal);
        if ($exif) {
            // Intentar obtener fecha original
            if (isset($exif['DateTimeOriginal'])) {
                $meta['fecha'] = $exif['DateTimeOriginal'];
            } elseif (isset($exif['DateTimeDigitized'])) {
                $meta['fecha'] = $exif['DateTimeDigitized'];
            } elseif (isset($exif['DateTime'])) {
                $meta['fecha'] = $exif['DateTime'];
            }
            
            // Guardamos todo el array como JSON para auditoría futura
            // Convertimos a UTF-8 para evitar errores de JSON
            array_walk_recursive($exif, function(&$item, $key){
                if(!mb_detect_encoding($item, 'utf-8', true)){
                    $item = utf8_encode($item);
                }
            });
            $meta['json'] = json_encode($exif);
        }
    }
    return $meta;
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

    // =================================================================
    // 3. PROCESAMIENTO DE CUBETAS (Vincular al Camión)
    // =================================================================
    $cubetas_input = [
        trim($_POST['serie_cubeta_1'] ?? ''),
        trim($_POST['serie_cubeta_2'] ?? '')
    ];

    $tipo_aceite_previo = null; 

    foreach ($cubetas_input as $i => $serie) {
        if (empty($serie)) continue; 

        // Consultamos la cubeta
        $sql_check = "SELECT 
                        i.id, i.estatus, i.id_cat_lubricante, 
                        c.nombre_producto 
                      FROM tb_inventario_lubricantes i
                      JOIN tb_cat_lubricantes c ON i.id_cat_lubricante = c.id
                      WHERE i.numero_serie = ? 
                      LIMIT 1 FOR UPDATE";
        
        $stmt = $conn->prepare($sql_check);
        $stmt->bind_param("s", $serie);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) {
            throw new Exception("La cubeta con serie '$serie' no existe en inventario.");
        }

        $item = $res->fetch_assoc();

        // Validación A: Disponibilidad
        if ($item['estatus'] !== 'Disponible') {
            throw new Exception("La cubeta '$serie' ya fue utilizada o dada de baja.");
        }

        // Validación B: Consistencia de Mezcla
        if ($tipo_aceite_previo !== null) {
            if ($tipo_aceite_previo['id'] != $item['id_cat_lubricante']) {
                throw new Exception("🚫 ¡ERROR! Estás mezclando aceites diferentes.\n\n" .
                                    "Cubeta 1: " . $tipo_aceite_previo['nombre'] . "\n" .
                                    "Cubeta 2: " . $item['nombre_producto']);
            }
        } else {
            $tipo_aceite_previo = [
                'id' => $item['id_cat_lubricante'],
                'nombre' => $item['nombre_producto']
            ];
        }

        // C. CONSUMIR Y VINCULAR AL CAMIÓN
        // Aquí agregamos 'id_camion_uso'
        $sql_use = "UPDATE tb_inventario_lubricantes 
                    SET estatus = 'Usado', id_camion_uso = ? 
                    WHERE id = ?";
        $stmt_use = $conn->prepare($sql_use);
        $stmt_use->bind_param("ii", $id_camion, $item['id']);
        $stmt_use->execute();
    }

    // =================================================================
    // 4. PROCESAMIENTO DE FILTROS (Igual que antes)
    // =================================================================
    $filtros_a_procesar = [
        ['serie' => $_POST['nuevo_filtro_aceite'] ?? '', 'tipo_esperado' => 'Aceite'],
        ['serie' => $_POST['nuevo_filtro_centrifugo'] ?? '', 'tipo_esperado' => 'Centrifugo']
    ];

    foreach ($filtros_a_procesar as $f) {
        $serie = trim($f['serie']);
        $tipo_esperado = $f['tipo_esperado'];

        if (!empty($serie)) {
            $sql = "SELECT i.id, i.estatus, c.tipo_filtro 
                    FROM tb_inventario_filtros i
                    JOIN tb_cat_filtros c ON i.id_cat_filtro = c.id
                    WHERE i.numero_serie = ? 
                    LIMIT 1 FOR UPDATE";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $serie);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res->num_rows === 0) throw new Exception("El filtro '$serie' no existe.");
            $item = $res->fetch_assoc();

            if ($item['estatus'] !== 'Disponible') throw new Exception("El filtro '$serie' no está disponible.");

            if ($item['tipo_filtro'] !== $tipo_esperado) {
                throw new Exception("🚫 ERROR: La serie '$serie' es de " . strtoupper($item['tipo_filtro']) . 
                                    ", pero lo ingresaste en " . strtoupper($tipo_esperado) . ".");
            }

            $sql_up = "UPDATE tb_inventario_filtros SET estatus = 'Instalado', id_camion_instalado = ? WHERE id = ?";
            $stmt_up = $conn->prepare($sql_up);
            $stmt_up->bind_param("ii", $id_camion, $item['id']);
            $stmt_up->execute();
        }
    }

    // =================================================================
    // 5. PROCESAR FOTOS + HASH + METADATOS
    // =================================================================
    $archivos = ['foto_viejos', 'foto_nuevos', 'foto_general'];
    $carpeta = "../../../uploads/evidencias_salidas/"; 
    if (!is_dir($carpeta)) mkdir($carpeta, 0777, true);

    foreach ($archivos as $input_name) {
        if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] === UPLOAD_ERR_OK) {
            
            $tmp = $_FILES[$input_name]['tmp_name'];
            
            // A. Generar HASH ÚNICO (Huella digital)
            $hash_archivo = hash_file('sha256', $tmp);

            // B. Verificar Duplicados (Anti-Fraude Global)
            // Buscamos si este hash ya existe en CUALQUIER evidencia del sistema
            $sql_dup = "SELECT id FROM tb_evidencias_entrada_taller WHERE hash_archivo = ? LIMIT 1";
            $stmt_dup = $conn->prepare($sql_dup);
            $stmt_dup->bind_param("s", $hash_archivo);
            $stmt_dup->execute();
            if ($stmt_dup->get_result()->num_rows > 0) {
                throw new Exception("🚫 FOTO REPETIDA: La imagen de '$input_name' ya existe en el sistema. Debes tomar una foto nueva.");
            }

            // C. Extraer Metadatos
            $info_meta = extraerMetadatos($tmp);
            
            // D. Validar Tiempo (24h máximo)
            if ($info_meta['fecha']) {
                // Limpieza de fecha EXIF (YYYY:MM:DD -> YYYY-MM-DD)
                $fecha_limpia = preg_replace('/^(\d{4}):(\d{2}):(\d{2})/', '$1-$2-$3', $info_meta['fecha']);
                try {
                    $fechaFoto = new DateTime($fecha_limpia);
                    $ahora = new DateTime();
                    $horas = ($ahora->getTimestamp() - $fechaFoto->getTimestamp()) / 3600;
                    
                    if ($horas > 24) throw new Exception("La evidencia '$input_name' es antigua (>24h).");
                } catch (Exception $e) {
                    // Si falla el parseo de fecha, decidimos si bloquear o dejar pasar.
                    // Aquí relanzamos si es nuestro error de >24h.
                    if ($e->getMessage() === "La evidencia '$input_name' es antigua (>24h).") throw $e;
                }
            }

            // E. Guardar
            $ext = pathinfo($_FILES[$input_name]['name'], PATHINFO_EXTENSION);
            $nombre_final = "SALIDA_" . $id_entrada . "_" . $input_name . "_" . time() . "." . $ext;
            
            if (move_uploaded_file($tmp, $carpeta . $nombre_final)) {
                $ruta_bd = "../uploads/evidencias_salidas/" . $nombre_final;
                $tipo_foto = "Salida - " . ucfirst(str_replace('foto_', '', $input_name));
                
                // INSERTAMOS hash_archivo Y metadatos
                $sql_ins = "INSERT INTO tb_evidencias_entrada_taller 
                            (id_entrada, ruta_archivo, tipo_foto, descripcion, fecha_captura, hash_archivo, metadatos_json) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt_ins = $conn->prepare($sql_ins);
                $stmt_ins->bind_param("issssss", 
                    $id_entrada, 
                    $ruta_bd, 
                    $tipo_foto, 
                    $comentarios, 
                    $info_meta['fecha'], // Fecha captura
                    $hash_archivo,       // El hash SHA-256
                    $info_meta['json']   // JSON completo
                );
                $stmt_ins->execute();
            }
        }
    }

    // =================================================================
    // 6. CIERRE Y ACTUALIZACIÓN
    // =================================================================
    $hoy = date('Y-m-d');
    
    // Actualizar Camión
    $sql_camion = "UPDATE tb_camiones SET estatus = 'Activo', mantenimiento_requerido = 'No', fecha_ult_mantenimiento = ?";
    $params_types = "s";
    $params_vals = [$hoy];

    if (!empty($_POST['nuevo_filtro_aceite'])) {
        $sql_camion .= ", fecha_ult_cambio_aceite = ?, serie_filtro_aceite_actual = ?";
        $params_types .= "ss"; $params_vals[] = $hoy; $params_vals[] = $_POST['nuevo_filtro_aceite'];
    }
    if (!empty($_POST['nuevo_filtro_centrifugo'])) {
        $sql_camion .= ", fecha_ult_cambio_centrifugo = ?, serie_filtro_centrifugo_actual = ?";
        $params_types .= "ss"; $params_vals[] = $hoy; $params_vals[] = $_POST['nuevo_filtro_centrifugo'];
    }
    // Si queremos actualizar el lubricante actual en el camión
    if ($tipo_aceite_previo) {
        $sql_camion .= ", lubricante_actual = ?";
        $params_types .= "s"; $params_vals[] = $tipo_aceite_previo['nombre'];
    }

    $sql_camion .= " WHERE id = ?";
    $params_types .= "i";
    $params_vals[] = $id_camion;

    $stmt_c = $conn->prepare($sql_camion);
    $stmt_c->bind_param($params_types, ...$params_vals);
    $stmt_c->execute();

    // Cerrar Ticket
    $sql_close = "UPDATE tb_entradas_taller 
                  SET estatus_entrada = 'Entregado', 
                      fecha_fin_reparacion = NOW() 
                  WHERE id = ?";
    $stmt_close = $conn->prepare($sql_close);
    $stmt_close->bind_param("i", $id_entrada);
    $stmt_close->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Mantenimiento finalizado exitosamente.']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>