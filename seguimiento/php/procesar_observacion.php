<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

include '../php/conexion.php';

if (isset($_POST['id']) && isset($_POST['comentario'])) {
    $id = intval($_POST['id']);
    $comentario = $con->real_escape_string($_POST['comentario']);
    $hoy = date('Y-m-d');

    // Obtener el número de orden_compra
    $result = $con->query("SELECT orden_compra FROM compras_detalle WHERE id = $id");
    $oc = ($result && $row = $result->fetch_assoc()) ? $row['orden_compra'] : "Desconocida";

    // Actualizar observación
    $sql = "UPDATE compras_detalle 
            SET comentario = '$comentario', 
                fecha_alerta = '$hoy',
                alertas_dias = 0 
            WHERE id = $id";

    if ($con->query($sql)) {

        // Configuración del correo
        $mail = new PHPMailer(true);

        try {
            // Configuración general del correo
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.office365.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'notificacion@melonesoilterminal.com';
            $mail->Password = 'n@x1LTm#';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->addAddress('rdelgado@melonesterminal.com'); // Cambia por quien debe recibirlo

            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = "Observación registrada para OC #$oc";

            // Cuerpo del correo
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; color: #333; background-color: #f9f9f9; padding: 20px; border-radius: 10px; border: 1px solid #ddd; max-width: 600px; margin: auto;'>
                    <h2 style='color: #004080;'>🔔 Observación Registrada</h2>
                    <p style='font-size: 16px;'>Se ha registrado una observación para la siguiente orden de compra:</p>
                    
                    <table style='width: 100%; border-collapse: collapse; margin-top: 10px;'>
                        <tr>
                            <td style='padding: 8px; font-weight: bold; background-color: #e6f0ff; width: 30%;'>Orden de Compra:</td>
                            <td style='padding: 8px; background-color: #f2f2f2;'>$oc</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; font-weight: bold; background-color: #e6f0ff;'>Fecha:</td>
                            <td style='padding: 8px; background-color: #f2f2f2;'>$hoy</td>
                        </tr>
                    </table>

                    <p style='margin-top: 20px; font-weight: bold;'>Comentario:</p>
                    <div style='background-color: #fff3cd; padding: 15px; border-left: 5px solid #ffc107; border-radius: 5px; font-size: 15px;'>
                        $comentario
                    </div>

                    <p style='margin-top: 20px;'>Puedes revisar la orden de compra en el sistema:</p>
                    <p>
                    <!--
                        <a href='https://apps.melonesoilterminal.com/compras/seguimiento/view/formulario.php' style='display: inline-block; 
                        padding: 10px 15px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 5px;'>
                        📋 Ver Orden de Compra
                        </a>
                      -->  
                    </p>

                    <hr style='margin-top: 30px;'>
                    <small style='color: #888;'>Este es un mensaje automático del sistema de compras. Por favor, no responder a este correo.</small>
                </div>
            ";


            $mail->send();
            echo "Observación guardada y correo enviado.";
        } catch (Exception $e) {
            echo "Observación guardada, pero no se pudo enviar el correo. Error: {$mail->ErrorInfo}";
        }

    } else {
        echo "Error al guardar observación: " . $con->error;
    }
} else {
    echo "Datos incompletos.";
}
?>

