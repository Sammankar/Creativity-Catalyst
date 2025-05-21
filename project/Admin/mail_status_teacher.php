<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
include 'connection.php'; // Database connection

function sendTeacherStatusEmail($teacher_id, $changed_by, $new_status, $subject, $body, $warning, $attachments) {
    $mail = new PHPMailer(true);
    $uploadDir = __DIR__ . '/images/teacher_status_report/';  // Make sure this path is correct
    $attachmentPaths = [];
    
    // Fetch Teacher Email
    $db = new mysqli('localhost', 'root', '', 'project');
    $stmt = $db->prepare("SELECT email, users_status FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $teacher = $result->fetch_assoc();
    $stmt->close();
    
    if (!$teacher) {
        return "Teacher not found.";
    }
    
    $old_status = $teacher['users_status'];
    $email = $teacher['email'];
    $statusText = ($new_status == 1) ? "Active" : "Inactive";
    
    try {
        // Handle File Uploads with validation
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        $maxFileSize = 15 * 1024 * 1024; // 15MB
        if (!empty($_FILES['attachments']['name'][0])) {
            foreach ($_FILES['attachments']['tmp_name'] as $key => $tmp_name) {
                $fileType = $_FILES['attachments']['type'][$key];
                $fileSize = $_FILES['attachments']['size'][$key];
                $filename = time() . '_' . $_FILES['attachments']['name'][$key];
                $filePath = $uploadDir . $filename;

                // Debug the path
                if (!is_dir($uploadDir)) {
                    return "Error: The directory does not exist.";
                }

                // Validate file type and size
                if (!in_array($fileType, $allowedTypes)) {
                    return "Invalid file type. Only PDF, JPEG, and PNG are allowed.";
                }
                if ($fileSize > $maxFileSize) {
                    return "File size exceeds the 15MB limit.";
                }

                // Move the uploaded file to the specified directory
                if (move_uploaded_file($tmp_name, $filePath)) {
                    $attachmentPaths[] = $filename;
                } else {
                    return "Error uploading file.";
                }
            }
        }
        
        // Insert Log into Database (Teacher Status Report)
        $stmt = $db->prepare("INSERT INTO teacher_status_reports (teacher_id, changed_by, old_status, new_status, subject, body, warning, attachments) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $jsonAttachments = json_encode($attachmentPaths);
        $stmt->bind_param("iiiissss", $teacher_id, $changed_by, $old_status, $new_status, $subject, $body, $warning, $jsonAttachments);
        $stmt->execute();
        $stmt->close();
        
        // Update Teacher Status
        $stmt = $db->prepare("UPDATE users SET users_status = ? WHERE user_id = ?");
        $stmt->bind_param("ii", $new_status, $teacher_id);
        $stmt->execute();
        $stmt->close();
        
        // Send Email to Teacher
        $mail->isSMTP();
        $mail->Host = 'smtp.sendgrid.net';  // SendGrid SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'apikey'; // SendGrid API Key Username (always 'apikey')
        $mail->Password = '';  // Your SendGrid API Key
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        $mail->setFrom('enter-email-id', 'Creativity Catalyst');
        $mail->addAddress($email);
        
        // Add Attachments
        foreach ($attachmentPaths as $file) {
            $mail->addAttachment($uploadDir . $file);
        }
        
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = "<html><body>"
            . "<h3>Status Change Notification</h3>"
            . "<p>Your account status has been changed to <strong>$statusText</strong>.</p>"
            . "<p>$body</p>"
            . ($warning ? "<p style='color: red; font-weight: bold;'>⚠️ $warning</p>" : "")
            . "<p>Regards,<br>Creativity Catalyst Team</p>"
            . "</body></html>";
        
        $mail->send();
        return "Status updated and email sent.";
        
    } catch (Exception $e) {
        return "Error: " . $mail->ErrorInfo;
    }
}

?>
