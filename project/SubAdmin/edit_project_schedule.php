<?php
include "header.php";
include "connection.php";

if (!isset($_GET['schedule_id'])) {
    echo "<div class='text-center mt-10 text-red-600'>Invalid Request. Schedule ID is missing.</div>";
    exit;
}

$schedule_id = intval($_GET['schedule_id']);

$stmt = $conn->prepare("
    SELECT pss.*, 
           ac.start_date AS cal_start, 
           ac.end_date AS cal_end, 
           c.name AS course_name 
    FROM project_submission_schedule pss
    LEFT JOIN academic_calendar ac ON pss.academic_calendar_id = ac.id
    LEFT JOIN courses c ON pss.course_id = c.course_id
    WHERE pss.id = ?
");
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<div class='text-center mt-10 text-red-600'>No schedule found for this ID.</div>";
    exit;
}

$schedule = $result->fetch_assoc();
$stmt->close();

$cal_start = $schedule['cal_start'];
$cal_end = $schedule['cal_end'];
$proj_start = $schedule['start_date'];
$proj_end = $schedule['end_date'];

$stageStmt = $conn->prepare("SELECT * FROM project_submission_stages WHERE schedule_id = ? ORDER BY stage_number ASC");
$stageStmt->bind_param("i", $schedule_id);
$stageStmt->execute();
$stageResult = $stageStmt->get_result();

$stages = [];
while ($row = $stageResult->fetch_assoc()) {
    $stages[] = $row;
}
$stageStmt->close();
?>

<main class="h-full overflow-y-auto mt-8">
    <div class="container px-6 mx-auto grid">
        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-200">
            <h1 class="text-xl font-semibold mb-4">Edit Project Submission Schedule</h1>
            <?php if (isset($_GET['status']) && isset($_GET['message'])): ?>
    <div class="p-3 mb-4 rounded <?= $_GET['status'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
        <?= htmlspecialchars($_GET['message']) ?>
    </div>
<?php endif; ?>


            <div class="mb-6 space-y-2">
                <p><strong>Course:</strong> <?= htmlspecialchars($schedule['course_name']) ?></p>
                <p><strong>Semester:</strong> <?= htmlspecialchars($schedule['semester']) ?></p>
                <p><strong>Academic Year:</strong> <?= htmlspecialchars($schedule['academic_year']) ?></p>
                <p><strong>Academic Calendar:</strong> <?= htmlspecialchars($cal_start) ?> to <?= htmlspecialchars($cal_end) ?></p>
                <p><strong>Project Submission:</strong> <?= htmlspecialchars($proj_start) ?> to <?= htmlspecialchars($proj_end) ?></p>
            </div>

            <div class="space-y-6">
                <?php foreach ($stages as $index => $stage): 
                    $today = date('Y-m-d');
                    $can_edit_date = strtotime($today) < strtotime($stage['unlock_date'] . " 23:59:59");
                    $is_locked = $stage['is_locked'] == 1 ? "Inactive" : "Active";
                ?>
                    <div class="bg-gray-100 p-4 rounded-lg shadow-md relative" id="stage-card-<?= $stage['id'] ?>">
                        <form method="POST" action="update_stage.php" class="space-y-2 stage-form" data-index="<?= $index ?>">
                            <input type="hidden" name="stage_id" value="<?= $stage['id'] ?>">
                            <input type="hidden" name="schedule_id" value="<?= $schedule_id ?>">

                            <div class="flex justify-between items-center mb-2">
                                <h2 class="text-lg font-semibold">Stage <?= $stage['stage_number'] ?></h2>
                                <span class="text-sm font-medium px-3 py-1 rounded-full <?= $stage['is_locked'] ? 'bg-red-200 text-red-800' : 'bg-green-200 text-green-800' ?>">
                                    <?= $is_locked ?>
                                </span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Title</label>
                                    <input type="text" name="title" value="<?= htmlspecialchars($stage['title']) ?>"
                                        class="w-full px-3 py-2 border rounded-md bg-white title-field" readonly>
                                    
                                    <label class="block mt-4 text-sm font-medium text-gray-700">Description</label>
                                    <textarea name="description"
                                        class="w-full px-3 py-2 border rounded-md bg-white description-field"
                                        rows="3" maxlength="1000" readonly><?= htmlspecialchars($stage['description']) ?></textarea>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Unlock Date</label>
                                    <input type="date" name="unlock_date" value="<?= $stage['unlock_date'] ?>"
                                        class="w-full px-3 py-2 border rounded-md bg-white unlock-field"
                                        <?= $can_edit_date ? '' : 'readonly' ?>
                                        min="<?= $proj_start ?>" max="<?= $proj_end ?>">
                                    <p class="text-xs text-red-600 hidden unlock-error mt-1">Invalid unlock date</p>

                                    <label class="block mt-4 text-sm font-medium text-gray-700">Number of Files</label>
                                    <input type="number" name="no_of_files" value="<?= $stage['no_of_files'] ?>"
                                        min="1" max="2"
                                        class="w-full px-3 py-2 border rounded-md bg-white files-field"
                                        <?= $can_edit_date ? '' : 'readonly' ?>>
                                </div>
                            </div>

                            <div class="flex justify-end space-x-2 mt-4">
                                <button type="button" class="edit-btn px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Edit</button>
                                <button type="submit" class="save-btn px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 hidden">Save</button>
                                <button type="button" class="cancel-btn px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 hidden">Cancel</button>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="flex justify-end mt-6">
                <a href="main_project_stages.php" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-gray-700 shadow">
                    Back to List
                </a>
            </div>
        </div>
    </div>
