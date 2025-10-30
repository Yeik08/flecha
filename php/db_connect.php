<?php
/*
* db_connect.php
* Se encarga de establecer la conexión con la base de datos.
* VERSIÓN CORREGIDA: No usa die() para ser compatible con API/fetch.
*/

// 1. Carga las credenciales de config.php
require_once 'config.php';

// 2. Intenta crear la conexión usando MySQLi
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// 3. Verifica si hay un error de conexión
if ($conn->connect_error) {
    // SI FALLA: Guarda el error para el log del servidor
    error_log("Error de Conexión a BD: " . $conn->connect_error);
    // IMPORTANTE: Asigna false a $conn para que otros scripts puedan verificarlo
    $conn = false; 
} else {
    // 4. Asegura que la conexión use UTF-8 (solo si fue exitosa)
    $conn->set_charset("utf8mb4");
}

// El script que incluya este archivo recibirá la variable $conn (o false si falló)
?>