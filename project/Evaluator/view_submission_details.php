<?php
include "header.php";
include "connection.php";

// Get selected competition ID
$competition_id = isset($_GET['competition_id']) ? intval($_GET['competition_id']) : 0;
if ($competition_id <= 0) {
    echo "Invalid competition ID.";
    exit;
}
// Get competition details
$compQuery = $conn->query("SELECT * FROM competitions WHERE competition_id = $competition_id");
$comp = $compQuery->fetch_assoc();

// Check assignment condition
$today = new DateTime();
$submissionEnd = !empty($comp['student_submission_end_date']) ? new DateTime($comp['student_submission_end_date']) : null;
$alreadyAssigned = $conn->query("SELECT COUNT(*) AS total FROM evaluator_assignments WHERE competition_id = $competition_id")->fetch_assoc()['total'];

if ($alreadyAssigned == 0 && $submissionEnd && $today >= $submissionEnd) {
    include_once "round_robin_assignment.php";
    assignEvaluatorsRoundRobin($competition_id, $conn); // Function we'll write next
}

// Pagination setup
$limit = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Search (by student name or college name)
$search = isset($_GET['search']) ? $_GET['search'] : "";
$searchQuery = $search ? "AND (u.full_name LIKE '%$search%' OR clg.name LIKE '%$search%')" : "";


// Main query: fetch verified submissions for the selected competition
$evaluator_id = $_SESSION['user_id']; // assuming you store this at login

$query = "
    SELECT 
        ss.submission_id,
        u.full_name AS student_name,
        clg.name AS college_name,
        crs.name AS course_name,
        ss.current_semester,
        ss.submission_date
    FROM evaluator_assignments ea
    JOIN student_submissions ss ON ea.submission_id = ss.submission_id
    JOIN users u ON ss.student_user_id = u.user_id
    JOIN colleges clg ON ss.college_id = clg.college_id
    JOIN courses crs ON ss.course_id = crs.course_id
    WHERE 
        ea.competition_id = $competition_id
        AND ea.evaluator_id = $evaluator_id
        AND ss.is_verified_by_project_head = 1
        $searchQuery
    ORDER BY ss.submission_date DESC
    LIMIT $limit OFFSET $offset
";
$result = $conn->query($query);

// Total records for pagination
$countQuery = "
    SELECT COUNT(*) AS total
    FROM evaluator_assignments ea
    JOIN student_submissions ss ON ea.submission_id = ss.submission_id
    JOIN users u ON ss.student_user_id = u.user_id
    JOIN colleges clg ON ss.college_id = clg.college_id
    WHERE 
        ea.competition_id = $competition_id
        AND ea.evaluator_id = $evaluator_id
        AND ss.is_verified_by_project_head = 1
        $searchQuery
";


$totalResult = $conn->query($countQuery);
$totalRecords = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

// Display any flash message
$message = $_SESSION['message'] ?? "";
$message_type = $_SESSION['message_type'] ?? "";
unset($_SESSION['message'], $_SESSION['message_type']);
?>

<!-- HTML STARTS HERE -->
<main class="h-full overflow-y-auto">
    <div class="container px-6 mx-auto grid">
        <h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">Verified Submissions</h2>

        <!-- Search -->
        <form method="GET" class="flex items-center mb-4">
            <input type="hidden" name="competition_id" value="<?php echo $competition_id; ?>">
            <input type="text" name="search" placeholder="Search by Student or College" value="<?php echo htmlspecialchars($search); ?>"
                class="px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit"
                class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-md ml-2 hover:bg-blue-600">Search</button>
        </form>

        <?php if ($message): ?>
            <div class="bg-white border <?php echo ($message_type === 'success') ? 'border-green-500 text-green-600' : 'border-red-500 text-red-600'; ?> px-6 py-3 rounded-lg shadow-lg mb-4">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Submissions Table -->
        <div class="w-full overflow-hidden rounded-lg shadow-xs">
            <div class="w-full overflow-x-auto">
                <table class="w-full whitespace-no-wrap">
                    <thead>
                        <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Student Name</th>
                            <th class="px-4 py-3">College</th>
                            <th class="px-4 py-3">Course</th>
                            <th class="px-4 py-3">Semester</th>
                            <th class="px-4 py-3">Submitted At</th>
                            <th class="px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y dark:divide-gray-700">
                        <?php if ($result->num_rows > 0): ?>
                            <?php $sr = $offset + 1; ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="text-gray-700">
                                    <td class="px-4 py-3 text-sm"><?php echo $sr++; ?></td>
                                    <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($row['student_name']); ?></td>
                                    <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($row['college_name']); ?></td>
                                    <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($row['course_name']); ?></td>
                                    <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($row['current_semester']); ?></td>
                                    <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($row['submission_date']); ?></td>
                                    <td class="px-4 py-3 text-sm">
                                        <a href="view_submission_details_v2.php?submission_id=<?php echo $row['submission_id']; ?>"
                                           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition">
                                           View
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="px-4 py-3 text-center text-sm text-gray-600">No submissions found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="flex justify-between items-center mt-4">
            <nav class="flex space-x-2">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?competition_id=<?php echo $competition_id; ?>&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"
                        class="px-4 py-2 text-sm font-semibold border rounded <?php echo ($i == $page) ? 'bg-blue-500 text-white' : 'text-blue-500 border-blue-500 hover:bg-blue-100'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </nav>
        </div>
    </div>
</main>
