<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../../php/db_connect.php';

if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

try {
    // AGREGAMOS 't.estatus_entrada' AL SELECT
$sql = "SELECT 
                t.id, 
                t.folio, 
                t.fecha_ingreso, 
                t.estatus_entrada,
                c.id as id_camion, 
                c.numero_economico, 
                c.placas, 
                c.marca,
                c.serie_filtro_aceite_actual, 
                c.serie_filtro_centrifugo_actual,
                t.tipo_mantenimiento_solicitado,
                u.nombre as origen_taller,
                -- ✅ NUEVOS CAMPOS: Material entregado por Almacén
                t.filtro_aceite_entregado,
                t.filtro_centrifugo_entregado,
                t.cubeta_1_entregada,
                t.cubeta_2_entregada
            FROM tb_entradas_taller t
            JOIN tb_camiones c ON t.id_camion = c.id
            LEFT JOIN tb_cat_ubicaciones u ON t.id_taller = u.id 
            WHERE t.estatus_entrada IN ('Recibido', 'En Proceso')
            ORDER BY 
                CASE WHEN t.estatus_entrada = 'En Proceso' THEN 1 ELSE 2 END, 
                t.fecha_ingreso ASC";
    $result = $conn->query($sql);
    
    // ... (el resto del código sigue igual)
    if (!$result) {
        throw new Exception("Error SQL: " . $conn->error);
    }

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