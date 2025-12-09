
<?php

require_once 'db_connect.php';

$nombre = "Yeykocf";
$apellido_p = "Cuesta";
$apellido_m = "Meneses";
$fecha_ingreso = "2024-01-01"; // O usa date("Y-m-d");
$role_id = 1; // 1 = Administrador (según nuestro script SQL)
$username = "administrator";
$email = "schekolat@gmail.com";
$password_simple = "WTK_cuesta01"; // La contraseña que USARÁS para entrar
// -----------------------------

// 2. Hashea la contraseña (¡La parte importante!)
$password_hash = password_hash($password_simple, PASSWORD_DEFAULT);

// 3. Prepara la consulta SQL para insertar en 'empleados'
$sql = "INSERT INTO empleados 
            (nombre, apellido_p, apellido_m, fecha_ingreso, role_id, username, email, password_hash) 
        VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Error al preparar la consulta: " . $conn->error);
}

// 4. Vincula los parámetros
// 'ssssisss' = string, string, string, string, integer, string, string, string
$stmt->bind_param("ssssisss", 
    $nombre, 
    $apellido_p, 
    $apellido_m, 
    $fecha_ingreso, 
    $role_id, 
    $username, 
    $email, 
    $password_hash
);

// 5. Ejecuta la consulta
if ($stmt->execute()) {
    echo "<h1>¡ÉXITO!</h1>";
    echo "<p>Usuario Administrador creado:</p>";
    echo "<ul>";
    echo "<li><strong>Usuario:</strong> " . $username . "</li>";
    echo "<li><strong>Contraseña:</strong> " . $password_simple . "</li>";
    echo "</ul>";
    echo "<p>Ya puedes ir al <a href='../index.html'>index.html</a> e iniciar sesión.</p>";
} else {
    echo "<h1>ERROR</h1>";
    echo "<p>No se pudo crear el usuario. ¿Posible error?</p>";
    echo "<p><strong>Error MySQL:</strong> " . $stmt->error . "</p>";
    echo "<p><strong>*Nota:</strong> Si el error dice 'Duplicate entry', significa que el 'username' o 'email' ya existe. Prueba con otro.</p>";
}

$stmt->close();
$conn->close();

?>