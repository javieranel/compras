<?php

/**
 * ============================================================
 * GUARDAR ATRASO
 * ============================================================
 * 
 * 1. Recibe el ID por GET o POST
 * 2. Busca la compra
 * 3. Muestra formulario de observación
 * 4. Guarda la observación
 * 5. Envía correo al solicitante
 *
 * ============================================================
 */


/*
|--------------------------------------------------------------------------
| PHPMailer
|--------------------------------------------------------------------------
*/

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';


/*
|--------------------------------------------------------------------------
| CONEXIÓN
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/conexion.php';


if (!isset($con) || !($con instanceof mysqli)) {

    die("Error: no existe una conexión válida a la base de datos.");
}

$con->set_charset("utf8mb4");


/*
|--------------------------------------------------------------------------
| RECIBIR ID
|--------------------------------------------------------------------------
|
| Aceptamos:
|
| GET:
| guardar_atraso.php?id=982
|
| POST:
| <input type="hidden" name="id" value="982">
|
|--------------------------------------------------------------------------
*/

$id_recibido = $_POST['id'] ?? $_GET['id'] ?? '';


/*
|--------------------------------------------------------------------------
| VALIDAR ID
|--------------------------------------------------------------------------
*/

$id = filter_var(
    $id_recibido,
    FILTER_VALIDATE_INT
);


