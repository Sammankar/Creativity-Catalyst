<?php
include "header.php";
include "connection.php";

// Unlock stages if unlock_date has passed and schedule status is 1
$today = date('Y-m-d');
$updateStagesQuery = "
    UPDATE project_submission_stages s
    INNER JOIN project_submission_schedule pss ON s.schedule_id = pss.id
    SET s.is_locked = 0
    WHERE s.unlock_date <= ? AND s.is_locked = 1 AND pss.status = 1
";
$stmt = $conn->prepare($updateStagesQuery);
$stmt->bind_param("s", $today);
$stmt->execute();
$stmt->close();

$lockStagesQuery = "
    UPDATE project_submission_stages s
    INNER JOIN project_submission_schedule pss ON s.schedule_id = pss.id
    SET s.is_locked = 1
    WHERE s.unlock_date > ? AND s.is_locked = 0 AND pss.status = 1
";
$lockStmt = $conn->prepare($lockStagesQuery);
$lockStmt->bind_param("s", $today);
$lockStmt->execute();
$lockStmt->close();


// Pagination setup
$limit = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Search input
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$searchCondition = $search ? "AND (pss.semester LIKE ? OR pss.status LIKE ?)" : "";

// Get sub-admin session
$sub_admin_id = $_SESSION['user_id'];
$college_id = $course_id = null;

// Get course & college
$stmt = $conn->prepare("SELECT college_id, course_id FROM users WHERE user_id = ?");
$stmt->bind_param("i", $sub_admin_id);
$stmt->execute();
$userResult = $stmt->get_result();
if ($userData = $userResult->fetch_assoc()) {
    $college_id = $userData['college_id'];
    $course_id = $userData['course_id'];
}
$stmt->close();

