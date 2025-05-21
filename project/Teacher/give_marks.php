<?php
ob_start();
include 'header.php';
include 'connection.php';// Needed if using session for guide ID
$guide_id = $_SESSION['user_id'] ?? 0;

$stage_id = isset($_GET['stage_id']) ? intval($_GET['stage_id']) : 0;
$student_user_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$semester = $_GET['semester'] ?? '';
$course = $_GET['course'] ?? '';
$academic_year = $_GET['academic_year'] ?? '';

$errors = [];
$success = "";

if ($stage_id === 0 || $student_user_id === 0) {
    echo "<div class='p-4 text-red-600'>Invalid access. Missing stage or student ID.</div>";
    exit;
}

// Fetch stage and schedule
$stage_sql = "SELECT ps.*, sch.start_date, sch.end_date 
              FROM project_submission_stages ps 
              INNER JOIN project_submission_schedule sch ON ps.schedule_id = sch.id 
              WHERE ps.id = ?";
$stmt = $conn->prepare($stage_sql);
$stmt->bind_param("i", $stage_id);
$stmt->execute();
$stage_result = $stmt->get_result();
$stage = $stage_result->fetch_assoc();
$stmt->close();

if (!$stage) {
    echo "<div class='p-4 text-red-600'>Stage not found.</div>";
    exit;
}

$unlock_date = strtotime($stage['unlock_date']);
$start_date = strtotime($stage['start_date']);
$end_date = strtotime($stage['end_date']);
$today = strtotime(date('Y-m-d'));

// Fetch submission
$submission_sql = "SELECT * FROM project_stage_submissions WHERE stage_id = ? AND student_user_id = ?";
$sub_stmt = $conn->prepare($submission_sql);
$sub_stmt->bind_param("ii", $stage_id, $student_user_id);
$sub_stmt->execute();
$submission_result = $sub_stmt->get_result();
$submission = $submission_result->fetch_assoc();
$sub_stmt->close();

$submitted_files = [];
$upload_status_text = "Not Submitted";
if ($submission) {
    switch ($submission['status']) {
        case 0: $upload_status_text = "Submitted"; break;
        case 1: $upload_status_text = "Approved"; break;
        case 2: $upload_status_text = "Rejected"; break;
    }
    if (!empty($submission['submitted_files'])) {
        $submitted_files = explode(',', $submission['submitted_files']);
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $marks = floatval($_POST['guide_marks']);
    $feedback = trim($_POST['guide_feedback']);

    // Validate
    if ($marks < 0 || $marks > $stage['marks']) {
        $errors[] = "Marks must be between 0 and {$stage['marks']}.";
    }

    if (empty($errors)) {
        $update_sql = "UPDATE project_stage_submissions 
                       SET guide_marks = ?, guide_feedback = ?, reviewed_by = ?, reviewed_at = NOW() 
                       WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("dsii", $marks, $feedback, $guide_id, $submission['id']);
        if ($stmt->execute()) {
            $success = "Marks successfully saved.";
            // Refresh submission data
            header("Location: give_marks.php?stage_id=$stage_id&student_id=$student_user_id&semester=$semester&academic_year=$academic_year&course=$course&success=1");
            exit;
        } else {
            $errors[] = "Database error: Unable to save marks.";
        }
    }
}
?>

<main class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900">
    <div class="w-full max-w-2xl mx-auto p-4">
        <div class="max-w-3xl p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
            <h2 class="text-xl font-semibold text-center text-gray-700 dark:text-gray-200 mb-6">Stage Details</h2>

            <div class="mb-4"><strong class="text-gray-700 dark:text-gray-200">Stage Number:</strong> <?= $stage['stage_number'] ?><strong class="text-gray-700 dark:text-gray-200">  Title:</strong> <?= htmlspecialchars($stage['title']) ?> </div>
            <div class="mb-4"><strong class="text-gray-700 dark:text-gray-200">Unlock Date:</strong> <?= date('j M Y', $unlock_date) ?> <strong class="text-gray-700 dark:text-gray-200">   Upload Status:</strong> <?= $upload_status_text ?></div>


            <?php if (!empty($submitted_files)): ?>
                <div class="mb-4"><strong class="text-gray-700 dark:text-gray-200 mb-2">Submitted Files:</strong>
                    <div class="space-y-3 mt-2">
                        <?php foreach ($submitted_files as $index => $file): ?>
                            <div class="flex justify-between items-center bg-gray-100 dark:bg-gray-700 px-4 py-2 rounded-md">
                                <span class="text-gray-800 dark:text-gray-100">File <?= $index + 1 ?></span>
                                <div class="flex gap-2">
                                    <a href="../Student/images/stages/<?= htmlspecialchars($file) ?>" target="_blank" class="px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700">View</a>
                                    <a href="../Student/images/stages/<?= htmlspecialchars($file) ?>" download class="px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700">Download</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div class="p-3 bg-green-200 text-green-800 rounded mb-4">Marks saved successfully!</div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="p-3 bg-red-200 text-red-800 rounded mb-4">
                    <?= implode("<br>", $errors) ?>
                </div>
            <?php endif; ?>

            <?php if ($today >= $unlock_date && $today >= $start_date && $today <= $end_date): ?>
                <?php if (empty($submission['guide_marks'])): ?>
                    <h2 class="text-xl font-semibold text-center text-gray-700 dark:text-gray-200 mt-8 mb-4">Give Marks</h2>
                    <form method="POST">
                        <div class="mb-4">
                            <label class="block font-semibold text-gray-700 dark:text-gray-200">Max Marks for Stage:</label>
                            <div class="text-gray-800 dark:text-gray-100"><?= $stage['marks'] ?></div>
                        </div>

                        <div class="mb-4">
                            <label class="block font-semibold text-gray-700 dark:text-gray-200" for="guide_marks">Enter Marks:</label>
                            <input type="number" step="0.01" min="0" max="<?= $stage['marks'] ?>" name="guide_marks" id="guide_marks" required
                                   class="w-full px-4 py-2 mt-1 border rounded-md focus:ring focus:ring-blue-300 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div class="mb-4">
                            <label class="block font-semibold text-gray-700 dark:text-gray-200" for="guide_feedback">Feedback (Optional):</label>
                            <textarea name="guide_feedback" id="guide_feedback" rows="3"
                                      class="w-full px-4 py-2 mt-1 border rounded-md focus:ring focus:ring-blue-300 dark:bg-gray-700 dark:text-white"></textarea>
                        </div>

                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-green-700">Submit Marks</button>
                    </form>
                <?php else: ?>
                    <div class="mt-6 text-green-700 font-medium">Marks already given: <?= $submission['guide_marks'] ?></div>
                <?php endif; ?>
            <?php else: ?>
                <div class="p-4 mt-4 text-yellow-700 bg-yellow-100 rounded-md">Marks can only be given after unlock date and within the schedule period.</div>
            <?php endif; ?>

            <a href="view_submissions.php?student_id=<?= $student_user_id ?>&academic_year=<?= urlencode($academic_year) ?>&semester=<?= urlencode($semester) ?>&course=<?= urlencode($course) ?>" 
               class="inline-block w-full text-center mt-6 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Back</a>
        </div>
    </div>
</main>
