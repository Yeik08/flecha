<?php

// Carga credenciales de config.php
require_once 'config.php';

// Intento de crear la conexión usando MySQLi
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// 3. Verifica si hay un error de conexión
if ($conn->connect_error) {
    // SI FALLA: Guarda el error para el log del servidor
    error_log("Error de Conexión a BD: " . $conn->connect_error);
    $conn = false; 
} else {
    $conn->set_charset("utf8mb4");
}


?>