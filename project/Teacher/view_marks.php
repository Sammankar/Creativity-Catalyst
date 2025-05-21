<?php
ob_start();
include 'header.php';
include 'connection.php';

$student_id = $_GET['student_id'] ?? 0;
$academic_year = $_GET['academic_year'] ?? '';
$semester = $_GET['semester'] ?? '';
$course = $_GET['course'] ?? '';

$stage_id = isset($_GET['stage_id']) ? intval($_GET['stage_id']) : 0;
$student_user_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$semester = isset($_GET['semester']) ? $_GET['semester'] : '';
$course = isset($_GET['course']) ? $_GET['course'] : '';
$academic_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : '';

// Fetch student details
$student_stmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = ?");
$student_stmt->bind_param("i", $student_id);
$student_stmt->execute();
$student_result = $student_stmt->get_result()->fetch_assoc();
$student_stmt->close();

// Fetch schedule
$schedule_stmt = $conn->prepare("SELECT * FROM project_submission_schedule WHERE academic_year = ? AND semester = ? AND course_id = ?");
$schedule_stmt->bind_param("ssi", $academic_year, $semester, $course);
$schedule_stmt->execute();
$schedule = $schedule_stmt->get_result()->fetch_assoc();
$schedule_stmt->close();

if (!$schedule) {
    echo "<div class='p-4 text-red-600'>No schedule found.</div>";
    exit;
}

$schedule_id = $schedule['id'];

// Fetch academic calendar end date
$calendar_stmt = $conn->prepare("SELECT end_date, declare_result FROM academic_calendar WHERE id = ?");
$calendar_stmt->bind_param("i", $schedule['academic_calendar_id']);
$calendar_stmt->execute();
$calendar_result = $calendar_stmt->get_result()->fetch_assoc();
$calendar_stmt->close();

$declare_result = $calendar_result['declare_result']; // ✅ Correct usage


$calendar_end_date = isset($calendar_result['end_date']) ? new DateTime($calendar_result['end_date']) : null;

// Fetch stages
$stages_stmt = $conn->prepare("SELECT * FROM project_submission_stages WHERE schedule_id = ? ORDER BY stage_number ASC");
$stages_stmt->bind_param("i", $schedule_id);
$stages_stmt->execute();
$stages_result = $stages_stmt->get_result();
$stages_stmt->close();

$total_marks_obtained = 0;
$total_marks = 0;
$stages_data = [];

while ($stage = $stages_result->fetch_assoc()) {
    $sub_stmt = $conn->prepare("SELECT * FROM project_stage_submissions WHERE student_user_id = ? AND stage_id = ?");
    $sub_stmt->bind_param("ii", $student_id, $stage['id']);
    $sub_stmt->execute();
    $submission = $sub_stmt->get_result()->fetch_assoc();
    $sub_stmt->close();

    if ($submission) {
        $total_marks += $stage['marks'];
        $total_marks_obtained += (float)$submission['guide_marks'];
    }

    $stages_data[] = [
        'stage' => $stage,
        'submission' => $submission
    ];
}

// Time logic
$now = new DateTime();  // Current time
$schedule_end_date = new DateTime($schedule['end_date']);  // Schedule end date (no +1 day)

// Check if the upload is allowed based on date
$can_upload = $now >= $schedule_end_date;  // Check if today is equal to or greater than the schedule's end date

// Check if marks already uploaded
$check_result_stmt = $conn->prepare("SELECT * FROM student_semester_result WHERE user_id = ? AND course_id = ? AND semester = ? AND academic_year = ?");
$check_result_stmt->bind_param("iiss", $student_id, $course, $semester, $academic_year);
$check_result_stmt->execute();
$result_data = $check_result_stmt->get_result()->fetch_assoc();
$check_result_stmt->close();

$already_uploaded = $result_data && $result_data['internal_total_marks'] !== null && $result_data['internal_obtained_marks'] !== null;

// New logic for checking if the academic year has ended
$academic_year_ended = ($calendar_end_date && $now > $calendar_end_date);  // Check if the academic year end date has passed

