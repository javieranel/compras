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

    die('<div style="
            font-family:Arial;
            padding:40px;
            text-align:center;
        ">
            <h2>❌ Compra no válida</h2>
            <p>No se recibió un ID de compra válido.</p>
            <a href="index.php">Volver a compras</a>
        </div>');
}


/*
|--------------------------------------------------------------------------
| BUSCAR COMPRA + CORREO DEL SOLICITANTE
|--------------------------------------------------------------------------
|
| Relacionamos:
|
| compras_detalle.area
| compras_detalle.solicitante
|
| con:
|
| area_solicitante.area
| area_solicitante.solicitante
|
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        c.id,
        c.fecha_solicitud,
        c.status,
        c.solicitud,
        c.categoria,
        c.destino,
        c.area,
        c.solicitante,
        c.descripcion,
        c.proveedor,
        c.orden_compra,
        c.fecha_aprobada,
        c.llego_terminal,
        c.llego_isla,
        c.fecha_llegada_isla,
        c.fecha_llegada,
        c.status_entrega,
        c.contador_dias,
        c.alertas_dias,
        c.fecha_alerta,
        c.comentario,

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

    die('Error preparando consulta: '
        . htmlspecialchars($con->error));
}


$stmt->bind_param(
    "i",
    $id
);


$stmt->execute();


$resultado =
    $stmt->get_result();


$compra =
    $resultado->fetch_assoc();


$stmt->close();


/*
|--------------------------------------------------------------------------
| VALIDAR COMPRA
|--------------------------------------------------------------------------
*/

