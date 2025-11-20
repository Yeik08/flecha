<?php
// Portal/portal-camiones/fetch_catalogos.php
header('Content-Type: application/json');
session_start();



// Permitimos acceso si es Mesa de Mantenimiento (2) O Receptor (5)
if (!isset($_SESSION['loggedin']) || ($_SESSION['role_id'] != 2 && $_SESSION['role_id'] != 5)) {
    http_response_code(403);// No autorizado
    echo json_encode(['error' => 'Acceso no autorizado.']);
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
            // 1. Corregimos el SQL:
            //    - Seleccionamos `id_interno` (ej: "CON-007") y lo llamamos `id_usuario` para que el JS (scripts.js) lo entienda.
            //    - Usamos CONCAT() para unir nombre, apellido_p y apellido_m en un solo campo llamado `nombre_completo`.
            $sql = "SELECT 
                        id_interno AS id_usuario, 
                        CONCAT(nombre, ' ', apellido_p, ' ', apellido_m) AS nombre_completo
                    FROM empleados
                    WHERE role_id = 7 AND estatus = 'activo'
                    ORDER BY nombre_completo"; 
            
            $result = $conn->query($sql);
            
            if ($result) {
                // 2. Usamos un bucle simple para pasar los datos al JSON.
                // El JavaScript (scripts.js) ya espera los campos "id_usuario" y "nombre_completo".
                $respuesta = []; 
                while ($row = $result->fetch_assoc()) {
                    $respuesta[] = $row;
                }
                $result->free();
            } else {
                 // Si la consulta falla, lanzamos un error
                 throw new Exception("Error en consulta Conductores: ". $conn->error);
            }
            break;
        case 'filtros_aceite':
            // Selecciona marcas únicas de tipo 'Aceite'
            $sql = "SELECT DISTINCT marca FROM tb_cat_filtros WHERE tipo_filtro = 'Aceite' ORDER BY marca";
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $respuesta[] = $row; // Devuelve ej: [{"marca":"SCANIA"}]
                }
                $result->free();
            } else {
                throw new Exception("Error en consulta Filtros Aceite: " . $conn->error);
            }
            break;
        
        case 'filtros_centrifugo':
            // Selecciona marcas únicas de tipo 'Centrifugo'
            $sql = "SELECT DISTINCT marca FROM tb_cat_filtros WHERE tipo_filtro = 'Centrifugo' ORDER BY marca";
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $respuesta[] = $row; // Devuelve ej: [{"marca":"SCANIA"}]
                }
                $result->free();
            } else {
                throw new Exception("Error en consulta Filtros Centrifugo: " . $conn->error);
            }
            break;  
            
        case 'lubricantes':
            // Selecciona productos del catálogo de lubricantes
            $sql = "SELECT nombre_producto AS nombre 
                    FROM tb_cat_lubricantes 
                    ORDER BY nombre_producto";
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $respuesta[] = $row; // Devuelve ej: [{"nombre":"SAE 10W30 MULTIGRADO"}]
                }
                $result->free();
            } else {
                throw new Exception("Error en consulta Lubricantes: " . $conn->error);
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