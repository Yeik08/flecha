<?php
/*
* Portal/portal-mecanico/php/finalizar_mantenimiento.php
* VERSIÓN BLINDADA:
* - Valida que el filtro escaneado corresponda al tipo correcto (Aceite vs Centrífugo).
* - Impide mezclar diferentes tipos de aceite.
* - Guarda metadatos EXIF completos de las evidencias.
* - Registra fecha exacta de finalización.
*/
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../../php/db_connect.php'; 

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
    // 1. Datos Básicos
    $id_entrada = $_POST['id_entrada'] ?? '';
    $id_camion = $_POST['id_camion_real'] ?? '';
    $comentarios = $_POST['comentarios'] ?? '';
    
    if(empty($id_entrada) || empty($id_camion)) {
        throw new Exception("Faltan identificadores del servicio.");
    }

    // =================================================================
    // 2. VALIDACIÓN Y PROCESAMIENTO DE CUBETAS DE ACEITE (Anti-Mezcla)
    // =================================================================
    $cubetas_input = [
        trim($_POST['serie_cubeta_1'] ?? ''),
        trim($_POST['serie_cubeta_2'] ?? '')
    ];

    $tipo_aceite_previo = null; // Para guardar el ID del producto de la primera cubeta

    foreach ($cubetas_input as $i => $serie) {
        if (empty($serie)) continue; 

        // Consultamos la cubeta Y su tipo de producto (JOIN)
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
            throw new Exception("La cubeta '$serie' no existe en inventario.");
        }

        $item = $res->fetch_assoc();

        // Validación A: Disponibilidad
        if ($item['estatus'] !== 'Disponible') {
            throw new Exception("La cubeta '$serie' ya fue utilizada o dada de baja.");
        }

        // Validación B: Consistencia de Mezcla (Anti-Coctel)
        if ($tipo_aceite_previo !== null) {
            if ($tipo_aceite_previo['id'] != $item['id_cat_lubricante']) {
                throw new Exception("🚫 ¡ERROR CRÍTICO! Estás mezclando aceites diferentes.\n\n" .
                                    "Cubeta 1: " . $tipo_aceite_previo['nombre'] . "\n" .
                                    "Cubeta 2: " . $item['nombre_producto'] . "\n\n" .
                                    "Verifica las series.");
            }
        } else {
            // Guardamos el tipo de la primera cubeta para comparar con la siguiente
            $tipo_aceite_previo = [
                'id' => $item['id_cat_lubricante'],
                'nombre' => $item['nombre_producto']
            ];
        }

        // Si pasa, consumimos
        $sql_use = "UPDATE tb_inventario_lubricantes SET estatus = 'Usado' WHERE id = ?";
        $stmt_use = $conn->prepare($sql_use);
        $stmt_use->bind_param("i", $item['id']);
        $stmt_use->execute();
    }

    // =================================================================
    // 3. VALIDACIÓN Y PROCESAMIENTO DE FILTROS (Tipo Correcto)
    // =================================================================
    
    // Mapeamos el input del form con el 'tipo_filtro' exacto que debe tener en la BD
    $filtros_a_procesar = [
        ['serie' => $_POST['nuevo_filtro_aceite'] ?? '', 'tipo_esperado' => 'Aceite'],
        ['serie' => $_POST['nuevo_filtro_centrifugo'] ?? '', 'tipo_esperado' => 'Centrifugo']
    ];

    foreach ($filtros_a_procesar as $f) {
        $serie = trim($f['serie']);
        $tipo_esperado = $f['tipo_esperado'];

        if (!empty($serie)) {
            // JOIN para ver qué tipo de filtro es realmente esta serie
            $sql = "SELECT i.id, i.estatus, c.tipo_filtro 
                    FROM tb_inventario_filtros i
                    JOIN tb_cat_filtros c ON i.id_cat_filtro = c.id
                    WHERE i.numero_serie = ? 
                    LIMIT 1 FOR UPDATE";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $serie);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res->num_rows === 0) throw new Exception("El filtro '$serie' no existe en inventario.");
            $item = $res->fetch_assoc();

            // Validación A: Disponibilidad
            if ($item['estatus'] !== 'Disponible') throw new Exception("El filtro '$serie' no está disponible.");

            // Validación B: Tipo Correcto (Lo que pediste)
            if ($item['tipo_filtro'] !== $tipo_esperado) {
                throw new Exception("🚫 ERROR DE PIEZA: La serie '$serie' corresponde a un filtro de " . strtoupper($item['tipo_filtro']) . 
                                    ", pero lo ingresaste en el campo de " . strtoupper($tipo_esperado) . ".\n\nPor favor corrige.");
            }

            // Instalar
            $sql_up = "UPDATE tb_inventario_filtros SET estatus = 'Instalado', id_camion_instalado = ? WHERE id = ?";
            $stmt_up = $conn->prepare($sql_up);
            $stmt_up->bind_param("ii", $id_camion, $item['id']);
            $stmt_up->execute();
        }
    }

    // =================================================================
    // 4. PROCESAR FOTOS + METADATOS (Auditoría)
    // =================================================================
    $archivos = ['foto_viejos', 'foto_nuevos', 'foto_general'];
    $carpeta = "../../../uploads/evidencias_salidas/"; 
    if (!is_dir($carpeta)) mkdir($carpeta, 0777, true);

    foreach ($archivos as $input_name) {
        if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] === UPLOAD_ERR_OK) {
            
            $tmp = $_FILES[$input_name]['tmp_name'];
            
            // Extracción de metadatos ANTES de mover
            $info_meta = extraerMetadatos($tmp);
            
            // Validación de tiempo (Regla 24h)
            if ($info_meta['fecha']) {
                $fechaFoto = new DateTime($info_meta['fecha']);
                $ahora = new DateTime();
                $horas = ($ahora->diff($fechaFoto)->days * 24) + $ahora->diff($fechaFoto)->h;
                if ($horas > 24) throw new Exception("La evidencia '$input_name' es antigua (>24h). Usa una foto actual.");
            }

            $ext = pathinfo($_FILES[$input_name]['name'], PATHINFO_EXTENSION);
            $nombre_final = "SALIDA_" . $id_entrada . "_" . $input_name . "_" . time() . "." . $ext;
            
            if (move_uploaded_file($tmp, $carpeta . $nombre_final)) {
                $ruta_bd = "../uploads/evidencias_salidas/" . $nombre_final;
                $tipo_foto = "Salida - " . ucfirst(str_replace('foto_', '', $input_name));
                
                // Insertamos con metadatos
                $sql_ins = "INSERT INTO tb_evidencias_entrada_taller 
                            (id_entrada, ruta_archivo, tipo_foto, descripcion, fecha_captura, metadatos_json) 
                            VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_ins = $conn->prepare($sql_ins);
                $stmt_ins->bind_param("isssss", 
                    $id_entrada, 
                    $ruta_bd, 
                    $tipo_foto, 
                    $comentarios, 
                    $info_meta['fecha'], // Puede ser null
                    $info_meta['json']   // JSON completo
                );
                $stmt_ins->execute();
            }
        }
    }

    // =================================================================
    // 5. CIERRE DE MANTENIMIENTO
    // =================================================================
    $hoy = date('Y-m-d');
    
    // 1. Actualizar Camión
    $sql_camion = "UPDATE tb_camiones SET estatus = 'Activo', mantenimiento_requerido = 'No', fecha_ult_mantenimiento = ?";
    $params_types = "s";
    $params_vals = [$hoy];

    // Actualizar series en camión si se cambiaron
    if (!empty($_POST['nuevo_filtro_aceite'])) {
        $sql_camion .= ", fecha_ult_cambio_aceite = ?, serie_filtro_aceite_actual = ?";
        $params_types .= "ss"; $params_vals[] = $hoy; $params_vals[] = $_POST['nuevo_filtro_aceite'];
    }
    if (!empty($_POST['nuevo_filtro_centrifugo'])) {
        $sql_camion .= ", fecha_ult_cambio_centrifugo = ?, serie_filtro_centrifugo_actual = ?";
        $params_types .= "ss"; $params_vals[] = $hoy; $params_vals[] = $_POST['nuevo_filtro_centrifugo'];
    }
    // Si queremos guardar qué aceite se usó en el camión, podríamos actualizar 'lubricante_actual' aquí también
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

    // 2. Cerrar Ticket con FECHA FIN
    $sql_close = "UPDATE tb_entradas_taller 
                  SET estatus_entrada = 'Entregado', 
                      fecha_fin_reparacion = NOW() 
                  WHERE id = ?";
    $stmt_close = $conn->prepare($sql_close);
    $stmt_close->bind_param("i", $id_entrada);
    $stmt_close->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Mantenimiento finalizado correctamente.']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>