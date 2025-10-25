<?php
/*
* login.php
* Procesa el formulario de inicio de sesión.
*/

// 1. Inicia la sesión de PHP en la parte SUPERIOR
// (Necesario para guardar que el usuario ha iniciado sesión)
session_start();

// 2. Incluye el archivo de conexión
require_once 'db_connect.php';

// 3. Verifica que los datos vengan del formulario (método POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 4. Obtiene los datos del formulario (de forma segura)
    $username = $_POST['username'] ?? ''; // El 'name' del input
    $password = $_POST['password'] ?? ''; // El 'name' del input

    if (empty($username) || empty($password)) {
        header("Location: ../index.html?error=campos_vacios");
        exit;
    }

    // 5. Prepara la consulta SQL (Previene Inyección SQL)
    // Buscamos al empleado por su 'username' O 'email'
    $sql = "SELECT id_empleado, username, password_hash, role_id, nombre FROM empleados WHERE username = ? OR email = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $username);
    
    // 6. Ejecuta la consulta
    $stmt->execute();
    $result = $stmt->get_result();

    // 7. Verifica si el usuario existe (1 fila encontrada)
    if ($result->num_rows == 1) {
        
        $user = $result->fetch_assoc();

        // 8. ¡Verifica la contraseña!
        // Compara la contraseña del formulario ($password) con el hash de la BD
        if (password_verify($password, $user['password_hash'])) {
            
            // ¡ÉXITO! Contraseña correcta.
            
            // 9. Guarda los datos del usuario en la Sesión
            session_regenerate_id(true); // Seguridad extra
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user['id_empleado'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nombre_completo'] = $user['nombre'];
            $_SESSION['role_id'] = $user['role_id'];

            // 10. Redirige al Portal
            header("Location: ../Portal/index.html");
            exit;

        } else {
            // Error: Contraseña incorrecta
            header("Location: ../index.html?error=pass_incorrecta");
            exit;
        }

    } else {
        // Error: Usuario no encontrado
        header("Location: ../index.html?error=user_no_encontrado");
        exit;
    }

    $stmt->close();
    $conn->close();

} else {
    // Si alguien entra a este archivo sin enviar un formulario, lo echa
    header("Location: ../index.html");
    exit;
}
?>