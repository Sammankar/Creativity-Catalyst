<?php
include('header.php');

// Fetch user_id from session
$user_id = $_SESSION['user_id'];

// Fetch college_id from users table using user_id
$collegeQuery = "SELECT college_id, course_id FROM users WHERE user_id = $user_id";
$collegeResult = $conn->query($collegeQuery);
$collegeRow = $collegeResult->fetch_assoc();
$college_id = $collegeRow['college_id'];
$course_id = $collegeRow['course_id'];

// Pagination Variables
$limit = 5; // Records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search term handling
$search = "";
if (!empty($_GET['search'])) {
    // Get the raw search term
    $search = $_GET['search'];

    // Escape it for use in SQL query
    $searchQuery = " AND (u.full_name LIKE '%$search%' OR u.email LIKE '%$search%')";
} else {
    $searchQuery = ""; // No search query if empty
}

// Fetch Students with Course Name for the specific college
$query = "
    SELECT 
        u.user_id,
        u.full_name AS student_name,  
        u.email AS student_email, 
        u.phone_number AS student_phone, 
        u.role AS student_role,
        u.access_status,
        u.users_status AS users_status,
        u.current_semester,
        u.created_at,
        c.name AS course_name,
         COALESCE(sa.current_academic_year, ssr.academic_year) AS academic_year
    FROM users u
    LEFT JOIN courses c ON u.course_id = c.course_id
    LEFT JOIN student_academics sa ON u.user_id = sa.user_id
    LEFT JOIN student_semester_result ssr 
        ON u.user_id = ssr.user_id 
        AND ssr.course_id = u.course_id 
        AND ssr.semester = u.current_semester
    WHERE u.role = 5 
      AND u.college_id = $college_id 
      AND u.course_id = $course_id
      AND (sa.current_academic_year IS NULL OR ssr.academic_year IS NULL)
      $searchQuery
    GROUP BY u.user_id
    LIMIT $limit OFFSET $offset";

$result = $conn->query($query);

// Fetch Data
$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

// Total Records Count for Pagination
$countQuery = "
    SELECT COUNT(DISTINCT u.user_id) as total
    FROM users u
    LEFT JOIN student_academics sa ON u.user_id = sa.user_id
    LEFT JOIN student_semester_result ssr 
        ON u.user_id = ssr.user_id 
        AND ssr.course_id = u.course_id 
        AND ssr.semester = u.current_semester
    WHERE u.role = 5 
      AND u.college_id = $college_id 
      AND u.course_id = $course_id
      AND (sa.current_academic_year IS NULL OR ssr.academic_year IS NULL)
      $searchQuery";

$countResult = $conn->query($countQuery);
$countRow = $countResult->fetch_assoc();
$totalRecords = $countRow['total'];
$totalPages = ceil($totalRecords / $limit);
?>
<main class="h-full overflow-y-auto">
    <div class="container px-6 mx-auto grid">
        <h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">Manual Academics Assign Students</h2>
        
        <div class="grid gap-6 mb-8 md:grid-cols-2 xl:grid-cols-4">
            <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
                <div class="p-3 mr-4 text-orange-500 bg-orange-100 rounded-full dark:text-orange-100 dark:bg-orange-500">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 2L2 6l8 4 8-4-8-4zM2 9l8 4 8-4v2l-8 4-8-4V9zM2 14v2h16v-2H2z"></path>
                    </svg>
                </div>
                <div>
                    <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">Total Students</p>
                    <p class="text-lg font-semibold text-gray-700 dark:text-gray-200"><?php echo $totalRecords; ?></p>
                </div>
            </div>
        </div>
<div class="mb-4">
    <a href="student_list.php" 
       class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-md shadow-md hover:bg-blue-600 inline-block w-auto">
        Back To Student List
    </a>
