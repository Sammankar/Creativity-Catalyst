<?php
session_start();
include 'connection.php';

if (!isset($_GET['file'])) {
    die("No file specified.");
}

// Get the file name from the query parameter
$file = $_GET['file'];

// Define the full path to the file in the images/admin_access_reports directory
$file_path = __DIR__ . '/images/evaluator_access_reports/' . $file;


// Check if the file exists
if (!file_exists($file_path)) {
    die("File not found.");
}

// Set the headers to force the browser to download the file
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path));

// Read the file and send it to the browser
readfile($file_path);
exit;
?>
