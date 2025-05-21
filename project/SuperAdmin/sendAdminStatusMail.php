<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php'; // Adjusted path for vendor

function sendEmail($email, $subject, $body, $attachment = null) {
    $mail = new PHPMailer(true);

    try {
        // SMTP Settings for SendGrid
        $mail->isSMTP();
        $mail->Host = 'smtp.sendgrid.net';  // SendGrid SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'apikey'; // SendGrid API Key Username (always 'apikey')
        $mail->Password = '';  // Your SendGrid API Key
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Sender & Recipient
        $mail->setFrom('enter-email-id', 'Creativity Catalyst');  // Your sender email
        $mail->addAddress($email);

        // Email Content
        $mail->isHTML(true);  // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $body;

        // Handle Attachments (if provided)
        if ($attachment && isset($attachment['tmp_name']) && $attachment['error'] === UPLOAD_ERR_OK) {
            $mail->addAttachment($attachment['tmp_name'], $attachment['name']);
        }

        // Send email
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>