</div>

        <h4 class="mb-4 text-lg font-semibold text-gray-600 dark:text-gray-300">Students List</h4>
        
        <div class="w-full mb-8 overflow-hidden rounded-lg shadow-xs">
            <div class="w-full overflow-x-auto">
                <!-- Search Form -->
                <div class="flex justify-between items-center mb-4">
                    <div class="w-1/3">
                    
                    </div>

                    <form method="GET" class="flex items-center space-x-2 w-1/3 justify-end">
                        <input 
                            type="text" 
                            name="search" 
                            placeholder="Search Students..." 
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

                <!-- Table -->
                <table class="w-full whitespace-no-wrap">
                    <thead>
                        <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800">
                            <th class="px-4 py-3">SR NO</th>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Email</th>   
                            <th class="px-4 py-3">COURSE</th>
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
                                <td class="px-4 py-3 font-semibold text-xs"><?php echo $student['student_name']; ?></td>
                                <td class="px-4 py-3 font-semibold text-xs"><?php echo $student['student_email']; ?></td>
                                <td class="px-4 py-3 font-semibold text-xs"><?php echo $student['course_name']; ?></td>
                                <td class="px-4 py-3 font-semibold text-xs"><?php echo $student['current_semester']; ?></td>
                                <td class="px-4 py-3 font-semibold text-xs">
            <?= $student['academic_year'] ? htmlspecialchars($student['academic_year']) : 'Not Assigned'; ?>
        </td>
                                <td class="px-4 py-3 font-semibold text-xs">
                                    <button 
                                        class="px-4 py-2 text-white bg-blue-500 font-semibold rounded-full shadow-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 view-Student"
                                        data-id="<?php echo $student['user_id']; ?>"> <!-- Correct variable for Student -->
                                        View
                                    </button>
                               
                                    <a href="javascript:void(0);" 
   onclick="openAssignModal(
       '<?php echo $student['user_id']; ?>', 
       '<?php echo addslashes($student['student_name']); ?>', 
       '<?php echo $student['student_email']; ?>', 
       '<?php echo $student['current_semester']; ?>', 
       '<?php echo $course_id; ?>', 
       '<?php echo addslashes($student['course_name']); ?>'
   )"
   class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-full shadow-md hover:bg-blue-600">
   Assign
</a>



                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="flex justify-between mt-4">
                    <div>
                        <span class="text-sm text-gray-600 dark:text-gray-400">Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $totalRecords); ?> of <?php echo $totalRecords; ?> results</span>
                    </div>
                    <div class="flex space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="px-3 py-1 bg-gray-300 text-gray-600 rounded-md hover:bg-gray-400">Previous</a>
                        <?php endif; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="px-3 py-1 bg-gray-300 text-gray-600 rounded-md hover:bg-gray-400">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
       

<!-- View Student Details Modal -->
 <!-- View Student Details Modal -->
<div id="viewStudentModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
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
            <label class="font-semibold">Phone Number:</label>
            <input type="text" id="StudentPhone" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">College:</label>
            <input type="text" id="StudentCollege" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <button id="closeStudentModal" class="w-full bg-blue-500 text-white py-2 rounded font-semibold">OK</button>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const viewButtons = document.querySelectorAll(".view-Student");
    const modal = document.getElementById("viewStudentModal");
    const closeModal = document.getElementById("closeStudentModal");

    viewButtons.forEach(button => {
        button.addEventListener("click", () => {
            const Student_id = button.dataset.id;

            fetch("view_Student_details.php?id=" + Student_id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById("StudentName").value = data.Student.full_name;
                        document.getElementById("StudentEmail").value = data.Student.email;
                        document.getElementById("StudentPhone").value = data.Student.phone_number;
                        document.getElementById("StudentCollege").value = data.Student.college_name;
                        modal.classList.remove("hidden");
                    } else {
                        alert("Failed to fetch Student details.");
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("An error occurred.");
                });
        });
    });

    closeModal.addEventListener("click", () => {
        modal.classList.add("hidden");
    });
});


