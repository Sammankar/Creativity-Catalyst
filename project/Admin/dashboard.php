<?php
include "header.php";
include "connection.php";

// Pagination setup
$limit = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : "";
$searchCondition = "";

// Step 1: Get admin's college_id using session user_id
$admin_id = $_SESSION['user_id'];
$collegeQuery = "SELECT college_id FROM users WHERE user_id = ?";
$stmt = $conn->prepare($collegeQuery);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$collegeResult = $stmt->get_result();
$collegeRow = $collegeResult->fetch_assoc();
$college_id = $collegeRow['college_id'];
$stmt->close();

// Step 2: Get course_ids of the college
$course_ids = [];
$courseQuery = "SELECT course_id FROM college_courses WHERE college_id = ? AND college_course_status = 1";
$stmt = $conn->prepare($courseQuery);
$stmt->bind_param("i", $college_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $course_ids[] = $row['course_id'];
}
$stmt->close();

// Avoid empty course_ids issue
if (empty($course_ids)) {
    $course_ids[] = -1; // So it returns no result safely
}
$course_id_list = implode(",", array_map('intval', $course_ids));

// Search condition
if ($search) {
    $searchCondition = "AND (ac.id LIKE '%$search%' OR ac.semester LIKE '%$search%' OR ac.academic_year LIKE '%$search%')";
}

// Main query to fetch filtered academic calendar data
$query = "
    SELECT 
        ac.id, 
        ac.course_id, 
        c.name AS course_name, 
        ac.semester, 
        ac.academic_year, 
        ac.start_date, 
        ac.end_date, 
        ac.is_editable, 
        u.full_name AS created_by_name, 
        ac.created_by, 
        ac.created_at, 
        ac.updated_at,
        ac.status
    FROM 
        academic_calendar ac
    LEFT JOIN 
        courses c ON ac.course_id = c.course_id
    LEFT JOIN 
        users u ON ac.created_by = u.user_id
    WHERE 
        ac.course_id IN ($course_id_list) $searchCondition
    LIMIT $limit OFFSET $offset
";
$result = $conn->query($query);

// Fetch total records for pagination
$totalQuery = "
    SELECT COUNT(*) AS total 
    FROM academic_calendar ac 
    WHERE ac.course_id IN ($course_id_list) $searchCondition
";
$totalResult = $conn->query($totalQuery);
$totalRow = $totalResult->fetch_assoc();
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $limit);

// Academic calendar statistics
$totalacademicsQuery = "SELECT COUNT(*) AS total FROM academic_calendar WHERE course_id IN ($course_id_list)";
$activeacademicsQuery = "SELECT COUNT(*) AS active FROM academic_calendar WHERE is_editable = 1 AND course_id IN ($course_id_list)";
$deactiveacademicsQuery = "SELECT COUNT(*) AS deactive FROM academic_calendar WHERE is_editable = 0 AND course_id IN ($course_id_list)";

$totalacademicsResult = $conn->query($totalacademicsQuery);
$activeacademicsResult = $conn->query($activeacademicsQuery);
$deactiveacademicsResult = $conn->query($deactiveacademicsQuery);

$totalacademics = $totalacademicsResult->fetch_assoc()['total'];
$activeacademics = $activeacademicsResult->fetch_assoc()['active'];
$deactiveacademics = $deactiveacademicsResult->fetch_assoc()['deactive'];

