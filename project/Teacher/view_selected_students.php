<?php
include 'header.php';
include "connection.php";

// Get competition_id from URL
$competition_id = $_GET['competition_id'] ?? null;
$participant_id = $_GET['participant_id'] ?? null;

// Pagination setup
$limit = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Search input
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$searchCondition = $search ? " AND (u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)" : "";

// Get sub-admin's details
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

// Check valid course
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
    $params = [$college_id, $course_id, $competition_id];  // Add competition_id to the params
    $types = "iii";  // Add "i" for competition_id to the types

    if ($search) {
        $like = "%$search%";
        $params = array_merge($params, [$like, $like, $like]);
        $types .= "sss";
    }

    // Main query to get participants
    $query = "
        SELECT cp.*, 
               u.full_name, u.username, u.email, u.phone_number, u.current_semester AS student_semester, 
               c.name AS competition_name, s.status , s.is_verified_by_project_head
        FROM competition_participants cp
        LEFT JOIN users u ON cp.student_user_id = u.user_id
        LEFT JOIN competitions c ON cp.competition_id = c.competition_id
        LEFT JOIN student_submissions s on cp.competition_id= s.competition_id
        WHERE cp.college_id = ? AND cp.course_id = ? AND cp.competition_id = ? $searchCondition
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

    // Count query to get the total number of records
    $countQuery = "
        SELECT COUNT(*) AS total 
        FROM competition_participants cp
        LEFT JOIN users u ON cp.student_user_id = u.user_id
        WHERE cp.college_id = ? AND cp.course_id = ? AND cp.competition_id = ? $searchCondition
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

// Competition edit button logic
$canEditSelection = false;
if ($competition_id) {
    // Query competition details
    $compQuery = "SELECT college_registration_start_date, college_registration_end_date FROM competitions WHERE competition_id = ?";
    $stmt = $conn->prepare($compQuery);
    $stmt->bind_param("i", $competition_id);
    $stmt->execute();
    $compResult = $stmt->get_result();

    if ($compData = $compResult->fetch_assoc()) {
        // Get start and end dates
        $startDate = new DateTime($compData['college_registration_start_date']);
        $endDate = new DateTime($compData['college_registration_end_date']);
        $now = new DateTime('now'); // Current date and time

        // Set the end time to 23:59:59 to allow the button till the end of the day
        $endDate->setTime(23, 59, 59);

        // Compare current time with the start and end date
        if ($now >= $startDate && $now <= $endDate) {
            $canEditSelection = true; // Button is enabled
        }
    }
    $stmt->close();
}
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
                <a href="student_competition_selection_list.php"
                    class="inline-block px-4 py-2 bg-blue-500 text-white font-semibold rounded-md shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 cursor-default"
                >
                    Back To Competition List
                </a>
                 <!-- Right: Edit Selection Button -->
                 <?php if ($canEditSelection): ?>
                        <a href="edit_selection_list.php?competition_id=<?= $competition_id ?>"
                           class="inline-block px-4 py-2 bg-blue-500 text-white font-semibold rounded-md shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 ml-2">
                            Edit Selection
                        </a>
                    <?php else: ?>
                        <button disabled
                                class="inline-block px-4 py-2 bg-gray-300 text-gray-600 font-semibold rounded-md shadow-md cursor-not-allowed ml-2">
                            Edit Selection (Closed)
                        </button>
                    <?php endif; ?>
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
            <?php
        $statusText = "Not Submitted";
        if ($row['status'] == 0 && $row['is_verified_by_project_head'] == 0) {
            $statusText = "Submitted but Not Verified";
        } elseif ($row['status'] == 0 && $row['is_verified_by_project_head'] == 1) {
            $statusText = "Submitted and Approved";
        } elseif ($row['status'] == 1 && $row['is_verified_by_project_head'] == 2) {
            $statusText = "Submitted but Rejected";
        }
    ?>
            <td class="px-4 py-3 font-semibold text-xs"><?php echo $statusText; ?></td>
            <td class="px-4 py-3 font-semibold text-xs"><?php echo ($row['is_verified_by_project_head'] ? 'Verified' : 'Not Verified'); ?></td>
            <td class="px-4 py-3 font-semibold text-xs">
    <a href="view_submission_details.php?competition_id=<?php echo $row['competition_id']; ?>&participant_id=<?php echo $row['participant_id']; ?>" 
       class="px-4 py-2 text-black font-semibold rounded-full shadow-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
        View
    </a>
</td>

        </tr>
    <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="7" class="text-center py-4 text-gray-500">No participants found.</td>
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
</div>

<?php
// Close the connection
$conn->close();
?>
