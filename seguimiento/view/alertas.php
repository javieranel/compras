<?php
session_start();


if (!isset($_SESSION['nombre'])) {
    header('Location: /compras/seguimiento/login.php');
    exit;
}




include '../php/conexion.php';
require_once __DIR__ . '/../auth.php';

/*
|--------------------------------------------------------------------------
| CONFIGURACIÓN DE TIEMPOS
|--------------------------------------------------------------------------
*/

$limites = [
    'llegada a la isla' => 7,
    'importación'       => 60,
    'importacion'       => 60,
    'servicio'          => 15,
    'compra local'      => 7
];


/*
|--------------------------------------------------------------------------
| FUNCIONES
|--------------------------------------------------------------------------
*/


/**
 * Normaliza texto para comparar categorías.
 */
function normalizarTexto($texto)
{
    $texto = trim((string)$texto);

    return function_exists('mb_strtolower')
        ? mb_strtolower($texto, 'UTF-8')
        : strtolower($texto);
}


/**
 * Obtiene el límite según la categoría.
 */
function obtenerLimiteDias($categoria)
{
    global $limites;

    $categoriaNormalizada = normalizarTexto($categoria);

    return $limites[$categoriaNormalizada] ?? null;
}


/**
 * Determina si el estado indica que la compra ya terminó.
 */
function estaFinalizada($estado)
{
    $estado = normalizarTexto($estado);

    $estadosFinales = [
        'entregado',
        'entregada',
        'completado',
        'completada',
        'finalizado',
        'finalizada'
    ];

    return in_array($estado, $estadosFinales, true);
}


/**
 * Calcula días desde la fecha de solicitud.
 */
function calcularDias($fechaSolicitud)
{
    if (empty($fechaSolicitud)) {
        return 0;
    }

    try {

        $fechaInicio = new DateTime($fechaSolicitud);
        $fechaHoy    = new DateTime();

        $diferencia = $fechaInicio->diff($fechaHoy);

        return (int)$diferencia->days;
    } catch (Exception $e) {

        return 0;
    }
}


/**
 * Determina si una compra está atrasada.
 *
 * REGLAS:
 *
 * Llegada a la isla:
 *   llego_isla = Si         -> NO atrasada
 *   llego_isla = No         -> revisar límite de 7 días
 *   llego_isla = No Aplica  -> NO controlar
 *
 * Importación:
 *   > 60 días
 *
 * Servicio:
 *   > 15 días
 *
 * Compra local:
 *   > 7 días
 */
function estaAtrasada(
    $categoria,
    $llegoIsla,
    $fechaSolicitud,
    $statusEntrega
) {

    /*
     * Si ya fue entregada, no está atrasada.
     */
    if (estaFinalizada($statusEntrega)) {
        return false;
    }


    $categoriaNormalizada =
        normalizarTexto($categoria);


    /*
     * CASO ESPECIAL:
     * LLEGADA A LA ISLA
     */
    if ($categoriaNormalizada === 'llegada a la isla') {

        $llego =
            normalizarTexto($llegoIsla);


        /*
         * Si ya llegó a la isla,
         * no genera alerta.
         */
        if ($llego === 'si') {
            return false;
        }


        /*
         * Si no aplica,
         * tampoco genera alerta.
         */
        if (
            $llego === 'no aplica' ||
            $llego === 'no_aplica'
        ) {
            return false;
        }


        /*
         * Solo "No" debe continuar
         * con el conteo.
         */
        if ($llego !== 'no') {
            return false;
        }
    }


    /*
     * Obtener límite.
     */
    $limite =
        obtenerLimiteDias($categoria);


    if ($limite === null) {
        return false;
    }


    /*
     * Calcular días.
     */
    $dias =
        calcularDias($fechaSolicitud);


    /*
     * IMPORTANTE:
     * Se considera atraso cuando supera
     * el límite, no cuando es igual.
     */
    return $dias > $limite;
}


/**
 * Clase visual de categoría.
 */
function claseCategoria($categoria)
{
    $categoria =
        normalizarTexto($categoria);

    switch ($categoria) {

        case 'llegada a la isla':
            return 'categoria-isla';

        case 'importación':
        case 'importacion':
            return 'categoria-importacion';

        case 'servicio':
            return 'categoria-servicio';

        case 'compra local':
            return 'categoria-local';

        default:
            return 'categoria-default';
    }
}


/**
 * Icono de categoría.
 */
function iconoCategoria($categoria)
{
    $categoria =
        normalizarTexto($categoria);

    switch ($categoria) {

        case 'llegada a la isla':
            return 'bi-geo-alt-fill';

        case 'importación':
        case 'importacion':
            return 'bi-ship';

        case 'servicio':
            return 'bi-tools';

        case 'compra local':
            return 'bi-shop';

        default:
            return 'bi-box';
    }
}


