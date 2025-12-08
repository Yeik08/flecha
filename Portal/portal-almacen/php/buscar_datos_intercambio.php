<?php
session_start();
header('Content-Type: application/json');
require_once '../../../php/db_connect.php';

// Solo Almacén (6) o Admin (1)
if (!isset($_SESSION['loggedin']) || ($_SESSION['role_id'] != 6 && $_SESSION['role_id'] != 1)) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']); exit;
}

$ticket = $_GET['ticket'] ?? '';

if (empty($ticket)) {
    echo json_encode(['success' => false, 'message' => 'Ingresa un folio.']); exit;
}

try {
    // Buscamos la entrada
    // VALIDACIÓN CLAVE: El estatus debe ser 'En Proceso' (Mecánico ya inició)
    $sql = "SELECT 
                t.id, t.folio, t.estatus_entrada,
                c.id as id_camion, c.numero_economico, c.placas,
                c.serie_filtro_aceite_actual, 
                c.serie_filtro_centrifugo_actual,
                c.proximo_servicio_tipo,  -- ✅ NUEVO CAMPO IMPORTANTE
                CONCAT(e.nombre, ' ', e.apellido_p) as nombre_mecanico
            FROM tb_entradas_taller t
            JOIN tb_camiones c ON t.id_camion = c.id
            LEFT JOIN empleados e ON t.id_mecanico_responsable = e.id_empleado
            WHERE t.folio = ? LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $ticket);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        // Regla de Negocio: El mecánico debe haber aceptado el camión primero
        if ($row['estatus_entrada'] !== 'En Proceso') {
            echo json_encode([
                'success' => false, 
                'message' => '⛔ El mecánico aún no ha aceptado ("Iniciado") esta unidad. No se puede entregar material.'
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
