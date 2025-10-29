<?php
// fetch_catalogos.php (en la raíz del proyecto)
header('Content-Type: application/json'); 
session_start();

// 1. Incluir el archivo de conexión
require_once 'db_connect.php'; // $conn ya está disponible aquí

// Verificar que la conexión se estableció
if ($conn->connect_error) {
    http_response_code(500); 
    echo json_encode(['error' => 'Error de conexión a BD']);
    exit; 
}

$tipo = $_GET['tipo'] ?? ''; 
$respuesta = [];

try {
    // 3. Ejecutar la consulta SQL según el tipo (usando MySQLi)
    switch ($tipo) {
        
        case 'tecnologias':
            // --- CORRECCIONES AQUÍ ---
            $sql = "SELECT 
                        id, 
                        nombre_tecnologia AS nombre -- Renombramos la columna para que coincida con lo que espera el JS
                    FROM 
                        tb_tecnologias_carroceria -- Nombre correcto de la tabla
                    -- WHERE activo = 1 -- Quitado porque no existe en tu tabla
                    ORDER BY 
                        nombre_tecnologia"; // Ordenar por el nombre real de la columna
            // --- FIN CORRECCIONES ---
                        
            $result = $conn->query($sql);

            if ($result) {
                $respuesta = $result->fetch_all(MYSQLI_ASSOC); 
                $result->free(); 
            } else {
                throw new Exception("Error en consulta Tecnologías: " . $conn->error);
            }
            break;

        case 'conductores':
            // Ajusta 'id_rol = 3' si tu rol de conductor es diferente
            $sql = "SELECT id_usuario, nombre_completo 
                    FROM usuarios 
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

    // 4. Cerrar la conexión
    $conn->close();

    // 5. Devolver la respuesta como JSON
    echo json_encode($respuesta);

} catch (Exception $e) {
    // 6. Manejo de errores
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close(); 
    }
    http_response_code(500); 
    echo json_encode(['error' => 'Error en el servidor: ' . $e->getMessage()]);
}
?>