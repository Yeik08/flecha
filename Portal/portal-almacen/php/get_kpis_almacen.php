<?php
require_once '../../../php/db_connect.php';
$id_almacen = $_GET['id_almacen'] ?? 0; // 3=Magdalena, 4=Poniente, etc.

$sql = "SELECT 
    (SELECT COUNT(*) FROM tb_inventario_filtros WHERE estatus='Disponible' AND id_ubicacion = ?) as filtros,
    (SELECT COUNT(*) FROM tb_inventario_lubricantes WHERE estatus='Disponible' AND id_ubicacion = ?) as cubetas";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_almacen, $id_almacen);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

echo json_encode($res);
?>