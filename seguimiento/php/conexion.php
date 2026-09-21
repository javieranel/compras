<?php

$host = "localhost";
//$usuario = "root";
//$usuario = "admin";
$usuario = "comprasseguimientoadmin";
//$password = "";
//$base_datos = "compras";
$base_datos = "comprasseguimientodb";
//$password = "TuNuevaContraseña";
$password = "dpQwi8#.";

$con = new mysqli($host, $usuario, $password, $base_datos);

if ($con->connect_error) {
    die("Error de conexión a la base de datos: " . $con->connect_error);
}

// Configurar caracteres
$con->set_charset("utf8mb4");

?>