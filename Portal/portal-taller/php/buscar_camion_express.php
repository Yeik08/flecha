<?php
/* Portal/portal-taller/php/buscar_camion_express.php */
session_start();
header('Content-Type: application/json');
// Ajusta la ruta según tu estructura real. Si estás en /php/, subes 3 niveles.
require_once '../../../php/db_connect.php'; 

if (!isset($_GET['q'])) exit;

$busqueda = "%" . $_GET['q'] . "%";

// Buscamos datos vitales: Próximo Mantenimiento y Conductor Asignado
$sql = "SELECT 
            c.id, 
            c.numero_economico, 
            c.placas, 
            c.fecha_estimada_mantenimiento,
            c.estado_salud,
            e.id_empleado as id_chofer_asignado,
            CONCAT(e.nombre, ' ', e.apellido_p) as nombre_chofer,
            e.id_interno as id_interno_chofer
        FROM tb_camiones c
        LEFT JOIN empleados e ON c.id_conductor_asignado = e.id_empleado
        WHERE c.numero_economico LIKE ? OR c.placas LIKE ? 
        LIMIT 10";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $busqueda, $busqueda);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode($data);
?>