<?php
include 'header.php';
include 'connection.php';

$today = date('Y-m-d');

// Get parameters from the URL
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$academic_year = $_GET['academic_year'] ?? '';
$semester = $_GET['semester'] ?? '';
$course = $_GET['course'] ?? '';

// Fetch the matching schedule ID
$schedule_sql = "SELECT * FROM project_submission_schedule 
                 WHERE academic_year = ? AND semester = ? AND course_id = ? AND is_editable = 1";
$schedule_stmt = $conn->prepare($schedule_sql);
$schedule_stmt->bind_param("ssi", $academic_year, $semester, $course);
$schedule_stmt->execute();
$schedule = $schedule_stmt->get_result()->fetch_assoc();
$schedule_stmt->close();

if (!$schedule) {
    echo "<div class='p-4 text-red-600'>No submission schedule found for the selected parameters.</div>";
    exit;
}

$schedule_id = $schedule['id'];

// Get student’s submission IDs for this schedule
$submission_ids = [];
$submission_sql = "SELECT s.id FROM project_stage_submissions s
                   INNER JOIN project_submission_stages ps ON s.stage_id = ps.id
                   WHERE s.student_user_id = ? AND ps.schedule_id = ?";
$submission_stmt = $conn->prepare($submission_sql);
$submission_stmt->bind_param("ii", $student_id, $schedule_id);
$submission_stmt->execute();
$submission_result = $submission_stmt->get_result();
while ($row = $submission_result->fetch_assoc()) {
    $submission_ids[] = $row['id'];
}
$submission_stmt->close();

// Fetch all stages for the schedule
$stages_stmt = $conn->prepare("SELECT * FROM project_submission_stages WHERE schedule_id = ? ORDER BY unlock_date ASC");
$stages_stmt->bind_param("i", $schedule_id);
$stages_stmt->execute();
$stages_result = $stages_stmt->get_result();
?>

<main class="h-full overflow-y-auto">
    <div class="container px-6 mx-auto">
        <div class="flex flex-wrap items-center justify-between my-6">
            <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-200">
                Viewing Student Submissions
            </h2>
            <?php
            include "connection.php";
$chat_disabled = false;

// Fetch academic end date from academic_calendar
$calendar_sql = "SELECT ac.end_date 
                 FROM academic_calendar ac
                 INNER JOIN project_submission_schedule pss 
                 ON ac.id = pss.academic_calendar_id
                 WHERE pss.id = ?";
$calendar_stmt = $conn->prepare($calendar_sql);
$calendar_stmt->bind_param("i", $schedule_id);
$calendar_stmt->execute();
$calendar_result = $calendar_stmt->get_result();
if ($calendar_row = $calendar_result->fetch_assoc()) {
    $end_datetime = $calendar_row['end_date'] . ' 23:59:59';
    if (date('Y-m-d H:i:s') > $end_datetime) {
        $chat_disabled = true;
    }
}
$calendar_stmt->close();
?>

            <div class="flex space-x-2 mt-2 md:mt-0">
            <?php if (!empty($submission_ids)): ?>
    <!-- View Marks Button -->
    <form method="GET" action="view_marks.php">
        <input type="hidden" name="student_id" value="<?= $student_id ?>">
        <input type="hidden" name="academic_year" value="<?= htmlspecialchars($academic_year) ?>">
        <input type="hidden" name="semester" value="<?= htmlspecialchars($semester) ?>">
        <input type="hidden" name="course" value="<?= htmlspecialchars($course) ?>">
        
        <button type="submit" 
                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-600 mr-2">
            View Marks
        </button>
    </form>
<?php endif; ?>
<?php if (!empty($submission_ids)): ?>
    <form method="POST" action="stage_chat.php">
        <?php foreach ($submission_ids as $sid): ?>
            <input type="hidden" name="submission_ids[]" value="<?= $sid ?>">
        <?php endforeach; ?>
        <input type="hidden" name="student_id" value="<?= $student_id ?>">
        <input type="hidden" name="academic_year" value="<?= htmlspecialchars($academic_year) ?>">
        <input type="hidden" name="semester" value="<?= htmlspecialchars($semester) ?>">
        <input type="hidden" name="course" value="<?= htmlspecialchars($course) ?>">

        <button type="submit" 
                class="px-4 py-2 <?= $chat_disabled ? 'bg-purple-600 cursor-not-allowed' : 'bg-purple-600 hover:bg-purple-700' ?> text-white rounded-md"
                <?= $chat_disabled ? 'disabled' : '' ?>>
            Open Chat
        </button>
    </form>
