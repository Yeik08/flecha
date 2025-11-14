<?php
/*
* Portal/portal-camiones/api_camiones.php
* Obtiene la lista de todos los camiones para la tabla principal.
*/

session_start();
header('Content-Type: application/json');

// --- 1. Seguridad y Conexión ---
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

require_once '../../php/db_connect.php'; 

if ($conn === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

try {
    // --- 2. Consultar la base de datos ---
    // Consultamos las columnas que la tabla HTML necesita
    $sql = "SELECT 
                id, 
                numero_economico, 
                placas, 
                estatus, 
                fecha_ult_mantenimiento, 
                mantenimiento_requerido 
            FROM tb_camiones 
            ORDER BY numero_economico ASC";
    
    $result = $conn->query($sql);
    $camiones = [];

    if ($result) {
        // Recolectamos todos los camiones en un array
        while ($row = $result->fetch_assoc()) {
            $camiones[] = $row;
        }
        // Enviamos la respuesta como JSON
        echo json_encode(['success' => true, 'data' => $camiones]);
    } else {
        throw new Exception("Error al consultar los camiones: " . $conn->error);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>