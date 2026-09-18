<?php
session_start();

if (!isset($_SESSION['nombre'])) {
    header('Location: /compras/seguimiento/login.php');
    exit;
}


include '../php/conexion.php';
require_once __DIR__ . '/../auth.php';


// ================================
// CONTADORES PARA LOS BOTONES
// ================================

$total_compras = $con->query("
    SELECT COUNT(*) AS total 
    FROM compras_detalle
")->fetch_assoc()['total'];


$total_cerradas = $con->query("
    SELECT COUNT(*) AS total 
    FROM compras_detalle
    WHERE status_entrega = 'Completada'
")->fetch_assoc()['total'];


$total_pendientes = $con->query("
    SELECT COUNT(*) AS total 
    FROM compras_detalle
    WHERE status_entrega = 'Pendiente'
")->fetch_assoc()['total'];


$total_parcial = $con->query("
    SELECT COUNT(*) AS total 
    FROM compras_detalle
    WHERE status_entrega = 'Parcial'
")->fetch_assoc()['total'];


// ================================
// ACTUALIZAR DIAS AUTOMATICAMENTE
// ================================

$sql_update_dias = "
    UPDATE compras_detalle
    SET contador_dias = DATEDIFF(CURDATE(), fecha_solicitud)
";

$con->query($sql_update_dias);


// ================================
// TRAER COMPRAS
// ================================

$sql_compras = "
    SELECT * 
    FROM compras_detalle
    ORDER BY fecha_solicitud DESC
";

$result_compras = $con->query($sql_compras);

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
   BARRA DE FILTROS
========================================================= */
.filtros-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
    margin-bottom: 20px;
    padding: 16px 20px;
    background: var(--bg-card);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
}
.filtros-bar .label {
    font-size: 11.5px;
    font-weight: 700;
    letter-spacing: .5px;
    text-transform: uppercase;
    color: var(--text-secondary);
    margin-right: 6px;
}

.btn-filtro {
    border-radius: var(--radius-md);
    padding: 9px 16px;
    font-size: 12.5px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all var(--transition);
    border: 1.5px solid transparent;
    position: relative;
}
.btn-filtro .count {
    background: rgba(255,255,255,.25);
    padding: 2px 8px;
    border-radius: 50px;
    font-size: 11px;
    font-weight: 700;
    margin-left: 2px;
}
.btn-filtro:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,.12);
}
.btn-filtro.active {
    box-shadow: 0 4px 12px rgba(0,0,0,.15);
    transform: translateY(-1px);
}
.btn-filtro.active::after {
    content: "";
    position: absolute;
    bottom: -3px; left: 50%;
    transform: translateX(-50%);
    width: 20px; height: 3px;
    border-radius: 3px;
    background: currentColor;
    opacity: .5;
}

/* Colores específicos de cada botón (mantengo clases bootstrap + refuerzo) */
.btn-filtro.btn-primary {
    background: linear-gradient(135deg, #0d6efd, #4d8dfd);
    border-color: #0d6efd;
}
.btn-filtro.btn-success {
    background: linear-gradient(135deg, #198754, #20c997);
    border-color: #198754;
}
.btn-filtro.btn-danger {
    background: linear-gradient(135deg, #dc3545, #e4606d);
    border-color: #dc3545;
}
.btn-filtro.btn-warning {
    background: linear-gradient(135deg, #ffc107, #fd7e14);
    color: #1a1d23;
    border-color: #ffc107;
}
.btn-filtro.btn-warning .count {
    background: rgba(0,0,0,.12);
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
   BADGES DE ESTADO
========================================================= */
.badge-status {
    padding: 6px 12px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 11px;
    letter-spacing: .3px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
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
    .filtros-bar { padding: 14px 16px; }
    .btn-filtro { padding: 8px 12px; font-size: 11.5px; }
}
</style>


<!-- =========================================================
     HEADER DE PÁGINA
========================================================= -->
<div class="container main-content mt-4">

    <div class="page-header animate-in">
        <div class="d-flex align-items-center gap-3">
            <div class="header-icon">
                <i class="bi bi-database-fill-check"></i>
            </div>
            <div>
                <h2>Base de Datos · Registro de Compras</h2>
                <p class="subtitle mb-0">
                    Consulta y filtra el historial completo de órdenes de compra
                </p>
            </div>
        </div>
    </div>

    <!-- =========================================================
         BARRA DE FILTROS
    ========================================================= -->
    <div class="filtros-bar animate-in delay-1">
        <span class="label">
            <i class="bi bi-funnel-fill me-1"></i> Filtrar:
        </span>

        <button class="btn btn-primary btn-filtro filtro active" data-status="">
            <i class="bi bi-list-ul"></i>
            Todas
            <span class="count"><?= $total_compras ?></span>
        </button>

        <button class="btn btn-success btn-filtro filtro" data-status="Completada">
            <i class="bi bi-check-circle-fill"></i>
            OC Cerradas
            <span class="count"><?= $total_cerradas ?></span>
        </button>

        <button class="btn btn-danger btn-filtro filtro" data-status="Pendiente">
            <i class="bi bi-clock-fill"></i>
            OC Pendientes
            <span class="count"><?= $total_pendientes ?></span>
        </button>

        <button class="btn btn-warning btn-filtro filtro" data-status="Parcial">
            <i class="bi bi-hourglass-split"></i>
            Entrega Parcial
            <span class="count"><?= $total_parcial ?></span>
        </button>
    </div>

    <!-- =========================================================
         TABLA
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
                        <th>Días</th>
                        <th>Estado Entrega</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_compras && $result_compras->num_rows > 0): ?>
                        <?php while ($row = $result_compras->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['fecha_solicitud']) ?></td>
                                <td><?= htmlspecialchars($row['status']) ?></td>
                                <td><strong><?= htmlspecialchars($row['solicitud']) ?></strong></td>
                                <td><?= htmlspecialchars($row['categoria']) ?></td>
                                <td><?= htmlspecialchars($row['area']) ?></td>
                                <td><?= htmlspecialchars($row['solicitante']) ?></td>
                                <td style="max-width: 220px;"><?= htmlspecialchars($row['descripcion']) ?></td>
                                <td><?= htmlspecialchars($row['proveedor']) ?></td>
                                <td><?= htmlspecialchars($row['orden_compra']) ?></td>
                                <td><?= htmlspecialchars($row['fecha_aprobada']) ?></td>
                                <td><?= htmlspecialchars($row['llego_terminal']) ?></td>
                                <td class="text-center">
                                    <?= htmlspecialchars($row['contador_dias']) ?>
                                </td>

                                <td data-search="<?= htmlspecialchars($row['status_entrega']) ?>">
                                    <?php
                                    $statusEntrega = $row['status_entrega'];

                                    if ($statusEntrega == "Completada") {
                                        echo '<span class="badge-status bg-success"><i class="bi bi-check-circle-fill"></i> OC Cerrada</span>';
                                    } elseif ($statusEntrega == "Pendiente") {
                                        echo '<span class="badge-status bg-danger"><i class="bi bi-clock-fill"></i> OC Pendiente</span>';
                                    } elseif ($statusEntrega == "Parcial") {
                                        echo '<span class="badge-status bg-warning"><i class="bi bi-hourglass-split"></i> Entrega Parcial</span>';
                                    } else {
                                        echo '<span class="badge-status bg-secondary">' . htmlspecialchars($statusEntrega) . '</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="13" class="text-center py-5">
                                <div style="color: var(--text-secondary);">
                                    <i class="bi bi-inbox" style="font-size: 42px; opacity: .4;"></i>
                                    <p class="mt-2 mb-0 fw-semibold">No hay registros disponibles</p>
                                    <small>Cuando se ingresen compras aparecerán aquí</small>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tbody>
            </table>
        </div>
    </div>

</div>


<?php include '../includes/footer.php'; ?>


<script>
    $(document).ready(function() {

        var tabla = $('#tablaCompras').DataTable({
            responsive: true,
            order: [[0, 'desc']],
            language: {
                url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
            }
        });

        // FILTRO BOTONES
        $('.filtro').click(function() {

            // Marcar botón activo
            $('.filtro').removeClass('active');
            $(this).addClass('active');

            let estado = $(this).data('status');

            if (estado == "") {
                tabla.column(12).search("").draw();
            } else {
                tabla.column(12).search(estado).draw();
            }
        });

    });
</script>


</body>

</html>