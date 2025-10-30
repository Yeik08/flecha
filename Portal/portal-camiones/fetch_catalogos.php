<?php
// Portal/portal-camiones/fetch_catalogos.php
header('Content-Type: application/json');
session_start();

// --- Bloque de seguridad ---
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 2) {
    http_response_code(403); // No autorizado
    echo json_encode(['error' => 'Acceso no autorizado. Inicie sesión de nuevo.']);
    exit; 
}

// 1. Incluir el archivo de conexión
// Ruta corregida: Sube 2 niveles (desde portal-camiones/ y Portal/) para llegar a la raíz y entrar a php/
require_once '../../php/db_connect.php'; 

// Si el script llega aquí, la conexión fue exitosa (gracias a die() en db_connect).
$tipo = $_GET['tipo'] ?? '';
$respuesta = [];

try {
    // 3. Ejecutar la consulta SQL (usando el bucle 'while' que sí funciona)
    switch ($tipo) {
        
        case 'tecnologias':
            $sql = "SELECT id, nombre_tecnologia AS nombre
                    FROM tb_tecnologias_carroceria
                    ORDER BY nombre_tecnologia";
            $result = $conn->query($sql);
            if ($result) {
                $respuesta = []; 
                while ($row = $result->fetch_assoc()) {
                    $respuesta[] = $row;
                }
                $result->free();
            } else {
                throw new Exception("Error en consulta Tecnologías: " . $conn->error);
            }
            break;

        case 'conductores':
            $sql = "SELECT id_empleado AS id_usuario, nombre_completo
                    FROM empleados
                    WHERE id_rol = 3 AND estatus = 'activo'
                    ORDER BY nombre_completo";
            $result = $conn->query($sql);
            if ($result) {
                $conductores_db = []; 
                while ($row = $result->fetch_assoc()) {
                    $conductores_db[] = $row;
                }
                $result->free();
                
                foreach ($conductores_db as $conductor) {
                    $respuesta[] = [
                        'id_usuario' => $conductor['id_usuario'],
                        'nombre_completo' => $conductor['nombre_completo'] . ' (' . $conductor['id_usuario'] . ')'
                    ];
                }
            } else {
                 throw new Exception("Error en consulta Conductores: ". $conn->error);
            }
            break;

        default:
            $respuesta = ['error' => 'Tipo de catálogo no válido'];
            http_response_code(400);
            break;
    }

    $conn->close();
    echo json_encode($respuesta);

} catch (Exception $e) {
    if (isset($conn) && $conn instanceof mysqli) {
         $conn->close();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Error en el servidor: ' . $e->getMessage()]);
}
?>