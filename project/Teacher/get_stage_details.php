<?php
include 'header.php';
include 'connection.php';

$stage_id = isset($_GET['stage_id']) ? intval($_GET['stage_id']) : 0;
$student_user_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$semester = isset($_GET['semester']) ? $_GET['semester'] : '';
$course = isset($_GET['course']) ? $_GET['course'] : '';
$academic_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : '';

if ($stage_id === 0 || $student_user_id === 0) {
    echo "<div class='p-4 text-red-600'>Invalid access. Missing stage or student ID.</div>";
    exit;
}

// Fetch stage details
$sql = "SELECT * FROM project_submission_stages WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $stage_id);
$stmt->execute();
$result = $stmt->get_result();
$stage = $result->fetch_assoc();
$stmt->close();

if (!$stage) {
    echo "<div class='p-4 text-red-600'>Stage not found.</div>";
    exit;
}

$unlock_date = date('j M Y', strtotime($stage['unlock_date']));

// Fetch student submission for this stage
$submission_sql = "SELECT * FROM project_stage_submissions WHERE stage_id = ? AND student_user_id = ?";
$sub_stmt = $conn->prepare($submission_sql);
$sub_stmt->bind_param("ii", $stage_id, $student_user_id);
$sub_stmt->execute();
$submission_result = $sub_stmt->get_result();
$submission = $submission_result->fetch_assoc();
$sub_stmt->close();

// Determine upload status
$upload_status_text = "Not Submitted";
$submitted_files = [];

if ($submission) {
    switch ($submission['status']) {
        case 0: $upload_status_text = "Submitted"; break;
        case 1: $upload_status_text = "Approved"; break;
        case 2: $upload_status_text = "Rejected"; break;
    }

    if (in_array($submission['status'], [0, 1]) && !empty($submission['submitted_files'])) {
        $submitted_files = explode(',', $submission['submitted_files']);
    }
}
?>

<main class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900">
    <div class="w-full max-w-2xl mx-auto p-4">
        <div class="max-w-3xl p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
            <h2 class="text-xl font-semibold text-center text-gray-700 dark:text-gray-200 mb-6">Stage Details</h2>

            <div class="mb-4">
                <label class="block font-semibold text-gray-700 dark:text-gray-200">Stage Number:</label>
                <div class="text-gray-800 dark:text-gray-100"><?= $stage['stage_number'] ?></div>
            </div>

            <div class="mb-4">
                <label class="block font-semibold text-gray-700 dark:text-gray-200">Title:</label>
                <div class="text-gray-800 dark:text-gray-100"><?= htmlspecialchars($stage['title']) ?></div>
            </div>

            <div class="mb-4">
                <label class="block font-semibold text-gray-700 dark:text-gray-200">Description:</label>
                <div class="text-gray-800 dark:text-gray-100"><?= nl2br(htmlspecialchars($stage['description'])) ?></div>
            </div>

            <div class="mb-4">
                <label class="block font-semibold text-gray-700 dark:text-gray-200">Unlock Date:</label>
                <div class="text-gray-800 dark:text-gray-100"><?= $unlock_date ?></div>
            </div>

            <div class="mb-4">
                <label class="block font-semibold text-gray-700 dark:text-gray-200">Number of Files Required:</label>
                <div class="text-gray-800 dark:text-gray-100"><?= $stage['no_of_files'] ?></div>
            </div>

            <div class="mb-4">
                <label class="block font-semibold text-gray-700 dark:text-gray-200">Upload Status:</label>
                <div class="text-gray-800 dark:text-gray-100"><?= $upload_status_text ?></div>
            </div>

            <?php if (!empty($submitted_files)): ?>
                <div class="mb-4">
                    <label class="block font-semibold text-gray-700 dark:text-gray-200 mb-2">Submitted Files:</label>
                    <div class="space-y-3">
                        <?php foreach ($submitted_files as $index => $file): ?>
                            <div class="flex justify-between items-center bg-gray-100 dark:bg-gray-700 px-4 py-2 rounded-md">
                                <span class="text-gray-800 dark:text-gray-100">File <?= $index + 1 ?></span>
                                <div class="flex gap-2">
                                    <a href="../Student/images/stages/<?= htmlspecialchars($file) ?>" target="_blank"
                                       class="px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                                        View
                                    </a>
                                    <a href="../Student/images/stages/<?= htmlspecialchars($file) ?>" download
                                       class="px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                                        Download
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <a href="view_submissions.php?student_id=<?= $student_user_id ?>&academic_year=<?= urlencode($academic_year) ?>&semester=<?= urlencode($semester) ?>&course=<?= urlencode($course) ?>" 
   class="inline-block w-full text-center mt-6 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
    Back
</a>


        </div>
    </div>
</main>
