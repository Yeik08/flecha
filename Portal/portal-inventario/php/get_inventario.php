<?php
/*
* Portal/portal-inventario/php/get_inventario.php
* VERSIÓN KPI DETALLADO: Desglose de filtros y lista de alertas
*/

ob_start();
session_start();
header('Content-Type: application/json');
require_once '../../../php/db_connect.php';

$roles_permitidos = [1, 2, 6];
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role_id'], $roles_permitidos)) {
    ob_end_clean(); 
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

// RECIBIR FILTROS (Ubicación)
$filtro_ubicacion = isset($_GET['ubicacion']) ? trim($_GET['ubicacion']) : 'todos';
$filtro_tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : 'todos'; // Para la tabla

$response = [
    'success' => true,
    'kpis' => [
        'filtros_detalle' => [], // Array: Tipo -> Cantidad
        'cubetas_total' => 0,
        'filtros_instalados' => 0,
        'alertas_lista' => []    // Array de nombres
    ],
    'tabla' => []
];

try {
    if (!$conn) throw new Exception("Error de conexión a BD");

    // --- FILTRO SQL (Solo aplicamos ubicación a los KPIs de stock disponible) ---
    $where_ubi = "";
    $params_ubi = [];
    $types_ubi = "";

    if ($filtro_ubicacion !== 'todos' && $filtro_ubicacion !== '') {
        $where_ubi = " AND u.nombre LIKE ? ";
        $params_ubi[] = "%" . $filtro_ubicacion . "%";
        $types_ubi .= "s";
    }

    // --- 1. CÁLCULO DE KPIS (DETALLADOS) ---
    
    // A. Filtros Disponibles (DESGLOSE POR TIPO)
    // Agrupamos por 'tipo_filtro' para saber cuántos hay de cada uno
    $sql_filtros = "SELECT cf.tipo_filtro, COUNT(*) as total 
                    FROM tb_inventario_filtros f
                    JOIN tb_cat_filtros cf ON f.id_cat_filtro = cf.id
                    JOIN tb_cat_ubicaciones u ON f.id_ubicacion = u.id
                    WHERE f.estatus = 'Disponible' $where_ubi
                    GROUP BY cf.tipo_filtro";
    
    $stmt = $conn->prepare($sql_filtros);
    if($where_ubi) $stmt->bind_param($types_ubi, ...$params_ubi);
    $stmt->execute();
    $res_f = $stmt->get_result();
    
    while ($row = $res_f->fetch_assoc()) {
        $response['kpis']['filtros_detalle'][] = [
            'tipo' => $row['tipo_filtro'],
            'total' => $row['total']
        ];
    }

    // B. Cubetas Disponibles (Total)
    $sql_aceite = "SELECT COUNT(*) as total 
                   FROM tb_inventario_lubricantes l
                   JOIN tb_cat_ubicaciones u ON l.id_ubicacion = u.id
                   WHERE l.estatus = 'Disponible' $where_ubi";
    
    $stmt = $conn->prepare($sql_aceite);
    if($where_ubi) $stmt->bind_param($types_ubi, ...$params_ubi);
    $stmt->execute();
    $response['kpis']['cubetas_total'] = $stmt->get_result()->fetch_assoc()['total'];

    // C. Alertas de Stock Bajo (LISTA DE NOMBRES)
    // Buscamos qué tipos de filtros tienen menos de 3 unidades disponibles EN ESTA UBICACIÓN
    $sql_bajos = "SELECT cf.tipo_filtro, COUNT(*) as cantidad
                  FROM tb_inventario_filtros f
                  JOIN tb_cat_filtros cf ON f.id_cat_filtro = cf.id
                  JOIN tb_cat_ubicaciones u ON f.id_ubicacion = u.id
                  WHERE f.estatus = 'Disponible' $where_ubi
                  GROUP BY f.id_cat_filtro, cf.tipo_filtro
                  HAVING cantidad < 3";
    
    $stmt = $conn->prepare($sql_bajos);
    if($where_ubi) $stmt->bind_param($types_ubi, ...$params_ubi);
    $stmt->execute();
    $res_b = $stmt->get_result();
    
    while ($row = $res_b->fetch_assoc()) {
        $response['kpis']['alertas_lista'][] = $row['tipo_filtro'] . " (" . $row['cantidad'] . ")";
    }
    // Ojo: También podríamos checar si hay CERO de algún tipo que exista en el catálogo pero no en inventario
    // pero por ahora esto alerta sobre lo que sí hay pero es poco.

    // D. Instalados (Global, solo informativo)
    $sql_inst = "SELECT COUNT(*) as total FROM tb_inventario_filtros WHERE estatus = 'Instalado'";
    $response['kpis']['filtros_instalados'] = $conn->query($sql_inst)->fetch_assoc()['total'];


    // --- 2. DATOS PARA LA TABLA ---
    // (Tu lógica de tabla se mantiene igual, filtrando por tipo y ubicación)
    $lista_final = [];
    
    // Filtros
    if ($filtro_tipo === 'todos' || $filtro_tipo === 'Filtro') {
        $sql_tab_f = "SELECT f.id, 'Filtro' as tipo_bien, cf.tipo_filtro as categoria,
                        CONCAT(cf.marca, ' - ', cf.numero_parte) as descripcion,
                        f.numero_serie as identificador, '1 pza' as cantidad_formato,
                        u.nombre as ubicacion
                      FROM tb_inventario_filtros f
                      JOIN tb_cat_filtros cf ON f.id_cat_filtro = cf.id
                      JOIN tb_cat_ubicaciones u ON f.id_ubicacion = u.id
                      WHERE f.estatus = 'Disponible' $where_ubi
                      ORDER BY f.id DESC LIMIT 100";
        $stmt = $conn->prepare($sql_tab_f);
        if($where_ubi) $stmt->bind_param($types_ubi, ...$params_ubi);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $lista_final[] = $row;
    }

    // Cubetas
    if ($filtro_tipo === 'todos' || $filtro_tipo === 'Lubricante') {
        $sql_tab_l = "SELECT l.id, 'Lubricante' as tipo_bien, 'Aceite' as categoria,
                        cl.nombre_producto as descripcion, l.numero_serie as identificador, 
                        '1 Cubeta' as cantidad_formato, u.nombre as ubicacion
                      FROM tb_inventario_lubricantes l
                      JOIN tb_cat_lubricantes cl ON l.id_cat_lubricante = cl.id
                      JOIN tb_cat_ubicaciones u ON l.id_ubicacion = u.id
                      WHERE l.estatus = 'Disponible' $where_ubi
                      ORDER BY l.id DESC LIMIT 100";
        $stmt = $conn->prepare($sql_tab_l);
        if($where_ubi) $stmt->bind_param($types_ubi, ...$params_ubi);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $lista_final[] = $row;
    }

    $response['tabla'] = $lista_final;

    ob_end_clean();
    echo json_encode($response);

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>