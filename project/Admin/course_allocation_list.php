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

// Fetch total records for pagination
$totalQuery = "SELECT COUNT(*) AS total 
               FROM college_courses cc 
               JOIN courses c ON cc.course_id = c.course_id 
               WHERE cc.college_id = '$college_id' 
               AND cc.college_course_status != 0
               $searchQuery";
$totalResult = mysqli_query($conn, $totalQuery);
$totalRow = mysqli_fetch_assoc($totalResult);
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $limit);

// Fetch all courses (allocated and non-allocated)
$query = "
    SELECT 
        c.course_id, 
        c.name AS course_name, 
        c.total_semesters, 
        c.duration, 
        IFNULL(u.full_name, 'Not Allocated') AS allocated_sub_admin,
        CASE 
            WHEN cc.sub_admin_id IS NOT NULL THEN 'Allocated'
            ELSE 'Not Allocated'
        END AS allocation_status,
        cc.college_course_id
    FROM college_courses cc
    JOIN courses c ON cc.course_id = c.course_id
    LEFT JOIN users u ON cc.sub_admin_id = u.user_id
    WHERE cc.college_id = '$college_id' 
    AND cc.college_course_status != 0  -- Exclude courses with status 0 (inactive courses)
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
            <h2
              class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200"
            >
              Courses
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
  <path d="M10 2L2 6l8 4 8-4-8-4zM2 9l8 4 8-4v2l-8 4-8-4V9zM2 14v2h16v-2H2z"></path>
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
                  <?php echo $totalRecords;?>
                  </p>
                </div>
              </div>
              <!-- Card -->
              <!-- Card -->
            </div>

            <h4 class="mb-4 text-lg font-semibold text-gray-600 dark:text-gray-300">
    Courses  List
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
            onclick="location.href='allocate_course.php';" >
            Allocate Course
        </button>

        <button id="requestCourseBtn" 
            class="bg-blue-500 text-white px-4 py-2 rounded" 
            onclick="location.href='deallocate_course.php';">
            Deallocate Course
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






</div>

<?php $conn->close(); ?>

</body>
</html>


















