<?php
session_start();
include "connection.php";

if (!isset($_SESSION['user_id']) || !isset($_GET['calendar_id'])) {
  header("Location: notifications.php");
  exit();
}

$user_id = $_SESSION['user_id'];
$calendar_id = intval($_GET['calendar_id']);

// Check if already read
$check = $conn->prepare("SELECT * FROM notification_reads WHERE user_id = ? AND calendar_id = ?");
$check->bind_param("ii", $user_id, $calendar_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows == 0) {
  // Insert read log
  $insert = $conn->prepare("INSERT INTO notification_reads (user_id, calendar_id) VALUES (?, ?)");
  $insert->bind_param("ii", $user_id, $calendar_id);
  $insert->execute();
}

header("Location: academic_calender.php");
exit();
