<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['nombre']) || empty($_SESSION['nombre'])) {

    $_SESSION['redirect_after_login'] =
        $_SERVER['REQUEST_URI']
        ?? '/compras/seguimiento/index.php';

    header('Location: /compras/seguimiento/login.php');
    exit;
}