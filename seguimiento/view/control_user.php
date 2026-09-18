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


require_once "../php/conexion.php"; // Asegúrate de que esta conexión esté bien
$usuarios = $con->query("SELECT * FROM usuarios");
require_once __DIR__ . '/../auth.php';
include '../includes/navbar.php';
?>



<div class="container mt-5">
    <h3 class="mb-4">Administrador de Usuarios</h3>

    <table class="table table-bordered table-hover bg-white shadow-sm">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Nombre de Usuario</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $usuarios->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['nombre']) ?></td>
                    <td>
                        <a href="https://apps.melonesoilterminal.com/compras/seguimiento/php/edit_user.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                        <button class="btn btn-danger btn-sm eliminar-btn" data-id="<?= $row['id'] ?>">Eliminar</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.querySelectorAll('.eliminar-btn').forEach(btn => {
  btn.addEventListener('click', function () {
    const userId = this.dataset.id;

    Swal.fire({
      title: '¿Estás seguro?',
      text: 'Esta acción no se puede deshacer',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = `https://apps.melonesoilterminal.com/compras/seguimiento/php/eliminar_usuario.php?id=${userId}`;
      }
    });
  });
});
</script>

<?php include '../includes/footer.php';?>