</main>

<script>
    const projStart = new Date("<?= $proj_start ?>");
    const projEnd = new Date("<?= $proj_end ?>");

    document.querySelectorAll('.edit-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const form = btn.closest('form');
            form.querySelectorAll('.title-field, .description-field, .unlock-field, .files-field').forEach(field => {
                if (!field.hasAttribute('readonly')) return;
                field.removeAttribute('readonly');
            });
            btn.classList.add('hidden');
            form.querySelector('.save-btn').classList.remove('hidden');
            form.querySelector('.cancel-btn').classList.remove('hidden');
        });
    });

    document.querySelectorAll('.cancel-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            location.reload(); // Revert changes
        });
    });

    document.querySelectorAll('.unlock-field').forEach((field, index) => {
        field.addEventListener('input', () => validateUnlockDate(field, index));
    });

    function validateUnlockDate(input, index) {
        const currentDate = new Date(input.value);
        const form = input.closest('form');
        const errorMsg = form.querySelector('.unlock-error');
        const saveBtn = form.querySelector('.save-btn');

        let isValid = true;

        // Boundary check
        if (currentDate < projStart || currentDate > projEnd) {
            isValid = false;
            errorMsg.textContent = "Date must be within project submission schedule.";
        }

        // Order check
        if (index > 0) {
            const prevUnlockInput = document.querySelectorAll('.unlock-field')[index - 1];
            const prevDate = new Date(prevUnlockInput.value);
            if (currentDate <= prevDate) {
                isValid = false;
                errorMsg.textContent = "Stage unlock date must be after previous stage.";
            }
        }

        if (!isValid) {
            errorMsg.classList.remove('hidden');
            saveBtn.disabled = true;
        } else {
            errorMsg.classList.add('hidden');
            saveBtn.disabled = false;
        }
    }
</script>
<script>
document.querySelectorAll('.save-stage-btn').forEach(button => {
    button.addEventListener('click', function () {
        const stageContainer = this.closest('.stage-container');
        const scheduleId = parseInt(this.getAttribute('data-schedule-id'));
        const stageNumber = parseInt(stageContainer.getAttribute('data-stage'));
        const newDate = stageContainer.querySelector('input[name="unlock_date"]').value;

        fetch('update_stage.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `schedule_id=${scheduleId}&stage_number=${stageNumber}&new_date=${encodeURIComponent(newDate)}`
        })
        .then(response => response.json())
        .then(data => {
            const status = data.status;
            const message = encodeURIComponent(data.message);
            window.location.href = `edit_project_schedule.php?schedule_id=${scheduleId}&status=${status}&message=${message}`;
        })
        .catch(error => {
            const message = encodeURIComponent("Something went wrong while connecting to server.");
            window.location.href = `edit_project_schedule.php?schedule_id=${scheduleId}&status=error&message=${message}`;
        });
    });
});
</script>

