<?php
// src/correo.php
// Función enviarCorreo($to, $subject, $bodyHtml) -> bool
// Intenta usar PHPMailer (Composer). Si no existe, usa mail() como fallback.


$config = require __DIR__ . '/configuracion.php';

function enviarCorreo(string $to, string $subject, string $bodyHtml): bool {
    $config = require __DIR__ . '/configuracion.php';

    // Si existe PHPMailer vía Composer
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        try {
            require_once __DIR__ . '/../vendor/autoload.php';
           // $mail = new PHPMailer\PHPMailer\PHPMailer(true); ACTIVAR AL FINAL PARA PROBAR

            $mail->isSMTP();
            $mail->Host = $config['smtp_host'] ?? '';
            $mail->SMTPAuth = true;
            $mail->Username = $config['smtp_user'] ?? '';
            $mail->Password = $config['smtp_pass'] ?? '';
            $mail->SMTPSecure = $config['smtp_secure'] ?? 'tls';
            $mail->Port = $config['smtp_port'] ?? 587;

            $fromEmail = $config['from_email'] ?? $config['smtp_user'] ?? 'no-reply@localhost';
            $fromName  = $config['from_name'] ?? 'SCV';

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $bodyHtml;

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('[correo] PHPMailer error: ' . $e->getMessage());
            return false;
        }
    }

    // Fallback simple usando mail()
    $fromEmail = $config['from_email'] ?? 'no-reply@localhost';
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: {$fromEmail}\r\n";

    $success = mail($to, $subject, $bodyHtml, $headers);
    if (!$success) error_log('[correo] mail() failed for ' . $to);
    return $success;
}