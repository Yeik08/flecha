
<?php
/*
* Portal/portal-mecanico/php/listar_pendientes_mecanico.php
* Muestra los camiones que están "En Taller" esperando servicio.
*/
session_start();
header('Content-Type: application/json');
require_once '../../../php/db_connect.php';

if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

try {
    // Buscamos entradas activas (Recibido o En Proceso)
    $sql = "SELECT 
                t.id, t.folio, t.fecha_ingreso, 
                c.numero_economico, c.placas, c.marca,
                c.serie_filtro_aceite_actual, c.serie_filtro_centrifugo_actual,
                t.tipo_mantenimiento_solicitado
            FROM tb_entradas_taller t
            JOIN tb_camiones c ON t.id_camion = c.id
            WHERE t.estatus_entrada IN ('Recibido', 'En Proceso')
            ORDER BY t.fecha_ingreso ASC"; // Los más viejos primero (FIFO)

    $result = $conn->query($sql);
    
    $pendientes = [];
    while ($row = $result->fetch_assoc()) {
        $pendientes[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $pendientes]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>