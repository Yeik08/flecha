<?php
/*
* db_connect.php
* Se encarga de establecer la conexión con la base de datos.
*/

// 1. Carga las credenciales de config.php
require_once 'config.php';

// 2. Crea la conexión usando MySQLi
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// 3. Verifica si hay un error de conexión
if ($conn->connect_error) {
    // Si falla, detiene el script y muestra el error.
    die("Error de Conexión: " . $conn->connect_error);
}

// 4. Asegura que la conexión use UTF-8 (para acentos y ñ)
$conn->set_charset("utf8mb4");
