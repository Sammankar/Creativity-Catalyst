<?php
session_start();
include 'connection.php';
require_once(__DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php'); // Include TCPDF library

// Fetch logs data
$query = "SELECT cr.request_id, u.full_name AS admin_name, u.email AS admin_email,
                 cr.status, cr.created_at, cr.status_updated_at, 
                 GROUP_CONCAT(co.name SEPARATOR ', ') AS requested_courses,
                 cl.name AS college_name
          FROM course_requests cr
          LEFT JOIN users u ON cr.admin_id = u.user_id
          LEFT JOIN courses co ON cr.requested_course_id = co.course_id
          LEFT JOIN colleges cl ON cr.college_id = cl.college_id
          GROUP BY cr.request_id
          ORDER BY cr.created_at DESC";

$result = mysqli_query($conn, $query);

// Create PDF instance
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetTitle('Course Request Logs');
$pdf->AddPage();

// Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(190, 10, 'Course Request Logs', 0, 1, 'C');
$pdf->Ln(5);

// Table Header
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(30, 10, 'College', 1);
$pdf->Cell(40, 10, 'Admin', 1);
$pdf->Cell(30, 10, 'Courses', 1);
$pdf->Cell(30, 10, 'Status', 1);
$pdf->Cell(40, 10, 'Created Date', 1);
$pdf->Cell(40, 10, 'Updated Date', 1);
$pdf->Ln();

// Table Data
$pdf->SetFont('helvetica', '', 10);
while ($row = mysqli_fetch_assoc($result)) {
    $status = ($row['status'] == 0) ? "Pending" : (($row['status'] == 1) ? "Approved" : "Rejected");

    $created_at = date("d M Y, h:i A", strtotime($row['created_at']));
    $updated_at = (!empty($row['status_updated_at'])) ? date("d M Y, h:i A", strtotime($row['status_updated_at'])) : 'N/A';

    $pdf->Cell(30, 10, $row['college_name'], 1);
    $pdf->Cell(40, 10, $row['admin_name'], 1);
    $pdf->Cell(30, 10, $row['requested_courses'], 1);
    $pdf->Cell(30, 10, $status, 1);
    $pdf->Cell(40, 10, $created_at, 1);
    $pdf->Cell(40, 10, $updated_at, 1);
    $pdf->Ln();
}

// Output PDF
$pdf->Output('course_logs.pdf', 'D'); // 'D' forces download
?>
