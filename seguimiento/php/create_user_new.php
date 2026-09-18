<?php
session_start();
include 'conexion.php'; // Asegúrate de que este archivo conecta correctamente

$nombre = trim($_POST['nombre'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($nombre) || empty($password)) {
    $_SESSION['error'] = 'Todos los campos son obligatorios.';
    header('Location: /compras/login.php');
    exit;
}

// Encriptar la contraseña
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Insertar en base de datos
$stmt = $con->prepare("INSERT INTO usuarios (nombre, clave) VALUES (?, ?)");
$stmt->bind_param("ss", $nombre, $passwordHash);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Usuario creado exitosamente.';
} else {
    $_SESSION['error'] = 'Error al crear usuario. Es posible que ya exista.';
}

header('Location:../compras/seguimiento/login.php');
exit;
?>

<?php
session_start();
if (isset($_SESSION['success']) || isset($_SESSION['error'])):
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  Swal.fire({
    icon: '<?= isset($_SESSION['success']) ? 'success' : 'error' ?>',
    title: '<?= isset($_SESSION['success']) ? '¡Éxito!' : '¡Error!' ?>',
    text: '<?= $_SESSION['success'] ?? $_SESSION['error'] ?>'
  });
</script>
<?php unset($_SESSION['success'], $_SESSION['error']); endif; ?>

