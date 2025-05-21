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

// Fetch total records for pagination (only non-allocated courses)
$totalQuery = "SELECT COUNT(*) AS total 
               FROM college_courses cc 
               JOIN courses c ON cc.course_id = c.course_id 
               WHERE cc.college_id = '$college_id' 
               AND cc.sub_admin_id IS NULL
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
        cc.college_course_id
    FROM college_courses cc
    JOIN courses c ON cc.course_id = c.course_id
    WHERE cc.college_id = '$college_id' 
    AND cc.sub_admin_id IS NULL  -- Only non-allocated courses
    AND cc.college_course_status != 0  -- Exclude inactive courses
    $searchQuery
    ORDER BY c.name
    LIMIT $limit OFFSET $offset
";

$result = mysqli_query($conn, $query);

// Fetch sub-admins not yet allocated
$query1 = "
    SELECT u.user_id, u.full_name, u.users_status, u.email, u.phone_number
    FROM users u
    WHERE u.role = 3 
    AND u.user_id NOT IN (
        SELECT sub_admin_id FROM college_courses WHERE college_id = '$college_id' AND sub_admin_id IS NOT NULL
    )
    AND u.users_status = 1";  // Only Active users

$result1 = mysqli_query($conn, $query1);
$subAdmins = mysqli_fetch_all($result1, MYSQLI_ASSOC);

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
                        Total Not Allocated Courses
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
            onclick="location.href='deallocate_course.php';">
            Deallocate Course
        </button>
    </div>

    <!-- Center: Display Messages -->
    <div class="flex-grow text-center">
    <?php if (isset($_GET['message']) && isset($_GET['message_type']) && $_GET['message_type'] === 'success'): ?>
    <div id="alertBox" class="p-4 mb-4 text-sm rounded-lg flex justify-between items-center bg-green-100 text-green-700 border border-green-400">
        <span><?php echo htmlspecialchars($_GET['message']); ?></span>
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
            <td class="px-4 py-3 font-semibold text-xs">
            <button 
    class="px-4 py-2 bg-blue-400 bg-text-white font-semibold rounded-full shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 status-toggle"
    data-id="<?php echo $row['college_course_id']; ?>"
    onclick="openAllocationPopup(<?php echo $row['college_course_id']; ?>, <?php echo $row['course_id']; ?>, '<?php echo $row['course_name']; ?>')">
    Allocate
</button>

