<?php
session_start();

if (!isset($_SESSION['nombre'])) {
    header('Location: /compras/seguimiento/login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| REGLAS DE ATRASO
|--------------------------------------------------------------------------
| LLEGADA A LA ISLA:   fecha_aprobada → fecha_llegada_isla (7 días)
| IMPORTACIÓN:         fecha_solicitud → fecha_cierre_oc (60 días)
| COMPRA LOCAL:        fecha_solicitud → fecha_cierre_oc (7 días)
| SERVICIOS:           fecha_solicitud → fecha_cierre_oc (15 días)
| Si no está cerrada, se usa CURDATE().
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/php/conexion.php';
require_once __DIR__ . '/auth.php';

if (!isset($con) || !($con instanceof mysqli)) {
    die("Error: no se encontró una conexión válida a la base de datos.");
}

$con->set_charset("utf8mb4");

/*
|--------------------------------------------------------------------------
| FILTROS
|--------------------------------------------------------------------------
*/
$fecha_inicio     = $_GET['fecha_inicio'] ?? '';
$fecha_fin        = $_GET['fecha_fin'] ?? '';
$categoria_filtro = $_GET['categoria'] ?? '';
$estado_filtro    = $_GET['estado'] ?? '';

// =========================================================
// VALIDACIÓN DE FECHAS (evita errores SQL por formato inválido)
// =========================================================
if ($fecha_inicio !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_inicio)) {
    $fecha_inicio = '';
}
if ($fecha_fin !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_fin)) {
    $fecha_fin = '';
}

$where  = [];
$params = [];
$types  = "";

if ($fecha_inicio !== '') {
    $where[]  = "fecha_solicitud >= ?";
    $params[] = $fecha_inicio;
    $types   .= "s";
}

if ($fecha_fin !== '') {
    $where[]  = "fecha_solicitud <= ?";
    $params[] = $fecha_fin;
    $types   .= "s";
}

// =========================================================
// FILTRO CATEGORÍA (case-insensitive)
// =========================================================
if ($categoria_filtro !== '') {
    $where[]  = "UPPER(categoria) = UPPER(?)";
    $params[] = $categoria_filtro;
    $types   .= "s";
}

if ($estado_filtro !== '') {
    $where[]  = "status_entrega = ?";
    $params[] = $estado_filtro;
    $types   .= "s";
}

$whereSQL = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

/*
|--------------------------------------------------------------------------
| FUNCIÓN AUXILIAR
|--------------------------------------------------------------------------
*/
function ejecutarConsulta($con, $sql, $types = "", $params = [])
{
    $stmt = $con->prepare($sql);

    if (!$stmt) {
        die("Error preparando consulta: " . $con->error);
    }

    if ($types !== "" && count($params) > 0) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        die("Error ejecutando consulta: " . $stmt->error);
    }

    return $stmt->get_result();
}

/*
|--------------------------------------------------------------------------
| TOTAL / ABIERTAS / CERRADAS
|--------------------------------------------------------------------------
*/
$r = ejecutarConsulta($con, "SELECT COUNT(*) AS total FROM compras_detalle $whereSQL", $types, $params);
$totalOrdenes = (int)($r->fetch_assoc()['total'] ?? 0);

$w = $where;
$w[] = "LOWER(TRIM(status_entrega)) = 'pendiente'";
$r = ejecutarConsulta($con, "SELECT COUNT(*) AS total FROM compras_detalle WHERE " . implode(" AND ", $w), $types, $params);
$ordenesAbiertas = (int)($r->fetch_assoc()['total'] ?? 0);

$w = $where;
$w[] = "LOWER(TRIM(status_entrega)) IN ('entregado','completada','cerrado','finalizado')";
$r = ejecutarConsulta($con, "SELECT COUNT(*) AS total FROM compras_detalle WHERE " . implode(" AND ", $w), $types, $params);
$ordenesCerradas = (int)($r->fetch_assoc()['total'] ?? 0);

$condicionBase = $whereSQL ? substr($whereSQL, 6) : '';

/*
|--------------------------------------------------------------------------
| ATRASOS: ISLA
|--------------------------------------------------------------------------
*/
$c = $condicionBase ? [$condicionBase, "fecha_aprobada IS NOT NULL"] : ["fecha_aprobada IS NOT NULL"];
$c[] = "((fecha_llegada_isla IS NOT NULL AND DATEDIFF(fecha_llegada_isla, fecha_aprobada) > 7) OR (fecha_llegada_isla IS NULL AND DATEDIFF(CURDATE(), fecha_aprobada) > 7))";
$r = ejecutarConsulta($con, "SELECT COUNT(*) AS total FROM compras_detalle WHERE " . implode(" AND ", $c), $types, $params);
$atrasosIsla = (int)($r->fetch_assoc()['total'] ?? 0);

$c = $condicionBase ? [$condicionBase, "fecha_aprobada IS NOT NULL"] : ["fecha_aprobada IS NOT NULL"];
$r = ejecutarConsulta($con, "SELECT COUNT(*) AS total FROM compras_detalle WHERE " . implode(" AND ", $c), $types, $params);
$totalIsla = (int)($r->fetch_assoc()['total'] ?? 0);
$efectividadIsla = $totalIsla > 0 ? round((($totalIsla - $atrasosIsla) / $totalIsla) * 100, 1) : 100;

/*
|--------------------------------------------------------------------------
| ATRASOS: IMPORTACIÓN
|--------------------------------------------------------------------------
*/
$c = $condicionBase ? [$condicionBase] : [];
$c[] = "UPPER(categoria) IN ('IMPORTACION','IMPORTACIÓN')";
$c[] = "fecha_solicitud IS NOT NULL";
$c[] = "((fecha_cierre_oc IS NOT NULL AND DATEDIFF(fecha_cierre_oc, fecha_solicitud) > 60) OR (fecha_cierre_oc IS NULL AND DATEDIFF(CURDATE(), fecha_solicitud) > 60))";
$r = ejecutarConsulta($con, "SELECT COUNT(*) AS total FROM compras_detalle WHERE " . implode(" AND ", $c), $types, $params);
$atrasosImportacion = (int)($r->fetch_assoc()['total'] ?? 0);

$c = $condicionBase ? [$condicionBase, "UPPER(categoria) IN ('IMPORTACION','IMPORTACIÓN')"] : ["UPPER(categoria) IN ('IMPORTACION','IMPORTACIÓN')"];
$r = ejecutarConsulta($con, "SELECT COUNT(*) AS total FROM compras_detalle WHERE " . implode(" AND ", $c), $types, $params);
$totalImportacion = (int)($r->fetch_assoc()['total'] ?? 0);
$efectividadImportacion = $totalImportacion > 0 ? round((($totalImportacion - $atrasosImportacion) / $totalImportacion) * 100, 1) : 100;