if (!$id || $id <= 0) {

?>

    <!DOCTYPE html>

    <html lang="es">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0">

        <title>ID inválido</title>


        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
            rel="stylesheet">

        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
            rel="stylesheet">


        <style>
            body {

                background:
                    linear-gradient(135deg,
                        #eef4ff,
                        #f8fbff,
                        #eefaf5);

                min-height: 100vh;

            }

            .error-card {

                max-width: 650px;

                margin: 80px auto;

                border: none;

                border-radius: 25px;

                overflow: hidden;

            }
        </style>

    </head>


    <body>


        <div class="container">

            <div class="card error-card shadow-lg">

                <div class="card-body text-center p-5">


                    <div
                        class="text-danger mb-3"
                        style="font-size: 65px;">

                        <i class="bi bi-exclamation-triangle-fill"></i>

                    </div>


                    <h2 class="fw-bold text-danger">

                        ID inválido

                    </h2>


                    <p class="text-muted fs-5">

                        No se recibió un ID de compra válido.

                    </p>


                    <div class="alert alert-light border mt-4">

                        <strong>
                            ID recibido:
                        </strong>

                        <br>

                        <code>

                            <?= htmlspecialchars(
                                (string)$id_recibido
                            ) ?>

                        </code>

                    </div>


                    <a
                        href="../index.php"
                        class="btn btn-primary rounded-pill px-4 mt-3">

                        <i class="bi bi-house me-2"></i>

                        Volver al inicio

                    </a>


                </div>

            </div>

        </div>


    </body>

    </html>

<?php

    exit;
}


/*
|--------------------------------------------------------------------------
| BUSCAR COMPRA
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        fecha_solicitud,
        solicitud,
        categoria,
        area,
        solicitante,
        descripcion,
        proveedor,
        orden_compra,
        llego_terminal,
        llego_isla,
        fecha_llegada,
        fecha_llegada_isla,
        status_entrega,
        fecha_cierre_oc,
        contador_dias,
        alertas_dias,
        comentario
    FROM compras_detalle
    WHERE id = ?
    LIMIT 1
";


$stmt = $con->prepare($sql);


if (!$stmt) {

    die("Error preparando consulta: "
        . $con->error);
}


$stmt->bind_param(
    "i",
    $id
);


if (!$stmt->execute()) {

    die("Error ejecutando consulta: "
        . $stmt->error);
}


$resultado = $stmt->get_result();

$compra = $resultado->fetch_assoc();

$stmt->close();


/*
|--------------------------------------------------------------------------
| VERIFICAR COMPRA
|--------------------------------------------------------------------------
*/

if (!$compra) {

    die("

        <div style='
            font-family:Arial;
            max-width:650px;
            margin:60px auto;
            padding:30px;
            text-align:center;
            background:#f8d7da;
            color:#842029;
            border-radius:15px;
        '>

            <h2>
                ❌ Compra no encontrada
            </h2>

            <p>
                No existe una compra con el ID:
                <strong>{$id}</strong>
            </p>

        </div>

    ");
}


/*
|--------------------------------------------------------------------------
| DATOS
|--------------------------------------------------------------------------
*/

$orden_compra = $compra['orden_compra'] ?? '';

$categoria = $compra['categoria'] ?? '';

$area = $compra['area'] ?? '';

$solicitante = $compra['solicitante'] ?? '';

$proveedor = $compra['proveedor'] ?? '';

$alertas_dias = (int)(
    $compra['alertas_dias'] ?? 0
);

$comentario_actual = $compra['comentario'] ?? '';




/*
|--------------------------------------------------------------------------
| COMENTARIO RECIBIDO
|--------------------------------------------------------------------------
*/

$comentario = trim(
    $_POST['comentario'] ?? ''
);


/*
|--------------------------------------------------------------------------
| MOSTRAR FORMULARIO
|--------------------------------------------------------------------------
|
| Si todavía no se ha enviado el comentario,
| mostramos el formulario.
|
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

?>


    <!DOCTYPE html>

    <html lang="es">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0">

        <title>
            Registrar atraso
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
                    linear-gradient(135deg,
                        #eef4ff,
                        #f8fbff,
                        #eefaf5);

                min-height: 100vh;

            }


            .contenedor {

                max-width: 850px;

                margin: 60px auto;

            }


            .card {

                border: none;

                border-radius: 25px;

                overflow: hidden;

            }


            .header {

                background:
                    linear-gradient(135deg,
                        #dc3545,
                        #fd7e14);

                color: white;

                padding: 35px;

            }


            .icono {

                width: 70px;

                height: 70px;

                border-radius: 50%;

                background: rgba(255, 255, 255, .20);

                display: flex;

                align-items: center;

                justify-content: center;

                font-size: 35px;

                margin-bottom: 15px;

            }


            .dato {

                background: #f8f9fa;

                border-radius: 15px;

                padding: 15px;

                height: 100%;

            }


            .dato-label {

                font-size: .75rem;

                text-transform: uppercase;

                color: #6c757d;

                font-weight: bold;

            }


            .dato-valor {

                font-size: 1.05rem;

                font-weight: 600;

                margin-top: 5px;

            }
        </style>

    </head>


    <body>


        <div class="container contenedor">


            <div class="card shadow-lg">


                <!-- ENCABEZADO -->

                <div class="header">


                    <div class="icono">

                        <i class="bi bi-exclamation-triangle-fill"></i>

                    </div>


                    <h2 class="fw-bold">

                        Registrar atraso

                    </h2>


                    <p class="mb-0 opacity-75">

                        Agrega una observación a la orden de compra.

                    </p>


                </div>


                <!-- CUERPO -->

                <div class="card-body p-4 p-md-5">


                    <div class="row g-3 mb-4">


                        <!-- ID -->

                        <div class="col-md-4">

                            <div class="dato">

                                <div class="dato-label">
                                    ID
                                </div>

                                <div class="dato-valor text-primary">

                                    #<?= $id ?>

                                </div>

                            </div>

                        </div>


                        <!-- OC -->

                        <div class="col-md-4">

                            <div class="dato">

                                <div class="dato-label">
                                    Orden de compra
                                </div>

                                <div class="dato-valor">

                                    <?= htmlspecialchars(
                                        $orden_compra
                                    ) ?>

                                </div>

                            </div>

                        </div>


                        <!-- CATEGORÍA -->

                        <div class="col-md-4">

                            <div class="dato">

                                <div class="dato-label">
                                    Categoría
                                </div>

                                <div class="dato-valor">

                                    <?= htmlspecialchars(
                                        $categoria
                                    ) ?>

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

                                    <?= htmlspecialchars(
                                        $solicitante
                                    ) ?>

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

                                    <?= htmlspecialchars(
                                        $proveedor
                                    ) ?>

                                </div>

                            </div>

                        </div>


                        <!-- DÍAS -->

                        <div class="col-md-6">

                            <div class="dato">

                                <div class="dato-label">
                                    Días de alerta
                                </div>

                                <div class="dato-valor text-danger">

                                    <?= $alertas_dias ?> días

                                </div>

                            </div>

                        </div>


                    </div>


                    <!-- FORMULARIO -->

                    <form
                        method="POST"
                        action="guardar_atraso.php">


                        <!-- ID OCULTO -->

                        <input
                            type="hidden"
                            name="id"
                            value="<?= $id ?>">


                        <div class="mb-4">


                            <label
                                class="form-label fw-bold"
                                for="comentario">

                                <i class="bi bi-chat-left-text me-1"></i>

                                Observación del atraso

                            </label>


                            <textarea
                                class="form-control"
                                id="comentario"
                                name="comentario"
                                rows="5"
                                required
                                placeholder="Escriba aquí la observación..."><?= htmlspecialchars(
                                                                                    $comentario_actual
                                                                                ) ?></textarea>


                        </div>


                        <div class="d-flex gap-2">


                            <button
                                type="submit"
                                class="btn btn-danger px-4">

                                <i class="bi bi-save me-2"></i>

                                Guardar atraso

                            </button>


                            <a
                                href="../view/panel_oc.php?id=<?= $id ?>"
                                class="btn btn-secondary px-4">

                                <i class="bi bi-x-circle me-2"></i>

                                Cancelar

                            </a>


                        </div>


                    </form>


                </div>


            </div>


        </div>


    </body>

    </html>


<?php

    exit;
}


/*
|--------------------------------------------------------------------------
| VALIDAR COMENTARIO
|--------------------------------------------------------------------------
*/

if ($comentario === '') {

    die("Debe escribir una observación.");
}


/*
|--------------------------------------------------------------------------
| GUARDAR OBSERVACIÓN
|--------------------------------------------------------------------------
*/

$sqlUpdate = "
    UPDATE compras_detalle
    SET comentario = ?
    WHERE id = ?
";


$stmtUpdate = $con->prepare(
    $sqlUpdate
);


if (!$stmtUpdate) {

    die("Error preparando actualización: "
        . $con->error);
}


$stmtUpdate->bind_param(
    "si",
    $comentario,
    $id
);


if (!$stmtUpdate->execute()) {

    die("Error guardando observación: "
        . $stmtUpdate->error);
}


$filasAfectadas = $stmtUpdate->affected_rows;


$stmtUpdate->close();


/*
|--------------------------------------------------------------------------
| CORREO DEL SOLICITANTE
|--------------------------------------------------------------------------
|
| IMPORTANTE:
|
| Actualmente tu tabla que mostraste tiene:
|
| solicitante VARCHAR(100)
|
| pero NO tiene correo_solicitante.
|
| Por eso dejamos esta variable preparada.
|
|--------------------------------------------------------------------------
*/

//$correoSolicitante = '';
$correoSolicitante = trim(
    $_POST['correo_solicitante'] ?? ''
);

/*
|--------------------------------------------------------------------------
| SI YA TIENES UN CORREO FIJO PARA PRUEBA
|--------------------------------------------------------------------------
|
| Puedes temporalmente colocar aquí un correo:
|
$correoSolicitante = trim(
    $_POST['correo_solicitante'] ?? ''
);
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| ENVÍO DEL CORREO
|--------------------------------------------------------------------------
*/



$correoEnviado = false;

$errorCorreo = '';


if ($correoSolicitante !== '') {


    try {


        $mail = new PHPMailer(true);


        /*
        |--------------------------------------------------------------------------
        | SMTP
        |--------------------------------------------------------------------------
        */

        $mail->isSMTP();

        $mail->Host = 'smtp.office365.com';

        $mail->SMTPAuth = true;

        $mail->Username =
            'notificacion@melonesoilterminal.com';

        $mail->Password =
            'n@x1LTm#';

        $mail->SMTPSecure =
            PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port = 587;


        /*
        |--------------------------------------------------------------------------
        | REMITENTE
        |--------------------------------------------------------------------------
        */

        $mail->setFrom(
            'notificacion@melonesoilterminal.com',
            'Seguimiento de Compras'
        );


        /*
        |--------------------------------------------------------------------------
        | DESTINATARIO
        |--------------------------------------------------------------------------
        */

        $mail->addAddress(
            $correoSolicitante,
            $solicitante
        );


        /*
        |--------------------------------------------------------------------------
        | HTML
        |--------------------------------------------------------------------------
        */

        $mail->isHTML(true);

        $mail->CharSet = 'UTF-8';


        $mail->Subject =
            '⚠️ Atraso registrado - OC #'
            . $orden_compra;


        $cuerpo = '

        <div style="
            font-family:Arial,sans-serif;
            max-width:650px;
            margin:auto;
            border:1px solid #ddd;
            border-radius:15px;
            overflow:hidden;
            background:#f8f9fa;
        ">


            <div style="
                background:#dc3545;
                color:#fff;
                padding:30px;
                text-align:center;
            ">

                <h2 style="margin:0;">
                    ⚠️ Atraso registrado
                </h2>

                <p style="margin-top:10px;">
                    Sistema de Seguimiento de Compras
                </p>

            </div>


            <div style="padding:30px;">

                <p>

                    Hola
                    <strong>'
            . htmlspecialchars($solicitante)
            . '</strong>,

                </p>


                <p>

                    Se ha registrado un atraso
                    relacionado con la siguiente
                    orden de compra:

                </p>


                <table style="
                    width:100%;
                    border-collapse:collapse;
                    margin-top:20px;
                ">


                    <tr>

                        <td style="
                            padding:10px;
                            border-bottom:1px solid #ddd;
                            font-weight:bold;
                        ">
                            ID
                        </td>

                        <td style="
                            padding:10px;
                            border-bottom:1px solid #ddd;
                        ">
                            #'
            . $id .
            '
                        </td>

                    </tr>


                    <tr>

                        <td style="
                            padding:10px;
                            border-bottom:1px solid #ddd;
                            font-weight:bold;
                        ">
                            Orden de compra
                        </td>

                        <td style="
                            padding:10px;
                            border-bottom:1px solid #ddd;
                        ">
                            '
            . htmlspecialchars(
                $orden_compra
            )
            . '
                        </td>

                    </tr>


                    <tr>

                        <td style="
                            padding:10px;
                            border-bottom:1px solid #ddd;
                            font-weight:bold;
                        ">
                            Categoría
                        </td>

                        <td style="
                            padding:10px;
                            border-bottom:1px solid #ddd;
                        ">
                            '
            . htmlspecialchars(
                $categoria
            )
            . '
                        </td>

                    </tr>


                    <tr>

                        <td style="
                            padding:10px;
                            border-bottom:1px solid #ddd;
                            font-weight:bold;
                        ">
                            Días de atraso
                        </td>

                        <td style="
                            padding:10px;
                            border-bottom:1px solid #ddd;
                            color:#dc3545;
                            font-weight:bold;
                        ">
                            '
            . $alertas_dias .
            ' días
                        </td>

                    </tr>


                </table>


                <div style="
                    margin-top:20px;
                    padding:15px;
                    background:#fff3cd;
                    border:1px solid #ffecb5;
                    border-radius:10px;
                ">

                    <strong>
                        Observación:
                    </strong>

                    <p style="margin-top:8px;">

                        '
            . nl2br(
                htmlspecialchars(
                    $comentario
                )
            )
            . '

                    </p>

                </div>


                <div style="
                    text-align:center;
                    margin-top:30px;
                ">


                    <a
                        href="https://apps.melonesoilterminal.com/compras/seguimiento/view/panel_oc.php?id='
            . $id .
            '"
                        style="
                            display:inline-block;
                            background:#0d6efd;
                            color:white;
                            text-decoration:none;
                            padding:12px 25px;
                            border-radius:8px;
                            font-weight:bold;
                        "
                    >

                        Ver orden de compra

                    </a>


                </div>


                <p style="
                    text-align:center;
                    color:#777;
                    font-size:12px;
                    margin-top:30px;
                ">

                    Este es un correo automático.
                    Por favor no responda a este mensaje.

                </p>


            </div>

        </div>

        ';


        $mail->Body = $cuerpo;


        /*
        |--------------------------------------------------------------------------
        | ENVIAR
        |--------------------------------------------------------------------------
        */

        $mail->send();


        $correoEnviado = true;
    } catch (Exception $e) {


        $correoEnviado = false;

        $errorCorreo =
            $mail->ErrorInfo;
    }
}


/*
|--------------------------------------------------------------------------
| CERRAR CONEXIÓN
|--------------------------------------------------------------------------
*/

$con->close();

?>


<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Atraso registrado
    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet">


    <style>
        body {

            background:
                linear-gradient(135deg,
                    #eef4ff,
                    #f8fbff,
                    #eefaf5);

            min-height: 100vh;

        }


        .contenedor {

            max-width: 750px;

            margin: 70px auto;

        }


        .card {

            border: none;

            border-radius: 25px;

            overflow: hidden;

        }


        .header {

            background:
                linear-gradient(135deg,
                    #198754,
                    #20c997);

            color: #fff;

            padding: 40px;

            text-align: center;

        }


        .icono {

            width: 80px;

            height: 80px;

            background: rgba(255, 255, 255, .2);

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 42px;

            margin: 0 auto 20px;

        }


        .dato {

            background: #f8f9fa;

            border-radius: 15px;

            padding: 15px;

        }


        .dato-label {

            color: #6c757d;

            font-size: .75rem;

            text-transform: uppercase;

            font-weight: bold;

        }


        .dato-valor {

            font-size: 1.05rem;

            font-weight: 600;

            margin-top: 5px;

        }
    </style>

</head>


<body>


    <div class="container contenedor">


        <div class="card shadow-lg">


            <div class="header">


                <div class="icono">

                    <i class="bi bi-check-lg"></i>

                </div>


                <h2 class="fw-bold">

                    ¡Atraso registrado!

                </h2>


                <p class="mb-0 opacity-75">

                    La observación fue guardada correctamente.

                </p>


            </div>


            <div class="card-body p-4 p-md-5">


                <div class="row g-3">


                    <div class="col-md-6">

                        <div class="dato">

                            <div class="dato-label">
                                ID de compra
                            </div>

                            <div class="dato-valor text-primary">

                                #<?= $id ?>

                            </div>

                        </div>

                    </div>


                    <div class="col-md-6">

                        <div class="dato">

                            <div class="dato-label">
                                Orden de compra
                            </div>

                            <div class="dato-valor">

                                <?= htmlspecialchars(
                                    $orden_compra
                                ) ?>

                            </div>

                        </div>

                    </div>


                    <div class="col-md-6">

                        <div class="dato">

                            <div class="dato-label">
                                Solicitante
                            </div>

                            <div class="dato-valor">

                                <?= htmlspecialchars(
                                    $solicitante
                                ) ?>

                            </div>

                        </div>

                    </div>


                    <div class="col-md-6">

                        <div class="dato">

                            <div class="dato-label">
                                Días de atraso
                            </div>

                            <div class="dato-valor text-danger">

                                <?= $alertas_dias ?> días

                            </div>

                        </div>

                    </div>


                    <div class="col-12">

                        <div class="alert alert-warning">

                            <strong>

                                <i class="bi bi-chat-left-text me-2"></i>

                                Observación:

                            </strong>


                            <div class="mt-2">

                                <?= nl2br(
                                    htmlspecialchars(
                                        $comentario
                                    )
                                ) ?>

                            </div>

                        </div>

                    </div>


                    <?php if ($correoEnviado): ?>

                        <div class="col-12">

                            <div class="alert alert-success">

                                <i class="bi bi-envelope-check-fill me-2"></i>

                                <strong>
                                    Correo enviado correctamente
                                </strong>

                                <br>

                                Se notificó al solicitante:

                                <strong>
                                    <?= htmlspecialchars($correoSolicitante) ?>
                                </strong>

                            </div>

                        </div>

                    <?php elseif ($correoSolicitante === ''): ?>

                        <div class="col-12">

                            <div class="alert alert-warning">

                                <i class="bi bi-envelope-exclamation me-2"></i>

                                <strong>
                                    Atraso guardado.
                                </strong>

                                <br>

                                No se recibió el correo del solicitante.

                            </div>

                        </div>

                    <?php else: ?>

                        <div class="col-12">

                            <div class="alert alert-danger">

                                <i class="bi bi-envelope-x-fill me-2"></i>

                                <strong>
                                    Atraso guardado, pero el correo no pudo enviarse.
                                </strong>

                                <br>

                                Destinatario:

                                <strong>
                                    <?= htmlspecialchars($correoSolicitante) ?>
                                </strong>

                                <?php if ($errorCorreo !== ''): ?>

                                    <hr>

                                    <strong>
                                        Error de PHPMailer:
                                    </strong>

                                    <br>

                                    <code>
                                        <?= htmlspecialchars($errorCorreo) ?>
                                    </code>

                                <?php endif; ?>

                            </div>

                        </div>

                    <?php endif; ?>


                </div>


                <div class="text-center mt-4">


                    <a
                        href="../view/alertas.php"
                        class="btn btn-primary rounded-pill px-4">

                        <i class="bi bi-arrow-left me-2"></i>

                        Volver a la orden

                    </a>


                </div>


            </div>


        </div>


    </div>


</body>

</html>