<?php
ob_start();
include('header.php');
include('connection.php');

$allocationDetails = null;
$allocationStatus = 'Not Allocated';

$loggedUserId = $_SESSION['user_id'];
$studentId = $_GET['user_id'] ?? null;

if (!$studentId) {
    die("Student ID not provided.");
}

// Get sub-admin's college_id and course_id
$userQuery = "SELECT college_id, course_id FROM users WHERE user_id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("i", $loggedUserId);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();
$college_id = $userData['college_id'];
$course_id = $userData['course_id'];

// Fetch current allocation
$query = "
    SELECT 
        ga.id AS guide_allocation_id,
        s.full_name AS student_name,
        g.full_name AS guide_name,
        g.user_id AS current_guide_id,
        c.name AS course_name,
        sa.current_semester AS semester,
        sa.current_academic_year AS academic_year,
        ga.assigned_at,
        a.full_name AS sub_admin_name,
        ga.academic_calendar_id
    FROM guide_allocations ga
    LEFT JOIN users s ON ga.student_user_id = s.user_id
    LEFT JOIN users g ON ga.guide_user_id = g.user_id
    LEFT JOIN users a ON ga.assigned_by = a.user_id
    LEFT JOIN student_academics sa ON ga.student_user_id = sa.user_id
    LEFT JOIN courses c ON s.course_id = c.course_id
    WHERE ga.student_user_id = ? AND ga.is_current = 1
    LIMIT 1
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $allocationDetails = $result->fetch_assoc();
    $allocationStatus = 'Allocated';
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

// Handle update request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guide_id'])) {
    $newGuideId = (int)$_POST['guide_id'];
    $currentGuideId = $allocationDetails['current_guide_id'] ?? null;
    $semester = $allocationDetails['semester'];
    $academicYear = $allocationDetails['academic_year'];
    $calendarId = $allocationDetails['academic_calendar_id'];

    if ($newGuideId && $newGuideId != $currentGuideId) {
        // 1. Mark current record as inactive
        $updateOld = "UPDATE guide_allocations SET is_current = 0, updated_at = NOW() WHERE student_user_id = ? AND is_current = 1";
        $stmt = $conn->prepare($updateOld);
        $stmt->bind_param("i", $studentId);
        $stmt->execute();

        // 2. Insert new guide allocation
        $insert = "INSERT INTO guide_allocations (
            student_user_id, guide_user_id, college_id, course_id, semester, academic_year, academic_calendar_id,
            assigned_by, assigned_at, is_current, previous_guide_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1, ?)";

        $stmt = $conn->prepare($insert);
        $stmt->bind_param("iiiiissii", $studentId, $newGuideId, $college_id, $course_id, $semester, $academicYear, $calendarId, $loggedUserId, $currentGuideId);
        $stmt->execute();

        // Redirect to list
        header("Location: guide_allocated_list.php?updated=1");
        exit;
    }
}
?>

