<?php

require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Método no permitido');
}


/*
|--------------------------------------------------------------------------
| ID
|--------------------------------------------------------------------------
*/

$id = $_POST['id'] ?? null;

if (!$id) {
    die('Error: No se recibió el ID de la compra.');
}


/*
|--------------------------------------------------------------------------
| Datos del formulario
|--------------------------------------------------------------------------
*/

$fecha_solicitud = $_POST['fecha_solicitud'] ?? null;
$solicitud = $_POST['solicitud'] ?? '';
$categoria = $_POST['categoria'] ?? '';
$solicitante = $_POST['solicitante'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$proveedor = $_POST['proveedor'] ?? '';
$orden_compra = $_POST['orden_compra'] ?? '';

$llego_terminal = $_POST['llego_terminal'] ?? 'No';


$llego_isla = $_POST['llego_isla'] ?? 'No';


/*
|--------------------------------------------------------------------------
| Fechas
|--------------------------------------------------------------------------
*/

$fecha_llegada = !empty($_POST['fecha_llegada'])
    ? $_POST['fecha_llegada']
    : null;

$fecha_llegada_isla = !empty($_POST['fecha_llegada_isla'])
    ? $_POST['fecha_llegada_isla']
    : null;


/*
|--------------------------------------------------------------------------
| ESTADO DE LA SOLICITUD
|--------------------------------------------------------------------------
|
| Aquí ya NO evaluamos terminal ni isla.
|
*/

$status_entrega = $_POST['status_entrega'] ?? 'Pendiente';


/*
|--------------------------------------------------------------------------
| FECHA DE CIERRE
|--------------------------------------------------------------------------
|
| Solamente se registra cuando la solicitud
| está marcada como Completada.
|
*/

if ($status_entrega === 'Completada') {

    $fecha_cierre_oc = date('Y-m-d H:i:s');

} else {

    $fecha_cierre_oc = null;

}


/*
|--------------------------------------------------------------------------
| ACTUALIZAR
|--------------------------------------------------------------------------
*/

$sql = "UPDATE compras_detalle SET

    fecha_solicitud = ?,
    solicitud = ?,
    categoria = ?,
    solicitante = ?,
    descripcion = ?,
    proveedor = ?,
    orden_compra = ?,

    fecha_llegada = ?,

    llego_terminal = ?,
    llego_isla = ?,

    fecha_llegada_isla = ?,

    status_entrega = ?,

    fecha_cierre_oc = ?

    WHERE id = ?";


$stmt = $con->prepare($sql);


if (!$stmt) {

    die(
        'Error preparando consulta: '
        . $con->error
    );

}


/*
|--------------------------------------------------------------------------
| BIND
|--------------------------------------------------------------------------
*/

$stmt->bind_param(
    "sssssssssssssi",

    $fecha_solicitud,
    $solicitud,
    $categoria,
    $solicitante,
    $descripcion,
    $proveedor,
    $orden_compra,

    $fecha_llegada,

    $llego_terminal,
    $llego_isla,

    $fecha_llegada_isla,

    $status_entrega,

    $fecha_cierre_oc,

    $id
);


/*
|--------------------------------------------------------------------------
| EJECUTAR
|--------------------------------------------------------------------------
*/

if (!$stmt->execute()) {

    die(
        'Error actualizando la compra: '
        . $stmt->error
    );

}


$filas_afectadas = $stmt->affected_rows;


/*
|--------------------------------------------------------------------------
| DATOS PARA PANTALLA DE RESULTADO
|--------------------------------------------------------------------------
*/

$stmt->close();


/*
|--------------------------------------------------------------------------
| Volvemos a consultar la compra
|--------------------------------------------------------------------------
|
| Esto es importante.
| Así mostramos exactamente lo que quedó guardado
| en MariaDB.
|
*/

$sql_verificar = "
    SELECT
        id,
        fecha_solicitud,
        categoria,
        solicitante,
        proveedor,
        orden_compra,
        llego_terminal,
        llego_isla,
        fecha_llegada_isla,
        status_entrega,
        fecha_cierre_oc
    FROM compras_detalle
    WHERE id = ?
";


$stmt_verificar = $con->prepare($sql_verificar);

if (!$stmt_verificar) {

    die(
        'Error verificando actualización: '
        . $con->error
    );

}


$stmt_verificar->bind_param(
    "i",
    $id
);


$stmt_verificar->execute();


$resultado = $stmt_verificar->get_result();


if ($resultado->num_rows === 0) {

    die('No se encontró la compra después de actualizar.');

}


$compra = $resultado->fetch_assoc();


$stmt_verificar->close();
$con->close();


/*
|--------------------------------------------------------------------------
| Variables para pantalla
|--------------------------------------------------------------------------
*/

$id = $compra['id'];

$fecha_solicitud = $compra['fecha_solicitud'];

$categoria = $compra['categoria'];

$solicitante = $compra['solicitante'];

$proveedor = $compra['proveedor'];

$orden_compra = $compra['orden_compra'];

$llego_terminal = $compra['llego_terminal'];

$llego_isla = $compra['llego_isla'];

$fecha_llegada_isla = $compra['fecha_llegada_isla'];

$status_entrega = $compra['status_entrega'];

$fecha_cierre_oc = $compra['fecha_cierre_oc'];


/*
|--------------------------------------------------------------------------
| Función para mostrar datos
|--------------------------------------------------------------------------
*/

function mostrarDato($dato)
{

    if (
        $dato === null ||
        $dato === ''
    ) {

        return '<span class="text-muted">
                    No registrado
                </span>';

    }

    return htmlspecialchars(
        $dato,
        ENT_QUOTES,
        'UTF-8'
    );

}


/*
|--------------------------------------------------------------------------
| CARGAR PANTALLA
|--------------------------------------------------------------------------
*/

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Compra actualizada</title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <style>

        body {

            background:
                linear-gradient(
                    135deg,
                    #eef4ff 0%,
                    #f8fbff 50%,
                    #eefaf5 100%
                );

            min-height: 100vh;

        }


        .resultado-container {

            max-width: 850px;

            margin: 60px auto;

        }


        .resultado-card {

            border: none;

            border-radius: 25px;

            overflow: hidden;

            box-shadow:
                0 15px 45px
                rgba(0,0,0,.10);

        }


        .resultado-header {

            background:
                linear-gradient(
                    135deg,
                    #0d6efd,
                    #2563eb,
                    #4f46e5
                );

            color: white;

            padding: 35px;

        }


        .check-icon {

            width: 75px;

            height: 75px;

            border-radius: 50%;

            background:
                rgba(255,255,255,.20);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 40px;

            margin-bottom: 15px;

        }


        .dato {

            background: #f8f9fa;

            border-radius: 15px;

            padding: 15px 18px;

            height: 100%;

        }


        .dato-label {

            font-size: .78rem;

            text-transform: uppercase;

            color: #6c757d;

            font-weight: 700;

            letter-spacing: .5px;

        }


        .dato-valor {

            font-size: 1.05rem;

            font-weight: 600;

            margin-top: 4px;

            word-break: break-word;

        }


        .estado {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 8px 16px;

            border-radius: 50px;

            font-weight: 700;

        }


        .estado-completada {

            background: #d1e7dd;

            color: #0f5132;

        }


        .estado-parcial {

            background: #fff3cd;

            color: #664d03;

        }


        .estado-pendiente {

            background: #f8d7da;

            color: #842029;

        }

    </style>

</head>


<body>


<div class="resultado-container">

    <div class="card resultado-card">


        <!-- HEADER -->

        <div class="resultado-header">

            <div class="check-icon">

                <i class="bi bi-check-lg"></i>

            </div>


            <h2 class="fw-bold mb-2">

                ¡Compra actualizada!

            </h2>


            <p class="mb-0 opacity-75">

                La información fue guardada correctamente.

            </p>

        </div>


        <!-- BODY -->

        <div class="card-body p-4 p-md-5">


            <div class="row g-3">


                <!-- ID -->

                <div class="col-md-4">

                    <div class="dato">

                        <div class="dato-label">

                            ID de compra

                        </div>

                        <div class="dato-valor text-primary">

                            #<?= $id ?>

                        </div>

                    </div>

                </div>


                <!-- CATEGORIA -->

                <div class="col-md-8">

                    <div class="dato">

                        <div class="dato-label">

                            Categoría

                        </div>

                        <div class="dato-valor">

                            <?= mostrarDato($categoria) ?>

                        </div>

                    </div>

                </div>


                <!-- SOLICITANTE -->

                <div class="col-md-6">

                    <div class="dato">

                        <div class="dato-label">

                            Solicitante

                        </div>

                        <div class="dato-valor">

                            <?= mostrarDato($solicitante) ?>

                        </div>

                    </div>

                </div>


                <!-- PROVEEDOR -->

                <div class="col-md-6">

                    <div class="dato">

                        <div class="dato-label">

                            Proveedor

                        </div>

                        <div class="dato-valor">

                            <?= mostrarDato($proveedor) ?>

                        </div>

                    </div>

                </div>


                <!-- ORDEN -->

                <div class="col-md-6">

                    <div class="dato">

                        <div class="dato-label">

                            Orden de compra

                        </div>

                        <div class="dato-valor">

                            <?= mostrarDato($orden_compra) ?>

                        </div>

                    </div>

                </div>


                <!-- FECHA SOLICITUD -->

                <div class="col-md-6">

                    <div class="dato">

                        <div class="dato-label">

                            Fecha de solicitud

                        </div>

                        <div class="dato-valor">

                            <?= mostrarDato($fecha_solicitud) ?>

                        </div>

                    </div>

                </div>


                <!-- TERMINAL -->

                <div class="col-md-4">

                    <div class="dato">

                        <div class="dato-label">

                            Llegó a terminal

                        </div>

                        <div class="dato-valor">

                            <?= mostrarDato($llego_terminal) ?>

                        </div>

                    </div>

                </div>


                <!-- ISLA -->

                <div class="col-md-4">

                    <div class="dato">

                        <div class="dato-label">

                            Llegó a isla

                        </div>

                        <div class="dato-valor">

                            <?= mostrarDato($llego_isla) ?>

                        </div>

                    </div>

                </div>


                <!-- FECHA ISLA -->

                <div class="col-md-4">

                    <div class="dato">

                        <div class="dato-label">

                            Fecha llegada isla

                        </div>

                        <div class="dato-valor">

                            <?= mostrarDato($fecha_llegada_isla) ?>

                        </div>

                    </div>

                </div>


                <!-- ESTADO -->

                <div class="col-12">

                    <div class="dato">

                        <div class="dato-label mb-2">

                            Estado actual

                        </div>


                        <?php

                        $clase_estado =
                            'estado-pendiente';

                        $icono_estado =
                            'bi-clock-fill';


                        if (
                            $status_entrega ===
                            'Completada'
                        ) {

                            $clase_estado =
                                'estado-completada';

                            $icono_estado =
                                'bi-check-circle-fill';

                        } elseif (
                            $status_entrega ===
                            'Entrega Parcial'
                        ) {

                            $clase_estado =
                                'estado-parcial';

                            $icono_estado =
                                'bi-hourglass-split';

                        }

                        ?>


                        <span
                            class="estado
                            <?= $clase_estado ?>"
                        >

                            <i
                                class="bi
                                <?= $icono_estado ?>"
                            ></i>

                            <?= mostrarDato($status_entrega) ?>

                        </span>

                    </div>

                </div>


                <!-- FECHA CIERRE -->

                <div class="col-md-6">

                    <div class="dato">

                        <div class="dato-label">

                            Fecha de cierre OC

                        </div>

                        <div class="dato-valor text-success">

                            <?php if (
                                !empty($fecha_cierre_oc)
                            ): ?>

                                <i
                                    class="bi bi-calendar-check me-1"
                                ></i>

                                <?= mostrarDato($fecha_cierre_oc) ?>

                            <?php else: ?>

                                <span class="text-muted">

                                    Aún no cerrada

                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>


                <!-- ACTUALIZACIÓN -->

                <div class="col-md-6">

                    <div class="dato">

                        <div class="dato-label">

                            Registro actualizado

                        </div>

                        <div class="dato-valor">

                            <span class="text-success">

                                <i
                                    class="bi bi-check-circle me-1"
                                ></i>

                                Cambios guardados correctamente

                            </span>

                        </div>

                    </div>

                </div>

            </div>


            <!-- BOTONES -->

            <div
                class="d-flex
                       flex-wrap
                       gap-2
                       justify-content-center
                       mt-5"
            >

                <a
                    href="../view/editar_compra.php"
                    class="btn btn-primary px-4 rounded-pill"
                >

                    <i
                        class="bi bi-pencil-square me-2"
                    ></i>

                    Seguir editando

                </a>


                <a
                    href="../index.php"
                    class="btn btn-outline-secondary px-4 rounded-pill"
                >

                    <i class="bi bi-house me-2"></i>

                    Ir al inicio

                </a>

            </div>

        </div>

    </div>

</div>


<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>