<?php
/*
* db_connect.php
* Se encarga de establecer la conexión con la base de datos.
*/

// 1. Carga las credenciales de config.php
require_once 'config.php';

// 2. Intenta crear la conexión usando MySQLi
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// 3. Verifica si hay un error de conexión
if ($conn->connect_error) {
    // SI FALLA: En lugar de die(), simplemente dejamos $conn como null o false
    // El script que incluye este archivo DEBE verificar si $conn es válido.
    // Opcionalmente, puedes loggear el error aquí para el servidor.
    error_log("Error de Conexión a BD: " . $conn->connect_error);
    $conn = false; // Indicamos que la conexión falló
} else {
    // 4. Asegura que la conexión use UTF-8 (solo si la conexión fue exitosa)
    $conn->set_charset("utf8mb4");
}

// Nota: No hay 'return' aquí, la variable $conn está disponible globalmente
// para el script que haga require_once 'db_connect.php';
?>


