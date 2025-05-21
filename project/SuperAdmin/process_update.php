<?php
// Include your database connection file
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'];
    $name = $_POST['name'];
    $total_semesters = $_POST['total_semesters'];
    $duration = $_POST['duration'];

    $sql = "UPDATE courses SET name = ?, total_semesters = ?, duration = ? WHERE course_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('siii', $name, $total_semesters, $duration, $course_id);

    if ($stmt->execute()) {
        header('Location: courses.php?message=Course updated successfully');
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    header('Location: courses.php');
    exit;
}
?>
