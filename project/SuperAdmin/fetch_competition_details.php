<?php // fetch_competition_details.php
include "connection.php";

if (isset($_GET['id'])) {
    $competitionId = $_GET['id'];

    // Fetch the competition details
    $query = "SELECT * FROM competitions WHERE competition_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $competitionId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $competition = $result->fetch_assoc();

        // Format dates to "1 Jan 2025" format
        $dateFormat = 'd M Y';

        $competition['college_registration_start_date'] = date($dateFormat, strtotime($competition['college_registration_start_date']));
        $competition['college_registration_end_date'] = date($dateFormat, strtotime($competition['college_registration_end_date']));
        $competition['student_submission_start_date'] = date($dateFormat, strtotime($competition['student_submission_start_date']));
        $competition['student_submission_end_date'] = date($dateFormat, strtotime($competition['student_submission_end_date']));
        $competition['evaluation_start_date'] = date($dateFormat, strtotime($competition['evaluation_start_date']));
        $competition['evaluation_end_date'] = date($dateFormat, strtotime($competition['evaluation_end_date']));
        $competition['result_declaration_date'] = date($dateFormat, strtotime($competition['result_declaration_date']));

        // Return data as JSON
        echo json_encode(['success' => true, 'competition' => $competition]);
    } else {
        echo json_encode(['success' => false]);
    }
}
?>