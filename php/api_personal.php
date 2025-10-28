<?php

// ini_set('display_errors', 1); // Mantén esto comentado por ahora
// error_reporting(E_ALL);

session_start();
header('Content-Type: application/json'); // Siempre responderemos en JSON

// Inicializa $conn a null para evitar errores si la conexión falla
$conn = null;
require_once 'db_connect.php'; // Incluimos la conexión (puede fallar y $conn seguirá null)

// --- Seguridad: Solo el Administrador (role_id 1) puede usar esto ---
// Verifica la sesión DESPUÉS de intentar la conexión
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    // Si la conexión falló, $conn será null, no podemos cerrar nada
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

// Verifica si la conexión se estableció correctamente en db_connect.php
if ($conn === null || $conn->connect_error) {
     echo json_encode(['success' => false, 'message' => 'Error crítico: No se pudo conectar a la base de datos.']);
     exit; // Salimos si no hay conexión
}


$metodo = $_SERVER['REQUEST_METHOD']; // Vemos si es GET (leer), POST (crear) o DELETE (borrar)

try {
    switch ($metodo) {
        // --- TAREA 2 (Leer) ---
       case 'GET':
            // Verifica si se pidió un ID específico en la URL (ej: api_personal.php?id=2)
            $id_pedido = $_GET['id'] ?? null;

            if ($id_pedido) {
                 // --- Devolver UN solo empleado ---
                 // Seleccionamos todas las columnas de empleados (e.*) Y el nombre del rol
                 $sql = "SELECT e.*, r.nombre_rol
                         FROM empleados e
                         JOIN roles r ON e.role_id = r.id
                         WHERE e.id_empleado = ?"; // Buscamos por el ID numérico
                 $stmt_get = $conn->prepare($sql);
                 if ($stmt_get === false) {
                    throw new Exception('Error al preparar GET por ID: ' . $conn->error);
                 }
                 $stmt_get->bind_param("i", $id_pedido);
                 $stmt_get->execute();
                 $result = $stmt_get->get_result();
                 $empleado = $result->fetch_assoc(); // Obtiene solo una fila (el empleado)

                 if ($empleado) {
                    // Si se encontró, devuelve sus datos
                    echo json_encode(['success' => true, 'data' => $empleado]);
                 } else {
                    // Si no se encontró (ID inválido), devuelve error
                    echo json_encode(['success' => false, 'message' => 'Empleado no encontrado con ese ID.']);
                 }
                 $stmt_get->close();

            } else {
                 // --- Devolver TODOS los empleados (como lo tenías antes) ---
                 $sql = "SELECT e.id_empleado, e.id_interno, e.nombre, e.apellido_p, e.apellido_m, e.fecha_ingreso, r.nombre_rol, e.estatus
                         FROM empleados e
                         JOIN roles r ON e.role_id = r.id
                         -- WHERE e.estatus = 'activo' -- Puedes descomentar si solo quieres activos
                         ORDER BY e.id_empleado DESC";
                 $result = $conn->query($sql);
                 if ($result === false) {
                    throw new Exception('Error al ejecutar consulta GET: ' . $conn->error);
                 }

                 $empleados = [];
                 while ($row = $result->fetch_assoc()) {
                     $empleados[] = $row;
                 }
                 echo json_encode(['success' => true, 'data' => $empleados]);
            }
            break; // Fin del case 'GET' modificado

        // --- TAREA 1 (Escribir/Crear) ---
        case 'POST':
            // 1. Obtenemos los datos del formulario
            $nombre = $_POST['nombre'] ?? '';
            $apellido_p = $_POST['apellido_paterno'] ?? '';
            $apellido_m = $_POST['apellido_materno'] ?? '';
            $role_id = $_POST['rol'] ?? null;
            $fecha_ingreso = $_POST['fecha_ingreso'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            // Validación de campos requeridos
            if (empty($nombre) || empty($apellido_p) || empty($role_id) || empty($email) || empty($password) || empty($fecha_ingreso)) {
                 throw new Exception('Error: Todos los campos con * son requeridos.');
            }

            // Hash de la contraseña
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
             if ($password_hash === false) {
                 throw new Exception('Error: Falló la creación del hash de la contraseña.');
            }

            // --- Verificación de Duplicado por Nombre Completo ---
            $sql_check = "SELECT id_empleado FROM empleados WHERE nombre = ? AND apellido_p = ? AND apellido_m = ?";
            $stmt_check = $conn->prepare($sql_check);
             if ($stmt_check === false) {
                 throw new Exception('Error al preparar la verificación de duplicados: ' . $conn->error);
            }
            $stmt_check->bind_param("sss", $nombre, $apellido_p, $apellido_m);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                throw new Exception('Ya existe un empleado registrado con ese nombre completo.');
            }
            $stmt_check->close();
            // --- FIN DE LA VERIFICACIÓN ---


            // --- LÓGICA DE DOBLE ID (Transacción) ---
            $conn->begin_transaction();

            // 2. Inserta los datos básicos
            $sql_insert = "INSERT INTO empleados (nombre, apellido_p, apellido_m, fecha_ingreso, role_id, email, password_hash)
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            if ($stmt_insert === false) {
                 throw new Exception('Error al preparar INSERT: ' . $conn->error);
            }
            $stmt_insert->bind_param("ssssiss", $nombre, $apellido_p, $apellido_m, $fecha_ingreso, $role_id, $email, $password_hash);

            if (!$stmt_insert->execute()) {
                 // Si falla el INSERT (podría ser por email duplicado), lanza excepción
                 if ($conn->errno == 1062) { // Error de duplicado
                      throw new Exception('Error al registrar: El correo electrónico ya está en uso.');
                 } else {
                     throw new Exception('Error al ejecutar INSERT: ' . $stmt_insert->error);
                 }
            }

            // 3. Obtiene el ID numérico
            $nuevo_id_empleado = $conn->insert_id;
            if (empty($nuevo_id_empleado)) {
                 throw new Exception('Error: No se pudo obtener el ID del nuevo empleado.'); // No debería necesitar rollback aquí
            }

            // 4. Define prefijos
            $prefijos = [ 1 => 'ADM', 2 => 'MES', 3 => 'MEC', 4 => 'JFT', 5 => 'RCT', 6 => 'ALM', 7 => 'CON' ];
            $prefijo = $prefijos[$role_id] ?? 'USR';

            // 5. Formatea ID interno
            $id_formateado = str_pad($nuevo_id_empleado, 3, '0', STR_PAD_LEFT);
            $id_interno_nuevo = $prefijo . '-' . $id_formateado;

            // 6. Actualiza con ID Interno y Username
            $sql_update = "UPDATE empleados SET id_interno = ?, username = ? WHERE id_empleado = ?";
            $stmt_update = $conn->prepare($sql_update);
            if ($stmt_update === false) {
                 throw new Exception('Error al preparar UPDATE: ' . $conn->error); // No necesita rollback si solo falló el prepare
            }
            $stmt_update->bind_param("ssi", $id_interno_nuevo, $id_interno_nuevo, $nuevo_id_empleado);

            if (!$stmt_update->execute()) {
                 // Si falla el UPDATE (podría ser por username/id_interno duplicado)
                 if ($conn->errno == 1062) {
                     throw new Exception('Error al registrar (Paso 2): El ID interno o username generado ya existe. Intente de nuevo.');
                 } else {
                     throw new Exception('Error al ejecutar UPDATE: ' . $stmt_update->error);
                 }
            }

            // 7. Si todo salió bien, confirma la transacción
            $conn->commit();

            // Respuesta exitosa
            echo json_encode(['success' => true, 'message' => "Empleado registrado ($id_interno_nuevo). Su usuario es $id_interno_nuevo"]);

        break; // Fin del case 'POST'

        // --- TAREA 3 (Eliminar/Baja) ---
        case 'DELETE':
    parse_str(file_get_contents("php://input"), $datos);
    $id_empleado = $datos['id'] ?? null;

    if (empty($id_empleado)) {
        throw new Exception('No se proporcionó un ID de empleado para desactivar.');
    }

    // Cambiamos DELETE por UPDATE
    $sql_deactivate = "UPDATE empleados SET estatus = 'inactivo' WHERE id_empleado = ?";
    $stmt_deactivate = $conn->prepare($sql_deactivate);
    if ($stmt_deactivate === false) {
         throw new Exception('Error al preparar desactivación: ' . $conn->error);
    }
    $stmt_deactivate->bind_param("i", $id_empleado);

    if ($stmt_deactivate->execute()) {
        if ($stmt_deactivate->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Empleado desactivado con éxito']);
        } else {
            throw new Exception('No se encontró ningún empleado activo con ese ID.');
        }
    } else {
        throw new Exception('Error al desactivar: ' . $stmt_deactivate->error);
    }
break;
case 'PUT':
            // Leemos los datos enviados en el body
            parse_str(file_get_contents("php://input"), $datos);
            // Lee el ID (puede venir como 'id' de reactivar o 'id_empleado_editar' de editar)
            $id_empleado = $datos['id'] ?? $datos['id_empleado_editar'] ?? null;
            $accion = $datos['accion'] ?? null; // Leemos la acción enviada ('reactivar' o nada)

            // --- Lógica de Reactivación ---
            if ($accion === 'reactivar') {
                 if (empty($id_empleado)) {
                     throw new Exception('No se proporcionó un ID de empleado para reactivar.');
                 }
                // Preparamos la consulta para cambiar el estatus a 'activo'
                $sql_reactivate = "UPDATE empleados SET estatus = 'activo' WHERE id_empleado = ?";
                $stmt_reactivate = $conn->prepare($sql_reactivate);
                if ($stmt_reactivate === false) { throw new Exception('Error al preparar reactivación: ' . $conn->error); }
                $stmt_reactivate->bind_param("i", $id_empleado);

                if ($stmt_reactivate->execute()) {
                    if ($stmt_reactivate->affected_rows > 0) {
                        echo json_encode(['success' => true, 'message' => 'Empleado reactivado con éxito']);
                    } else { throw new Exception('No se encontró ningún empleado inactivo con ese ID.'); }
                } else { throw new Exception('Error al reactivar: ' . $stmt_reactivate->error); }
                $stmt_reactivate->close();

            // --- Lógica de Edición ---
            } else { // Si no es reactivar, asumimos que es editar
                 // Obtenemos los datos del formulario (asegúrate que $id_empleado se leyó correctamente arriba)
                 $nombre = $datos['nombre'] ?? '';
                 $apellido_p = $datos['apellido_paterno'] ?? '';
                 $apellido_m = $datos['apellido_materno'] ?? '';
                 $role_id = $datos['rol'] ?? null;
                 $fecha_ingreso = $datos['fecha_ingreso'] ?? '';
                 $email = $datos['email'] ?? '';
                 $password = $datos['password'] ?? ''; // Opcional

                 // Validación para EDICIÓN (aquí estaba el error al reactivar)
                 if (empty($id_empleado) || empty($nombre) || empty($apellido_p) || empty($role_id) || empty($email) || empty($fecha_ingreso)) {
                     throw new Exception('Error: Faltan datos requeridos para la actualización.'); // Este es el error que veías
                 }

                 // --- Verificación Duplicado Nombre (Excluyendo actual) ---
                 $sql_check_nombre = "SELECT id_empleado FROM empleados WHERE nombre = ? AND apellido_p = ? AND apellido_m = ? AND id_empleado != ?";
                 $stmt_check_nombre = $conn->prepare($sql_check_nombre);
                 if ($stmt_check_nombre === false) { throw new Exception('Error al preparar check nombre duplicado: ' . $conn->error); }
                 $stmt_check_nombre->bind_param("sssi", $nombre, $apellido_p, $apellido_m, $id_empleado);
                 $stmt_check_nombre->execute();
                 $stmt_check_nombre->store_result();
                 if ($stmt_check_nombre->num_rows > 0) { throw new Exception('Ya existe OTRO empleado con ese nombre completo.'); }
                 $stmt_check_nombre->close();

                 // --- Verificación Duplicado Email (Excluyendo actual) ---
                 $sql_check_email = "SELECT id_empleado FROM empleados WHERE email = ? AND id_empleado != ?";
                 $stmt_check_email = $conn->prepare($sql_check_email);
                 if ($stmt_check_email === false) { throw new Exception('Error al preparar check email duplicado: ' . $conn->error); }
                 $stmt_check_email->bind_param("si", $email, $id_empleado);
                 $stmt_check_email->execute();
                 $stmt_check_email->store_result();
                 if ($stmt_check_email->num_rows > 0) { throw new Exception('Ya existe OTRO empleado con ese correo electrónico.'); }
                 $stmt_check_email->close();

                 // Prepara la consulta UPDATE
                 if (!empty($password)) {
                     $password_hash = password_hash($password, PASSWORD_DEFAULT);
                     if ($password_hash === false) { throw new Exception('Error al hashear nueva contraseña.'); }
                     $sql_update = "UPDATE empleados SET nombre=?, apellido_p=?, apellido_m=?, fecha_ingreso=?, role_id=?, email=?, password_hash=? WHERE id_empleado = ?";
                     $types = "ssssissi";
                     $params = [$nombre, $apellido_p, $apellido_m, $fecha_ingreso, $role_id, $email, $password_hash, $id_empleado];
                 } else {
                     $sql_update = "UPDATE empleados SET nombre=?, apellido_p=?, apellido_m=?, fecha_ingreso=?, role_id=?, email=? WHERE id_empleado = ?";
                     $types = "ssssisi";
                     $params = [$nombre, $apellido_p, $apellido_m, $fecha_ingreso, $role_id, $email, $id_empleado];
                 }

                 $stmt_update = $conn->prepare($sql_update);
                 if ($stmt_update === false) { throw new Exception('Error al preparar UPDATE: ' . $conn->error); }
                 $stmt_update->bind_param($types, ...$params);

                 if ($stmt_update->execute()) {
                     if ($stmt_update->affected_rows > 0) {
                         echo json_encode(['success' => true, 'message' => 'Empleado actualizado con éxito']);
                     } else {
                         echo json_encode(['success' => true, 'message' => 'Empleado actualizado (sin cambios detectados).']);
                     }
                 } else {
                      if ($conn->errno == 1062) {
                          throw new Exception('Error al actualizar: El correo electrónico ya está en uso por otro empleado.');
                      } else {
                         throw new Exception('Error al ejecutar UPDATE: ' . $stmt_update->error);
                      }
                 }
                 $stmt_update->close();
            } // Fin del else (lógica de edición)

        break;
    } // Fin del switch
} catch (Exception $e) {
    // Bloque CATCH más seguro: Verifica si $conn es un objeto válido ANTES de usarlo
    if (is_object($conn) && $conn->connect_errno == 0 && method_exists($conn, 'ping') && $conn->ping()) {
         // Solo intenta rollback si hay conexión Y si existe una transacción activa
        try {
             if (method_exists($conn, 'in_transaction') && $conn->in_transaction) {
                $conn->rollback();
            }
        } catch (mysqli_sql_exception $rollback_ex) {
             // Ignora errores durante el rollback si la conexión ya está mal
        }
    }
    // Siempre envía la respuesta de error JSON
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);

} finally {
    // Bloque FINALLY más seguro: Cierra la conexión solo si es un objeto válido y está abierta
    if (is_object($conn) && $conn->connect_errno == 0 && method_exists($conn, 'ping') && $conn->ping()) {
        $conn->close();
    }
}
?>