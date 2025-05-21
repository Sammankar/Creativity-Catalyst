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

// Fetch stages
$stages_stmt = $conn->prepare("SELECT * FROM project_submission_stages WHERE schedule_id = ? ORDER BY stage_number ASC");
$stages_stmt->bind_param("i", $schedule_id);
$stages_stmt->execute();
$stages_result = $stages_stmt->get_result();
$stages_stmt->close();

$total_marks_obtained = 0;
$total_marks = 0;
$stages_data = [];

// First loop: collect data and calculate total marks
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
?>

<main class="h-full overflow-y-auto flex justify-center bg-gray-50 py-8">
    <div class="w-full max-w-2xl px-4"> <!-- Reduced max-width from 3xl to 2xl -->
        <!-- Student Information Card -->
        <div class="mb-6 p-6 bg-white rounded-lg shadow-md">
            <h3 class="text-xl font-semibold text-gray-700 mb-4">Student Information</h3>
            <p><strong>Name:</strong> <?= htmlspecialchars($student_result['full_name']) ?></p>
            <p><strong>Semester:</strong> <?= htmlspecialchars($semester) ?></p>
            <p><strong>Academic Year:</strong> <?= htmlspecialchars($academic_year) ?></p>
            <p><strong>Total Marks:</strong> <?= $schedule['total_marks'] ?></p>
            <p><strong>Marks Obtained:</strong> <?= $total_marks_obtained ?> / <?= $total_marks ?></p>
            <a href="project_submission_schedule.php" 
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
                            <a href="<?= 'images/stages/' . htmlspecialchars($file_path) ?>" 
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