// Flash message
$message = isset($_SESSION['message']) ? $_SESSION['message'] : "";
$message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : "";
unset($_SESSION['message'], $_SESSION['message_type']);
if(isset($_SESSION['user_id'])){
    // Retrieve the admin's user_id from the session
    $admin_user_id = $_SESSION['user_id'];
    
    // Step 1: Get the college_id and admin's full name for the logged-in admin
    $query = "SELECT college_id, full_name FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $admin_user_id);
    $stmt->execute();
    $stmt->bind_result($college_id, $admin_full_name);
    $stmt->fetch();
    $stmt->close();

    // Check if a valid college_id is retrieved
    if($college_id) {
        // Step 2: Count the total number of courses for that college_id
        $query_courses = "SELECT COUNT(college_course_id) AS total_courses FROM college_courses WHERE college_id = ?";
        $stmt_courses = $conn->prepare($query_courses);
        $stmt_courses->bind_param("i", $college_id);
        $stmt_courses->execute();
        $stmt_courses->bind_result($total_courses);
        $stmt_courses->fetch();
        $stmt_courses->close();

        // Step 3: Count the total number of sub-admins (role 3), teachers (role 4), and students (role 5)
        $query_sub_admins = "SELECT COUNT(*) FROM users WHERE role = 3 AND college_id = ?";
        $query_teachers = "SELECT COUNT(*) FROM users WHERE role = 4 AND college_id = ?";
        $query_students = "SELECT COUNT(*) FROM users WHERE role = 5 AND college_id = ?";

        // Prepare and execute queries
        $stmt_sub_admins = $conn->prepare($query_sub_admins);
        $stmt_sub_admins->bind_param("i", $college_id);
        $stmt_sub_admins->execute();
        $stmt_sub_admins->bind_result($total_sub_admins);
        $stmt_sub_admins->fetch();
        $stmt_sub_admins->close();

        $stmt_teachers = $conn->prepare($query_teachers);
        $stmt_teachers->bind_param("i", $college_id);
        $stmt_teachers->execute();
        $stmt_teachers->bind_result($total_teachers);
        $stmt_teachers->fetch();
        $stmt_teachers->close();

        $stmt_students = $conn->prepare($query_students);
        $stmt_students->bind_param("i", $college_id);
        $stmt_students->execute();
        $stmt_students->bind_result($total_students);
        $stmt_students->fetch();
        $stmt_students->close();

    } else {
        echo "Admin does not belong to any college.";
    }

    // Step 4: Fetch college details (including logo)
    $query_college = "SELECT name, address, contact_number, college_logo FROM colleges WHERE college_id = ?";
    $stmt_college = $conn->prepare($query_college);
    $stmt_college->bind_param("i", $college_id);
    $stmt_college->execute();
    $stmt_college->bind_result($college_name, $college_address, $college_contact, $college_logo);
    $stmt_college->fetch();
    $stmt_college->close();

    // Check if the logo exists and set a default if not
    $college_logo_path = "../Admin/" . htmlspecialchars($college_logo);
    $default_logo = "default-logo.png"; // Default logo if no logo exists
    $logo_src = (file_exists($college_logo_path) && !empty($college_logo)) ? $college_logo_path : "../Admin/" . $default_logo;

    // Close the database connection
    $conn->close();
} else {
    echo "You are not logged in.";
}

?>

<main class="h-full overflow-y-auto">
          <div class="container px-6 mx-auto grid">
            <h2
              class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200"
            >
              Dashboard
            </h2>
            <!-- CTA -->

            <!-- Cards -->
            <div class="grid gap-6 mb-8 md:grid-cols-2 xl:grid-cols-4">
              <!-- Card -->
              <div
                class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800"
              >
                <div
                  class="p-3 mr-4 text-blue-500 bg-blue-100 rounded-full dark:text-blue-100 dark:bg-blue-500"
                >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
  <path fill-rule="evenodd" d="M10 2a8 8 0 110 16 8 8 0 010-16zm3.293 6.293a1 1 0 10-1.414-1.414L10 8.586 8.121 6.707a1 1 0 10-1.414 1.414L8.586 10l-1.879 1.879a1 1 0 101.414 1.414L10 11.414l1.879 1.879a1 1 0 101.414-1.414L11.414 10l1.879-1.879z" clip-rule="evenodd"></path>
</svg>

                </div>
                <div>
                  <p
                    class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400"
                  >
                    Total Courses
                  </p>
                  <p
                    class="text-lg font-semibold text-gray-700 dark:text-gray-200"
                  >
                  <?php echo $total_courses; ?>
                  </p>
                </div>
              </div>
              <div
                class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800"
              >
                <div
                  class="p-3 mr-4 text-blue-500 bg-blue-100 rounded-full dark:text-blue-100 dark:bg-blue-500"
                >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
  <path fill-rule="evenodd" d="M10 2a8 8 0 110 16 8 8 0 010-16zm3.293 6.293a1 1 0 10-1.414-1.414L10 8.586 8.121 6.707a1 1 0 10-1.414 1.414L8.586 10l-1.879 1.879a1 1 0 101.414 1.414L10 11.414l1.879 1.879a1 1 0 101.414-1.414L11.414 10l1.879-1.879z" clip-rule="evenodd"></path>
