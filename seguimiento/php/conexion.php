<?php

$host = "localhost";
$usuario = "root";
$password = "";
$base_datos = "compras";

$con = new mysqli($host, $usuario, $password, $base_datos);

if ($con->connect_error) {
    die("Error de conexión a la base de datos: " . $con->connect_error);
}

// Configurar caracteres
$con->set_charset("utf8mb4");

?>