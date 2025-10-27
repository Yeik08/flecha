<?php
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
            // ... (aquí va la captura de datos: $nombre, $apellido_p, etc.) ...
            $nombre = $_POST['nombre'] ?? '';
            $apellido_p = $_POST['apellido_paterno'] ?? '';
            $apellido_m = $_POST['apellido_materno'] ?? '';
            $role_id = $_POST['rol'] ?? null;
            $fecha_ingreso = $_POST['fecha_ingreso'] ?? '';
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            // Validación simple
            if (empty($nombre) || empty($apellido_p) || empty($role_id) || empty($username) || empty($email) || empty($password)) {
                throw new Exception('Todos los campos con * son requeridos.');
            }
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // --- INICIO DE LA NUEVA LÓGICA ---
            
            // 1. Inicia una transacción (para asegurar que ambas consultas funcionen)
            $conn->begin_transaction();

            // 2. Inserta los datos básicos del empleado (SIN el id_interno)
            $sql_insert = "INSERT INTO empleados (nombre, apellido_p, apellido_m, fecha_ingreso, role_id, username, email, password_hash)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("ssssisss", $nombre, $apellido_p, $apellido_m, $fecha_ingreso, $role_id, $username, $email, $password_hash);
            
            if (!$stmt_insert->execute()) {
                throw new Exception('Error al registrar (Paso 1): ' . $stmt_insert->error);
            }

            // 3. Obtiene el ID numérico (id_empleado) que se acaba de crear
            $nuevo_id_empleado = $conn->insert_id;

            // 4. Define los prefijos para cada rol (¡Igual que en tu script.js!)
            $prefijos = [
                1 => 'ADM', // Administrador
                2 => 'MES', // Mesa de Mantenimiento
                3 => 'MEC', // Técnico Mecánico
                4 => 'JFT', // Jefe de Taller
                5 => 'RCT', // Receptor de Taller
                6 => 'ALM', // Almacenista
                7 => 'CON'  // Conductor
            ];
            $prefijo = $prefijos[$role_id] ?? 'USR'; // 'USR' como default

            // 5. Formatea el ID interno (ej: MEC-003)
            $id_formateado = str_pad($nuevo_id_empleado, 3, '0', STR_PAD_LEFT);
            $id_interno_nuevo = $prefijo . '-' . $id_formateado;

            // 6. Actualiza el empleado con su nuevo ID Interno
            $sql_update = "UPDATE empleados SET id_interno = ? WHERE id_empleado = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("si", $id_interno_nuevo, $nuevo_id_empleado);

            if (!$stmt_update->execute()) {
                throw new Exception('Error al registrar (Paso 2): ' . $stmt_update->error);
            }

            // 7. Si todo salió bien, confirma la transacción
            $conn->commit();
            echo json_encode(['success' => true, 'message' => "Empleado registrado con éxito ($id_interno_nuevo)"]);
            
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>