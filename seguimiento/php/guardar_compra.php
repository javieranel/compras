<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

require_once __DIR__ . '/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha_solicitud = $_POST['fecha_solicitud'] ?? '';
    $status = $_POST['status'] ?? '';
    $solicitud = $_POST['solicitud'] ?? '';
    $categoria = $_POST['categoria'] ?? '';
    $area = $_POST['area'] ?? '';
    $solicitante = $_POST['solicitante'] ?? '';
    $correo_solicitante = $_POST['correo_solicitante'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $proveedor = $_POST['proveedor'] ?? '';
    $orden_compra = $_POST['orden_compra'] ?? '';
    $fecha_aprobada = $_POST['fecha_aprobada'] ?? '';
    $status_entrega = "Pendiente";
    $llego_terminal = "No";
    $llego_isla = "No";
    $destino = $_POST['destino'] ?? '';

    $fecha1 = new DateTime($fecha_solicitud);
    $fecha2 = new DateTime();
    $intervalo = $fecha1->diff($fecha2);
    $alerta_dias = $intervalo->days;

    if (
        empty($fecha_solicitud) ||
        empty($status) ||
        empty($solicitud) ||
        empty($categoria) ||
        empty($area) ||
        empty($solicitante) ||
        empty($descripcion) ||
        empty($proveedor) ||
        empty($orden_compra) ||
        empty($fecha_aprobada) ||
        empty($destino)
    ) {
        echo json_encode([
            'success' => false,
            'error' => 'Faltan datos obligatorios.'
        ]);
        exit;
    }



    

    $stmt = $con->prepare("INSERT INTO compras_detalle (
        fecha_solicitud, status, solicitud, categoria, area, solicitante, 
        descripcion, proveedor, orden_compra, fecha_aprobada, 
        llego_terminal, llego_isla, status_entrega, alertas_dias, destino
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
        "ssssssssssssssi",
        $fecha_solicitud, $status, $solicitud, $categoria, $area, $solicitante,
        $descripcion, $proveedor, $orden_compra, $fecha_aprobada,
        $llego_terminal, $llego_isla, $status_entrega, $alerta_dias, $destino
    );

    if ($stmt->execute()) {
        // Éxito: Enviar correo
        $mail = new PHPMailer(true);
        try {
            // Configuración del servidor
            $mail->isSMTP();
            $mail->Host = 'smtp.office365.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'notificacion@melonesoilterminal.com';
            $mail->Password = 'n@x1LTm#';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            // Remitente y destinatario
            $mail->addAddress($correo_solicitante); // destinatario

            // Contenido del correo
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Tu orden de compra ha sido registrada';
            $mail->Body = '
<div style="font-family: Arial, sans-serif; background-color: #ffffff; max-width: 600px; margin: 0 auto; border-radius: 10px; border: 1px solid #ddd; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">

    <div style="background-color: #0056b3; color: #fff; padding: 20px; text-align: center;">
        <h2 style="margin: 0; font-size: 22px;">📦 Nueva Solicitud de Compra</h2>
    </div>

    <div style="padding: 20px; color: #333;">
        <p style="font-size: 16px;">Hola <strong>' . htmlspecialchars($solicitante) . '</strong>,</p>

        <p style="font-size: 15px;">Tu solicitud ha sido registrada exitosamente. A continuación te compartimos los detalles:</p>

        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; font-size: 14px;">
            <p>🧾 <strong>Orden de compra:</strong> ' . htmlspecialchars($orden_compra) . '</p>
            <p>📝 <strong>Descripción:</strong> ' . htmlspecialchars($descripcion) . '</p>
            <p>🏢 <strong>Proveedor:</strong> ' . htmlspecialchars($proveedor) . '</p>
            <p>🏷️ <strong>Categoría:</strong> ' . htmlspecialchars($categoria) . '</p>
            <p>📍 <strong>Área:</strong> ' . htmlspecialchars($area) . '</p>
            <p>📅 <strong>Fecha de solicitud:</strong> ' . htmlspecialchars($fecha_solicitud) . '</p>
        </div>
        
        <p style="margin-top: 40px; font-size: 13px; color: #888; text-align: center;">                                                                                                                                             
            Este mensaje fue generado automáticamente por el sistema. No es necesario responder.
        </p>
    </div>
</div>';



            $mail->send();

            echo json_encode(['success' => true, 'correo' => 'enviado']);
        } catch (Exception $e) {
            echo json_encode(['success' => true, 'correo' => 'fallo', 'error' => $mail->ErrorInfo]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }

    $stmt->close();
}
?>