<!-- HTML UI Part (same as before + Update button at the bottom) -->
<div class="container px-6 mx-auto grid">
    <h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">
        Allocate Guide - Preview
    </h2>

    <!-- Back Button -->
    <div class="flex items-center mb-4 space-x-4">
        <a href="guide_allocated_list.php"
           class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-md shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">
            Back To Guide Allocated List
        </a>
    </div>

    <!-- Existing Allocation -->
    <?php if ($allocationDetails): ?>
        <div class="mb-6 p-6 border rounded-md bg-gray-50 dark:bg-gray-700 shadow">
            <h4 class="text-md font-semibold mb-4 text-gray-700 dark:text-gray-200">
                Existing Guide Allocation Details
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                <div>
                    <label class="block text-gray-600 dark:text-gray-300">Student Name</label>
                    <input type="text" readonly value="<?= htmlspecialchars($allocationDetails['student_name']) ?>"
                           class="w-full mt-1 p-2 rounded-md bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-gray-600 dark:text-gray-300">Guide Name</label>
                    <input type="text" readonly value="<?= htmlspecialchars($allocationDetails['guide_name']) ?>"
                           class="w-full mt-1 p-2 rounded-md bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-gray-600 dark:text-gray-300">Course Name</label>
                    <input type="text" readonly value="<?= htmlspecialchars($allocationDetails['course_name']) ?>"
                           class="w-full mt-1 p-2 rounded-md bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-gray-600 dark:text-gray-300">Semester</label>
                    <input type="text" readonly value="<?= htmlspecialchars($allocationDetails['semester']) ?>"
                           class="w-full mt-1 p-2 rounded-md bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-gray-600 dark:text-gray-300">Academic Year</label>
                    <input type="text" readonly value="<?= htmlspecialchars($allocationDetails['academic_year']) ?>"
                           class="w-full mt-1 p-2 rounded-md bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-gray-600 dark:text-gray-300">Assigned By (Sub-Admin)</label>
                    <input type="text" readonly value="<?= htmlspecialchars($allocationDetails['sub_admin_name']) ?>"
                           class="w-full mt-1 p-2 rounded-md bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-white">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-gray-600 dark:text-gray-300">Assigned At</label>
                    <input type="text" readonly value="<?= date('d-m-Y', strtotime($allocationDetails['assigned_at'])) ?>"
                           class="w-full mt-1 p-2 rounded-md bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-white">
                </div>
            </div>
        </div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-6 p-6 border rounded-md bg-gray-50 dark:bg-gray-700 shadow">
            <h4 class="text-md font-semibold mb-4 text-gray-700 dark:text-gray-200">Assign New Guide</h4>

            <div class="mb-4">
                <label for="guideDropdown"
                       class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Select Guide</label>
                <select id="guideDropdown" name="guide_id"
                        class="w-full p-2 border rounded-md bg-white dark:bg-gray-600 text-gray-700 dark:text-white"
                        onchange="showGuideDetails(this)">
                    <option value="">-- Select Guide --</option>
                    <?php foreach ($guides as $guide): ?>
                        <option 
                            value="<?= htmlspecialchars($guide['user_id']) ?>"
                            data-name="<?= htmlspecialchars($guide['full_name'])?>"
                             data-email="<?= htmlspecialchars($guide['email']) ?>"> 
                             <?= htmlspecialchars($guide['full_name']) ?> 
                            </option> 
                            <?php endforeach; ?> 
                        </select> 
                    </div>

                    <div id="guideDetails" class="hidden mt-6 p-4 rounded-md bg-gray-100 dark:bg-gray-800">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300">Guide Name</label>
                    <input type="text" id="guideName" readonly
                           class="w-full mt-1 p-2 border rounded-md bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300">Guide Email</label>
                    <input type="text" id="guideEmail" readonly
                           class="w-full mt-1 p-2 border rounded-md bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-white">
                </div>
            </div>
            <div id="guideStudentCount" class="mt-4 text-sm text-gray-700 dark:text-gray-300"></div>
        </div>
            <!-- Update Button -->
    <div class="mb-10">
        <button type="submit"
                class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-md shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">
            Update Guide Allocation
        </button>
    </div>
    </div>


</form>

    <!-- Guide Dropdown -->
   
</div> <script> function showGuideDetails(select) { const selectedOption = select.options[select.selectedIndex]; const guideId = select.value; if (!guideId) { document.getElementById('guideDetails').classList.add('hidden'); return; } document.getElementById('guideName').value = selectedOption.getAttribute('data-name'); document.getElementById('guideEmail').value = selectedOption.getAttribute('data-email'); document.getElementById('guideDetails').classList.remove('hidden'); fetch('fetch_guide_count.php?guide_id=' + guideId) .then(res => res.text()) .then(data => { document.getElementById('guideStudentCount').innerHTML = data; }); } </script>
