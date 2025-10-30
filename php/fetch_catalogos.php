<?php
// php/fetch_catalogos.php
header('Content-Type: application/json');
session_start();

// 1. Incluir el archivo de conexión
require_once 'db_connect.php'; // $conn debería estar disponible ahora (o ser 'false')

// 2. VERIFICACIÓN DE CONEXIÓN
// Esta es la parte más importante
if ($conn === false || !$conn) {
    http_response_code(500); // Internal Server Error
    // Devuelve un error JSON, no HTML
    echo json_encode(['error' => 'No se pudo establecer la conexión a la base de datos. Revisa config.php.']);
    exit; // Detiene el script limpiamente
}

$tipo = $_GET['tipo'] ?? '';
$respuesta = [];

try {
    // 3. Ejecutar la consulta SQL según el tipo
    switch ($tipo) {
        
        case 'tecnologias':
            $sql = "SELECT id, nombre_tecnologia AS nombre
                    FROM tb_tecnologias_carroceria
                    ORDER BY nombre_tecnologia";
            $result = $conn->query($sql);
            if ($result) {
                $respuesta = $result->fetch_all(MYSQLI_ASSOC);
                $result->free();
            } else {
                throw new Exception("Error en consulta Tecnologías: " . $conn->error);
            }
            break;

        case 'conductores':
            // Asegúrate que id_rol = 3 y estatus = 'activo' sean correctos
            $sql = "SELECT id_empleado AS id_usuario, nombre_completo
                    FROM empleados
                    WHERE id_rol = 3 AND estatus = 'activo'
                    ORDER BY nombre_completo";
            $result = $conn->query($sql);
            if ($result) {
                $conductores = $result->fetch_all(MYSQLI_ASSOC);
                $result->free();
                foreach ($conductores as $conductor) {
                    $respuesta[] = [
                        'id_usuario' => $conductor['id_usuario'],
                        'nombre_completo' => $conductor['nombre_completo'] . ' (' . $conductor['id_usuario'] . ')'
                    ];
                }
            } else {
                 throw new Exception("Error en consulta Conductores: " . $conn->error);
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