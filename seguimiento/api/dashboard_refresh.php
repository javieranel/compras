<?php

/**
 * Endpoint AJAX que devuelve los KPIs del dashboard en JSON
 * Ruta: /compras/seguimiento/api/dashboard_refresh.php
 */

session_start();

if (!isset($_SESSION['nombre'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../php/conexion.php';

if (!isset($con) || !($con instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['error' => 'Sin conexión a BD']);
    exit;
}

$con->set_charset("utf8mb4");

$fecha_inicio     = $_GET['fecha_inicio'] ?? '';
$fecha_fin        = $_GET['fecha_fin'] ?? '';
$categoria_filtro = $_GET['categoria'] ?? '';
$estado_filtro    = $_GET['estado'] ?? '';

$where  = [];
$params = [];
$types  = "";

if ($fecha_inicio !== '') {
    $where[] = "fecha_solicitud >= ?";
    $params[] = $fecha_inicio;
    $types .= "s";
}
if ($fecha_fin !== '') {
    $where[] = "fecha_solicitud <= ?";
    $params[] = $fecha_fin;
    $types .= "s";
}
if ($categoria_filtro !== '') {
    $where[] = "categoria = ?";
    $params[] = $categoria_filtro;
    $types .= "s";
}
if ($estado_filtro !== '') {
    $where[] = "status_entrega = ?";
    $params[] = $estado_filtro;
    $types .= "s";
}

$whereSQL = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

function ejecutarConsulta($con, $sql, $types = "", $params = [])
{
    $stmt = $con->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => $con->error]);
        exit;
    }
    if ($types !== "" && count($params) > 0) $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error' => $stmt->error]);
        exit;
    }
    return $stmt->get_result();
}

// Totales
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

// Isla
$c = $condicionBase ? [$condicionBase, "fecha_aprobada IS NOT NULL"] : ["fecha_aprobada IS NOT NULL"];
$c[] = "((fecha_llegada_isla IS NOT NULL AND DATEDIFF(fecha_llegada_isla, fecha_aprobada) > 7) OR (fecha_llegada_isla IS NULL AND DATEDIFF(CURDATE(), fecha_aprobada) > 7))";
$r = ejecutarConsulta($con, "SELECT COUNT(*) AS total FROM compras_detalle WHERE " . implode(" AND ", $c), $types, $params);
$atrasosIsla = (int)($r->fetch_assoc()['total'] ?? 0);

// Importación
$c = $condicionBase ? [$condicionBase] : [];
$c[] = "UPPER(categoria) IN ('IMPORTACION','IMPORTACIÓN')";
$c[] = "fecha_solicitud IS NOT NULL";
$c[] = "((fecha_cierre_oc IS NOT NULL AND DATEDIFF(fecha_cierre_oc, fecha_solicitud) > 60) OR (fecha_cierre_oc IS NULL AND DATEDIFF(CURDATE(), fecha_solicitud) > 60))";
$r = ejecutarConsulta($con, "SELECT COUNT(*) AS total FROM compras_detalle WHERE " . implode(" AND ", $c), $types, $params);
$atrasosImportacion = (int)($r->fetch_assoc()['total'] ?? 0);

// Local
$c = $condicionBase ? [$condicionBase] : [];
$c[] = "UPPER(categoria) = 'COMPRA LOCAL'";
$c[] = "fecha_solicitud IS NOT NULL";
$c[] = "((fecha_cierre_oc IS NOT NULL AND DATEDIFF(fecha_cierre_oc, fecha_solicitud) > 7) OR (fecha_cierre_oc IS NULL AND DATEDIFF(CURDATE(), fecha_solicitud) > 7))";
$r = ejecutarConsulta($con, "SELECT COUNT(*) AS total FROM compras_detalle WHERE " . implode(" AND ", $c), $types, $params);
$atrasosLocal = (int)($r->fetch_assoc()['total'] ?? 0);

// Servicios
$c = $condicionBase ? [$condicionBase] : [];
$c[] = "categoria IN ('Servicio','Servicios')";
$c[] = "fecha_solicitud IS NOT NULL";
$c[] = "((fecha_cierre_oc IS NOT NULL AND DATEDIFF(fecha_cierre_oc, fecha_solicitud) > 15) OR (fecha_cierre_oc IS NULL AND DATEDIFF(CURDATE(), fecha_solicitud) > 15))";
$r = ejecutarConsulta($con, "SELECT COUNT(*) AS total FROM compras_detalle WHERE " . implode(" AND ", $c), $types, $params);
$atrasosServicio = (int)($r->fetch_assoc()['total'] ?? 0);

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success'   => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'kpis' => [
        'totalOrdenes'        => $totalOrdenes,
        'ordenesAbiertas'     => $ordenesAbiertas,
        'ordenesCerradas'     => $ordenesCerradas,
        'atrasosIsla'         => $atrasosIsla,
        'atrasosImportacion'  => $atrasosImportacion,
        'atrasosLocal'        => $atrasosLocal,
        'atrasosServicio'     => $atrasosServicio,
    ]
], JSON_UNESCAPED_UNICODE);
