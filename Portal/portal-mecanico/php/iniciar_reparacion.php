<?php
/*
* Portal/portal-mecanico/php/iniciar_reparacion.php
* - Marca el inicio del trabajo.
* - FIRMA DIGITAL: Registra al usuario conectado como responsable.
*/
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../../php/db_connect.php';

// 1. Seguridad
if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$id_entrada = $_POST['id'] ?? null;
$id_usuario_actual = $_SESSION['user_id']; // <--- AQUÍ CAPTURAMOS QUIÉN ES

if (!$id_entrada) {
    echo json_encode(['success' => false, 'message' => 'Falta el ID de la entrada']);
    exit;
}

try {
    // 2. Verificar que no esté ya asignado (Opcional, para evitar robos de ticket)
    // Si quieres permitir que cualquiera inicie, salta este paso.
    // Aquí asumimos que el primero que da click se lo queda.

    // 3. Actualizar BD: Estatus + Fecha + RESPONSABLE
    $sql = "UPDATE tb_entradas_taller 
            SET estatus_entrada = 'En Proceso', 
                fecha_inicio_reparacion = NOW(),
                id_mecanico_responsable = ? 
            WHERE id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_usuario_actual, $id_entrada);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Unidad asignada a ti. Reparación iniciada.']);
    } else {
        throw new Exception("Error al actualizar: " . $stmt->error);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>