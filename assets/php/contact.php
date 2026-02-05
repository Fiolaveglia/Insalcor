<?php
// Mostrar errores para debugging (comentar en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuración del destinatario
$recipientEmail = 'fiolaveglia@gmail.com'; // CAMBIAR por tu email de INSALCOR
$recipientName = 'Insalcor'; 
          
// Validar que el formulario se envió por POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo '<div class="alert alert-danger" role="alert">Error: Método de envío inválido.</div>';
    exit;
}

// Recoger y sanitizar los datos del formulario
$senderName = isset($_POST['contact-name']) ? strip_tags(trim($_POST['contact-name'])) : '';
$senderEmail = isset($_POST['contact-email']) ? strtolower(filter_var(trim($_POST['contact-email']), FILTER_SANITIZE_EMAIL)) : '';  
$senderCompany = isset($_POST['contact-empresa']) ? strip_tags(trim($_POST['contact-empresa'])) : '';
$senderPhone = isset($_POST['contact-phone']) ? preg_replace('/[^0-9]/', '', trim($_POST['contact-phone'])) : '';  
$senderMessage = isset($_POST['contact-message']) ? strip_tags(trim($_POST['contact-message'])) : '';

// Validar campos requeridos
if (empty($senderName) || empty($senderEmail) || empty($senderMessage)) {
    echo '<div class="alert alert-danger" role="alert">Error: Por favor complete todos los campos requeridos.</div>';
    exit;
}

// Validar formato de email
if (!filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
    echo '<div class="alert alert-danger" role="alert">Error: El formato del email no es válido.</div>';
    exit;
}

// Validar teléfono (mínimo 9 dígitos si no está vacío)
if (!empty($senderPhone) && strlen($senderPhone) < 9) {
    echo '<div class="alert alert-danger" role="alert">Error: El teléfono debe tener al menos 9 dígitos.</div>';
    exit;
}

// Validar que teléfono solo contenga números (redundante pero por seguridad)
if (!empty($senderPhone) && !preg_match('/^[0-9]+$/', $senderPhone)) {
    echo '<div class="alert alert-danger" role="alert">Error: El teléfono solo puede contener números.</div>';
    exit;
}

// Cargar PHPMailer 6.x
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Crear una nueva instancia de PHPMailer
$mail = new PHPMailer(true);

try {
    // ============================================
    // CONFIGURACIÓN SMTP - EDITAR LOS DATOS
    // ============================================
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';                     // Servidor SMTP
    $mail->SMTPAuth = true;
    $mail->Username = 'fiolaveglia@gmail.com';             // CAMBIAR: Mail corporativo insalcor
    $mail->Password = 'nzpelxdnswwsxryl';            // CAMBIAR: Tu contraseña de aplicación (16 dígitos SIN ESPACIOS)
    $mail->SMTPSecure = 'tls';                          // tls o ssl
    $mail->Port = 587;                                  // 587 para TLS, 465 para SSL
    
    // Configuración del servidor SMTP (descomentar y configurar según tu servidor)
    // $mail->isSMTP();
    // $mail->Host = 'smtp.ejemplo.com';
    // $mail->SMTPAuth = true;
    // $mail->Username = 'tu-email@ejemplo.com';
    // $mail->Password = 'contraseña';
    // $mail->SMTPSecure = 'tls';
    // $mail->Port = 587;
    // Configuración del remitente y destinatario

    $mail->setFrom($recipientEmail, $recipientName);
    $mail->addReplyTo($senderEmail, $senderName);
    $mail->addAddress($recipientEmail, $recipientName);

    // Asunto del email
    $mail->Subject = 'Nuevo mensaje de contacto desde el sitio web - ' . $senderName;

    // Habilitar HTML
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';

    // Construir el cuerpo del mensaje en HTML
    $emailBody = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Helvetica, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #0D1F61; color: white; padding: 20px; text-align: center; }
            h2 { font-family: Helvetica, sans-serif; margin: 0; }
            .content { background-color: #f9f9f9; padding: 20px; }
            .field { margin-bottom: 15px; }
            .field-label { font-weight: bold; color: #0D1F61; }
            .field-value { margin-top: 5px; padding: 10px; background-color: white; border-left: 3px solid #0D1F61; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Nuevo mensaje de contacto</h2>
                <p>Recibido desde el formulario web de Insalcor</p>
            </div>
            <div class='content'>
                <div class='field'>
                    <div class='field-label'>Nombre:</div>
                    <div class='field-value'>" . htmlspecialchars($senderName) . "</div>
                </div>
                <div class='field'>
                    <div class='field-label'>Email:</div>
                    <div class='field-value'><a href='mailto:" . htmlspecialchars($senderEmail) . "'>" . htmlspecialchars($senderEmail) . "</a></div>
                </div>
                <div class='field'>
                    <div class='field-label'>Empresa:</div>
                    <div class='field-value'>" . htmlspecialchars($senderCompany) . "</div>
                </div>
                <div class='field'>
                    <div class='field-label'>Teléfono:</div>
                    <div class='field-value'>" . htmlspecialchars($senderPhone) . "</div>
                </div>
                <div class='field'>
                    <div class='field-label'>Mensaje:</div>
                    <div class='field-value'>" . nl2br(htmlspecialchars($senderMessage)) . "</div>
                </div>
            </div>
            <div class='footer'>
                <p>Este mensaje fue enviado desde el formulario de contacto de www.insalcor.com</p>
                <p>Fecha: " . date('d/m/Y H:i:s') . "</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $mail->Body = $emailBody;

    // Versión alternativa en texto plano
    $mail->AltBody = "
Nuevo mensaje de contacto

Nombre: $senderName
Email: $senderEmail
Empresa: $senderCompany
Teléfono: $senderPhone
Mensaje: $senderMessage

---
Enviado desde el formulario de contacto de www.insalcor.com
Fecha: " . date('d/m/Y H:i:s');

    // Enviar el email
    if (!$mail->send()) {
        echo '<div class="alert alert-danger" role="alert">Error al enviar el mensaje: ' . $mail->ErrorInfo . '</div>';
    } else {
        echo '<div class="alert alert-success" role="alert">¡Gracias por contactarnos! Te responderemos a la brevedad.</div>';
    }

} catch (Exception $e) {
    echo '<div class="alert alert-danger" role="alert">Error: No se pudo enviar el mensaje. ' . $e->getMessage() . '</div>';
}
?>
