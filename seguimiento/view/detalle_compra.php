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


include '../php/conexion.php';
require_once __DIR__ . '/../auth.php';

/*
|--------------------------------------------------------------------------
| VALIDAR ID
|--------------------------------------------------------------------------
*/

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id) {
    die('ID de compra no válido.');
}


/*
|--------------------------------------------------------------------------
| FUNCIONES
|--------------------------------------------------------------------------
*/

function h($valor)
{
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        'UTF-8'
    );
}


function fechaBonita($fecha)
{
    if (empty($fecha)) {
        return '-';
    }

    return date(
        'd/m/Y',
        strtotime($fecha)
    );
}


function normalizar($texto)
{
    $texto = trim((string)$texto);

    if (function_exists('mb_strtolower')) {
        return mb_strtolower($texto, 'UTF-8');
    }

    return strtolower($texto);
}


/*
|--------------------------------------------------------------------------
| OBTENER COMPRA + CORREO
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        c.*,

        a.correo AS correo_solicitante

    FROM compras_detalle c

    LEFT JOIN area_solicitante a
        ON c.area = a.area
        AND c.solicitante = a.solicitante

    WHERE c.id = ?

    LIMIT 1
";

$stmt = $con->prepare($sql);

if (!$stmt) {
    die('Error preparando consulta: ' .
        h($con->error));
}

$stmt->bind_param(
    "i",
    $id
);

$stmt->execute();

$resultado = $stmt->get_result();

$compra = $resultado->fetch_assoc();

$stmt->close();


if (!$compra) {
    die('<div style="font-family:Arial;padding:40px;text-align:center;">
            <h2>Compra no encontrada</h2>
            <p>No existe una compra con el ID #' .
        h($id) .
        '</p>
            <a href="../alertas.php">Volver</a>
        </div>');
}


/*
|--------------------------------------------------------------------------
| CALCULAR DÍAS
|--------------------------------------------------------------------------
*/

$diasTranscurridos = 0;

if (!empty($compra['fecha_solicitud'])) {

    $inicio = new DateTime(
        $compra['fecha_solicitud']
    );

    $hoy = new DateTime();

    $diasTranscurridos =
        (int)$inicio
            ->diff($hoy)
            ->days;
}


/*
|--------------------------------------------------------------------------
| DETERMINAR LÍMITE
|--------------------------------------------------------------------------
*/

$categoria =
    normalizar(
        $compra['categoria']
    );

$limiteDias = null;

switch ($categoria) {

    case 'llegada a la isla':
        $limiteDias = 7;
        break;

    case 'importacion':
    case 'importación':
        $limiteDias = 60;
        break;

    case 'servicio':
        $limiteDias = 15;
        break;

    case 'compra local':
        $limiteDias = 7;
        break;
}


/*
|--------------------------------------------------------------------------
| CALCULAR ATRASO
|--------------------------------------------------------------------------
*/

$diasAtraso = 0;

$estaAtrasada = false;

$llegoIsla =
    normalizar(
        $compra['llego_isla']
    );


if (
    $categoria === 'llegada a la isla'
) {

    if ($llegoIsla === 'no') {

        if (
            $limiteDias !== null &&
            $diasTranscurridos > $limiteDias
        ) {

            $estaAtrasada = true;

            $diasAtraso =
                $diasTranscurridos
                -
                $limiteDias;
        }
    }
} else {

    if (
        $limiteDias !== null &&
        $diasTranscurridos > $limiteDias
    ) {

        $estaAtrasada = true;

        $diasAtraso =
            $diasTranscurridos
            -
            $limiteDias;
    }
}


/*
|--------------------------------------------------------------------------
| ESTADO VISUAL
|--------------------------------------------------------------------------
*/

