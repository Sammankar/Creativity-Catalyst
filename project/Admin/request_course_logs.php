<?php 
include "header.php";
include "connection.php";

// Pagination setup
$limit = 5; // Number of rows per page
$page = isset($_GET['page']) ? intval($_GET['page']) : 1; // Current page
$offset = ($page - 1) * $limit; // Offset for SQL query

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : "";
$searchQuery = $search ? "AND (c.name LIKE '%$search%' OR u.full_name LIKE '%$search%')" : "";

// Fetch total records for pagination
$totalQuery = "SELECT COUNT(*) AS total FROM course_requests cr
LEFT JOIN colleges c ON cr.college_id = c.college_id
LEFT JOIN users u ON cr.admin_id = u.user_id
WHERE 1 $searchQuery";
$totalResult = $conn->query($totalQuery);
$totalRow = $totalResult->fetch_assoc();
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $limit);

// Fetch paginated course request logs
$query = "
    SELECT cr.request_id, cr.college_id, cr.admin_id, cr.requested_course_id, cr.status, cr.created_at, cr.status_updated_at,
           c.name AS college_name, 
           u.full_name AS admin_name,
           co.name  -- Fetch the course name
    FROM course_requests cr
    LEFT JOIN colleges c ON cr.college_id = c.college_id
    LEFT JOIN users u ON cr.admin_id = u.user_id
    LEFT JOIN courses co ON cr.requested_course_id = co.course_id  -- Join with courses table
    WHERE 1 $searchQuery
    ORDER BY cr.created_at DESC  -- Sort by created_at descending
    LIMIT $limit OFFSET $offset";

$result = $conn->query($query);

$user_id = $_SESSION['user_id']; // Get logged-in user's ID

// Fetch the college_id of the logged-in user
$query1 = "SELECT college_id FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query1);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result1 = $stmt->get_result();
$userData = $result1->fetch_assoc();

$college_id = $userData['college_id']; // Get the college_id

// Fetch course request logs for this college
$query2 = "
    SELECT 
        COUNT(*) AS total_logs,
        SUM(CASE WHEN cr.status = 0 THEN 1 ELSE 0 END) AS pending_logs,
        SUM(CASE WHEN cr.status = 1 THEN 1 ELSE 0 END) AS approved_logs,
        SUM(CASE WHEN cr.status = 2 THEN 1 ELSE 0 END) AS rejected_logs
    FROM course_requests cr
    WHERE cr.college_id = ?";

$stmt = $conn->prepare($query2);
$stmt->bind_param("i", $college_id);
$stmt->execute();
$result2 = $stmt->get_result();
$logData = $result2->fetch_assoc();
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
                    Total Course Logs
                  </p>
                  <p
                    class="text-lg font-semibold text-gray-700 dark:text-gray-200"
                  >
                  <?php echo $logData['total_logs']; ?>
                  </p>
                </div>
              </div>
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
                    Pending Course Logs
                  </p>
                  <p
                    class="text-lg font-semibold text-gray-700 dark:text-gray-200"
                  >
                  <?php echo $logData['pending_logs']; ?>
                  </p>
                </div>
              </div>
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
                    Approve Course Logs
                  </p>
                  <p
                    class="text-lg font-semibold text-gray-700 dark:text-gray-200"
                  >
                  <?php echo $logData['approved_logs']; ?>
                  </p>
                </div>
              </div>
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
                    Rejected Course Logs
                  </p>
                  <p
                    class="text-lg font-semibold text-gray-700 dark:text-gray-200"
                  >
                  <?php echo $logData['rejected_logs']; ?>
                  </p>
                </div>
              </div>
            </div>
              <!-- Card -->
              <!-- Card -->
            </div>

            <h4 class="mb-4 text-lg font-semibold text-gray-600 dark:text-gray-300">
    Requested Course Logs
</h4>
<div class="w-full mb-8 overflow-hidden rounded-lg shadow-xs">
    
    <!-- Table Wrapper -->
    <div class="w-full overflow-x-auto">
        <!-- Search Form -->
        <div class="flex justify-between items-center mb-4">
    <!-- Left Side: Add Course & Request Course Buttons -->
    <div class="flex space-x-4">
    <button
            class="bg-blue-500 text-white px-4 py-2 rounded" 
            onclick="location.href='course_selection.php';">
            Back To Course Selection
        </button>
    </div>

    <!-- Center: Display Messages -->
    <div class="flex-grow text-center">
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
            <th class="px-4 py-3">STATUS</th>
            <th class="px-4 py-3">REQUESTED AT</th>
            <th class="px-4 py-3">UPDATED AT</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
        <?php if ($result->num_rows > 0): ?>
            <?php $srNo = $offset + 1; ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400"><?php echo $srNo++; ?></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($row['college_name']); ?></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($row['admin_name']); ?></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($row['name']); ?></td>
            <td class="px-4 py-3">
                            <button 
                                class="px-4 py-2 font-semibold rounded-full shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2"
                                style="background-color: <?php echo ($row['status'] == 1) ? '#10b981' : (($row['status'] == 0) ? '#facc10' : '#ef4444'); ?>; color: white;">
                                <?php echo ($row['status'] == 1) ? 'Approved' : (($row['status'] == 0) ? 'Pending' : 'Rejected'); ?>
                            </button>

                            </td>
                            <td class="px-4 py-3">
    <?php echo date('d M Y, g:i A', strtotime($row['created_at'])); ?>
</td>
<td class="px-4 py-3">
    <?php echo date('d M Y, g:i A', strtotime($row['status_updated_at'])); ?>
</td>

                    
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">No Requested Course log</td>
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

