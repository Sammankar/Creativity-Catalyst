<?php
include 'header.php';
include 'connection.php';

$today = date('Y-m-d');


$schedule_id = isset($_GET['schedule_id']) ? intval($_GET['schedule_id']) : 0;
$student_user_id = $_SESSION['user_id'];
$updated_stage_ids = [];

$schedule_sql = "SELECT * FROM project_submission_schedule WHERE is_editable = 1 AND id = ?";
$schedule_stmt = $conn->prepare($schedule_sql);
$schedule_stmt->bind_param("i", $schedule_id);
$schedule_stmt->execute();
$schedule = $schedule_stmt->get_result()->fetch_assoc();
$schedule_stmt->close();
$academic_year = $schedule['academic_year'];
$semester = $schedule['semester'];
$course = $schedule['course_id'];

$is_schedule_started = (strtotime($today) >= strtotime($schedule['start_date']));
// Fetch the latest submission_id for the student and schedule
$submission_ids = [];
$submission_sql = "SELECT s.id FROM project_stage_submissions s
                   INNER JOIN project_submission_stages ps ON s.stage_id = ps.id
                   WHERE s.student_user_id = ? AND ps.schedule_id = ?";
$submission_stmt = $conn->prepare($submission_sql);
$submission_stmt->bind_param("ii", $student_user_id, $schedule_id);
$submission_stmt->execute();
$submission_result = $submission_stmt->get_result();
while ($row = $submission_result->fetch_assoc()) {
    $submission_ids[] = $row['id'];
}
$submission_stmt->close();
// Fetch all stages for the given schedule ID
$sql = "SELECT id, unlock_date, is_locked FROM project_submission_stages WHERE schedule_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$result = $stmt->get_result();

// Unlock stages if the current date is past the unlock date
while ($stage = $result->fetch_assoc()) {
    if (strtotime($today) >= strtotime($stage['unlock_date']) && $stage['is_locked'] == 1) {
        $update_sql = "UPDATE project_submission_stages SET is_locked = 0 WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $stage['id']);
        $update_stmt->execute();
        $update_stmt->close();
    }
}

// Fetch all stages again after unlocking
$stmt->prepare("SELECT * FROM project_submission_stages WHERE schedule_id = ? ORDER BY unlock_date ASC");
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$stages_result = $stmt->get_result();
?>

<main class="h-full overflow-y-auto">
    <div class="container px-6 mx-auto">
    <div class="flex flex-wrap items-center justify-between my-6">
    <!-- Left: Heading -->
    <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-200">
        Project Submission Stages
    </h2>
<?php
include "connection.php";
// Check if chat should be disabled based on academic_calendar end date
$chat_disabled = false;

$calendar_sql = "SELECT ac.end_date 
                 FROM academic_calendar ac
                 INNER JOIN project_submission_schedule pss ON ac.id = pss.academic_calendar_id
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
    <!-- Right: Chat + Back buttons -->
    <div class="flex space-x-2 mt-2 md:mt-0">
    <?php if (!empty($submission_ids)): ?>
    <!-- View Marks Button -->
    <form method="GET" action="view_marks.php">
        <input type="hidden" name="student_id" value="<?= $student_user_id ?>">
        <input type="hidden" name="academic_year" value="<?= htmlspecialchars($academic_year) ?>">
        <input type="hidden" name="semester" value="<?= htmlspecialchars($semester) ?>">
        <input type="hidden" name="course" value="<?= htmlspecialchars($course) ?>">

        <button type="submit" 
                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-600 mr-2">
            View Marks
        </button>
    </form>
<?php endif; ?>
<?php if ($is_schedule_started && !empty($submission_ids)): ?>
    <form method="POST" action="stage_chat.php">
        <?php foreach ($submission_ids as $sid): ?>
            <input type="hidden" name="submission_ids[]" value="<?= $sid ?>">
        <?php endforeach; ?>
        <button type="submit" 
                class="px-4 py-2 <?= $chat_disabled ? 'bg-gray-400 cursor-not-allowed' : 'bg-purple-600 hover:bg-purple-700' ?> text-white rounded-md"
                <?= $chat_disabled ? 'disabled' : '' ?>>
            Open Chat
        </button>
    </form>
<?php endif; ?>


        <a href="project_submission_schedule.php" 
           class="inline-block px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            Back
        </a>
    </div>
</div>

        <?php
        // Check if there is a success message in the URL parameters
        if (isset($_GET['success'])) {
            $success_msg = htmlspecialchars($_GET['success']);
            echo '<div class="p-3 mb-4 text-sm text-green-700 bg-green-100 rounded">' . $success_msg . '</div>';
        }
        ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php while ($stage = $stages_result->fetch_assoc()): ?>
                <?php
                    // Check the unlock date and determine the status
                    $unlock_date_raw = $stage['unlock_date'];
                    $unlock_date = date('j M Y', strtotime($unlock_date_raw));
                    $status = '';
                    $status_class = '';

                    if (strtotime($today) < strtotime($unlock_date_raw)) {
                        $status = 'Upcoming';
                        $status_class = 'text-yellow-500';
                    } elseif (strtotime($today) >= strtotime($unlock_date_raw) && $stage['is_locked'] == 0) {
                        $status = 'Unlocked';
                        $status_class = 'text-green-600';
                    } elseif ($stage['is_locked'] == 1) {
                        $status = 'Locked';
                        $status_class = 'text-red-600';
                    }

                    // Check if the user has already submitted for this stage
                    $check_submission_sql = "SELECT * FROM project_stage_submissions WHERE student_user_id = ? AND stage_id = ?";
                    $check_submission_stmt = $conn->prepare($check_submission_sql);
                    $check_submission_stmt->bind_param("ii", $student_user_id, $stage['id']);
                    $check_submission_stmt->execute();
                    $submission_result = $check_submission_stmt->get_result();
                    $submission = $submission_result->fetch_assoc();
                    $check_submission_stmt->close();
                ?>
                <div class="w-full max-w-xl p-6 bg-white rounded-lg shadow-md dark:bg-gray-800 aspect-square flex flex-col justify-between">
                    <div>
                        <div class="text-lg font-bold text-gray-700 dark:text-gray-200 mb-1">Stage: <?= $stage['stage_number'] ?></div>
                        <div class="text-md font-semibold text-gray-600 dark:text-gray-300 mb-1"><?= htmlspecialchars($stage['title']) ?></div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Unlock Date: <?= $unlock_date ?></div>
                        <div class="font-semibold <?= $status_class ?> mb-1"><?= $status ?></div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">Number of Files: <?= $stage['no_of_files'] ?> (Min: 1, Max: <?= $stage['no_of_files'] ?>)</div>
                    </div>

                    <div class="mt-4 flex space-x-2">
                    <a href="get_stage_details.php?stage_id=<?= $stage['id'] ?>" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 mr-2">View</a>
                    
        <!-- No submission exists, allow upload -->
        <?php
