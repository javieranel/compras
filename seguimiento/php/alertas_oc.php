<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

require_once __DIR__ . '/conexion.php';


// ============================================================
// CONFIGURACIÓN
// ============================================================

$DIAS_ISLA = 15;
$DIAS_SERVICIOS = 15;
$MESES_IMPORTACIONES = 2;

$correo_remitente = 'notificacion@melonesoilterminal.com';
$correo_destino   = 'rdelgado@melonesterminal.com';
$correo_destino   = 'jtapia@melonesterminal.com';

// IMPORTANTE:
// Coloca aquí la contraseña NUEVA de la cuenta de correo,
// o mejor aún, utiliza una variable de entorno.
$correo_password = getenv('n@x1LTm#');



// ============================================================
// VARIABLES
// ============================================================

$alertas_isla = [];
$alertas_servicios = [];
$alertas_importaciones = [];


// ============================================================
// 1. ISLA MOTI
//
// destino = 1
//
// Se calcula:
// fecha_aprobada -> fecha_llegada_isla
//
// Si todavía no llega y han pasado más de 15 días,
// genera alerta.
// ============================================================

$sql_isla = "
    SELECT
        id,
        orden_compra,
        categoria,
        destino,
        area,
        solicitante,
        fecha_aprobada,
        fecha_llegada_isla,
        DATEDIFF(CURDATE(), fecha_aprobada) AS dias_transcurridos
    FROM compras_detalle
    WHERE destino = '1'
      AND fecha_aprobada IS NOT NULL
      AND fecha_llegada_isla IS NULL
      AND DATEDIFF(CURDATE(), fecha_aprobada) > $DIAS_ISLA
      AND status_entrega <> 'Entregado'
";

$res_isla = $con->query($sql_isla);

if ($res_isla) {

    while ($row = $res_isla->fetch_assoc()) {
        $alertas_isla[] = $row;
    }

}


// ============================================================
// 2. SERVICIOS
//
// Se calcula:
// fecha_aprobada -> fecha_llegada
//
// Más de 15 días genera alerta.
// ============================================================

$sql_servicios = "
    SELECT
        id,
        orden_compra,
        categoria,
        destino,
        area,
        solicitante,
        fecha_aprobada,
        fecha_llegada,
        DATEDIFF(CURDATE(), fecha_aprobada) AS dias_transcurridos
    FROM compras_detalle
    WHERE LOWER(TRIM(categoria)) = 'servicios'
      AND fecha_aprobada IS NOT NULL
      AND fecha_llegada IS NULL
      AND DATEDIFF(CURDATE(), fecha_aprobada) > $DIAS_SERVICIOS
      AND status_entrega <> 'Entregado'
";

$res_servicios = $con->query($sql_servicios);

if ($res_servicios) {

    while ($row = $res_servicios->fetch_assoc()) {
        $alertas_servicios[] = $row;
    }

}


// ============================================================
// 3. IMPORTACIONES
//
// Se calcula:
// fecha_aprobada -> fecha_llegada
//
// Más de 2 meses genera alerta.
// ============================================================

$sql_importaciones = "
    SELECT
        id,
        orden_compra,
        categoria,
        destino,
        area,
        solicitante,
        fecha_aprobada,
        fecha_llegada,

        DATEDIFF(CURDATE(), fecha_aprobada)
            AS dias_transcurridos,

        TIMESTAMPDIFF(
            MONTH,
            fecha_aprobada,
            CURDATE()
        ) AS meses_transcurridos

    FROM compras_detalle

    WHERE LOWER(TRIM(categoria)) = 'importaciones'
      AND fecha_aprobada IS NOT NULL
      AND fecha_llegada IS NULL

      AND TIMESTAMPDIFF(
            MONTH,
            fecha_aprobada,
            CURDATE()
          ) >= $MESES_IMPORTACIONES

      AND status_entrega <> 'Entregado'
";

$res_importaciones = $con->query($sql_importaciones);

if ($res_importaciones) {

    while ($row = $res_importaciones->fetch_assoc()) {
        $alertas_importaciones[] = $row;
    }

}


// ============================================================
// CONTAR ALERTAS
// ============================================================

$total_isla = count($alertas_isla);
$total_servicios = count($alertas_servicios);
$total_importaciones = count($alertas_importaciones);

$total_alertas =
    $total_isla +
    $total_servicios +
    $total_importaciones;


