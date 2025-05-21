<?php
include 'header.php';
// Database connection
include 'connection.php';

// Fetch selected courses for the admin's college
$admin_id = $_SESSION['user_id'];
$fetchCollege = "SELECT college_id FROM colleges WHERE admin_id = '$admin_id'";
$collegeResult = mysqli_query($conn, $fetchCollege);
$collegeRow = mysqli_fetch_assoc($collegeResult);
$college_id = $collegeRow['college_id'] ?? null;

if (!$college_id) {
    echo json_encode(["status" => "error", "message" => "College not found"]);
    exit;
}

// Pagination setup
$limit = 5; // Number of rows per page
$page = isset($_GET['page']) ? intval($_GET['page']) : 1; // Current page
$offset = ($page - 1) * $limit; // Offset for SQL query

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : "";
$searchQuery = $search ? "AND c.name LIKE '%$search%'" : "";

// Fetch total records for pagination (only allocated courses)
$totalQuery = "SELECT COUNT(*) AS total 
               FROM college_courses cc 
               JOIN courses c ON cc.course_id = c.course_id 
               WHERE cc.college_id = '$college_id' 
               AND cc.sub_admin_id IS NOT NULL  -- Only allocated courses
               AND cc.college_course_status != 0
               $searchQuery";
$totalResult = mysqli_query($conn, $totalQuery);
$totalRow = mysqli_fetch_assoc($totalResult);
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $limit);

$query = "
    SELECT 
        c.course_id, 
        c.name AS course_name, 
        c.total_semesters, 
        c.duration, 
        u.full_name AS allocated_sub_admin,
        cc.college_course_id,
        cc.sub_admin_id 
    FROM college_courses cc
    JOIN courses c ON cc.course_id = c.course_id
    LEFT JOIN users u ON cc.sub_admin_id = u.user_id
    WHERE cc.college_id = '$college_id' 
    AND cc.sub_admin_id IS NOT NULL  -- Only allocated courses
    AND cc.college_course_status != 0  -- Exclude inactive courses
    $searchQuery
    ORDER BY c.name
    LIMIT $limit OFFSET $offset
";

$result = mysqli_query($conn, $query);

$message = isset($_SESSION['message']) ? $_SESSION['message'] : "";
$message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : "";

// Clear message after displaying
unset($_SESSION['message'], $_SESSION['message_type']);
?>


<main class="h-full overflow-y-auto">
    <div class="container px-6 mx-auto grid">
        <h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">
            Courses
        </h2>
        <!-- CTA -->

        <!-- Cards -->
        <div class="grid gap-6 mb-8 md:grid-cols-2 xl:grid-cols-4">
            <!-- Card -->
            <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
                <div class="p-3 mr-4 text-orange-500 bg-orange-100 rounded-full dark:text-orange-100 dark:bg-orange-500">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 2L2 6l8 4 8-4-8-4zM2 9l8 4 8-4v2l-8 4-8-4V9zM2 14v2h16v-2H2z"></path>
                    </svg>
                </div>
                <div>
                    <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                        Total Deallocated Courses
                    </p>
                    <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                        <?php echo $totalRecords; ?>
                    </p>
                </div>
            </div>
            <!-- Card -->
            <!-- Card -->
        </div>
    </div>



            <h4 class="mb-4 text-lg font-semibold text-gray-600 dark:text-gray-300">
    Courses Allocation List
</h4>
<div class="w-full mb-8 overflow-hidden rounded-lg shadow-xs">
    
    <!-- Table Wrapper -->
    <div class="w-full overflow-x-auto">
        <!-- Search Form -->
        <div class="flex justify-between items-center mb-4">
    <!-- Left Side: Add Course & Request Course Buttons -->
    <div class="flex space-x-4">
    <button id="addCourseBtn"  
            class="bg-blue-500 text-white px-4 py-2 rounded" 
            onclick="location.href='course_allocation_list.php';" >
            Back To Course List
        </button>

        <button id="requestCourseBtn" 
            class="bg-blue-500 text-white px-4 py-2 rounded" 
            onclick="location.href='allocate_course.php';">
            Allocated Course
        </button>
    </div>

    <!-- Center: Display Messages -->
    <div class="flex-grow text-center">
    <?php if (!empty($message)): ?> 
    <div id="alertBox" class="p-4 mb-4 text-sm rounded-lg flex justify-between items-center 
        <?php echo ($message_type === 'success') ? 'bg-green-100 text-green-700 border border-green-400' : 'bg-red-100 text-red-700 border border-red-400'; ?>">
        <span><?php echo htmlspecialchars($message); ?></span>
        <!-- Close Button -->
        <button onclick="closeAlert()" class="ml-4 focus:outline-none">
            <span class="text-gray-600 hover:text-gray-900">&times;</span>
        </button>
    </div>

    <script>
        function closeAlert() {
            document.getElementById("alertBox").style.opacity = "0";
            setTimeout(() => {
                document.getElementById("alertBox").style.display = "none";
            }, 300);
        }
    </script>
<?php endif; ?>


    </div>

    <!-- Right Side: Search Bar -->
    <form method="GET" class="flex items-center space-x-2">
        <input 
            type="text" 
            name="search" 
            placeholder="Search courses..." 
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