</td>

            <!-- Display allocation status -->



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

    <!-- Popup HTML: Allocation Form -->
    <div id="allocationPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-[800px]">
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 text-center">Course Allocation to Sub-admin</h2>

        <form id="allocationForm" enctype="multipart/form-data">
            <input type="hidden" id="college_course_id" name="college_course_id">
            <input type="hidden" id="course_id" name="course_id">
            <input type="hidden" id="course_name" name="course_name">

            <div class="flex space-x-6">
                <div class="w-1/2">
                    <div class="mb-4">
                        <label class="block text-gray-700 dark:text-gray-200">Select Sub-admin</label>
                        <select name="sub_admin_id" id="sub_admin_id" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-md">
                            <option value="">Select Sub-admin</option>
                            <?php foreach ($subAdmins as $subAdmin) { ?>
                                <option value="<?php echo $subAdmin['user_id']; ?>" 
                                        data-fullname="<?php echo $subAdmin['full_name']; ?>"
                                        data-email="<?php echo $subAdmin['email']; ?>"
                                        data-phone="<?php echo $subAdmin['phone_number']; ?>">
                                    <?php echo $subAdmin['full_name']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <!-- Sub-admin's Personal Data -->
                    <div class="mb-4">
                        <label class="block text-gray-700 dark:text-gray-200">Full Name</label>
                        <input type="text" id="sub_admin_full_name" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-md" readonly>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 dark:text-gray-200">Email ID</label>
                        <input type="email" id="sub_admin_email" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-md" readonly>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 dark:text-gray-200">Phone Number</label>
                        <input type="text" id="sub_admin_phone" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-md" readonly>
                    </div>
                </div>

                <div class="w-1/2">
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

                    <div class="mb-4">
                        <label class="block text-gray-700 dark:text-gray-200">Attachment</label>
                        <input type="file" name="attachment" id="attachment" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-md">
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-center space-x-3">
                <button type="button" id="cancelAllocation" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">
                    Cancel
                </button>
                <button type="submit" id="confirmAllocation" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                    Allocate
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Success Popup -->
<div id="statusSuccessPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 text-center">Course Allocated Successfully</h2>
        <p class="text-gray-600 dark:text-gray-300 text-center">
            The course has been successfully allocated to the sub-admin.
        </p>
        <p class="text-gray-600 dark:text-gray-300 text-center mt-2" id="statusSuccessMessage"></p> <!-- Success message -->
        <div class="mt-6 flex justify-center">
            <button id="closeStatusSuccess" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                OK
            </button>
        </div>
    </div>
</div>

<script>
    // Success Popup Logic
    function showSuccessPopup(message) {
        document.getElementById('statusSuccessMessage').textContent = message;
        document.getElementById('statusSuccessPopup').classList.remove('hidden');
    }

    // Close popup and redirect after allocation
    document.getElementById('closeStatusSuccess').addEventListener('click', function() {
        document.getElementById('statusSuccessPopup').classList.add('hidden');
        window.location.href = "course_allocation_list.php?message=Course+allocated+successfully&message_type=success";
    });

    // Call this function after successful allocation (AJAX response)
    function handleAllocationSuccess() {
        showSuccessPopup('Course allocated successfully!');
    }
</script>

<script>
// JavaScript to open the popup with selected course data
function openAllocationPopup(collegeCourseId, courseId, courseName) {
    document.getElementById('college_course_id').value = collegeCourseId; // Corrected variable name
    document.getElementById('course_id').value = courseId;
    document.getElementById('course_name_display').value = courseName;
    document.getElementById('allocationPopup').classList.remove('hidden');
}

// JavaScript to handle form submission (allocation)
document.getElementById('allocationForm').addEventListener('submit', function(event) {
    event.preventDefault();
    const collegeCourseId = document.getElementById('college_course_id').value; 
    const courseId = document.getElementById('course_id').value;
    const subAdminId = document.getElementById('sub_admin_id').value;
    const subject = document.getElementById('subject').value;
    const body = document.getElementById('body').value;
    const attachment = document.getElementById('attachment').files[0];

    if (!subAdminId) {
        alert("Please select a Sub-admin.");
        return;
    }
    if (!subject) {
        alert("Please enter a Subject.");
        return;
    }
    if (!body) {
        alert("Please enter the Body.");
        return;
    }

    const formData = new FormData();
    formData.append("college_course_id", collegeCourseId);  // Corrected variable name
    formData.append("course_id", courseId);
    formData.append("sub_admin_id", subAdminId);
    formData.append("subject", subject);
    formData.append("body", body);
    if (attachment) {
        formData.append("attachment", attachment);
    }

    // AJAX to handle the allocation insertion
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "allocate_actual_course.php", true);

    xhr.onload = function() {
        if (xhr.status === 200) {
            const response = xhr.responseText;
            if (response === 'success') {
                handleAllocationSuccess();  // Trigger Success Popup
                document.getElementById('allocationPopup').classList.add('hidden');
            } else {
                alert("Failed to allocate the course. Try again.");
            }
        }
    };

    xhr.send(formData);
});

// Close the popup if Cancel is clicked
document.getElementById('cancelAllocation').addEventListener('click', function() {
    document.getElementById('allocationPopup').classList.add('hidden');
});

// Update sub-admin personal data when selection changes
document.getElementById('sub_admin_id').addEventListener('change', function() {
    var selectedOption = this.options[this.selectedIndex];
    var fullName = selectedOption.getAttribute('data-fullname');
    var email = selectedOption.getAttribute('data-email');
    var phone = selectedOption.getAttribute('data-phone');

    // Update the read-only fields with the selected sub-admin's personal data
    document.getElementById('sub_admin_full_name').value = fullName;
    document.getElementById('sub_admin_email').value = email;
    document.getElementById('sub_admin_phone').value = phone;
});
</script>






</div>

<?php $conn->close(); ?>

</body>
</html>


















