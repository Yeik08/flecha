<?php
// 1. Inicializar la sesión existente
session_start();

// 2. Destruir todas las variables de sesión
$_SESSION = array();

// 3. Borrar la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Finalmente, destruir la sesión
session_destroy();

// 5. Redirigir al login y detener el script
header("Location: ../index.html");
exit;
?>