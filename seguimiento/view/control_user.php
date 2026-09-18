<?php

session_start();

if (!isset($_SESSION['nombre'])) {
    header('Location: /compras/seguimiento/login.php');
    exit;
}

require_once "../php/conexion.php";
require_once __DIR__ . '/../auth.php';

$usuarios = $con->query("SELECT * FROM usuarios");

// ================================
// CONTADORES SEGUROS
// ================================
$total_usuarios = 0;
if ($usuarios instanceof mysqli_result) {
    $total_usuarios = $usuarios->num_rows;
}

// Nombre del admin en sesión (para el header)
$admin_nombre = $_SESSION['nombre'] ?? 'Administrador';
?>

<?php include '../includes/navbar.php'; ?>

<style>
/* =========================================================
   VARIABLES Y BASE
========================================================= */
:root {
    --bg-body: #f4f6fb;
    --bg-card: #ffffff;
    --bg-hover: #f0f4ff;
    --bg-input: #ffffff;
    --bg-soft-blue: rgba(13, 110, 253, .10);
    --bg-soft-green: rgba(25, 135, 84, .10);
    --bg-soft-orange: rgba(253, 126, 20, .10);
    --bg-soft-red: rgba(220, 53, 69, .10);
    --bg-soft-purple: rgba(102, 16, 242, .10);

    --text-primary: #1a1d23;
    --text-secondary: #6c757d;

    --border-color: #e5e9f0;
    --shadow-sm: 0 1px 3px rgba(0,0,0,.04), 0 1px 2px rgba(0,0,0,.06);
    --shadow-md: 0 4px 12px rgba(0,0,0,.06), 0 2px 4px rgba(0,0,0,.04);
    --shadow-lg: 0 12px 32px rgba(0,0,0,.08), 0 4px 8px rgba(0,0,0,.04);

    --accent-blue: #0d6efd;
    --accent-green: #198754;
    --accent-orange: #fd7e14;
    --accent-red: #dc3545;
    --accent-purple: #6610f2;

    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 16px;
    --radius-xl: 20px;

    --transition: 220ms cubic-bezier(.4, 0, .2, 1);
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background-color: var(--bg-body);
    padding-top: 90px;
    font-size: 13px;
    margin: 0;
    color: var(--text-primary);
    -webkit-font-smoothing: antialiased;
}

.main-content { margin-top: 20px; width: 100%; }

/* =========================================================
   HEADER DE PÁGINA
========================================================= */
.page-header {
    background: linear-gradient(135deg, #0d6efd, #6610f2);
    color: white;
    border-radius: var(--radius-xl);
    padding: 32px 36px;
    margin-bottom: 24px;
    box-shadow: 0 15px 35px rgba(13, 110, 253, .25);
    position: relative;
    overflow: hidden;
}
.page-header::before {
    content: "";
    position: absolute;
    top: -50%; right: -20%;
    width: 400px; height: 400px;
    background: radial-gradient(circle, rgba(255,255,255,.15), transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
.page-header h2 {
    font-weight: 800;
    letter-spacing: -.5px;
    font-size: 1.75rem;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
}
.page-header .subtitle {
    opacity: .88;
    margin: 6px 0 0;
    font-size: .9rem;
}
.page-header .header-icon {
    width: 52px; height: 52px;
    background: rgba(255,255,255,.18);
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    backdrop-filter: blur(8px);
    flex-shrink: 0;
}

/* =========================================================
   BARRA DE ACCIONES
========================================================= */
.action-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    padding: 16px 20px;
    background: var(--bg-card);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
}
.action-bar .stats {
    display: flex;
    gap: 24px;
    align-items: center;
    flex-wrap: wrap;
}
.stat-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: var(--text-secondary);
}
.stat-item strong {
    color: var(--text-primary);
    font-weight: 700;
    font-size: 14px;
}
.stat-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: var(--accent-blue);
    box-shadow: 0 0 0 3px var(--bg-soft-blue);
}
.stat-dot.purple {
    background: var(--accent-purple);
    box-shadow: 0 0 0 3px var(--bg-soft-purple);
}