/**
 * Obtiene la descripción del control.
 */
function descripcionControl($categoria, $llegoIsla)
{
    $categoriaNormalizada =
        normalizarTexto($categoria);

    if (
        $categoriaNormalizada ===
        'llegada a la isla'
    ) {

        $llego =
            normalizarTexto($llegoIsla);

        if ($llego === 'si') {
            return 'Llegó a la isla';
        }

        if (
            $llego === 'no aplica' ||
            $llego === 'no_aplica'
        ) {
            return 'No aplica';
        }

        return 'Pendiente de llegada';
    }

    $limite =
        obtenerLimiteDias($categoria);

    if ($limite !== null) {
        return 'Máximo ' . $limite . ' días';
    }

    return 'Sin límite definido';
}


/*
|--------------------------------------------------------------------------
| OBTENER COMPRAS
|--------------------------------------------------------------------------
*/

$compras = [];


$sql = "
    SELECT
        id,
        fecha_solicitud,
        status,
        solicitud,
        categoria,
        destino,
        area,
        solicitante,
        descripcion,
        proveedor,
        orden_compra,
        fecha_aprobada,
        llego_terminal,
        llego_isla,
        fecha_llegada_isla,
        fecha_llegada,
        status_entrega,
        contador_dias,
        alertas_dias,
        fecha_alerta,
        comentario
    FROM compras_detalle
    ORDER BY id DESC
";


$resultado = $con->query($sql);


if ($resultado) {

    while ($fila = $resultado->fetch_assoc()) {

        /*
         * Días desde solicitud.
         */
        $fila['dias_transcurridos'] =
            calcularDias(
                $fila['fecha_solicitud']
            );


        /*
         * Límite de la categoría.
         */
        $fila['limite_dias'] =
            obtenerLimiteDias(
                $fila['categoria']
            );


        /*
         * Verificar atraso.
         */
        $fila['atrasada'] =
            estaAtrasada(
                $fila['categoria'],
                $fila['llego_isla'],
                $fila['fecha_solicitud'],
                $fila['status_entrega']
            );


        /*
         * Días excedidos.
         */
        if (
            $fila['atrasada'] &&
            $fila['limite_dias'] !== null
        ) {

            $fila['dias_excedidos'] =
                $fila['dias_transcurridos']
                -
                $fila['limite_dias'];
        } else {

            $fila['dias_excedidos'] = 0;
        }


        $compras[] = $fila;
    }
}


/*
|--------------------------------------------------------------------------
| ESTADÍSTICAS
|--------------------------------------------------------------------------
*/

$totalCompras = count($compras);

$atrasadas = 0;

$llegadaIsla = 0;
$importaciones = 0;
$servicios = 0;
$comprasLocales = 0;

$llegaronIsla = 0;
$pendientesIsla = 0;