// Upload logic
if (isset($_POST['upload_marks']) && $can_upload && !$already_uploaded) {
    $total_marks_final = $schedule['total_marks'];
    $obtained_marks_final = $total_marks_obtained;

    // Calculate pass/fail
    $pass_threshold = $total_marks_final * 0.35;
    $status = $obtained_marks_final >= $pass_threshold ? 1 : 2;

    if ($result_data) {
        $update_stmt = $conn->prepare("UPDATE student_semester_result SET internal_total_marks = ?, internal_obtained_marks = ?, status = ?, updated_at = NOW() WHERE id = ?");
        $update_stmt->bind_param("ddii", $total_marks_final, $obtained_marks_final, $status, $result_data['id']);
        $update_stmt->execute();
        $update_stmt->close();
    } else {
        $insert_stmt = $conn->prepare("INSERT INTO student_semester_result (user_id, course_id, college_id, semester, academic_calendar_id, academic_year, internal_total_marks, internal_obtained_marks, status, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $insert_stmt->bind_param("iiissdddi", $student_id, $course, $schedule['college_id'], $semester, $schedule['academic_calendar_id'], $academic_year, $total_marks_final, $obtained_marks_final, $status);
        $insert_stmt->execute();
        $insert_stmt->close();
    }

    $already_uploaded = true;
}

?>

<main class="h-full overflow-y-auto flex justify-center bg-gray-50 py-8">
    <div class="w-full max-w-2xl px-4">
        <!-- Student Information Card -->
        <div class="mb-6 p-6 bg-white rounded-lg shadow-md">
            <h3 class="text-xl font-semibold text-gray-700 mb-4">Student Information</h3>
            <p><strong>Name:</strong> <?= htmlspecialchars($student_result['full_name']) ?></p>
            <p><strong>Semester:</strong> <?= htmlspecialchars($semester) ?></p>
            <p><strong>Academic Year:</strong> <?= htmlspecialchars($academic_year) ?></p>
            <p><strong>Total Marks:</strong> <?= $schedule['total_marks'] ?></p>
            <p><strong>Marks Obtained:</strong> <?= $total_marks_obtained ?> / <?= $total_marks ?></p>

            <?php if ($already_uploaded): ?>
                <div class="text-green-600 font-semibold mt-4">Marks already uploaded.</div>
            <?php elseif ($academic_year_ended): ?>
                <div class="text-red-600 font-semibold mt-4">
                    Academic Year <?= htmlspecialchars($academic_year) ?> has ended on <?= $calendar_end_date->format('d F Y') ?>. Marks have been uploaded.
                </div>
            <?php elseif (!$can_upload): ?>
                <div class="text-blue-600 font-semibold mt-4">
                    Upload Marks Button will be available after the End Date (like after <?= (new DateTime($schedule['end_date']))->format('d F Y') ?>).
                </div>
            <?php else: ?>
                <form method="post" onsubmit="return confirm('Are you sure you want to upload the marks? This action can only be done once.')">
                    <button type="submit" name="upload_marks" class="mt-4 px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                        Upload Marks
                    </button>
                    <div class="text-sm text-gray-500 mt-2">
                        This button will be visible until the Academic year-end date: <?= $calendar_end_date->format('d F Y') ?>.
                    </div>
                </form>
            <?php endif; ?>
            <?php 
include "connection.php";

// Assuming $student_id, $course, $semester, $academic_year, and $schedule are already available
// Also assuming $result_data is fetched above this block
// Example: $result_data = fetch_student_result($student_id, $semester, $academic_year, $course);

// Process form submission
if (isset($_POST['force_pass']) || isset($_POST['force_fail'])) {
    $external_status = isset($_POST['force_pass']) ? 1 : 2;

    if ($result_data) {
        $update_stmt = $conn->prepare("UPDATE student_semester_result SET external_status = ?, updated_at = NOW() WHERE id = ?");
        $update_stmt->bind_param("ii", $external_status, $result_data['id']);
        $update_stmt->execute();
        $update_stmt->close();

        // Redirect with status update flag
        header("Location: view_marks.php?student_id={$student_id}&academic_year={$academic_year}&semester={$semester}&course={$course}&status_updated=1");
        exit;
    } else {
        $error_message = "Error: Student not found.";
    }
}

// Determine status message only if data exists and external_status is set
$final_status_message = '';
$status_color = '';
$show_buttons = true;

if (!$result_data) {
    $final_status_message = "Error: Student not found.";
    $show_buttons = false;
} elseif (in_array($result_data['external_status'], [1, 2])) {
    if ($result_data['external_status'] == 1) {
        $final_status_message = "Student is marked as Pass";
        $status_color = "#10b981"; // Green
    } else {
        $final_status_message = "Student is marked as Fail";
        $status_color = "#ef4444"; // Red
    }
    $show_buttons = false;
}
?>


<!-- Display Section -->
<div class="mt-6">
<h4 class="text-lg font-semibold text-gray-700 mb-2">External Pass/Fail option appears after university result is declared.</h4>
    <?php if ($declare_result == 1): ?>
<div class="mt-6">
    

    <?php if (!$show_buttons): ?>
        <div class="mt-4">
            <button disabled class="px-4 py-2 text-white rounded-md cursor-not-allowed" style="background-color: <?= $status_color ?>;">
                <?= htmlspecialchars($final_status_message) ?>
            </button>
        </div>
    <?php else: ?>
        <form method="post" class="flex gap-4" onsubmit="return confirmSubmit(event)">
            <button type="submit" name="force_pass" class="px-4 py-2 bg-green-100 text-black rounded-md hover:bg-green-700 mr-2">
                Pass
            </button>
            <button type="submit" name="force_fail" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                Fail
            </button>
        </form>
    <?php endif; ?>
</div>
<?php endif; ?>


    <?php if (isset($_GET['status_updated']) && $_GET['status_updated'] == 1): ?>
        <div class="mt-4 bg-green-100 text-green-800 px-4 py-2 rounded">
            Student status updated successfully.
        </div>
    <?php endif; ?>
</div>

            <a href="view_submissions.php?student_id=<?= $student_user_id ?>&academic_year=<?= urlencode($academic_year) ?>&semester=<?= urlencode($semester) ?>&course=<?= urlencode($course) ?>" 
               class="inline-block mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
               Back
            </a>
        </div>

        <!-- Stage Cards -->
        <?php foreach ($stages_data as $item): 
            $stage = $item['stage'];
            $submission = $item['submission'];
        ?>
        <div class="mb-6 p-6 bg-white rounded-lg shadow-md">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">
                Stage <?= $stage['stage_number'] ?>: <?= htmlspecialchars($stage['title']) ?>
            </h3>
            <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($stage['description'])) ?></p>

            <?php if ($submission): ?>
                <p class="mt-2"><strong>Marks:</strong> <?= $submission['guide_marks'] ?> / <?= $stage['marks'] ?></p>
                <p><strong>Feedback:</strong> <?= nl2br(htmlspecialchars($submission['guide_feedback'] ?? 'None')) ?></p>
                <div class="mt-2">
                    <?php
                    $file_paths = array_filter(array_map('trim', explode(',', $submission['submitted_files'])));
                    if (!empty($file_paths)):
                        foreach ($file_paths as $index => $file_path):
                    ?>
                        <p>
                            File <?= $index + 1 ?>: 
                            <a href="<?= '../Student/images/stages/' . htmlspecialchars($file_path) ?>" 
                               class="text-blue-600 underline" 
                               target="_blank" download>
                               View / Download
                            </a>
                        </p>
                    <?php endforeach; else: ?>
                        <p class="text-red-500">No files uploaded.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p class="text-red-600 mt-2">No submission uploaded for this stage.</p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</main>
<script>
function confirmSubmit(event) {
    const btn = event.submitter;
    let action = '';
    if (btn.name === 'force_pass') action = 'mark this student as PASS';
    if (btn.name === 'force_fail') action = 'mark this student as FAIL';

    return confirm(`Are you sure you want to ${action}?`);
}
</script>