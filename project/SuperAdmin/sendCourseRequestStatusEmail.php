<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php'; // Adjusted path for vendor

function sendCourseRequestStatusEmail($college_admin_email, $course_name, $status) {
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
        $mail->addAddress($college_admin_email);  // College Admin Email

        // Email Content
        $mail->isHTML(true);  // Set email format to HTML
        $mail->Subject = 'Course Request Status Notification';
        
        // Setting the body message based on the status
        if ($status == 'approved') {
            $mail->Body    = "<p>Dear College Admin,</p>
                              <p>Your course request for <strong>$course_name</strong> has been approved.</p>
                              <p>Regards,<br>Creativity Catalyst Team</p>";
        } else {
            $mail->Body    = "<p>Dear College Admin,</p>
                              <p>Your course request for <strong>$course_name</strong> has been rejected.</p>
                              <p>Regards,<br>Creativity Catalyst Team</p>";
        }

        // Send email
        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Mailer Error: " . $mail->ErrorInfo;
        return false;
    }
}
?>
