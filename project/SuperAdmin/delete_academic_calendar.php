<?php
include "connection.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Ensure calendar is not released
    $checkQuery = "SELECT status FROM academic_calendar WHERE id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($status);
    $stmt->fetch();
    $stmt->close();

    if ($status !== 0) {
        echo json_encode(['success' => false, 'message' => 'This academic calendar has already been released and cannot be deleted.']);
        exit;
    }

    // Safe to delete
    $deleteQuery = "DELETE FROM academic_calendar WHERE id = ?";
    $stmtDel = $conn->prepare($deleteQuery);
    $stmtDel->bind_param("i", $id);

    if ($stmtDel->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error while deleting.']);
    }

    $stmtDel->close();
}
?>
