<?php
/*
* Portal/portal-camiones/php/get_historial_camion.php
* Obtiene el expediente completo de un camión (Entradas, Salidas, Material, Fotos)
*/
session_start();
header('Content-Type: application/json');
require_once '../../../php/db_connect.php';

if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']); exit;
}

$id_camion = $_GET['id'] ?? '';
if (empty($id_camion)) {
    echo json_encode(['success' => false, 'message' => 'ID faltante']); exit;
}

try {
    // 1. Consultar Entradas al Taller (Historial)
    $sql = "SELECT 
                t.id, t.folio, t.fecha_ingreso, t.fecha_fin_reparacion, 
                t.tipo_mantenimiento_solicitado, t.estatus_entrada,
                -- Personal
                CONCAT(m.nombre, ' ', m.apellido_p) as mecanico,
                CONCAT(a.nombre, ' ', a.apellido_p) as almacenista,
                -- Material Usado
                t.filtro_aceite_entregado, t.filtro_centrifugo_entregado,
                t.cubeta_1_entregada, t.cubeta_2_entregada
            FROM tb_entradas_taller t
            LEFT JOIN empleados m ON t.id_mecanico_responsable = m.id_empleado
            LEFT JOIN empleados a ON t.id_almacenista_entrega = a.id_empleado
            WHERE t.id_camion = ?
            ORDER BY t.fecha_ingreso DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_camion);
    $stmt->execute();
    $res = $stmt->get_result();

    $historial = [];

    while ($row = $res->fetch_assoc()) {
        $id_entrada = $row['id'];
        
        // 2. Calcular Duración
        $duracion = "En curso";
        if ($row['fecha_ingreso'] && $row['fecha_fin_reparacion']) {
            $inicio = new DateTime($row['fecha_ingreso']);
            $fin = new DateTime($row['fecha_fin_reparacion']);
            $diff = $inicio->diff($fin);
            $duracion = $diff->days . "d " . $diff->h . "h " . $diff->i . "m";
        }
        $row['duracion'] = $duracion;

        // 3. Obtener Fotos de esta entrada
        $sql_fotos = "SELECT ruta_archivo, tipo_foto FROM tb_evidencias_entrada_taller WHERE id_entrada = ?";
        $stmt_f = $conn->prepare($sql_fotos);
        $stmt_f->bind_param("i", $id_entrada);
        $stmt_f->execute();
        $res_f = $stmt_f->get_result();
        
        $fotos = [];
        while ($f = $res_f->fetch_assoc()) {
            $fotos[] = $f;
        }
        $row['fotos'] = $fotos;

        $historial[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $historial]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>