// Course name
$course_name = '';
$stmt = $conn->prepare("SELECT name FROM courses WHERE course_id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$courseData = $stmt->get_result()->fetch_assoc();
$course_name = $courseData['name'] ?? '';
$stmt->close();

// Search
$params = [$course_id];
$types = "i";
if ($search) {
    $like = "%$search%";
    $params = array_merge($params, [$like, $like]);
    $types .= "ss";
}

// Fetch paginated schedules
$query = "
    SELECT pss.*, u.full_name AS created_by_name 
    FROM project_submission_schedule pss
    LEFT JOIN users u ON pss.created_by = u.user_id
    WHERE pss.course_id = ? $searchCondition
    ORDER BY pss.start_date DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Count for pagination
$totalQuery = "SELECT COUNT(*) AS total FROM project_submission_schedule pss WHERE pss.course_id = ? $searchCondition";
$stmt = $conn->prepare($totalQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$totalRecords = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$totalPages = ceil($totalRecords / $limit);
$stmt->close();
// Count total schedules with exactly 4 stages
$countQuery = "
    SELECT COUNT(*) AS complete_schedules
    FROM project_submission_schedule pss
    WHERE pss.course_id = ?
    AND (
        SELECT COUNT(*) 
        FROM project_submission_stages pstage 
        WHERE pstage.schedule_id = pss.id
    ) = 4
";
$stmt = $conn->prepare($countQuery);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$countResult = $stmt->get_result()->fetch_assoc();
$completeSchedules = $countResult['complete_schedules'] ?? 0;
$stmt->close();

// Flash message
$message = $_SESSION['message'] ?? "";
$message_type = $_SESSION['message_type'] ?? "";
unset($_SESSION['message'], $_SESSION['message_type']);
?>
<main class="h-full overflow-y-auto">
          <div class="container px-6 mx-auto grid">
            <h2
              class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200"
            >
              Project Stage Schedule
            </h2>
            <!-- CTA -->

            <!-- Cards -->
            <div class="grid gap-6 mb-8 md:grid-cols-2 xl:grid-cols-4">
<!-- Card -->
            <div
                class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800"
              >
                <div
                  class="p-3 mr-4 text-orange-500 bg-orange-100 rounded-full dark:text-orange-100 dark:bg-orange-500"
                >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
  <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V4a2 2 0 00-2-2H6zm0 2h8v12H6V4zm2 2h4v2H8V6zm0 4h4v2H8v-2z" clip-rule="evenodd"></path>
</svg>


                </div>
                
                <div>
                  <p
                    class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400"
                  >
                  Total Completed Schedules(4 Stages):
                  </p>
                  <p
                    class="text-lg font-semibold text-gray-700 dark:text-gray-200"
                  >
                  <?php echo $completeSchedules; ?>
                  </p>
                </div>
              </div>
              <!-- Card -->
              <!-- Card -->
            </div>

<h4 class="mb-4 text-lg font-semibold text-gray-600 dark:text-gray-300">
    Project Submission Stages Schedule List
</h4>

<div class="w-full mb-8 overflow-hidden rounded-lg shadow-xs">
    <div class="w-full overflow-x-auto">

        <div class="flex justify-between items-center mb-4">
        <a 
        href="schedule_project_stages.php" 
        class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-md shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2"
    >
        + Schedule Project Stages
    </a>

            <!-- Flash Message -->
            <?php if (!empty($message)): ?>
                <div id="custom-popup" class="bg-white border <?php echo ($message_type === 'success') ? 'border-green-500 text-green-600' : 'border-red-500 text-red-600'; ?> px-6 py-3 rounded-lg shadow-lg flex items-center space-x-3 mx-auto">
                    <span><?= htmlspecialchars($message); ?></span>
                    <button onclick="closePopup()" class="ml-2 text-gray-700 font-bold">×</button>
                </div>
                <script>
                    function closePopup() {
                        document.getElementById("custom-popup").style.display = "none";
                    }
                    setTimeout(closePopup, 5000);
                </script>
            <?php endif; ?>

            <!-- Search -->
            <form method="GET" class="flex items-center space-x-2">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search schedules..." 
                    value="<?= htmlspecialchars($search); ?>" 
                    class="px-4 py-2 border border-gray-300 rounded-md"
                >
                <button 
                    type="submit" 
                    class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-md"
                >Search</button>
            </form>
        </div>

        <!-- Table -->
        <table class="w-full whitespace-no-wrap">
            <thead>
                <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                    <th class="px-4 py-3">SEMESTER</th>
                    <th class="px-4 py-3">ACADEMIC-YEAR</th>
                    <th class="px-4 py-3">PROJECT-START</th>
                    <th class="px-4 py-3">PROJECT-END</th>
                    <th class="px-4 py-3">STAGE-1</th>
                    <th class="px-4 py-3">STAGE-2</th>
                    <th class="px-4 py-3">STAGE-3</th>
                    <th class="px-4 py-3">STAGE-4</th>
                    <th class="px-4 py-3">STATUS</th>
                    <th class="px-4 py-3">ACTION</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y">
<?php if ($result && $result->num_rows > 0): ?>
    <?php $sr = $offset + 1; ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <?php
            $schedule_id = $row['id'];
            $stages = [];

            // Fetch 4 stages for this schedule
            $stageStmt = $conn->prepare("SELECT stage_number, unlock_date FROM project_submission_stages WHERE schedule_id = ? ORDER BY stage_number ASC");
            $stageStmt->bind_param("i", $schedule_id);
            $stageStmt->execute();
            $stageResult = $stageStmt->get_result();
            while ($stageRow = $stageResult->fetch_assoc()) {
                $formattedDate = date("j M Y", strtotime($stageRow['unlock_date']));
                $stages[$stageRow['stage_number']] = $formattedDate;
            }
            $stageStmt->close();

            // Fill missing stages with "-"
            for ($i = 1; $i <= 4; $i++) {
                if (!isset($stages[$i])) $stages[$i] = "-";
            }

            // Project status
            $endDateTime = strtotime($row['end_date'] . " 23:59:59");
            $status = (time() > $endDateTime) ? '<span class="text-blue-600 font-semibold">Completed</span>' : '<span class="text-green-600 font-semibold">In Progress</span>';

        ?>
        <tr class="text-gray-700 text-sm">
            <td class="px-4 py-3"><?= htmlspecialchars($row['semester']) ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($row['academic_year']) ?></td>
            <td class="px-4 py-3"><?= date("j M Y", strtotime($row['start_date'])) ?></td>
            <td class="px-4 py-3"><?= date("j M Y", strtotime($row['end_date'])) ?></td>
            <td class="px-4 py-3"><?= $stages[1] ?></td>
            <td class="px-4 py-3"><?= $stages[2] ?></td>
            <td class="px-4 py-3"><?= $stages[3] ?></td>
            <td class="px-4 py-3"><?= $stages[4] ?></td>
            <td class="px-4 py-3">
                <span class="inline-block px-2 py-1 text-xs font-medium rounded-full <?= $status === 'Completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                    <?= $status ?>
                </span>
            </td>
            <td class="px-4 py-3">
                <a href="view_project_schedule.php?schedule_id=<?= $schedule_id ?>" class="text-blue-600 hover:underline mr-2">View</a>
               <?php if ($row['is_editable'] == 1 && $row['status'] == 1): ?>
    <span class="text-gray-400 cursor-not-allowed" title="Editing disabled">Edit</span>
<?php else: ?>
    <a href="edit_project_schedule.php?schedule_id=<?= $schedule_id ?>" class="text-indigo-600 hover:underline">Edit</a>
<?php endif; ?>

            </td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr>
        <td colspan="11" class="px-4 py-3 text-center text-sm text-gray-500">No records found.</td>
    </tr>
<?php endif; ?>
</tbody>

        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="flex justify-end mt-4 px-4">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i; ?>&search=<?= urlencode($search); ?>" 
                   class="mx-1 px-3 py-1 rounded-md <?= $i === $page ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700'; ?>">
                    <?= $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>
<div id="viewProjectModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-lg shadow-lg w-96 p-6">
        <h2 class="text-lg font-semibold mb-4">Project Schedule Details</h2>

        <div class="mb-2">
            <label class="font-semibold">Course Name: </label>
            <input type="text" id="projectCourse" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">Semester: </label>
            <input type="text" id="projectSemester" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">Academic Year: </label>
            <input type="text" id="projectYear" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">Start Date: </label>
            <input type="text" id="projectStart" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">End Date: </label>
            <input type="text" id="projectEnd" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">Created By: </label>
            <input type="text" id="projectCreator" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-4">
            <label class="font-semibold">Status: </label>
            <input type="text" id="projectStatus" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <button id="closeProjectModal" class="w-full bg-blue-500 text-white py-2 rounded font-semibold">OK</button>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const viewButtons = document.querySelectorAll(".view-project");
    const modal = document.getElementById("viewProjectModal");
    const closeModal = document.getElementById("closeProjectModal");

    viewButtons.forEach(button => {
        button.addEventListener("click", (e) => {
            e.preventDefault();
            const id = button.dataset.id;

            fetch("fetch_project_schedule.php?id=" + id)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const p = data.schedule;
                        document.getElementById("projectCourse").value = p.course_name;
                        document.getElementById("projectSemester").value = p.semester;
                        document.getElementById("projectYear").value = p.academic_year;
                        document.getElementById("projectStart").value = new Date(p.start_date).toLocaleDateString();
                        document.getElementById("projectEnd").value = new Date(p.end_date).toLocaleDateString();
                        document.getElementById("projectCreator").value = p.created_by_name;

                        // Set end date to 23:59:59
                        const endDate = new Date(p.end_date);
                        endDate.setHours(23, 59, 59, 999);

                        document.getElementById("projectStatus").value = (new Date() > endDate) ? 'Closed' : 'Open';

                        modal.classList.remove("hidden");
                    } else {
                        alert("Unable to load project schedule details.");
                    }
                })
                .catch(error => {
                    console.error(error);
                    alert("Error fetching details.");
                });
        });
    });

    closeModal.addEventListener("click", () => {
        modal.classList.add("hidden");
    });
});
</script>
<!-- Project Status Confirmation Popup -->
<div id="projectStatusPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 text-center">Confirm Project Status Change</h2>
        <p class="text-gray-600 dark:text-gray-300 text-center">
            Are you sure you want to <span id="projectStatusActionText" class="font-bold"></span> this project?
        </p>
        <div class="mt-6 flex justify-center space-x-3">
            <button id="cancelProjectStatusChange" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">
                Cancel
            </button>
            <button id="confirmProjectStatusChange" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                Confirm
            </button>
        </div>
    </div>