/* Botón crear usuario (opcional) */
.btn-create {
    background: linear-gradient(135deg, #0d6efd, #6610f2);
    color: white;
    border: 0;
    border-radius: var(--radius-md);
    padding: 10px 20px;
    font-weight: 600;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 14px rgba(13, 110, 253, .30);
    transition: all var(--transition);
    text-decoration: none;
}
.btn-create:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(13, 110, 253, .40);
    color: white;
}

/* =========================================================
   TABLA MODERNA
========================================================= */
.table-wrapper {
    background: var(--bg-card);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border-color);
    overflow: hidden;
}
.table-modern {
    margin: 0;
    font-size: 12.5px;
    color: var(--text-primary);
}
.table-modern thead th {
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    color: var(--text-primary);
    font-weight: 700;
    font-size: 11px;
    letter-spacing: .4px;
    text-transform: uppercase;
    padding: 14px 12px;
    border-bottom: 2px solid var(--border-color);
    white-space: nowrap;
}
.table-modern tbody td {
    padding: 12px;
    vertical-align: middle;
    border-bottom: 1px solid var(--border-color);
    color: var(--text-primary);
}
.table-modern tbody tr {
    transition: background-color var(--transition);
}
.table-modern tbody tr:hover {
    background-color: var(--bg-hover);
}
.table-modern tbody tr:last-child td { border-bottom: 0; }

/* =========================================================
   BADGE DE ID
========================================================= */
.badge-id {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 700;
    font-size: 11px;
    background: #eef1f5;
    color: var(--text-secondary);
    font-family: 'JetBrains Mono', 'Courier New', monospace;
}

/* =========================================================
   USUARIO CON AVATAR
========================================================= */
.user-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}
.user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0d6efd, #6610f2);
    color: white;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 13px;
    flex-shrink: 0;
    text-transform: uppercase;
    box-shadow: 0 3px 8px rgba(13, 110, 253, .25);
}
.user-name {
    font-weight: 600;
    color: var(--text-primary);
}
.user-badge-admin {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 50px;
    background: var(--bg-soft-purple);
    color: var(--accent-purple);
    font-size: 10px;
    font-weight: 700;
    margin-left: 6px;
    text-transform: uppercase;
    letter-spacing: .3px;
}

/* =========================================================
   BOTONES DE ACCIÓN
========================================================= */
.btn-action {
    border-radius: var(--radius-md);
    padding: 7px 14px;
    font-weight: 600;
    font-size: 12px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all var(--transition);
    border-width: 1.5px;
    text-decoration: none;
}
.btn-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,.12);
}
.btn-action.btn-edit {
    color: var(--accent-orange);
    border-color: var(--accent-orange);
    background: transparent;
}
.btn-action.btn-edit:hover {
    background: var(--accent-orange);
    color: white;
    border-color: var(--accent-orange);
}
.btn-action.btn-delete {
    color: var(--accent-red);
    border-color: var(--accent-red);
    background: transparent;
}
.btn-action.btn-delete:hover {
    background: var(--accent-red);
    color: white;
    border-color: var(--accent-red);
}

/* =========================================================
   ANIMACIONES
========================================================= */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}
.animate-in { animation: fadeInUp .5s cubic-bezier(.4, 0, .2, 1) backwards; }
.animate-in.delay-1 { animation-delay: .08s; }
.animate-in.delay-2 { animation-delay: .16s; }

/* =========================================================
   RESPONSIVE
========================================================= */
@media (max-width: 768px) {
    body { padding-top: 80px; }
    .page-header { padding: 24px 20px; }
    .page-header h2 { font-size: 1.35rem; }
    .page-header .header-icon { width: 42px; height: 42px; font-size: 20px; }
    .action-bar { padding: 14px 16px; }
    .user-cell { gap: 8px; }
    .user-avatar { width: 32px; height: 32px; font-size: 12px; }
}
</style>


<!-- =========================================================
     HEADER DE PÁGINA
