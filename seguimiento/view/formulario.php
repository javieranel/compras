<?php

session_start();

if (!isset($_SESSION['nombre'])) {
    header('Location: /compras/seguimiento/login.php');
    exit;
}


require_once __DIR__ . '/../auth.php';
include '../php/conexion.php';


// Traer áreas para el select
$sql_areas = "SELECT DISTINCT area FROM area_solicitante ORDER BY area ASC";
$result_areas = $con->query($sql_areas);

// Traer compras para mostrar en la tabla
$sql_compras = "SELECT * FROM compras_detalle WHERE status_entrega = 'Pendiente' ORDER BY id DESC";
$result_compras = $con->query($sql_compras);

$sql_solicitantes = "SELECT DISTINCT solicitante FROM area_solicitante ORDER BY solicitante ASC";
$result_solicitantes = $con->query($sql_solicitantes);


$proveedores = [];

$sql = "SELECT nombre FROM proveedores ORDER BY nombre ASC";
$result = $con->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $proveedores[] = $row['nombre'];
    }
}


// Actualizar automáticamente el campo status_entrega en base a condiciones
$sql_update_dias = "
    UPDATE compras_detalle
    SET contador_dias = DATEDIFF(fecha_llegada, fecha_solicitud)
    WHERE fecha_llegada IS NOT NULL
";
$con->query($sql_update_dias);


// Validar órdenes con más de 15 días
$sql_alerta = "SELECT id, fecha_solicitud, alertas_dias, comentario FROM compras_detalle 
               WHERE status_entrega = 'Pendiente' 
               AND DATEDIFF(CURDATE(), fecha_solicitud) > 15 
               AND (comentario IS NULL OR comentario = '')";

$result_alerta = $con->query($sql_alerta);


// Incluye PHPMailer fuera del while
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

// Consulta de OCs pendientes
$sql = "SELECT id, orden_compra, categoria, area, solicitante, alertas_dias 
        FROM compras_detalle 
        WHERE llego_terminal = 'No' 
        AND alertas_dias > 15";
$res = $con->query($sql);

if ($res->num_rows > 0) {

    // Configuración general del correo
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.office365.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'notificacion@melonesoilterminal.com';
    $mail->Password = 'n@x1LTm#';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;
    $mail->addAddress('rdelgado@melonesterminal.com');

    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = 'Alertas de Órdenes de Compra sin cerrar';

    $cuerpo = '
        <div style="font-family: Arial, sans-serif; max-width:600px; margin:auto; border:1px solid #ddd; border-radius:8px; padding:20px; background:#f9f9f9;">
        <h2 style="color:#333; text-align:center;">⚠️ Órdenes de Compra Pendientes</h2>
        <p style="color:#555; font-size:16px;">Las siguientes órdenes llevan más de <strong>15 días</strong> sin ser marcadas como recibidas. Por favor, revisa y agrega observación si es necesario.</p>
        <table style="width:100%; border-collapse: collapse; margin-top:20px;">
            <thead>
            <tr style="background-color:#007BFF; color:#fff; text-align:left;">
                <th style="padding:10px;"># OC</th>
                <th style="padding:10px;">Categoría</th>
                <th style="padding:10px;">Área</th>
                <th style="padding:10px;">Solicitante</th>
                <th style="padding:10px; text-align:center;">Días</th>
                <th style="padding:10px;">Acción</th>
            </tr>
            </thead>
            <tbody>';

    while ($row = $res->fetch_assoc()) {
        $cuerpo .= '
            <tr style="background:#fff; border-bottom:1px solid #ddd; align="center"">
                <td style="padding:10px; color:#333;">' . htmlspecialchars($row['orden_compra']) . '</td>
                <td style="padding:10px; color:#333;">' . htmlspecialchars($row['categoria']) . '</td>
                <td style="padding:10px; color:#333;">' . htmlspecialchars($row['area']) . '</td>
                <td style="padding:10px; color:#333;">' . htmlspecialchars($row['solicitante']) . '</td>
                <td style="padding:10px; text-align:center; color:#333;">' . intval($row['alertas_dias']) . '</td>
                <td style="padding:10px;">
                <a href="https://apps.melonesoilterminal.com/compras/seguimiento/view/panel_oc.php?id=' . $row['id'] . '" 
                    style="display:inline-block; background:#28a745; color:#fff; text-decoration:none; padding:8px 16px; border-radius:5px; font-weight:bold;">
                    Agregar Observación
                </a>
                </td>
            </tr>';
    }

    $cuerpo .= '
            </tbody>
        </table>
        <p style="text-align:center; margin-top:30px; color:#777;">Este es un correo automático, por favor no respondas a este mensaje.</p>
        </div>
        ';

    $mail->Body = $cuerpo;

    try {
        $mail->send();
    } catch (Exception $e) {
        echo "Error al enviar el correo: {$mail->ErrorInfo}";
    }
}