/*
|--------------------------------------------------------------------------
| ATRASOS: COMPRA LOCAL
|--------------------------------------------------------------------------
*/
$c = $condicionBase ? [$condicionBase] : [];
$c[] = "UPPER(categoria) = 'COMPRA LOCAL'";
$c[] = "fecha_solicitud IS NOT NULL";
$c[] = "((fecha_cierre_oc IS NOT NULL AND DATEDIFF(fecha_cierre_oc, fecha_solicitud) > 7) OR (fecha_cierre_oc IS NULL AND DATEDIFF(CURDATE(), fecha_solicitud) > 7))";
$r = ejecutarConsulta($con, "SELECT COUNT(*) AS total FROM compras_detalle WHERE " . implode(" AND ", $c), $types, $params);
$atrasosLocal = (int)($r->fetch_assoc()['total'] ?? 0);

$c = $condicionBase ? [$condicionBase, "UPPER(categoria) = 'COMPRA LOCAL'"] : ["UPPER(categoria) = 'COMPRA LOCAL'"];
$r = ejecutarConsulta($con, "SELECT COUNT(*) AS total FROM compras_detalle WHERE " . implode(" AND ", $c), $types, $params);
$totalLocal = (int)($r->fetch_assoc()['total'] ?? 0);
$efectividadLocal = $totalLocal > 0 ? round((($totalLocal - $atrasosLocal) / $totalLocal) * 100, 1) : 100;

/*
|--------------------------------------------------------------------------
| ATRASOS: SERVICIOS
|--------------------------------------------------------------------------
*/
$c = $condicionBase ? [$condicionBase] : [];
$c[] = "categoria IN ('Servicio','Servicios')";
$c[] = "fecha_solicitud IS NOT NULL";
$c[] = "((fecha_cierre_oc IS NOT NULL AND DATEDIFF(fecha_cierre_oc, fecha_solicitud) > 15) OR (fecha_cierre_oc IS NULL AND DATEDIFF(CURDATE(), fecha_solicitud) > 15))";
$r = ejecutarConsulta($con, "SELECT COUNT(*) AS total FROM compras_detalle WHERE " . implode(" AND ", $c), $types, $params);
$atrasosServicio = (int)($r->fetch_assoc()['total'] ?? 0);

$c = $condicionBase ? [$condicionBase, "categoria IN ('Servicio','Servicios')"] : ["categoria IN ('Servicio','Servicios')"];
$r = ejecutarConsulta($con, "SELECT COUNT(*) AS total FROM compras_detalle WHERE " . implode(" AND ", $c), $types, $params);
$totalServicio = (int)($r->fetch_assoc()['total'] ?? 0);
$efectividadServicio = $totalServicio > 0 ? round((($totalServicio - $atrasosServicio) / $totalServicio) * 100, 1) : 100;

/*
|--------------------------------------------------------------------------
| DATOS PARA GRÁFICOS
|--------------------------------------------------------------------------
*/
$labelsAtrasos = ['Llegada a la isla', 'Importación', 'Compra local', 'Servicios'];
$datosAtrasos  = [$atrasosIsla, $atrasosImportacion, $atrasosLocal, $atrasosServicio];
$datosEfectividad = [$efectividadIsla, $efectividadImportacion, $efectividadLocal, $efectividadServicio];

$r = ejecutarConsulta($con, "SELECT categoria, COUNT(*) AS cantidad FROM compras_detalle $whereSQL GROUP BY categoria ORDER BY cantidad DESC", $types, $params);
$categorias = [];
$cantidades = [];
while ($row = $r->fetch_assoc()) {
    $categorias[] = $row['categoria'] ?: 'Sin categoría';
    $cantidades[] = (int)$row['cantidad'];
}

$r = ejecutarConsulta($con, "SELECT COALESCE(NULLIF(status_entrega,''),'Sin estado') AS estado, COUNT(*) AS cantidad FROM compras_detalle $whereSQL GROUP BY estado ORDER BY cantidad DESC", $types, $params);
$estados = [];
$cantidadesEstados = [];
while ($row = $r->fetch_assoc()) {
    $estados[] = $row['estado'];
    $cantidadesEstados[] = (int)$row['cantidad'];
}

$r = ejecutarConsulta($con, "SELECT COALESCE(NULLIF(area,''),'Sin área') AS area, COUNT(*) AS cantidad FROM compras_detalle $whereSQL GROUP BY area ORDER BY cantidad DESC", $types, $params);
$areas = [];
$cantidadesAreas = [];
while ($row = $r->fetch_assoc()) {
    $areas[] = $row['area'];
    $cantidadesAreas[] = (int)$row['cantidad'];
}

// Estados para el filtro
$estadosFiltro = [];
$rEstados = ejecutarConsulta($con, "SELECT DISTINCT status_entrega FROM compras_detalle WHERE status_entrega IS NOT NULL AND status_entrega <> '' ORDER BY status_entrega");
while ($row = $rEstados->fetch_assoc()) {
    $estadosFiltro[] = $row['status_entrega'];
}

// Fallback si no hay estados
if (empty($estadosFiltro)) {
    $estadosFiltro = ['Pendiente', 'Completada', 'Parcial'];
}