if (!$compra) {

    die('<div style="
            font-family:Arial;
            padding:40px;
            text-align:center;
        ">
            <h2>❌ Compra no encontrada</h2>
            <p>
                No existe una compra con el ID:
                <strong>'
        . htmlspecialchars($id)
        . '</strong>
            </p>

            <a href="./alertas.php">
                Volver a compras
            </a>
        </div>');
}


/*
|--------------------------------------------------------------------------
| FUNCIONES
|--------------------------------------------------------------------------
*/

function normalizarTexto($texto)
{
    $texto = trim((string)$texto);

    return function_exists('mb_strtolower')
        ? mb_strtolower($texto, 'UTF-8')
        : strtolower($texto);
}


function calcularDias($fecha)
{
    if (empty($fecha)) {
        return 0;
    }

    try {

        $inicio =
            new DateTime($fecha);

        $hoy =
            new DateTime();

        return (int)$inicio
            ->diff($hoy)
            ->days;
    } catch (Exception $e) {

        return 0;
    }
}


function obtenerLimite($categoria)
{
    $categoria =
        normalizarTexto($categoria);

    switch ($categoria) {

        case 'llegada a la isla':
            return 7;

        case 'importación':
        case 'importacion':
            return 60;

        case 'servicio':
            return 15;

        case 'compra local':
            return 7;

        default:
            return null;
    }
}


/*
|--------------------------------------------------------------------------
| CALCULAR INFORMACIÓN
|--------------------------------------------------------------------------
*/

$diasTranscurridos =
    calcularDias(
        $compra['fecha_solicitud']
    );


$limiteDias =
    obtenerLimite(
        $compra['categoria']
    );


$diasAtraso = 0;


$atrasada = false;


/*
|--------------------------------------------------------------------------
| VALIDAR ATRASO
|--------------------------------------------------------------------------
*/

$categoria =
    normalizarTexto(
        $compra['categoria']
    );


$llegoIsla =
    normalizarTexto(
        $compra['llego_isla']
    );


if (
    $categoria ===
    'llegada a la isla'
) {

    /*
     * Si ya llegó:
     */

    if ($llegoIsla === 'si') {

        $atrasada = false;
    }

    /*
     * Si no aplica:
     */ elseif (
        $llegoIsla === 'no aplica' ||
        $llegoIsla === 'no_aplica'
    ) {

        $atrasada = false;
    }

    /*
     * Si todavía no llega:
     */ elseif (
        $llegoIsla === 'no'
    ) {

        if (
            $limiteDias !== null &&
            $diasTranscurridos > $limiteDias
        ) {

            $atrasada = true;

            $diasAtraso =
                $diasTranscurridos
                -
                $limiteDias;
        }
    }
} else {

    /*
     * Importación,
     * Servicio,
     * Compra local.
     */

    if (
        $limiteDias !== null &&
        $diasTranscurridos > $limiteDias
    ) {

        $atrasada = true;

        $diasAtraso =
            $diasTranscurridos
            -
            $limiteDias;
    }
}


/*
|--------------------------------------------------------------------------
| FECHA FORMATEADA
|--------------------------------------------------------------------------
*/

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

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Reportar atraso #<?= htmlspecialchars($compra['id']) ?>
    </title>


    <!-- BOOTSTRAP -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <!-- ICONOS -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet">


    <style>
        body {

            background:
                linear-gradient(180deg,
                    #f1f5f9,
                    #f8fafc);

            font-family:
                "Segoe UI",
                Arial,
                sans-serif;

        }


        .topbar {

            background:
                linear-gradient(135deg,
                    #172554,
                    #1d4ed8,
                    #0284c7);

            color: white;

            padding: 25px;

            border-radius: 0 0 24px 24px;

        }


        .main-card {

            background: white;

            border: none;

            border-radius: 20px;

            box-shadow:
                0 10px 35px rgba(15, 23, 42, .08);

        }


        .info-card {

            border-radius: 15px;

            padding: 18px;

            background: #f8fafc;

            border: 1px solid #e2e8f0;

            height: 100%;

        }


        .info-label {

            color: #64748b;

            font-size: 12px;

            text-transform: uppercase;

            font-weight: 700;

            letter-spacing: .4px;

            margin-bottom: 5px;

        }


        .info-value {

            font-size: 15px;

            font-weight: 650;

            color: #0f172a;

        }


        .alerta {

            background:
                linear-gradient(135deg,
                    #fee2e2,
                    #fff1f2);

            border: 1px solid #fecaca;

            color: #991b1b;

            border-radius: 18px;

        }


        .normal {

            background:
                linear-gradient(135deg,
                    #dcfce7,
                    #f0fdf4);

            border: 1px solid #bbf7d0;

            color: #166534;

            border-radius: 18px;

        }


        .dias-grande {

            font-size: 38px;

            font-weight: 800;

        }


        .form-control,
        .form-select {

            border-radius: 11px;

            padding: 11px 13px;

            border-color: #dbe2ea;

        }


        .form-control:focus,
        .form-select:focus {

            border-color: #2563eb;

            box-shadow:
                0 0 0 .2rem rgba(37, 99, 235, .12);

        }


        .btn-primary {

            border-radius: 11px;

            padding: 11px 20px;

            font-weight: 650;

        }


        .btn-light {

            border-radius: 11px;

            padding: 11px 20px;

        }
    </style>

</head>


<body>


    <!-- =========================================================
     CABECERA
========================================================= -->

    <div class="topbar">

        <div class="container">

            <div class="d-flex align-items-center">

                <a
                    href="../index.php"
                    class="
                btn
                btn-light
                me-3">

                    <i
                        class="
                    bi
                    bi-arrow-left"></i>

                </a>


                <div>

                    <div class="small opacity-75">

                        Seguimiento de compras

                    </div>


                    <h2 class="mb-0 fw-bold">

                        Reportar atraso

                        <span class="opacity-75">

                            #<?= htmlspecialchars(
                                    $compra['id']
                                ) ?>

                        </span>

                    </h2>

                </div>

            </div>

        </div>

    </div>



    <!-- =========================================================
     CONTENIDO
========================================================= -->

    <div class="container py-4">


        <!-- =====================================================
         ESTADO DEL ATRASO
    ====================================================== -->

        <?php if ($atrasada): ?>

            <div
                class="
            alerta
            p-4
            mb-4">

                <div class="row align-items-center">

                    <div class="col-md-8">

                        <div
                            class="
                        d-flex
                        align-items-center">

                            <i
                                class="
                            bi
                            bi-exclamation-triangle-fill
                            fs-1
                            me-3"></i>


                            <div>

                                <h4 class="fw-bold mb-1">

                                    Compra atrasada

                                </h4>


                                <div>

                                    Esta compra ha superado
                                    el tiempo máximo permitido.

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
                        dias-grande">

                            +<?= $diasAtraso ?>

                        </div>


                        <div>

                            días de atraso

                        </div>

                    </div>

                </div>

            </div>


        <?php else: ?>


            <div
                class="
            normal
            p-4
            mb-4">

                <i
                    class="
                bi
                bi-check-circle-fill
                me-2"></i>

                Esta compra actualmente
                no supera el límite establecido.

            </div>


        <?php endif; ?>



        <!-- =====================================================
         INFORMACIÓN DE LA COMPRA
    ====================================================== -->

        <div class="main-card p-4 mb-4">

            <div
                class="
            d-flex
            align-items-center
            mb-4">

                <i
                    class="
                bi
                bi-box-seam
                text-primary
                fs-3
                me-2"></i>


                <div>

                    <h4 class="fw-bold mb-0">

                        Información de la compra

                    </h4>


                    <small class="text-muted">

                        Datos obtenidos automáticamente
                        del registro.

                    </small>

                </div>

            </div>



            <div class="row g-3">


                <!-- ID -->

                <div class="col-md-3">

                    <div class="info-card">

                        <div class="info-label">

                            ID compra

                        </div>


                        <div class="info-value">

                            #<?= htmlspecialchars(
                                    $compra['id']
                                ) ?>

                        </div>

                    </div>

                </div>


                <!-- FECHA -->

                <div class="col-md-3">

                    <div class="info-card">

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


                <!-- CATEGORÍA -->

                <div class="col-md-3">

                    <div class="info-card">

                        <div class="info-label">

                            Categoría

                        </div>


                        <div class="info-value">

                            <?= htmlspecialchars(
                                $compra['categoria']
                                    ?: '-'
                            ) ?>

                        </div>

                    </div>

                </div>


                <!-- LÍMITE -->

                <div class="col-md-3">

                    <div class="info-card">

                        <div class="info-label">

                            Límite

                        </div>


                        <div class="info-value">

                            <?= $limiteDias !== null
                                ? $limiteDias . ' días'
                                : '-' ?>

                        </div>

                    </div>

                </div>


                <!-- ÁREA -->

                <div class="col-md-4">

                    <div class="info-card">

                        <div class="info-label">

                            Área

                        </div>


                        <div class="info-value">

                            <?= htmlspecialchars(
                                $compra['area']
                                    ?: '-'
                            ) ?>

                        </div>

                    </div>

                </div>


                <!-- SOLICITANTE -->

                <div class="col-md-4">

                    <div class="info-card">

                        <div class="info-label">

                            Solicitante

                        </div>


                        <div class="info-value">

                            <?= htmlspecialchars(
                                $compra['solicitante']
                                    ?: '-'
                            ) ?>

                        </div>

                    </div>

                </div>


                <!-- CORREO -->

                <div class="col-md-4">

                    <div class="info-card">

                        <div class="info-label">

                            Correo del solicitante

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

                                    <?= htmlspecialchars(
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


                <!-- PROVEEDOR -->

                <div class="col-md-4">

                    <div class="info-card">

                        <div class="info-label">

                            Proveedor

                        </div>


                        <div class="info-value">

                            <?= htmlspecialchars(
                                $compra['proveedor']
                                    ?: '-'
                            ) ?>

                        </div>

                    </div>

                </div>


                <!-- ORDEN -->

                <div class="col-md-4">

                    <div class="info-card">

                        <div class="info-label">

                            Orden de compra

                        </div>


                        <div class="info-value">

                            <?= htmlspecialchars(
                                $compra['orden_compra']
                                    ?: '-'
                            ) ?>

                        </div>

                    </div>

                </div>


                <!-- DESTINO -->

                <div class="col-md-4">

                    <div class="info-card">

                        <div class="info-label">

                            Destino

                        </div>


                        <div class="info-value">

                            <?= htmlspecialchars(
                                $compra['destino']
                                    ?: '-'
                            ) ?>

                        </div>

                    </div>

                </div>


                <!-- LLEGÓ ISLA -->

                <div class="col-md-4">

                    <div class="info-card">

                        <div class="info-label">

                            Llegó a la isla

                        </div>


                        <div class="info-value">

                            <?= htmlspecialchars(
                                $compra['llego_isla']
                                    ?: '-'
                            ) ?>

                        </div>

                    </div>

                </div>


                <!-- DÍAS -->

                <div class="col-md-4">

                    <div class="info-card">

                        <div class="info-label">

                            Días transcurridos

                        </div>


                        <div class="info-value">

                            <?= $diasTranscurridos ?>
                            días

                        </div>

                    </div>

                </div>


                <!-- ATRASO -->

                <div class="col-md-4">

                    <div class="info-card">

                        <div class="info-label">

                            Días de atraso

                        </div>


                        <div
                            class="
                        info-value
                        <?= $atrasada
                            ? 'text-danger'
                            : 'text-success' ?>">

                            <?= $diasAtraso ?>
                            días

                        </div>

                    </div>

                </div>


                <!-- DESCRIPCIÓN -->

                <div class="col-12">

                    <div class="info-card">

                        <div class="info-label">

                            Descripción

                        </div>


                        <div class="info-value">

                            <?= nl2br(
                                htmlspecialchars(
                                    $compra['descripcion']
                                        ?: '-'
                                )
                            ) ?>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- =====================================================
         FORMULARIO
    ====================================================== -->

        <div class="main-card p-4">

            <div
                class="
            d-flex
            align-items-center
            mb-4">

                <i
                    class="
                bi
                bi-journal-text
                text-danger
                fs-3
                me-2"></i>


                <div>

                    <h4 class="fw-bold mb-0">

                        Registrar seguimiento

                    </h4>


                    <small class="text-muted">

                        Explique el motivo del atraso
                        y las acciones realizadas.

                    </small>

                </div>

            </div>


            <form
                action="../php/guardar_atraso.php"
                method="POST">


                <!-- ID OCULTO -->

                <input
                    type="hidden"
                    name="id"
                    value="<?= htmlspecialchars(
                                $compra['id']
                            ) ?>">


                <!-- CORREO DEL SOLICITANTE -->
                <input 
                    type="hidden" 
                    name="correo_solicitante" 
                    value="<?= htmlspecialchars(
                        $compra['correo_solicitante'] ?? '') ?>">


                <div class="row g-4">


                    <!-- MOTIVO -->

                    <div class="col-md-6">

                        <label
                            class="
                        form-label
                        fw-semibold">

                            Motivo del atraso

                            <span class="text-danger">

                                *

                            </span>

                        </label>


                        <select
                            name="motivo"
                            class="form-select"
                            required>

                            <option value="">

                                Seleccione un motivo

                            </option>


                            <option
                                value="
                            Retraso del proveedor">

                                Retraso del proveedor

                            </option>


                            <option
                                value="
                            Retraso del transporte">

                                Retraso del transporte

                            </option>


                            <option
                                value="
                            Problema aduanero">

                                Problema aduanero

                            </option>


                            <option
                                value="
                            Problema logístico">

                                Problema logístico

                            </option>


                            <option
                                value="
                            Falta de documentación">

                                Falta de documentación

                            </option>


                            <option
                                value="
                            Problema con la orden de compra">

                                Problema con la orden de compra

                            </option>


                            <option
                                value="Otro">

                                Otro

                            </option>

                        </select>

                    </div>


                    <!-- ESTADO -->

                    <div class="col-md-6">

                        <label
                            class="
                        form-label
                        fw-semibold">

                            Estado del seguimiento

                        </label>


                        <select
                            name="estado"
                            class="form-select">

                            <option
                                value="Pendiente">

                                Pendiente

                            </option>


                            <option
                                value="En seguimiento">

                                En seguimiento

                            </option>


                            <option
                                value="Resuelto">

                                Resuelto

                            </option>

                        </select>

                    </div>


                    <!-- EXPLICACIÓN -->

                    <div class="col-12">

                        <label
                            class="
                        form-label
                        fw-semibold">

                            ¿Por qué no ha llegado?

                            <span class="text-danger">

                                *

                            </span>

                        </label>


                        <textarea
                            name="explicacion"
                            class="form-control"
                            rows="5"
                            required
                            placeholder="
Explique detalladamente la razón del atraso.
Por ejemplo: El proveedor informó que la mercancía
presenta un retraso debido a problemas de transporte..."></textarea>

                    </div>


                    <!-- ACCIÓN -->

                    <div class="col-12">

                        <label
                            class="
                        form-label
                        fw-semibold">

                            Acción tomada

                        </label>


                        <textarea
                            name="accion_tomada"
                            class="form-control"
                            rows="4"
                            placeholder="
Indique qué acción se está realizando para solucionar
o dar seguimiento al atraso..."></textarea>

                    </div>


                    <!-- COMENTARIO -->

                    <div class="col-12">

                        <label
                            class="
                        form-label
                        fw-semibold">

                            Comentario adicional

                        </label>


                        <input
                            type="text"
                            name="comentario"
                            class="form-control"
                            maxlength="255"
                            placeholder="
Comentario adicional...">

                    </div>


                    <!-- CORREO -->

                    <div class="col-12">

                        <div
                            class="
                        alert
                        alert-info
                        d-flex
                        align-items-center">

                            <i
                                class="
                            bi
                            bi-envelope
                            fs-4
                            me-3"></i>


                            <div>

                                <strong>
                                    Notificación por correo
                                </strong>

                                <br>

                                <small>

                                    Al guardar el reporte,
                                    enviaremos una notificación
                                    al correo registrado del
                                    solicitante:

                                    <strong>
                                        <?= htmlspecialchars(
                                            $compra['correo_solicitante']
                                                ?: 'No encontrado'
                                        ) ?>
                                    </strong>

                                </small>

                            </div>

                        </div>

                    </div>


                </div>



                <!-- BOTONES -->

                <div
                    class="
                d-flex
                justify-content-between
                mt-4
                pt-4
                border-top">

                    <a
                        href="./alertas.php"
                        class="btn btn-light">

                        <i
                            class="
                        bi
                        bi-arrow-left
                        me-2"></i>

                        Cancelar

                    </a>


                    <button
                        type="submit"
                        class="
                    btn
                    btn-primary">

                        <i
                            class="
                        bi
                        bi-save
                        me-2"></i>

                        Guardar seguimiento

                    </button>

                </div>


            </form>

        </div>


    </div>



    <!-- BOOTSTRAP -->

    <script
        src="
    https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


</body>

</html>