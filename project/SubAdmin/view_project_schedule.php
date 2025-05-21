<?php
include "header.php";
include "connection.php";

if (!isset($_GET['schedule_id'])) {
    echo "<p class='text-red-600 text-center mt-10'>Schedule ID is missing.</p>";
    exit;
}

$schedule_id = intval($_GET['schedule_id']);

// Get schedule info
$stmt = $conn->prepare("
    SELECT pss.*, ac.start_date AS cal_start, ac.end_date AS cal_end, c.name AS course_name 
    FROM project_submission_schedule pss
    LEFT JOIN academic_calendar ac ON ac.id = pss.academic_calendar_id
    LEFT JOIN courses c ON c.course_id = pss.course_id
    WHERE pss.id = ?
");
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$schedule = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$schedule) {
    echo "<p class='text-red-600 text-center mt-10'>Schedule not found.</p>";
    exit;
}

// Get project stages
$stage_stmt = $conn->prepare("
    SELECT * FROM project_submission_stages 
    WHERE schedule_id = ? ORDER BY stage_number ASC
");
$stage_stmt->bind_param("i", $schedule_id);
$stage_stmt->execute();
$stages_result = $stage_stmt->get_result();
$stages = [];
while ($stage = $stages_result->fetch_assoc()) {
    $stages[] = $stage;
}
$stage_stmt->close();
?>

<main class="h-full overflow-y-auto mt-8">
    <div class="container px-6 mx-auto grid">
        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-200">
            <h1 class="text-xl font-semibold mb-4">View Project Submission Schedule</h1>

            <div class="mb-6 space-y-1 text-sm text-gray-700">
                <p><strong>Course:</strong> <?= htmlspecialchars($schedule['course_name']); ?></p>
                <p><strong>Semester:</strong> <?= htmlspecialchars($schedule['semester']); ?></p>
                <p><strong>Academic Year:</strong> <?= htmlspecialchars($schedule['academic_year']); ?></p>
                <p><strong>Academic Calendar:</strong> <?= date("j M Y", strtotime($schedule['cal_start'])) ?> to <?= date("j M Y", strtotime($schedule['cal_end'])) ?></p>
                <p><strong>Project Submission:</strong> <?= date("j M Y", strtotime($schedule['start_date'])) ?> to <?= date("j M Y", strtotime($schedule['end_date'])) ?></p>
            </div>

            <div class="mt-6">
                <h2 class="text-lg font-semibold mb-4">Project Stages</h2>
                <?php if (count($stages) > 0): ?>
                    <div class="grid gap-4">
                        <?php foreach ($stages as $stage): ?>
                            <div class="p-4 border rounded-lg bg-gray-50">
                                <p class="font-medium text-gray-800 mb-1">
                                    Stage <?= $stage['stage_number'] ?>: <?= htmlspecialchars($stage['title']) ?>
                                </p>
                                <p class="text-sm text-gray-600 mb-1">
                                    <strong>Unlock Date:</strong> <?= date("j M Y", strtotime($stage['unlock_date'])) ?>
                                </p>
                                <p class="text-sm text-gray-600 mb-1">
                                    <strong>Status:</strong>
                                    <span class="<?= $stage['is_locked'] == 1 ? 'text-red-600' : 'text-green-600' ?> font-semibold">
                                        <?= $stage['is_locked'] == 1 ? 'Inactive' : 'Active' ?>
                                    </span>
                                </p>
                                <p class="text-sm text-gray-700 mt-2 line-clamp-2">
                                <strong>Description:</strong>
                                    <?= htmlspecialchars($stage['description']) ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-sm text-gray-500">No stages available.</p>
                <?php endif; ?>
            </div>

            <div class="flex justify-end mt-6">
                <a href="main_project_stages.php"
                   class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-lg shadow-md hover:bg-gray-600">
                    Back
                </a>
            </div>
        </div>
    </div>
</main>

<!-- Tailwind line-clamp plugin required -->
<style>
.line-clamp-2 {
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}
</style>