</script>
<!-- Manual Academic Year Assignment Modal -->
<div id="assignAcademicYearModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-lg shadow-lg w-96 p-6">
        <h2 class="text-lg font-semibold mb-4 text-center">Assign Academic Year</h2>

        <input type="hidden" id="assignUserId">
        <input type="hidden" id="assignCourseId">

        <!-- Student Info -->
        <div class="mb-2">
            <label class="font-semibold">Student Name:</label>
            <input type="text" id="assignStudentName" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">Email:</label>
            <input type="text" id="assignStudentEmail" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">Course:</label>
            <input type="text" id="assignCourseName" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">Current Semester:</label>
            <input type="text" id="assignCurrentSemester" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <!-- Dropdown -->
        <div class="mb-2">
            <label class="font-semibold">Select Academic Year:</label>
            <select id="assignAcademicYearDropdown" class="w-full px-3 py-2 border rounded">
                <option value="">Select Academic Year</option>
            </select>
        </div>

        <!-- Start and End Dates (shown after dropdown selection) -->
        <div id="calendarDatesSection" class="mb-4 hidden">
            <div class="mb-2">
                <label class="font-semibold">Start Date:</label>
                <input type="text" id="calendarStartDate" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
            </div>
            <div class="mb-2">
                <label class="font-semibold">End Date:</label>
                <input type="text" id="calendarEndDate" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
            </div>
        </div>

        <!-- Action Buttons -->
        <button id="assignYearBtn" class="w-full bg-blue-500 text-white py-2 rounded font-semibold">Assign</button>
        <button onclick="closeAssignModal()" class="w-full bg-gray-500 text-white py-2 rounded font-semibold mt-2">Cancel</button>
    </div>
</div>

<script>
function openAssignModal(userId, fullName, email, currentSemester, courseId, courseName) {
    document.getElementById("assignUserId").value = userId;
    document.getElementById("assignStudentName").value = fullName;
    document.getElementById("assignStudentEmail").value = email;
    document.getElementById("assignCurrentSemester").value = currentSemester;
    document.getElementById("assignCourseId").value = courseId;
    document.getElementById("assignCourseName").value = courseName;

    // Reset dropdown
    const dropdown = document.getElementById("assignAcademicYearDropdown");
    dropdown.innerHTML = "<option value=''>Select Academic Year</option>";
    document.getElementById("calendarDatesSection").classList.add("hidden");

    fetch(`get_academic_years.php?course_id=${courseId}&semester=${currentSemester}`)
        .then(res => res.json())
        .then(data => {
            data.forEach(row => {
                const option = document.createElement("option");
                option.value = row.id; // academic_calendar.id
                option.textContent = row.academic_year;
                option.dataset.start = row.start_date;
                option.dataset.end = row.end_date;
                dropdown.appendChild(option);
            });
        });

    document.getElementById("assignAcademicYearModal").classList.remove("hidden");
}

function closeAssignModal() {
    document.getElementById("assignAcademicYearModal").classList.add("hidden");
}

// Show start/end dates on selection
document.getElementById("assignAcademicYearDropdown").addEventListener("change", function () {
    const selectedOption = this.options[this.selectedIndex];
    if (this.value) {
        document.getElementById("calendarStartDate").value = selectedOption.dataset.start;
        document.getElementById("calendarEndDate").value = selectedOption.dataset.end;
        document.getElementById("calendarDatesSection").classList.remove("hidden");
    } else {
        document.getElementById("calendarDatesSection").classList.add("hidden");
    }
});

// Assign academic year (with calendar ID)
document.getElementById("assignYearBtn").addEventListener("click", () => {
    const userId = document.getElementById("assignUserId").value;
    const calendarId = document.getElementById("assignAcademicYearDropdown").value;

    if (!calendarId) {
        alert("Please select an academic year.");
        return;
    }

    fetch("update_academic_year.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `user_id=${userId}&calendar_id=${calendarId}`
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if (data.success) location.reload();
    });
});
</script>




    </div>
</main>
