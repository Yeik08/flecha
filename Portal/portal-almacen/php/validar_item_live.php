<?php
// flecha/Portal/portal-almacen/php/validar_item_live.php
require_once '../../../php/db_connect.php';

header('Content-Type: application/json');

$serie = trim($_GET['serie'] ?? '');
$tipo_buscado = $_GET['tipo'] ?? ''; // 'filtro' o 'cubeta'

if (empty($serie)) {
    echo json_encode(['valid' => true]); // Si está vacío no mostramos error aún
    exit;
}

try {
    if ($tipo_buscado === 'filtro') {
        $sql = "SELECT i.estatus, c.tipo_filtro, u.nombre as ubicacion 
                FROM tb_inventario_filtros i
                JOIN tb_cat_filtros c ON i.id_cat_filtro = c.id
                JOIN tb_cat_ubicaciones u ON i.id_ubicacion = u.id
                WHERE i.numero_serie = ?";
    } elseif ($tipo_buscado === 'cubeta') {
        $sql = "SELECT i.estatus, l.nombre_producto, u.nombre as ubicacion, i.id_cat_lubricante
                FROM tb_inventario_lubricantes i
                JOIN tb_cat_lubricantes l ON i.id_cat_lubricante = l.id
                JOIN tb_cat_ubicaciones u ON i.id_ubicacion = u.id
                WHERE i.numero_serie = ?";
    } else {
        throw new Exception("Tipo de búsqueda inválido");
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $serie);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        echo json_encode(['valid' => false, 'msg' => '❌ Este número de serie NO existe en el inventario.']);
        exit;
    }

    $item = $res->fetch_assoc();

    // 1. Validar Disponibilidad
    if ($item['estatus'] !== 'Disponible') {
        echo json_encode(['valid' => false, 'msg' => '⚠️ El ítem no está disponible (Estatus: ' . $item['estatus'] . ').']);
        exit;
    }

    // 2. Devolver datos para validaciones extra en JS (ubicación, tipo)
    echo json_encode([
        'valid' => true, 
        'data' => $item,
        'msg' => '✅ Disponible en ' . $item['ubicacion']
    ]);

} catch (Exception $e) {
    echo json_encode(['valid' => false, 'msg' => 'Error: ' . $e->getMessage()]);
}
?>