/*
|--------------------------------------------------------------------------
| CONTADORES SEGUROS PARA LA BARRA DE ACCIONES
|--------------------------------------------------------------------------
| Se calculan sin romper si las variables no existen en este contexto.
|--------------------------------------------------------------------------
*/

$total_pendientes = 0;
if (isset($result_compras) && $result_compras instanceof mysqli_result) {
    $total_pendientes = $result_compras->num_rows;
}

$total_proveedores = 0;
if (isset($proveedores) && is_array($proveedores) && count($proveedores) > 0) {
    $total_proveedores = count($proveedores);
} else {
    // Fallback: consultar conteo si por alguna razón el array está vacío
    $res_fb = $con->query("SELECT COUNT(*) AS total FROM proveedores");
    if ($res_fb) {
        $total_proveedores = (int)($res_fb->fetch_assoc()['total'] ?? 0);
    }
}

$total_alerta = 0;
if (isset($result_alerta) && $result_alerta instanceof mysqli_result) {
    $total_alerta = $result_alerta->num_rows;
}

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
.stat-dot.danger {
    background: var(--accent-red);
    box-shadow: 0 0 0 3px var(--bg-soft-red);
}

/* =========================================================
   BOTONES
========================================================= */
.btn-modern {
    border-radius: var(--radius-md);
    font-weight: 600;
    padding: 10px 20px;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all var(--transition);
    border: 0;
    position: relative;
    overflow: hidden;
}
.btn-modern.btn-primary-modern {
    background: linear-gradient(135deg, #0d6efd, #6610f2);
    color: white;
    box-shadow: 0 4px 14px rgba(13, 110, 253, .30);
}
.btn-modern.btn-primary-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(13, 110, 253, .40);
    color: white;
}
.btn-modern.btn-primary-modern:active {
    transform: translateY(0);
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
   BADGES
========================================================= */
.badge-circle {
    width: 38px;
    height: 38px;
    display: inline-flex;
    justify-content: center;
    align-items: center;
    font-weight: 700;
    font-size: 13px;
    border-radius: 50%;
    color: white;
    box-shadow: 0 2px 6px rgba(0,0,0,.12);
    transition: transform var(--transition);
}
.badge-circle:hover { transform: scale(1.08); }

.badge-circle.bg-success {
    background: linear-gradient(135deg, #198754, #20c997) !important;
    box-shadow: 0 3px 10px rgba(25, 135, 84, .35);
}
.badge-circle.bg-warning {
    background: linear-gradient(135deg, #ffc107, #fd7e14) !important;
    color: #1a1d23 !important;
    box-shadow: 0 3px 10px rgba(253, 126, 20, .35);
}
.badge-circle.bg-danger {
    background: linear-gradient(135deg, #dc3545, #b02a37) !important;
    box-shadow: 0 3px 10px rgba(220, 53, 69, .35);
    animation: pulse-danger 2s infinite;
}
@keyframes pulse-danger {
    0%, 100% { box-shadow: 0 3px 10px rgba(220, 53, 69, .35); }
    50%      { box-shadow: 0 3px 16px rgba(220, 53, 69, .65); }
}

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
.badge-status.bg-danger {
    background: var(--bg-soft-red) !important;
    color: var(--accent-red) !important;
}

/* =========================================================
   MODAL
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
.form-control[readonly] {
    background: #f1f5f9;
    color: var(--text-secondary);
}

.btn-save {
    background: linear-gradient(135deg, #0d6efd, #6610f2);
    color: white;
    border: 0;
    border-radius: var(--radius-md);
    padding: 12px 24px;
    font-weight: 700;
    font-size: 13px;
    width: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 4px 14px rgba(13, 110, 253, .30);
    transition: all var(--transition);
}
.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(13, 110, 253, .40);
    color: white;
}
.btn-save:disabled {
    opacity: .7;
    transform: none;
    cursor: not-allowed;
}

#loader {
    text-align: center;
    padding: 20px;
    background: var(--bg-soft-blue);
    border-radius: var(--radius-md);
    margin: 12px 28px 0;
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
                <i class="bi bi-cart-plus-fill"></i>
            </div>
            <div>
                <h2>Ingresar Nueva Compra</h2>
                <p class="subtitle mb-0">
                    Registra y gestiona las órdenes de compra del sistema
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
                <span>Órdenes pendientes:</span>
                <strong><?= $total_pendientes ?></strong>
            </div>
            <div class="stat-item">
                <span class="stat-dot warning"></span>
                <span>Total proveedores:</span>
                <strong><?= $total_proveedores ?></strong>
            </div>
            <div class="stat-item">
                <span class="stat-dot danger"></span>
                <span>Órdenes con alerta:</span>
                <strong><?= $total_alerta ?></strong>
            </div>
        </div>

        <button class="btn btn-modern btn-primary-modern"
                data-bs-toggle="modal"
                data-bs-target="#modalCompra">
            <i class="bi bi-plus-lg"></i>
            Nueva Compra
        </button>
    </div>

    <!-- =========================================================
         TABLA DE COMPRAS
    ========================================================= -->
    <div class="table-wrapper animate-in delay-2">
        <div class="table-responsive">
            <table id="tablaCompras" class="table table-modern table-hover align-middle">
                <thead>
                    <tr>
                        <th>Fecha Solicitud</th>
                        <th>Status</th>
                        <th>Solicitud</th>
                        <th>Categoría</th>
                        <th>Área</th>
                        <th>Solicitante</th>
                        <th>Descripción</th>
                        <th>Proveedor</th>
                        <th>Orden Compra</th>
                        <th>Fecha Aprobada</th>
                        <th>Recibido</th>
                        <th class="text-center">Días</th>
                        <th>Estado Entrega</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_compras && $result_compras->num_rows > 0): ?>
                        <?php while ($row = $result_compras->fetch_assoc()): ?>
                            <?php
                            $fechaSolicitud = new DateTime($row['fecha_solicitud']);
                            $fechaHoy = new DateTime();
                            $diasTranscurridos = $fechaSolicitud->diff($fechaHoy)->days;
                            $reporteLlegada = empty($row['fecha_llegada']) ? 'No' : htmlspecialchars($row['fecha_llegada']);
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($row['fecha_solicitud']) ?></td>
                                <td><?= htmlspecialchars($row['status']) ?></td>
                                <td><?= htmlspecialchars($row['solicitud']) ?></td>
                                <td><?= htmlspecialchars($row['categoria']) ?></td>
                                <td><?= htmlspecialchars($row['area']) ?></td>
                                <td><?= htmlspecialchars($row['solicitante']) ?></td>
                                <td><?= htmlspecialchars($row['descripcion']) ?></td>
                                <td><?= htmlspecialchars($row['proveedor']) ?></td>
                                <td><?= htmlspecialchars($row['orden_compra']) ?></td>
                                <td><?= htmlspecialchars($row['fecha_aprobada']) ?></td>
                                <td><?= htmlspecialchars($row['llego_terminal']) ?></td>

                                <?php
                                $dias = intval($row['contador_dias']);
                                if ($dias >= 15) {
                                    $circleClass = "bg-danger";
                                    $tooltip = "Atrasado ($dias días)";
                                } elseif ($dias >= 7) {
                                    $circleClass = "bg-warning";
                                    $tooltip = "Atención ($dias días)";
                                } else {
                                    $circleClass = "bg-success";
                                    $tooltip = "En tiempo ($dias días)";
                                }
                                ?>
                                <td class="text-center">
                                    <span class="badge-circle <?= $circleClass ?>"
                                          title="<?= $tooltip ?>">
                                        <?= htmlspecialchars($dias) ?>
                                    </span>
                                </td>

                                <td>
                                    <?php
                                    $status = $row['status_entrega'];
                                    $badgeClass = ($status === 'Completada') ? 'success' : 'danger';
                                    $icon = ($status === 'Completada') ? 'check-circle-fill' : 'clock-fill';
                                    ?>
                                    <span class="badge-status bg-<?= $badgeClass ?>">
                                        <i class="bi bi-<?= $icon ?> me-1"></i>
                                        <?= htmlspecialchars($status) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="13" class="text-center py-5">
                                <div style="color: var(--text-secondary);">
                                    <i class="bi bi-inbox" style="font-size: 42px; opacity: .4;"></i>
                                    <p class="mt-2 mb-0 fw-semibold">No hay órdenes pendientes</p>
                                    <small>Todas las órdenes están al día 🎉</small>
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
     MODAL NUEVO REGISTRO
========================================================= -->
<div class="modal fade" id="modalCompra" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div id="loader" style="display:none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2 mb-0 fw-semibold">Procesando, por favor espera...</p>
        </div>

        <form id="formCompra" class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-cart-plus-fill"></i>
                    Registrar Compra
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <!-- SECCIÓN: Información básica -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="bi bi-info-circle"></i> Información básica
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="fecha_solicitud" class="form-label">Fecha de solicitud</label>
                            <input type="date" class="form-control" id="fecha_solicitud" name="fecha_solicitud" required>
                        </div>

                        <div class="col-md-6">
                            <label for="status" class="form-label">Estado</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="">Selecciona un estado</option>
                                <option value="CON OC">CON OC</option>
                                <option value="SIN OC">SIN OC</option>
                                <option value="COTIZANDO">COTIZANDO</option>
                                <option value="PENDIENTE DE PAGO">PENDIENTE DE PAGO</option>
                                <option value="ENTREGADO">ENTREGADO</option>
                                <option value="ANULADO">ANULADO</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="solicitud" class="form-label">Código Solicitud</label>
                            <input type="text" class="form-control" id="solicitud" name="solicitud" placeholder="Ej: SOL-2024-001" required>
                        </div>

                        <div class="col-md-6">
                            <label for="categoria" class="form-label">Categoría</label>
                            <select class="form-select" id="categoria" name="categoria" required>
                                <option value="">Selecciona una categoría</option>
                                <option value="COMPRA LOCAL">COMPRA LOCAL</option>
                                <option value="SERVICIOS">SERVICIOS</option>
                                <option value="IMPORTACIÓN">IMPORTACIÓN</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN: Área y solicitante -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="bi bi-people"></i> Área y solicitante
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <?php
                            $area_actual = 'Mantenimiento';
                            $areas = ['MANTENIMIENTO', 'OPERACIONES', 'PMO', 'ADMINISTRATIVO', 'IT', 'SEGURIDAD'];
                            ?>
                            <label for="area" class="form-label">Área</label>
                            <select class="form-select" id="area" name="area" required>
                                <option value="">Selecciona un área</option>
                                <?php foreach ($areas as $area): ?>
                                    <option value="<?= htmlspecialchars($area) ?>" <?= ($area === $area_actual) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($area) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="solicitante" class="form-label">Solicitante</label>
                            <select class="form-select" id="solicitante" name="solicitante" required>
                                <option value="">Selecciona un solicitante</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="correo_solicitante" class="form-label">Correo del solicitante</label>
                            <input type="text" class="form-control" name="correo_solicitante" id="correo_solicitante" placeholder="Se completará automáticamente" readonly>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN: Detalle de la compra -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="bi bi-card-text"></i> Detalle de la compra
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" placeholder="Describe el detalle de la compra..." rows="3" required></textarea>
                        </div>

                        <div class="col-md-6">
                            <label for="proveedor" class="form-label">Proveedor</label>
                            <select class="form-select" id="proveedor" name="proveedor" required>
                                <option value="">Seleccione un proveedor</option>
                                <?php foreach ($proveedores as $prov): ?>
                                    <option value="<?= htmlspecialchars($prov) ?>"><?= htmlspecialchars($prov) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="orden_compra" class="form-label">Orden de Compra</label>
                            <input type="text" class="form-control" id="orden_compra" name="orden_compra" placeholder="Ej: OC-2024-001" required>
                        </div>

                        <div class="col-md-6">
                            <label for="fecha_aprobada" class="form-label">Fecha Aprobada</label>
                            <input type="date" class="form-control" id="fecha_aprobada" name="fecha_aprobada" required>
                        </div>

                        <div class="col-md-6">
                            <label for="destino" class="form-label">¿Dónde se utilizará la compra?</label>
                            <select class="form-select" id="destino" name="destino" required>
                                <option value="" selected disabled>Selecciona el destino</option>
                                <option value="1">Isla MOTI</option>
                                <option value="2">Oficina Moti</option>
                                <option value="3">Otro Lugar</option>
                            </select>
                        </div>
                    </div>
                </div>

            </div>

            <div class="modal-footer">
                <button type="submit" class="btn-save">
                    <i class="bi bi-save-fill"></i>
                    Guardar Compra
                </button>
            </div>

        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        $('#tablaCompras').DataTable({
            responsive: true,
            order: [[1, 'asc']],
            language: {
                url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
            }
        });

        $('#formCompra').submit(function(e) {
            e.preventDefault();

            Swal.fire({
                title: 'Enviando...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: '../php/guardar_compra.php',
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    Swal.close();
                    try {
                        const res = JSON.parse(response);
                        if (res.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: 'Compra registrada correctamente.',
                                timer: 2000,
                                timerProgressBar: true,
                                showConfirmButton: false
                            });
                            $('#modalCompra').modal('hide');
                            setTimeout(() => location.reload(), 2100);
                            $('#formCompra')[0].reset();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: res.error || 'No se pudo guardar la compra.'
                            });
                        }
                    } catch {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Respuesta inválida del servidor.'
                        });
                    }
                },
                error: function() {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al conectar con el servidor.'
                    });
                }
            });
        });
    });

    $(document).ready(function() {
        $('#area').on('change', function() {
            var areaSeleccionada = $(this).val();

            if (areaSeleccionada) {
                $.ajax({
                    url: '../php/obtener_solicitantes.php',
                    type: 'POST',
                    data: { area: areaSeleccionada },
                    dataType: 'json',
                    success: function(data) {
                        $('#solicitante').empty().append('<option value="">Selecciona un solicitante</option>');

                        $.each(data, function(index, value) {
                            $('#solicitante').append(
                                '<option value="' + value.nombre + '" data-correo="' + value.correo + '">' +
                                value.nombre +
                                '</option>'
                            );
                        });
                    }
                });
            } else {
                $('#solicitante').empty().append('<option value="">Selecciona un solicitante</option>');
            }
        });

        $('#solicitante').on('change', function() {
            var correo = $(this).find(':selected').data('correo');
            $('#correo_solicitante').val(correo);
        });
    });
</script>

</body>

</html>