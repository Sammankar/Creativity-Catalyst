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

// Fetch stage details
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

// Fetch guide_user_id from guide_allocations
$guide_sql = "SELECT guide_user_id FROM guide_allocations WHERE student_user_id = ?";
$guide_stmt = $conn->prepare($guide_sql);
$guide_stmt->bind_param("i", $student_user_id);
$guide_stmt->execute();
$guide_result = $guide_stmt->get_result();
$guide_row = $guide_result->fetch_assoc();
$guide_stmt->close();

$guide_user_id = $guide_row['guide_user_id'] ?? null;
if (!$guide_user_id) {
    die("Guide not assigned.");
}

$error_msgs = [];
$success_msg = "";

// Handle file submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploaded_files = [];

    // Loop to upload files as per the number of files for this stage
    for ($i = 0; $i < $stage['no_of_files']; $i++) {
        if (isset($_FILES['files']['name'][$i]) && $_FILES['files']['error'][$i] === 0) {
            $file_name = $_FILES['files']['name'][$i];
            $file_tmp = $_FILES['files']['tmp_name'][$i];
            $file_size = $_FILES['files']['size'][$i];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // Check file extension and size
            if ($file_ext !== 'pdf') {
                $error_msgs[] = "File $file_name is not a PDF.";
            } elseif ($file_size > 30 * 1024 * 1024) {
                $error_msgs[] = "File $file_name exceeds the 30MB size limit.";
            } else {
                // Create a unique file name
                $new_file_name = uniqid() . '.' . $file_ext;
                $upload_dir = 'images/stages/';

                // Ensure directory exists
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                // Move the uploaded file to the directory
                if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                    $uploaded_files[] = $new_file_name;
                } else {
                    $error_msgs[] = "Error uploading $file_name.";
                }
            }
        }
    }

    // If no files uploaded successfully
    if (empty($uploaded_files)) {
        $error_msgs[] = "No files were uploaded successfully.";
    }

    // If no errors, insert the data into the database
    if (empty($error_msgs)) {
        // Prepare the submitted_files string
        $submitted_files = implode(',', $uploaded_files);  // Save files as comma-separated

        // Ensure the submitted files are not empty
        if (empty($submitted_files)) {
            $error_msgs[] = "Submitted files list is empty.";
        } else {
            $now = date('Y-m-d H:i:s');

            // Insert the submission details into the database
            $insert_sql = "INSERT INTO project_stage_submissions (
                college_id, student_user_id, guide_user_id, schedule_id, stage_id, stage_number,
                submitted_files, submission_date, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param(
                "iiiiisssss",
                $college_id,
                $student_user_id,
                $guide_user_id,
                $stage['schedule_id'],
                $stage_id,
                $stage['stage_number'],
                $submitted_files,
                $now,
                $now,
                $now
            );
            

            // Execute and check if the query runs successfully
            if ($insert_stmt->execute()) {
                $success_msg = "Files uploaded and submission saved successfully.";
                header("Location: project_submission_stages.php?schedule_id=" . $stage['schedule_id'] . "&success=" . urlencode($success_msg));
                exit(); // Ensure no further code execution after redirection
            } else {
                $error_msgs[] = "Database error: " . $insert_stmt->error;
            }

            $insert_stmt->close();
        }
    }
}

?>

<!-- Your form and display logic goes here -->


<!-- Form for file upload -->
<main class="h-full overflow-y-auto mt-8">
    <div class="container px-6 mx-auto">
        <h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">Upload Files for Stage: <?= htmlspecialchars($stage['stage_number']) ?></h2>

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
                    Submit Files
                </button>
            </div>
        </form>
    </div>
</main>

