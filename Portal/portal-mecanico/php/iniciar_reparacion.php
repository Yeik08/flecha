<?php
/*
* Portal/portal-mecanico/php/iniciar_reparacion.php
* Marca el inicio del trabajo y cambia estatus a 'En Proceso'.
*/
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../../php/db_connect.php';

if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$id_entrada = $_POST['id'] ?? null;

if (!$id_entrada) {
    echo json_encode(['success' => false, 'message' => 'Falta el ID de la entrada']);
    exit;
}

try {
    // Actualizamos estatus y la fecha de inicio (NOW)
    $sql = "UPDATE tb_entradas_taller 
            SET estatus_entrada = 'En Proceso', 
                fecha_inicio_reparacion = NOW() 
            WHERE id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_entrada);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Reparación iniciada. El cronómetro está corriendo.']);
    } else {
        throw new Exception("Error al actualizar: " . $stmt->error);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>