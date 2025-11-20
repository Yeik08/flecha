<?php
/*
* Portal/portal-taller/php/registrar_entrada.php
* Procesa el formulario de recepción de unidades, valida conductores y tiempos,
* y guarda la evidencia fotográfica.
*/

session_start();
header('Content-Type: application/json');

// --- 1. Seguridad ---
// Validamos que el usuario esté logueado y tenga permisos (Rol 5 = Receptor, Rol 1 = Admin)
if (!isset($_SESSION['loggedin']) || ($_SESSION['role_id'] != 5 && $_SESSION['role_id'] != 1)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

// Conexión a la base de datos (Ajusta la ruta según tu estructura)
// Si este archivo está en /Portal/portal-taller/php/, subimos 3 niveles para llegar a /php/
require_once '../../php/db_connect.php'; 

if ($conn === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

$id_recepcionista = $_SESSION['user_id']; 
$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método de solicitud no válido.');
    }

    // Iniciamos transacción para asegurar integridad (todo o nada)
    $conn->begin_transaction();

    // --- 2. Recibir Datos del Formulario ---
    
    $id_camion = $_POST['id_camion'] ?? null; // Viene del input hidden 'id_camion_seleccionado' o 'id_camion'
    // En tu JS/HTML el name es 'id_camion', asegurémonos de recibirlo.
    if (!$id_camion) $id_camion = $_POST['id_camion_seleccionado'] ?? null;

    $km_llegada = $_POST['kilometraje_entrada'] ?? 0;
    $combustible = $_POST['nivel_combustible'] ?? ''; // Ej: 1/4, Lleno
    $tipo_mto = $_POST['tipo_mantenimiento'] ?? ''; // Ej: Mantenimiento Preventivo
    $obs = $_POST['observaciones_recepcion'] ?? ''; 
    $fecha_ingreso_form = $_POST['fecha_ingreso'] ?? date('Y-m-d H:i:s'); // Si el usuario cambió la fecha
    
    // Conductores
    $id_cond_asignado = !empty($_POST['id_conductor_asignado']) ? $_POST['id_conductor_asignado'] : null;
    $id_cond_entrega = !empty($_POST['id_conductor_entrega']) ? $_POST['id_conductor_entrega'] : null;

    if (!$id_camion) {
        throw new Exception("No se ha seleccionado un camión válido.");
    }

    // --- 3. Lógica de Negocio ---

    // A. Alerta de Conductor
    // Si hay un conductor asignado y el que entrega es diferente, marcamos alerta.
    $alerta_conductor = 'No';
    if ($id_cond_asignado && $id_cond_entrega && $id_cond_asignado != $id_cond_entrega) {
        $alerta_conductor = 'Si';
    }

    // B. Cálculo de Tiempos (Temprano/Tarde)
    $clasificacion_tiempo = 'No Programado';
    $dias_diferencia = 0;

    // Consultamos la fecha estimada del camión
    $sql_fecha = "SELECT fecha_estimada_mantenimiento FROM tb_camiones WHERE id = ?";
    $stmt_f = $conn->prepare($sql_fecha);
    $stmt_f->bind_param("i", $id_camion);
    $stmt_f->execute();
    $res_f = $stmt_f->get_result()->fetch_assoc();

    if ($res_f && !empty($res_f['fecha_estimada_mantenimiento'])) {
        $fecha_est = new DateTime($res_f['fecha_estimada_mantenimiento']);
        $fecha_real = new DateTime($fecha_ingreso_form);
        
        // Calculamos la diferencia en días
        // Si Fecha Real < Fecha Estimada -> Diferencia es negativa (Anticipado)
        // Si Fecha Real > Fecha Estimada -> Diferencia es positiva (Tarde)
        $intervalo = $fecha_est->diff($fecha_real);
        $dias = (int)$intervalo->format('%r%a'); // %r incluye el signo (+/-)
        
        $dias_diferencia = $dias;

        if ($dias < -7) {
            $clasificacion_tiempo = 'Anticipado';
        } elseif ($dias > 7) {
            $clasificacion_tiempo = 'Tarde';
        } else {
            $clasificacion_tiempo = 'A Tiempo';
        }
    }

    // --- 4. Insertar en tb_entradas_taller ---

    $folio = "ENT-" . date('ymd') . "-" . rand(1000, 9999); // Generar Folio Único
    $id_taller = 1; // Valor por defecto (Taller Magdalena). Podrías recibirlo por POST si hay varios.

    $sql_insert = "INSERT INTO tb_entradas_taller (
        folio, 
        id_camion, 
        fecha_ingreso, 
        id_recepcionista, 
        id_taller, 
        tipo_mantenimiento_solicitado, 
        kilometraje_entrada, 
        nivel_combustible,
        id_conductor_asignado, 
        id_conductor_entrega, 
        alerta_conductor, 
        clasificacion_tiempo, 
        dias_desviacion, 
        observaciones_recepcion, 
        estatus_entrada
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Recibido')";

    $stmt = $conn->prepare($sql_insert);
    if (!$stmt) throw new Exception("Error en prepare SQL: " . $conn->error);

    // Tipos: s=string, i=int, d=double
    // Cadena: s i s i i s d s i i s s i s
    $stmt->bind_param("sisiisdssiisis", 
        $folio, 
        $id_camion, 
        $fecha_ingreso_form, 
        $id_recepcionista, 
        $id_taller, 
        $tipo_mto, 
        $km_llegada, 
        $combustible,
        $id_cond_asignado, 
        $id_cond_entrega, 
        $alerta_conductor, 
        $clasificacion_tiempo, 
        $dias_diferencia, 
        $obs
    );

    if (!$stmt->execute()) {
        throw new Exception("Error al guardar la entrada: " . $stmt->error);
    }
    
    $id_entrada = $conn->insert_id;


    // --- 5. Actualizar Estatus del Camión ---
    $sql_update_camion = "UPDATE tb_camiones SET estatus = 'En Taller' WHERE id = ?";
    $stmt_up = $conn->prepare($sql_update_camion);
    $stmt_up->bind_param("i", $id_camion);
    $stmt_up->execute();


    // --- 6. Guardar Evidencia Fotográfica ---
    // El campo en el HTML es 'foto_entrada'
    if (isset($_FILES['foto_entrada']) && $_FILES['foto_entrada']['error'] === UPLOAD_ERR_OK) {
        
        $nombre_original = $_FILES['foto_entrada']['name'];
        $ext = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
        
        // Validar extensión
        $permitidos = ['jpg', 'jpeg', 'png'];
        if (in_array($ext, $permitidos)) {
            
            // Nombre único para evitar sobrescritura
            $nombre_foto = "EVIDENCIA_" . $folio . "_" . time() . "." . $ext;
            
            // Ruta relativa donde se guardará (ajusta según tu estructura de carpetas)
            // Asumiendo: Portal/portal-taller/php/ -> subimos a Portal/ -> subimos a root -> uploads/
            $carpeta_destino = "../../uploads/evidencias_entradas/";
            
            if (!file_exists($carpeta_destino)) {
                mkdir($carpeta_destino, 0777, true);
            }
            
            $ruta_completa = $carpeta_destino . $nombre_foto;
            
            if (move_uploaded_file($_FILES['foto_entrada']['tmp_name'], $ruta_completa)) {
                // Guardar ruta en BD (guardamos ruta relativa para mostrarla fácil web)
                $ruta_bd = "../uploads/evidencias_entradas/" . $nombre_foto;
                
                $sql_foto = "INSERT INTO tb_evidencias_entrada_taller (id_entrada, ruta_archivo, tipo_foto, descripcion) VALUES (?, ?, 'Entrada', 'Evidencia de Recepción')";
                $stmt_foto = $conn->prepare($sql_foto);
                $stmt_foto->bind_param("is", $id_entrada, $ruta_bd);
                $stmt_foto->execute();
            }
        }
    }

    // --- 7. Finalizar ---
    $conn->commit();
    
    $response['success'] = true;
    $response['message'] = "Entrada registrada correctamente.\nFolio: " . $folio . "\nEstatus: " . $clasificacion_tiempo;

} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = "Error: " . $e->getMessage();
}

echo json_encode($response);
$conn->close();
?>