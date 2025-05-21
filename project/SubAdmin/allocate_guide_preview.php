<?php
ob_start();
include('header.php');

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header('Location: login.php');
    exit();
}

$selectedStudents = [];
$academicYears = [];
$students = [];
$course_id = $college_id = '';
$allocationStatus = '';
$guide_id = null;
$assignedStudentCounts = [];

// Get user info
$userQuery = "SELECT course_id, college_id FROM users WHERE user_id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userResult = $stmt->get_result();

if ($userRow = $userResult->fetch_assoc()) {
    $course_id = $userRow['course_id'];
    $college_id = $userRow['college_id'];

    // Get academic years
    $calendarQuery = "SELECT DISTINCT academic_year FROM academic_calendar WHERE course_id = ? AND is_editable = 1 AND status = 1";
    $stmt = $conn->prepare($calendarQuery);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $calendarResult = $stmt->get_result();
    while ($row = $calendarResult->fetch_assoc()) {
        $academicYears[] = $row['academic_year'];
    }

    $guides = [];
    $guideQuery = "SELECT user_id, full_name, email FROM users WHERE role = 4 AND guide_permission = 1 AND users_status = 1 AND college_id = ? AND course_id = ?";
    $stmt = $conn->prepare($guideQuery);
    $stmt->bind_param("ii", $college_id, $course_id);
    $stmt->execute();
    $guideResult = $stmt->get_result();

    while ($row = $guideResult->fetch_assoc()) {
        $guides[] = $row;
    }

    // Get selected students from form
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_students']) && !isset($_POST['final_allocate'])) {
        $selectedStudents = $_POST['selected_students'];
        $guide_id = $_POST['guide_id'] ?? null;

        $placeholders = implode(',', array_fill(0, count($selectedStudents), '?'));
        $studentQuery = "SELECT u.user_id, u.full_name AS student_name, u.email AS student_email, sa.current_semester, sa.current_academic_year 
                         FROM users u 
                         LEFT JOIN student_academics sa ON u.user_id = sa.user_id
                         WHERE u.role = 5 
                         AND u.college_id = ? 
                         AND u.course_id = ? 
                         AND u.user_id IN ($placeholders)";

        $types = str_repeat("i", count($selectedStudents));
        $stmt = $conn->prepare($studentQuery);
        $stmt->bind_param("ii" . $types, $college_id, $course_id, ...$selectedStudents);
        $stmt->execute();
        $studentResult = $stmt->get_result();

        while ($row = $studentResult->fetch_assoc()) {
            $students[] = $row;
        }

        // Allocation check for displaying status
        $allocationCheckQuery = "SELECT COUNT(*) AS allocated_students FROM guide_allocations WHERE course_id = ? AND is_current = 1";
        $stmt = $conn->prepare($allocationCheckQuery);
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $allocationCheckResult = $stmt->get_result();
        $allocationStatus = $allocationCheckResult->fetch_assoc()['allocated_students'] > 0 ? 'Allocated' : 'Not Allocated';

        // ✅ NEW: Get assigned student counts for this guide across current academic calendars
        $today = date('Y-m-d');
        $activeCalendarsQuery = "
            SELECT id, semester, academic_year 
            FROM academic_calendar 
            WHERE course_id = ? 
              AND status = 1 
              AND is_editable = 1 
              AND start_date <= ? 
              AND end_date >= ?
        ";
        $stmt = $conn->prepare($activeCalendarsQuery);
        $stmt->bind_param("iss", $course_id, $today, $today);
        $stmt->execute();
        $activeCalendarsResult = $stmt->get_result();

        while ($cal = $activeCalendarsResult->fetch_assoc()) {
            $calendar_id = $cal['id'];
            $semester = $cal['semester'];
            $academic_year = $cal['academic_year'];

            $countQuery = "
                SELECT COUNT(*) AS student_count 
                FROM guide_allocations 
                WHERE guide_user_id = ? 
                  AND academic_calendar_id = ? 
                  AND is_current = 1
            ";
            $countStmt = $conn->prepare($countQuery);
            $countStmt->bind_param("ii", $guide_id, $calendar_id);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $countRow = $countResult->fetch_assoc();

            $assignedStudentCounts[] = [
                'semester' => $semester,
                'academic_year' => $academic_year,
                'count' => $countRow['student_count']
            ];
        }
    }
}

