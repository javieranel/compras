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


include '../includes/navbar.php';
include '../php/conexion.php';
require_once __DIR__ . '/../auth.php';


// Obtener todos los registros de compras
$query = "SELECT * FROM compras_detalle WHERE fecha_llegada IS NULL OR status_entrega = 'Pendiente' OR llego_isla = 'No' ORDER BY id DESC";
$result = $con->query($query);

// Contadores seguros para la barra
$total_registros = ($result instanceof mysqli_result) ? $result->num_rows : 0;

// Contar cuántos están pendientes de cierre (status = Pendiente)
$total_pendientes = 0;
$total_parciales = 0;
$res_tmp = $con->query("SELECT status_entrega, COUNT(*) AS total FROM compras_detalle WHERE fecha_llegada IS NULL OR status_entrega = 'Pendiente' OR llego_isla = 'No' GROUP BY status_entrega");
if ($res_tmp) {
    while ($r = $res_tmp->fetch_assoc()) {
        $st = strtolower(trim($r['status_entrega'] ?? ''));
        if ($st === 'pendiente') $total_pendientes = (int)$r['total'];
        elseif (strpos($st, 'parcial') !== false) $total_parciales = (int)$r['total'];
    }
}
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
.stat-dot.warning {
    background: var(--accent-orange);
    box-shadow: 0 0 0 3px var(--bg-soft-orange);
}
.stat-dot.purple {
    background: var(--accent-purple);
    box-shadow: 0 0 0 3px var(--bg-soft-purple);
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
    text-align: center;
}
.table-modern tbody td {
    padding: 12px;
    vertical-align: middle;
    border-bottom: 1px solid var(--border-color);
    color: var(--text-primary);
    text-align: center;
}
.table-modern tbody tr {
    transition: background-color var(--transition);
}
.table-modern tbody tr:hover {
    background-color: var(--bg-hover);
}
.table-modern tbody tr:last-child td { border-bottom: 0; }

/* =========================================================
   BADGES DE ESTADO
========================================================= */
.badge-status {
    padding: 6px 12px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 11px;
    letter-spacing: .3px;
    display: inline-block;
}
.badge-status.bg-success {
    background: var(--bg-soft-green) !important;
    color: var(--accent-green) !important;
}
.badge-status.bg-warning {
    background: var(--bg-soft-orange) !important;
    color: var(--accent-orange) !important;
}
.badge-status.bg-danger {
    background: var(--bg-soft-red) !important;
    color: var(--accent-red) !important;
}
.badge-status.bg-secondary {
    background: #eef1f5 !important;
    color: var(--text-secondary) !important;
}

/* Botones de acción en la tabla */
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

