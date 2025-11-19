<?php
/*
* Portal/portal-camiones/editar_camion.php
* Actualiza datos operativos del camión.
*/
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 2) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']); exit;
}
require_once '../../php/db_connect.php'; 

$id_camion = $_POST['id_camion'] ?? null;
$estatus = $_POST['estatus'] ?? '';
$placas = $_POST['placas'] ?? '';
$km = $_POST['kilometraje_total'] ?? 0;
$id_conductor_texto = $_POST['id_conductor'] ?? '';

try {
    if (!$id_camion) throw new Exception("Falta ID del camión.");

    // 1. Buscar ID numérico del conductor (si se cambió)
    $id_conductor_numerico = null;
    if (!empty($id_conductor_texto)) {
        $stmt = $conn->prepare("SELECT id_empleado FROM empleados WHERE id_interno = ? AND role_id = 7");
        $stmt->bind_param("s", $id_conductor_texto);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $id_conductor_numerico = $row['id_empleado'];
        } else {
            throw new Exception("Conductor '$id_conductor_texto' no encontrado.");
        }
    }

    // 2. Actualizar
    $sql = "UPDATE tb_camiones SET 
            estatus = ?, 
            placas = ?, 
            kilometraje_total = ?,
            id_conductor_asignado = ? 
            WHERE id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdii", $estatus, $placas, $km, $id_conductor_numerico, $id_camion);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Camión actualizado correctamente.']);
    } else {
        throw new Exception("Error al actualizar: " . $stmt->error);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
?>