include(__DIR__ . '/includes/navbar.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Dashboard de gestión de compras, órdenes de compra (OC) y seguimientos">
    <title>Dashboard de Compras</title>

    <!-- =========================================================
         SCRIPT ANTI-FLASH (aplica el tema antes de renderizar)
    ========================================================== -->
    <script>
    (function() {
        try {
            const saved = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = saved || (prefersDark ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
            document.documentElement.setAttribute('data-bs-theme', theme);
        } catch (e) {}
    })();
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* =========================================================
   VARIABLES DE TEMA
========================================================= */
:root {
    --bg-body: #f4f6fb;
    --bg-card: #ffffff;
    --bg-input: #ffffff;
    --bg-hover: #f0f4ff;
    --bg-progress: #e9ecef;
    --bg-header-from: #0d6efd;
    --bg-header-to: #6610f2;
    --bg-navbar-from: #0d6efd;
    --bg-navbar-to: #6f42c1;

    --text-primary: #212529;
    --text-secondary: #6c757d;

    --border-color: #dee2e6;
    --shadow-sm: 0 10px 25px rgba(0,0,0,.06);
    --shadow-md: 0 10px 30px rgba(0,0,0,.07);
    --shadow-lg: 0 18px 40px rgba(0,0,0,.12);

    --accent-blue: #0d6efd;
    --accent-purple: #6610f2;
    --accent-orange: #fd7e14;
    --accent-green: #198754;
    --accent-red: #dc3545;

    --transition: 250ms cubic-bezier(.4, 0, .2, 1);
    --grid-color: rgba(0,0,0,.05);
}

[data-theme="dark"] {
    --bg-body: #0f1115;
    --bg-card: #171a21;
    --bg-input: #1e222b;
    --bg-hover: #232834;
    --bg-progress: #2a2f3a;
    --bg-header-from: #1a2a4a;
    --bg-header-to: #2a1a4a;
    --bg-navbar-from: #1a2a4a;
    --bg-navbar-to: #2a1a4a;

    --text-primary: #e8eaed;
    --text-secondary: #9aa0a8;

    --border-color: #2a2f3a;
    --shadow-sm: 0 10px 25px rgba(0,0,0,.4);
    --shadow-md: 0 10px 30px rgba(0,0,0,.5);
    --shadow-lg: 0 18px 40px rgba(0,0,0,.6);

    --grid-color: rgba(255,255,255,.08);
}

/* =========================================================
   TRANSICIONES SELECTIVAS (no usar * global)
========================================================= */
body,
.dashboard-header,
.card,
.summary-card,
.delay-card,
.chart-card,
.filter-card,
.form-control,
.form-select,
.navbar-custom,
.navbar-custom .dropdown-menu,
.badge,
.btn,
.table-modern,
.progress {
    transition: background-color var(--transition),
                color var(--transition),
                border-color var(--transition);
}

body {
    padding-top: 70px;
    background: var(--bg-body);
    color: var(--text-primary);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

/* =========================================================
   HEADER
========================================================= */
.dashboard-header {
    background: linear-gradient(135deg, var(--bg-header-from), var(--bg-header-to));
    color: white;
    border-radius: 0 0 30px 30px;
    padding: 45px 50px 55px;
    margin: 0 0 30px 0;
    box-shadow: 0 15px 35px rgba(13,110,253,.25);
    position: relative;
    z-index: 1;
}
.dashboard-header h1 { font-weight: 800; letter-spacing: -.5px; }
.dashboard-header p { opacity: .9; }

/* =========================================================
   CARDS
========================================================= */
.filter-card,
.summary-card,
.delay-card,
.chart-card {
    border: 0;
    background: var(--bg-card);
    color: var(--text-primary);
    box-shadow: var(--shadow-md);
}
.filter-card { border-radius: 20px; }
.summary-card { border-radius: 22px; overflow: hidden; position: relative; transition: transform var(--transition), box-shadow var(--transition); }
.summary-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }
.summary-card::before { content: ""; position: absolute; top: 0; left: 0; right: 0; height: 5px; background: var(--accent-blue); }
.delay-card { border-radius: 24px; height: 100%; transition: transform var(--transition), box-shadow var(--transition); overflow: hidden; }
.delay-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-lg); }
.chart-card { border-radius: 22px; }

/* =========================================================
   ICONOS Y NÚMEROS
========================================================= */
.summary-icon, .delay-icon {
    width: 60px; height: 60px;
    border-radius: 18px;
    display: flex; align-items: center; justify-content: center;
    font-size: 28px;
}
.delay-icon { width: 62px; height: 62px; font-size: 30px; }
.summary-number { font-size: 2.2rem; font-weight: 800; }
.delay-number { font-size: 2.7rem; font-weight: 800; line-height: 1; }
.delay-limit { font-size: .85rem; color: var(--text-secondary); }
.effectiveness { font-size: 1.5rem; font-weight: 800; }

/* =========================================================
   PROGRESO
========================================================= */
.progress { height: 9px; border-radius: 20px; background-color: var(--bg-progress); }
.progress-bar { border-radius: 20px; transition: width 1.2s cubic-bezier(.4, 0, .2, 1); }

/* =========================================================
   TÍTULOS Y BADGES
========================================================= */
.chart-title { font-weight: 700; color: var(--text-primary); }
.section-title { font-weight: 800; color: var(--text-primary); }
.badge-soft { border-radius: 50px; padding: 7px 12px; font-weight: 600; }
.soft-blue   { background: rgba(13,110,253,.12); color: var(--accent-blue); }
.soft-orange { background: rgba(253,126,20,.12); color: var(--accent-orange); }
.soft-green  { background: rgba(25,135,84,.12); color: var(--accent-green); }
.soft-red    { background: rgba(220,53,69,.12); color: var(--accent-red); }
.soft-purple { background: rgba(102,16,242,.12); color: var(--accent-purple); }

/* =========================================================
   FORMULARIOS
========================================================= */
.btn-filter { border-radius: 12px; font-weight: 600; padding: 11px 20px; }
.form-control, .form-select {
    border-radius: 12px;
    padding: 11px 13px;
    border-color: var(--border-color);
    background: var(--bg-input);
    color: var(--text-primary);
}
.form-control:focus, .form-select:focus {
    box-shadow: 0 0 0 .2rem rgba(13,110,253,.12);
    border-color: #86b7fe;
    background: var(--bg-input);
    color: var(--text-primary);
}

/* =========================================================
   NAVBAR
========================================================= */
.navbar-custom {
    min-height: 70px;
    background: linear-gradient(135deg, var(--bg-navbar-from), var(--bg-navbar-to));
    box-shadow: 0 4px 18px rgba(0,0,0,.18);
}
.navbar-brand { display: flex; align-items: center; gap: 9px; font-size: 1.1rem; font-weight: 600; color: white !important; }
.logo-icon {
    width: 38px; height: 38px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 10px;
    background: rgba(255,255,255,.15);
    font-size: 1.15rem;
}
.navbar-custom .nav-link {
    display: flex; align-items: center; gap: 6px;
    color: rgba(255,255,255,.92);
    font-size: .84rem; font-weight: 500;
    padding: 9px 12px; margin: 0 2px;
    border-radius: 9px;
    transition: all .2s ease;
}
.navbar-custom .nav-link:hover {
    color: white;
    background: rgba(255,255,255,.12);
    transform: translateY(-1px);
}
.navbar-custom .nav-link.active {
    color: white;
    background: rgba(255,255,255,.18);
    font-weight: 600;
}
.navbar-custom .nav-link i { font-size: 1rem; }

.navbar-custom .dropdown-menu {
    border: none;
    border-radius: 12px;
    padding: 8px;
    margin-top: 10px;
    min-width: 225px;
    box-shadow: 0 10px 35px rgba(0,0,0,.18);
    background: var(--bg-card);
}
.navbar-custom .dropdown-item {
    display: flex; align-items: center; gap: 9px;
    padding: 9px 11px; border-radius: 8px;
    font-size: .83rem; font-weight: 500;
    color: var(--text-primary);
    transition: all .2s ease;
}
.navbar-custom .dropdown-item:hover {
    background: var(--bg-hover);
    transform: translateX(3px);
    color: var(--text-primary);
}
.navbar-custom .dropdown-item i { width: 20px; text-align: center; }

