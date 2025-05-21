<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php'; // Adjusted path for vendor

function sendAccessStatusMail($email, $fullName, $currentAccessStatus) {
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
        $mail->addAddress($email, $fullName);

        // Define subject & message
        $subject = ($currentAccessStatus == 1) ? "Access Granted" : "Access Revoked";
        $message = "<p>Dear $fullName,</p>
                    <p>Your account access has been <strong>" . ($currentAccessStatus == 1 ? "granted" : "revoked") . "</strong>.</p>
                    <p>If you have any questions, please contact support.</p>
                    <p>Regards,<br>Creativity Catalyst Team</p>";

        // Email Content
        $mail->isHTML(true);  // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $message;

        // Send email
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>
