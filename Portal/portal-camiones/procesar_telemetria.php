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

// 1. Obtener configuración y último mantenimiento
    $sql_config = "SELECT 
        c.fecha_ult_cambio_aceite, 
        t.intervalo_horas_aceite -- Este es el 1350
      FROM tb_camiones c
      LEFT JOIN tb_tecnologias_carroceria t ON c.id_tecnologia = t.id
      WHERE c.id = ?";
      
    $stmt = $conn->prepare($sql_config);
    $stmt->bind_param("i", $camion_id);
    $stmt->execute();
    $config = $stmt->get_result()->fetch_assoc();

    if (!$config || empty($config['fecha_ult_cambio_aceite'])) return; // No se puede calcular sin fecha base
    
    $limite_horas = $config['intervalo_horas_aceite'] ?? 1350; // Default 1350 si no tiene tecnología
    $fecha_base = new DateTime($config['fecha_ult_cambio_aceite']);

    // 2. Calcular el PROMEDIO de los últimos 3 meses (Tu lógica de "Promedio Operativo")
    // Usamos solo los últimos 3 registros para que sea un promedio "fresco" y real.
    $sql_promedio = "SELECT AVG(horas_operacion_mes) as promedio 
                     FROM (
                        SELECT horas_operacion_mes 
                        FROM tb_telemetria_historico 
                        WHERE camion_id = ? 
                        ORDER BY anio DESC, mes DESC 
                        LIMIT 3
                     ) as ultimos_meses";
                     
    $stmt_prom = $conn->prepare($sql_promedio);
    $stmt_prom->bind_param("i", $camion_id);
    $stmt_prom->execute();
    $res_prom = $stmt_prom->get_result()->fetch_assoc();
    
    $promedio_mensual = floatval($res_prom['promedio']);

    // Evitar división por cero si el camión no ha trabajado
    if ($promedio_mensual < 1) $promedio_mensual = 1; 

    // 3. LA FÓRMULA MAESTRA (Tu requerimiento)
    // Ejemplo: 1350 / 242.1 = 5.57 meses de vida útil
    $meses_vida_util = $limite_horas / $promedio_mensual;
    
    // Convertir meses a días (aprox 30.4 días por mes)
    $dias_vida_util = round($meses_vida_util * 30.4);
    
    // 4. Calcular la FECHA ESTIMADA
    // Fecha Estimada = Fecha Último Manto + Días de Vida Útil
    $fecha_estimada = clone $fecha_base;
    $fecha_estimada->modify("+$dias_vida_util days");
    $fecha_estimada_str = $fecha_estimada->format('Y-m-d');

    // 5. Definir el ESTADO con "Margen de Error" (Semáforo)
    $hoy = new DateTime();
    $dias_restantes = $hoy->diff($fecha_estimada)->format('%r%a'); // Positivo si falta, negativo si pasó
    $dias_restantes = intval($dias_restantes);

    $estado = 'Ok';
    // Margen de entrada prematura: Si falta menos de 1 mes (30 días), ya es "Próximo"
    if ($dias_restantes < 30 && $dias_restantes >= 0) {
        $estado = 'Próximo';
    } elseif ($dias_restantes < 0) {
        // Margen de entrada tardía: Ya se pasó la fecha
        $estado = 'Vencido';
    }

    // 6. GUARDAR EN LA BASE DE DATOS Y AUDITAR
    
    // Actualizar el camión
    $sql_update = "UPDATE tb_camiones SET 
        promedio_horas_mensual = ?,
        meses_estimados_vida = ?,
        fecha_estimada_mantenimiento = ?,
        estado_salud = ?
        WHERE id = ?";
    
    $stmt_up = $conn->prepare($sql_update);
    $stmt_up->bind_param("ddssi", $promedio_mensual, $meses_vida_util, $fecha_estimada_str, $estado, $camion_id);
    $stmt_up->execute();

    // 7. AUDITORÍA (Registrar el cálculo)
    // Solo guardamos auditoría si el estado cambió a Próximo/Vencido o si es un cambio drástico, 
    // para no llenar la tabla. Aquí guardamos siempre para que veas que funciona.
    $sql_audit = "INSERT INTO tb_auditoria_calculos (id_camion, promedio_usado, nueva_fecha_estimada, motivo) VALUES (?, ?, ?, ?)";
    $motivo = "Cálculo Mensual (Vida útil: " . round($meses_vida_util, 1) . " meses)";
    $stmt_audit = $conn->prepare($sql_audit);
    $stmt_audit->bind_param("idss", $camion_id, $promedio_mensual, $fecha_estimada_str, $motivo);
    $stmt_audit->execute();


    // 1. Obtener datos del camión
    $sql_config = "SELECT 
        c.kilometraje_total,
        c.fecha_ult_cambio_aceite, 
        c.fecha_ult_cambio_centrifugo,
        t.intervalo_horas_aceite 
      FROM tb_camiones c
      LEFT JOIN tb_tecnologias_carroceria t ON c.id_tecnologia = t.id
      WHERE c.id = ?";
      
    $stmt = $conn->prepare($sql_config);
    $stmt->bind_param("i", $camion_id);
    $stmt->execute();
    $config = $stmt->get_result()->fetch_assoc();

    if (!$config) return;
    
    // Configuración base
    $intervalo_aceite_hrs = $config['intervalo_horas_aceite'] ?? 1350;
    $intervalo_centrifugo_hrs = $intervalo_aceite_hrs * 2; // Regla: Cada 2 mantenimientos (2700)

    // 2. Calcular Promedio de Uso (Últimos 3 meses)
    $sql_promedio = "SELECT AVG(horas_operacion_mes) as promedio 
                     FROM (
                        SELECT horas_operacion_mes 
                        FROM tb_telemetria_historico 
                        WHERE camion_id = ? 
                        ORDER BY anio DESC, mes DESC 
                        LIMIT 3
                     ) as ultimos_meses";
    $stmt_prom = $conn->prepare($sql_promedio);
    $stmt_prom->bind_param("i", $camion_id);
    $stmt_prom->execute();
    $res_prom = $stmt_prom->get_result()->fetch_assoc();
    $promedio_mensual = floatval($res_prom['promedio']);
    if ($promedio_mensual < 1) $promedio_mensual = 1; // Evitar div/0

    // --- CÁLCULO 1: ACEITE ---
    $fecha_base_aceite = $config['fecha_ult_cambio_aceite'] ? new DateTime($config['fecha_ult_cambio_aceite']) : new DateTime();
    $meses_vida_aceite = $intervalo_aceite_hrs / $promedio_mensual;
    $dias_vida_aceite = round($meses_vida_aceite * 30.4);
    
    $f_est_aceite = clone $fecha_base_aceite;
    $f_est_aceite->modify("+$dias_vida_aceite days");
    
    // Semáforo Aceite
    $hoy = new DateTime();
    $dias_rest_aceite = intval($hoy->diff($f_est_aceite)->format('%r%a'));
    $estado_aceite = 'Ok';
    if ($dias_rest_aceite < 0) $estado_aceite = 'Vencido';
    elseif ($dias_rest_aceite < 30) $estado_aceite = 'Próximo';

    // --- CÁLCULO 2: CENTRÍFUGO ---
    $fecha_base_cent = $config['fecha_ult_cambio_centrifugo'] ? new DateTime($config['fecha_ult_cambio_centrifugo']) : new DateTime();
    $meses_vida_cent = $intervalo_centrifugo_hrs / $promedio_mensual;
    $dias_vida_cent = round($meses_vida_cent * 30.4);
    
    $f_est_cent = clone $fecha_base_cent;
    $f_est_cent->modify("+$dias_vida_cent days");

    // Semáforo Centrífugo
    $dias_rest_cent = intval($hoy->diff($f_est_cent)->format('%r%a'));
    $estado_centrifugo = 'Ok';
    if ($dias_rest_cent < 0) $estado_centrifugo = 'Vencido';
    elseif ($dias_rest_cent < 30) $estado_centrifugo = 'Próximo';

    // --- CÁLCULO 3: REGLA DEL LUBRICANTE (1 Millón KM) ---
    $km_actual = floatval($config['kilometraje_total']);
    $lubricante_sugerido = ($km_actual >= 1000000) ? "SAE 15W30" : "SAE 10W30 MULTIGRADO";

    // 4. ACTUALIZAR TODO EN LA BD
    $sql_update = "UPDATE tb_camiones SET 
        promedio_horas_mensual = ?,
        meses_estimados_vida = ?,
        fecha_estimada_mantenimiento = ?,
        estado_salud = ?,
        fecha_estimada_centrifugo = ?,
        estado_centrifugo = ?,
        lubricante_sugerido = ?
        WHERE id = ?";
    
    $f_aceite_str = $f_est_aceite->format('Y-m-d');
    $f_cent_str = $f_est_cent->format('Y-m-d');

    $stmt_up = $conn->prepare($sql_update);
    $stmt_up->bind_param("ddsssssi", 
        $promedio_mensual, 
        $meses_vida_aceite, 
        $f_aceite_str, 
        $estado_aceite,
        $f_cent_str,
        $estado_centrifugo,
        $lubricante_sugerido,
        $camion_id
    );
    $stmt_up->execute();


}






?>