// ============================================================
// SI NO HAY ALERTAS
// ============================================================

if ($total_alertas === 0) {

    echo "No hay alertas pendientes.";

    exit;
}


// ============================================================
// VALIDAR CONTRASEÑA
// ============================================================

if (empty($correo_password)) {

    echo "ERROR: No está configurada la contraseña del correo.";

    exit;
}


// ============================================================
// CREAR CORREO
// ============================================================

$mail = new PHPMailer(true);

try {

    // --------------------------------------------------------
    // SMTP OFFICE365
    // --------------------------------------------------------

    $mail->isSMTP();

    $mail->Host = 'smtp.office365.com';

    $mail->SMTPAuth = true;

    $mail->Username = $correo_remitente;

    $mail->Password = $correo_password;

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

    $mail->Port = 587;


    // --------------------------------------------------------
    // CONFIGURACIÓN GENERAL
    // --------------------------------------------------------

    $mail->CharSet = 'UTF-8';

    $mail->setFrom(
        $correo_remitente,
        'Sistema de Seguimiento de Compras'
    );

    $mail->addAddress(
        $correo_destino
    );

    $mail->isHTML(true);


    // --------------------------------------------------------
    // ASUNTO
    // --------------------------------------------------------

    $mail->Subject =
        '⚠️ Alertas de Órdenes de Compra - ' .
        $total_alertas .
        ' pendientes';


    // ========================================================
    // INICIO DEL CORREO
    // ========================================================

    $cuerpo = '

    <!DOCTYPE html>

    <html>

    <head>

        <meta charset="UTF-8">

    </head>

    <body>

    <div style="
        font-family: Arial, Helvetica, sans-serif;
        max-width: 900px;
        margin: 0 auto;
        background: #f5f5f5;
        padding: 20px;
    ">

        <div style="
            background: white;
            border-radius: 10px;
            padding: 25px;
            border: 1px solid #ddd;
        ">

            <h2 style="
                text-align: center;
                color: #333;
                margin-bottom: 10px;
            ">
                ⚠️ Alertas de Órdenes de Compra
            </h2>

            <p style="
                text-align: center;
                color: #666;
                font-size: 15px;
            ">
                El sistema detectó órdenes de compra
                que han superado el tiempo establecido.
            </p>

            <div style="
                text-align: center;
                margin: 20px 0;
            ">

                <span style="
                    display: inline-block;
                    background: #dc3545;
                    color: white;
                    padding: 10px 18px;
                    border-radius: 20px;
                    font-weight: bold;
                ">
                    ' . $total_alertas . ' alertas
                </span>

            </div>
    ';


    // ========================================================
    // SECCIÓN ISLA MOTI
    // ========================================================

    if ($total_isla > 0) {

        $cuerpo .= '

        <div style="
            margin-top: 30px;
        ">

            <h3 style="
                background: #dc3545;
                color: white;
                padding: 12px;
                border-radius: 6px;
            ">
                🏝️ Isla MOTI
            </h3>

            <p style="color:#555;">
                Órdenes con más de <strong>
                ' . $DIAS_ISLA . '
                días</strong> desde la aprobación
                y que todavía no han llegado a la isla.
            </p>

            <table style="
                width:100%;
                border-collapse:collapse;
                margin-top:15px;
            ">

                <thead>

                    <tr style="
                        background:#007bff;
                        color:white;
                    ">

                        <th style="padding:10px;">
                            OC
                        </th>

                        <th style="padding:10px;">
                            Categoría
                        </th>

                        <th style="padding:10px;">
                            Área
                        </th>

                        <th style="padding:10px;">
                            Solicitante
                        </th>

                        <th style="padding:10px;">
                            Aprobada
                        </th>

                        <th style="padding:10px;">
                            Días
                        </th>

                        <th style="padding:10px;">
                            Acción
                        </th>

                    </tr>

                </thead>

                <tbody>
        ';


        foreach ($alertas_isla as $row) {

            $cuerpo .= '

                    <tr style="
                        background:#fff;
                        border-bottom:1px solid #ddd;
                    ">

                        <td style="padding:10px;">
                            ' .
                            htmlspecialchars(
                                $row['orden_compra'] ?? ''
                            )
                            . '
                        </td>

                        <td style="padding:10px;">
                            ' .
                            htmlspecialchars(
                                $row['categoria'] ?? ''
                            )
                            . '
                        </td>

                        <td style="padding:10px;">
                            ' .
                            htmlspecialchars(
                                $row['area'] ?? ''
                            )
                            . '
                        </td>

                        <td style="padding:10px;">
                            ' .
                            htmlspecialchars(
                                $row['solicitante'] ?? ''
                            )
                            . '
                        </td>

                        <td style="padding:10px;">
                            ' .
                            htmlspecialchars(
                                $row['fecha_aprobada'] ?? ''
                            )
                            . '
                        </td>

                        <td style="
                            padding:10px;
                            text-align:center;
                            font-weight:bold;
                            color:#dc3545;
                        ">
                            ' .
                            intval(
                                $row['dias_transcurridos']
                            )
                            . '
                        </td>

                        <td style="padding:10px;">

                            <a href="
                            https://apps.melonesoilterminal.com/compras/seguimiento/view/panel_oc.php?id=' .
                            intval($row['id']) .
                            '"
                            style="
                                display:inline-block;
                                background:#28a745;
                                color:#fff;
                                text-decoration:none;
                                padding:8px 12px;
                                border-radius:5px;
                                font-weight:bold;
                            ">
                                Ver OC
                            </a>

                        </td>

                    </tr>
            ';
        }


        $cuerpo .= '

                </tbody>

            </table>

        </div>
        ';
    }


    // ========================================================
    // SECCIÓN SERVICIOS
    // ========================================================

    if ($total_servicios > 0) {

        $cuerpo .= '

        <div style="
            margin-top:35px;
        ">

            <h3 style="
                background:#ff9800;
                color:white;
                padding:12px;
                border-radius:6px;
            ">
                🛠️ Servicios
            </h3>

            <p style="color:#555;">
                Servicios con más de <strong>
                ' . $DIAS_SERVICIOS .
                ' días</strong> desde la aprobación
                y que todavía no han sido cerrados.
            </p>

            <table style="
                width:100%;
                border-collapse:collapse;
                margin-top:15px;
            ">

                <thead>

                    <tr style="
                        background:#ff9800;
                        color:white;
                    ">

                        <th style="padding:10px;">
                            OC
                        </th>

                        <th style="padding:10px;">
                            Área
                        </th>

                        <th style="padding:10px;">
                            Solicitante
                        </th>

                        <th style="padding:10px;">
                            Aprobada
                        </th>

                        <th style="padding:10px;">
                            Días
                        </th>

                        <th style="padding:10px;">
                            Acción
                        </th>

                    </tr>

                </thead>

                <tbody>
        ';


        foreach ($alertas_servicios as $row) {

            $cuerpo .= '

                    <tr style="
                        background:#fff;
                        border-bottom:1px solid #ddd;
                    ">

                        <td style="padding:10px;">
                            ' .
                            htmlspecialchars(
                                $row['orden_compra'] ?? ''
                            )
                            . '
                        </td>

                        <td style="padding:10px;">
                            ' .
                            htmlspecialchars(
                                $row['area'] ?? ''
                            )
                            . '
                        </td>

                        <td style="padding:10px;">
                            ' .
                            htmlspecialchars(
                                $row['solicitante'] ?? ''
                            )
                            . '
                        </td>

                        <td style="padding:10px;">
                            ' .
                            htmlspecialchars(
                                $row['fecha_aprobada'] ?? ''
                            )
                            . '
                        </td>

                        <td style="
                            padding:10px;
                            text-align:center;
                            font-weight:bold;
                            color:#ff9800;
                        ">
                            ' .
                            intval(
                                $row['dias_transcurridos']
                            )
                            . '
                        </td>

                        <td style="padding:10px;">

                            <a href="
                            https://apps.melonesoilterminal.com/compras/seguimiento/view/panel_oc.php?id=' .
                            intval($row['id']) .
                            '"
                            style="
                                display:inline-block;
                                background:#28a745;
                                color:#fff;
                                text-decoration:none;
                                padding:8px 12px;
                                border-radius:5px;
                                font-weight:bold;
                            ">
                                Ver OC
                            </a>

                        </td>

                    </tr>
            ';
        }


        $cuerpo .= '

                </tbody>

            </table>

        </div>
        ';
    }


    // ========================================================
    // SECCIÓN IMPORTACIONES
    // ========================================================

    if ($total_importaciones > 0) {

        $cuerpo .= '

        <div style="
            margin-top:35px;
        ">

            <h3 style="
                background:#6f42c1;
                color:white;
                padding:12px;
                border-radius:6px;
            ">
                🚢 Importaciones
            </h3>

            <p style="color:#555;">
                Importaciones con más de <strong>
                ' . $MESES_IMPORTACIONES .
                ' meses</strong> desde la aprobación.
            </p>

            <table style="
                width:100%;
                border-collapse:collapse;
                margin-top:15px;
            ">

                <thead>

                    <tr style="
                        background:#6f42c1;
                        color:white;
                    ">

                        <th style="padding:10px;">
                            OC
                        </th>

                        <th style="padding:10px;">
                            Área
                        </th>

                        <th style="padding:10px;">
                            Solicitante
                        </th>

                        <th style="padding:10px;">
                            Aprobada
                        </th>

                        <th style="padding:10px;">
                            Días
                        </th>

                        <th style="padding:10px;">
                            Meses
                        </th>

                        <th style="padding:10px;">
                            Acción
                        </th>

                    </tr>

                </thead>

                <tbody>
        ';


        foreach ($alertas_importaciones as $row) {

            $cuerpo .= '

                    <tr style="
                        background:#fff;
                        border-bottom:1px solid #ddd;
                    ">

                        <td style="padding:10px;">
                            ' .
                            htmlspecialchars(
                                $row['orden_compra'] ?? ''
                            )
                            . '
                        </td>

                        <td style="padding:10px;">
                            ' .
                            htmlspecialchars(
                                $row['area'] ?? ''
                            )
                            . '
                        </td>

                        <td style="padding:10px;">
                            ' .
                            htmlspecialchars(
                                $row['solicitante'] ?? ''
                            )
                            . '
                        </td>

                        <td style="padding:10px;">
                            ' .
                            htmlspecialchars(
                                $row['fecha_aprobada'] ?? ''
                            )
                            . '
                        </td>

                        <td style="
                            padding:10px;
                            text-align:center;
                        ">
                            ' .
                            intval(
                                $row['dias_transcurridos']
                            )
                            . '
                        </td>

                        <td style="
                            padding:10px;
                            text-align:center;
                            font-weight:bold;
                            color:#6f42c1;
                        ">
                            ' .
                            intval(
                                $row['meses_transcurridos']
                            )
                            . '
                        </td>

                        <td style="padding:10px;">

                            <a href="
                            https://apps.melonesoilterminal.com/compras/seguimiento/view/panel_oc.php?id=' .
                            intval($row['id']) .
                            '"
                            style="
                                display:inline-block;
                                background:#28a745;
                                color:#fff;
                                text-decoration:none;
                                padding:8px 12px;
                                border-radius:5px;
                                font-weight:bold;
                            ">
                                Ver OC
                            </a>

                        </td>

                    </tr>
            ';
        }


        $cuerpo .= '

                </tbody>

            </table>

        </div>
        ';
    }


    // ========================================================
    // PIE DEL CORREO
    // ========================================================

    $cuerpo .= '

            <div style="
                margin-top:35px;
                padding-top:15px;
                border-top:1px solid #ddd;
            ">

                <p style="
                    text-align:center;
                    color:#777;
                    font-size:13px;
                ">
                    Este es un correo automático generado por
                    el Sistema de Seguimiento de Compras.
                </p>

                <p style="
                    text-align:center;
                    color:#999;
                    font-size:12px;
                ">
                    Por favor, no responder directamente
                    a este mensaje.
                </p>

            </div>

        </div>

    </div>

    </body>

    </html>

    ';


    // ========================================================
    // ENVIAR
    // ========================================================

    $mail->Body = $cuerpo;

    $mail->send();

    echo "
        <div style='
            font-family:Arial;
            padding:20px;
            color:green;
        '>
            ✅ Correo enviado correctamente.
            <br>
            Total de alertas: $total_alertas
            <br>
            Isla MOTI: $total_isla
            <br>
            Servicios: $total_servicios
            <br>
            Importaciones: $total_importaciones
        </div>
    ";


} catch (Exception $e) {

    echo "
        <div style='
            font-family:Arial;
            padding:20px;
            color:red;
        '>
            ❌ Error al enviar el correo:
            <br>
            " .
            htmlspecialchars(
                $mail->ErrorInfo
            )
            . "
        </div>
    ";

}

?>