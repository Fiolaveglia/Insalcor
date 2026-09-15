<?php
/**
 * Formulario de contacto (contact.html) → email a la casilla de la empresa.
 *
 * Toda la configuración (destinatario y SMTP) sale de variables de entorno;
 * ver .env.example en la raíz del proyecto. Responde un fragmento HTML
 * (alerta de Bootstrap) que functions.js inserta en .contact-result.
 */
declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require __DIR__ . '/../../inc/env.php';
require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

ini_set('display_errors', '0');
header('Content-Type: text/html; charset=utf-8');

$lang = ($_COOKIE['insalcor_lang'] ?? 'es') === 'en' ? 'en' : 'es';

const MENSAJES = [
    'es' => [
        'method' => 'Método de envío inválido.',
        'required' => 'Por favor complete todos los campos requeridos.',
        'email' => 'El formato del email no es válido.',
        'phone' => 'El teléfono debe tener al menos 9 dígitos.',
        'send' => 'No se pudo enviar el mensaje. Por favor intente nuevamente más tarde o escríbanos por WhatsApp.',
        'ok' => '¡Gracias por contactarnos! Te responderemos a la brevedad.',
    ],
    'en' => [
        'method' => 'Invalid request method.',
        'required' => 'Please fill in all required fields.',
        'email' => 'The email address is not valid.',
        'phone' => 'The phone number must have at least 9 digits.',
        'send' => 'The message could not be sent. Please try again later or reach us on WhatsApp.',
        'ok' => 'Thank you for contacting us! We will get back to you shortly.',
    ],
];

function responder(string $tipo, string $clave, int $status = 200): never
{
    global $lang;
    http_response_code($status);
    $clase = $tipo === 'ok' ? 'alert-success' : 'alert-danger';
    echo '<div class="alert ' . $clase . '" role="alert">' . htmlspecialchars(MENSAJES[$lang][$clave]) . '</div>';
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    responder('error', 'method', 405);
}

// Honeypot: campo oculto que una persona nunca completa. Si viene con algo es
// un bot; le respondemos "ok" sin enviar nada.
if (trim((string) ($_POST['contact-website'] ?? '')) !== '') {
    responder('ok', 'ok');
}

// Sin saltos de línea en los campos de una sola línea (evita inyección de headers).
$unaLinea = static fn (string $campo): string =>
    trim(preg_replace('/[\r\n\t]+/', ' ', strip_tags((string) ($_POST[$campo] ?? ''))));

$senderName = mb_substr($unaLinea('contact-name'), 0, 120);
$senderEmail = strtolower($unaLinea('contact-email'));
$senderCompany = mb_substr($unaLinea('contact-empresa'), 0, 160);
$senderPhone = preg_replace('/\D/', '', (string) ($_POST['contact-phone'] ?? ''));
$senderMessage = mb_substr(trim(strip_tags((string) ($_POST['contact-message'] ?? ''))), 0, 5000);

if ($senderName === '' || $senderEmail === '' || $senderMessage === '') {
    responder('error', 'required', 422);
}
if (!filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
    responder('error', 'email', 422);
}
if ($senderPhone !== '' && strlen($senderPhone) < 9) {
    responder('error', 'phone', 422);
}

$recipientEmail = env('CONTACT_TO_EMAIL');
$recipientName = env('CONTACT_TO_NAME', 'Insalcor');
$smtpUser = env('SMTP_USER');

if (!$recipientEmail || !env('SMTP_HOST') || !$smtpUser || !env('SMTP_PASS')) {
    error_log('[contact] Falta configuración: CONTACT_TO_EMAIL, SMTP_HOST, SMTP_USER y SMTP_PASS son obligatorias (ver .env.example).');
    responder('error', 'send', 500);
}

$fecha = date('d/m/Y H:i:s');
$h = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$filas = [
    'Nombre' => $h($senderName),
    'Email' => '<a href="mailto:' . $h($senderEmail) . '">' . $h($senderEmail) . '</a>',
    'Empresa' => $h($senderCompany) ?: '—',
    'Teléfono' => $h($senderPhone) ?: '—',
    'Mensaje' => nl2br($h($senderMessage)),
];
$campos = '';
foreach ($filas as $etiqueta => $valor) {
    $campos .= "
        <div style='margin-bottom:15px'>
            <div style='font-weight:bold;color:#0D1F61'>{$etiqueta}:</div>
            <div style='margin-top:5px;padding:10px;background:#fff;border-left:3px solid #0D1F61'>{$valor}</div>
        </div>";
}

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = env('SMTP_HOST');
    $mail->Port = (int) env('SMTP_PORT', '587');
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = env('SMTP_PASS');
    $mail->SMTPSecure = env('SMTP_SECURE', 'tls') === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Timeout = 15;
    $mail->CharSet = PHPMailer::CHARSET_UTF8;

    $mail->setFrom(env('SMTP_FROM_EMAIL', $smtpUser), env('SMTP_FROM_NAME', 'Sitio web Insalcor'));
    $mail->addAddress($recipientEmail, $recipientName);
    $mail->addReplyTo($senderEmail, $senderName);

    $mail->isHTML(true);
    $mail->Subject = 'Nuevo mensaje de contacto desde el sitio web - ' . $senderName;
    $mail->Body = "
    <div style='font-family:Helvetica,Arial,sans-serif;line-height:1.6;color:#333;max-width:600px;margin:0 auto;padding:20px'>
        <div style='background:#0D1F61;color:#fff;padding:20px;text-align:center'>
            <h2 style='margin:0'>Nuevo mensaje de contacto</h2>
            <p style='margin:5px 0 0'>Recibido desde el formulario web de Insalcor</p>
        </div>
        <div style='background:#f9f9f9;padding:20px'>{$campos}</div>
        <div style='text-align:center;padding:20px;font-size:12px;color:#666'>
            <p>Enviado desde el formulario de contacto de www.insalcor.com — {$fecha}</p>
        </div>
    </div>";
    $mail->AltBody = "Nuevo mensaje de contacto\n\n"
        . "Nombre: $senderName\nEmail: $senderEmail\nEmpresa: $senderCompany\nTeléfono: $senderPhone\n\n"
        . "Mensaje:\n$senderMessage\n\n---\nEnviado desde el formulario de contacto de www.insalcor.com — $fecha";

    $mail->send();
} catch (Exception $e) {
    // El detalle técnico va al log del servidor, nunca al visitante.
    error_log('[contact] Error al enviar: ' . $mail->ErrorInfo);
    responder('error', 'send', 500);
}

responder('ok', 'ok');
