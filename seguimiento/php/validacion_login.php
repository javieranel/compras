<?php
session_start();
include 'conexion.php';

$usuario = trim($_POST['usuario'] ?? '');
$clave = $_POST['clave'] ?? '';

if (empty($usuario) || empty($clave)) {
    echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
    exit;
}

$stmt = $con->prepare("SELECT id, nombre, clave FROM usuarios WHERE nombre = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $datos = $result->fetch_assoc();
    if (password_verify($clave, $datos['clave'])) {
        $_SESSION['nombre'] = $datos['nombre'];
        echo json_encode(['status' => 'ok']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Contraseña incorrecta.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado.']);
}
