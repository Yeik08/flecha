<?php
// php/fetch_catalogos.php (Con nombres confirmados por image_41049d.png)
header('Content-Type: application/json');
session_start();

require_once 'db_connect.php'; // Incluye tu conexión $conn

// Verifica conexión
if ($conn === false || !$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo establecer la conexión a la base de datos.']);
    exit;
}

$tipo = $_GET['tipo'] ?? '';
$respuesta = [];

try {
    switch ($tipo) {
        case 'tecnologias':
            // Usa los nombres correctos de tabla y columna
            $sql = "SELECT
                        id,
                        nombre_tecnologia AS nombre
                    FROM
                        tb_tecnologias_carroceria
                    ORDER BY
                        nombre_tecnologia";
            $result = $conn->query($sql);
            if ($result) {
                $respuesta = $result->fetch_all(MYSQLI_ASSOC);
                $result->free();
            } else {
                throw new Exception("Error en consulta Tecnologías: " . $conn->error);
            }
            break;

        case 'conductores':
             // Usa los nombres correctos de tabla y columnas
             // Asegúrate que id_rol = 3 y estatus = 'activo' sean correctos para tus conductores
            $sql = "SELECT
                        id_empleado AS id_usuario, -- Renombra id_empleado
                        nombre_completo
                    FROM
                        empleados
                    WHERE
                        id_rol = 3 AND estatus = 'activo'
                    ORDER BY
                        nombre_completo";
            $result = $conn->query($sql);
            if ($result) {
                $conductores = $result->fetch_all(MYSQLI_ASSOC);
                $result->free();
                foreach ($conductores as $conductor) {
                    $respuesta[] = [
                        'id_usuario' => $conductor['id_usuario'], // Usa el alias
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
    if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
         $conn->close();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Error en el servidor: ' . $e->getMessage()]);
}
?>