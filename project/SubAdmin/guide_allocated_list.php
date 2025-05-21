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
            " . (!empty($selectedAcademicYear) ? "AND sa.current_academic_year = '$selectedAcademicYear'" : "") . "
            " . (!empty($selectedSemester) ? "AND sa.current_semester = '$selectedSemester'" : "") . "
            $searchQuery";

        $countResult = $conn->query($countQuery);
        $countRow = $countResult->fetch_assoc();
        $totalRecords = $countRow['total'];
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
        href="guide_allocation.php" 
        class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-md shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2"
    >
        + Allocate Guide
    </a>
</div>

<div class="w-full mb-8 overflow-hidden rounded-lg shadow-xs">
    <div class="w-full overflow-x-auto">
        <table class="w-full whitespace-no-wrap">
            <thead>
                <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800">
                    <th class="px-4 py-3">SR NO</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">SEMESTER</th>
                    <th class="px-4 py-3">ACADEMIC YEAR</th>
                    <th class="px-4 py-3">GUIDE NAME</th>
                    <th class="px-4 py-3">ACTION</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
                <?php $srNo = $offset + 1; ?>
                <?php foreach ($students as $student): ?>
                    <tr class="text-gray-700 dark:text-gray-400">
                        <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400"><?php echo $srNo++; ?></td>
                        <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($student['student_name']); ?></td>
                        <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($student['current_semester']); ?></td>
                        <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($student['current_academic_year']); ?></td>
                        <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($student['guide_name']); ?></td>
                        <td class="px-4 py-3 space-x-2">
                        <button 
    class="view-Student bg-blue-500 text-white px-3 py-1 rounded-md hover:bg-blue-600"
    data-id="<?php echo $student['user_id']; ?>"
>
    View
</button>


    <a href="update_guide.php?user_id=<?php echo $student['user_id']; ?>" class="bg-blue-500 text-white px-3 py-1 rounded-md hover:bg-blue-600">Update</a>
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


<!-- Modal -->
<div id="viewStudentModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-lg shadow-lg w-96 p-6">
        <h2 class="text-lg font-semibold mb-4">Student Details</h2>

        <div class="mb-2">
            <label class="font-semibold">Student Name:</label>
            <input type="text" id="StudentName" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">Email:</label>
            <input type="text" id="StudentEmail" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">Course:</label>
            <input type="text" id="StudentCourse" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">Semester:</label>
            <input type="text" id="StudentSemester" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">Academic Year:</label>
            <input type="text" id="StudentAcademicYear" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">Guide:</label>
            <input type="text" id="StudentGuide" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <button id="closeStudentModal" class="w-full bg-blue-500 text-white py-2 rounded font-semibold mt-4">OK</button>
    </div>
</div>

<!-- JavaScript -->
<script>
document.addEventListener("DOMContentLoaded", () => {
    const viewButtons = document.querySelectorAll(".view-Student");
    const modal = document.getElementById("viewStudentModal");
    const closeModal = document.getElementById("closeStudentModal");

    viewButtons.forEach(button => {
        button.addEventListener("click", () => {
            const studentId = button.getAttribute("data-id");

            fetch("view_student.php?id=" + studentId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const student = data.Student;

                        document.getElementById("StudentName").value = student.full_name || '';
                        document.getElementById("StudentEmail").value = student.email || '';
                        document.getElementById("StudentCourse").value = student.course_name || '';
                        document.getElementById("StudentSemester").value = student.semester || '';
                        document.getElementById("StudentAcademicYear").value = student.academic_year || '';
                        document.getElementById("StudentGuide").value = student.guide_name || '';

                        modal.classList.remove("hidden");
                    } else {
                        alert("Failed to fetch student details.");
                    }
                })
                .catch(error => {
                    console.error("Error fetching student data:", error);
                    alert("Something went wrong.");
                });
        });
    });

    closeModal.addEventListener("click", () => {
        modal.classList.add("hidden");
    });
});
</script>
<script>
function updateFilters() {
    const year = document.getElementById("academicYear").value;
    const sem = document.getElementById("semester").value;
    const search = document.getElementById("searchBox").value;
    const params = new URLSearchParams();

    if (year) params.set("academic_year", year);
    if (sem) params.set("semester", sem);
    if (search) params.set("search", search);
    
    window.location.href = "guide_allocated_list.php?" + params.toString();
}

document.getElementById("academicYear").addEventListener("change", updateFilters);
document.getElementById("semester").addEventListener("change", updateFilters);
document.getElementById("searchBox").addEventListener("keyup", function(e) {
    if (e.key === "Enter") updateFilters();
});

function resetFilters() {
    window.location.href = "guide_allocated_list.php";
}
</script>

</div>
</main>
