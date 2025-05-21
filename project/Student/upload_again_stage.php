<?php
ob_start();
include 'header.php';
include 'connection.php';

if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

$student_user_id = $_SESSION['user_id'];
$userQuery = "SELECT college_id, course_id FROM users WHERE user_id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("i", $student_user_id);
$stmt->execute();
$userResult = $stmt->get_result();
if ($userData = $userResult->fetch_assoc()) {
    $college_id = $userData['college_id'];
    $course_id = $userData['course_id'];
}
$stmt->close();

$stage_id = isset($_GET['stage_id']) ? intval($_GET['stage_id']) : 0;
if (!$stage_id) {
    die("Stage ID is missing.");
}

$stage_sql = "SELECT * FROM project_submission_stages WHERE id = ?";
$stmt = $conn->prepare($stage_sql);
$stmt->bind_param("i", $stage_id);
$stmt->execute();
$stage_result = $stmt->get_result();
$stage = $stage_result->fetch_assoc();
$stmt->close();

if (!$stage) {
    die("Stage not found.");
}

// Fetch existing rejected submission
$submission_sql = "SELECT * FROM project_stage_submissions 
                   WHERE student_user_id = ? AND college_id = ? AND stage_id = ? AND status = 2 AND submitted_files IS NULL 
                   ORDER BY id DESC LIMIT 1";

$stmt = $conn->prepare($submission_sql);
$stmt->bind_param("iii", $student_user_id, $college_id, $stage_id);
$stmt->execute();
$submission_result = $stmt->get_result();
$existing_submission = $submission_result->fetch_assoc();
$stmt->close();

if (!$existing_submission) {
    die("No rejected submission found to re-upload.");
}

$error_msgs = [];
$success_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploaded_files = [];

    for ($i = 0; $i < $stage['no_of_files']; $i++) {
        if (isset($_FILES['files']['name'][$i]) && $_FILES['files']['error'][$i] === 0) {
            $file_name = $_FILES['files']['name'][$i];
            $file_tmp = $_FILES['files']['tmp_name'][$i];
            $file_size = $_FILES['files']['size'][$i];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if ($file_ext !== 'pdf') {
                $error_msgs[] = "File $file_name is not a PDF.";
            } elseif ($file_size > 30 * 1024 * 1024) {
                $error_msgs[] = "File $file_name exceeds the 30MB size limit.";
            } else {
                $new_file_name = uniqid() . '.' . $file_ext;
                $upload_dir = 'images/stages/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                    $uploaded_files[] = $new_file_name;
                } else {
                    $error_msgs[] = "Error uploading $file_name.";
                }
            }
        }
    }

    if (empty($uploaded_files)) {
        $error_msgs[] = "No files were uploaded successfully.";
    }

    if (empty($error_msgs)) {
        $submitted_files = implode(',', $uploaded_files);
        $now = date('Y-m-d H:i:s');

        $update_sql = "UPDATE project_stage_submissions 
                       SET submitted_files = ?, submission_date = ?, updated_at = ?, status = 0 
                       WHERE stage_id = ?";

        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssi", $submitted_files, $now, $now, $existing_submission['id']);

        if ($stmt->execute()) {
            $success_msg = "Files re-uploaded successfully.";
            header("Location: project_submission_stages.php?schedule_id=" . $stage['schedule_id'] . "&success=" . urlencode($success_msg));
            exit();
        } else {
            $error_msgs[] = "Database update error: " . $stmt->error;
        }

        $stmt->close();
    }
}
?>

<!-- Same form and UI from original code -->
<main class="h-full overflow-y-auto mt-8">
    <div class="container px-6 mx-auto">
        <h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">Upload Again for Stage: <?= htmlspecialchars($stage['stage_number']) ?></h2>

        <?php if (!empty($success_msg)): ?>
            <div class="p-3 mb-4 text-sm text-green-700 bg-green-100 rounded">
                <?= htmlspecialchars($success_msg) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msgs)): ?>
            <div class="p-3 mb-4 text-sm text-red-700 bg-red-100 rounded">
                <?php foreach ($error_msgs as $msg): ?>
                    <div><?= htmlspecialchars($msg) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="text-sm text-gray-500 mb-4">Number of Files: <?= $stage['no_of_files'] ?> (Min: 1, Max: <?= $stage['no_of_files'] ?>)</div>

            <?php for ($i = 0; $i < $stage['no_of_files']; $i++): ?>
                <div class="mb-4">
                    <label for="file_<?= $i ?>" class="block text-sm font-medium text-gray-700">
                        File <?= $i + 1 ?> (PDF only, Max 30MB)
                    </label>
                    <input type="file" name="files[]" id="file_<?= $i ?>" accept="application/pdf" required
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm 
                                  focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
            <?php endfor; ?>

            <div class="flex justify-end mt-6">
                <a href="project_submission_stages.php?schedule_id=<?= $stage['schedule_id'] ?>"
                   class="px-4 py-2 mr-2 bg-gray-500 text-black font-semibold rounded-lg shadow-md 
                          hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
                    Back
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-lg shadow-md 
                               hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">
                    Upload Again
                </button>
            </div>
        </form>
    </div>
</main>
