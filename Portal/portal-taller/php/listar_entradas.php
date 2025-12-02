<?php
session_start();
header('Content-Type: application/json');

// Ajusta la ruta si es necesario
require_once '../../../php/db_connect.php';

// Seguridad: Solo Receptor (5) o Admin (1)
if (!isset($_SESSION['loggedin']) || ($_SESSION['role_id'] != 5 && $_SESSION['role_id'] != 1)) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

try {
    // Seleccionamos datos clave y calculamos alertas
    $sql = "SELECT 
                t.id,
                t.folio,
                c.numero_economico,
                c.placas,
                t.fecha_ingreso,
                t.tipo_mantenimiento_solicitado as tipo,
                t.estatus_entrada,
                t.clasificacion_tiempo, -- Para alertas de tiempo
                t.alerta_conductor      -- Para alertas de chofer
            FROM tb_entradas_taller t
            JOIN tb_camiones c ON t.id_camion = c.id
            ORDER BY t.fecha_ingreso DESC
            LIMIT 50"; // Limitamos a las últimas 50 para no saturar

    $result = $conn->query($sql);
    
    $entradas = [];
    while ($row = $result->fetch_assoc()) {
        // Formateamos la fecha para que se vea bonita (DD/MM/YYYY HH:MM)
        $date = new DateTime($row['fecha_ingreso']);
        $row['fecha_formato'] = $date->format('d/m/Y H:i');
        $entradas[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $entradas]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>