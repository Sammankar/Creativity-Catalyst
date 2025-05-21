<?php
include "header.php";
include "connection.php";

// Get calendar ID from query
$calendar_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Pagination
$limit = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$searchCondition = ($search !== "") ? "AND (u.full_name LIKE '%$search%' OR u.enrollment_number LIKE '%$search%')" : "";

// Get calendar info
$calendarQuery = "SELECT ac.*, c.name as course_name FROM academic_calendar ac
                  LEFT JOIN courses c ON ac.course_id = c.course_id
                  WHERE ac.id = ?";
$stmt = $conn->prepare($calendarQuery);
$stmt->bind_param("i", $calendar_id);
$stmt->execute();
$calendarResult = $stmt->get_result();
$calendar = $calendarResult->fetch_assoc();
$stmt->close();

$semester = $calendar['semester'];
$academic_year = $calendar['academic_year'];
$course_id = $calendar['course_id'];

// Fetch eligible students
$query = "SELECT u.user_id, u.full_name, sa.current_semester, c.name AS course_name, ac.start_date, ac.end_date
          FROM users u
          JOIN student_academics sa ON sa.user_id = u.user_id
          JOIN courses c ON sa.course_id = c.course_id
          JOIN academic_calendar ac ON ac.id = ?
          WHERE sa.course_id = ? 
            AND sa.current_semester = ?
            AND sa.current_academic_year = ?
            $searchCondition
          LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($query);
$stmt->bind_param("iiis", $calendar_id, $course_id, $semester, $academic_year);
$stmt->execute();
$result = $stmt->get_result();

// Total for pagination
$countQuery = "SELECT COUNT(*) AS total FROM users u
               JOIN student_academics sa ON sa.user_id = u.user_id
               WHERE sa.course_id = ? 
               AND sa.current_semester = ?
               AND sa.current_academic_year = ?
               $searchCondition";

$stmt = $conn->prepare($countQuery);
$stmt->bind_param("iis", $course_id, $semester, $academic_year);
$stmt->execute();
$countResult = $stmt->get_result();
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);
$stmt->close();

// Count eligible students for this calendar
$eligibleQuery = "SELECT COUNT(*) AS total FROM student_academics
                  WHERE course_id = ? 
                    AND current_semester = ? 
                    AND current_academic_year = ?";
$stmt = $conn->prepare($eligibleQuery);
$stmt->bind_param("iis", $course_id, $semester, $academic_year);
$stmt->execute();
$eligibleResult = $stmt->get_result();
$totalEligible = $eligibleResult->fetch_assoc()['total'];
$stmt->close();

// Count upgraded students from student_semester_result
$upgradedQuery = "SELECT COUNT(*) AS total FROM student_semester_result
                  WHERE academic_calendar_id = ? 
                    AND course_id = ? 
                    AND semester = ? 
                    AND academic_year = ?";
$stmt = $conn->prepare($upgradedQuery);
$stmt->bind_param("iiis", $calendar_id, $course_id, $semester, $academic_year);
$stmt->execute();
$upgradedResult = $stmt->get_result();
$totalUpgraded = $upgradedResult->fetch_assoc()['total'];
$stmt->close();

// Check if upgrade log entry exists
$upgrade_check_query = "SELECT COUNT(*) FROM academic_semester_upgrade_logs WHERE previous_calendar_id = ?";
$upgrade_check_stmt = $conn->prepare($upgrade_check_query);
$upgrade_check_stmt->bind_param("i", $calendar_id); // current page's calendar_id
$upgrade_check_stmt->execute();
$upgrade_check_stmt->bind_result($upgrade_count);
$upgrade_check_stmt->fetch();
$upgrade_done = ($upgrade_count > 0);
$upgrade_check_stmt->close();
?>

<main class="h-full overflow-y-auto">
          <div class="container px-6 mx-auto grid">
            <h2
              class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200"
            >
            Students Eligible for Semester Upgrade (<?php echo htmlspecialchars($calendar['course_name']) ?> - Sem <?php echo $semester; ?>) And  Academic Year: <?php echo htmlspecialchars($calendar['academic_year']); ?>
            </h2>
            <!-- CTA -->

            <!-- Cards -->
            <div class="grid gap-6 mb-8 md:grid-cols-2 xl:grid-cols-4">
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
                    Total Count
                  </p>
                  <p
                    class="text-lg font-semibold text-gray-700 dark:text-gray-200"
                  >
                  <?php echo $totalRecords; ?>
                  </p>
                </div>
              </div>
              <!-- Card -->
              <!-- Card -->
            </div>
<h4 class="mb-4 text-lg font-semibold text-gray-600 dark:text-gray-300"> 
    
