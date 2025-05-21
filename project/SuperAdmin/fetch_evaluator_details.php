<?php
include 'connection.php';

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? 0;

if ($user_id) {
    // Fetch evaluator basic details + competition counts
    $stmt = $conn->prepare("
        SELECT u.full_name, u.email, u.phone_number,
               (SELECT COUNT(*) 
                FROM evaluators e 
                JOIN competitions c ON e.competition_id = c.competition_id 
                WHERE e.user_id = u.user_id AND c.competition_status = 1) AS active_count,
               (SELECT COUNT(*) 
                FROM evaluators e 
                JOIN competitions c ON e.competition_id = c.competition_id 
                WHERE e.user_id = u.user_id AND c.competition_status = 2) AS completed_count
        FROM users u
        WHERE u.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $evaluator = $result->fetch_assoc();

    // Fetch active competition details
    $stmt2 = $conn->prepare("
        SELECT c.name, c.evaluation_start_date, c.evaluation_end_date, c.result_declaration_date 
        FROM evaluators e
        JOIN competitions c ON e.competition_id = c.competition_id
        WHERE e.user_id = ? AND c.competition_status = 1
    ");
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $activeCompetitionsResult = $stmt2->get_result();
    $activeCompetitions = [];

    while ($row = $activeCompetitionsResult->fetch_assoc()) {
        $activeCompetitions[] = $row;
    }

    if ($evaluator) {
        echo json_encode([
            'name' => $evaluator['full_name'],
            'email' => $evaluator['email'],
            'phone' => $evaluator['phone_number'],
            'active_count' => $evaluator['active_count'],
            'completed_count' => $evaluator['completed_count'],
            'active_competitions' => $activeCompetitions,
        ]);
    } else {
        echo json_encode(['error' => 'Evaluator not found']);
    }
} else {
    echo json_encode(['error' => 'Invalid user ID']);
}

?>
