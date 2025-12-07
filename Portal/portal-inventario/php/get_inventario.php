<?php
/*
* Portal/portal-inventario/php/get_inventario.php
* Versión Corregida y Expandida (Sin errores de sintaxis)
*/

// Evitar salidas de texto antes del JSON
ob_start();

session_start();
header('Content-Type: application/json');

// Ajuste de ruta (Subir 3 niveles)
require_once '../../../php/db_connect.php';

// 1. Seguridad
if (!isset($_SESSION['loggedin']) || ($_SESSION['role_id'] != 2 && $_SESSION['role_id'] != 1)) {
    ob_end_clean(); // Limpiar buffer
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

// Inicializar respuesta
$response = [
    'success' => true,
    'kpis' => [
        'filtros_disponibles' => 0,
        'litros_totales' => 0,
        'filtros_instalados' => 0,
        'stock_bajo' => 0
    ],
    'tabla' => []
];

try {
    if (!$conn) {
        throw new Exception("Error de conexión a BD");
    }

    // --- 1. CÁLCULO DE KPIS (TARJETAS) ---
    
    // A. Filtros Disponibles
    $sql_filtros = "SELECT COUNT(*) as total FROM tb_inventario_filtros WHERE estatus = 'Disponible'";
    $res_f = $conn->query($sql_filtros);
    if ($res_f && $row = $res_f->fetch_assoc()) {
        $response['kpis']['filtros_disponibles'] = $row['total'];
    }

    // B. Litros de Aceite
    $sql_aceite = "SELECT SUM(litros_disponibles) as total FROM tb_inventario_lubricantes";
    $res_a = $conn->query($sql_aceite);
    if ($res_a && $row = $res_a->fetch_assoc()) {
        // Validamos si es null (0 si no hay nada)
        $total = $row['total'] ? $row['total'] : 0;
        $response['kpis']['litros_totales'] = number_format($total, 1);
    }

    // C. Filtros Instalados
    $sql_inst = "SELECT COUNT(*) as total FROM tb_inventario_filtros WHERE estatus = 'Instalado'";
    $res_i = $conn->query($sql_inst);
    if ($res_i && $row = $res_i->fetch_assoc()) {
        $response['kpis']['filtros_instalados'] = $row['total'];
    }

    // D. Alertas de Stock Bajo
    $sql_bajos = "SELECT COUNT(*) as tipos_bajos FROM (
                    SELECT id_cat_filtro 
                    FROM tb_inventario_filtros 
                    WHERE estatus = 'Disponible' 
                    GROUP BY id_cat_filtro 
                    HAVING COUNT(*) < 3
                  ) as subquery";
    $res_b = $conn->query($sql_bajos);
    if ($res_b && $row = $res_b->fetch_assoc()) {
        $response['kpis']['stock_bajo'] = $row['tipos_bajos'];
    }


    // --- 2. DATOS PARA LA TABLA ---
    
    $lista_final = [];

    // A. Consultar Filtros
    $sql_tabla_filtros = "SELECT 
                            f.id,
                            'Filtro' as tipo_bien,
                            cf.tipo_filtro as categoria,
                            CONCAT(cf.marca, ' - ', cf.numero_parte) as descripcion,
                            f.numero_serie as identificador,
                            '1 pza' as cantidad_formato,
                            u.nombre as ubicacion,
                            'Disponible' as estatus
                          FROM tb_inventario_filtros f
                          JOIN tb_cat_filtros cf ON f.id_cat_filtro = cf.id
                          JOIN tb_cat_ubicaciones u ON f.id_ubicacion = u.id
                          WHERE f.estatus = 'Disponible'
                          ORDER BY f.id DESC
                          LIMIT 50";
    
    $res_tab_f = $conn->query($sql_tabla_filtros);
    if ($res_tab_f) {
        while ($row = $res_tab_f->fetch_assoc()) {
            $lista_final[] = $row;
        }
    }

    // B. Consultar Lubricantes
    $sql_tabla_lub = "SELECT 
                        l.id,
                        'Lubricante' as tipo_bien,
                        'Aceite' as categoria,
                        cl.nombre_producto as descripcion,
                        'A Granel' as identificador,
                        CONCAT(l.litros_disponibles, ' L') as cantidad_formato,
                        u.nombre as ubicacion,
                        'Disponible' as estatus
                      FROM tb_inventario_lubricantes l
                      JOIN tb_cat_lubricantes cl ON l.id_cat_lubricante = cl.id
                      JOIN tb_cat_ubicaciones u ON l.id_ubicacion = u.id
                      ORDER BY l.id DESC";

    $res_tab_l = $conn->query($sql_tabla_lub);
    if ($res_tab_l) {
        while ($row = $res_tab_l->fetch_assoc()) {
            $lista_final[] = $row;
        }
    }

    $response['tabla'] = $lista_final;

    // Limpiar cualquier salida previa y enviar JSON
    ob_end_clean();
    echo json_encode($response);

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>