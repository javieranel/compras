<?php
require_once "conexion.php";
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo "ID inválido.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $clave = $_POST['clave'];

    $sql = "UPDATE usuarios SET nombre = ?, clave = ? WHERE id = ?";
    $stmt = $con->prepare($sql);
    $claveHash = password_hash($clave, PASSWORD_DEFAULT);
    $stmt->bind_param("ssi", $nombre, $claveHash, $id);
    $stmt->execute();

    //header("Location: https://apps.melonesoilterminal.com/compras/seguimiento/index.php");
    header("Location: http://localhost/compras/seguimiento/index.php");
    exit;
}

$res = $con->query("SELECT * FROM usuarios WHERE id = $id");
$usuario = $res->fetch_assoc();

if (!$usuario) {
    echo "Usuario no encontrado.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Usuario</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container mt-5">
  <h3>Editar Usuario</h3>
  <form method="POST" class="bg-white p-4 shadow-sm rounded">
    <div class="mb-3">
      <label class="form-label">Nombre de Usuario</label>
      <input type="text" name="nombre" class="form-control" 
       value="<?php echo htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8'); ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Nueva Contraseña</label>
      <input type="password" name="clave" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
    <a href="../php/eliminar_usuario.php" class="btn btn-secondary">Cancelar</a>
  </form>
</div>

</body>
</html>
