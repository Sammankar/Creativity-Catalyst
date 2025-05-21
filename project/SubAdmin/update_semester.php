<?php
// Include the database connection file
include('connection.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php'; // Adjusted path for vendor

// Check if the required POST parameters are set
if (isset($_POST['student_id']) && isset($_POST['new_semester'])) {
    $student_id = $_POST['student_id'];
    $new_semester = $_POST['new_semester'];
    $warning = isset($_POST['warning']) ? $_POST['warning'] : '';  // Get the warning, default to empty if not provided

    // Validate the inputs (optional, you can add further validation here)
    if (empty($student_id) || empty($new_semester)) {
        echo json_encode(['success' => false, 'message' => 'Missing student ID or semester.']);
        exit;
    }

    // Begin transaction to handle both the update and log insertion atomically
    try {
        // Start transaction with mysqli
        mysqli_begin_transaction($conn);

        // Get the current semester before updating (to log the old semester)
        $stmt = $conn->prepare("SELECT current_semester, course_id, email FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!$user) {
            throw new Exception("Student not found.");
        }

        $old_semester = $user['current_semester'];
        $course_id = $user['course_id'];
        $email = $user['email'];  // Get the student's email

        // Update the student's current semester in the users table
        $sql = "UPDATE users SET current_semester = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $new_semester, $student_id);
        $stmt->execute();

        // Check if the update was successful
        if ($stmt->affected_rows === 0) {
            throw new Exception("No rows were updated in users table.");
        }

        // Update the student_academics table for admission_semester and current_semester
        $update_academics_sql = "UPDATE student_academics 
                                 SET admission_semester = ?, current_semester = ? 
                                 WHERE user_id = ?";
        $stmt = $conn->prepare($update_academics_sql);
        $stmt->bind_param("iii", $new_semester, $new_semester, $student_id);
        $stmt->execute();

        // Check if the update was successful
        if ($stmt->affected_rows === 0) {
            throw new Exception("No rows were updated in student_academics table.");
        }

        // Insert a record into the semester_updates table for logging the change
        $current_time = date('Y-m-d H:i:s');
        $body = "Updated semester from $old_semester to $new_semester";

        // Insert into the semester_updates table
        $insert_sql = "INSERT INTO semester_updates (student_id, old_semester, new_semester, body, warning, updated_at)
                       VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("iiisss", $student_id, $old_semester, $new_semester, $body, $warning, $current_time);
        $stmt->execute();

        // Commit transaction if both operations succeed
        mysqli_commit($conn);

        // Send email after successful update
        // Here’s the email sending code integrated into this file:

        // Set up the email
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
            $mail->addAddress($email);  // Student's email address

            // Email Content
            $mail->isHTML(true);  // Set email format to HTML
            $mail->Subject = 'Semester Updated Successfully';

            // Construct the email body
            $emailBody = "<p>Dear student,</p>
                          <p>Your semester has been successfully updated to <strong>Semester $new_semester</strong>.</p>
                          <p>If you did not request this change, please contact the administration immediately.</p>";

            // Check if there's a warning message to include
            if (!empty($warning)) {
                $emailBody .= "<p><strong>Important Notice:</strong></p>";
                $emailBody .= "<p style='color: red; font-weight: bold;'>$warning</p>";
            }

            $mail->Body = $emailBody;

            // Send email
            $mail->send();
        } catch (Exception $e) {
            echo "Mailer Error: " . $mail->ErrorInfo;
            throw new Exception("Email sending failed: " . $e->getMessage());
        }

        // Return success response
        echo json_encode(['success' => true, 'message' => 'Semester updated successfully, log created, and email sent.']);

    } catch (Exception $e) {
        // If an error occurs, roll back the transaction
        mysqli_rollback($conn);

        // Return error message
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    // Return error if required POST parameters are not set
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
}

?>