</svg>

                </div>
                <div>
                  <p
                    class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400"
                  >
                    Total Sub-Admins
                  </p>
                  <p
                    class="text-lg font-semibold text-gray-700 dark:text-gray-200"
                  >
                  <?php echo $total_sub_admins; ?>
                  </p>
                </div>
              </div>
              <div
                class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800"
              >
                <div
                  class="p-3 mr-4 text-blue-500 bg-blue-100 rounded-full dark:text-blue-100 dark:bg-blue-500"
                >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
  <path fill-rule="evenodd" d="M10 2a8 8 0 110 16 8 8 0 010-16zm3.293 6.293a1 1 0 10-1.414-1.414L10 8.586 8.121 6.707a1 1 0 10-1.414 1.414L8.586 10l-1.879 1.879a1 1 0 101.414 1.414L10 11.414l1.879 1.879a1 1 0 101.414-1.414L11.414 10l1.879-1.879z" clip-rule="evenodd"></path>
</svg>

                </div>
                <div>
                  <p
                    class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400"
                  >
                    Total Teachers
                  </p>
                  <p
                    class="text-lg font-semibold text-gray-700 dark:text-gray-200"
                  >
                  <?php echo $total_teachers; ?>
                  </p>
                </div>
              </div>
              <div
                class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800"
              >
                <div
                  class="p-3 mr-4 text-blue-500 bg-blue-100 rounded-full dark:text-blue-100 dark:bg-blue-500"
                >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
  <path fill-rule="evenodd" d="M10 2a8 8 0 110 16 8 8 0 010-16zm3.293 6.293a1 1 0 10-1.414-1.414L10 8.586 8.121 6.707a1 1 0 10-1.414 1.414L8.586 10l-1.879 1.879a1 1 0 101.414 1.414L10 11.414l1.879 1.879a1 1 0 101.414-1.414L11.414 10l1.879-1.879z" clip-rule="evenodd"></path>
</svg>

                </div>
                <div>
                  <p
                    class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400"
                  >
                    Total Students
                  </p>
                  <p
                    class="text-lg font-semibold text-gray-700 dark:text-gray-200"
                  >
                  <?php echo $total_students; ?>
                  </p>
                </div>
              </div>
              
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
                    Total Scheduled
                  </p>
                  <p
                    class="text-lg font-semibold text-gray-700 dark:text-gray-200"
                  >
                  <?php echo $totalacademics; ?>
                  </p>
                </div>
              </div>
              <!-- Card -->
              <div
                class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800"
              >
                <div
                  class="p-3 mr-4 text-green-500 bg-green-100 rounded-full dark:text-green-100 dark:bg-green-500"
                >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
  <path fill-rule="evenodd" d="M10 2a8 8 0 110 16 8 8 0 010-16zm-1 9.293l3.293-3.293a1 1 0 011.414 1.414l-4 4a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L9 11.293z" clip-rule="evenodd"></path>
</svg>

                </div>
                <div>
                  <p
                    class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400"
                  >
                    Ongoing Schedule
                  </p>
                  <p
                    class="text-lg font-semibold text-gray-700 dark:text-gray-200"
                  >
                  <?php echo $activeacademics; ?>
                  </p>
                </div>
              </div>
              <!-- Card -->
              <div
                class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800"
              >
                <div
                  class="p-3 mr-4 text-blue-500 bg-blue-100 rounded-full dark:text-blue-100 dark:bg-blue-500"
                >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
  <path fill-rule="evenodd" d="M10 2a8 8 0 110 16 8 8 0 010-16zm3.293 6.293a1 1 0 10-1.414-1.414L10 8.586 8.121 6.707a1 1 0 10-1.414 1.414L8.586 10l-1.879 1.879a1 1 0 101.414 1.414L10 11.414l1.879 1.879a1 1 0 101.414-1.414L11.414 10l1.879-1.879z" clip-rule="evenodd"></path>
</svg>

                </div>
                <div>
                  <p
                    class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400"
                  >
                    Completed Schedule
                  </p>
                  <p
                    class="text-lg font-semibold text-gray-700 dark:text-gray-200"
                  >
                  <?php echo $deactiveacademics; ?>
                  </p>
                </div>
              </div>
              <!-- Card -->
            </div>

            <h4 class="mb-4 text-lg font-semibold text-gray-600 dark:text-gray-300">
    Academic Schedule List