.alertas-link { position: relative; }
.alerta-indicador {
    position: absolute;
    top: 5px; right: 5px;
    width: 7px; height: 7px;
    background: #ff3b30;
    border-radius: 50%;
    border: 2px solid #0d6efd;
    animation: pulse-alert 2s infinite;
}
@keyframes pulse-alert {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.4); opacity: .7; }
}

.theme-button {
    display: flex; align-items: center; gap: 6px;
    color: white;
    border: 1px solid rgba(255,255,255,.25);
    background: rgba(255,255,255,.10);
    border-radius: 9px;
    font-size: .78rem;
    padding: 7px 10px;
    transition: all .2s ease;
}
.theme-button:hover {
    color: white;
    background: rgba(255,255,255,.18);
    transform: rotate(-8deg);
}

/* =========================================================
   ANIMACIONES DE ENTRADA
========================================================= */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
.animate-in { animation: fadeInUp .6s cubic-bezier(.4, 0, .2, 1) backwards; }
.row > [class*="col-"]:nth-child(1) .animate-in { animation-delay: .05s; }
.row > [class*="col-"]:nth-child(2) .animate-in { animation-delay: .15s; }
.row > [class*="col-"]:nth-child(3) .animate-in { animation-delay: .25s; }
.row > [class*="col-"]:nth-child(4) .animate-in { animation-delay: .35s; }

/* =========================================================
   AJUSTES DE MODO OSCURO
========================================================= */
[data-theme="dark"] .text-muted {
    color: #9aa0a8 !important;
}
[data-theme="dark"] .badge.bg-white {
    background-color: #1e222b !important;
    color: #e8eaed !important;
}
[data-theme="dark"] .btn-outline-secondary {
    color: #9aa0a8;
    border-color: #2a2f3a;
}
[data-theme="dark"] .btn-outline-secondary:hover {
    background: #232834;
    color: #e8eaed;
    border-color: #2a2f3a;
}
[data-theme="dark"] .bg-light {
    background-color: #1e222b !important;
    color: #e8eaed !important;
}

/* =========================================================
   RESPONSIVE
========================================================= */
@media (max-width: 768px) {
    .dashboard-header { padding: 35px 20px; border-radius: 0 0 22px 22px; }
    .dashboard-header h1 { font-size: 2rem; }
    .delay-number { font-size: 2.2rem; }
}
@media (max-width: 991px) {
    .navbar-custom .navbar-nav { padding-top: 10px; padding-bottom: 10px; }
    .navbar-custom .nav-link { margin: 2px 0; }
    .navbar-custom .dropdown-menu { margin-top: 2px; box-shadow: none; }
}
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: .01ms !important;
        transition-duration: .01ms !important;
    }
}
</style>
</head>

<body>

<!-- =========================================================
     NAVBAR
========================================================= -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top">
    <div class="container-fluid px-4">

        <a class="navbar-brand" href="/compras/seguimiento/index.php">
            <span class="logo-icon"><i class="bi bi-box-seam-fill"></i></span>
            <span>Registro de Compras</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuNav"
                aria-controls="menuNav" aria-expanded="false" aria-label="Abrir menú">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menuNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">

                <li class="nav-item">
                    <a class="nav-link active" href="/compras/seguimiento/index.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="ordenesDropdown" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-file-earmark-text-fill"></i> Órdenes
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="ordenesDropdown">
                        <li><a class="dropdown-item" href="/compras/seguimiento/view/formulario.php">
                            <i class="bi bi-plus-circle text-primary"></i> Ingresar OC</a></li>
                        <li><a class="dropdown-item" href="/compras/seguimiento/view/editar_compra.php">
                            <i class="bi bi-pencil-square text-warning"></i> Completar OC</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/compras/seguimiento/view/bdd_compras.php">
                            <i class="bi bi-database-fill-check text-success"></i> Base de Datos</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="listadosDropdown" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-list-check"></i> Listados
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="listadosDropdown">
                        <li><a class="dropdown-item" href="/compras/seguimiento/view/area_solicitante.php">
                            <i class="bi bi-people-fill text-primary"></i> Área / Solicitante</a></li>
                        <li><a class="dropdown-item" href="/compras/seguimiento/view/proveedores.php">
                            <i class="bi bi-truck text-success"></i> Proveedores</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle alertas-link" href="#" id="alertasDropdown" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-bell-fill"></i> Alertas
                        <span class="alerta-indicador"></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="alertasDropdown">
                        <li><a class="dropdown-item" href="/compras/seguimiento/view/alertas.php">
                            <i class="bi bi-exclamation-triangle-fill text-danger"></i> Ver alertas</a></li>
                        <li><a class="dropdown-item" href="/compras/seguimiento/view/ver_listado_alertas.php">
                            <i class="bi bi-clock-history text-warning"></i> Historial de alertas</a></li>
                        <li><a class="dropdown-item" href="/compras/seguimiento/view/ver_listado_alertas.php">
                            <i class="bi bi-diagram-3-fill text-primary"></i> Trazabilidad</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="configuracionDropdown" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-gear-fill"></i> Configuración
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="configuracionDropdown">
                        <li><a class="dropdown-item" href="/compras/seguimiento/view/create_user.php">
                            <i class="bi bi-person-plus-fill text-primary"></i> Crear Usuarios</a></li>
                        <li><a class="dropdown-item" href="/compras/seguimiento/view/control_user.php">
                            <i class="bi bi-person-fill-gear text-warning"></i> Control de Usuarios</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="/compras/seguimiento/login.php">
                            <i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
                    </ul>
                </li>

                <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                    <button id="toggleTheme" type="button" class="btn theme-button">
                        <i id="themeIcon" class="bi bi-moon-stars-fill"></i>
                        <span id="themeText">Oscuro</span>
                    </button>
                </li>

            </ul>
        </div>
    </div>
</nav>

<!-- =========================================================
     ENCABEZADO
