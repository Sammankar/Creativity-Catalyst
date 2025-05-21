<?php
include('header.php');

if (!isset($_GET['student_id'])) {
    echo "Student ID is missing.";
    exit;
}

$student_id = (int) $_GET['student_id'];
$student_name = '';
$current_semester = 0;
$allSemesters = [];

// Get sub-admin's course_id and college_id from session (assuming session stores them)
$subAdminId = $_SESSION['user_id'] ?? null;
$course_id = 0;
$college_id = 0;

if ($subAdminId) {
    $query = "SELECT course_id, college_id FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $subAdminId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $adminData = $res->fetch_assoc();
        $course_id = $adminData['course_id'];
        $college_id = $adminData['college_id'];
    }
}

// Fetch student full name and current semester
$studentQuery = "
    SELECT u.full_name, sa.current_semester
    FROM users u
    JOIN student_academics sa ON u.user_id = sa.user_id
    WHERE u.user_id = ?
      AND u.course_id = ?
      AND u.college_id = ?
";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("iii", $student_id, $course_id, $college_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Student not found or does not belong to your college/course.";
    exit;
}

$studentInfo = $result->fetch_assoc();
$student_name = $studentInfo['full_name'];
$current_semester = (int) $studentInfo['current_semester'];

// Fetch semester-wise data for the student
$recordQuery = "
    SELECT semester, academic_year, status, external_status
    FROM student_semester_result
    WHERE user_id = ?
      AND course_id = ?
      AND college_id = ?
    ORDER BY semester DESC
";
$stmt = $conn->prepare($recordQuery);
$stmt->bind_param("iii", $student_id, $course_id, $college_id);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $sem = (int) $row['semester'];
    $status = (int) $row['status'];
    $external_status = (int) $row['external_status'];

    // Determine result statuses
    if ($status === 0) {
        $internal_status = "In Progress";
        $external_result = "-";
        $overall_status = "In Progress";
    } elseif ($status === 1 && $external_status === 0) {
        $internal_status = "Passed";
        $external_result = "Not Declared";
        $overall_status = "Awaiting External";
    } elseif ($status === 1 && $external_status === 1) {
        $internal_status = "Passed";
        $external_result = "Passed";
        $overall_status = "Passed";
    } else {
        $internal_status = "Unknown";
        $external_result = "Unknown";
        $overall_status = "Unknown";
    }

    // Fetch allocated guide name for this semester
    $guide_name = "-";
    $guideQuery = "
        SELECT g.guide_user_id, u.full_name
        FROM guide_allocations g
        JOIN users u ON g.guide_user_id = u.user_id
        WHERE g.student_user_id = ?
          AND g.semester = ?
          AND g.course_id = ?
          AND g.college_id = ?
          AND g.is_current = 1
        LIMIT 1
    ";
    $stmtGuide = $conn->prepare($guideQuery);
    $stmtGuide->bind_param("iiii", $student_id, $sem, $course_id, $college_id);
    $stmtGuide->execute();
    $guideResult = $stmtGuide->get_result();

    if ($guideResult->num_rows > 0) {
        $guideRow = $guideResult->fetch_assoc();
        $guide_name = $guideRow['full_name'];
    }

    $allSemesters[$sem] = [
        'academic_year' => $row['academic_year'],
        'semester' => $row['semester'],
        'internal_status' => $internal_status,
        'external_result' => $external_result,
        'overall_status' => $overall_status,
        'guide_name' => $guide_name,
    ];
}
?>

<main class="h-full overflow-y-auto">
    <div class="container px-6 mx-auto grid">
        <h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">
            <?php echo htmlspecialchars($student_name); ?> - Current Semester: <?php echo $current_semester; ?>
        </h2>

       <div class="mb-4">
            <a href="student_data.php" 
               class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-md inline-block w-auto">
                Back
            </a>
        </div>

        <?php
        for ($sem = $current_semester; $sem >= 1; $sem--):
            $data = $allSemesters[$sem] ?? null;
        ?>
            <div class="mb-6">
                <h4 class="mb-2 text-lg font-semibold text-gray-600 dark:text-gray-300">
                    Semester <?php echo $sem; ?>
                </h4>
                <?php if ($data): ?>
                    <div class="w-full mb-4 overflow-hidden rounded-lg shadow-xs">
                        <div class="w-full overflow-x-auto">
                            <table class="w-full whitespace-no-wrap">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800">
                                        <th class="px-4 py-3">Name</th>
                                        <th class="px-4 py-3">Academic Year</th>
                                        <th class="px-4 py-3">Semester</th>
                                        <th class="px-4 py-3">Internal Result Status</th>
                                        <th class="px-4 py-3">External Result Status</th>
                                        <th class="px-4 py-3">Overall Status</th>
                                        <th class="px-4 py-3">Allocated Guide</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
                                    <tr class="text-gray-700 dark:text-gray-400">
                                        <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($student_name); ?></td>
                                        <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($data['academic_year']); ?></td>
                                        <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($data['semester']); ?></td>
                                        <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($data['internal_status']); ?></td>
                                        <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($data['external_result']); ?></td>
                                        <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($data['overall_status']); ?></td>
                                        <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($data['guide_name']); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 dark:text-gray-400">No data available for Semester <?php echo $sem; ?>.</p>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    </div>
</main>