</div>

<!-- Project Status Success Popup -->
<div id="projectStatusSuccessPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 text-center">Project Status Updated</h2>
        <p class="text-gray-600 dark:text-gray-300 text-center">
            The project status has been updated successfully.
        </p>
        <div class="mt-6 flex justify-center">
            <button id="closeProjectStatusSuccess" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                OK
            </button>
        </div>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const toggleButtons = document.querySelectorAll(".project-status-toggle");
    const statusPopup = document.getElementById("projectStatusPopup");
    const statusSuccessPopup = document.getElementById("projectStatusSuccessPopup");
    const cancelBtn = document.getElementById("cancelProjectStatusChange");
    const confirmBtn = document.getElementById("confirmProjectStatusChange");
    const closeSuccessBtn = document.getElementById("closeProjectStatusSuccess");
    const statusActionText = document.getElementById("projectStatusActionText");

    let selectedProjectId = null;
    let newStatus = null;
    let currentButton = null;

    // Handle clicking the status toggle button
    toggleButtons.forEach(button => {
        button.addEventListener("click", () => {
            if (button.dataset.status == 0 && button.dataset.editable == 0) return;

            selectedProjectId = button.dataset.id;
            newStatus = button.dataset.status === "1" ? 0 : 1;
            currentButton = button;

            statusActionText.textContent = newStatus === 1 ? "Start" : "Stop";
            statusPopup.classList.remove("hidden");
        });
    });

    // Cancel popup
    cancelBtn.addEventListener("click", () => {
        statusPopup.classList.add("hidden");
    });

    // Confirm status change
    confirmBtn.addEventListener("click", () => {
        if (!selectedProjectId) return;

        fetch("update_project_status.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                id: selectedProjectId,
                status: newStatus,
            }),
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                currentButton.dataset.status = data.newStatus;
                currentButton.style.backgroundColor = data.newStatus == 1 ? "#10b981" : "#ef4444";
                currentButton.textContent = data.newStatus == 1 ? "Start" : "Stop";

                statusPopup.classList.add("hidden");
                statusSuccessPopup.classList.remove("hidden");
            } else {
                alert("Failed to update project status.");
            }
        })
        .catch(err => {
            console.error(err);
            alert("Error occurred.");
        });
    });

    // Close success popup
    closeSuccessBtn.addEventListener("click", () => {
        statusSuccessPopup.classList.add("hidden");
    });
});
</script>



