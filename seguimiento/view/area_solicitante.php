<?php

session_start();

if (!isset($_SESSION['nombre'])) {
    header('Location: /compras/seguimiento/login.php');
    exit;
}

echo '<div class="user-banner">
    👤 Usuario conectado:
    <strong>' . htmlspecialchars($_SESSION['nombre']) . '</strong>
</div>';


include '../php/conexion.php';
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    $area = trim($_POST['area']);
    $solicitante = trim($_POST['solicitante']);
    $correo = trim($_POST['correo']);
    if (!empty($area) && !empty($solicitante)) {
      $stmt = $con->prepare("INSERT INTO area_solicitante (area, solicitante,correo) VALUES (?, ?, ?)");
      $stmt->bind_param("sss", $area, $solicitante, $correo);
      $stmt->execute();
      $stmt->close();
    }
  }

  if (isset($_POST['accion']) && $_POST['accion'] === 'editar') {
    $id = $_POST['id'];
    $area = trim($_POST['area']);
    $solicitante = trim($_POST['solicitante']);
    $correo = trim($_POST['correo']);
    $stmt = $con->prepare("UPDATE area_solicitante SET area = ?, solicitante = ?, correo = ? WHERE id = ?");
    $stmt->bind_param("sssi", $area, $solicitante, $correo, $id);
    $stmt->execute();
    $stmt->close();
  }

  if (isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
    $id = $_POST['id'];
    $stmt = $con->prepare("DELETE FROM area_solicitante WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
  }

  header("Location: " . $_SERVER['PHP_SELF']);
  exit;
}

include '../includes/navbar.php';


// ================================
// CONTADORES SEGUROS PARA LA BARRA
// ================================
$total_registros = 0;
$r = $con->query("SELECT COUNT(*) AS total FROM area_solicitante");
if ($r) $total_registros = (int)($r->fetch_assoc()['total'] ?? 0);

$total_areas = 0;
$r = $con->query("SELECT COUNT(DISTINCT area) AS total FROM area_solicitante");
if ($r) $total_areas = (int)($r->fetch_assoc()['total'] ?? 0);
?>

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
   BANNER DE USUARIO
========================================================= */
.user-banner {
    background: linear-gradient(135deg, #e7f1ff, #f0e7ff);
    color: #084298;
    padding: 14px 20px;
    margin: 20px;
    border: 1px solid #d0e2ff;
    border-radius: var(--radius-md);
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: var(--shadow-sm);
}

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

/* =========================================================
   CARD DEL FORMULARIO
========================================================= */
.form-card {
    background: var(--bg-card);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border-color);
    padding: 24px 28px;
    margin-bottom: 20px;
}
.form-card-title {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .8px;
    text-transform: uppercase;
    color: var(--text-secondary);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.form-card-title::after {
    content: "";
    flex: 1;
    height: 1px;
    background: var(--border-color);
}

/* Formularios */
.form-label {
    font-weight: 600;
    font-size: 12px;
    color: var(--text-primary);
    margin-bottom: 6px;
}
.form-control, .form-select {
    border-radius: var(--radius-md);
    padding: 10px 14px;
    font-size: 13px;
    border: 1.5px solid var(--border-color);
    background: var(--bg-input);
    color: var(--text-primary);
    transition: all var(--transition);
}
.form-control:focus, .form-select:focus {
    border-color: var(--accent-blue);
    box-shadow: 0 0 0 4px rgba(13, 110, 253, .10);
    background: var(--bg-input);
}

/* Botón guardar */
.btn-save {
    background: linear-gradient(135deg, #198754, #20c997);
    color: white;
    border: 0;
    border-radius: var(--radius-md);
    padding: 10px 20px;
    font-weight: 600;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    box-shadow: 0 4px 14px rgba(25, 135, 84, .30);
    transition: all var(--transition);
}
.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(25, 135, 84, .40);
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

/* Badge de área */
.badge-area {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 11px;
    letter-spacing: .3px;
    background: var(--bg-soft-blue);
    color: var(--accent-blue);
}
.badge-area.mantenimiento   { background: var(--bg-soft-blue);   color: var(--accent-blue); }
.badge-area.operaciones     { background: var(--bg-soft-green);  color: var(--accent-green); }
.badge-area.pmo             { background: var(--bg-soft-purple); color: var(--accent-purple); }
.badge-area.administracion  { background: var(--bg-soft-orange); color: var(--accent-orange); }
.badge-area.it              { background: var(--bg-soft-blue);   color: var(--accent-blue); }
.badge-area.seguridad       { background: var(--bg-soft-red);    color: var(--accent-red); }

/* Correo con icono */
.email-cell {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--text-secondary);
    font-size: 12px;
}
.email-cell a {
    color: var(--accent-blue);
    text-decoration: none;
    font-weight: 500;
}
.email-cell a:hover { text-decoration: underline; }

/* Botones de acción */
.btn-action {
    width: 34px;
    height: 34px;
    padding: 0;
    border-radius: var(--radius-md);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all var(--transition);
    border-width: 1.5px;
}
.btn-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,.12);
}
.btn-action.btn-outline-warning:hover {
    background: var(--accent-orange);
    border-color: var(--accent-orange);
    color: white;
}
.btn-action.btn-outline-danger:hover {
    background: var(--accent-red);
    border-color: var(--accent-red);
    color: white;
}