if ($estaAtrasada) {

    $estadoClase = 'danger';

    $estadoIcono =
        'bi-exclamation-triangle-fill';

    $estadoTexto =
        'Compra atrasada';
} else {

    $estadoClase = 'success';

    $estadoIcono =
        'bi-check-circle-fill';

    $estadoTexto =
        'Dentro del tiempo permitido';
}

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <title>
        Detalle de compra #<?= h($compra['id']) ?>
    </title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <!-- Bootstrap Icons -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet">


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


        .header {

            background:
                linear-gradient(135deg,
                    #172554,
                    #1d4ed8,
                    #0284c7);

            color: white;

            padding: 28px 0;

            border-radius:
                0 0 25px 25px;

            box-shadow:
                0 8px 25px rgba(15, 23, 42, .15);

        }


        .main-card {

            background: white;

            border: none;

            border-radius: 20px;

            box-shadow:
                0 10px 35px rgba(15, 23, 42, .08);

        }


        .section-title {

            display: flex;

            align-items: center;

            gap: 10px;

            font-weight: 700;

            margin-bottom: 20px;

        }


        .section-title i {

            font-size: 23px;

            color: #2563eb;

        }


        .info-box {

            background: #f8fafc;

            border:
                1px solid #e2e8f0;

            border-radius: 14px;

            padding: 16px;

            height: 100%;

        }


        .info-label {

            font-size: 11px;

            text-transform: uppercase;

            font-weight: 700;

            color: #64748b;

            margin-bottom: 5px;

            letter-spacing: .4px;

        }


        .info-value {

            font-size: 15px;

            font-weight: 600;

            color: #0f172a;

            word-break: break-word;

        }


        .status-card {

            border-radius: 18px;

            padding: 25px;

        }


        .status-number {

            font-size: 42px;

            font-weight: 800;

            line-height: 1;

        }


        .progress {

            height: 10px;

            border-radius: 20px;

            background: #e2e8f0;

        }


        .progress-bar {

            border-radius: 20px;

        }


        .timeline {

            border-left:
                3px solid #dbeafe;

            padding-left: 25px;

            margin-left: 8px;

        }


        .timeline-item {

            position: relative;

            margin-bottom: 22px;

        }


        .timeline-item::before {

            content: "";

            position: absolute;

            width: 13px;

            height: 13px;

            background: #2563eb;

            border-radius: 50%;

            left: -33px;

            top: 4px;

        }


        .btn-action {

            border-radius: 11px;

            padding:
                9px 16px;

            font-weight: 600;

        }


        .description {

            white-space: pre-line;

            line-height: 1.6;

        }
    </style>

</head>


<body>


    <!-- =========================================================
     HEADER
========================================================= -->

    <div class="header">

        <div class="container">

            <div class="d-flex align-items-center">

                <a
                    href="../index.php"
                    class="btn btn-light me-3">

                    <i
                        class="
                    bi
                    bi-arrow-left"></i>

                </a>


                <div>

                    <div class="small opacity-75">

                        Seguimiento de compras

                    </div>


                    <h2 class="fw-bold mb-0">

                        Detalle de compra

                        <span class="opacity-75">

                            #<?= h($compra['id']) ?>

                        </span>

                    </h2>

                </div>

            </div>

        </div>

    </div>



    <div class="container py-4">


        <!-- =====================================================
         ESTADO GENERAL
    ====================================================== -->

        <div
            class="
        status-card
        bg-<?= $estadoClase ?>
        bg-opacity-10
        border
        border-<?= $estadoClase ?>
        border-opacity-25
        mb-4">

            <div class="row align-items-center">


                <div class="col-md-8">

                    <div class="d-flex align-items-center">

                        <i
                            class="
                        bi
                        <?= $estadoIcono ?>
                        text-<?= $estadoClase ?>
                        fs-1
                        me-3"></i>


                        <div>

                            <h4
                                class="
                            fw-bold
                            text-<?= $estadoClase ?>
                            mb-1">

                                <?= $estadoTexto ?>

                            </h4>


                            <div class="text-muted">

                                Categoría:

                                <strong>

                                    <?= h(
                                        $compra['categoria']
                                            ?: '-'
                                    ) ?>

                                </strong>

                            </div>

                        </div>

                    </div>

                </div>


                <div
                    class="
                col-md-4
                text-md-end
                mt-3
                mt-md-0">

                    <div
                        class="
                    status-number
                    text-<?= $estadoClase ?>">

                        <?= $diasTranscurridos ?>

                    </div>


                    <div class="text-muted">

                        días transcurridos

                    </div>

                </div>

            </div>

        </div>



        <!-- =====================================================
         DATOS GENERALES
    ====================================================== -->

        <div class="main-card p-4 mb-4">

            <div class="section-title">

                <i class="bi bi-clipboard-data"></i>

                <h4 class="mb-0">

                    Información general

                </h4>

            </div>


            <div class="row g-3">


                <div class="col-md-3">

                    <div class="info-box">

                        <div class="info-label">

                            ID

                        </div>

                        <div class="info-value">

                            #<?= h(
                                    $compra['id']
                                ) ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="info-box">

                        <div class="info-label">

                            Solicitud

                        </div>

                        <div class="info-value">

                            <?= h(
                                $compra['solicitud']
                                    ?: '-'
                            ) ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="info-box">

                        <div class="info-label">

                            Fecha solicitud

                        </div>

                        <div class="info-value">

                            <?= fechaBonita(
                                $compra['fecha_solicitud']
                            ) ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="info-box">

                        <div class="info-label">

                            Estado

                        </div>

                        <div class="info-value">

                            <?= h(
                                $compra['status']
                                    ?: '-'
                            ) ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="info-box">

                        <div class="info-label">

                            Categoría

                        </div>

                        <div class="info-value">

                            <?= h(
                                $compra['categoria']
                                    ?: '-'
                            ) ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="info-box">

                        <div class="info-label">

                            Área solicitante

                        </div>

                        <div class="info-value">

                            <?= h(
                                $compra['area']
                                    ?: '-'
                            ) ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="info-box">

                        <div class="info-label">

                            Solicitante

                        </div>

                        <div class="info-value">

                            <?= h(
                                $compra['solicitante']
                                    ?: '-'
                            ) ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="info-box">

                        <div class="info-label">

                            Correo

                        </div>

                        <div class="info-value">

                            <?php if (
                                !empty($compra['correo_solicitante'])
                            ): ?>

                                <span
                                    class="
                                text-success">

                                    <i
                                        class="
                                    bi
                                    bi-envelope-check
                                    me-1"></i>

                                    <?= h(
                                        $compra['correo_solicitante']
                                    ) ?>

                                </span>

                            <?php else: ?>

                                <span
                                    class="
                                text-danger">

                                    <i
                                        class="
                                    bi
                                    bi-envelope-x
                                    me-1"></i>

                                    Correo no encontrado

                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="info-box">

                        <div class="info-label">

                            Destino

                        </div>

                        <div class="info-value">

                            <?= h(
                                $compra['destino']
                                    ?: '-'
                            ) ?>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- =====================================================
         PROVEEDOR / ORDEN
    ====================================================== -->

        <div class="main-card p-4 mb-4">

            <div class="section-title">

                <i class="bi bi-truck"></i>

                <h4 class="mb-0">

                    Proveedor y orden

                </h4>

            </div>


            <div class="row g-3">


                <div class="col-md-4">

                    <div class="info-box">

                        <div class="info-label">

                            Proveedor

                        </div>

                        <div class="info-value">

                            <?= h(
                                $compra['proveedor']
                                    ?: '-'
                            ) ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="info-box">

                        <div class="info-label">

                            Orden de compra

                        </div>

                        <div class="info-value">

                            <?= h(
                                $compra['orden_compra']
                                    ?: '-'
                            ) ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="info-box">

                        <div class="info-label">

                            Fecha aprobada

                        </div>

                        <div class="info-value">

                            <?= fechaBonita(
                                $compra['fecha_aprobada']
                            ) ?>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- =====================================================
         TIEMPOS
    ====================================================== -->

        <div class="main-card p-4 mb-4">

            <div class="section-title">

                <i class="bi bi-clock-history"></i>

                <h4 class="mb-0">

                    Seguimiento de tiempos

                </h4>

            </div>


            <div class="row g-3">


                <div class="col-md-4">

                    <div class="info-box text-center">

                        <div class="info-label">

                            Días transcurridos

                        </div>

                        <div
                            class="
                        fs-2
                        fw-bold">

                            <?= $diasTranscurridos ?>

                        </div>

                        <small class="text-muted">

                            desde la solicitud

                        </small>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="info-box text-center">

                        <div class="info-label">

                            Límite permitido

                        </div>

                        <div
                            class="
                        fs-2
                        fw-bold
                        text-primary">

                            <?= $limiteDias !== null
                                ? $limiteDias
                                : '-' ?>

                        </div>

                        <small class="text-muted">

                            días

                        </small>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="info-box text-center">

                        <div class="info-label">

                            Días de atraso

                        </div>

                        <div
                            class="
                        fs-2
                        fw-bold
                        <?= $estaAtrasada
                            ? 'text-danger'
                            : 'text-success' ?>">

                            <?= $diasAtraso ?>

                        </div>

                        <small class="text-muted">

                            <?= $estaAtrasada
                                ? 'fuera del límite'
                                : 'sin atraso' ?>

                        </small>

                    </div>

                </div>


                <?php if (
                    $limiteDias !== null
                ): ?>

                    <?php

                    $porcentaje =
                        ($diasTranscurridos /
                            $limiteDias) * 100;

                    $porcentaje =
                        min(
                            100,
                            max(
                                0,
                                $porcentaje
                            )
                        );

                    ?>

                    <div class="col-12 mt-3">

                        <div
                            class="
                        d-flex
                        justify-content-between
                        mb-2">

                            <span
                                class="
                            fw-semibold">

                                Progreso del tiempo

                            </span>


                            <span
                                class="
                            text-muted">

                                <?= round(
                                    $porcentaje
                                ) ?>%

                            </span>

                        </div>


                        <?php $porcentaje = max(0, min(100, (float)$porcentaje)); ?>

                        <div class="progress">
                            <div
                                class="progress-bar <?= $estaAtrasada ? 'bg-danger' : 'bg-primary' ?>"
                                role="progressbar"
                                style="width: <?= $porcentaje ?>%;"
                                aria-valuenow="<?= $porcentaje ?>"
                                aria-valuemin="0"
                                aria-valuemax="100"></div>
                        </div>

                    </div>

                <?php endif; ?>

            </div>

        </div>



        <!-- =====================================================
         LOGÍSTICA
    ====================================================== -->

        <div class="main-card p-4 mb-4">

            <div class="section-title">

                <i class="bi bi-boxes"></i>

                <h4 class="mb-0">

                    Estado logístico

                </h4>

            </div>


            <div class="row g-3">


                <div class="col-md-4">

                    <div class="info-box">

                        <div class="info-label">

                            Llegó a terminal

                        </div>

                        <div class="info-value">

                            <?= h(
                                $compra['llego_terminal']
                                    ?: '-'
                            ) ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="info-box">

                        <div class="info-label">

                            Llegó a la isla

                        </div>

                        <div class="info-value">

                            <?php

                            $isla =
                                normalizar(
                                    $compra['llego_isla']
                                );

                            ?>

                            <?php if (
                                $isla === 'si'
                            ): ?>

                                <span
                                    class="
                                badge
                                text-bg-success
                                px-3
                                py-2">

                                    <i
                                        class="
                                    bi
                                    bi-check-circle
                                    me-1"></i>

                                    Sí

                                </span>

                            <?php elseif (
                                $isla === 'no'
                            ): ?>

                                <span
                                    class="
                                badge
                                text-bg-danger
                                px-3
                                py-2">

                                    <i
                                        class="
                                    bi
                                    bi-x-circle
                                    me-1"></i>

                                    No

                                </span>

                            <?php else: ?>

                                <span
                                    class="
                                badge
                                text-bg-secondary
                                px-3
                                py-2">

                                    <?= h(
                                        $compra['llego_isla']
                                            ?: 'No aplica'
                                    ) ?>

                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="info-box">

                        <div class="info-label">

                            Fecha llegada isla

                        </div>

                        <div class="info-value">

                            <?= fechaBonita(
                                $compra['fecha_llegada_isla']
                            ) ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="info-box">

                        <div class="info-label">

                            Fecha llegada

                        </div>

                        <div class="info-value">

                            <?= fechaBonita(
                                $compra['fecha_llegada']
                            ) ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-8">

                    <div class="info-box">

                        <div class="info-label">

                            Estado de entrega

                        </div>

                        <div class="info-value">

                            <?= h(
                                $compra['status_entrega']
                                    ?: '-'
                            ) ?>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- =====================================================
         DESCRIPCIÓN
    ====================================================== -->

        <div class="main-card p-4 mb-4">

            <div class="section-title">

                <i class="bi bi-card-text"></i>

                <h4 class="mb-0">

                    Descripción de la compra

                </h4>

            </div>


            <div
                class="
            description
            text-muted">

                <?= h(
                    $compra['descripcion']
                        ?: 'Sin descripción registrada.'
                ) ?>

            </div>

        </div>



        <!-- =====================================================
         COMENTARIOS
    ====================================================== -->

        <?php if (
            !empty($compra['comentario'])
        ): ?>

            <div class="main-card p-4 mb-4">

                <div class="section-title">

                    <i class="bi bi-chat-left-text"></i>

                    <h4 class="mb-0">

                        Comentario

                    </h4>

                </div>


                <div class="alert alert-light border">

                    <?= h(
                        $compra['comentario']
                    ) ?>

                </div>

            </div>

        <?php endif; ?>



        <!-- =====================================================
         BOTONES
    ====================================================== -->

        <div
            class="
        d-flex
        justify-content-between
        align-items-center
        flex-wrap
        gap-2
        mb-4">


            <a
                href="./alertas.php"
                class="btn btn-light btn-action">

                <i
                    class="
                bi
                bi-arrow-left
                me-2"></i>

                Volver

            </a>


            <?php if (
                $estaAtrasada
            ): ?>

                <a
                    href="accion_atraso.php?id=<?= urlencode(
                                                    $compra['id']
                                                ) ?>"
                    class="
                btn
                btn-danger
                btn-action">

                    <i
                        class="
                    bi
                    bi-exclamation-triangle
                    me-2"></i>

                    Reportar atraso

                </a>

            <?php endif; ?>

        </div>

    </div>



    <script
        src="
    https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js
"></script>


</body>

</html>