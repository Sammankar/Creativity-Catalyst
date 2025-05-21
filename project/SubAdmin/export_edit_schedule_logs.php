<?php
session_start();
require_once(__DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php');
include "connection.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid schedule ID");
}

$schedule_id = intval($_GET['id']);

// Fetch schedule info
$schedule_stmt = $conn->prepare("SELECT * FROM project_submission_schedule WHERE id = ?");
$schedule_stmt->bind_param("i", $schedule_id);
$schedule_stmt->execute();
$schedule_result = $schedule_stmt->get_result();
$schedule = $schedule_result->fetch_assoc();

if (!$schedule) {
    die("Schedule not found.");
}

// Fetch course and college info
$college_id = $schedule['college_id'];
$course_id = $schedule['course_id'];

$college = $conn->query("SELECT name FROM colleges WHERE college_id = $college_id")->fetch_assoc();
$course = $conn->query("SELECT name FROM courses WHERE course_id = $course_id")->fetch_assoc();

// Fetch logs
$log_stmt = $conn->prepare("SELECT * FROM project_schedule_edit_logs WHERE schedule_id = ? ORDER BY edited_at ASC");
$log_stmt->bind_param("i", $schedule_id);
$log_stmt->execute();
$logs_result = $log_stmt->get_result();

if ($logs_result->num_rows === 0) {
    die("No edit logs available.");
}

// Start PDF
$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 12);

$html = '<h2 style="text-align:center">Project Submission Edit Logs</h2>';
$html .= '<br><strong>College:</strong> ' . $college['name'];
$html .= '<br><strong>Course:</strong> ' . $course['name'];
$html .= '<br><strong>Semester:</strong> Semester ' . $schedule['semester'];
$html .= '<br><strong>Academic Year:</strong> ' . $schedule['academic_year'];
$html .= '<br><strong>Academic Schedule Duration:</strong> ' . $schedule['start_date'] . ' to ' . $schedule['end_date'] . '<br><br>';

$html .= '<table border="1" cellspacing="0" cellpadding="5">
<thead>
<tr>
<th>#</th>
<th>Previous Start Date</th>
<th>Previous End Date</th>
<th>New Start Date</th>
<th>New End Date</th>
<th>Edited By</th>
<th>Edited At</th>
</tr>
</thead>
<tbody>';

$count = 1;
while ($log = $logs_result->fetch_assoc()) {
    $user_id = $log['edited_by'];
    $user_result = $conn->query("SELECT full_name FROM users WHERE user_id = $user_id");
    $user = $user_result->fetch_assoc();

    $html .= '<tr>';
    $html .= '<td>' . $count++ . '</td>';
    $html .= '<td>' . $log['previous_start_date'] . '</td>';
    $html .= '<td>' . $log['previous_end_date'] . '</td>';
    $html .= '<td>' . $log['new_start_date'] . '</td>';
    $html .= '<td>' . $log['new_end_date'] . '</td>';
    $html .= '<td>' . ($user['full_name'] ?? 'Unknown') . '</td>';
    $html .= '<td>' . $log['edited_at'] . '</td>';
    $html .= '</tr>';
}

$html .= '</tbody></table>';

$pdf->writeHTML($html);
$pdf->Output("edit_logs_schedule_$schedule_id.pdf", 'I');
exit();
