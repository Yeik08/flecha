<?php
/*
* Portal/portal-taller/php/obtener_detalle_entrada.php
* Obtiene todos los detalles de una entrada específica para el modal.
*/
session_start();
header('Content-Type: application/json');

require_once '../../../php/db_connect.php'; // Ajusta la ruta si es necesario

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Falta el ID']);
    exit;
}

$id_entrada = $_GET['id'];

try {
    // Consulta con JOINs para traer nombres de conductores y la evidencia
    $sql = "SELECT 
                t.*,
                c.numero_economico,
                c.placas,
                c.marca,
                c.anio,
                -- Obtenemos nombres de conductores
                CONCAT(e1.nombre, ' ', e1.apellido_p) as nombre_asignado,
                CONCAT(e2.nombre, ' ', e2.apellido_p) as nombre_entrega,
                -- Obtenemos la foto (tomamos la primera si hubiera varias, aunque el sistema guarda 1 por ahora)
                ev.ruta_archivo as foto_evidencia,
                ev.fecha_captura as fecha_foto_meta
            FROM tb_entradas_taller t
            JOIN tb_camiones c ON t.id_camion = c.id
            LEFT JOIN empleados e1 ON t.id_conductor_asignado = e1.id_empleado
            LEFT JOIN empleados e2 ON t.id_conductor_entrega = e2.id_empleado
            LEFT JOIN tb_evidencias_entrada_taller ev ON t.id = ev.id_entrada
            WHERE t.id = ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_entrada);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Formateo de fechas para que se vean bien
        $row['fecha_ingreso_f'] = date('d/m/Y h:i A', strtotime($row['fecha_ingreso']));
        
        // Ajuste de ruta de imagen (Parche de rutas relativas)
        // La BD guarda "../uploads/...", pero desde taller.php necesitamos "../../uploads/..."
        if ($row['foto_evidencia']) {
            $row['foto_evidencia'] = '../' . $row['foto_evidencia'];
        } else {
            $row['foto_evidencia'] = '../img/sin_foto.png'; // Fallback por si no hay foto
        }

        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Entrada no encontrada']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>