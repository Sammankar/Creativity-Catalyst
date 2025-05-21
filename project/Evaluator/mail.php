<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php'; // Adjusted path for vendor

function sendVerificationEmail($email, $verification_link) {
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
        $mail->Subject = 'Email Verification Required';
        $mail->Body    = "<p>Click the link below to verify your email:</p>
                          <p><a href='$verification_link' target='_blank'>$verification_link</a></p>";

        // Send email
        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Mailer Error: " . $mail->ErrorInfo;
        return false;
    }
}
?>