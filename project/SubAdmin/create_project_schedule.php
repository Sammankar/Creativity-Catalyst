<?php
ob_start();
include "header.php";
include "connection.php";

$calendar_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sub_admin_id= $_SESSION['user_id'];
// Fetch academic calendar details
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
$college_row = $college_result->fetch_assoc();
$college_id = $college_row['college_id']; 

if (!$calendar_id) {
    $_SESSION['message'] = "Invalid Calendar ID.";
    $_SESSION['message_type'] = "error";
    header("Location: projectstages.php");
    exit;
}

$stmt = $conn->prepare("SELECT ac.*, c.name AS course_name 
                        FROM academic_calendar ac 
                        JOIN courses c ON ac.course_id = c.course_id 
                        WHERE ac.id = ?");
$stmt->bind_param("i", $calendar_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result->num_rows) {
    $_SESSION['message'] = "Academic calendar not found.";
    $_SESSION['message_type'] = "error";
    header("Location: projectstages.php");
    exit;
}

$calendar = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_start = $_POST['submission_start'];
    $submission_end = $_POST['submission_end'];

    // Check date bounds
    if ($submission_start < $calendar['start_date'] || $submission_end > $calendar['end_date']) {
        $_SESSION['message'] = "Dates must be between semester start and end dates.";
        $_SESSION['message_type'] = "error";
        header("Location: schedule_project_submission.php?id=" . $calendar_id);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO project_submission_schedule 
    (academic_calendar_id, course_id, semester, academic_year, start_date, end_date, created_by, created_at, college_id) 
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
$stmt->bind_param("iiisssii", 
    $calendar['id'], 
    $calendar['course_id'], 
    $calendar['semester'], 
    $calendar['academic_year'], 
    $submission_start, 
    $submission_end, 
    $sub_admin_id,
    $college_id 
    );

    if ($stmt->execute()) {
        $_SESSION['message'] = "Project schedule created successfully.";
        $_SESSION['message_type'] = "success";
        header("Location: projectstages.php");
        exit;
    } else {
        $_SESSION['message'] = "Error saving schedule.";
        $_SESSION['message_type'] = "error";
        header("Location: schedule_project_submission.php?id=" . $calendar_id);
        exit;
    }
}
?>


<main class="h-full overflow-y-auto mt-8">
    <div class="container px-6 mx-auto grid">
        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-200">
            <h1 class="text-xl font-semibold mb-4">Create Project Submission Schedule</h1>

            <!-- Academic Calendar Info -->
            <div class="mb-6">
                <p><strong>Course:</strong> <?= $calendar['course_name']; ?></p>
                <p><strong>Semester:</strong> <?= $calendar['semester']; ?></p>
                <p><strong>Academic Year:</strong> <?= $calendar['academic_year']; ?></p>
                <p><strong>Semester Start:</strong> <?= $calendar['start_date']; ?> | <strong>End:</strong> <?= $calendar['end_date']; ?></p>
            </div>

            <!-- Form -->
            <form method="POST">
                <div class="mb-4">
                    <label for="submission_start" class="block text-sm font-medium text-gray-700">Project Submission Start Date</label>
                    <input type="date" name="submission_start" id="submission_start" required 
                           class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <span id="start_validity" class="text-sm mt-1 block"></span>
                </div>

                <div class="mb-4">
                    <label for="submission_end" class="block text-sm font-medium text-gray-700">Project Submission End Date</label>
                    <input type="date" name="submission_end" id="submission_end" required 
                           class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <span id="end_validity" class="text-sm mt-1 block"></span>
                </div>

                <div class="flex justify-end">
                    <a href="schedule_project_submission.php"
                       class="px-4 py-2 mr-2 bg-gray-500 text-black font-semibold rounded-lg shadow-md hover:bg-gray-600">
                       Cancel
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-lg shadow-md hover:bg-blue-600">
                        Save Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<!-- Live validation script -->
<script>
    const semStart = new Date("<?= $calendar['start_date'] ?>");
    const semEnd = new Date("<?= $calendar['end_date'] ?>");

    const startInput = document.getElementById('submission_start');
    const endInput = document.getElementById('submission_end');
    const startValidity = document.getElementById('start_validity');
    const endValidity = document.getElementById('end_validity');

    function validateDate(inputEl, msgEl, type) {
        const selectedDate = new Date(inputEl.value);
        if (!inputEl.value) {
            msgEl.textContent = '';
            return;
        }

        if (selectedDate >= semStart && selectedDate <= semEnd) {
            msgEl.textContent = `${type} date is within the semester.`;
            msgEl.className = "text-green-600 text-sm mt-1";
        } else {
            msgEl.textContent = `${type} date is out of semester range!`;
            msgEl.className = "text-red-600 text-sm mt-1";
        }
    }

    startInput.addEventListener('change', () => validateDate(startInput, startValidity, 'Start'));
    endInput.addEventListener('change', () => validateDate(endInput, endValidity, 'End'));
</script>
