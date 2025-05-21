<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php'; // Adjusted path for vendor

function sendCourseDeallocationEmail($courseName, $subAdminName, $subAdminEmail, $subject, $body) {
    
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
        $mail->addAddress($subAdminEmail);  // Send to sub-admin's email

        // Email Content
        $mail->isHTML(true);  // Set email format to HTML
        $mail->Subject = 'Course Deallocation Notification';
        $mail->Body = "<p>Dear $subAdminName,</p>
        <p>The following course has been deallocated from your assignment:</p>
        <p><strong>Course Name:</strong> $courseName</p>
        <p><strong>Subject:</strong> $subject</p>
        <p><strong>Details:</strong> $body</p>
        <p><br></p>
        <p><strong>Deallocated by(Your Admin):</strong> Your Admin</p>
        <p>If you have any questions, please contact your admin for further details.</p>
        <p><br></p>
        <p>Regards,</p>
        <p>Course Deallocation Team</p>";
                       

       
        // Send email
        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Mailer Error: " . $mail->ErrorInfo;
        return false;
    }
}
?>
