<?php
include('header.php');
include('connection.php');

$user_id = $_SESSION['user_id'] ?? null;
$competition_id = $_GET['competition_id'] ?? null;

if (!$user_id || !$competition_id) {
    echo "Access Denied.";
    exit;
}

$competition = $conn->query("SELECT max_submissions_per_college, name FROM competitions WHERE competition_id = $competition_id")->fetch_assoc();
$max_allowed = (int)$competition['max_submissions_per_college'];
$competition_name = $competition['name'];

$userQuery = "SELECT course_id, college_id FROM users WHERE user_id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userResult = $stmt->get_result();

$students = [];
$totalPages = 0;
$academicYears = [];

if ($userRow = $userResult->fetch_assoc()) {
    $course_id = $userRow['course_id'];
    $college_id = $userRow['college_id'];

    $calendarQuery = "SELECT DISTINCT academic_year FROM academic_calendar WHERE course_id = ? AND ((is_editable = 1 AND status = 1) OR (is_editable = 0 AND status = 2))";
    $stmt = $conn->prepare($calendarQuery);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $calendarResult = $stmt->get_result();
    while ($row = $calendarResult->fetch_assoc()) {
        $academicYears[] = $row['academic_year'];
    }

    $courseResult = $conn->query("SELECT name, total_semesters FROM courses WHERE course_id = $course_id");
    $courseData = $courseResult->fetch_assoc();
    $total_semesters = $courseData['total_semesters'] ?? '';

    $selected_ids = [];
    $res = $conn->query("SELECT student_user_id FROM competition_participants WHERE competition_id = $competition_id AND college_id = $college_id");
    while ($r = $res->fetch_assoc()) {
        $selected_ids[] = $r['student_user_id'];
    }

    $limit = 5;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;
    $search = $_GET['search'] ?? '';
    $searchQuery = !empty($search) ? " AND (u.full_name LIKE '%$search%' OR u.email LIKE '%$search%')" : "";
    $selectedAcademicYear = $_GET['academic_year'] ?? '';
    $selectedSemester = $_GET['semester'] ?? '';

    $query = "
    SELECT 
        u.user_id,
        u.full_name AS student_name,
        u.email AS student_email,
        gu.full_name AS guide_name,
        sa.current_semester,
        sa.current_academic_year
    FROM users u
    LEFT JOIN guide_allocations g ON u.user_id = g.student_user_id
    LEFT JOIN users gu ON g.guide_user_id = gu.user_id
    LEFT JOIN student_academics sa ON u.user_id = sa.user_id 
    WHERE u.role = 5 
    AND u.college_id = $college_id 
    AND u.course_id = $course_id
    AND g.is_current = 1
    AND (
        sa.current_semester < $total_semesters
        OR (sa.status != 1 OR sa.current_semester IS NULL)
    )
    AND u.user_id NOT IN (
        SELECT student_user_id 
        FROM competition_participants 
        WHERE competition_id = $competition_id AND college_id = $college_id
    )
    " . (!empty($selectedAcademicYear) ? "AND sa.current_academic_year = '$selectedAcademicYear'" : "") . "
    " . (!empty($selectedSemester) ? "AND sa.current_semester = '$selectedSemester'" : "") . "
    $searchQuery
    LIMIT $limit OFFSET $offset";


    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    $countQuery = "
    SELECT COUNT(*) as total 
    FROM users u 
    LEFT JOIN guide_allocations g ON u.user_id = g.student_user_id
    LEFT JOIN student_academics sa ON u.user_id = sa.user_id
    WHERE u.role = 5 
    AND u.college_id = $college_id 
    AND u.course_id = $course_id 
    AND g.is_current = 1
    AND u.user_id NOT IN (
        SELECT student_user_id 
        FROM competition_participants 
        WHERE competition_id = $competition_id AND college_id = $college_id
    )
    " . (!empty($selectedAcademicYear) ? "AND sa.current_academic_year = '$selectedAcademicYear'" : "") . "
    " . (!empty($selectedSemester) ? "AND sa.current_semester = '$selectedSemester'" : "") . "
    $searchQuery";

    $totalRecords = $conn->query($countQuery)->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $limit);
    $selected_count = count($selected_ids);
}
?>

<main class="h-full overflow-y-auto">
<div class="container px-6 mx-auto grid">
<h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">Select Students for <?= htmlspecialchars($competition_name) ?> - Max Allowed Per Course: <?= $max_allowed ?></h2>

