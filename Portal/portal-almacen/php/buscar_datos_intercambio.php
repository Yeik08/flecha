<?php
session_start();
header('Content-Type: application/json');
require_once '../../../php/db_connect.php';

// Validar sesión
if (!isset($_SESSION['loggedin']) || ($_SESSION['role_id'] != 6 && $_SESSION['role_id'] != 1)) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']); exit;
}

$ticket = $_GET['ticket'] ?? '';

if (empty($ticket)) {
    echo json_encode(['success' => false, 'message' => 'Ingresa un folio.']); exit;
}

try {
    // Consulta LIMPIA (Sin comentarios para evitar errores de sintaxis)
    $sql = "SELECT 
                t.id, 
                t.folio, 
                t.estatus_entrada,
                c.id as id_camion, 
                c.numero_economico, 
                c.placas,
                c.serie_filtro_aceite_actual, 
                c.serie_filtro_centrifugo_actual,
                c.proximo_servicio_tipo,
                c.lubricante_sugerido,
                CONCAT(e.nombre, ' ', e.apellido_p) as nombre_mecanico,
                t.filtro_aceite_entregado
            FROM tb_entradas_taller t
            JOIN tb_camiones c ON t.id_camion = c.id
            LEFT JOIN empleados e ON t.id_mecanico_responsable = e.id_empleado
            WHERE t.folio = ? LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error en la consulta: " . $conn->error);
    }
    
    $stmt->bind_param("s", $ticket);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        // 1. Validar que el mecánico haya iniciado
        if ($row['estatus_entrada'] !== 'En Proceso') {
            echo json_encode([
                'success' => false, 
                'message' => '⛔ El mecánico aún no ha aceptado ("Iniciado") esta unidad. No se puede entregar material.'
            ]);
            exit;
        }

        // 2. Validar si ya se entregó
        if (!empty($row['filtro_aceite_entregado'])) {
             echo json_encode([
                'success' => false, 
                'message' => '⚠️ ALERTA: Este ticket YA FUE SURTIDO anteriormente.'
            ]);
            exit;
        }
        
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ticket no encontrado.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>