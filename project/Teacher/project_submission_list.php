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
        WHERE ga.guide_user_id = $user_id
        AND ga.is_current = 1";
    
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
            $students[] = $row;
        }

        $countQuery = "
        SELECT COUNT(*) AS total_allocations
        FROM guide_allocations ga
        JOIN users s ON ga.student_user_id = s.user_id
        JOIN student_academics sa ON sa.user_id = s.user_id
        JOIN courses c ON ga.course_id = c.course_id
        JOIN colleges cl ON s.college_id = cl.college_id
        WHERE ga.guide_user_id = $user_id
        AND ga.is_current = 1;

        $searchQuery";

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

<div class="grid gap-6 mb-8 md:grid-cols-2 xl:grid-cols-4">
    <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
        <div class="p-3 mr-4 text-orange-500 bg-orange-100 rounded-full dark:text-orange-100 dark:bg-orange-500">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 2L2 6l8 4 8-4-8-4zM2 9l8 4 8-4v2l-8 4-8-4V9zM2 14v2h16v-2H2z"></path>
            </svg>
        </div>
        <div>
            <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">Guide Allocated Students</p>
            <p class="text-lg font-semibold text-gray-700 dark:text-gray-200"><?php echo $totalRecords; ?></p>
        </div>
    </div>
</div>

<h4 class="mb-4 text-lg font-semibold text-gray-600 dark:text-gray-300">Guide Allocated Students List</h4>

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

    <!-- Reset button (newly added) -->
    <button onclick="resetFilters()" class="bg-grey-500 text-black px-4 py-2 rounded-md">Reset</button>
    <!-- Allocate button -->
    <a 
        href="guide_allocated_list.php" 
        class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-md shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2"
    >
        Back To Guide Allocated List
    </a>
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
                    <th class="px-4 py-3">ACTION</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
                <?php $srNo = $offset + 1; ?>
                <?php foreach ($students as $student): ?>
                    <tr class="text-gray-700 dark:text-gray-400">
                        <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400"><?php echo $srNo++; ?></td>
                        <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($student['student_name']); ?></td>
                        <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400">
    <?php echo !empty($student['student_email']) ? htmlspecialchars($student['student_email']) : 'NOT Added'; ?>
</td>
                        <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($student['current_semester']); ?></td>
                        <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($student['allocation_year']); ?></td>
                       

                        <td class="px-4 py-3 space-x-2">
                        <a 
    href="view_submissions.php?student_id=<?php echo $student['student_user_id']; ?>&academic_year=<?php echo urlencode($student['allocation_year']); ?>&semester=<?php echo $student['allocated_semester']; ?>&course=<?php echo $student['course_id']; ?>" 
    class="bg-blue-500 text-white px-3 py-1 rounded-md inline-block"
>
    View
</a>

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


<script>
function updateFilters() {
    const year = document.getElementById("academicYear").value;
    const sem = document.getElementById("semester").value;
    const search = document.getElementById("searchBox").value;
    const params = new URLSearchParams();

    if (year) params.set("academic_year", year);
    if (sem) params.set("semester", sem);
    if (search) params.set("search", search);
    
    window.location.href = "project_submission_list.php?" + params.toString();
}

document.getElementById("academicYear").addEventListener("change", updateFilters);
document.getElementById("semester").addEventListener("change", updateFilters);
document.getElementById("searchBox").addEventListener("keyup", function(e) {
    if (e.key === "Enter") updateFilters();
});

function resetFilters() {
    window.location.href = "project_submission_list.php";
}
</script>



</div>
</main>
