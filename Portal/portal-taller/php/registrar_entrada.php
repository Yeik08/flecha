<?php
/*
* Portal/portal-taller/php/registrar_entrada.php
* VERSIÓN DEFINITIVA V6:
* - Ruta de conexión corregida (../../../php/db_connect.php).
* - Tipos de datos en bind_param corregidos (Observaciones es 's', no 'i').
*/

// Apagar errores visuales para proteger JSON
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

session_start();
header('Content-Type: application/json');

function enviarRespuesta($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

try {
    // 1. Seguridad
    if (!isset($_SESSION['loggedin']) || ($_SESSION['role_id'] != 5 && $_SESSION['role_id'] != 1)) {
        enviarRespuesta(false, 'Acceso no autorizado.');
    }

    // --- CORRECCIÓN DE RUTA CRÍTICA ---
    // Subimos 3 niveles: php/ -> portal-taller/ -> Portal/ -> Raíz
    require_once '../../../php/db_connect.php'; 
    
    if ($conn === false) {
        enviarRespuesta(false, 'Error de conexión a la base de datos.');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        enviarRespuesta(false, 'Método inválido.');
    }

    $id_usuario = $_SESSION['user_id'];
    $conn->begin_transaction();

    // 2. Recibir Datos
    $id_camion = $_POST['id_camion_seleccionado'] ?? null;
    $km_llegada = floatval($_POST['kilometraje_entrada'] ?? 0);
    $combustible = $_POST['nivel_combustible'] ?? 'No especificado';
    $id_taller = $_POST['id_taller'] ?? 1; 
    $tipo_mto = $_POST['tipo_mantenimiento'] ?? 'General';
    $obs = $_POST['observaciones_recepcion'] ?? '';
    $fecha_ingreso = !empty($_POST['fecha_ingreso']) ? $_POST['fecha_ingreso'] : date('Y-m-d H:i:s');

    if (!$id_camion) {
        throw new Exception("No se seleccionó ningún camión.");
    }

    // Validar KM (No menor al actual)
        $sql_check = "SELECT kilometraje_total, fecha_estimada_mantenimiento FROM tb_camiones WHERE id = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("i", $id_camion);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result()->fetch_assoc();
        
        $km_actual_sistema = floatval($res_check['kilometraje_total']);
        if ($km_llegada < $km_actual_sistema) {
            throw new Exception("Error: El kilometraje ingresado ($km_llegada) es MENOR al actual ($km_actual_sistema).");
        }








    // 3. Lógica de Negocio y Conductores
    $id_cond_asignado = !empty($_POST['id_conductor_asignado_hidden']) ? $_POST['id_conductor_asignado_hidden'] : null;
    $id_cond_entrega = !empty($_POST['id_conductor_entrega']) ? $_POST['id_conductor_entrega'] : null;
    
    // Buscar ID si viene nombre
    if ($id_cond_entrega && !is_numeric($id_cond_entrega)) {
        $stmt_c = $conn->prepare("SELECT id_empleado FROM empleados WHERE id_interno = ? OR nombre LIKE ? LIMIT 1");
        $like_name = "%$id_cond_entrega%";
        $stmt_c->bind_param("ss", $id_cond_entrega, $like_name);
        $stmt_c->execute();
        $res_c = $stmt_c->get_result();
        if($row_c = $res_c->fetch_assoc()) {
            $id_cond_entrega = $row_c['id_empleado'];
        } else {
             $id_cond_entrega = null; 
        }
    }
    $alerta_cond = ($id_cond_asignado && $id_cond_entrega && $id_cond_asignado != $id_cond_entrega) ? 'Si' : 'No';

    // Tiempos
    $sql_fechas = "SELECT fecha_estimada_mantenimiento FROM tb_camiones WHERE id = ?";
    $stmt = $conn->prepare($sql_fechas);
    $stmt->bind_param("i", $id_camion);
    $stmt->execute();
    $res_f = $stmt->get_result()->fetch_assoc();
    
    $clasificacion = 'No Programado';
    $dias_dif = 0;

    if ($res_f && !empty($res_f['fecha_estimada_mantenimiento'])) {
        $fecha_est = new DateTime($res_f['fecha_estimada_mantenimiento']);
        $fecha_real = new DateTime($fecha_ingreso);
        $intervalo = $fecha_est->diff($fecha_real);
        $dias_dif = (int)$intervalo->format('%r%a');

        if ($dias_dif < -7) $clasificacion = 'Anticipado';
        elseif ($dias_dif > 7) $clasificacion = 'Tarde';
        else $clasificacion = 'A Tiempo';
    }

    // 4. Insertar Entrada
    $folio = "ENT-" . date('ymd') . "-" . rand(1000, 9999);


    $sql_insert = "INSERT INTO tb_entradas_taller (
        folio, id_camion, id_recepcionista, id_taller, kilometraje_entrada, nivel_combustible, 
        tipo_mantenimiento_solicitado, id_conductor_asignado, id_conductor_entrega, alerta_conductor,
        clasificacion_tiempo, dias_desviacion, observaciones_recepcion, fecha_ingreso
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql_insert);
    if (!$stmt) throw new Exception("Error preparando consulta: " . $conn->error);

    // --- CORRECCIÓN DE TIPOS DE DATO ---
    // Antes: "siiidssiisssis" (La penúltima 'i' causaba error en observaciones)
    // Ahora: "siiidssiisssss" (La penúltima es 's' para texto)
    $stmt->bind_param("siiidssiisssss", 
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
        $obs,             // Este ahora es 's'
        $fecha_ingreso
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Error al guardar en BD: " . $stmt->error);
    }
    $id_entrada = $conn->insert_id;

    // 5. Actualizar Estatus Camión
    $stmt_up = $conn->prepare("UPDATE tb_camiones SET estatus = 'En Taller' WHERE id = ?");
    $stmt_up->bind_param("i", $id_camion);
    $stmt_up->execute();


    // --- 6. Guardar Foto CON AUDITORÍA FORENSE (ÚNICO BLOQUE) ---
if (isset($_FILES['foto_entrada']) && $_FILES['foto_entrada']['error'] === UPLOAD_ERR_OK) {
        
        $tmp_name = $_FILES['foto_entrada']['tmp_name'];
        $nombre_original = $_FILES['foto_entrada']['name'];
        $ext = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
        
        $permitidos = ['jpg', 'jpeg', 'png'];
        if (in_array($ext, $permitidos)) {
            
            // A. Generar Huella Digital
            $hash_archivo = hash_file('sha256', $tmp_name);

            $sql_duplicado = "SELECT t.folio, t.fecha_ingreso 
                              FROM tb_evidencias_entrada_taller e
                              JOIN tb_entradas_taller t ON e.id_entrada = t.id
                              WHERE e.hash_archivo = ? LIMIT 1";
            
            $stmt_dup = $conn->prepare($sql_duplicado);
            $stmt_dup->bind_param("s", $hash_archivo);
            $stmt_dup->execute();
            $res_dup = $stmt_dup->get_result();

            if ($res_dup->num_rows > 0) {
                // ¡AJÁ! Te atrapé. Ya existe.
                $info_dup = $res_dup->fetch_assoc();
                throw new Exception("⛔ FOTO RECHAZADA: Esta imagen ya fue utilizada anteriormente en el Folio " . $info_dup['folio'] . " del " . date('d/m/Y', strtotime($info_dup['fecha_ingreso'])));
            }
            $stmt_dup->close();            





            // B. Obtener Metadatos (PRIORIDAD: LO QUE ENVÍA EL FRONTEND)
            $fecha_captura = !empty($_POST['meta_fecha_captura']) ? $_POST['meta_fecha_captura'] : null;
            $metadatos_json = !empty($_POST['meta_datos_json']) ? $_POST['meta_datos_json'] : null;

            // C. Respaldo: Si el frontend falló, intentamos leerlo con PHP (Solo JPG/JPEG)
            if (empty($fecha_captura) && in_array($ext, ['jpg', 'jpeg']) && function_exists('exif_read_data')) {
                $exif = @exif_read_data($tmp_name);
                if ($exif) {
                    // Guardamos JSON si no venía del front
                    if (empty($metadatos_json)) {
                        $metadatos_json = json_encode($exif);
                    }
                    
                    // Buscamos fecha
                    if (isset($exif['DateTimeOriginal'])) {
                        $fecha_captura = $exif['DateTimeOriginal']; 
                    } elseif (isset($exif['DateTimeDigitized'])) {
                        $fecha_captura = $exif['DateTimeDigitized'];
                    } elseif (isset($exif['DateTime'])) {
                        $fecha_captura = $exif['DateTime'];
                    }
                }
            }
 // --- AUDITORÍA DE TIEMPO (NUEVO BLOQUE) ---
            if (!empty($fecha_captura)) {
                // Establecer zona horaria (Vital para México)
                date_default_timezone_set('America/Mexico_City'); 
                
                // Limpiar formato EXIF: cambiar los primeros ':' por '-' (Ej: 2025:11:25 -> 2025-11-25)
                // El formato EXIF estándar es "YYYY:MM:DD HH:MM:SS"
                $fecha_limpia = preg_replace('/^(\d{4}):(\d{2}):(\d{2})/', '$1-$2-$3', $fecha_captura);
                
                try {
                    $fecha_foto_dt = new DateTime($fecha_limpia);
                    $ahora = new DateTime();
                    
                    // Calcular diferencia en horas
                    $diferencia_segundos = $ahora->getTimestamp() - $fecha_foto_dt->getTimestamp();
                    $horas_pasadas = $diferencia_segundos / 3600;

                    // Validaciones
                    // 1. Futuro (Tolerancia 10 mins por relojes desajustados)
                    if ($diferencia_segundos < -600) {
                        throw new Exception("⛔ FOTO RECHAZADA: La fecha de la foto es futura. Revisa la hora de tu cámara.");
                    }

                    // 2. Antigüedad (4 Horas)
                    if ($horas_pasadas > 4) {
                        throw new Exception("⛔ FOTO RECHAZADA: Evidencia antigua (" . round($horas_pasadas, 1) . " horas). Solo se permiten fotos tomadas hace menos de 4 horas.");
                    }

                } catch (Exception $e) {
                    // Si es nuestra excepción, la relanzamos. Si es error de fecha, lo ignoramos o manejamos.
                    if (strpos($e->getMessage(), 'FOTO RECHAZADA') !== false) {
                        throw $e;
                    }
                    // Si la fecha no era válida, podríamos decidir rechazar o dejar pasar.
                    // Por seguridad, si no podemos validar la fecha, asumimos riesgo o pedimos reintentar.
                }
            } else {
                // OPCIONAL: ¿Rechazar si no tiene fecha en absoluto?
                throw new Exception("⛔ FOTO RECHAZADA: La imagen no contiene fecha de captura original (Metadata perdida). Usa una foto original.");
            }
            // D. Mover y Guardar
            $nombre_foto = "EVIDENCIA_" . $folio . "_" . time() . "." . $ext;
            $carpeta = "../../../uploads/evidencias_entradas/";
            
            // Asegurar permisos correctos al crear carpeta
            if (!is_dir($carpeta)) {
                mkdir($carpeta, 0777, true);
            }
            
            if (move_uploaded_file($tmp_name, $carpeta . $nombre_foto)) {
                $ruta_bd = "../uploads/evidencias_entradas/" . $nombre_foto;
                
                $sql_foto = "INSERT INTO tb_evidencias_entrada_taller 
                            (id_entrada, ruta_archivo, tipo_foto, descripcion, fecha_captura, hash_archivo, metadatos_json) 
                            VALUES (?, ?, 'General', 'Evidencia Recepción', ?, ?, ?)";
                
                $stmt_foto = $conn->prepare($sql_foto);
                $stmt_foto->bind_param("issss", $id_entrada, $ruta_bd, $fecha_captura, $hash_archivo, $metadatos_json);
                $stmt_foto->execute();
            }
        }
    }
    $conn->commit();
    enviarRespuesta(true, "Entrada registrada. Folio: " . $folio);

} catch (Exception $e) {
    $conn->rollback();
    enviarRespuesta(false, "Error del Sistema: " . $e->getMessage());
}
$conn->close();
?>