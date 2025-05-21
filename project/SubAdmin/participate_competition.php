<?php
session_start();
include "connection.php";

if (!isset($_SESSION['user_id']) || !isset($_POST['competition_id'])) {
    echo "unauthorized";
    exit;
}

$sub_admin_id = $_SESSION['user_id'];
$competition_id = intval($_POST['competition_id']);

// Get college_id
$sql = "SELECT college_id FROM users WHERE user_id = $sub_admin_id";
$res = $conn->query($sql);
if ($res->num_rows == 0) {
    echo "no_college";
    exit;
}
$college_id = $res->fetch_assoc()['college_id'];

// Check if already participated
$check = "SELECT * FROM college_competitions WHERE competition_id=$competition_id AND sub_admin_id=$sub_admin_id";
if ($conn->query($check)->num_rows > 0) {
    echo "already";
    exit;
}

// Insert participation
$insert = "INSERT INTO college_competitions (competition_id, college_id, sub_admin_id) 
           VALUES ($competition_id, $college_id, $sub_admin_id)";

if ($conn->query($insert)) {
    echo "success";
} else {
    echo "error";
}
?>
