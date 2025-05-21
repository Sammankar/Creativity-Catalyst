<?php
ob_start();
include "header.php";
include "connection.php";

$schedule_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sub_admin_id = $_SESSION['user_id'];

if (!$schedule_id) {
    $_SESSION['message'] = "Invalid schedule ID.";
    $_SESSION['message_type'] = "error";
    header("Location: projectstages.php");
    exit;
}

// Get sub-admin's college
$college_stmt = $conn->prepare("SELECT college_id FROM users WHERE user_id = ?");
$college_stmt->bind_param("i", $sub_admin_id);
$college_stmt->execute();
$college_result = $college_stmt->get_result();
$college_id = $college_result->fetch_assoc()['college_id'] ?? 0;

// Get schedule with academic calendar info
$stmt = $conn->prepare("SELECT ps.*, ac.course_id, ac.semester, ac.academic_year, ac.start_date AS cal_start, ac.end_date AS cal_end, c.name AS course_name
    FROM project_submission_schedule ps
    JOIN academic_calendar ac ON ps.academic_calendar_id = ac.id
    JOIN courses c ON ac.course_id = c.course_id
    WHERE ps.id = ? AND ps.college_id = ?");
$stmt->bind_param("ii", $schedule_id, $college_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result->num_rows) {
    $_SESSION['message'] = "Schedule not found or unauthorized.";
    $_SESSION['message_type'] = "error";
    header("Location: projectstages.php");
    exit;
}

$schedule = $result->fetch_assoc();

// Live date check
$today = date("Y-m-d");
if ($today < $schedule['start_date'] || $today > $schedule['end_date']) {
    $_SESSION['message'] = "You can only schedule stages within project submission period.";
    $_SESSION['message_type'] = "error";
    header("Location: main_project_stages.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $total_marks = intval($_POST['total_marks']);
    $stages = $_POST['stage'];
    $valid = true;
    $marks_sum = 0;
    $unlock_dates = [];
    $last_date = null;

    foreach ($stages as $i => $stage) {
        $title = trim($stage['title']);
        $description = trim($stage['description']);
        $marks = intval($stage['marks']);
        $unlock = $stage['unlock'];
        $no_of_files = intval($stage['no_of_files']);

        $marks_sum += $marks;

        if (in_array($unlock, $unlock_dates)) {
            $valid = false;
            $errors[] = "Unlock dates must be unique.";
        }
        if ($unlock < $schedule['start_date'] || $unlock > $schedule['end_date']) {
            $valid = false;
            $errors[] = "Unlock dates must be within the project schedule period.";
        }

        if ($last_date !== null && $unlock <= $last_date) {
            $valid = false;
            $errors[] = "Stage " . ($i) . " unlock date must be after previous stage.";
        }

        if ($no_of_files < 1 || $no_of_files > 2) {
            $valid = false;
            $errors[] = "Stage " . ($i) . " must have 1 to 2 allowed file uploads.";
        }

        $unlock_dates[] = $unlock;
        $last_date = $unlock;
    }

    if ($marks_sum !== $total_marks) {
        $valid = false;
        $errors[] = "Total of stage marks must equal Total Marks.";
    }

    if ($valid) {
        // Update total_marks in project_submission_schedule
        $update_marks_stmt = $conn->prepare("UPDATE project_submission_schedule SET total_marks = ? WHERE id = ?");
        $update_marks_stmt->bind_param("ii", $total_marks, $schedule_id);
        $update_marks_stmt->execute();

        // Insert each stage
        $insert = $conn->prepare("INSERT INTO project_submission_stages 
        (schedule_id, stage_number, title, description, marks, unlock_date, no_of_files, scheduled_by, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");

        foreach (array_values($stages) as $index => $stage) {
            $title = $stage['title'];
            $desc = $stage['description'];
            $marks = intval($stage['marks']);
            $unlock = $stage['unlock'];
            $no_of_files = intval($stage['no_of_files']);
            $stage_num = $index + 1;

            $insert->bind_param("iissisii", $schedule_id, $stage_num, $title, $desc, $marks, $unlock, $no_of_files, $sub_admin_id);
            $insert->execute();
        }

        $_SESSION['message'] = "Stages created successfully.";
        $_SESSION['message_type'] = "success";
        header("Location: main_project_stages.php");
        exit;
    }
}
?>


<main class="h-full overflow-y-auto mt-8">
    <div class="container px-6 mx-auto grid">
        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-200">
            <h1 class="text-xl font-semibold mb-4">Setup Project Stages</h1>

            <div class="mb-6 space-y-2">
                <p><strong>Course:</strong> <?= $schedule['course_name']; ?></p>
                <p><strong>Semester:</strong> <?= $schedule['semester']; ?></p>
                <p><strong>Academic Year:</strong> <?= $schedule['academic_year']; ?></p>
                <p><strong>Academic Schedule Duration:</strong> <?= $schedule['cal_start']; ?> to <?= $schedule['cal_end']; ?></p>
                <p><strong>Project Schedule Duration:</strong> <?= $schedule['start_date']; ?> to <?= $schedule['end_date']; ?></p>
            </div>

            <?php if (!empty($errors)) : ?>
                <div class="bg-red-100 text-red-700 p-3 mb-4 rounded">
                    <ul class="list-disc ml-6">
                        <?php foreach ($errors as $err) : ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" id="stageForm">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Total Marks</label>
                    <input type="number" name="total_marks" id="total_marks" min="1" max="1000" required
                        class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 shadow-sm"
                        value="<?= $_POST['total_marks'] ?? '' ?>">
                </div>

                <?php for ($i = 1; $i <= 4; $i++) :
                    $data = $_POST['stage'][$i] ?? ['title' => '', 'description' => '', 'marks' => '', 'unlock' => '', 'no_of_files' => ''];
                ?>
                    <fieldset class="border border-gray-300 rounded-md p-4 mb-4">
                        <legend class="font-semibold text-lg mb-2">Stage <?= $i ?></legend>
                        <input type="hidden" name="stage[<?= $i ?>][number]" value="<?= $i ?>">
                        <div class="mb-2">
                            <label class="block text-sm">Stage Title</label>
                            <input type="text" name="stage[<?= $i ?>][title]" required
                                class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2"
                                value="<?= htmlspecialchars($data['title']) ?>">
                        </div>
                        <div class="mb-2">
                            <label class="block text-sm">Description</label>
                            <textarea name="stage[<?= $i ?>][description]" rows="2" required
                                class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2"><?= htmlspecialchars($data['description']) ?></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="block text-sm">Marks</label>
                            <input type="number" name="stage[<?= $i ?>][marks]" min="0" required
                                class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 marks-input"
                                value="<?= htmlspecialchars($data['marks']) ?>">
                        </div>
                        <div class="mb-2">
                            <label class="block text-sm">Unlock Date</label>
                            <input type="date" name="stage[<?= $i ?>][unlock]" required
                                class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 unlock-input"
                                min="<?= $schedule['start_date'] ?>" max="<?= $schedule['end_date'] ?>"
                                value="<?= htmlspecialchars($data['unlock']) ?>">
                        </div>
                        <div class="mb-2">
                            <label class="block text-sm">Number of Files (1-2)</label>
                            <input type="number" name="stage[<?= $i ?>][no_of_files]" min="1" max="2" required
                                class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 file-input"
                                value="<?= htmlspecialchars($data['no_of_files']) ?>">
                        </div>
                    </fieldset>
                <?php endfor; ?>

                <p id="markWarning" class="text-red-600 text-sm mb-2 hidden">❌ Sum of stage marks must equal Total Marks.</p>
                <p id="dateWarning" class="text-red-600 text-sm mb-2 hidden">❌ Unlock dates must be unique and in proper order.</p>
                <p id="fileWarning" class="text-red-600 text-sm mb-2 hidden">❌ Each stage must allow between 1 and 2 files.</p>

                <div class="flex justify-end">
                    <a href="projectstages.php"
                        class="px-4 py-2 mr-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Cancel</a>
                    <button type="submit" id="saveBtn"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                        disabled>Save Stages</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    const form = document.getElementById('stageForm');
    const saveBtn = document.getElementById('saveBtn');
    const totalInput = document.getElementById('total_marks');
    const markWarning = document.getElementById('markWarning');
    const dateWarning = document.getElementById('dateWarning');
    const fileWarning = document.getElementById('fileWarning');

    function validateForm() {
        const total = parseInt(totalInput.value) || 0;
        let sum = 0;
        const marksInputs = document.querySelectorAll('.marks-input');
        const dateInputs = document.querySelectorAll('.unlock-input');
        const fileInputs = document.querySelectorAll('.file-input');

        marksInputs.forEach(input => {
            sum += parseInt(input.value) || 0;
        });

        const marksValid = sum === total;
        markWarning.classList.toggle('hidden', marksValid);

        let validDates = true;
        let prev = null;
        const seen = new Set();
        dateInputs.forEach(input => {
            const date = input.value;
            if (!date || seen.has(date)) {
                validDates = false;
            }
            if (prev && date <= prev) {
                validDates = false;
            }
            seen.add(date);
            prev = date;
        });
        dateWarning.classList.toggle('hidden', validDates);

        let validFiles = true;
        fileInputs.forEach(input => {
            const val = parseInt(input.value);
            if (isNaN(val) || val < 1 || val > 2) {
                validFiles = false;
            }
        });
        fileWarning.classList.toggle('hidden', validFiles);

        saveBtn.disabled = !(marksValid && validDates && validFiles);
    }

    form.addEventListener('input', validateForm);
    window.addEventListener('load', validateForm);
</script>
