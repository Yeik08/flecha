<?php
/*
* Portal/portal-camiones/api_camiones.php
* VERSIÓN V2: Soporta buscar TODOS o UNO solo por ID.
*/

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 2) {
    echo json_encode(['success' => false]); exit;
}
require_once '../../php/db_connect.php'; 

// ¿Nos pidieron un ID específico?
$id_camion = $_GET['id'] ?? null;

try {
    if ($id_camion) {
        // --- MODO: DETALLES DE UN CAMIÓN (Para Editar) ---
        // Hacemos JOIN con empleados para obtener el ID interno del conductor (ej: CON-012)
        $sql = "SELECT 
                    c.*, 
                    e.id_interno as id_interno_conductor 
                FROM tb_camiones c
                LEFT JOIN empleados e ON c.id_conductor_asignado = e.id_empleado
                WHERE c.id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_camion);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Camión no encontrado']);
        }

    } else {
        // --- MODO: LISTA COMPLETA (Para la Tabla) ---
        $sql = "SELECT id, numero_economico, placas, estatus, fecha_ult_mantenimiento, fecha_estimada_mantenimiento, mantenimiento_requerido 
                FROM tb_camiones ORDER BY numero_economico ASC";
        $result = $conn->query($sql);
        $camiones = [];
        while ($row = $result->fetch_assoc()) { $camiones[] = $row; }
        echo json_encode(['success' => true, 'data' => $camiones]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
?>

