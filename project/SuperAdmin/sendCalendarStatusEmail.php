<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function sendCalendarStatusEmail($created_by, $semester, $status_action, $calendar_course_id) {
    include 'connection.php';

        // Fetch the full name of the Super-admin using their user_id
        $stmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $created_by);  // Bind the created_by (user_id of the Super-admin)
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($admin = $result->fetch_assoc()) {
            $super_admin_name = $admin['full_name'];  // Fetch the full name of the Super-admin
        } else {
            $super_admin_name = "Super-admin Not Found";  // In case there's no Super-admin with that user_id
        }
    
        $stmt->close();

    // Fetch users and the course name from the 'courses' table by joining with 'users' table
    $stmt = $conn->prepare("SELECT u.role, u.users_status, u.email, u.course_id, c.name AS course_name 
                            FROM users u
                            JOIN courses c ON u.course_id = c.course_id");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    $stmt->close();
    
    // Loop through users to send emails
    foreach ($users as $user) {
        $role = $user['role'];
        $users_status = $user['users_status'];
        $email = $user['email'];
        $course_id = $user['course_id'];
        $course_name = $user['course_name'];  // Get course name from the result
    
        // Only send email if criteria match (Guide or Admin)
        if ((($role == 3 && $users_status == 1 && $course_id == $calendar_course_id) || ($role == 2 && $users_status == 1))) {
            // Send email to this user
            $mail = new PHPMailer(true);
    
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.sendgrid.net';
                $mail->SMTPAuth = true;
                $mail->Username = 'apikey';
                $mail->Password = '';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
    
                $mail->setFrom('enter-email-id', 'Creativity Catalyst');
                $mail->addAddress($email);
    
                $statusText = $status_action == 1 ? 'Created' : 'Suspended';
    
                $mail->isHTML(true);
                $mail->Subject = 'Academic Calendar Status Update';
                $mail->Body = "
                    <html>
                        <body>
                            <h3>Status Change Notification</h3>
                            <p>The academic calendar has been <strong>{$statusText}</strong> for:</p>
                            <ul>
                                <li><strong>Course Name:</strong> {$course_name}</li>
                                <li><strong>Semester:</strong> {$semester}</li>
                            </ul>
                            <p>Please log in to your dashboard to check the status.</p>
                            <p>Academic Calendar Issued By University (Super-Admin): {$super_admin_name}</p>
                            <p>Regards,<br>University Team</p>
                        </body>
                    </html>";
    
                $mail->send();
            } catch (Exception $e) {
                return "Mailer Error: " . $mail->ErrorInfo;
            }
        }
    }
    
    return "Status updated and email sent.";
}
?>