<!-- Courses Table -->
<table class="w-full whitespace-no-wrap">
    <thead>
        <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800">
            <th class="px-4 py-3">SR NO.</th>
            <th class="px-4 py-3">COURSE NAME</th>
            <th class="px-4 py-3">TOTAL SEMESTERS</th>
            <th class="px-4 py-3">DURATION</th>
            <th class="px-4 py-3">ALLOCATED SUB_ADMIN</th>
            <th class="px-4 py-3">ACTION</th>
            
        </tr>
    </thead>
    <tbody class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
        <?php if ($result->num_rows > 0): ?>
            <?php $srNo = $offset + 1; ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400"><?php echo $srNo++; ?></td>
            <td class="px-4 py-3 font-semibold text-xs"><?php echo htmlspecialchars($row['course_name']); ?></td>
            <td class="px-4 py-3 font-semibold text-xs"><?php echo htmlspecialchars($row['total_semesters']); ?></td>
            <td class="px-4 py-3 font-semibold text-xs"><?php echo htmlspecialchars($row['duration']); ?> Years</td>
            <td class="px-4 py-3 font-semibold text-xs"><?php echo htmlspecialchars($row['allocated_sub_admin']); ?></td>
            <!-- Display allocation status -->
            <td class="px-4 py-3 font-semibold text-xs">
    <button 
        class="px-4 py-2 bg-red-400 bg-text-white font-semibold rounded-full shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 status-toggle"
        data-id="<?php echo $row['college_course_id']; ?>"
        onclick="openDeallocationPopup(<?php echo $row['college_course_id']; ?>,'<?php echo $row['course_name'];  ?>' , <?php echo ($row['sub_admin_id']); ?>)">
        Deallocate
    </button>
</td>


                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">No Courses Selected</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

</div>

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

    <!-- Deallocation Popup -->
<div id="deallocationPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-[800px]">
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 text-center">Deallocate Course from Sub-admin</h2>

        <form id="deallocationForm" enctype="multipart/form-data">
            <input type="hidden" id="deallocate_course_id" name="course_id">
            <input type="hidden" id="deallocate_college_course_id" name="college_course_id">
            <input type="hidden" id="deallocate_course_name" name="course_name">
            <input type="hidden" id="sub_admin_id" name="user_id">

            <!-- Flex container for two columns -->
            <div class="flex space-x-6">
                <!-- Sub-admin Data Section -->
                <div class="w-1/2 space-y-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 dark:text-gray-200">Sub-admin Name</label>
                        <input type="text" id="sub_admin_full_name" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-md" readonly>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 dark:text-gray-200">Email</label>
                        <input type="email" id="sub_admin_email" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-md" readonly>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 dark:text-gray-200">Phone Number</label>
                        <input type="text" id="sub_admin_phone" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-md" readonly>
                    </div>
                </div>

                <!-- Course Details and Deallocation Section -->
                <div class="w-1/2 space-y-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 dark:text-gray-200">Course Name</label>
                        <input type="text" id="course_name_display" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-md" disabled>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 dark:text-gray-200">Subject</label>
                        <input type="text" name="subject" id="subject" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-md" required>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 dark:text-gray-200">Body</label>
                        <textarea name="body" id="body" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-md" required></textarea>
                    </div>

                
                </div>
            </div>

            <div class="mt-6 flex justify-center space-x-3">
                <button type="button" id="cancelDeallocation" class="px-4 py-2 bg-gray-500 text-black rounded-md hover:bg-gray-600">
                    Cancel
                </button>
                <button type="submit" id="confirmDeallocation" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-red-600">
                    Deallocate
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Success Popup -->
<div id="statusSuccessPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 text-center">Course Deallocated Successfully</h2>
        <p class="text-gray-600 dark:text-gray-300 text-center">
            The course has been successfully deallocated from the sub-admin.
        </p>
        <div class="mt-6 flex justify-center">
            <button id="closeStatusSuccess" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                OK
            </button>
        </div>
    </div>
</div>

<script>
// Open Deallocation Popup
function openDeallocationPopup(collegeCourseId, courseName, subAdminId) {
    // Open the popup
    document.getElementById('deallocationPopup').classList.remove('hidden');
    
    // Set values in the form
    document.getElementById('deallocate_course_id').value = courseName;
    document.getElementById('deallocate_college_course_id').value = collegeCourseId;
    document.getElementById('deallocate_course_name').value = courseName;
    document.getElementById('sub_admin_id').value = subAdminId;

    // Fetch sub-admin data and populate fields
    fetchSubAdminDetails(subAdminId);
    document.getElementById('course_name_display').value = courseName;
}

function fetchSubAdminDetails(subAdminId) {
    fetch(`get_sub_admin_details.php?sub_admin_id=${subAdminId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('sub_admin_full_name').value = data.full_name;
            document.getElementById('sub_admin_email').value = data.email;
            document.getElementById('sub_admin_phone').value = data.phone;
        })
        .catch(error => console.error("Error fetching sub-admin details:", error));
}

// Prevent default form submission and handle confirmation
document.getElementById('deallocationForm').addEventListener('submit', function(event) {
    event.preventDefault();  // Prevent the default form submission

    // Show a confirmation popup
    if (confirm("Are you sure you want to deallocate this course?")) {
        // Proceed with deallocation and email sending
        deallocateCourseAndSendEmail();
    }
});

function deallocateCourseAndSendEmail() {
    let formData = new FormData(document.getElementById('deallocationForm'));

    fetch('deallocate_actual_course.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // After successful deallocation, show success message and redirect
            document.getElementById('deallocationPopup').classList.add('hidden');
            document.getElementById('statusSuccessPopup').classList.remove('hidden');
            
            // Close success popup after 2 seconds and redirect
            setTimeout(() => {
                document.getElementById('statusSuccessPopup').classList.add('hidden');
                window.location.href = 'deallocate_course.php'; // Redirect
            }, 2000); // Wait for 2 seconds before redirecting
        } else {
            alert('Deallocation failed');
        }
    })
    .catch(error => console.error("Error deallocating course:", error));
}

</script>

</div>

<?php $conn->close(); ?>

</body>
</html>


















