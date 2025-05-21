<?php
ob_start();
include "header.php";

if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "Unauthorized access! Please log in as Super-Admin.";
    $_SESSION['message_type'] = "error";
    header("Location: index.php");
    exit();
}

include("connection.php");

$created_by = $_SESSION['user_id'];
$academic_id = $_GET['id']; // Academic calendar ID for editing

// Fetch the academic calendar details
$query_academic = "SELECT ac.*, c.name AS course_name, c.total_semesters, c.course_status
                   FROM academic_calendar ac 
                   JOIN courses c ON ac.course_id = c.course_id
                   WHERE ac.id = ?";
$stmt_academic = $conn->prepare($query_academic);
$stmt_academic->bind_param("i", $academic_id);
$stmt_academic->execute();
$result_academic = $stmt_academic->get_result()->fetch_assoc();
$stmt_academic->close();

if (!$result_academic) {
    $_SESSION['message'] = "Academic calendar not found.";
    $_SESSION['message_type'] = "error";
    header("Location: academic_calendar.php");
    exit();
}

// Check if editing is allowed
$can_edit = $result_academic['is_editable'] == 1 && (strtotime($result_academic['end_date']) >= time());
?>

<main class="h-full overflow-y-auto mt-8">
    <div class="container px-6 mx-auto grid">
        <?php if (isset($_SESSION['message'])) { ?>
            <div class="p-3 mb-3 text-sm text-<?php echo $_SESSION['message_type'] == 'success' ? 'green' : 'red'; ?>-700 bg-<?php echo $_SESSION['message_type'] == 'success' ? 'green' : 'red'; ?>-100 rounded">
                <?php echo $_SESSION['message']; ?>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        <?php } ?>

        <div class="container mx-auto p-6 mt-8">
            <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-200">
                <h1 class="text-xl font-semibold mb-4">Selected Academic Calendar Details</h1>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Course</label>
                    <input type="text" value="<?php echo $result_academic['course_name']; ?>" readonly
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-100">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Semester</label>
                    <input type="text" value="Semester <?php echo $result_academic['semester']; ?>" readonly
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-100">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Academic Year</label>
                    <input type="text" value="<?php echo $result_academic['academic_year']; ?>" readonly
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-100">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Start Date</label>
                    <input type="text" value="<?php echo date('d-F-Y', strtotime($result_academic['start_date'])); ?>" readonly
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-100">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">End Date</label>
                    <input type="text" value="<?php echo date('d-F-Y', strtotime($result_academic['end_date'])); ?>" readonly
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-100">
                </div>

                <?php if ($can_edit): ?>
                    <h2 class="text-lg font-semibold mb-4">Update Academic Calendar</h2>
                    <form method="POST" action="update_academic_logic.php" onsubmit="return validateEndDate();">
                        <input type="hidden" name="academic_id" value="<?php echo $academic_id; ?>">
                        <input type="hidden" id="original_end_date" value="<?php echo $result_academic['end_date']; ?>">
                        <input type="hidden" id="calendar_status" value="<?php echo $result_academic['status']; ?>">

                        <div class="mb-4">
                            <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                            <input type="date" name="start_date" id="start_date" required
                                value="<?php echo $result_academic['start_date']; ?>"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div class="mb-1">
                            <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                            <input type="date" name="end_date" id="end_date" required
                                value="<?php echo $result_academic['end_date']; ?>"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                oninput="validateEndDate()">
                        </div>

                        <div id="endDateError" class="text-red-500 text-sm mb-3 hidden">
                            Cannot update to a date earlier than the original end date after release.
                        </div>

                        <div class="flex justify-end">
                            <a href="academic_calendar.php" class="px-4 py-2 mr-2 bg-gray-500 text-black font-semibold rounded-lg shadow-md hover:bg-gray-600 focus:outline-none">
                                Cancel
                            </a>
                            <button id="updateButton" type="submit" class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-lg shadow-md hover:bg-blue-600">
                                Update Calendar
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="text-red-500 text-center mt-4">
                        This academic calendar has ended and cannot be edited.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
function validateEndDate() {
    const originalEndDate = new Date(document.getElementById('original_end_date').value);
    const newEndDate = new Date(document.getElementById('end_date').value);
    const calendarStatus = parseInt(document.getElementById('calendar_status').value);
    const errorDiv = document.getElementById('endDateError');
    const updateBtn = document.getElementById('updateButton');

    if (calendarStatus === 1 && newEndDate < originalEndDate) {
        errorDiv.classList.remove('hidden');
        updateBtn.disabled = true;
        updateBtn.classList.add('opacity-50', 'cursor-not-allowed');
        return false;
    } else {
        errorDiv.classList.add('hidden');
        updateBtn.disabled = false;
        updateBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        return true;
    }
}
</script>
