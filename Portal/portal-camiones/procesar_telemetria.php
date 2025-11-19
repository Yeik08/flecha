<?php
/*
* Portal/portal-camiones/procesar_telemetria.php
* VERSIÓN CON ALERTA DE DUPLICADOS
*/

session_start();
header('Content-Type: application/json');

// --- 1. Seguridad y Conexión ---
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

require_once '../../php/db_connect.php'; 

if ($conn === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión a BD.']);
    exit;
}

// --- 2. Validar Archivo ---
if (!isset($_FILES['archivo_recorridos']) || $_FILES['archivo_recorridos']['error'] != UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Error: No se recibió el archivo.']);
    exit;
}

$csv_file = $_FILES['archivo_recorridos']['tmp_name'];

// --- 3. Procesar el CSV ---
$conn->begin_transaction();
$fila = 0;
$errores = [];
$exitosos = 0;
$duplicados = []; // Lista para guardar los camiones repetidos

try {
    $file = fopen($csv_file, 'r');
    if ($file === FALSE) throw new Exception("No se pudo abrir el archivo CSV.");

    fgetcsv($file); // Omitir encabezados
    $fila = 1;

    while (($columna = fgetcsv($file, 1000, ",")) !== FALSE) {
        $fila++;
        
        $unidad = $columna[0] ?? null;
        $anio = $columna[1] ?? null;
        $mes = $columna[2] ?? null;
        $km_mes = $columna[3] ?? 0;
        $t_conduciendo = $columna[4] ?? 0;
        $t_detenido = $columna[5] ?? 0;
        $t_ralenti = $columna[6] ?? 0;

        if (empty($unidad) || empty($anio) || empty($mes)) {
            continue; // Saltar filas vacías
        }

        // 4. Buscar el ID del camión
        $stmt_camion = $conn->prepare("SELECT id FROM tb_camiones WHERE numero_economico = ?");
        $stmt_camion->bind_param("s", $unidad);
        $stmt_camion->execute();
        $result_camion = $stmt_camion->get_result();
        
        if ($result_camion->num_rows > 0) {
            $camion_id = $result_camion->fetch_assoc()['id'];

            // 5. Insertar o Actualizar
            $sql_insert = "INSERT INTO tb_telemetria_historico 
                (camion_id, anio, mes, kilometraje_mes, tiempo_conduciendo_horas, tiempo_detenido_horas, tiempo_ralenti_horas) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                kilometraje_mes = VALUES(kilometraje_mes),
                tiempo_conduciendo_horas = VALUES(tiempo_conduciendo_horas),
                tiempo_detenido_horas = VALUES(tiempo_detenido_horas),
                tiempo_ralenti_horas = VALUES(tiempo_ralenti_horas)";
            
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("iiidddd", $camion_id, $anio, $mes, $km_mes, $t_conduciendo, $t_detenido, $t_ralenti);
            $stmt_insert->execute();

            // --- DETECCIÓN DE DUPLICADOS ---
            // Si affected_rows es 2, significa que actualizó un registro existente
            if ($stmt_insert->affected_rows == 2) {
                $duplicados[] = "$unidad (Mes $mes/$anio)";
            }
            
            // 6. Recalcular Mantenimiento
            recalcularMantenimiento($conn, $camion_id);
            $exitosos++;
            
        } else {
            $errores[] = "Fila $fila: Camión '$unidad' no encontrado.";
        }
    }
    fclose($file);

    if (empty($errores)) {
        $conn->commit();
        
        // Preparamos el mensaje de respuesta
        $response = [
            'success' => true, 
            'message' => "Proceso completado. $exitosos registros procesados.",
            'lista_duplicados' => $duplicados // Enviamos la lista al JS
        ];
        echo json_encode($response);

    } else {
        throw new Exception("Errores: " . implode(" | ", $errores));
    }

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// --- Función auxiliar para recalcular ---
function recalcularMantenimiento($conn, $camion_id) {
    $sql_intervalos = "SELECT c.fecha_ult_cambio_aceite, t.intervalo_km_aceite, t.intervalo_horas_aceite 
                       FROM tb_camiones c
                       LEFT JOIN tb_tecnologias_carroceria t ON c.id_tecnologia = t.id
                       WHERE c.id = ?";
    $stmt_int = $conn->prepare($sql_intervalos);
    $stmt_int->bind_param("i", $camion_id);
    $stmt_int->execute();
    $result_int = $stmt_int->get_result()->fetch_assoc();

    if (!$result_int || empty($result_int['fecha_ult_cambio_aceite'])) return;
    
    $fecha_ultimo_mto = $result_int['fecha_ult_cambio_aceite'];
    $intervalo_km = $result_int['intervalo_km_aceite'] ?? 9999999;
    $intervalo_horas = $result_int['intervalo_horas_aceite'] ?? 9999999;
    
    if ($intervalo_km == 0 || $intervalo_horas == 0) return;

    $sql_acumulado = "SELECT SUM(kilometraje_mes) AS km_acumulados, SUM(horas_operacion_mes) AS horas_acumuladas 
                      FROM tb_telemetria_historico 
                      WHERE camion_id = ? AND MAKEDATE(anio, 1) + INTERVAL (mes - 1) MONTH > ?";
    $stmt_acum = $conn->prepare($sql_acumulado);
    $stmt_acum->bind_param("is", $camion_id, $fecha_ultimo_mto);
    $stmt_acum->execute();
    $acumulado = $stmt_acum->get_result()->fetch_assoc();

    $km_usados = $acumulado['km_acumulados'] ?? 0;
    $horas_usadas = $acumulado['horas_acumuladas'] ?? 0;

    if ($km_usados >= $intervalo_km || $horas_usadas >= $intervalo_horas) {
        $conn->query("UPDATE tb_camiones SET mantenimiento_requerido = 'Si' WHERE id = $camion_id");
    } else {
        $conn->query("UPDATE tb_camiones SET mantenimiento_requerido = 'No' WHERE id = $camion_id");
    }
}
?>