<?php
include 'connection.php';

function assignEvaluatorsRoundRobin($competition_id, $conn) {
    // Get all verified submissions for this competition
    $submissions = $conn->query("
        SELECT submission_id FROM student_submissions 
        WHERE competition_id = $competition_id AND is_verified_by_project_head = 1
    ");

    // Get evaluators for this competition
    $evaluators = $conn->query("
        SELECT user_id FROM evaluators 
        WHERE competition_id = $competition_id
    ");

    if ($submissions->num_rows == 0 || $evaluators->num_rows == 0) return;

    // Fetch all submission IDs into an array
    $submissionList = [];
    while ($row = $submissions->fetch_assoc()) {
        $submissionList[] = $row['submission_id'];
    }

    // Fetch all evaluator IDs into an array
    $evaluatorList = [];
    while ($row = $evaluators->fetch_assoc()) {
        $evaluatorList[] = $row['user_id'];
    }

    // Check number of evaluators
    $evaluatorCount = count($evaluatorList);
    $evaluatorIndex = 0;

    // Assign evaluators to each submission (round-robin)
    foreach ($submissionList as $submission_id) {
        // Assign two evaluators to each submission, ensuring no duplicates
        for ($i = 0; $i < 2; $i++) {
            $evaluator_id = $evaluatorList[$evaluatorIndex % $evaluatorCount];
            
            // Ensure we don't assign the same evaluator twice to the same submission
            $existingAssignmentCheck = $conn->query("
                SELECT COUNT(*) AS count
                FROM evaluator_assignments 
                WHERE competition_id = $competition_id
                AND submission_id = $submission_id
                AND evaluator_id = $evaluator_id
            ");
            
            $existingAssignment = $existingAssignmentCheck->fetch_assoc();
            
            // If evaluator is not already assigned to the submission, insert
            if ($existingAssignment['count'] == 0) {
                $conn->query("
                    INSERT INTO evaluator_assignments (competition_id, submission_id, evaluator_id)
                    VALUES ($competition_id, $submission_id, $evaluator_id)
                ");
            }

            // Move to the next evaluator
            $evaluatorIndex++;
        }
    }
}

?>
