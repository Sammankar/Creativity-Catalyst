<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
include 'connection.php';

function sendAccessChangeEmail($user_id, $changed_by, $new_access, $subject, $body, $warning, $attachments) {
    $mail = new PHPMailer(true);
    $uploadDir = __DIR__ . '/images/student_access_reports/';
    $attachmentPaths = null; // Initialize attachmentPaths as null
    
    // Fetch User Email
    $db = new mysqli('localhost', 'root', '', 'project');
    $stmt = $db->prepare("SELECT email, access_status FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        return "User not found.";
    }
    
    $old_access = $user['access_status'];
    $email = $user['email'];
    $accessText = ($new_access == 1) ? "Granted" : "Restricted";
    
    try {
        // Handle File Uploads
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        $maxFileSize = 15 * 1024 * 1024;

        if (!empty($_FILES['attachments']['name'][0])) {
            foreach ($_FILES['attachments']['tmp_name'] as $key => $tmp_name) {
                $fileType = $_FILES['attachments']['type'][$key];
                $fileSize = $_FILES['attachments']['size'][$key];
                $filename = time() . '_' . $_FILES['attachments']['name'][$key];
                $filePath = $uploadDir . $filename;

                if (!is_dir($uploadDir)) {
                    return "Error: The directory does not exist.";
                }

                if (!in_array($fileType, $allowedTypes) || $fileSize > $maxFileSize) {
                    return "Invalid file type or size exceeded.";
                }

                if (move_uploaded_file($tmp_name, $filePath)) {
                    // Store the filenames in the attachmentPaths array
                    if ($attachmentPaths === null) {
                        $attachmentPaths = [];  // Initialize as an array if files are uploaded
                    }
                    $attachmentPaths[] = $filename;
                } else {
                    return "Error uploading file.";
                }
            }
        }
        
        // Insert Log into Database
        $stmt = $db->prepare("INSERT INTO student_access_denial_reports (user_id, changed_by, old_access, new_access, subject, body, warning, attachments) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        // If no files, insert null for attachment field
        $jsonAttachments = ($attachmentPaths !== null) ? json_encode($attachmentPaths) : null;
        $stmt->bind_param("iiiissss", $user_id, $changed_by, $old_access, $new_access, $subject, $body, $warning, $jsonAttachments);
        $stmt->execute();
        if ($stmt->error) {
            return "Error inserting into access_denial_reports: " . $stmt->error;
        }
        $report_id = $db->insert_id;  // Capture the report_id for chat_reports
        $stmt->close();

        $query = "INSERT INTO student_chat_reports (report_id, locked) VALUES (?, 0)";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $report_id);
        $stmt->execute();
        
        if ($stmt->error) {
            return "Error inserting into chat_reports: " . $stmt->error;
        }
        $stmt->close();
        
        // Update User Access
        $stmt = $db->prepare("UPDATE users SET access_status = ? WHERE user_id = ?");
        $stmt->bind_param("ii", $new_access, $user_id);
        $stmt->execute();
        $stmt->close();
        
        // Send Email
        $mail->isSMTP();
        $mail->Host = 'smtp.sendgrid.net';
        $mail->SMTPAuth = true;
        $mail->Username = 'apikey';
        $mail->Password = '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        $mail->setFrom('enter-email-id', 'Access Control');
        $mail->addAddress($email);
        
        // Attach files if any exist
        if ($attachmentPaths !== null) {
            foreach ($attachmentPaths as $file) {
                $mail->addAttachment($uploadDir . $file);
            }
        }
        
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = "<html><body>"
            . "<h3>Access Change Notification</h3>"
            . "<p>Your account access has been <strong>$accessText</strong>.</p>"
            . "<p>$body</p>"
            . ($warning ? "<p style='color: red; font-weight: bold;'>⚠️ $warning</p>" : "")
            . "<p>Regards,<br>Access Control Team</p>"
            . "</body></html>";
        
        // Debugging: Check if mail can be sent
        if (!$mail->send()) {
            return "Mailer Error: " . $mail->ErrorInfo;
        }

        return "Access updated and email sent.";
        
    } catch (Exception $e) {
        return "Error: " . $mail->ErrorInfo;
    }
}
?>