========================================================= -->
<div class="dashboard-header">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="bg-white bg-opacity-25 rounded-1 p-2">
                        <i class="bi bi-speedometer2 fs-2"></i>
                    </div>
                    <div>
                        <h1 class="mb-0">Dashboard de Compras</h1>
                        <p class="mb-0 mt-1">Control de tiempos y atrasos</p>
                    </div>
                </div>
            </div>
            <div>
                <span class="badge bg-white text-primary px-3 py-2 rounded-pill">
                    <i class="bi bi-clock-history me-1"></i>
                    <span id="lastUpdate">Actualizado ahora</span>
                </span>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">

    <!-- =========================================================
         FILTROS
    ========================================================= -->
    <div class="card filter-card mb-4 animate-in">
        <div class="card-body p-4">
            <div class="d-flex align-items-center mb-4">
                <div class="summary-icon soft-blue me-3"><i class="bi bi-funnel"></i></div>
                <div>
                    <h5 class="mb-1 fw-bold">Filtros del dashboard</h5>
                    <small class="text-muted">Filtra la información que deseas analizar</small>
                </div>
            </div>

            <form method="GET" id="filtroForm">
                <div class="row g-3 align-items-end">

                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="fecha_inicio" class="form-label fw-semibold">
                            <i class="bi bi-calendar-event me-1"></i> Desde
                        </label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio"
                               value="<?= htmlspecialchars($fecha_inicio) ?>">
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="fecha_fin" class="form-label fw-semibold">
                            <i class="bi bi-calendar-check me-1"></i> Hasta
                        </label>
                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin"
                               value="<?= htmlspecialchars($fecha_fin) ?>">
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="categoria" class="form-label fw-semibold">
                            <i class="bi bi-tags me-1"></i> Categoría
                        </label>
                        <select class="form-select" id="categoria" name="categoria">
                            <option value="">Todas las categorías</option>
                            <option value="Importación"  <?= $categoria_filtro === 'Importación'  ? 'selected' : '' ?>>Importación</option>
                            <option value="Compra local" <?= $categoria_filtro === 'Compra local' ? 'selected' : '' ?>>Compra local</option>
                            <option value="Servicio"     <?= $categoria_filtro === 'Servicio'     ? 'selected' : '' ?>>Servicio</option>
                            <option value="Servicios"    <?= $categoria_filtro === 'Servicios'    ? 'selected' : '' ?>>Servicios</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6 col-lg-2">
                        <label for="estado" class="form-label fw-semibold">
                            <i class="bi bi-clipboard-check me-1"></i> Estado
                        </label>
                        <select class="form-select" id="estado" name="estado">
                            <option value="">Todos</option>
                            <?php foreach ($estadosFiltro as $est): ?>
                                <option value="<?= htmlspecialchars($est) ?>"
                                    <?= $estado_filtro === $est ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($est) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-lg-1 d-grid">
                        <button type="submit" class="btn btn-primary btn-filter" id="btnFiltrar">
                            <i class="bi bi-search me-1"></i> Filtrar
                        </button>
                    </div>
                </div>

                <div class="text-end mt-3">
                    <a href="<?= htmlspecialchars(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) ?>"
                       class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="bi bi-x-circle me-1"></i> Limpiar filtros
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- =========================================================
         RESUMEN GENERAL
    ========================================================= -->
    <div class="d-flex align-items-center mb-3">
        <div>
            <h3 class="section-title mb-1">Resumen general</h3>
            <p class="text-muted mb-0">Estado actual de las órdenes</p>
        </div>
    </div>

    <div class="row g-4 mb-5">

        <div class="col-12 col-md-4">
            <div class="card summary-card h-100 animate-in">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="badge-soft soft-blue">Total</span>
                            <div class="summary-number text-primary mt-3"
                                 data-count="<?= $totalOrdenes ?>"
                                 data-key="totalOrdenes"
                                 data-format="int">0</div>
                            <div class="text-muted">Órdenes registradas</div>
                        </div>
                        <div class="summary-icon soft-blue"><i class="bi bi-stack"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card summary-card h-100 animate-in">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="badge-soft soft-orange">En proceso</span>
                            <div class="summary-number text-warning mt-3"
                                 data-count="<?= $ordenesAbiertas ?>"
                                 data-key="ordenesAbiertas"
                                 data-format="int">0</div>
                            <div class="text-muted">Órdenes abiertas</div>
                        </div>
                        <div class="summary-icon soft-orange"><i class="bi bi-hourglass-split"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card summary-card h-100 animate-in">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="badge-soft soft-green">Completadas</span>
                            <div class="summary-number text-success mt-3"
                                 data-count="<?= $ordenesCerradas ?>"
                                 data-key="ordenesCerradas"
                                 data-format="int">0</div>
                            <div class="text-muted">Órdenes cerradas</div>
                        </div>
                        <div class="summary-icon soft-green"><i class="bi bi-check2-circle"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =========================================================
         ANÁLISIS DE ATRASOS
    ========================================================= -->
    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h3 class="section-title mb-1">Análisis de atrasos</h3>
            <p class="text-muted mb-0">Órdenes que superan el tiempo permitido</p>
        </div>
        <span class="badge bg-light text-dark border rounded-pill">
            <i class="bi bi-calendar3 me-1"></i> Desde fecha de solicitud
        </span>
    </div>

    <div class="row g-4 mb-5">

        <!-- ISLA -->
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card delay-card animate-in">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="delay-icon soft-blue"><i class="bi bi-geo-alt"></i></div>
                        <?php if ($atrasosIsla > 0): ?>
                            <span class="badge rounded-pill text-bg-danger">
                                <?= $atrasosIsla ?> atraso<?= $atrasosIsla != 1 ? 's' : '' ?>
                            </span>
                        <?php else: ?>
                            <span class="badge rounded-pill text-bg-success">Al día</span>
                        <?php endif; ?>
                    </div>
                    <h5 class="fw-bold mt-4 mb-1">Llegada a la isla</h5>
                    <div class="delay-limit mb-3">Límite: 7 días</div>
                    <div class="delay-number text-primary"
                         data-count="<?= $atrasosIsla ?>"
                         data-key="atrasosIsla"
                         data-format="int">0</div>
                    <div class="text-muted mb-3">órdenes atrasadas</div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="fw-semibold">Efectividad</small>
                        <span class="effectiveness text-primary"
                              data-count="<?= $efectividadIsla ?>"
                              data-format="decimal"
                              data-suffix="%">0%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-primary"
                             style="width: <?= min(100, max(0, $efectividadIsla)) ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- IMPORTACIÓN -->
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card delay-card animate-in">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="delay-icon soft-purple"><i class="bi bi-airplane"></i></div>
                        <?php if ($atrasosImportacion > 0): ?>
                            <span class="badge rounded-pill text-bg-danger">
                                <?= $atrasosImportacion ?> atraso<?= $atrasosImportacion != 1 ? 's' : '' ?>
                            </span>
                        <?php else: ?>
                            <span class="badge rounded-pill text-bg-success">Al día</span>
                        <?php endif; ?>
                    </div>
                    <h5 class="fw-bold mt-4 mb-1">Importación</h5>
                    <div class="delay-limit mb-3">Límite: 60 días</div>
                    <div class="delay-number" style="color:#6610f2;"
                         data-count="<?= $atrasosImportacion ?>"
                         data-key="atrasosImportacion"
                         data-format="int">0</div>
                    <div class="text-muted mb-3">órdenes atrasadas</div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="fw-semibold">Efectividad</small>
                        <span class="effectiveness" style="color:#6610f2;"
                              data-count="<?= $efectividadImportacion ?>"
                              data-format="decimal"
                              data-suffix="%">0%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" style="width: <?= min(100, max(0, $efectividadImportacion)) ?>%; background:#6610f2;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- LOCAL -->
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card delay-card animate-in">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="delay-icon soft-orange"><i class="bi bi-shop"></i></div>
                        <?php if ($atrasosLocal > 0): ?>
                            <span class="badge rounded-pill text-bg-danger">
                                <?= $atrasosLocal ?> atraso<?= $atrasosLocal != 1 ? 's' : '' ?>
                            </span>
                        <?php else: ?>
                            <span class="badge rounded-pill text-bg-success">Al día</span>
                        <?php endif; ?>
                    </div>
                    <h5 class="fw-bold mt-4 mb-1">Compra local</h5>
                    <div class="delay-limit mb-3">Límite: 7 días</div>
                    <div class="delay-number" style="color:#fd7e14;"
                         data-count="<?= $atrasosLocal ?>"
                         data-key="atrasosLocal"
                         data-format="int">0</div>
                    <div class="text-muted mb-3">órdenes atrasadas</div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="fw-semibold">Efectividad</small>
                        <span class="effectiveness" style="color:#fd7e14;"
                              data-count="<?= $efectividadLocal ?>"
                              data-format="decimal"
                              data-suffix="%">0%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" style="width: <?= min(100, max(0, $efectividadLocal)) ?>%; background:#fd7e14;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SERVICIOS -->
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card delay-card animate-in">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="delay-icon soft-green"><i class="bi bi-tools"></i></div>
                        <?php if ($atrasosServicio > 0): ?>
                            <span class="badge rounded-pill text-bg-danger">
                                <?= $atrasosServicio ?> atraso<?= $atrasosServicio != 1 ? 's' : '' ?>
                            </span>
                        <?php else: ?>
                            <span class="badge rounded-pill text-bg-success">Al día</span>
                        <?php endif; ?>
                    </div>
                    <h5 class="fw-bold mt-4 mb-1">Servicios</h5>
                    <div class="delay-limit mb-3">Límite: 15 días</div>
                    <div class="delay-number text-success"
                         data-count="<?= $atrasosServicio ?>"
                         data-key="atrasosServicio"
                         data-format="int">0</div>
                    <div class="text-muted mb-3">órdenes atrasadas</div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="fw-semibold">Efectividad</small>
                        <span class="effectiveness text-success"
                              data-count="<?= $efectividadServicio ?>"
                              data-format="decimal"
                              data-suffix="%">0%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-success"
                             style="width: <?= min(100, max(0, $efectividadServicio)) ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =========================================================
         GRÁFICOS DE ATRASOS
    ========================================================= -->
    <div class="row g-4 mb-5">

        <div class="col-12 col-lg-7">
            <div class="card chart-card h-100 animate-in">
                <div class="card-body p-4">
                    <div class="mb-4">
                        <h5 class="chart-title mb-1">
                            <i class="bi bi-bar-chart-fill text-danger me-2"></i>
                            Órdenes atrasadas por proceso
                        </h5>
                        <p class="text-muted mb-0">Cantidad de órdenes que superan el límite establecido</p>
                    </div>
                    <div style="height: 350px;">
                        <canvas id="graficoAtrasos"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card chart-card h-100 animate-in">
                <div class="card-body p-4">
                    <div class="mb-4">
                        <h5 class="chart-title mb-1">
                            <i class="bi bi-speedometer2 text-success me-2"></i>
                            Efectividad por proceso
                        </h5>
                        <p class="text-muted mb-0">Porcentaje de órdenes dentro del tiempo esperado</p>
                    </div>
                    <div style="height: 350px;">
                        <canvas id="graficoEfectividad"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =========================================================
         CATEGORÍAS Y ESTADOS
    ========================================================= -->
    <div class="row g-4 mb-5">

        <div class="col-12 col-lg-8">
            <div class="card chart-card h-100 animate-in">
                <div class="card-body p-4">
                    <div class="mb-4">
                        <h5 class="chart-title mb-1">
                            <i class="bi bi-pie-chart-fill text-primary me-2"></i>
                            Órdenes por categoría
                        </h5>
                        <p class="text-muted mb-0">Distribución de las órdenes registradas</p>
                    </div>
                    <div style="height: 350px;">
                        <canvas id="graficoCategorias"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card chart-card h-100 animate-in">
                <div class="card-body p-4">
                    <div class="mb-4">
                        <h5 class="chart-title mb-1">
                            <i class="bi bi-check2-square text-success me-2"></i>
                            Estados
                        </h5>
                        <p class="text-muted mb-0">Situación de las órdenes</p>
                    </div>
                    <div style="height: 350px;">
                        <canvas id="graficoEstados"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =========================================================
         ÁREAS
    ========================================================= -->
    <div class="card chart-card mb-5 animate-in">
        <div class="card-body p-4">
            <div class="mb-4">
                <h5 class="chart-title mb-1">
                    <i class="bi bi-diagram-3-fill text-primary me-2"></i>
                    Órdenes por área
                </h5>
                <p class="text-muted mb-0">Distribución de solicitudes según el área solicitante</p>
            </div>
            <div style="height: 380px;">
                <canvas id="graficoAreas"></canvas>
            </div>
        </div>
    </div>

    <!-- =========================================================
         PIE DE INFORMACIÓN
    ========================================================= -->
    <div class="text-center text-muted py-3">
        <small>
            <i class="bi bi-info-circle me-1"></i>
            Los atrasos se calculan desde <strong>fecha_solicitud</strong>.<br>
            Isla: 7 días · Compra local: 7 días · Servicios: 15 días · Importación: 60 días.
        </small>
    </div>

