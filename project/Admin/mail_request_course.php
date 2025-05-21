<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php'; // Adjusted path for vendor

function sendCourseRequestEmail($superAdminEmail, $adminEmail, $adminName, $requestedCourses) {
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
        $mail->addAddress($superAdminEmail); // Super-Admin Email

        // Construct course list as HTML
        $courseListHtml = '<ul>';
        foreach ($requestedCourses as $course) {
            $courseListHtml .= "<li>$course</li>";
        }
        $courseListHtml .= '</ul>';

        // Email Content
        $mail->isHTML(true);  
        $mail->Subject = 'New Course Request from Admin';
        $mail->Body    = "<p>Dear Super-Admin,</p>
                          <p>The following admin has requested to add new courses:</p>
                          <p><strong>Admin Name:</strong> $adminName</p>
                          <p><strong>Admin Email:</strong> $adminEmail</p>
                          <p><strong>Requested Courses:</strong></p>
                          $courseListHtml
                          <p>Please log in to your panel and review the course requests.</p>
                          <p>Best Regards,<br>Creativity Catalyst Team</p>";

        // Send email
        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Mailer Error: " . $mail->ErrorInfo;
        return false;
    }
}
?>
