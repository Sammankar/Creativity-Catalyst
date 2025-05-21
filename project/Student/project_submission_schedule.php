<?php
include 'header.php';
include 'connection.php';

// Session check
if (!isset($_SESSION['user_id'])) {
    echo "You are not logged in.";
    exit;
}

$student_user_id = $_SESSION['user_id'];

// === [ Student Info ] ===
$stmt = $conn->prepare("SELECT college_id, full_name, course_id, current_semester FROM users WHERE user_id = ?");
$stmt->bind_param("i", $student_user_id);
$stmt->execute();
$stmt->bind_result($college_id, $student_full_name, $course_id, $current_semester);
$stmt->fetch();
$stmt->close();

// === [ Academic Info ] ===
$stmt = $conn->prepare("SELECT current_academic_year FROM student_academics WHERE user_id = ?");
$stmt->bind_param("i", $student_user_id);
$stmt->execute();
$stmt->bind_result($current_academic_year);
$stmt->fetch();
$stmt->close();

// === [ Schedule Data + Pagination ] ===
$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? "%" . $_GET['search'] . "%" : "%";

// Count total records
$count_query = $conn->prepare("
    SELECT COUNT(*) FROM project_submission_schedule pss
    LEFT JOIN academic_calendar ac ON pss.academic_calendar_id = ac.id
    WHERE pss.course_id = ? AND ac.academic_year LIKE ? AND ac.semester LIKE ?
");
$count_query->bind_param("iss", $course_id, $search, $search);
$count_query->execute();
$count_result = $count_query->get_result()->fetch_assoc();
$total = $count_result['COUNT(*)'];
$totalPages = ceil($total / $limit);

// Fetch paginated schedule data
$query = $conn->prepare("
    SELECT pss.*, ac.semester, ac.academic_year, c.name AS course_name
    FROM project_submission_schedule pss
    LEFT JOIN academic_calendar ac ON pss.academic_calendar_id = ac.id
    LEFT JOIN courses c ON pss.course_id = c.course_id
    WHERE pss.course_id = ? AND ac.academic_year LIKE ? AND ac.semester LIKE ?
    ORDER BY 
        CASE 
            WHEN CURDATE() BETWEEN pss.start_date AND pss.end_date THEN 1
            WHEN CURDATE() < pss.start_date THEN 2
            ELSE 3
        END, pss.start_date DESC
    LIMIT ? OFFSET ?
");
$query->bind_param("issii", $course_id, $search, $search, $limit, $offset);
$query->execute();
$result = $query->get_result();
?>

<main class="h-full overflow-y-auto">
    <div class="container px-6 mx-auto grid">
        <h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">Project Submission Schedule</h2>

        <div class="w-full mb-8 overflow-hidden rounded-lg shadow-xs">
            <div class="flex justify-between items-center mb-4">
                <form method="GET" class="flex items-center space-x-2">
                    <input type="text" name="search" placeholder="Search academic year/semester..."
                        value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>"
                        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <button type="submit"
                        class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-md shadow-md hover:bg-blue-600">
                        Search
                    </button>
                </form>
            </div>

            <div class="w-full overflow-x-auto">
                <table class="w-full whitespace-no-wrap">
                    <thead>
                        <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                            <th class="px-4 py-3">Academic Year</th>
                            <th class="px-4 py-3">Semester</th>
                            <th class="px-4 py-3">Start Date</th>
                            <th class="px-4 py-3">End Date</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Previous Stage</th>
                            <th class="px-4 py-3">Upcoming Stage</th>
                            <th class="px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
    while ($row = $result->fetch_assoc()):
        $status = "";
        $today = date('Y-m-d');
        
        $status_map = [
            'Upcoming' => 'text-[#2563EB]',    // blue-600
            'In-Progress' => 'text-[#D97706]', // yellow-600
            'Completed' => 'text-[#16A34A]'    // green-600
        ];
    
        if ($today < $row['start_date']) {
            $status_text = "Upcoming";
        } elseif ($today > $row['end_date']) {
            $status_text = "Completed";
        } else {
            $status_text = "In-Progress";
        }
    
        $status_color = $status_map[$status_text];
        $status = '<span class="' . $status_color . ' font-semibold">' . $status_text . '</span>';

        // Upcoming stage logic
        $upcoming_stage = "No upcoming stages";
        $sid = $row['id'];
        $stage_sql = "SELECT title, unlock_date, stage_number FROM project_submission_stages WHERE schedule_id = ? ORDER BY unlock_date ASC";
        $stmt_stage = $conn->prepare($stage_sql);
        $stmt_stage->bind_param("i", $sid);
        $stmt_stage->execute();
        $res_stage = $stmt_stage->get_result();
        while ($stage = $res_stage->fetch_assoc()) {
            if ($today < $stage['unlock_date']) {
                $upcoming_stage = "Stage: " . $stage['stage_number'] . " (" . $stage['unlock_date'] . ")";
                break;
            }
        }

        // Previous stage logic
        $previous_stage = "No Previous Stages";
        $stage_sql_prev = "SELECT title, stage_number, unlock_date FROM project_submission_stages WHERE schedule_id = ? AND unlock_date < ? ORDER BY unlock_date DESC LIMIT 1";
        $stmt_prev_stage = $conn->prepare($stage_sql_prev);
        $stmt_prev_stage->bind_param("is", $sid, $today);
        $stmt_prev_stage->execute();
        $res_prev_stage = $stmt_prev_stage->get_result();
        if ($prev_stage = $res_prev_stage->fetch_assoc()) {
            $previous_stage = "Stage: " . $prev_stage['stage_number'] . " (" . $prev_stage['unlock_date'] . ")";
        }
?>
<tr class="border-t hover:bg-gray-50">
    <td class="px-4 py-2"><?= htmlspecialchars($row['academic_year']) ?></td>
    <td class="px-4 py-2"><?= htmlspecialchars($row['semester']) ?></td>
    <td class="px-4 py-2"><?= htmlspecialchars($row['start_date']) ?></td>
    <td class="px-4 py-2"><?= htmlspecialchars($row['end_date']) ?></td>
    <td class="px-4 py-2"><?= $status ?></td>
    <td class="px-4 py-2"><?= $previous_stage ?></td>
    <td class="px-4 py-2"><?= $upcoming_stage ?></td>
    <td class="px-4 py-2">
        <a href="project_submission_stages.php?schedule_id=<?= $row['id'] ?>"
            class="px-3 py-1 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700">
            Submission
        </a>
    </td>
</tr>
<?php endwhile; ?>

                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex justify-end mt-4">
                <nav class="flex items-center space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($_GET['search'] ?? '') ?>"
                           class="px-4 py-2 border text-gray-600 rounded hover:bg-blue-500 hover:text-white">
                            Back
                        </a>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($_GET['search'] ?? '') ?>"
                           class="px-3 py-2 border rounded <?= $i == $page ? 'bg-blue-500 text-white' : 'text-gray-700 hover:bg-gray-200' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($_GET['search'] ?? '') ?>"
                           class="px-4 py-2 border text-gray-600 rounded hover:bg-blue-500 hover:text-white">
                            Next
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </div>
</main>