// Handle final allocation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['final_allocate']) && isset($_POST['selected_students']) && isset($_POST['guide_id'])) {
    $selectedStudents = $_POST['selected_students'];
    $guide_id = $_POST['guide_id'];

    $insertQuery = "INSERT INTO guide_allocations 
    (student_user_id, course_id, college_id, guide_user_id, semester, academic_year, academic_calendar_id, is_current, assigned_by, assigned_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())";    
    $stmt = $conn->prepare($insertQuery);

    foreach ($selectedStudents as $student_id) {
        // Fetch semester and academic year
        $infoQuery = "SELECT sa.current_semester, sa.current_academic_year 
                      FROM student_academics sa 
                      WHERE sa.user_id = ?";
        $infoStmt = $conn->prepare($infoQuery);
        $infoStmt->bind_param("i", $student_id);
        $infoStmt->execute();
        $infoResult = $infoStmt->get_result();
        $info = $infoResult->fetch_assoc();

        $semester = $info['current_semester'] ?? null;
        $academic_year = $info['current_academic_year'] ?? null;

        // Get academic_calendar_id
        $calendarId = null;
        $calQuery = "SELECT id FROM academic_calendar WHERE course_id = ? AND semester = ? AND academic_year = ? LIMIT 1";
        $calStmt = $conn->prepare($calQuery);
        $calStmt->bind_param("iis", $course_id, $semester, $academic_year);
        $calStmt->execute();
        $calResult = $calStmt->get_result();
        if ($calRow = $calResult->fetch_assoc()) {
            $calendarId = $calRow['id'];
        }

        // Check if already allocated
        $checkQuery = "SELECT id FROM guide_allocations WHERE student_user_id = ? AND is_current = 1";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("i", $student_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows === 0 && $calendarId) {
            $assigned_by = $_SESSION['user_id'];
            $stmt->bind_param("iiiissii", $student_id, $course_id, $college_id, $guide_id, $semester, $academic_year, $calendarId, $assigned_by);
            $stmt->execute();
        }
    }

    header('Location: guide_allocated_list.php');
    exit();
}
?>


<main class="h-full overflow-y-auto">
    <div class="container px-6 mx-auto grid">
        <h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">Allocate Guide - Preview</h2>
        
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-600 dark:text-gray-300">Current Allocation Status: <?php echo $allocationStatus; ?></h3>
        </div>

        <div class="mb-4">
            <label for="guideDropdown" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Select Guide</label>
            <select id="guideDropdown" name="guide_id" class="w-full p-2 border rounded-md" onchange="showGuideDetails()">
                <option value="">-- Select Guide --</option>
                <?php foreach ($guides as $guide): ?>
                    <option value="<?php echo htmlspecialchars($guide['user_id']); ?>" 
                            data-name="<?php echo htmlspecialchars($guide['full_name']); ?>" 
                            data-email="<?php echo htmlspecialchars($guide['email']); ?>">
                        <?php echo htmlspecialchars($guide['full_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="guideDetails" class="hidden mb-6 p-4 border rounded-md bg-gray-100 dark:bg-gray-700">
    <!-- Guide Name -->
    <div class="mb-4">
        <label class="block text-sm text-gray-700 dark:text-gray-300">Guide Name</label>
        <input type="text" id="guideName" readonly class="w-full mt-1 p-2 border rounded-md bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-white">
    </div>

    <!-- Guide Email -->
    <div class="mb-4">
        <label class="block text-sm text-gray-700 dark:text-gray-300">Guide Email</label>
        <input type="text" id="guideEmail" readonly class="w-full mt-1 p-2 border rounded-md bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-white">
    </div>

    <!-- Already Assigned Students -->
    <?php if (!empty($assignedStudentCounts)): ?>
    <div class="mb-4 p-3 border rounded-md bg-white dark:bg-gray-800 shadow-sm">
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Already Assigned Students to this Guide:</label>
        <ul class="list-disc pl-5 text-sm text-gray-700 dark:text-gray-200 space-y-1">
            <?php foreach ($assignedStudentCounts as $entry): ?>
                <li>Semester <?= htmlspecialchars($entry['semester']) ?> (<?= htmlspecialchars($entry['academic_year']) ?>): <?= htmlspecialchars($entry['count']) ?> students</li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>


        <?php if (!empty($students)): ?>
        <form action="allocate_guide_preview.php" method="POST">
            <input type="hidden" id="hiddenGuideId" name="guide_id" value="<?php echo htmlspecialchars($guide_id); ?>">
            <?php foreach ($selectedStudents as $sid): ?>
                <input type="hidden" name="selected_students[]" value="<?php echo htmlspecialchars($sid); ?>">
            <?php endforeach; ?>

            <div class="w-full mb-8 overflow-hidden rounded-lg shadow-xs">
                <div class="w-full overflow-x-auto">
                    <table class="w-full whitespace-no-wrap">
                        <thead>
                            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800">
                                <th class="px-4 py-3">SR NO</th>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">EMAIL ID</th>
                                <th class="px-4 py-3">SEMESTER</th>
                                <th class="px-4 py-3">ACADEMIC YEAR</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
                            <?php foreach ($students as $index => $student): ?>
                                <tr>
                                    <td class="px-4 py-3"><?php echo $index + 1; ?></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($student['student_name']); ?></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($student['student_email']); ?></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($student['current_semester']); ?></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($student['current_academic_year']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <button type="submit" name="final_allocate" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Final Allocate
            </button>
        </form>
        <?php endif; ?>
    </div>
</main>

<script>
function showGuideDetails() {
    const dropdown = document.getElementById('guideDropdown');
    const selectedOption = dropdown.options[dropdown.selectedIndex];
    const name = selectedOption.getAttribute('data-name');
    const email = selectedOption.getAttribute('data-email');

    document.getElementById('guideName').value = name || '';
    document.getElementById('guideEmail').value = email || '';
    document.getElementById('hiddenGuideId').value = dropdown.value || '';

    document.getElementById('guideDetails').classList.remove('hidden');
}
</script>
