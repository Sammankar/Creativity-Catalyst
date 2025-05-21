<?php
// fetchSubAdminDetails.php

include 'connection.php';

$sub_admin_id = $_GET['sub_admin_id'];

$stmt = $conn->prepare("SELECT full_name, email, phone_number FROM users WHERE user_id = ?");
$stmt->bind_param("i", $sub_admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    echo json_encode($user);
} else {
    echo json_encode(['error' => 'Sub-admin not found']);
}
?>
