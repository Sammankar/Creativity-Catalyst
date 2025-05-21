<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function sendProjectStatusEmail($schedule) {
    require 'connection.php';

    $course_id = $schedule['course_id'];
    $semester = $schedule['semester'];
    $academic_year = $schedule['academic_year'];
    $college_id = $schedule['college_id'];
    $end_date = $schedule['end_date'];

    // Fetch students matching course, semester, academic year and college
    $stmt = $conn->prepare("SELECT full_name, email FROM users 
                            WHERE role = 5 AND course_id = ? AND current_semester = ? 
                              AND college_id = ? AND users_status = 1");
    $stmt->bind_param("iii", $course_id, $semester, $college_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($student = $result->fetch_assoc()) {
        $full_name = $student['full_name'];
        $email = $student['email'];

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.sendgrid.net';
            $mail->SMTPAuth = true;
            $mail->Username = 'apikey';
            $mail->Password = ''; // Replace with secure value
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('enter-email-id', 'Creativity Catalyst');
            $mail->addAddress($email, $full_name);

            $mail->isHTML(true);
            $mail->Subject = "Project Submission Window Started";

            $mail->Body = "
                <html>
                    <body>
                        <p>Dear <strong>{$full_name}</strong>,</p>
                        <p>This is to inform you that your project submission schedule has been activated.</p>
                        <p><strong>Submission Deadline:</strong> {$end_date}</p>
                        <p>Please log in to the portal and complete all stages before the deadline.</p>
                        <br>
                        <p>Regards,<br>Creativity Catalyst Team</p>
                    </body>
                </html>";

            $mail->send();

        } catch (Exception $e) {
            // Optionally log the error: error_log("Email to {$email} failed: {$mail->ErrorInfo}");
        }
    }

    $stmt->close();
}
?>
