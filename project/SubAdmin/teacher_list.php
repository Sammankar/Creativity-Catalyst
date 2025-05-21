<?php
include('header.php');
include "connection.php";
// Fetch user_id from session
$user_id = $_SESSION['user_id'];

// Fetch college_id from users table using user_id
$collegeQuery = "SELECT college_id FROM users WHERE user_id = $user_id";
$collegeResult = $conn->query($collegeQuery);
$collegeRow = $collegeResult->fetch_assoc();
$college_id = $collegeRow['college_id'];

// Pagination Variables
$limit = 5; // Records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search term handling
$search = "";
if (!empty($_GET['search'])) {
    // Get the raw search term
    $search = $_GET['search'];

    // Escape it for use in SQL query
    $searchQuery = " AND (u.full_name LIKE '%$search%' OR u.email LIKE '%$search%')";
} else {
    $searchQuery = ""; // No search query if empty
}

// Fetch Guides with Course Name for the specific college
$query = "
    SELECT 
        u.user_id,
        u.full_name AS guide_name,  
        u.email AS guide_email, 
        u.phone_number AS guide_phone, 
        u.role AS guide_role,
        u.access_status,
        u.users_status AS guide_status,
        u.created_at, 
        c.name AS course_name
    FROM users u
    LEFT JOIN courses c ON u.course_id = c.course_id
    WHERE u.role = 4 AND u.guide_permission= 0 AND u.college_id = $college_id $searchQuery
    LIMIT $limit OFFSET $offset";

$result = $conn->query($query);

// Fetch Data
$guides = [];
while ($row = $result->fetch_assoc()) {
    $guides[] = $row;
}

// Total Records Count for Pagination
$countQuery = "SELECT COUNT(*) as total FROM users u WHERE u.role = 4 AND u.guide_permission=0  AND u.college_id = $college_id $searchQuery";
$countResult = $conn->query($countQuery);
$countRow = $countResult->fetch_assoc();
$totalRecords = $countRow['total'];
$totalPages = ceil($totalRecords / $limit);
?>
<main class="h-full overflow-y-auto">
    <div class="container px-6 mx-auto grid">
        <h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">Guides</h2>
        
        <div class="grid gap-6 mb-8 md:grid-cols-2 xl:grid-cols-4">
            <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
                <div class="p-3 mr-4 text-orange-500 bg-orange-100 rounded-full dark:text-orange-100 dark:bg-orange-500">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 2L2 6l8 4 8-4-8-4zM2 9l8 4 8-4v2l-8 4-8-4V9zM2 14v2h16v-2H2z"></path>
                    </svg>
                </div>
                <div>
                    <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">Total Guides</p>
                    <p class="text-lg font-semibold text-gray-700 dark:text-gray-200"><?php echo $totalRecords; ?></p>
                </div>
            </div>
        </div>

        <h4 class="mb-4 text-lg font-semibold text-gray-600 dark:text-gray-300">Teachers List</h4>
        
        <div class="w-full mb-8 overflow-hidden rounded-lg shadow-xs">
            <div class="w-full overflow-x-auto">
                <!-- Search Form -->
                <div class="flex justify-between items-center mb-4">
                    <div class="w-1/3"></div>

                    <form method="GET" class="flex items-center space-x-2 w-1/3 justify-end">
                        <input 
                            type="text" 
                            name="search" 
                            placeholder="Search guides..." 
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

                <!-- Table -->
                <table class="w-full whitespace-no-wrap">
                    <thead>
                        <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800">
                            <th class="px-4 py-3">SR NO</th>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Email</th>   
                            <th class="px-4 py-3">ALLOCATED COURSE</th>
                            <th class="px-4 py-3">STATUS</th>
                            <th class="px-4 py-3">ACCESS</th>
                            <th class="px-4 py-3">ACTION</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
                        <?php $srNo = $offset + 1; ?>
                        <?php foreach ($guides as $guide): ?>
                            <tr class="text-gray-700 dark:text-gray-400">
                                <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400"><?php echo $srNo++; ?></td>
                                <td class="px-4 py-3 font-semibold text-xs"><?php echo $guide['guide_name']; ?></td>
                                <td class="px-4 py-3 font-semibold text-xs"><?php echo $guide['guide_email']; ?></td>
                                <td class="px-4 py-3 font-semibold text-xs"><?php echo $guide['course_name']; ?></td>
                                <td class="px-4 py-3 font-semibold text-xs">
                                    <button 
                                        class="px-4 py-2 font-semibold rounded-full shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 status-toggle"
                                        data-id="<?php echo $guide['user_id']; ?>"
                                        data-status="<?php echo $guide['guide_status']; ?>"
                                        style="background-color: <?php echo ($guide['guide_status'] == 1) ? '#10b981' : '#ef4444'; ?>;
                                               color: <?php echo ($guide['guide_status'] == 1) ? 'white' : 'white'; ?>;">
                                        <?php echo ($guide['guide_status'] == 1) ? 'Active' : 'Inactive'; ?>
                                    </button>
                                </td>
                                <td class="px-4 py-3 font-semibold text-xs">
                                <?php if ($guide['access_status'] == 0): ?>
        <!-- Disable the button if access is restricted -->
        <button class="px-4 py-2 font-semibold rounded-full shadow-md bg-red-500 text-black cursor-not-allowed" disabled>
            Restricted
        </button>
    <?php else: ?>
                                    <button 
                                        class="px-4 py-2 font-semibold rounded-full shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 access-toggle"
                                        data-id="<?php echo $guide['user_id']; ?>"
                                        data-status="<?php echo $guide['access_status']; ?>"
                                        style="background-color: <?php echo $guide['access_status'] ? '#10b981' : '#ef4444'; ?>; color: white;">
                                        <?php echo $guide['access_status'] ? 'Access' : 'Restricted'; ?>
                                    </button>
                                    <?php endif; ?>
                                </td>  
                                <td class="px-4 py-3 font-semibold text-xs">
                                    <button 
                                        class="px-4 py-2 text-white bg-blue-500 font-semibold rounded-full shadow-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 view-guide"
                                        data-id="<?php echo $guide['user_id']; ?>"> <!-- Correct variable for guide -->
                                        View
                                    </button>
                                    <button 
    class="px-4 py-2 text-white bg-blue-500 font-semibold rounded-full shadow-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 promote-guide"
    data-id="<?php echo $guide['user_id']; ?>"
    data-name="<?php echo $guide['guide_name']; ?>"
    data-course="<?php echo $guide['course_name']; ?>" >
    Promote
