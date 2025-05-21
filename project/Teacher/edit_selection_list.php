<?php
include 'header.php';
include "connection.php";

$competition_id = $_GET['competition_id'] ?? null;
// Pagination setup
$limit = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Search input
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$searchCondition = $search ? " AND (u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)" : "";

// Step 1: Get sub-admin's details
$sub_admin_id = $_SESSION['user_id'];
$college_id = $course_id = null;

$userQuery = "SELECT college_id, course_id FROM users WHERE user_id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("i", $sub_admin_id);
$stmt->execute();
$userResult = $stmt->get_result();
if ($userData = $userResult->fetch_assoc()) {
    $college_id = $userData['college_id'];
    $course_id = $userData['course_id'];
}
$stmt->close();

// Step 2: Check valid course
$validCourse = false;
$verifyQuery = "SELECT 1 FROM college_courses WHERE college_id = ? AND course_id = ? AND college_course_status = 1";
$stmt = $conn->prepare($verifyQuery);
$stmt->bind_param("ii", $college_id, $course_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $validCourse = true;
}
$stmt->close();

$result = false;
$totalRecords = 0;
$totalPages = 0;

if ($validCourse) {
    // Prepare bindings
    $params = [$college_id, $course_id];
    $types = "ii";

    if ($search) {
        $like = "%$search%";
        $params = array_merge($params, [$like, $like, $like]);
        $types .= "sss";
    }

    // Main query
    $query = "
        SELECT cp.*, 
               u.full_name, u.username, u.email, u.phone_number, u.current_semester AS student_semester, 
               c.name AS competition_name
        FROM competition_participants cp
        LEFT JOIN users u ON cp.student_user_id = u.user_id
        LEFT JOIN competitions c ON cp.competition_id = c.competition_id
        WHERE cp.college_id = ? AND cp.course_id = ? $searchCondition
        ORDER BY cp.created_at DESC
        LIMIT ? OFFSET ?
    ";

    $stmt = $conn->prepare($query);
    $typesWithLimit = $types . "ii"; // add limit & offset
    $paramsWithLimit = [...$params, $limit, $offset];
    $stmt->bind_param($typesWithLimit, ...$paramsWithLimit);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    // Count query
    $countQuery = "
        SELECT COUNT(*) AS total 
        FROM competition_participants cp
        LEFT JOIN users u ON cp.student_user_id = u.user_id
        WHERE cp.college_id = ? AND cp.course_id = ? $searchCondition
    ";
    $stmt = $conn->prepare($countQuery);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $countResult = $stmt->get_result()->fetch_assoc();
    $totalRecords = $countResult['total'] ?? 0;
    $totalPages = ceil($totalRecords / $limit);
    $stmt->close();
}

// Flash message
$message = $_SESSION['message'] ?? "";
$message_type = $_SESSION['message_type'] ?? "";
unset($_SESSION['message'], $_SESSION['message_type']);
?>

<main class="h-full overflow-y-auto">
    <div class="container px-6 mx-auto grid">
        <h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">Competition Selection</h2>

        <?php if (!empty($suspension_message)) : ?>
            <div class="p-4 mb-4 text-sm text-yellow-800 rounded-lg bg-yellow-100 dark:bg-yellow-200 dark:text-yellow-900">
                <?php echo $suspension_message; ?>
            </div>
        <?php endif; ?>

        <!-- Dashboard Cards -->
        
        <!-- College Info and Course Overview Cards -->

        <h4 class="mb-4 text-lg font-semibold text-gray-600 dark:text-gray-300 mt-2">
    Selected Students List
</h4>
<div class="w-full mb-8 overflow-hidden rounded-lg shadow-xs">
    <!-- Table Wrapper -->
    <div class="w-full overflow-x-auto">
        <!-- Search Form -->
        <div class="flex justify-between items-center mb-4">
            <!-- Left: Add academic Button -->
            <div class="flex space-x-2">
            <a href="view_selected_students.php?competition_id=<?= $competition_id ?>"
   class="inline-block px-4 py-2 bg-blue-500 text-white font-semibold rounded-md shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 cursor-default">
    Back To View Student List
</a>

            </div>

            <!-- Center: Custom Popup Message -->
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

            <!-- Right: Search Bar -->
            <form method="GET" class="flex items-center space-x-2">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search academics..." 
                    value="<?php echo htmlspecialchars($search); ?>" 
                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                <button 
                    type="submit" 
                    class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-md shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2"
                >
                    Search
                </button>
            </form>
        </div>
    </div>
</div>