/* =========================================================
   MODALES
========================================================= */
.modal-content {
    border: 0;
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-lg);
    overflow: hidden;
}
.modal-header {
    background: linear-gradient(135deg, #0d6efd, #6610f2);
    color: white;
    padding: 22px 28px;
    border-bottom: 0;
}
.modal-header.modal-header-danger {
    background: linear-gradient(135deg, #dc3545, #b02a37);
}
.modal-header .modal-title {
    font-weight: 700;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 10px;
}
.modal-header .btn-close {
    filter: brightness(0) invert(1);
    opacity: .8;
}
.modal-header .btn-close:hover { opacity: 1; }

.modal-body {
    padding: 28px;
    background: var(--bg-card);
}
.modal-footer {
    background: #f8fafc;
    border-top: 1px solid var(--border-color);
    padding: 16px 28px;
}

/* Botones del modal */
.btn-modal {
    border-radius: var(--radius-md);
    padding: 10px 20px;
    font-weight: 600;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all var(--transition);
    border: 0;
}
.btn-modal.btn-primary-modern {
    background: linear-gradient(135deg, #0d6efd, #6610f2);
    color: white;
    box-shadow: 0 4px 14px rgba(13, 110, 253, .30);
}
.btn-modal.btn-primary-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(13, 110, 253, .40);
    color: white;
}
.btn-modal.btn-danger-modern {
    background: linear-gradient(135deg, #dc3545, #b02a37);
    color: white;
    box-shadow: 0 4px 14px rgba(220, 53, 69, .30);
}
.btn-modal.btn-danger-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(220, 53, 69, .40);
    color: white;
}
.btn-modal.btn-cancel {
    background: #eef1f5;
    color: var(--text-primary);
}
.btn-modal.btn-cancel:hover {
    background: #e2e6ec;
    color: var(--text-primary);
}

/* Icono grande en modal eliminar */
.delete-icon {
    width: 72px; height: 72px;
    margin: 0 auto 16px;
    border-radius: 50%;
    background: var(--bg-soft-red);
    color: var(--accent-red);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
}
.delete-message {
    text-align: center;
    color: var(--text-primary);
}
.delete-message h5 {
    font-weight: 700;
    margin-bottom: 8px;
}
.delete-message p {
    color: var(--text-secondary);
    font-size: 13px;
    margin: 0;
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
.animate-in.delay-3 { animation-delay: .24s; }

/* =========================================================
   DATATABLE OVERRIDES
========================================================= */
.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select {
    border-radius: var(--radius-md);
    border: 1.5px solid var(--border-color);
    padding: 8px 12px;
    font-size: 13px;
    transition: all var(--transition);
}
.dataTables_wrapper .dataTables_filter input:focus,
.dataTables_wrapper .dataTables_length select:focus {
    border-color: var(--accent-blue);
    box-shadow: 0 0 0 4px rgba(13, 110, 253, .10);
    outline: 0;
}
.dataTables_wrapper .dataTables_paginate .paginate_button {
    border-radius: var(--radius-sm) !important;
    margin: 0 2px;
    border: 0 !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: linear-gradient(135deg, #0d6efd, #6610f2) !important;
    color: white !important;
    border: 0 !important;
}
.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter {
    padding: 14px 20px;
    font-size: 12.5px;
    color: var(--text-secondary);
}

/* =========================================================
   RESPONSIVE
========================================================= */
@media (max-width: 768px) {
    body { padding-top: 80px; }
    .page-header { padding: 24px 20px; }
    .page-header h2 { font-size: 1.35rem; }
    .page-header .header-icon { width: 42px; height: 42px; font-size: 20px; }
    .action-bar { padding: 14px 16px; }
    .form-card { padding: 20px; }
    .modal-body { padding: 20px; }
    .modal-header { padding: 18px 20px; }
    .modal-footer { padding: 14px 20px; }
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
                <h2>Área y Solicitante</h2>
                <p class="subtitle mb-0">
                    Registra y administra las áreas y solicitantes del sistema
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
                <span>Registros totales:</span>
                <strong><?= $total_registros ?></strong>
            </div>
            <div class="stat-item">
                <span class="stat-dot purple"></span>
                <span>Áreas únicas:</span>
                <strong><?= $total_areas ?></strong>
            </div>
        </div>

        <div class="stat-item" style="font-size: 11.5px;">
            <i class="bi bi-info-circle text-primary"></i>
            <span>Los datos se guardan automáticamente al enviar el formulario</span>
        </div>
    </div>

    <!-- =========================================================
         FORMULARIO DE REGISTRO
    ========================================================= -->
    <div class="form-card animate-in delay-2">
        <div class="form-card-title">
            <i class="bi bi-plus-circle-fill"></i> Registrar nuevo
        </div>

        <form method="POST">
            <input type="hidden" name="accion" value="crear">

            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Área</label>
                    <select name="area" class="form-select" required>
                        <option value="">Seleccione un área</option>
                        <option value="Mantenimiento">Mantenimiento</option>
                        <option value="Operaciones">Operaciones</option>
                        <option value="PMO">PMO</option>
                        <option value="Administración">Administración</option>
                        <option value="IT">IT</option>
                        <option value="Seguridad e Higiene">Seguridad e Higiene</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Solicitante</label>
                    <input type="text" name="solicitante" class="form-control"
                           placeholder="Nombre del solicitante" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Correo electrónico</label>
                    <input type="email" name="correo" class="form-control"
                           placeholder="correo@empresa.com" required>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn-save">
                        <i class="bi bi-save-fill"></i>
                        Guardar
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- =========================================================
         TABLA
    ========================================================= -->
    <div class="table-wrapper animate-in delay-3">
        <div class="table-responsive">
            <table id="tablaAreaSolicitante" class="table table-modern table-hover align-middle">
                <thead>
                    <tr>
                        <th>Área</th>
                        <th>Solicitante</th>
                        <th>Correo</th>
                        <th class="text-center" style="width: 100px;">Editar</th>
                        <th class="text-center" style="width: 100px;">Eliminar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $con->query("SELECT * FROM area_solicitante ORDER BY id DESC");
                    if ($result && $result->num_rows > 0):
                        while ($row = $result->fetch_assoc()):

                            // Clase de color por área
                            $area_lower = strtolower($row['area']);
                            $area_class = 'badge-area';
                            if (strpos($area_lower, 'mantenimiento') !== false)   $area_class .= ' mantenimiento';
                            elseif (strpos($area_lower, 'operaciones') !== false) $area_class .= ' operaciones';
                            elseif (strpos($area_lower, 'pmo') !== false)         $area_class .= ' pmo';
                            elseif (strpos($area_lower, 'administra') !== false)  $area_class .= ' administracion';
                            elseif (strpos($area_lower, 'it') !== false)          $area_class .= ' it';
                            elseif (strpos($area_lower, 'seguridad') !== false)   $area_class .= ' seguridad';
                    ?>
                        <tr>
                            <td>
                                <span class="<?= $area_class ?>">
                                    <?= htmlspecialchars($row['area']) ?>
                                </span>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($row['solicitante']) ?></strong>
                            </td>
                            <td>
                                <span class="email-cell">
                                    <i class="bi bi-envelope-fill"></i>
                                    <a href="mailto:<?= htmlspecialchars($row['correo']) ?>">
                                        <?= htmlspecialchars($row['correo']) ?>
                                    </a>
                                </span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-warning btn-action"
                                        title="Editar"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditar"
                                        data-id="<?= htmlspecialchars($row['id']) ?>"
                                        data-area="<?= htmlspecialchars($row['area']) ?>"
                                        data-solicitante="<?= htmlspecialchars($row['solicitante']) ?>"
                                        data-correo="<?= htmlspecialchars($row['correo']) ?>">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-danger btn-action"
                                        title="Eliminar"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEliminar"
                                        data-id="<?= htmlspecialchars($row['id']) ?>"
                                        data-solicitante="<?= htmlspecialchars($row['solicitante']) ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div style="color: var(--text-secondary);">
                                    <i class="bi bi-inbox" style="font-size: 42px; opacity: .4;"></i>
                                    <p class="mt-2 mb-0 fw-semibold">No hay registros disponibles</p>
                                    <small>Usa el formulario de arriba para agregar el primero</small>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>


<?php include '../includes/footer.php'; ?>


<!-- =========================================================
     MODAL EDITAR
========================================================= -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-labelledby="editarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id" id="edit-id">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil-square"></i>
                    Editar Área / Solicitante
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Área</label>
                    <input type="text" class="form-control" name="area" id="edit-area" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Solicitante</label>
                    <input type="text" class="form-control" name="solicitante" id="edit-solicitante" required>
                </div>

                <div class="mb-0">
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" class="form-control" name="correo" id="edit-correo" required>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-modal btn-cancel" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i>
                    Cancelar
                </button>
                <button type="submit" class="btn-modal btn-primary-modern">
                    <i class="bi bi-save-fill"></i>
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>


<!-- =========================================================
     MODAL ELIMINAR
========================================================= -->
<div class="modal fade" id="modalEliminar" tabindex="-1" aria-labelledby="eliminarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="id" id="delete-id">

            <div class="modal-header modal-header-danger">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    Confirmar eliminación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <div class="delete-message">
                    <div class="delete-icon">
                        <i class="bi bi-trash"></i>
                    </div>
                    <h5>¿Estás seguro?</h5>
                    <p>
                        Vas a eliminar a
                        <strong id="delete-nombre" style="color: var(--accent-red);"></strong>.
                        Esta acción no se puede deshacer.
                    </p>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-modal btn-cancel" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i>
                    Cancelar
                </button>
                <button type="submit" class="btn-modal btn-danger-modern">
                    <i class="bi bi-trash-fill"></i>
                    Sí, eliminar
                </button>
            </div>
        </form>
    </div>
</div>


<script>
  $(document).ready(function() {
    $('#tablaAreaSolicitante').DataTable({
      language: {
        url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
      },
      responsive: true,
      order: [[0, 'asc']],
      pageLength: 10,
      lengthMenu: [5, 10, 25, 50]
    });

    // Rellenar campos del modal editar
    $('#modalEditar').on('show.bs.modal', function(event) {
      const button = $(event.relatedTarget);
      $('#edit-id').val(button.data('id'));
      $('#edit-area').val(button.data('area'));
      $('#edit-solicitante').val(button.data('solicitante'));
      $('#edit-correo').val(button.data('correo'));
    });

    // Pasar ID y nombre al modal eliminar
    $('#modalEliminar').on('show.bs.modal', function(event) {
      const button = $(event.relatedTarget);
      $('#delete-id').val(button.data('id'));
      $('#delete-nombre').text(button.data('solicitante') || 'este registro');
    });
  });
</script>

</body>

</html>