/* =========================================================
   MODAL MODERNO
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

/* Secciones dentro del modal */
.form-section { margin-bottom: 24px; }
.form-section:last-child { margin-bottom: 0; }
.form-section-title {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .8px;
    text-transform: uppercase;
    color: var(--text-secondary);
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.form-section-title::after {
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
.form-control:disabled, .form-select:disabled {
    background: #f1f5f9;
    color: var(--text-secondary);
    cursor: not-allowed;
}
.form-text {
    font-size: 11.5px;
    color: var(--text-secondary);
    margin-top: 6px;
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
.btn-modal.btn-save {
    background: linear-gradient(135deg, #198754, #20c997);
    color: white;
    box-shadow: 0 4px 14px rgba(25, 135, 84, .30);
}
.btn-modal.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(25, 135, 84, .40);
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
                <i class="bi bi-file-earmark-text-fill"></i>
            </div>
            <div>
                <h2>Completar Solicitud de Compras</h2>
                <p class="subtitle mb-0">
                    Actualiza el estado, fechas y observaciones de las órdenes en curso
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
                <span>Registros activos:</span>
                <strong><?= $total_registros ?></strong>
            </div>
            <div class="stat-item">
                <span class="stat-dot warning"></span>
                <span>Pendientes:</span>
                <strong><?= $total_pendientes ?></strong>
            </div>
            <div class="stat-item">
                <span class="stat-dot purple"></span>
                <span>Entregas parciales:</span>
                <strong><?= $total_parciales ?></strong>
            </div>
        </div>

        <div class="stat-item" style="font-size: 11.5px;">
            <i class="bi bi-info-circle text-primary"></i>
            <span>Edita o elimina cada orden según corresponda</span>
        </div>
    </div>

    <!-- =========================================================
         TABLA
    ========================================================= -->
    <div class="table-wrapper animate-in delay-2">
        <div class="table-responsive">
            <table id="table_editar" class="table table-modern table-hover align-middle">
                <thead>
                    <tr>
                        <th>Fecha Solicitud</th>
                        <th>Solicitud</th>
                        <th>Categoría</th>
                        <th>Solicitante</th>
                        <th>Descripción</th>
                        <th>Proveedor</th>
                        <th>Orden Compra</th>
                        <th>Fecha Recibido</th>
                        <th>¿Completada?</th>
                        <th>¿Llegó a Isla?</th>
                        <th><i class="bi bi-pencil-square"></i></th>
                        <th><i class="bi bi-trash"></i></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php
                            $status_entrega = $row['status_entrega'] ?? 'Pendiente';
                            $badge_status = match(true) {
                                strcasecmp($status_entrega, 'Completada') === 0 => 'success',
                                strcasecmp($status_entrega, 'Entrega Parcial') === 0 => 'warning',
                                strcasecmp($status_entrega, 'Pendiente') === 0 => 'danger',
                                default => 'secondary'
                            };

                            $llego_isla = $row['llego_isla'] ?? 'No';
                            $badge_isla = match(true) {
                                strcasecmp($llego_isla, 'Sí') === 0 => 'success',
                                strcasecmp($llego_isla, 'No Aplica') === 0 => 'secondary',
                                default => 'danger'
                            };
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($row['fecha_solicitud']) ?></td>
                                <td><strong><?= htmlspecialchars($row['solicitud']) ?></strong></td>
                                <td><?= htmlspecialchars($row['categoria']) ?></td>
                                <td><?= htmlspecialchars($row['solicitante']) ?></td>
                                <td class="text-start" style="max-width: 220px;">
                                    <?= htmlspecialchars($row['descripcion']) ?>
                                </td>
                                <td><?= htmlspecialchars($row['proveedor']) ?></td>
                                <td><?= htmlspecialchars($row['orden_compra']) ?></td>
                                <td><?= htmlspecialchars($row['fecha_llegada'] ?: '—') ?></td>
                                <td>
                                    <span class="badge-status bg-<?= $badge_status ?>">
                                        <?= htmlspecialchars($status_entrega) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-status bg-<?= $badge_isla ?>">
                                        <?= htmlspecialchars($llego_isla) ?>
                                    </span>
                                </td>
                                <td>
                                    <button
                                        class="btn btn-sm btn-outline-primary btn-action"
                                        title="Editar"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditar"

                                        data-id="<?= htmlspecialchars($row['id']) ?>"
                                        data-fecha="<?= htmlspecialchars($row['fecha_solicitud']) ?>"
                                        data-solicitud="<?= htmlspecialchars($row['solicitud']) ?>"
                                        data-categoria="<?= htmlspecialchars($row['categoria']) ?>"
                                        data-solicitante="<?= htmlspecialchars($row['solicitante']) ?>"
                                        data-descripcion="<?= htmlspecialchars($row['descripcion']) ?>"
                                        data-proveedor="<?= htmlspecialchars($row['proveedor']) ?>"
                                        data-orden="<?= htmlspecialchars($row['orden_compra']) ?>"
                                        data-llegada="<?= htmlspecialchars($row['fecha_llegada']) ?>"
                                        data-terminal="<?= htmlspecialchars($row['llego_terminal']) ?>"
                                        data-isla="<?= htmlspecialchars($row['llego_isla']) ?>"
                                        data-fecha-isla="<?= htmlspecialchars($row['fecha_llegada_isla'] ?? '') ?>"
                                        data-completada="<?= htmlspecialchars($status_entrega) ?>">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-danger btn-action"
                                        title="Eliminar"
                                        onclick="confirmarEliminar(<?= $row['id'] ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="12" class="text-center py-5">
                                <div style="color: var(--text-secondary);">
                                    <i class="bi bi-inbox" style="font-size: 42px; opacity: .4;"></i>
                                    <p class="mt-2 mb-0 fw-semibold">No hay registros activos</p>
                                    <small>Todas las órdenes están completas 🎉</small>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>


<!-- =========================================================
     MODAL EDITAR
========================================================= -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="../php/actualizar_compra.php">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square"></i>
                        Editar Compra
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" name="id" id="edit_id">

                    <!-- SECCIÓN: Información general -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-info-circle"></i> Información general
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Fecha Solicitud</label>
                                <input type="date" class="form-control" name="fecha_solicitud" id="edit_fecha_solicitud">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Solicitud</label>
                                <input type="text" class="form-control" name="solicitud" id="edit_solicitud">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Categoría</label>
                                <input type="text" class="form-control" name="categoria" id="edit_categoria">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Solicitante</label>
                                <input type="text" class="form-control" name="solicitante" id="edit_solicitante">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control" name="descripcion" id="edit_descripcion" rows="2"></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Proveedor</label>
                                <input type="text" class="form-control" name="proveedor" id="edit_proveedor">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Orden de Compra</label>
                                <input type="text" class="form-control" name="orden_compra" id="edit_orden_compra">
                            </div>
                        </div>
                    </div>

                    <!-- SECCIÓN: Llegada a Costa del Este -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-geo-alt"></i> Llegada a Costa del Este
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">¿Llegó a Costa del Este?</label>
                                <select class="form-select" name="llego_terminal" id="edit_llego_terminal">
                                    <option value="">Seleccione...</option>
                                    <option value="No">No</option>
                                    <option value="No Aplica">No Aplica</option>
                                    <option value="Sí">Sí</option>
                                </select>
                            </div>

                            <div class="col-md-6" id="div_fecha_llegada_costadeleste">
                                <label class="form-label">Fecha de Recibido (Costa del Este)</label>
                                <input type="date" class="form-control" name="fecha_llegada" id="edit_fecha_llegada">
                            </div>
                        </div>
                    </div>

                    <!-- SECCIÓN: Llegada a la Isla -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-water"></i> Llegada a la Isla MOTI
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">¿Llegó a la Isla?</label>
                                <select class="form-select" name="llego_isla" id="edit_llego_isla">
                                    <option value="">Seleccione...</option>
                                    <option value="No">No</option>
                                    <option value="No Aplica">No Aplica</option>
                                    <option value="Sí">Sí</option>
                                </select>
                            </div>

                            <div class="col-md-6" id="div_fecha_llegada_isla">
                                <label class="form-label">Fecha de Recibido (Isla MOTI)</label>
                                <input type="date" class="form-control" name="fecha_llegada_isla" id="edit_fecha_llegada_isla">
                            </div>
                        </div>
                    </div>

                    <!-- SECCIÓN: Estado de la solicitud -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-check2-circle"></i> Estado de la solicitud
                        </div>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">
                                    ¿Solicitud Completada?
                                </label>
                                <select class="form-select" name="status_entrega" id="edit_status_entrega" required>
                                    <option value="Pendiente">No</option>
                                    <option value="Entrega Parcial">Entrega Parcial</option>
                                    <option value="Completada">Sí</option>
                                </select>
                                <div class="form-text">
                                    Al seleccionar <strong>Completada</strong>, se registrará
                                    automáticamente la fecha de cierre de la OC.
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn-modal btn-save">
                        <i class="bi bi-save-fill"></i>
                        Guardar Cambios
                    </button>
                    <button type="button" class="btn-modal btn-cancel" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i>
                        Cancelar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


<?php include '../includes/footer.php'; ?>


<script>
    /* =========================================================
       DATATABLE
    ========================================================= */
    $(document).ready(function() {
        $('#table_editar').DataTable({
            language: {
                url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
            },
            responsive: true,
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50],
            order: [[0, 'desc']]
        });
    });

    /* =========================================================
       CONFIRMAR ELIMINACIÓN
    ========================================================= */
    function confirmarEliminar(id) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../php/eliminar.php?id=' + id;
            }
        });
    }

    /* =========================================================
       LLENAR MODAL AL ABRIR (con dataset)
    ========================================================= */
    document.addEventListener('DOMContentLoaded', function() {

        const modalEditar = document.getElementById('modalEditar');

        if (!modalEditar) return;

        modalEditar.addEventListener('show.bs.modal', function(event) {

            const boton = event.relatedTarget;
            if (!boton) return;

            document.getElementById('edit_id').value            = boton.dataset.id            || '';
            document.getElementById('edit_fecha_solicitud').value = boton.dataset.fecha        || '';
            document.getElementById('edit_solicitud').value      = boton.dataset.solicitud     || '';
            document.getElementById('edit_categoria').value      = boton.dataset.categoria     || '';
            document.getElementById('edit_solicitante').value    = boton.dataset.solicitante   || '';
            document.getElementById('edit_descripcion').value    = boton.dataset.descripcion   || '';
            document.getElementById('edit_proveedor').value      = boton.dataset.proveedor     || '';
            document.getElementById('edit_orden_compra').value   = boton.dataset.orden         || '';

            // Costa del Este
            document.getElementById('edit_llego_terminal').value = boton.dataset.terminal      || '';
            document.getElementById('edit_fecha_llegada').value  = boton.dataset.llegada       || '';

            // Isla
            document.getElementById('edit_llego_isla').value       = boton.dataset.isla        || '';
            document.getElementById('edit_fecha_llegada_isla').value = boton.dataset.fechaIsla || '';

            // Estado
            document.getElementById('edit_status_entrega').value = boton.dataset.completada    || 'Pendiente';

            // Ejecutar los controles de visibilidad
            controlarFechaLlegada();
            controlarLlegadaCostaEste();
        });
    });

    /* =========================================================
       CONTROL: FECHA LLEGADA A LA ISLA
    ========================================================= */
    function controlarFechaLlegada() {

        const estado    = document.getElementById("edit_llego_isla").value;
        const fecha     = document.getElementById("edit_fecha_llegada_isla");
        const contenedor = document.getElementById("div_fecha_llegada_isla");

        const reglas = {
            "Sí":        { mostrar: true,  habilitar: true  },
            "No":        { mostrar: true,  habilitar: false },
            "No Aplica": { mostrar: false, habilitar: false },
            "":          { mostrar: false, habilitar: false }
        };

        const accion = reglas[estado] || reglas[""];

        contenedor.style.display = accion.mostrar ? "block" : "none";
        fecha.disabled = !accion.habilitar;

        if (!accion.habilitar) fecha.value = "";
    }

    document
        .getElementById("edit_llego_isla")
        .addEventListener("change", controlarFechaLlegada);

    controlarFechaLlegada();

    /* =========================================================
       CONTROL: FECHA LLEGADA A COSTA DEL ESTE
    ========================================================= */
    function controlarLlegadaCostaEste() {

        const estado    = document.getElementById("edit_llego_terminal").value;
        const fecha     = document.getElementById("edit_fecha_llegada");
        const contenedor = document.getElementById("div_fecha_llegada_costadeleste");

        const reglas = {
            "Sí":        { mostrar: true,  habilitar: true  },
            "No":        { mostrar: true,  habilitar: false },
            "No Aplica": { mostrar: false, habilitar: false },
            "":          { mostrar: false, habilitar: false }
        };

        const accion = reglas[estado] || reglas[""];

        contenedor.style.display = accion.mostrar ? "block" : "none";
        fecha.disabled = !accion.habilitar;

        if (!accion.habilitar) fecha.value = "";
    }

    document
        .getElementById("edit_llego_terminal")
        .addEventListener("change", controlarLlegadaCostaEste);

    controlarLlegadaCostaEste();
</script>