foreach ($compras as $compra) {

    $categoria =
        normalizarTexto(
            $compra['categoria']
        );


    /*
     * Total atrasadas.
     */
    if ($compra['atrasada']) {
        $atrasadas++;
    }


    /*
     * Estadísticas por categoría.
     */
    switch ($categoria) {

        case 'llegada a la isla':

            $llegadaIsla++;

            $llego =
                normalizarTexto(
                    $compra['llego_isla']
                );

            if ($llego === 'si') {
                $llegaronIsla++;
            }

            if ($llego === 'no') {
                $pendientesIsla++;
            }

            break;


        case 'importación':
        case 'importacion':

            $importaciones++;

            break;


        case 'servicio':

            $servicios++;

            break;


        case 'compra local':

            $comprasLocales++;

            break;
    }
}

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Seguimiento de Compras
    </title>


    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <!-- =====================================================
         BOOTSTRAP ICONS
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet">


    <!-- =====================================================
         ESTILOS
    ====================================================== -->

    <style>
        body {

            background:
                linear-gradient(180deg,
                    #f1f5f9 0%,
                    #f8fafc 100%);

            font-family:
                "Segoe UI",
                Arial,
                sans-serif;

            color: #0f172a;

        }


        /* =====================================================
           NAVBAR
        ====================================================== */

        .navbar-custom {

            background:
                linear-gradient(135deg,
                    #0f172a,
                    #1e3a8a,
                    #2563eb);

        }


        .navbar-brand {

            font-weight: 700;

            letter-spacing: .3px;

        }


        /* =====================================================
           HEADER
        ====================================================== */

        .dashboard-header {

            background:
                linear-gradient(135deg,
                    #172554,
                    #1d4ed8,
                    #0284c7);

            color: white;

            border-radius: 24px;

            padding: 32px;

            box-shadow:
                0 15px 35px rgba(30, 64, 175, .22);

        }


        .dashboard-header h1 {

            font-weight: 750;

        }


        /* =====================================================
           BOTÓN NUEVA COMPRA
        ====================================================== */

        .btn-nueva {

            background: white;

            color: #1d4ed8;

            border: none;

            border-radius: 12px;

            padding: 11px 20px;

            font-weight: 650;

        }


        .btn-nueva:hover {

            background: #eff6ff;

            color: #1e40af;

        }


        /* =====================================================
           TARJETAS
        ====================================================== */

        .stat-card {

            border: none;

            border-radius: 18px;

            padding: 20px;

            background: white;

            box-shadow:
                0 8px 25px rgba(15, 23, 42, .07);

            transition: .2s;

            height: 100%;

        }


        .stat-card:hover {

            transform: translateY(-3px);

            box-shadow:
                0 14px 30px rgba(15, 23, 42, .12);

        }


        .stat-icon {

            width: 52px;

            height: 52px;

            border-radius: 15px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 24px;

        }


        .icon-blue {

            background: #dbeafe;

            color: #2563eb;

        }


        .icon-red {

            background: #fee2e2;

            color: #dc2626;

        }


        .icon-purple {

            background: #ede9fe;

            color: #7c3aed;

        }


        .icon-green {

            background: #dcfce7;

            color: #16a34a;

        }


        .icon-orange {

            background: #ffedd5;

            color: #ea580c;

        }


        .stat-number {

            font-size: 30px;

            font-weight: 750;

        }


        .stat-label {

            color: #64748b;

            font-size: 14px;

        }


        /* =====================================================
           TARJETA PRINCIPAL
        ====================================================== */

        .main-card {

            background: white;

            border: none;

            border-radius: 20px;

            box-shadow:
                0 8px 30px rgba(15, 23, 42, .07);

        }


        /* =====================================================
           CATEGORÍAS
        ====================================================== */

        .categoria-badge {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 7px 11px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 650;

            white-space: nowrap;

        }


        .categoria-isla {

            background: #dbeafe;

            color: #1d4ed8;

        }


        .categoria-importacion {

            background: #e0e7ff;

            color: #4338ca;

        }


        .categoria-servicio {

            background: #f3e8ff;

            color: #7e22ce;

        }


        .categoria-local {

            background: #dcfce7;

            color: #15803d;

        }


        .categoria-default {

            background: #e2e8f0;

            color: #475569;

        }


        /* =====================================================
           ESTADOS
        ====================================================== */

        .estado-badge {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            border-radius: 20px;

            padding: 7px 12px;

            font-size: 12px;

            font-weight: 650;

            white-space: nowrap;

        }


        .estado-normal {

            background: #dcfce7;

            color: #15803d;

        }


        .estado-atrasado {

            background: #fee2e2;

            color: #b91c1c;

        }


        .estado-proceso {

            background: #dbeafe;

            color: #1d4ed8;

        }


        .estado-pendiente {

            background: #fef3c7;

            color: #b45309;

        }


        .estado-isla {

            background: #cffafe;

            color: #0e7490;

        }


        /* =====================================================
           LLEGADA A LA ISLA
        ====================================================== */

        .llegada-badge {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding: 6px 10px;

            border-radius: 10px;

            font-size: 12px;

            font-weight: 650;

            white-space: nowrap;

        }


        .llegada-si {

            background: #dcfce7;

            color: #15803d;

        }


        .llegada-no {

            background: #fee2e2;

            color: #b91c1c;

        }


        .llegada-na {

            background: #e2e8f0;

            color: #64748b;

        }


        /* =====================================================
           FILA ATRASADA
        ====================================================== */

        .fila-atrasada {

            background:
                rgba(254, 226, 226, .48) !important;

        }


        .fila-atrasada:hover {

            background:
                rgba(254, 226, 226, .78) !important;

        }


        .dias-alerta {

            color: #dc2626;

            font-weight: 750;

        }


        .dias-normal {

            color: #475569;

            font-weight: 650;

        }


        /* =====================================================
           TABLA
        ====================================================== */

        .table thead th {

            background: #f8fafc;

            color: #475569;

            font-size: 12px;

            text-transform: uppercase;

            letter-spacing: .4px;

            white-space: nowrap;

            border-bottom: 2px solid #e2e8f0;

        }


        .table tbody td {

            vertical-align: middle;

        }


        .table tbody tr {

            transition: .15s;

        }


        .table tbody tr:hover {

            background: #f8fbff;

        }


        /* =====================================================
           BUSCADOR
        ====================================================== */

        .search-box {

            border-radius: 12px;

            border: 1px solid #e2e8f0;

            padding: 10px 14px;

        }


        /* =====================================================
           BOTÓN ACCIONES
        ====================================================== */

        .btn-action {

            width: 36px;

            height: 36px;

            border-radius: 10px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

        }


        /* =====================================================
           MODAL
        ====================================================== */

        .modal-content {

            border: none;

            border-radius: 20px;

            overflow: hidden;

        }


        .modal-header {

            background:
                linear-gradient(135deg,
                    #172554,
                    #2563eb);

            color: white;

            border: none;

        }


        .modal-footer {

            border-top: 1px solid #e2e8f0;

        }


        /* =====================================================
           ALERTA GENERAL
        ====================================================== */

        .alerta-info {

            background:
                linear-gradient(135deg,
                    #fff7ed,
                    #fffbeb);

            border: 1px solid #fed7aa;

            border-radius: 16px;

            color: #9a3412;

        }


        /* =====================================================
           INFO TIEMPOS
        ====================================================== */

        .tiempo-card {

            border-radius: 15px;

            padding: 15px;

            background: #f8fafc;

            border: 1px solid #e2e8f0;

            height: 100%;

        }


        footer {

            color: #64748b;

            font-size: 13px;

        }
    </style>

</head>


<?php include '../includes/navbar.php'; ?>
                    



                    <!-- =================================================
                     SEGUIMIENTO
                ================================================== -->

                    <li class="nav-item dropdown">

                        <ul
                            class="dropdown-menu dropdown-menu-end"
                            aria-labelledby="seguimientoDropdown">


                            <li>

                                <a
                                    class="dropdown-item"
                                    href="/compras/seguimiento/view/alertas.php">

                                    <i class="bi bi-graph-up-arrow me-2"></i>

                                    Panel de seguimiento

                                </a>

                            </li>


                            <li>

                                <a
                                    class="dropdown-item"
                                    href="/compras/seguimiento/view/atrasos.php">

                                    <i class="bi bi-clock-history me-2"></i>

                                    Seguimiento de atrasos

                                </a>

                            </li>


                            <li>

                                <a
                                    class="dropdown-item"
                                    href="/compras/seguimiento/view/ver_listado_alertas.php">

                                    <i class="bi bi-list-check me-2"></i>

                                    Historial de alertas

                                </a>

                            </li>


                        </ul>

                    </li>



                    <!-- =================================================
                     REPORTES
                ================================================== -->

            



                   



                    <!-- =================================================
                     PERFIL / USUARIO
                ================================================== -->

                    


                </ul>

            </div>

        </div>

    </nav>



    <!-- =========================================================
     CONTENIDO
========================================================= -->

    <div class="container-fluid px-4 py-4">


        <!-- =====================================================
         HEADER
    ====================================================== -->

        <div class="dashboard-header mb-4">

            <div class="row align-items-center">

                <div class="col-lg-8">

                    <div class="small opacity-75 mb-2">

                        <i class="bi bi-speedometer2 me-1"></i>

                        Panel de seguimiento

                    </div>


                    <h1 class="mb-2">

                        Gestión de compras

                    </h1>


                    <p class="mb-0 opacity-75">

                        Controla tus solicitudes y detecta
                        automáticamente las compras que
                        superan el tiempo establecido.

                    </p>

                </div>


                <div
                    class="col-lg-4 text-lg-end mt-3 mt-lg-0">

                    <button
                        class="btn btn-nueva shadow-sm"
                        onclick="location.href='../view/formulario.php'">

                        <i class="bi bi-plus-lg me-2"></i>
                        Nueva compra
                    </button>

                </div>

            </div>

        </div>



        <!-- =====================================================
         ALERTA GENERAL
    ====================================================== -->

        <?php if ($atrasadas > 0): ?>

            <div class="alerta-info p-3 mb-4">

                <div class="d-flex align-items-center">

                    <div class="me-3">

                        <i
                            class="bi bi-exclamation-triangle-fill fs-3"></i>

                    </div>


                    <div>

                        <div class="fw-bold">

                            Atención:
                            <?= $atrasadas ?>

                            compra<?= $atrasadas == 1
                                        ? ''
                                        : 's' ?>

                            supera<?= $atrasadas == 1
                                        ? ''
                                        : 'n' ?>

                            el tiempo permitido.

                        </div>


                        <small>

                            Revisa las solicitudes
                            marcadas en rojo.

                        </small>

                    </div>

                </div>

            </div>

        <?php endif; ?>



        <!-- =====================================================
         TARJETAS
    ====================================================== -->

        <div class="row g-4 mb-4">


            <!-- TOTAL -->

            <div class="col-12 col-sm-6 col-xl-3">

                <div class="stat-card">

                    <div
                        class="d-flex
                    justify-content-between
                    align-items-center">

                        <div>

                            <div class="stat-label">

                                Total de compras

                            </div>


                            <div class="stat-number">

                                <?= $totalCompras ?>

                            </div>

                        </div>


                        <div class="stat-icon icon-blue">

                            <i class="bi bi-bag-check"></i>

                        </div>

                    </div>

                </div>

            </div>


            <!-- ATRASADAS -->

            <div class="col-12 col-sm-6 col-xl-3">

                <div class="stat-card">

                    <div
                        class="d-flex
                    justify-content-between
                    align-items-center">

                        <div>

                            <div class="stat-label">

                                Compras atrasadas

                            </div>


                            <div
                                class="stat-number text-danger">

                                <?= $atrasadas ?>

                            </div>

                        </div>


                        <div class="stat-icon icon-red">

                            <i
                                class="bi
                            bi-exclamation-triangle"></i>

                        </div>

                    </div>

                </div>

            </div>


            <!-- IMPORTACIONES -->

            <div class="col-12 col-sm-6 col-xl-3">

                <div class="stat-card">

                    <div
                        class="d-flex
                    justify-content-between
                    align-items-center">

                        <div>

                            <div class="stat-label">

                                Importaciones

                            </div>


                            <div class="stat-number">

                                <?= $importaciones ?>

                            </div>

                        </div>


                        <div class="stat-icon icon-purple">

                            <i class="bi bi-ship"></i>

                        </div>

                    </div>

                </div>

            </div>


            <!-- PENDIENTES ISLA -->

            <div class="col-12 col-sm-6 col-xl-3">

                <div class="stat-card">

                    <div
                        class="d-flex
                    justify-content-between
                    align-items-center">

                        <div>

                            <div class="stat-label">

                                Pendientes de llegada

                            </div>


                            <div class="stat-number">

                                <?= $pendientesIsla ?>

                            </div>


                            <small class="text-muted">

                                Llegada a la isla

                            </small>

                        </div>


                        <div class="stat-icon icon-orange">

                            <i class="bi bi-geo-alt"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- =====================================================
         TABLA
    ====================================================== -->

        <div class="main-card">

            <div class="card-body p-4">


                <!-- CABECERA -->

                <div class="row align-items-center mb-4">

                    <div class="col-md-6">

                        <h4 class="fw-bold mb-1">

                            <i
                                class="bi
                            bi-list-check
                            text-primary me-2"></i>

                            Solicitudes de compra

                        </h4>


                        <div class="text-muted small">

                            El sistema calcula automáticamente
                            los días y las alertas.

                        </div>

                    </div>


                    <div class="col-md-6 mt-3 mt-md-0">

                        <div class="input-group">

                            <span
                                class="input-group-text
                            bg-white
                            border-end-0">

                                <i
                                    class="bi
                                bi-search
                                text-muted"></i>

                            </span>


                            <input
                                type="text"
                                id="buscarCompra"
                                class="form-control
                            search-box
                            border-start-0"
                                placeholder="Buscar compra...">

                        </div>

                    </div>

                </div>



                <!-- TABLA -->

                <div class="table-responsive">

                    <table
                        class="table table-hover align-middle"
                        id="tablaCompras">

                        <thead>

                            <tr>

                                <th>ID</th>

                                <th>Fecha</th>

                                <th>Categoría</th>

                                <th>Descripción</th>

                                <th>Proveedor</th>

                                <th>Isla</th>

                                <th>Días</th>

                                <th>Límite</th>

                                <th>Estado</th>

                                <th class="text-center">

                                    Acción

                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php if (empty($compras)): ?>

                                <tr>

                                    <td
                                        colspan="10"
                                        class="text-center py-5">

                                        <div class="text-muted">

                                            <i
                                                class="bi
                                        bi-inbox
                                        display-5
                                        d-block
                                        mb-3"></i>

                                            No hay compras
                                            registradas.

                                        </div>

                                    </td>

                                </tr>


                            <?php else: ?>


                                <?php foreach (
                                    $compras as $compra
                                ): ?>


                                    <?php

                                    $categoria =
                                        $compra['categoria']
                                        ?? '';


                                    $estado =
                                        trim(
                                            $compra['status_entrega']
                                                ??
                                                $compra['status']
                                                ??
                                                'Pendiente'
                                        );


                                    $estadoNormalizado =
                                        normalizarTexto(
                                            $estado
                                        );


                                    $dias =
                                        $compra['dias_transcurridos'];


                                    $limite =
                                        $compra['limite_dias'];


                                    $atrasada =
                                        $compra['atrasada'];


                                    $llegoIsla =
                                        $compra['llego_isla'] ?? '';


                                    $claseCategoria =
                                        claseCategoria(
                                            $categoria
                                        );


                                    $iconoCategoria =
                                        iconoCategoria(
                                            $categoria
                                        );


                                    /*
                             * Estado visual.
                             */

                                    if ($atrasada) {

                                        $claseEstado =
                                            'estado-atrasado';

                                        $iconoEstado =
                                            'bi-exclamation-triangle-fill';
                                    } elseif (
                                        estaFinalizada(
                                            $estado
                                        )
                                    ) {

                                        $claseEstado =
                                            'estado-normal';

                                        $iconoEstado =
                                            'bi-check-circle-fill';
                                    } elseif (
                                        str_contains(
                                            $estadoNormalizado,
                                            'proceso'
                                        ) ||
                                        str_contains(
                                            $estadoNormalizado,
                                            'transito'
                                        ) ||
                                        str_contains(
                                            $estadoNormalizado,
                                            'tránsito'
                                        )
                                    ) {

                                        $claseEstado =
                                            'estado-proceso';

                                        $iconoEstado =
                                            'bi-arrow-repeat';
                                    } else {

                                        $claseEstado =
                                            'estado-pendiente';

                                        $iconoEstado =
                                            'bi-hourglass-split';
                                    }


                                    /*
                             * Estado llegada isla.
                             */

                                    $llegoNormalizado =
                                        normalizarTexto(
                                            $llegoIsla
                                        );


                                    if (
                                        $llegoNormalizado
                                        === 'si'
                                    ) {

                                        $claseLlegada =
                                            'llegada-si';

                                        $iconoLlegada =
                                            'bi-check-circle-fill';

                                        $textoLlegada =
                                            'Sí';
                                    } elseif (
                                        $llegoNormalizado
                                        === 'no'
                                    ) {

                                        $claseLlegada =
                                            'llegada-no';

                                        $iconoLlegada =
                                            'bi-clock-fill';

                                        $textoLlegada =
                                            'No';
                                    } elseif (
                                        $llegoNormalizado
                                        === 'no aplica' ||
                                        $llegoNormalizado
                                        === 'no_aplica'
                                    ) {

                                        $claseLlegada =
                                            'llegada-na';

                                        $iconoLlegada =
                                            'bi-dash-circle';

                                        $textoLlegada =
                                            'No aplica';
                                    } else {

                                        $claseLlegada =
                                            'llegada-na';

                                        $iconoLlegada =
                                            'bi-question-circle';

                                        $textoLlegada =
                                            $llegoIsla
                                            ?: 'Sin definir';
                                    }

                                    ?>


                                    <tr
                                        class="<?= $atrasada
                                                    ? 'fila-atrasada'
                                                    : '' ?>">


                                        <!-- ID -->

                                        <td>

                                            <span
                                                class="fw-bold
                                        text-primary">

                                                #<?= htmlspecialchars(
                                                        $compra['id']
                                                    ) ?>

                                            </span>

                                        </td>


                                        <!-- FECHA -->

                                        <td>

                                            <?php if (
                                                !empty($compra['fecha_solicitud'])
                                            ): ?>

                                                <?= date(
                                                    'd/m/Y',
                                                    strtotime(
                                                        $compra['fecha_solicitud']
                                                    )
                                                ) ?>

                                            <?php else: ?>

                                                -

                                            <?php endif; ?>

                                        </td>


                                        <!-- CATEGORÍA -->

                                        <td>

                                            <span
                                                class="
                                        categoria-badge
                                        <?= $claseCategoria ?>">

                                                <i
                                                    class="bi
                                            <?= $iconoCategoria ?>"></i>

                                                <?= htmlspecialchars(
                                                    $categoria
                                                        ?: 'Sin categoría'
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- DESCRIPCIÓN -->

                                        <td>

                                            <div
                                                class="fw-semibold">

                                                <?= htmlspecialchars(
                                                    $compra['descripcion']
                                                        ?: '-'
                                                ) ?>

                                            </div>


                                            <?php if (
                                                !empty($compra['solicitante'])
                                            ): ?>

                                                <small
                                                    class="text-muted">

                                                    <i
                                                        class="bi
                                                bi-person me-1"></i>

                                                    <?= htmlspecialchars(
                                                        $compra['solicitante']
                                                    ) ?>

                                                </small>

                                            <?php endif; ?>

                                        </td>


                                        <!-- PROVEEDOR -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $compra['proveedor']
                                                    ?: '-'
                                            ) ?>

                                        </td>


                                        <!-- LLEGADA ISLA -->

                                        <td>

                                            <?php if (
                                                normalizarTexto(
                                                    $categoria
                                                )
                                                ===
                                                'llegada a la isla'
                                            ): ?>

                                                <span
                                                    class="
                                            llegada-badge
                                            <?= $claseLlegada ?>">

                                                    <i
                                                        class="bi
                                                <?= $iconoLlegada ?>"></i>

                                                    <?= htmlspecialchars(
                                                        $textoLlegada
                                                    ) ?>

                                                </span>

                                            <?php else: ?>

                                                <span
                                                    class="text-muted">

                                                    —

                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <!-- DÍAS -->

                                        <td>

                                            <?php if (
                                                $atrasada
                                            ): ?>

                                                <span
                                                    class="dias-alerta">

                                                    <i
                                                        class="
                                                bi
                                                bi-clock-fill
                                                me-1"></i>

                                                    <?= $dias ?>
                                                    días

                                                </span>


                                                <div>

                                                    <small
                                                        class="text-danger
                                                fw-semibold">

                                                        +<?= $compra['dias_excedidos'] ?>

                                                        días

                                                    </small>

                                                </div>


                                            <?php else: ?>

                                                <span
                                                    class="dias-normal">

                                                    <?= $dias ?>
                                                    días

                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <!-- LÍMITE -->

                                        <td>

                                            <?php if (
                                                $limite !== null
                                            ): ?>

                                                <span
                                                    class="text-muted">

                                                    <?= $limite ?>
                                                    días

                                                </span>

                                            <?php else: ?>

                                                —

                                            <?php endif; ?>

                                        </td>


                                        <!-- ESTADO -->

                                        <td>

                                            <span
                                                class="
                                        estado-badge
                                        <?= $claseEstado ?>">

                                                <i
                                                    class="bi
                                            <?= $iconoEstado ?>"></i>

                                                <?= htmlspecialchars(
                                                    $atrasada
                                                        ? 'ATRASADO'
                                                        : $estado
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- ACCIÓN -->

                                        <td class="text-center">

                                            <div class="d-flex justify-content-center gap-2">

                                                <!-- Ver detalle -->
                                                <a
                                                    href="detalle_compra.php?id=<?= urlencode($compra['id']) ?>"
                                                    class="btn btn-sm btn-outline-primary rounded-3"
                                                    title="Ver detalle">
                                                    <i class="bi bi-eye me-1"></i>
                                                    Ver
                                                </a>


                                                <?php

                                                /*
        |--------------------------------------------------------------------------
        | DETERMINAR SI ESTÁ ATRASADA
        |--------------------------------------------------------------------------
        */

                                                $mostrarAtraso = false;

                                                $diasCompra = 0;
                                                $limiteCompra = null;

                                                if (!empty($compra['fecha_solicitud'])) {

                                                    try {

                                                        $fechaSolicitud = new DateTime(
                                                            $compra['fecha_solicitud']
                                                        );

                                                        $hoy = new DateTime();

                                                        $diasCompra = (int) $fechaSolicitud
                                                            ->diff($hoy)
                                                            ->days;
                                                    } catch (Exception $e) {

                                                        $diasCompra = 0;
                                                    }
                                                }


                                                /*
        |--------------------------------------------------------------------------
        | NORMALIZAR CATEGORÍA
        |--------------------------------------------------------------------------
        */

                                                $categoriaCompra = trim(
                                                    strtolower(
                                                        $compra['categoria'] ?? ''
                                                    )
                                                );


                                                /*
        |--------------------------------------------------------------------------
        | LÍMITES
        |--------------------------------------------------------------------------
        */

                                                switch ($categoriaCompra) {

                                                    case 'importacion':
                                                    case 'importación':

                                                        $limiteCompra = 60;

                                                        break;


                                                    case 'servicio':

                                                        $limiteCompra = 15;

                                                        break;


                                                    case 'compra local':

                                                        $limiteCompra = 7;

                                                        break;


                                                    case 'llegada a la isla':

                                                        $limiteCompra = 7;

                                                        /*
                 * Para llegada a la isla solamente
                 * consideramos atraso cuando
                 * todavía NO ha llegado.
                 */

                                                        $llegoIsla = trim(
                                                            strtolower(
                                                                $compra['llego_isla'] ?? ''
                                                            )
                                                        );

                                                        if (
                                                            $llegoIsla === 'no' &&
                                                            $diasCompra > $limiteCompra
                                                        ) {

                                                            $mostrarAtraso = true;
                                                        }

                                                        break;
                                                }


                                                /*
        |--------------------------------------------------------------------------
        | CATEGORÍAS NORMALES
        |--------------------------------------------------------------------------
        */

                                                if (
                                                    $categoriaCompra !== 'llegada a la isla' &&
                                                    $limiteCompra !== null &&
                                                    $diasCompra > $limiteCompra
                                                ) {

                                                    $mostrarAtraso = true;
                                                }

                                                ?>


                                                <?php if ($mostrarAtraso): ?>

                                                    <!-- Reportar atraso -->
                                                    <a
                                                        href="accion_atraso.php?id=<?= urlencode($compra['id']) ?>"
                                                        class="
                    btn
                    btn-sm
                    btn-danger
                    rounded-3
                    px-3
                "
                                                        title="Reportar atraso">

                                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>

                                                        Atraso

                                                    </a>

                                                <?php endif; ?>

                                            </div>

                                        </td>

                                    </tr>


                                <?php endforeach; ?>


                            <?php endif; ?>


                        </tbody>

                    </table>

                </div>

            </div>

        </div>



        <!-- =====================================================
         REGLAS DE TIEMPO
    ====================================================== -->

        <div class="row g-3 mt-4 mb-2">

            <div class="col-12">

                <div class="main-card p-4">

                    <h6 class="fw-bold mb-3">

                        <i
                            class="
                        bi
                        bi-clock-history
                        text-primary
                        me-2"></i>

                        Tiempos máximos de seguimiento

                    </h6>


                    <div class="row g-3">


                        <!-- ISLA -->

                        <div class="col-md-3">

                            <div class="tiempo-card">

                                <div
                                    class="
                                d-flex
                                align-items-center">

                                    <div
                                        class="
                                    stat-icon
                                    icon-blue
                                    me-3">

                                        <i
                                            class="
                                        bi
                                        bi-geo-alt-fill"></i>

                                    </div>


                                    <div>

                                        <div class="fw-bold">

                                            Llegada a la isla

                                        </div>


                                        <small
                                            class="text-muted">

                                            Más de 7 días

                                        </small>

                                    </div>

                                </div>

                            </div>

                        </div>


                        <!-- IMPORTACIÓN -->

                        <div class="col-md-3">

                            <div class="tiempo-card">

                                <div
                                    class="
                                d-flex
                                align-items-center">

                                    <div
                                        class="
                                    stat-icon
                                    icon-purple
                                    me-3">

                                        <i
                                            class="bi bi-ship"></i>

                                    </div>


                                    <div>

                                        <div class="fw-bold">

                                            Importación

                                        </div>


                                        <small
                                            class="text-muted">

                                            Más de 60 días

                                        </small>

                                    </div>

                                </div>

                            </div>

                        </div>


                        <!-- SERVICIO -->

                        <div class="col-md-3">

                            <div class="tiempo-card">

                                <div
                                    class="
                                d-flex
                                align-items-center">

                                    <div
                                        class="stat-icon me-3"
                                        style="
                                    background:#f3e8ff;
                                    color:#7e22ce;">

                                        <i
                                            class="bi bi-tools"></i>

                                    </div>


                                    <div>

                                        <div class="fw-bold">

                                            Servicio

                                        </div>


                                        <small
                                            class="text-muted">

                                            Más de 15 días

                                        </small>

                                    </div>

                                </div>

                            </div>

                        </div>


                        <!-- LOCAL -->

                        <div class="col-md-3">

                            <div class="tiempo-card">

                                <div
                                    class="
                                d-flex
                                align-items-center">

                                    <div
                                        class="
                                    stat-icon
                                    icon-green
                                    me-3">

                                        <i
                                            class="bi bi-shop"></i>

                                    </div>


                                    <div>

                                        <div class="fw-bold">

                                            Compra local

                                        </div>


                                        <small
                                            class="text-muted">

                                            Más de 7 días

                                        </small>

                                    </div>

                                </div>

                            </div>

                        </div>


                    </div>


                    <!-- NOTA ISLA -->

                    <div
                        class="
                    mt-3
                    p-3
                    rounded-3
                    bg-light
                    border">

                        <small class="text-muted">

                            <i
                                class="
                            bi
                            bi-info-circle
                            text-primary
                            me-1"></i>

                            Para las solicitudes de
                            <strong>llegada a la isla</strong>,
                            el sistema utiliza el campo
                            <strong>llego_isla</strong>.
                            Si está en <strong>Si</strong>,
                            se considera que la compra ya llegó
                            y no genera alerta.

                        </small>

                    </div>

                </div>

            </div>

        </div>


    </div>



    <!-- =========================================================
     MODAL NUEVA COMPRA
========================================================= -->





    <!-- =========================================================
     FOOTER
========================================================= -->

    <footer class="text-center py-4">

        Sistema de Seguimiento de Compras

        &copy; <?= date('Y') ?>

    </footer>



    <!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

    <script
        src="
    https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>



    <!-- =========================================================
     BUSCADOR
========================================================= -->

    <script>
        document.addEventListener(
            "DOMContentLoaded",
            function() {

                const buscador =
                    document.getElementById(
                        "buscarCompra"
                    );


                const tabla =
                    document.getElementById(
                        "tablaCompras"
                    );


                if (
                    !buscador ||
                    !tabla
                ) {

                    return;

                }


                buscador.addEventListener(
                    "keyup",
                    function() {

                        const texto =
                            this.value.toLowerCase();


                        const filas =
                            tabla.querySelectorAll(
                                "tbody tr"
                            );


                        filas.forEach(
                            function(fila) {

                                const contenido =
                                    fila.textContent
                                    .toLowerCase();


                                fila.style.display =
                                    contenido.includes(
                                        texto
                                    ) ?
                                    "" :
                                    "none";

                            }
                        );

                    }
                );

            }
        );
    </script>


</body>

</html>