<!-- Participants Table -->
<table class="w-full whitespace-no-wrap">
    <thead>
        <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800">
            <th class="px-4 py-3">SR NO.</th>
            <th class="px-4 py-3">STUDENT NAME</th>
            <th class="px-4 py-3">STUDENT SEMESTER</th>
            <th class="px-4 py-3">COMPETITION NAME</th>
            <th class="px-4 py-3">SUBMISSION STATUS</th>
            <th class="px-4 py-3">VERIFICATION STATUS</th>
            <th class="px-4 py-3">ACTION</th>
            <th class="px-4 py-3">REMOVE</th> <!-- New Column for Remove Button -->
        </tr>
    </thead>
    <tbody class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
    <?php if ($result && $result->num_rows > 0): ?>
    <?php $srNo = $offset + 1; ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr class="text-gray-700 dark:text-gray-400">
            <td class="px-4 py-3 font-semibold text-xs"><?php echo $srNo++; ?></td>
            <td class="px-4 py-3 font-semibold text-xs"><?php echo htmlspecialchars($row['full_name']); ?></td>
            <td class="px-4 py-3 font-semibold text-xs"><?php echo htmlspecialchars($row['student_semester']); ?></td>
            <td class="px-4 py-3 font-semibold text-xs"><?php echo htmlspecialchars($row['competition_name']); ?></td>
            <td class="px-4 py-3 font-semibold text-xs"><?php echo htmlspecialchars($row['submission_status']); ?></td>
            <td class="px-4 py-3 font-semibold text-xs"><?php echo ($row['is_verified_by_project_head'] ? 'Verified' : 'Not Verified'); ?></td>
            <td class="px-4 py-3 font-semibold text-xs">
                <button 
                    class="px-4 py-2 text-black font-semibold rounded-full shadow-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 view-participant"
                    style="background-color: #ffff;">
                    View
                </button>
            </td>

            <!-- New Column: Remove Button -->
            <td class="px-4 py-3 text-xs">
            <button 
                    class="px-4 py-2 text-white bg-blue-500 font-semibold rounded-full shadow-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 remove-selection"
                    data-id="<?php echo $row['participant_id']; ?>"> 
                    Remove
                </button>
            </td>
        </tr>
    <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="8" class="text-center py-4 text-gray-500">No participants found.</td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>

<!-- Pagination -->
<div class="flex justify-end mt-4">
    <nav class="flex items-center space-x-2">
        <!-- Back Button -->
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>"
               class="px-4 py-2 border border-gray-300 text-gray-600 rounded-md hover:bg-blue-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">
                Back
            </a>
        <?php endif; ?>

        <!-- Page Numbers -->
        <?php 
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);
        ?>
        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"
               class="px-4 py-2 border border-gray-300 text-gray-600 rounded-md hover:bg-blue-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 <?php echo $i == $page ? 'bg-blue-500 text-white' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <!-- Next Button -->
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>"
               class="px-4 py-2 border border-gray-300 text-gray-600 rounded-md hover:bg-blue-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">
                Next
            </a>
        <?php endif; ?>
    </nav>
    <!-- Modal for Confirmation -->
    <!-- Custom confirmation popup -->
<div id="removeConfirmPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-semibold text-gray-700 mb-4 text-center">Confirm Removal</h2>
        <p id="removeConfirmMessage" class="text-gray-600 text-center mb-4"></p>
        <div class="flex justify-center space-x-4">
            <button id="removeConfirmYes" class="px-4 py-2 bg-blue-500 text-white rounded-md">Yes</button>
            <button id="removeConfirmNo" class="px-4 py-2 bg-gray-500 text-black rounded-md">No</button>
        </div>
    </div>
</div>

<!-- Custom success popup -->
<div id="removeSuccessPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-semibold text-gray-700 mb-4 text-center">Success</h2>
        <p id="removeSuccessMessage" class="text-gray-600 text-center"></p>
        <div class="flex justify-center mt-4">
            <button id="closeRemoveSuccess" class="px-4 py-2 bg-blue-500 text-white rounded-md">OK</button>
        </div>
    </div>
</div>
<!-- Rest of your table code goes here -->

</div>
<script>
   document.addEventListener("DOMContentLoaded", () => {
    const removeButtons = document.querySelectorAll(".remove-selection");
    const removeConfirmPopup = document.getElementById("removeConfirmPopup");
    const removeSuccessPopup = document.getElementById("removeSuccessPopup");
    const removeConfirmYes = document.getElementById("removeConfirmYes");
    const removeConfirmNo = document.getElementById("removeConfirmNo");
    const closeRemoveSuccess = document.getElementById("closeRemoveSuccess");

    let selectedParticipantId = null;

    // Trigger when Remove button is clicked
    removeButtons.forEach(button => {
        button.addEventListener("click", () => {
            selectedParticipantId = button.dataset.id;

            // Set the confirmation message dynamically
            const studentName = button.closest('tr').querySelector('td:nth-child(2)').textContent;
            document.getElementById("removeConfirmMessage").textContent = `Are you sure you want to remove student ${studentName}?`;

            // Show the confirmation popup
            removeConfirmPopup.classList.remove("hidden");
        });
    });

    // Confirm Removal (Yes)
    removeConfirmYes.addEventListener("click", () => {
        // Perform the removal action here
        const formData = new FormData();
        formData.append('participant_id', selectedParticipantId);

        fetch("remove_participant.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hide the confirmation popup
                removeConfirmPopup.classList.add("hidden");

                // Show the success popup
                document.getElementById("removeSuccessMessage").textContent = "Student removed successfully!";
                removeSuccessPopup.classList.remove("hidden");

                // Optionally, refresh or update the table
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("An error occurred.");
        });
    });

    // Cancel Removal (No)
    removeConfirmNo.addEventListener("click", () => {
        removeConfirmPopup.classList.add("hidden");
    });

    // Close success popup (OK button)
    closeRemoveSuccess.addEventListener("click", () => {
        removeSuccessPopup.classList.add("hidden");
    });
});

</script>


<?php
// Close the connection
$conn->close();
?>