$can_upload = false;
$end_date_passed = strtotime($today) > strtotime($schedule['end_date']);

if (
    strtotime($today) >= strtotime($stage['unlock_date']) &&
    !$end_date_passed &&
    $stage['is_locked'] == 0 &&
    empty($submission) // no previous submission
) {
    $can_upload = true;
}
?>

<?php if (!empty($submission)): ?>
    <?php if ($submission['status'] == 1): ?>
        <button disabled 
            class="px-4 py-2 bg-green-100 text-black rounded-md cursor-not-allowed">Submission Accepted</button>
    <?php elseif ($submission['status'] == 2 && is_null($submission['submitted_files'])): ?>
        <a href="upload_again_stage.php?stage_id=<?= $stage['id'] ?>" 
           class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-yellow-600">Upload Again</a>
        <span class="px-4 py-2 text-sm text-red-600 font-medium">Submission Rejected</span>
    <?php else: ?>
        <button disabled 
            class="px-4 py-2 bg-green-100 text-black rounded-md cursor-not-allowed">Already Submitted</button>
    <?php endif; ?>
<?php elseif ($can_upload): ?>
    <a href="upload_stage.php?stage_id=<?= $stage['id'] ?>" 
       class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-green-700">Upload</a>
<?php elseif ($end_date_passed): ?>
    <button disabled 
        class="px-4 py-2 bg-green-100 text-black rounded-md cursor-not-allowed">Submission Closed</button>
<?php else: ?>
    <button disabled 
        class="px-4 py-2 bg-green-100 text-black rounded-md cursor-not-allowed">Upload Disabled</button>
<?php endif; ?>


    
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <div id="viewProjectModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6">
        <h2 class="text-lg font-semibold mb-4">Project Submission Details</h2>

        <!-- Hidden Project Schedule ID -->
        <input type="hidden" id="ProjectScheduleID">

        <!-- Academic Year -->
        <div class="mb-3">
            <label class="font-semibold block mb-1">Academic Year:</label>
            <input type="text" id="AcademicYear" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <!-- Start Date -->
        <div class="mb-3">
            <label class="font-semibold block mb-1">Start Date:</label>
            <input type="text" id="StartDate" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <!-- End Date -->
        <div class="mb-3">
            <label class="font-semibold block mb-1">End Date:</label>
            <input type="text" id="EndDate" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <!-- Stage Number -->
        <div class="mb-3">
            <label class="font-semibold block mb-1">Stage Number:</label>
            <input type="text" id="StageNumber" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <!-- Title -->
        <div class="mb-3">
            <label class="font-semibold block mb-1">Title:</label>
            <input type="text" id="StageTitle" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <!-- Description -->
        <div class="mb-3">
            <label class="font-semibold block mb-1">Description:</label>
            <textarea id="StageDescription" class="w-full px-3 py-2 border rounded bg-gray-100" rows="3" readonly></textarea>
        </div>

        <!-- Unlock Date -->
        <div class="mb-3">
            <label class="font-semibold block mb-1">Unlock Date:</label>
            <input type="text" id="UnlockDate" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <!-- File Upload Status -->
        <div class="mb-3">
            <label class="font-semibold block mb-1">Files Upload Status:</label>
            <input type="text" id="UploadStatus" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <button id="closeProjectModal" class="w-full bg-blue-500 text-white py-2 rounded font-semibold mt-4">
            OK
        </button>
    </div>
</div>
<script>
// Open modal and fetch stage details via AJAX
function viewStageDetails(stageId) {
    // Show modal
    document.getElementById('viewProjectModal').classList.remove('hidden');
    
    // Perform AJAX request to fetch stage details
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'get_stage_details.php?stage_id=' + stageId, true);
    xhr.onload = function() {
        if (xhr.status == 200) {
            var data = JSON.parse(xhr.responseText);

            // Fill modal with data
            document.getElementById('StageNumber').value = data.stage_number;
            document.getElementById('StageTitle').value = data.title;
            document.getElementById('UnlockDate').value = data.unlock_date;
            document.getElementById('StageDescription').value = data.description;
            document.getElementById('ProjectScheduleID').value = data.schedule_id;
        }
    };
    xhr.send();
}

// Close modal
function closeModal() {
    document.getElementById('viewProjectModal').classList.add('hidden');
}
</script>
<script>
function openChatModal(submissionId) {
    window.location.href = "chat_interface.php?submission_id=" + submissionId;
}
</script>

    </div>
</main>
