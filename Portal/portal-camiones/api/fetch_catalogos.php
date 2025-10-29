<?php
// api/fetch_catalogos.php
header('Content-Type: application/json'); // Es crucial para que JS lo entienda
session_start();

// 1. Incluir el archivo de conexión
// (Usamos ../../ porque estamos en /portal-camiones/api/ y el conector está en /php/)
require_once '../../../php/db_connection.php';

// 2. Obtener la conexión
$pdo = get_db_connection();

$tipo = $_GET['tipo'] ?? ''; // Obtenemos el parámetro ?tipo=...
$respuesta = [];

try {
    // 3. Ejecutar la consulta SQL según el tipo
    switch ($tipo) {
        
        case 'tecnologias':
            /**
             * CONSULTA REAL: Obtiene todas las tecnologías activas
             * Asegúrate de que tu tabla se llame 'catalogo_tecnologias'
             * y tenga las columnas 'id' y 'nombre'.
             */
            $stmt = $pdo->query("
                SELECT id, nombre 
                FROM public.catalogo_tecnologias 
                WHERE activo = 1 
                ORDER BY nombre
            ");
            $respuesta = $stmt->fetchAll();
            break;

        case 'conductores':
            /**
             * CONSULTA REAL: Obtiene usuarios que son conductores
             * Asumimos que el ROL ID 3 = Conductor
             * Ajusta 'id_rol = 3' a lo que corresponda en tu sistema.
             */
            $stmt = $pdo->query("
                SELECT id_usuario, nombre_completo 
                FROM public.usuarios 
                WHERE id_rol = 3 AND estatus = 'activo' 
                ORDER BY nombre_completo
            ");
            // Formateamos el nombre para que coincida con el JS
            $conductores = $stmt->fetchAll();
            foreach ($conductores as $conductor) {
                $respuesta[] = [
                    'id_usuario' => $conductor['id_usuario'],
                    'nombre_completo' => $conductor['nombre_completo'] . ' (' . $conductor['id_usuario'] . ')'
                ];
            }
            break;

        default:
            $respuesta = ['error' => 'Tipo de catálogo no válido'];
            http_response_code(400); // Error "Bad Request"
            break;
    }

    // 4. Devolver la respuesta como JSON
    echo json_encode($respuesta);

} catch (PDOException $e) {
    // 5. Manejo de errores de SQL
    http_response_code(500); // Error "Internal Server Error"
    echo json_encode(['error' => 'Error de consulta SQL: ' . $e->getMessage()]);
}
?>