</div>

<!-- =========================================================
     SCRIPTS
========================================================= -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* =========================================================
   DATOS PHP → JS
========================================================= */
const labelsAtrasos     = <?= json_encode($labelsAtrasos, JSON_UNESCAPED_UNICODE) ?>;
const datosAtrasos      = <?= json_encode($datosAtrasos) ?>;
const datosEfectividad  = <?= json_encode($datosEfectividad) ?>;
const categorias        = <?= json_encode($categorias, JSON_UNESCAPED_UNICODE) ?>;
const cantidades        = <?= json_encode($cantidades) ?>;
const estados           = <?= json_encode($estados, JSON_UNESCAPED_UNICODE) ?>;
const cantidadesEstados = <?= json_encode($cantidadesEstados) ?>;
const areas             = <?= json_encode($areas, JSON_UNESCAPED_UNICODE) ?>;
const cantidadesAreas   = <?= json_encode($cantidadesAreas) ?>;

/* =========================================================
   HELPERS
========================================================= */
function getCSSVar(name) {
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
}
function createGradient(ctx, colorStart, colorEnd) {
    const g = ctx.createLinearGradient(0, 0, 0, 400);
    g.addColorStop(0, colorStart);
    g.addColorStop(1, colorEnd);
    return g;
}
function getGridColor() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    return isDark ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.05)';
}
function getTickColor() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    return isDark ? '#9aa0a8' : '#6c757d';
}
function getCardColor() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    return isDark ? '#171a21' : '#ffffff';
}
function getTooltipStyle() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    return {
        backgroundColor: isDark ? 'rgba(30,34,43,.98)' : 'rgba(15,17,21,.95)',
        titleColor: '#fff',
        bodyColor: '#e8eaed',
        padding: 12,
        cornerRadius: 10,
        displayColors: false,
        titleFont: { family: 'Inter', weight: '700', size: 13 },
        bodyFont: { family: 'Inter', size: 12 }
    };
}

