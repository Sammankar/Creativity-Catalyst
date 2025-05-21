<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php'; // Composer autoload

function sendOtpEmail($email, $otp) {
    $mail = new PHPMailer(true);

    try {
        // SMTP Settings for SendGrid
        $mail->isSMTP();
        $mail->Host = 'smtp.sendgrid.net';
        $mail->SMTPAuth = true;
        $mail->Username = 'apikey';
        $mail->Password = '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Reduce delays and errors
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        $mail->Timeout = 10; // Set timeout for quicker failover
        $mail->SMTPKeepAlive = false; // Close connection after sending

        // Sender & Recipient
        $mail->setFrom('enter-email-id', 'Creativity Catalyst');
        $mail->addAddress($email);

        // Email Content
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP for Login';
        $mail->Body    = "
            <p>Dear user,</p>
            <p>Your OTP for login is: <strong>$otp</strong></p>
            <p>This OTP is valid for the next 10 minutes. Please do not share it with anyone.</p>
            <p>Regards,<br>Creativity Catalyst Team</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("OTP Email failed to send: " . $mail->ErrorInfo); // Log error for debugging
        return false;
    }
}
?>