</h4>
<div class="w-full mb-8 overflow-hidden rounded-lg shadow-xs">
    
    <!-- Table Wrapper -->
    <div class="w-full overflow-x-auto">
        <!-- Search Form -->
        <div class="flex justify-between items-center mb-4">
    <!-- Left: Add academic Button -->
    <a 
        
        class="px-4 py-2"
    >
       
    </a>

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

        <!-- academics Table -->
        <table class="w-full whitespace-no-wrap">
    <thead>
        <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800">
            <th class="px-4 py-3">SR NO.</th>
            <th class="px-4 py-3">COURSE NAME</th>
            <th class="px-4 py-3">SEMESTER</th>
            <th class="px-4 py-3">ACADEMIC YEAR</th>
            <th class="px-4 py-3">START DATE</th>
            <th class="px-4 py-3">END DATE</th>
            <th class="px-4 py-3">ACADEMIC STATUS</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
        <?php if ($result->num_rows > 0): ?>
            <?php $srNo = $offset + 1; ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr class="text-gray-700 dark:text-gray-400">
                    <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400"><?php echo $srNo++; ?></td>
                    <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($row['course_name']); ?></td>
                    <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400"><?php echo $row['semester']; ?></td>
                    <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400"><?php echo $row['academic_year']; ?></td>
                    <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400">
    <?php echo date("d F Y", strtotime($row['start_date'])); ?>
</td>
<td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400">
    <?php echo date("d F Y", strtotime($row['end_date'])); ?>
</td>

                    <td class="px-4 py-3 font-semibold text-xs">
                    <button 
    class="px-4 py-2 font-semibold rounded-full shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2"
    data-id="<?php echo $row['id']; ?>"
    data-status="<?php echo $row['status']; ?>"
    style="background-color: <?php echo $row['status'] == 0 ? '#ef4444' : ($row['is_editable'] == 1 ? '#10b981' : '#f59e0b'); ?>; color: white; <?php echo $row['status'] == 0 ? 'pointer-events: none; opacity: 0.6;' : ''; ?>">
    <?php 
        // If status is 0, display "Suspended"
        if ($row['status'] == 0) {
            echo 'Suspended';
        } else {
            // Otherwise, display "Ongoing" or "Completed"
            echo $row['is_editable'] == 1 ? 'Ongoing' : 'Completed';
        }
    ?>
</button>




                    </td>
                    <td class="px-4 py-3 font-semibold text-xs">
              <button 
                  class="px-4 py-2 text-black font-semibold rounded-full shadow-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 view-academic"
                  style="background-color: #ffff;"
                  data-id="<?php echo $row['id']; ?>">
                  View
              </button>

                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">No academics found</td>
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

    <div id="viewacademicModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-lg shadow-lg w-96 p-6">
        <h2 class="text-lg font-semibold mb-4">Academic Details</h2>

        <div class="mb-2">
            <label class="font-semibold">Course Name: </label>
            <input type="text" id="coursename" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">Semester: </label>
            <input type="text" id="totalSemesters" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">Academic Year: </label>
            <input type="text" id="academicYear" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">Start Date: </label>
            <input type="text" id="startDate" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">End Date: </label>
            <input type="text" id="endDate" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-4">
            <label class="font-semibold">Created By: </label>
            <input type="text" id="createdBy" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-4">
            <label class="font-semibold">Status: </label>
            <input type="text" id="status" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <button id="closeModal" class="w-full bg-blue-500 text-white py-2 rounded font-semibold">OK</button>
    </div>
</div>

    <script>
document.addEventListener("DOMContentLoaded", () => {
    const viewButtons = document.querySelectorAll(".view-academic");
    const modal = document.getElementById("viewacademicModal");
    const closeModal = document.getElementById("closeModal");

    // Event listener for each "View" button
    viewButtons.forEach(button => {
        button.addEventListener("click", () => {
            const academicId = button.dataset.id; // Get the academic ID

            // Fetch the data from the server using the academic ID
            fetch("fetch_academics_details.php?id=" + academicId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Populate the modal fields with fetched data
                        document.getElementById("coursename").value = data.academic.course_name;
                        document.getElementById("totalSemesters").value = data.academic.semester;
                        document.getElementById("academicYear").value = data.academic.academic_year;
                        document.getElementById("startDate").value = new Date(data.academic.start_date).toLocaleDateString();
                        document.getElementById("endDate").value = new Date(data.academic.end_date).toLocaleDateString();
                        document.getElementById("createdBy").value = data.academic.created_by_name;
                        document.getElementById("status").value = data.academic.is_editable ? 'Ongoing' : 'Completed';
                        
                        // Show the modal
                        modal.classList.remove("hidden");
                    } else {
                        alert("Failed to fetch academic details.");
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("An error occurred.");
                });
        });
    });

    // Close the modal
    closeModal.addEventListener("click", () => {
        modal.classList.add("hidden");
    });
});

</script>

    <!-- Success Notification Popup -->



</div>




</body>
</html>
