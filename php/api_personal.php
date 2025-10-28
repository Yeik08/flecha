<?php


ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json'); // Siempre responderemos en JSON
require_once 'db_connect.php'; // Incluimos la conexión

// --- Seguridad: Solo el Administrador (role_id 1) puede usar esto ---
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

$metodo = $_SERVER['REQUEST_METHOD']; // Vemos si es GET (leer) o POST (crear)

try {
    switch ($metodo) {
        // --- TAREA 2 (Leer) ---
        case 'GET':

            // Consultamos todos los empleados y unimos con su rol
            $sql = "SELECT e.id_empleado, e.id_interno, e.nombre, e.apellido_p, e.apellido_m, e.fecha_ingreso, r.nombre_rol 
                    FROM empleados e
                    JOIN roles r ON e.role_id = r.id
                    ORDER BY e.id_empleado DESC";

            $result = $conn->query($sql);
            
            $empleados = [];
            while ($row = $result->fetch_assoc()) {
                $empleados[] = $row;
            }
            
            echo json_encode(['success' => true, 'data' => $empleados]);
            break;

        // --- TAREA 1 (Escribir/Crear) ---
        case 'POST':
            // 1. Obtenemos los datos (¡SIN 'username' ni 'id_interno'!)
            $nombre = $_POST['nombre'] ?? '';
            $apellido_p = $_POST['apellido_paterno'] ?? '';
            $apellido_m = $_POST['apellido_materno'] ?? '';
            $role_id = $_POST['rol'] ?? null;
            $fecha_ingreso = $_POST['fecha_ingreso'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            // Validación (ahora sin username)
            if (empty($nombre) || empty($apellido_p) || empty($role_id) || empty($email) || empty($password)) {
                throw new Exception('Todos los campos con * son requeridos.');
            }
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // --- LÓGICA DE DOBLE ID ---
            
            $conn->begin_transaction();

            // 2. Inserta los datos básicos (sin id_interno ni username)
            $sql_insert = "INSERT INTO empleados (nombre, apellido_p, apellido_m, fecha_ingreso, role_id, email, password_hash)
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("ssssiss", $nombre, $apellido_p, $apellido_m, $fecha_ingreso, $role_id, $email, $password_hash);
            
            if (!$stmt_insert->execute()) {
                throw new Exception('Error al registrar (Paso 1): ' . $stmt_insert->error);
            }

            // 3. Obtiene el ID numérico (id_empleado)
            $nuevo_id_empleado = $conn->insert_id;

            // 4. Define los prefijos (¡Igual que en JavaScript!)
            $prefijos = [
                1 => 'ADM', 2 => 'MES', 3 => 'MEC', 4 => 'JFT', 
                5 => 'RCT', 6 => 'ALM', 7 => 'CON'
            ];
            $prefijo = $prefijos[$role_id] ?? 'USR';

            // 5. Formatea el ID interno (ej: MEC-003)
            $id_formateado = str_pad($nuevo_id_empleado, 3, '0', STR_PAD_LEFT);
            $id_interno_nuevo = $prefijo . '-' . $id_formateado; // ej: "MEC-001"

            // 6. Actualiza el empleado con su ID Interno Y su Username
            // ¡Aquí guardamos el ID como username!
            $sql_update = "UPDATE empleados SET id_interno = ?, username = ? WHERE id_empleado = ?";
            $stmt_update = $conn->prepare($sql_update);
            // Pasamos $id_interno_nuevo a AMBOS campos (id_interno y username)
            $stmt_update->bind_param("ssi", $id_interno_nuevo, $id_interno_nuevo, $nuevo_id_empleado);

            if (!$stmt_update->execute()) {
                // Si falla (ej. username duplicado), cancelamos todo
                $conn->rollback();
                throw new Exception('Error al registrar (Paso 2): ' . $stmt_update->error);
            }

            // 7. Si todo salió bien, confirma
            $conn->commit();
            echo json_encode(['success' => true, 'message' => "Empleado registrado ($id_interno_nuevo). Su usuario es $id_interno_nuevo"]);
            
        break;

        case 'DELETE':
        // Obtenemos el ID numérico del empleado a borrar
        // PHP lee el body de la petición DELETE
        parse_str(file_get_contents("php://input"), $datos);
        $id_empleado = $datos['id'] ?? null;

        if (empty($id_empleado)) {
            throw new Exception('No se proporcionó un ID de empleado.');
        }

        // Preparamos la consulta para borrar
        $sql_delete = "DELETE FROM empleados WHERE id_empleado = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $id_empleado);

        if ($stmt_delete->execute()) {
            if ($stmt_delete->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Empleado eliminado con éxito']);
            } else {
                throw new Exception('No se encontró ningún empleado con ese ID.');
            }
        } else {
            throw new Exception('Error al eliminar: ' . $stmt_delete->error);
        }
        
        break;
    } // Fin del switch
} catch (Exception $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>