window.chartInstances = [];

/* =========================================================
   GRÁFICO ATRASOS
========================================================= */
const ctxAtrasos = document.getElementById('graficoAtrasos').getContext('2d');
const chartAtrasos = new Chart(ctxAtrasos, {
    type: 'bar',
    data: {
        labels: labelsAtrasos,
        datasets: [{
            label: 'Órdenes atrasadas',
            data: datosAtrasos,
            backgroundColor: [
                createGradient(ctxAtrasos, '#0d6efd', 'rgba(13,110,253,.2)'),
                createGradient(ctxAtrasos, '#6610f2', 'rgba(102,16,242,.2)'),
                createGradient(ctxAtrasos, '#fd7e14', 'rgba(253,126,20,.2)'),
                createGradient(ctxAtrasos, '#198754', 'rgba(25,135,84,.2)')
            ],
            borderRadius: 12,
            borderSkipped: false,
            maxBarThickness: 70
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 1500, easing: 'easeOutQuart' },
        plugins: {
            legend: { display: false },
            tooltip: {
                ...getTooltipStyle(),
                callbacks: {
                    label: (ctx) => ` ${ctx.parsed.y} orden${ctx.parsed.y !== 1 ? 'es' : ''} atrasada${ctx.parsed.y !== 1 ? 's' : ''}`
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { precision: 0, color: getTickColor(), font: { family: 'Inter' } },
                grid: { color: getGridColor(), drawBorder: false }
            },
            x: {
                grid: { display: false },
                ticks: { color: getTickColor(), font: { family: 'Inter', weight: '600' } }
            }
        }
    }
});
window.chartInstances.push(chartAtrasos);

/* =========================================================
   GRÁFICO EFECTIVIDAD
========================================================= */
const chartEfectividad = new Chart(document.getElementById('graficoEfectividad'), {
    type: 'doughnut',
    data: {
        labels: labelsAtrasos,
        datasets: [{
            label: 'Efectividad',
            data: datosEfectividad,
            backgroundColor: ['#0d6efd', '#6610f2', '#fd7e14', '#198754'],
            borderWidth: 3,
            borderColor: getCardColor(),
            hoverOffset: 12
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        animation: { duration: 1500, easing: 'easeOutQuart' },
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    usePointStyle: true,
                    color: getTickColor(),
                    font: { family: 'Inter', size: 12 }
                }
            },
            tooltip: {
                ...getTooltipStyle(),
                displayColors: true,
                callbacks: {
                    label: (ctx) => ` ${ctx.label}: ${ctx.parsed}% de efectividad`
                }
            }
        }
    }
});
window.chartInstances.push(chartEfectividad);

/* =========================================================
   GRÁFICO CATEGORÍAS
========================================================= */
const ctxCat = document.getElementById('graficoCategorias').getContext('2d');
const chartCategorias = new Chart(ctxCat, {
    type: 'bar',
    data: {
        labels: categorias,
        datasets: [{
            label: 'Órdenes',
            data: cantidades,
            backgroundColor: createGradient(ctxCat, '#0d6efd', 'rgba(13,110,253,.25)'),
            borderColor: '#0d6efd',
            borderWidth: 0,
            borderRadius: 10,
            maxBarThickness: 60
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 1500, easing: 'easeOutQuart' },
        plugins: {
            legend: { display: false },
            tooltip: {
                ...getTooltipStyle(),
                callbacks: {
                    label: (ctx) => ` ${ctx.parsed.y} orden${ctx.parsed.y !== 1 ? 'es' : ''}`
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { precision: 0, color: getTickColor(), font: { family: 'Inter' } },
                grid: { color: getGridColor(), drawBorder: false }
            },
            x: {
                grid: { display: false },
                ticks: { color: getTickColor(), font: { family: 'Inter', weight: '600' } }
            }
        }
    }
});
window.chartInstances.push(chartCategorias);

/* =========================================================
   GRÁFICO ESTADOS
========================================================= */
const chartEstados = new Chart(document.getElementById('graficoEstados'), {
    type: 'doughnut',
    data: {
        labels: estados,
        datasets: [{
            data: cantidadesEstados,
            backgroundColor: ['#198754', '#ffc107', '#dc3545', '#0d6efd', '#6610f2', '#6c757d'],
            borderColor: getCardColor(),
            borderWidth: 3,
            hoverOffset: 10
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '58%',
        animation: { duration: 1500, easing: 'easeOutQuart' },
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 12,
                    usePointStyle: true,
                    color: getTickColor(),
                    font: { family: 'Inter', size: 12 }
                }
            },
            tooltip: {
                ...getTooltipStyle(),
                displayColors: true,
                callbacks: {
                    label: (ctx) => ` ${ctx.label}: ${ctx.parsed} órdenes`
                }
            }
        }
    }
});
window.chartInstances.push(chartEstados);

/* =========================================================
   GRÁFICO ÁREAS
========================================================= */
const ctxAreas = document.getElementById('graficoAreas').getContext('2d');
const chartAreas = new Chart(ctxAreas, {
    type: 'bar',
    data: {
        labels: areas,
        datasets: [{
            label: 'Órdenes',
            data: cantidadesAreas,
            backgroundColor: createGradient(ctxAreas, '#6610f2', 'rgba(102,16,242,.25)'),
            borderColor: '#6610f2',
            borderWidth: 0,
            borderRadius: 10,
            maxBarThickness: 32
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        animation: { duration: 1500, easing: 'easeOutQuart' },
        plugins: {
            legend: { display: false },
            tooltip: {
                ...getTooltipStyle(),
                callbacks: {
                    label: (ctx) => ` ${ctx.parsed.x} orden${ctx.parsed.x !== 1 ? 'es' : ''}`
                }
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                ticks: { precision: 0, color: getTickColor(), font: { family: 'Inter' } },
                grid: { color: getGridColor(), drawBorder: false }
            },
            y: {
                grid: { display: false },
                ticks: { color: getTickColor(), font: { family: 'Inter', weight: '600' } }
            }
        }
    }
});
window.chartInstances.push(chartAreas);

/* =========================================================
   TOGGLE DE TEMA CLARO/OSCURO (CORREGIDO)
========================================================= */
(function() {
    const html  = document.documentElement;
    const btn   = document.getElementById('toggleTheme');
    const icon  = document.getElementById('themeIcon');
    const text  = document.getElementById('themeText');

    const saved      = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const initial    = saved || (prefersDark ? 'dark' : 'light');

    applyTheme(initial);

    if (btn) {
        btn.addEventListener('click', () => {
            const current = html.getAttribute('data-theme') || 'light';
            applyTheme(current === 'dark' ? 'light' : 'dark');
        });
    }

    function applyTheme(theme) {
        // Aplicar a <html>
        html.setAttribute('data-theme', theme);
        // Sincronizar con Bootstrap 5.3+
        html.setAttribute('data-bs-theme', theme);
        localStorage.setItem('theme', theme);

        // Actualizar icono y texto
        if (icon && text) {
            if (theme === 'dark') {
                icon.className = 'bi bi-sun-fill';
                text.textContent = 'Claro';
            } else {
                icon.className = 'bi bi-moon-stars-fill';
                text.textContent = 'Oscuro';
            }
        }

        // Colores calculados (sin depender del repaint del CSS)
        const isDark = theme === 'dark';
        const gridColor   = isDark ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.05)';
        const tickColor   = isDark ? '#9aa0a8' : '#6c757d';
        const cardColor   = isDark ? '#171a21' : '#ffffff';

        // Actualizar gráficos
        if (window.chartInstances) {
            window.chartInstances.forEach(chart => {
                if (chart.options.scales) {
                    Object.values(chart.options.scales).forEach(scale => {
                        if (scale.grid && scale.grid.color !== undefined) scale.grid.color = gridColor;
                        if (scale.ticks) scale.ticks.color = tickColor;
                    });
                }
                if (chart.options.plugins?.legend?.labels) {
                    chart.options.plugins.legend.labels.color = tickColor;
                }
                // Actualizar borderColor de donas
                if (chart.config.type === 'doughnut' || chart.config.type === 'pie') {
                    chart.data.datasets.forEach(ds => { ds.borderColor = cardColor; });
                }
                // Actualizar tooltip
                if (chart.options.plugins?.tooltip) {
                    chart.options.plugins.tooltip.backgroundColor = isDark
                        ? 'rgba(30,34,43,.98)'
                        : 'rgba(15,17,21,.95)';
                }
                chart.update('none');
            });
        }
    }
})();

/* =========================================================
   CONTADORES ANIMADOS
========================================================= */
(function() {
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function easeOutExpo(t) { return t === 1 ? 1 : 1 - Math.pow(2, -10 * t); }

    function formatNumber(value, format, decimals = 1) {
        if (format === 'decimal') return value.toFixed(decimals);
        return Math.floor(value).toLocaleString('es-ES');
    }

    function animateCounter(el) {
        const target = parseFloat(el.dataset.count) || 0;
        const format = el.dataset.format || 'int';
        const suffix = el.dataset.suffix || '';
        const duration = 1200;

        if (prefersReduced) {
            el.textContent = formatNumber(target, format) + suffix;
            return;
        }

        const start = performance.now();
        function tick(now) {
            const elapsed = now - start;
            const progress = Math.min(elapsed / duration, 1);
            const eased = easeOutExpo(progress);
            const current = target * eased;
            el.textContent = formatNumber(current, format) + suffix;
            if (progress < 1) requestAnimationFrame(tick);
            else el.textContent = formatNumber(target, format) + suffix;
        }
        requestAnimationFrame(tick);
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.3 });

    document.querySelectorAll('[data-count]').forEach(el => observer.observe(el));
})();

/* =========================================================
   REFRESCO AUTOMÁTICO AJAX (con manejo seguro de NaN)
========================================================= */
(function() {
    const INTERVAL = 60000;
    let timer = null;

    function getFilters() {
        const params = new URLSearchParams();
        const fi  = document.getElementById('fecha_inicio')?.value;
        const ff  = document.getElementById('fecha_fin')?.value;
        const cat = document.getElementById('categoria')?.value;
        const est = document.getElementById('estado')?.value;
        if (fi)  params.append('fecha_inicio', fi);
        if (ff)  params.append('fecha_fin', ff);
        if (cat) params.append('categoria', cat);
        if (est) params.append('estado', est);
        return params.toString();
    }

    async function refresh() {
        try {
            const res = await fetch('api/dashboard_refresh.php?' + getFilters(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();
            if (!data.success) return;

            // Actualizar KPIs con manejo seguro de NaN
            Object.entries(data.kpis).forEach(([key, value]) => {
                const el = document.querySelector(`[data-count][data-key="${key}"]`);
                if (!el) return;
                el.dataset.count = value;
                const format = el.dataset.format || 'int';
                const suffix = el.dataset.suffix || '';
                const num = Number(value) || 0;   // ← FIX: evita NaN
                el.textContent = (format === 'decimal'
                    ? num.toFixed(1)
                    : num.toLocaleString('es-ES')) + suffix;
            });

            const ts = document.getElementById('lastUpdate');
            if (ts && data.timestamp) {
                ts.textContent = 'Actualizado: ' + data.timestamp.split(' ')[1];
            }

        } catch (err) {
            console.warn('Error al refrescar dashboard:', err.message);
        }
    }

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            clearInterval(timer);
        } else {
            refresh();
            timer = setInterval(refresh, INTERVAL);
        }
    });

    timer = setInterval(refresh, INTERVAL);
})();

/* =========================================================
   BOTÓN FILTRAR (spinner)
========================================================= */
const btnFiltrar = document.getElementById('btnFiltrar');
const filtroForm = document.getElementById('filtroForm');

if (filtroForm && btnFiltrar) {
    filtroForm.addEventListener('submit', function() {
        btnFiltrar.disabled = true;
        btnFiltrar.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status"></span> Cargando...`;
    });
}
</script>

</body>
</html>