<?php
namespace App;

use PHPMailer\PHPMailer\PHPMailer;

class MailService
{
    public static function send(string $to, string $subject, string $template, array $vars = []): void
    {
        if (defined('APP_ENV') && APP_ENV === 'development') {
            error_log("[DEV MAIL] To: $to | Subject: $subject");
            return;
        }
        $body = self::render($template, $vars);
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'];
        $mail->Password   = $_ENV['SMTP_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)$_ENV['SMTP_PORT'];
        $mail->setFrom($_ENV['SMTP_FROM'], $_ENV['SMTP_FROM_NAME']);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->send();
    }

    private static function render(string $template, array $vars): string
    {
        $templatePath = ROOT . '/views/emails/' . $template . '.php';
        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Email template not found: $template");
        }
        extract($vars, EXTR_SKIP);
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }
}
