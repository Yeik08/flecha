<?php
/*
* Portal/portal-mecanico/php/buscar_ticket.php
* Busca una entrada de taller activa por su Folio.
*/
session_start();
header('Content-Type: application/json');
require_once '../../../php/db_connect.php'; 

// Seguridad básica
if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$ticket = $_GET['ticket'] ?? '';

if (empty($ticket)) {
    echo json_encode(['success' => false, 'message' => 'Ingresa un folio.']);
    exit;
}

try {
    // Buscamos la entrada si NO ha sido "Entregada" aún
    $sql = "SELECT 
                t.id, t.folio, t.fecha_ingreso, t.tipo_mantenimiento_solicitado,
                c.numero_economico, c.placas, c.marca
            FROM tb_entradas_taller t
            JOIN tb_camiones c ON t.id_camion = c.id
            WHERE t.folio = ? AND t.estatus_entrada != 'Entregado'
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $ticket);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ticket no encontrado o ya cerrado.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>