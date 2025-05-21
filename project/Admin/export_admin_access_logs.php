<?php
session_start();
require_once(__DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php'); // Ensure TCPDF is included
include 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user role and access status
$query = "SELECT role FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$role = $user['role'];

// Fetch reports data
$query = "SELECT 
            adr.*, 
            sa.full_name AS changed_by_name, 
            admin.full_name AS user_name, 
            admin.access_status,
            adr.previous_access_status,
            adr.current_access_status
          FROM access_denial_reports adr
          JOIN users sa ON adr.changed_by = sa.user_id
          JOIN users admin ON adr.user_id = admin.user_id
          ORDER BY adr.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$reports = $result->fetch_all(MYSQLI_ASSOC);

// Create new PDF document
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Your System');
$pdf->SetTitle('Access Reports Logs');
$pdf->SetMargins(10, 10, 10);
$pdf->AddPage();

// Title
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(190, 10, 'Access Reports Logs', 0, 1, 'C');
$pdf->Ln(5);

// Table Header
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(40, 10, 'Admin Name', 1, 0, 'C');
$pdf->Cell(40, 10, 'Super-Admin Name', 1, 0, 'C');
$pdf->Cell(40, 10, 'Prev Status', 1, 0, 'C');
$pdf->Cell(40, 10, 'Current Status', 1, 0, 'C');
$pdf->Cell(30, 10, 'Report Status', 1, 1, 'C');

// Table Content
$pdf->SetFont('helvetica', '', 10);
foreach ($reports as $report) {
    $previous_status = $report['previous_access_status'] == 1 ? 'Granted' : 'Restricted';
    $current_status = $report['current_access_status'] == 1 ? 'Granted' : 'Restricted';
    $report_status = $report['access_denial_report_status'] == 0 ? 'Ongoing' : 'Resolved';

    $pdf->Cell(40, 10, $report['user_name'], 1, 0, 'C');
    $pdf->Cell(40, 10, $report['changed_by_name'], 1, 0, 'C');
    $pdf->Cell(40, 10, $previous_status, 1, 0, 'C');
    $pdf->Cell(40, 10, $current_status, 1, 0, 'C');
    $pdf->Cell(30, 10, $report_status, 1, 1, 'C');
}

// Output PDF
$pdf->Output('Access_Reports_Logs.pdf', 'D');
?>
