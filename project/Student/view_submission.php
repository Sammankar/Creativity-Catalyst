<?php
include "connection.php";
include "header.php";

// Check if user is logged in
$student_id = $_SESSION['user_id'] ?? null;
if (!$student_id) {
    echo "User not logged in.";
    exit;
}

// Get competition ID from URL
$competition_id = $_GET['competition_id'] ?? null;
if (!$competition_id) {
    echo "Competition ID is missing.";
    exit;
}

// Get submission
$sql = "SELECT submitted_files, submission_date, is_verified_by_project_head, status FROM student_submissions WHERE student_user_id = ? AND competition_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $student_id, $competition_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<div style='padding: 2rem; font-weight: bold;'>No submission found for this competition.</div>";
    exit;
}

$submission = $result->fetch_assoc();
$files = json_decode($submission['submitted_files'], true);
$submission_date = $submission['submission_date'];
$status_maps = [0 => 'Submitted'];
$statuss = $status_maps[$submission['status']] ?? 'Unknown';
$status_map = [0 => 'Pending', 1 => 'Approved', 2 => 'Rejected'];
$status = $status_map[$submission['is_verified_by_project_head']] ?? 'Unknown';

// Fetch competition details excluding description, rules, and recommended submissions
$competition_sql = "SELECT `competition_id`, `name`, `number_of_files`, `max_submissions_per_college`, 
                            `college_registration_start_date`, `college_registration_end_date`, 
                            `student_submission_start_date`, `student_submission_end_date`, 
                            `evaluation_start_date`, `evaluation_end_date`, 
                            `result_declaration_date`, `total_prize_pool`, 
                            `top_ranks_awarded`, `created_by`, `created_at`, `updated_at`, 
                            `competition_status`
                    FROM `competitions`
                    WHERE `competition_id` = ?";
$comp_stmt = $conn->prepare($competition_sql);
$comp_stmt->bind_param("i", $competition_id);
$comp_stmt->execute();
$comp_result = $comp_stmt->get_result();

if ($comp_result->num_rows === 0) {
    echo "<div style='padding: 2rem; font-weight: bold;'>Competition not found.</div>";
    exit;
}

$competition = $comp_result->fetch_assoc();

$file_labels_sql = "SELECT `file_label_id`, `competition_id`, `file_number`, `label` FROM `competition_file_labels` WHERE `competition_id` = ?";
$file_labels_stmt = $conn->prepare($file_labels_sql);
$file_labels_stmt->bind_param("i", $competition_id);
$file_labels_stmt->execute();
$file_labels_result = $file_labels_stmt->get_result();

// Fetch Prizes
$prizes_sql = "SELECT `prize_id`, `competition_id`, `rank`, `prize_description` FROM `competition_prizes` WHERE `competition_id` = ?";
$prizes_stmt = $conn->prepare($prizes_sql);
$prizes_stmt->bind_param("i", $competition_id);
$prizes_stmt->execute();
$prizes_result = $prizes_stmt->get_result();
$message = $_SESSION['message'] ?? "";
$message_type = $_SESSION['message_type'] ?? "";
unset($_SESSION['message'], $_SESSION['message_type']);
?>


<main class="h-full overflow-y-auto">
  <div class="container px-6 mx-auto grid">
    <!-- Back Button -->
    <a href="competitions.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm mb-4">Back to Competitions</a>
    <?php if (!empty($message)): ?>
        <div id="custom-popup" class="bg-white border <?php echo ($message_type === 'success') ? 'border-green-500 text-green-600' : 'border-red-500 text-red-600'; ?> px-6 py-3 rounded-lg shadow-lg flex items-center space-x-3 mx-auto">
            
            <!-- Icon -->
            <?php if ($message_type === 'success'): ?>
                <svg class="w-6 h-6 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            <?php else: ?>
                <svg class="w-6 h-6 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M12 6a9 9 0 110 18A9 9 0 0112 6z" />
                </svg>
            <?php endif; ?>
            
            <!-- Message -->
            <span><?php echo htmlspecialchars($message); ?></span>

            <!-- Close Button -->
            <button onclick="closePopup()" class="ml-2 text-gray-700 font-bold">×</button>
        </div>

        <script>
            function closePopup() {
                document.getElementById("custom-popup").style.display = "none";
            }
            setTimeout(closePopup, 5000); // Hide popup after 5 seconds
        </script>
    <?php endif; ?>


    <h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">
      Your Submitted Files for <?= htmlspecialchars($competition['name']) ?>
    </h2>

    <div class="bg-white shadow-md rounded-lg p-6 space-y-6">
        <p><strong>Submission Date:</strong> <?= $submission_date ?></p>
        <p><strong>Submission Status:</strong> <?= $statuss ?></p>
        <p><strong>Verification Status:</strong> <?= $status ?></p>

        <h3 class="text-lg font-semibold mt-4">Files:</h3>
        <div class="space-y-4">
            <?php foreach ($files as $index => $file_path): ?>
                <?php
                    $file_url = htmlspecialchars($file_path);
                    $file_name = basename($file_path);
                ?>
                <div class="flex items-center justify-between bg-gray-100 p-3 rounded-md">
                    <span class="font-medium text-gray-800">File <?= $index + 1 ?>: <?= $file_name ?></span>
                    <div class="flex gap-2">
                        <a href="<?= $file_url ?>" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1 rounded text-sm">View</a>
                        <a href="<?= $file_url ?>" download="<?= $file_name ?>" class="bg-green-100 hover:bg-green-700 text-black px-4 py-1 rounded text-sm">Download</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Competition Data (Excluding Rules, Description, and Recommended Submissions) -->
        <h3 class="text-lg font-semibold mt-4">Competition Details</h3>
        <div class="space-y-2">
            <p><strong>Number of Files:</strong> <?= $competition['number_of_files'] ?></p>
            <p><strong>Max Submissions per Course:</strong> <?= $competition['max_submissions_per_college'] ?></p>
            <p><strong>College Registration Dates:</strong> <?= $competition['college_registration_start_date'] ?> to <?= $competition['college_registration_end_date'] ?></p>
            <p><strong>Student Submission Dates:</strong> <?= $competition['student_submission_start_date'] ?> to <?= $competition['student_submission_end_date'] ?></p>
            <p><strong>Evaluation Dates:</strong> <?= $competition['evaluation_start_date'] ?> to <?= $competition['evaluation_end_date'] ?></p>
            <p><strong>Result Declaration Date:</strong> <?= $competition['result_declaration_date'] ?></p>
            <p><strong>Total Prize Pool:</strong> <?= $competition['total_prize_pool'] ?></p>
            <p><strong>Top Ranks Awarded:</strong> <?= $competition['top_ranks_awarded'] ?></p>
    <ul class="space-y-2">
        <?php while ($prize = $prizes_result->fetch_assoc()): ?>
            <li><strong>Rank <?= $prize['rank'] ?>:</strong> <?= htmlspecialchars($prize['prize_description']) ?></li>
        <?php endwhile; ?>
    </ul>
        </div>
        <div class="flex justify-end">
                    <a href="competitions.php" class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-lg shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">
                        Back
                    </a>
                </div>
    </div>
    
  </div>
</main>


</main>
