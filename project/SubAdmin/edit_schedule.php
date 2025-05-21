<?php
ob_start();
include "header.php";
include "connection.php";

$sub_admin_id = $_SESSION['user_id'];
$schedule_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$schedule_id) {
    $_SESSION['message'] = "Invalid schedule ID.";
    $_SESSION['message_type'] = "error";
    header("Location: projectstages.php");
    exit;
}

// Fetch sub-admin college ID
$college_stmt = $conn->prepare("SELECT college_id FROM users WHERE user_id = ?");
$college_stmt->bind_param("i", $sub_admin_id);
$college_stmt->execute();
$college_result = $college_stmt->get_result();

if (!$college_result->num_rows) {
    $_SESSION['message'] = "User not found.";
    $_SESSION['message_type'] = "error";
    header("Location: projectstages.php");
    exit;
}
$college_id = $college_result->fetch_assoc()['college_id'];

// Fetch schedule with calendar and course info
$stmt = $conn->prepare("SELECT ps.*, ac.start_date AS cal_start, ac.end_date AS cal_end, ac.course_id, ac.semester, ac.academic_year, c.name AS course_name 
                        FROM project_submission_schedule ps
                        JOIN academic_calendar ac ON ps.academic_calendar_id = ac.id
                        JOIN courses c ON ac.course_id = c.course_id
                        WHERE ps.id = ? AND ps.college_id = ?");
$stmt->bind_param("ii", $schedule_id, $college_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result->num_rows) {
    $_SESSION['message'] = "Project schedule not found.";
    $_SESSION['message_type'] = "error";
    header("Location: projectstages.php");
    exit;
}
$schedule = $result->fetch_assoc();

$today = date("Y-m-d");
if ($schedule['cal_end'] < $today) {
    $_SESSION['message'] = "Cannot edit. Calendar already ended.";
    $_SESSION['message_type'] = "error";
    header("Location: projectstages.php");
    exit;
}

// Fetch stage unlock dates
$stages = [];
$stage_stmt = $conn->prepare("SELECT stage_number, unlock_date FROM project_submission_stages WHERE schedule_id = ?");
$stage_stmt->bind_param("i", $schedule_id);
$stage_stmt->execute();
$stage_result = $stage_stmt->get_result();
while ($row = $stage_result->fetch_assoc()) {
    $stages[] = $row;
}

// Update logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_start = $_POST['submission_start'];
    $new_end = $_POST['submission_end'];

    if ($new_start < $schedule['cal_start'] || $new_end > $schedule['cal_end']) {
        $_SESSION['message'] = "Dates must be within the academic calendar period.";
        $_SESSION['message_type'] = "error";
        header("Location: edit_schedule.php?id=" . $schedule_id);
        exit;
    }

    foreach ($stages as $stage) {
        if ($stage['unlock_date'] < $new_start || $stage['unlock_date'] > $new_end) {
            $_SESSION['message'] = "Stage " . $stage['stage_number'] . " unlock date (" . $stage['unlock_date'] . ") is outside the new schedule range.";
            $_SESSION['message_type'] = "error";
            header("Location: edit_schedule.php?id=" . $schedule_id);
            exit;
        }
    }

    $prev_start = $schedule['start_date'];
    $prev_end = $schedule['end_date'];

    $update_stmt = $conn->prepare("UPDATE project_submission_schedule 
        SET start_date = ?, end_date = ?, updated_at = NOW() WHERE id = ?");
    $update_stmt->bind_param("ssi", $new_start, $new_end, $schedule_id);

    if ($update_stmt->execute()) {
        $log_stmt = $conn->prepare("INSERT INTO project_schedule_edit_logs 
            (schedule_id, previous_start_date, previous_end_date, new_start_date, new_end_date, edited_by, edited_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $log_stmt->bind_param("issssi", $schedule_id, $prev_start, $prev_end, $new_start, $new_end, $sub_admin_id);
        $log_stmt->execute();

        $course_id = $schedule['course_id'];
        $semester = $schedule['semester'];
        $academic_year = $schedule['academic_year'];

        $update_academics = $conn->prepare("UPDATE student_academics 
            SET academic_project_schedule_start_date = ?, academic_project_schedule_end_date = ?, updated_at = NOW()
            WHERE academic_project_schedule_id = ? AND course_id = ? AND current_semester = ? AND current_academic_year = ?");
        $update_academics->bind_param("ssiiss", $new_start, $new_end, $schedule_id, $course_id, $semester, $academic_year);
        $update_academics->execute();

        $update_result = $conn->prepare("UPDATE student_semester_result 
            SET academic_project_schedule_start_date = ?, academic_project_schedule_end_date = ?, updated_at = NOW()
            WHERE academic_project_schedule_id = ? AND course_id = ? AND semester = ? AND academic_year = ?");
        $update_result->bind_param("ssiiss", $new_start, $new_end, $schedule_id, $course_id, $semester, $academic_year);
        $update_result->execute();

        require 'sendProjectUpdateEmail.php';
        $schedule['start_date'] = $new_start;
        $schedule['end_date'] = $new_end;
        sendProjectUpdateEmail($schedule);

        $_SESSION['message'] = "Schedule updated successfully and changes sent to students.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating schedule.";
        $_SESSION['message_type'] = "error";
    }

    header("Location: projectstages.php");
    exit;
}
?>

<main class="h-full overflow-y-auto mt-8">
    <div class="container px-6 mx-auto grid">
        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-200">
            <h1 class="text-xl font-semibold mb-4">Edit Project Submission Schedule</h1>

            <div class="mb-6">
                <p><strong>Course:</strong> <?= $schedule['course_name']; ?></p>
                <p><strong>Semester:</strong> <?= $schedule['semester']; ?></p>
                <p><strong>Academic Year:</strong> <?= $schedule['academic_year']; ?></p>
                <p><strong>Academic Calendar:</strong> <?= $schedule['cal_start']; ?> to <?= $schedule['cal_end']; ?></p>
                <p><strong>Project Submission:</strong> <?= $schedule['start_date']; ?> to <?= $schedule['end_date']; ?></p>
            </div>

            <form method="POST">
                <div class="mb-4">
                    <label for="submission_start" class="block text-sm font-medium text-gray-700">Project Submission Start Date</label>
                    <input type="date" name="submission_start" id="submission_start" required
                        value="<?= $schedule['start_date']; ?>"
                        class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <span id="start_validity" class="text-sm mt-1 block"></span>
                </div>

                <div class="mb-4">
                    <label for="submission_end" class="block text-sm font-medium text-gray-700">Project Submission End Date</label>
                    <input type="date" name="submission_end" id="submission_end" required
                        value="<?= $schedule['end_date']; ?>"
                        class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <span id="end_validity" class="text-sm mt-1 block"></span>
                </div>

                <div class="flex justify-end">
                    <a href="projectstages.php"
                       class="px-4 py-2 mr-2 bg-gray-500 text-black font-semibold rounded-lg shadow-md hover:bg-gray-600">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-lg shadow-md hover:bg-blue-600">
                        Update Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<!-- Live validation -->
<script>
    const semStart = new Date("<?= $schedule['cal_start'] ?>");
    const semEnd = new Date("<?= $schedule['cal_end'] ?>");
    const stages = <?= json_encode($stages); ?>;

    const startInput = document.getElementById('submission_start');
    const endInput = document.getElementById('submission_end');
    const startValidity = document.getElementById('start_validity');
    const endValidity = document.getElementById('end_validity');

    function validateDates() {
        const startDate = new Date(startInput.value);
        const endDate = new Date(endInput.value);
        let error = false;

        startValidity.textContent = "";
        endValidity.textContent = "";

        if (startDate < semStart || startDate > semEnd) {
            startValidity.textContent = "Start date is outside academic calendar.";
            startValidity.className = "text-red-600 text-sm";
            error = true;
        }

        if (endDate < semStart || endDate > semEnd) {
            endValidity.textContent = "End date is outside academic calendar.";
            endValidity.className = "text-red-600 text-sm";
            error = true;
        }

        stages.forEach(stage => {
            const stageDate = new Date(stage.unlock_date);
            if (stageDate < startDate || stageDate > endDate) {
                error = true;
                endValidity.textContent = `Stage ${stage.stage_number} unlock date (${stage.unlock_date}) is outside the selected range.`;
                endValidity.className = "text-red-600 text-sm";
            }
        });

        return !error;
    }

    startInput.addEventListener('change', validateDates);
    endInput.addEventListener('change', validateDates);
</script>