</button>

                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="flex justify-between mt-4">
                    <div>
                        <span class="text-sm text-gray-600 dark:text-gray-400">Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $totalRecords); ?> of <?php echo $totalRecords; ?> results</span>
                    </div>
                    <div class="flex space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="px-3 py-1 bg-gray-300 text-gray-600 rounded-md hover:bg-gray-400">Previous</a>
                        <?php endif; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="px-3 py-1 bg-gray-300 text-gray-600 rounded-md hover:bg-gray-400">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div id="promotionPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden"> 
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 text-center">Promote Guide</h2>
        <p class="text-gray-600 dark:text-gray-300 text-center">
            Are you sure you want to promote <span id="guideName" class="font-bold"></span> to a Guide with permissions in the course <span id="courseName" class="font-bold"></span>?
        </p>

        <!-- Confirmation Button -->
        <div class="mt-6 flex justify-center space-x-3">
            <button id="cancelPromotion" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">
                Cancel
            </button>
            <button id="confirmPromotion" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                Confirm Promotion
            </button>
        </div>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function() {
    // Select all promote buttons
    const promoteButtons = document.querySelectorAll('.promote-guide');
    const promotionPopup = document.getElementById('promotionPopup');
    const cancelPromotion = document.getElementById('cancelPromotion');
    const confirmPromotion = document.getElementById('confirmPromotion');
    
    let selectedGuideId = null;
    let selectedGuideName = '';
    let selectedCourseName = '';

    // Event listener for "Promote" button click
    promoteButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Get the guide's details from the button's data attributes
            selectedGuideId = button.getAttribute('data-id');
            selectedGuideName = button.getAttribute('data-name');
            selectedCourseName = button.getAttribute('data-course');
            
            // Set the text in the popup
            document.getElementById('guideName').textContent = selectedGuideName;
            document.getElementById('courseName').textContent = selectedCourseName;
            
            // Show the popup
            promotionPopup.classList.remove('hidden');
        });
    });

    // Close the popup (Cancel button)
    cancelPromotion.addEventListener('click', function() {
        promotionPopup.classList.add('hidden');
    });

    // Confirm the promotion (Update guide_permission in DB and redirect)
    confirmPromotion.addEventListener('click', function() {
        // Send AJAX request to update the guide_permission in the database
        fetch('promote_guide_v2.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ user_id: selectedGuideId })
        })
        .then(response => response.json())
        .then(data => {
            // If success, hide the popup and redirect to guide list
            if (data.success) {
                alert('Guide promoted successfully!');
                window.location.href = 'guide_list.php'; // Redirect to guide list page
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while promoting the guide.');
        });
    });
});

</script>
    </div>
</main>
