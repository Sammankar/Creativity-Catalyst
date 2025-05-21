<?php
include('header.php');

$user_id = $_SESSION['user_id'] ?? null;

$students = [];
$totalPages = 0;
$academicYears = [];

if ($user_id) {
    $userQuery = "SELECT course_id, college_id FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $userResult = $stmt->get_result();

    if ($userRow = $userResult->fetch_assoc()) {
        $course_id = $userRow['course_id'];
        $college_id = $userRow['college_id'];

        $calendarQuery = "SELECT DISTINCT academic_year FROM academic_calendar WHERE course_id = ? AND is_editable = 1 AND status = 1";
        $stmt = $conn->prepare($calendarQuery);
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $calendarResult = $stmt->get_result();
        while ($row = $calendarResult->fetch_assoc()) {
            $academicYears[] = $row['academic_year'];
        }

        $courseQuery = "SELECT name, total_semesters FROM courses WHERE course_id = ?";
        $stmt = $conn->prepare($courseQuery);
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $courseResult = $stmt->get_result();
        $courseData = $courseResult->fetch_assoc();

        $course_name = $courseData['name'] ?? '';
        $total_semesters = $courseData['total_semesters'] ?? '';

        $limit = 5;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;

        $search = $_GET['search'] ?? '';
        $searchQuery = !empty($search) ? " AND (u.full_name LIKE '%$search%' OR u.email LIKE '%$search%')" : "";

        $selectedAcademicYear = $_GET['academic_year'] ?? '';
        $selectedSemester = $_GET['semester'] ?? '';

        $query = "
        SELECT 
            ga.id AS allocation_id,
            ga.student_user_id,
            s.full_name AS student_name,
            s.username AS student_username,
            s.email AS student_email,
            s.current_semester,
            sa.academic_year,
            sa.course_id,
            sa.status AS academic_status,
            c.name AS course_name,
            cl.name AS college_name,
            ga.semester AS allocated_semester,
            ga.academic_year AS allocation_year,
            ga.academic_calendar_id,
            ga.assigned_at,
            ga.is_current,
            ga.previous_guide_id,
            ga.remarks
        FROM guide_allocations ga
        JOIN users s ON ga.student_user_id = s.user_id
        JOIN student_academics sa ON sa.user_id = s.user_id
        JOIN courses c ON ga.course_id = c.course_id
        JOIN colleges cl ON s.college_id = cl.college_id
        WHERE ga.course_id = $course_id AND ga.is_current = 1";

        if (!empty($selectedAcademicYear)) {
            $query .= " AND ga.academic_year = '" . $conn->real_escape_string($selectedAcademicYear) . "'";
        }

        if (!empty($selectedSemester)) {
            $query .= " AND ga.semester = " . (int)$selectedSemester;
        }

        $query .= $searchQuery;
        $query .= " ORDER BY ga.assigned_at DESC LIMIT $limit OFFSET $offset";

        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            $row['academic_status_text'] = ($row['academic_status'] == 1) ? 'Passout' : (($row['academic_status'] == 2) ? 'Left' : 'Ongoing');
            $students[] = $row;
        }

        $countQuery = "
        SELECT COUNT(*) AS total_allocations
        FROM guide_allocations ga
        JOIN users s ON ga.student_user_id = s.user_id
        JOIN student_academics sa ON sa.user_id = s.user_id
        WHERE ga.course_id = $course_id AND ga.is_current = 1";

        $countResult = $conn->query($countQuery);
        $countRow = $countResult->fetch_assoc();
        $totalRecords = $countRow['total_allocations'];
        $totalPages = ceil($totalRecords / $limit);
    }
}
?>

<main class="h-full overflow-y-auto">
<div class="container px-6 mx-auto grid">
<h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">Students</h2>
<h4 class="mb-4 text-lg font-semibold text-gray-600 dark:text-gray-300">All Students List</h4>

<div class="flex items-center mb-4 space-x-4">
    <select id="academicYear" class="border border-gray-300 px-3 py-2 rounded-md">
        <option value="">Select Academic Year</option>
        <?php foreach ($academicYears as $year): ?>
            <option value="<?php echo $year; ?>" <?php echo ($selectedAcademicYear === $year) ? 'selected' : ''; ?>>
                <?php echo $year; ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select id="semester" class="border border-gray-300 px-3 py-2 rounded-md">
        <option value="">Select Semester</option>
        <?php
        if (!empty($total_semesters)) {
            for ($i = 1; $i <= $total_semesters; $i++) {
                echo "<option value=\"$i\" " . ($selectedSemester == $i ? 'selected' : '') . ">Semester $i</option>";
            }
        }
        ?>
    </select>

    <input 
        type="text" 
        id="searchBox" 
        placeholder="Search by name/email" 
        value="<?php echo htmlspecialchars($search); ?>" 
        class="px-4 py-2 border border-gray-300 rounded-md"
    >

    <button onclick="resetFilters()" class="bg-grey-500 text-black px-4 py-2 rounded-md">Reset</button>
    <a href="student_list.php" class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-md shadow-md hover:bg-blue-600">Back To Student List</a>
</div>

<div class="w-full mb-8 overflow-hidden rounded-lg shadow-xs">
    <div class="w-full overflow-x-auto">
        <table class="w-full whitespace-no-wrap">
            <thead>
                <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800">
                    <th class="px-4 py-3">SR NO</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">EMAIL ID</th>
                    <th class="px-4 py-3">SEMESTER</th>
                    <th class="px-4 py-3">ACADEMIC YEAR</th>
                    <th class="px-4 py-3">ACADEMIC STATUS</th>
                    <th class="px-4 py-3">ACTION</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
                <?php $srNo = $offset + 1; ?>
                <?php foreach ($students as $student): ?>
                    <tr class="text-gray-700 dark:text-gray-400">
                        <td class="px-4 py-3 text-xs font-semibold"><?php echo $srNo++; ?></td>
                        <td class="px-4 py-3 text-xs font-semibold"><?php echo htmlspecialchars($student['student_name']); ?></td>
                        <td class="px-4 py-3 text-xs font-semibold"><?php echo !empty($student['student_email']) ? htmlspecialchars($student['student_email']) : 'NOT Added'; ?></td>
                        <td class="px-4 py-3 text-xs font-semibold"><?php echo htmlspecialchars($student['current_semester']); ?></td>
                        <td class="px-4 py-3 text-xs font-semibold"><?php echo htmlspecialchars($student['allocation_year']); ?></td>
                        <td class="px-4 py-3 text-xs font-semibold"><?php echo htmlspecialchars($student['academic_status_text']); ?></td>
                        <td class="px-4 py-3 space-x-2">
                            <a href="student_data_in_details.php?student_id=<?php echo $student['student_user_id']; ?>" class="bg-blue-500 text-white px-3 py-1 rounded-md inline-block">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="flex justify-between mt-4">
        <span class="text-sm text-gray-600 dark:text-gray-400">
            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $totalRecords); ?> of <?php echo $totalRecords; ?> results
        </span>
        <div class="space-x-2">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&academic_year=<?php echo $selectedAcademicYear; ?>&semester=<?php echo $selectedSemester; ?>&search=<?php echo $search; ?>" class="bg-gray-300 px-3 py-1 rounded-md">Previous</a>
            <?php endif; ?>
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>&academic_year=<?php echo $selectedAcademicYear; ?>&semester=<?php echo $selectedSemester; ?>&search=<?php echo $search; ?>" class="bg-gray-300 px-3 py-1 rounded-md">Next</a>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
</main>

<script>
function resetFilters() {
    window.location.href = "student_data.php";
}
</script>
