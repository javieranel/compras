<?php

session_start();

if (!isset($_SESSION['nombre'])) {
    header('Location: /compras/seguimiento/login.php');
    exit;
}

echo '<div style="
    background:#e7f1ff;
    color:#084298;
    padding:15px 20px;
    margin:20px;
    border:1px solid #b6d4fe;
    border-radius:10px;
    font-family:Arial;
">
    👤 Usuario conectado:
    <strong>' . htmlspecialchars($_SESSION['nombre']) . '</strong>
</div>';


include '../php/conexion.php';
require_once __DIR__ . '/../auth.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $clave = $_POST['clave'] ?? '';

    if (empty($nombre) || empty($clave)) {
        $mensaje = "Todos los campos son obligatorios.";
    } else {
        $claveHash = password_hash($clave, PASSWORD_DEFAULT);

        $stmt = $con->prepare("INSERT INTO usuarios (nombre, clave) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("ss", $nombre, $claveHash);
            if ($stmt->execute()) {
                $mensaje = "✅ Usuario <strong>$nombre</strong> creado correctamente.";
            } else {
                $mensaje = "❌ Error: el usuario ya existe o hubo un fallo.";
            }
        } else {
            $mensaje = "❌ Error al preparar la consulta.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registrar Usuario</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      background: linear-gradient(135deg, #007bff, #00c6ff);
    }
    .form-container {
      background-color: #fff;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 0 20px rgba(0,0,0,0.2);
    }
    .form-image {
      object-fit: cover;
      height: 100%;
    }
    .form-section {
      padding: 2rem;
    }
  </style>
</head>
<body>
<div class="container d-flex align-items-center justify-content-center min-vh-100">
  <div class="row w-100 form-container" style="max-width: 900px;">

    <!-- Imagen -->
    <div class="col-md-6 d-none d-md-block p-0">
      <img src="../img/melones.jpg" alt="Imagen" class="img-fluid form-image w-100">
    </div>

    <!-- Formulario -->
    <div class="col-md-6 form-section">
      <h4 class="mb-4 text-center text-primary"><i class="fas fa-user-plus me-2"></i>Registrar Nuevo Usuario</h4>

      <?php if (!empty($mensaje)): ?>
        <div class="alert alert-info text-center" role="alert">
          <?= $mensaje ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="mb-3">
          <label for="nombre" class="form-label">Nombre de usuario</label>
          <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ej. RDelagado" required>
        </div>
        <div class="mb-3">
          <label for="clave" class="form-label">Contraseña</label>
          <input type="password" class="form-control" id="clave" name="clave" placeholder="********" required>
        </div>
        <button type="submit" class="btn btn-success w-100">Registrar Usuario</button>
        <a href="./compras/seguimiento/login.php" class="btn btn-outline-secondary w-100 mt-2"><i class="fas fa-arrow-left"></i> Volver al login</a>
      </form>
    </div>
  </div>
</div>
</body>
</html>