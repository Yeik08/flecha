<?php
session_start();
// (Aquí va tu código de seguridad y conexión a db_connect.php)

// --- 1. Seguridad y Conexión ---
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

require_once '../../php/db_connect.php'; 

// 1. Recibir el archivo CSV (ej: $_FILES['archivo_telemetria'])
$csv_file = $_FILES['archivo_telemetria']['tmp_name'];

// 2. Leer el CSV (saltando la primera fila de encabezados)
$file = fopen($csv_file, 'r');
fgetcsv($file); // Omitir encabezados

while (($columna = fgetcsv($file, 1000, ",")) !== FALSE) {
    
    // 3. Obtener datos de la fila
    $unidad = $columna[0];
    $anio = $columna[1];
    $mes = $columna[2];
    $km_mes = $columna[3];
    $t_conduciendo = $columna[4];
    $t_detenido = $columna[5];
    $t_ralenti = $columna[6];

    try {
        // 4. Buscar el ID del camión
        $stmt_camion = $conn->prepare("SELECT id FROM tb_camiones WHERE numero_economico = ?");
        $stmt_camion->bind_param("s", $unidad);
        $stmt_camion->execute();
        $result_camion = $stmt_camion->get_result();
        
        if ($result_camion->num_rows > 0) {
            $camion_id = $result_camion->fetch_assoc()['id'];

            // 5. Insertar o Actualizar la telemetría
            // (Usamos ON DUPLICATE KEY UPDATE por si suben un mes que ya existía)
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

            // 6. ¡RECALCULAR MANTENIMIENTO! (Tu lógica clave)
            recalcularMantenimiento($conn, $camion_id);
        }
    } catch (Exception $e) {
        // Registrar error de esta fila
    }
}
fclose($file);

// --- FIN DEL SCRIPT PRINCIPAL ---


/*
* Esta es la función que "agenda" el mantenimiento
*/
function recalcularMantenimiento($conn, $camion_id) {
    
    // 1. Obtener los intervalos para este camión
    $sql_intervalos = "SELECT 
        c.fecha_ult_cambio_aceite, 
        t.intervalo_km_aceite, 
        t.intervalo_horas_aceite 
      FROM tb_camiones c
      JOIN tb_tecnologias_carroceria t ON c.id_tecnologia = t.id
      WHERE c.id = ?";
    
    $stmt_int = $conn->prepare($sql_intervalos);
    $stmt_int->bind_param("i", $camion_id);
    $stmt_int->execute();
    $result_int = $stmt_int->get_result()->fetch_assoc();

    $fecha_ultimo_mto = $result_int['fecha_ult_cambio_aceite'];
    $intervalo_km = $result_int['intervalo_km_aceite'];
    $intervalo_horas = $result_int['intervalo_horas_aceite'];

    // 2. Sumar todo el uso DESPUÉS del último mantenimiento
    $sql_acumulado = "SELECT 
        SUM(kilometraje_mes) AS km_acumulados, 
        SUM(horas_operacion_mes) AS horas_acumuladas 
      FROM tb_telemetria_historico 
      WHERE camion_id = ? 
      AND MAKEDATE(anio, 1) + INTERVAL (mes - 1) MONTH > ?";
      // (MAKEDATE crea una fecha como '2025-10-01' para comparar)

    $stmt_acum = $conn->prepare($sql_acumulado);
    $stmt_acum->bind_param("is", $camion_id, $fecha_ultimo_mto);
    $stmt_acum->execute();
    $acumulado = $stmt_acum->get_result()->fetch_assoc();

    $km_usados = $acumulado['km_acumulados'] ?? 0;
    $horas_usadas = $acumulado['horas_acumuladas'] ?? 0;

    // 3. Tomar la decisión
    if ($km_usados >= $intervalo_km || $horas_usadas >= $intervalo_horas) {
        // ¡Se necesita mantenimiento!
        $conn->query("UPDATE tb_camiones SET mantenimiento_requerido = 'Si' WHERE id = $camion_id");
    } else {
        // Aún no
        $conn->query("UPDATE tb_camiones SET mantenimiento_requerido = 'No' WHERE id = $camion_id");
    }
}
?>