<?php endif; ?>

                <a href="project_submission_list.php" 
                   class="inline-block px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 ml-2">
                    Back
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php while ($stage = $stages_result->fetch_assoc()): ?>
                <?php
                $unlock_date_raw = $stage['unlock_date'];
                $unlock_date = date('j M Y', strtotime($unlock_date_raw));
                $status = '';
                $status_class = '';

                if (strtotime($today) < strtotime($unlock_date_raw)) {
                    $status = 'Upcoming';
                    $status_class = 'text-yellow-500';
                } elseif ($stage['is_locked'] == 0) {
                    $status = 'Unlocked';
                    $status_class = 'text-green-600';
                } else {
                    $status = 'Locked';
                    $status_class = 'text-red-600';
                }

                // Fetch submission for this stage and student
                $check_submission_sql = "SELECT * FROM project_stage_submissions 
                                         WHERE student_user_id = ? AND stage_id = ?";
                $check_submission_stmt = $conn->prepare($check_submission_sql);
                $check_submission_stmt->bind_param("ii", $student_id, $stage['id']);
                $check_submission_stmt->execute();
                $submission_result = $check_submission_stmt->get_result();
                $submission = $submission_result->fetch_assoc();
                $check_submission_stmt->close();

                $submission_id = $submission ? $submission['id'] : 0;
                $chat_locked = false;

                if ($submission_id) {
                    $lock_check_sql = "SELECT id FROM stage_chat_locks WHERE submission_id = ?";
                    $lock_check_stmt = $conn->prepare($lock_check_sql);
                    $lock_check_stmt->bind_param("i", $submission_id);
                    $lock_check_stmt->execute();
                    $lock_result = $lock_check_stmt->get_result();
                    $chat_locked = $lock_result->num_rows > 0;
                    $lock_check_stmt->close();
                }
                ?>
                <div class="w-full max-w-xl p-6 bg-white rounded-lg shadow-md dark:bg-gray-800 aspect-square flex flex-col justify-between">
                    <div>
                        <div class="text-lg font-bold text-gray-700 dark:text-gray-200 mb-1">Stage: <?= $stage['stage_number'] ?></div>
                        <div class="text-md font-semibold text-gray-600 dark:text-gray-300 mb-1"><?= htmlspecialchars($stage['title']) ?></div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Unlock Date: <?= $unlock_date ?></div>
                        <div class="font-semibold <?= $status_class ?> mb-1"><?= $status ?></div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">Number of Files: <?= $stage['no_of_files'] ?> (Min: 1, Max: <?= $stage['no_of_files'] ?>)</div>
                    </div>

                    <?php
$today_date = strtotime($today);
$unlock_date_ts = strtotime($stage['unlock_date']);
$schedule_start = strtotime($schedule['start_date']);
$schedule_end = strtotime($schedule['end_date']);

// Check if stage is unlocked and current date is within schedule
$can_edit_or_give = ($stage['is_locked'] == 0 && $today_date >= $unlock_date_ts && $today_date >= $schedule_start && $today_date <= $schedule_end);

// Check if marks are already given
$marks_given = $submission && $submission['guide_marks'] !== null && $submission['guide_marks'] !== '';

?>

<div class="mt-4 flex space-x-2">
    <a href="get_stage_details.php?stage_id=<?= $stage['id'] ?>&student_id=<?= $student_id ?>&academic_year=<?= $academic_year ?>&semester=<?= $semester ?>&course=<?= $course ?>" 
       class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
        View
    </a>
    <?php
$submission_status = $submission['status'] ?? 0;

if ($submission && $submission_status == 0): ?>
   <a href="approve_stage_details.php?submission_id=<?= $submission['id'] ?>&stage_id=<?= $stage['id'] ?>&student_id=<?= $student_id ?>&academic_year=<?= $academic_year ?>&semester=<?= $semester ?>&course=<?= $course ?>" 
       class="px-4 py-2 bg-green-100 text-black rounded-md hover:bg-green-700">
        Approve
    </a>
    <a href="reject_stage_details.php?submission_id=<?= $submission['id'] ?>&stage_id=<?= $stage['id'] ?>&student_id=<?= $student_id ?>&academic_year=<?= $academic_year ?>&semester=<?= $semester ?>&course=<?= $course ?>" 
       class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
        Reject
    </a>
<?php elseif ($submission_status == 1): ?>
    <span class="px-4 py-2 bg-green-100 text-green-700 rounded-md">Submission Approved</span>
<?php elseif ($submission_status == 2): ?>
    <span class="px-4 py-2 bg-red-100 text-red-700 rounded-md">Submission Rejected</span>
<?php endif; ?>


<?php
$submission_status = $submission['status'] ?? 0;

// Completely HIDE Give Marks button after schedule end date
if ($today_date < $schedule_end && $can_edit_or_give && !$marks_given && $submission_status == 1): ?>
    <a href="give_marks.php?stage_id=<?= $stage['id'] ?>&student_id=<?= $student_id ?>&academic_year=<?= $academic_year ?>&semester=<?= $semester ?>&course=<?= $course ?>" 
       class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 ml-2">
        Give Marks
    </a>
<?php endif; ?>

<?php
// Completely HIDE Edit Marks button after schedule end date
if ($today_date < $schedule_end && $can_edit_or_give && $marks_given && $submission_status == 1): ?>
    <a href="edit_marks.php?stage_id=<?= $stage['id'] ?>&student_id=<?= $student_id ?>&academic_year=<?= $academic_year ?>&semester=<?= $semester ?>&course=<?= $course ?>" 
       class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 ml-2">
        Edit Marks
    </a>
<?php endif; ?>


</div>

                </div>
            <?php endwhile; ?>
        </div>
    </div>
</main>

<script>
function openChatModal(submissionId) {
    window.location.href = "chat_interface.php?submission_id=" + submissionId;
}
</script>
