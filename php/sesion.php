<?php
require_once("funciones.php")
$user = filter_input(INPUT_POST,'username', FILTER_VALIDATE_EMAIL);
$password = filter_input(INPUT_POST,'password', FILTER_SANITIZE_SPECIAL_CHARS);
login_user($user,$password);
?>

