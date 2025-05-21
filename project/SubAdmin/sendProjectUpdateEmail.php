<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function sendProjectUpdateEmail($schedule) {
    require 'connection.php';

    $course_id = $schedule['course_id'];
    $semester = $schedule['semester'];
    $academic_year = $schedule['academic_year'];
    $college_id = $schedule['college_id'];
    $start_date = $schedule['start_date'];
    $end_date = $schedule['end_date'];

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
            $mail->Subject = "Updated Project Submission Schedule";

            $mail->Body = "
                <html>
                    <body>
                        <p>Dear <strong>{$full_name}</strong>,</p>
                        <p>The schedule for your project submission has been updated.</p>
                        <p><strong>New Submission Window:</strong> {$start_date} to {$end_date}</p>
                        <p>Please make sure to check the updated timeline and complete all required stages within the deadline.</p>
                        <br>
                        <p>Regards,<br>Creativity Catalyst Team</p>
                    </body>
                </html>";

            $mail->send();

        } catch (Exception $e) {
            // Optional: Log email error
        }
    }

    $stmt->close();
}