<form method="get" class="mb-6">
    <input type="hidden" name="competition_id" value="<?= $competition_id ?>" />
    <div class="flex flex-wrap gap-4">
        <select name="academic_year" class="border px-4 py-2 rounded ml-2">
            <option value="">All Academic Years</option>
            <?php foreach ($academicYears as $year): ?>
                <option value="<?= $year ?>" <?= ($selectedAcademicYear == $year) ? 'selected' : '' ?>><?= $year ?></option>
            <?php endforeach; ?>
        </select>
        <select name="semester" class="border px-4 py-2 rounded ml-2">
            <option value="">All Semesters</option>
            <?php for ($i = 1; $i <= $total_semesters; $i++): ?>
                <option value="<?= $i ?>" <?= ($selectedSemester == $i) ? 'selected' : '' ?>>Semester <?= $i ?></option>
            <?php endfor; ?>
        </select>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or email" class="border px-4 py-2 rounded ml-2" />
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded ml-2">Filter</button>
        <a href="?competition_id=<?= $competition_id ?>" class="bg-blue-600 text-white px-4 py-2 rounded ml-2">Reset</a>

        <a 
        href="student_competition_selection_list.php" 
        class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-md shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 ml-2"
    >
        Back To Competitions
    </a>
    <?php if ($selected_count > 0): ?>
    <a href="view_selected_students.php?competition_id=<?= $competition_id ?>"
       class="inline-block px-4 py-2 bg-blue-600 text-white font-semibold rounded-md shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 ml-2">
        View Selected Students
    </a>
<?php else: ?>
    <a href="javascript:void(0);"
       title="Register Students First To View"
       class="inline-block px-4 py-2 bg-blue-500 text-white font-semibold rounded-md shadow-md ml-2 cursor-not-allowed opacity-60"
       onclick="return false;">
        View Selected Students
    </a>
<?php endif; ?>


    </div>
</form>

<form method="post" action="save_selected_students.php">
    <input type="hidden" name="competition_id" value="<?= $competition_id ?>" />
    <div class="ml-2">
        <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-indigo-700 transition mb-2">Save Selection</button>
    </div>
    <div class="w-full mb-8 overflow-hidden rounded-lg shadow-xs">
    <div class="w-full overflow-x-auto">
        <table class="w-full whitespace-no-wrap">
                <thead>
                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800">
                        <th class="px-4 py-3">Select</th>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Semester</th>
                        <th class="px-4 py-3">Academic Year</th>
                        <th class="px-4 py-3">Guide</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y">
                    <?php $sr = $offset + 1; ?>
                    <?php foreach ($students as $student): ?>
                        <?php
                            $isSelected = in_array($student['user_id'], $selected_ids);
                            $checkboxId = "student_" . $student['user_id'];
                        ?>
                        <tr>
                            <td class="px-4 py-3">
                                <input type="checkbox" 
                                       name="selected_students[]" 
                                       value="<?= $student['user_id'] ?>" 
                                       id="<?= $checkboxId ?>" 
                                       class="student-checkbox"
                                       <?= $isSelected ? 'checked' : '' ?> />
                            </td>
                            <td class="px-4 py-3 text-sm"><?= htmlspecialchars($student['student_name']) ?></td>
                            <td class="px-4 py-3 text-sm"><?= $student['current_semester'] ?></td>
                            <td class="px-4 py-3 text-sm"><?= $student['current_academic_year'] ?></td>
                            <td class="px-4 py-3 text-sm"><?= $student['guide_name'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex justify-between items-center">
            <span class="text-sm text-gray-600">
                Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $totalRecords) ?> of <?= $totalRecords ?>
            </span>
            <div class="space-x-2">
                <?php if ($page > 1): ?>
                    <a href="?competition_id=<?= $competition_id ?>&page=<?= $page - 1 ?>&academic_year=<?= $selectedAcademicYear ?>&semester=<?= $selectedSemester ?>&search=<?= $search ?>" class="bg-gray-300 px-3 py-1 rounded-md">Previous</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?competition_id=<?= $competition_id ?>&page=<?= $page + 1 ?>&academic_year=<?= $selectedAcademicYear ?>&semester=<?= $selectedSemester ?>&search=<?= $search ?>" class="bg-gray-300 px-3 py-1 rounded-md">Next</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

</form>
</div>
</main>

<script>
const checkboxes = document.querySelectorAll('.student-checkbox');
const maxAllowed = <?= $max_allowed ?>;

function updateLimit() {
    const selected = Array.from(checkboxes).filter(cb => cb.checked).length;
    checkboxes.forEach(cb => {
        if (!cb.checked) cb.disabled = selected >= maxAllowed;
    });
}
checkboxes.forEach(cb => cb.addEventListener('change', updateLimit));
updateLimit();
</script>