========================================================= -->
<div class="container main-content mt-4">

    <div class="page-header animate-in">
        <div class="d-flex align-items-center gap-3">
            <div class="header-icon">
                <i class="bi bi-people-fill"></i>
            </div>
            <div>
                <h2>Administrador de Usuarios</h2>
                <p class="subtitle mb-0">
                    Gestiona los usuarios con acceso al sistema de compras
                </p>
            </div>
        </div>
    </div>

    <!-- =========================================================
         BARRA DE ACCIONES
    ========================================================= -->
    <div class="action-bar animate-in delay-1">
        <div class="stats">
            <div class="stat-item">
                <span class="stat-dot"></span>
                <span>Total usuarios:</span>
                <strong><?= $total_usuarios ?></strong>
            </div>
            <div class="stat-item">
                <span class="stat-dot purple"></span>
                <span>Sesión activa:</span>
                <strong><?= htmlspecialchars($admin_nombre) ?></strong>
            </div>
        </div>

        <a href="/compras/seguimiento/view/create_user.php" class="btn-create">
            <i class="bi bi-person-plus-fill"></i>
            Nuevo Usuario
        </a>
    </div>

    <!-- =========================================================
         TABLA DE USUARIOS
    ========================================================= -->
    <div class="table-wrapper animate-in delay-2">
        <div class="table-responsive">
            <table class="table table-modern table-hover align-middle">
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th>Usuario</th>
                        <th class="text-center" style="width: 220px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($usuarios && $usuarios->num_rows > 0): ?>
                        <?php while ($row = $usuarios->fetch_assoc()): ?>
                            <?php
                            // Iniciales del usuario
                            $nombre = trim($row['nombre'] ?? '');
                            $iniciales = '';
                            if ($nombre !== '') {
                                $partes = preg_split('/[\s._-]+/', $nombre);
                                if (count($partes) >= 2) {
                                    $iniciales = mb_substr($partes[0], 0, 1) . mb_substr($partes[1], 0, 1);
                                } else {
                                    $iniciales = mb_substr($nombre, 0, 2);
                                }
                            }
                            $iniciales = mb_strtoupper($iniciales);

                            // Detectar si es admin (por nombre)
                            $es_admin = stripos($nombre, 'admin') !== false;
                            ?>
                            <tr>
                                <td>
                                    <span class="badge-id">#<?= htmlspecialchars($row['id']) ?></span>
                                </td>
                                <td>
                                    <div class="user-cell">
                                        <span class="user-avatar"><?= htmlspecialchars($iniciales) ?></span>
                                        <div>
                                            <span class="user-name"><?= htmlspecialchars($row['nombre']) ?></span>
                                            <?php if ($es_admin): ?>
                                                <span class="user-badge-admin">
                                                    <i class="bi bi-shield-fill-check"></i> Admin
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <a href="https://apps.melonesoilterminal.com/compras/seguimiento/php/edit_user.php?id=<?= htmlspecialchars($row['id']) ?>"
                                       class="btn-action btn-edit">
                                        <i class="bi bi-pencil-square"></i>
                                        Editar
                                    </a>

                                    <button class="btn-action btn-delete eliminar-btn"
                                            data-id="<?= htmlspecialchars($row['id']) ?>"
                                            data-nombre="<?= htmlspecialchars($row['nombre']) ?>">
                                        <i class="bi bi-trash"></i>
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center py-5">
                                <div style="color: var(--text-secondary);">
                                    <i class="bi bi-inbox" style="font-size: 42px; opacity: .4;"></i>
                                    <p class="mt-2 mb-0 fw-semibold">No hay usuarios registrados</p>
                                    <small>Usa el botón "Nuevo Usuario" para agregar el primero</small>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.querySelectorAll('.eliminar-btn').forEach(btn => {
  btn.addEventListener('click', function () {
    const userId = this.dataset.id;
    const userName = this.dataset.nombre || 'este usuario';

    Swal.fire({
      title: '¿Estás seguro?',
      html: `Vas a eliminar al usuario <strong style="color:#dc3545;">${userName}</strong>.<br>Esta acción no se puede deshacer.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = `https://apps.melonesoilterminal.com/compras/seguimiento/php/eliminar_usuario.php?id=${userId}`;
      }
    });
  });
});
</script>

<?php include '../includes/footer.php'; ?>