</h4>

<div class="w-full mb-8 overflow-hidden rounded-lg shadow-xs">

    <!-- Header + Search -->
    <div class="flex justify-between items-center mb-4 flex-wrap gap-4">
    <!-- Left Side -->
    <div class="flex items-center gap-6">
    <div class="mt-4 flex gap-4">
    <?php if (!$upgrade_done): ?>
        <form action="process_upgrade.php" method="POST" onsubmit="return confirm('Are you sure you want to upgrade all eligible students?');">
            <input type="hidden" name="calendar_id" value="<?php echo $calendar_id; ?>">
            <button type="submit" class="px-6 py-2 bg-blue-500 text-white font-bold rounded-md shadow-md hover:bg-blue-600">
                Upgrade All Students Semester
            </button>
        </form>
    <?php else: ?>
        <button class="px-6 py-2 bg-blue-500 text-white font-bold rounded-md shadow-md hover:bg-blue-600 cursor-not-allowed" disabled>
            Semester Already Upgraded
        </button>
        <a href="export_upgrade_pdf.php?calendar_id=<?php echo $calendar_id; ?>" 
           class="px-6 py-2 bg-blue-500 text-white font-bold rounded-md shadow-md hover:bg-blue-600">
            Export Report (PDF)
        </a>
    <?php endif; ?>
</div>


    <button 
        onclick="window.location.href='dashboard.php';"
        class="px-6 py-2 bg-blue-500 text-white font-bold rounded-md shadow-md hover:bg-blue-600">
        Back To Dashboard
    </button>
</div>



    <!-- Right Side (Search Bar) -->
    <form method="GET" class="flex items-center space-x-2">
        <input type="hidden" name="calendar_id" value="<?php echo $calendar_id; ?>">
        <input 
            type="text" 
            name="search" 
            placeholder="Search student..." 
            value="<?php echo htmlspecialchars($search); ?>" 
            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
        <button 
            type="submit" 
            class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-md shadow-md hover:bg-blue-600">
            Search
        </button>
    </form>
</div>


    <!-- Table -->
    <table class="w-full whitespace-no-wrap">
        <thead>
            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
            <th class="px-4 py-3">SR NO.</th>
        <th class="px-4 py-3">FULL NAME</th>
        <th class="px-4 py-3">COURSE</th>
        <th class="px-4 py-3">CURRENT SEMESTER</th>
        <th class="px-4 py-3">START DATE</th>
        <th class="px-4 py-3">END DATE</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y">
            <?php if ($result->num_rows > 0): ?>
                <?php $sr = $offset + 1; ?>
<?php while ($row = $result->fetch_assoc()): ?>
    <tr class="text-gray-700">
        <td class="px-4 py-3 text-xs"><?php echo $sr++; ?></td>
        <td class="px-4 py-3 text-xs"><?php echo htmlspecialchars($row['full_name']); ?></td>
        <td class="px-4 py-3 text-xs"><?php echo htmlspecialchars($row['course_name']); ?></td>
        <td class="px-4 py-3 text-xs"><?php echo $row['current_semester']; ?></td>
        <td class="px-4 py-3 text-xs"><?php echo date('d M Y', strtotime($row['start_date'])); ?></td>
        <td class="px-4 py-3 text-xs"><?php echo date('d M Y', strtotime($row['end_date'])); ?></td>
    </tr>
<?php endwhile; ?>

            <?php else: ?>
                <tr><td colspan="5" class="px-4 py-3 text-center text-gray-500">No students found</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="flex justify-end mt-4">
        <nav class="flex items-center space-x-2">
            <?php if ($page > 1): ?>
                <a href="?calendar_id=<?php echo $calendar_id; ?>&page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>"
                   class="px-4 py-2 border border-gray-300 text-gray-600 rounded-md hover:bg-blue-500 hover:text-white">Back</a>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="?calendar_id=<?php echo $calendar_id; ?>&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"
                   class="px-4 py-2 border border-gray-300 rounded-md hover:bg-blue-500 hover:text-white <?php echo $i == $page ? 'bg-blue-500 text-white' : 'text-gray-600'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="?calendar_id=<?php echo $calendar_id; ?>&page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>"
                   class="px-4 py-2 border border-gray-300 text-gray-600 rounded-md hover:bg-blue-500 hover:text-white">Next</a>
            <?php endif; ?>
        </nav>
    </div>
</div>

<script>
function upgradeAllStudents(calendarId) {
    if (confirm('Are you sure you want to upgrade all eligible students for this calendar?')) {
        window.location.href = `process_upgrade.php?calendar_id=${calendarId}`;
    }
}
</script>
