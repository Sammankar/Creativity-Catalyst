<?php
require_once(__DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php');
require_once('connection.php'); // DB connection

if (!isset($_GET['calendar_id'])) {
    die("Missing calendar ID");
}

$calendar_id = $_GET['calendar_id'];

// Fetch upgrade logs with user, course, college details
$sql = "
SELECT 
    logs.*, 
    u.full_name AS student_name,
    upgrader.full_name AS upgrader_name,
    c.name AS course_name,
    cl.name AS college_name,
    cal_new.academic_year AS new_year,
    cal_new.start_date AS new_start_date,
    cal_new.end_date AS new_end_date,
    cal_old.academic_year AS prev_year
FROM academic_semester_upgrade_logs AS logs
LEFT JOIN users AS u ON logs.user_id = u.user_id
LEFT JOIN users AS upgrader ON logs.upgraded_by = upgrader.user_id
LEFT JOIN courses AS c ON logs.course_id = c.course_id
LEFT JOIN colleges AS cl ON logs.college_id = cl.college_id
LEFT JOIN academic_calendar AS cal_old ON logs.previous_calendar_id = cal_old.id
LEFT JOIN academic_calendar AS cal_new ON logs.academic_calendar_id = cal_new.id
WHERE logs.previous_calendar_id = ?
ORDER BY logs.upgraded_at DESC
";


$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $calendar_id);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    // Format nulls for calendar data
    $row['new_year'] = $row['new_year'] ?: 'Not Scheduled';
    $row['new_start_date'] = $row['new_start_date'] ?: 'Not Scheduled';
    $row['new_end_date'] = $row['new_end_date'] ?: 'Not Scheduled';
    $row['prev_year'] = $row['prev_year'] ?: 'Not Scheduled';
    $rows[] = $row;
}

// Create PDF
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Student Management System');
$pdf->SetTitle('Semester Upgrade Report');
$pdf->SetMargins(10, 10, 10);
$pdf->AddPage();

// Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Semester Upgrade Report', 0, 1, 'C');
$pdf->Ln(5);

// Table header
$pdf->SetFont('helvetica', 'B', 10);
$tableHTML = '
<table border="1" cellpadding="4">
    <thead>
        <tr style="background-color:#f2f2f2;">
            <th>Student</th>
            <th>College</th>
            <th>Course</th>
            <th>Prev Sem</th>
            <th>New Sem</th>
            <th>Prev Year</th>
            <th>New Year</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Upgraded On</th>
            <th>Updated By</th>
        </tr>
    </thead>
    <tbody>
';

foreach ($rows as $r) {
    $tableHTML .= '<tr>
        <td>' . htmlspecialchars($r['student_name']) . '</td>
        <td>' . htmlspecialchars($r['college_name']) . '</td>
        <td>' . htmlspecialchars($r['course_name']) . '</td>
        <td>' . $r['previous_semester'] . '</td>
        <td>' . $r['current_semester'] . '</td>
        <td>' . $r['prev_year'] . '</td>
        <td>' . $r['new_year'] . '</td>
        <td>' . $r['new_start_date'] . '</td>
        <td>' . $r['new_end_date'] . '</td>
        <td>' . date('Y-m-d', strtotime($r['upgraded_at'])) . '</td>
        <td>' . htmlspecialchars($r['upgrader_name']) . '</td>  
    </tr>';
}

$tableHTML .= '</tbody></table>';

$pdf->SetFont('helvetica', '', 9);
$pdf->writeHTML($tableHTML, true, false, true, false, '');

// Output the PDF
$pdf->Output('semester_upgrade_report.pdf', 'D');
?>