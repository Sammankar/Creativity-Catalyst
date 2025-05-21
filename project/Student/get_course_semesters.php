<?php
// Database connection
include 'connection.php';

// Check if the course_id is provided in the GET request
if (isset($_GET['course_id'])) {
    $course_id = $_GET['course_id'];

    // Query to get the total_semesters for the selected course
    $query = "SELECT total_semesters FROM courses WHERE course_id = ?";
    if ($stmt = $conn->prepare($query)) {
        // Bind the course_id parameter and execute the statement
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $stmt->bind_result($total_semesters);
        $stmt->fetch();

        // Check if a valid total_semesters was fetched
        if ($total_semesters) {
            // Return the total semesters as JSON
            echo json_encode(['total_semesters' => $total_semesters]);
        } else {
            // If course doesn't exist or no semesters are found, return an error
            echo json_encode(['error' => 'Course not found']);
        }

        $stmt->close();
    } else {
        // Return error if there's an issue with the query
        echo json_encode(['error' => 'Failed to prepare query']);
    }
} else {
    // If no course_id is passed, return an error
    echo json_encode(['error' => 'Course ID is missing']);
}

// Close the database connection
$conn->close();
?>
