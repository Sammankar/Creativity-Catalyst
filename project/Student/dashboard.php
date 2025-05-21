<?php
include 'header.php';
include 'connection.php';

$suspension_message = "";
$formatted_start = "Academic Dates Not Scheduled";
$formatted_end = "Academic Dates Not Scheduled";

// Session check
if (!isset($_SESSION['user_id'])) {
    echo "You are not logged in.";
    exit;
}

$student_user_id = $_SESSION['user_id'];

// === [ Student Info ] ===
$query = "SELECT college_id, full_name, course_id, current_semester FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_user_id);
$stmt->execute();
$stmt->bind_result($college_id, $student_full_name, $course_id, $current_semester);
$stmt->fetch();
$stmt->close();

// === [ Academic Info ] ===
$query_academics = "SELECT current_academic_year FROM student_academics WHERE user_id = ?";
$stmt = $conn->prepare($query_academics);
$stmt->bind_param("i", $student_user_id);
$stmt->execute();
$stmt->bind_result($current_academic_year);
$stmt->fetch();
$stmt->close();

// === [ Suspension Check ] ===
$query_cc = "SELECT college_course_status FROM college_courses WHERE college_id = ? AND course_id = ?";
$stmt = $conn->prepare($query_cc);
$stmt->bind_param("ii", $college_id, $course_id);
$stmt->execute();
$stmt->bind_result($college_course_status);
$stmt->fetch();
$stmt->close();

if ($college_course_status == 0) {
    $suspension_message = "This Course is Temporarily Suspended by Your College.";
}

// === [ Academic Calendar Dates ] ===
$query_calendar = "
    SELECT start_date, end_date 
    FROM academic_calendar 
    WHERE course_id = ? AND semester = ? AND academic_year = ? AND status = 1 
    LIMIT 1
";
$stmt = $conn->prepare($query_calendar);
$stmt->bind_param("iis", $course_id, $current_semester, $current_academic_year);
$stmt->execute();
$stmt->bind_result($semester_start_date, $semester_end_date);

if ($stmt->fetch()) {
    if (!empty($semester_start_date) && $semester_start_date !== '1970-01-01') {
        $formatted_start = date("j F Y", strtotime($semester_start_date));
    }
    if (!empty($semester_end_date) && $semester_end_date !== '1970-01-01') {
        $formatted_end = date("j F Y", strtotime($semester_end_date));
    }
}
$stmt->close();

// === [ Course Details ] ===
$query_course = "SELECT name FROM courses WHERE course_id = ?";
$stmt_course = $conn->prepare($query_course);
$stmt_course->bind_param("i", $course_id);
$stmt_course->execute();
$stmt_course->bind_result($course_name);
$stmt_course->fetch();
$stmt_course->close();

// === [ College Details ] ===
$query_college = "SELECT name, address, contact_number, college_logo, director_name FROM colleges WHERE college_id = ?";
$stmt_college = $conn->prepare($query_college);
$stmt_college->bind_param("i", $college_id);
$stmt_college->execute();
$stmt_college->bind_result($college_name, $college_address, $college_contact, $college_logo, $director_name);
$stmt_college->fetch();
$stmt_college->close();

$college_logo_path = "../Admin/" . htmlspecialchars($college_logo);
$default_logo = "default-logo.png";
$logo_src = (file_exists($college_logo_path) && !empty($college_logo)) ? $college_logo_path : "../Admin/" . $default_logo;

$query_guide = "
    SELECT ga.id, ga.guide_user_id, u.full_name AS guide_name 
    FROM guide_allocations ga 
    LEFT JOIN users u ON ga.guide_user_id = u.user_id
    WHERE ga.student_user_id = ? AND ga.is_current = 1
    ORDER BY ga.assigned_at DESC
    LIMIT 1
";
$stmt_guide = $conn->prepare($query_guide);
$stmt_guide->bind_param("i", $student_user_id);
$stmt_guide->execute();
$stmt_guide->bind_result($guide_id, $guide_user_id, $guide_name);

if ($stmt_guide->fetch()) {
    // Found current guide
    $current_guide_name = $guide_name;
} else {
    // If no guide is found, set a default message
    $current_guide_name = "No guide assigned yet.";
}
$stmt_guide->close();


// === [ Student Semester Result Table ] ===

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Search
$search = isset($_GET['search']) ? "%" . $_GET['search'] . "%" : "%";

// Results Query
$query = $conn->prepare("
    SELECT ssr.*, 
           ac.semester, 
           ac.academic_year, 
           ac.start_date, 
           ac.end_date, 
           ac.status AS calendar_status, 
           ac.is_editable,
           c.name AS course_name
    FROM student_semester_result ssr
    LEFT JOIN academic_calendar ac ON ssr.academic_calendar_id = ac.id
    LEFT JOIN courses c ON ssr.course_id = c.course_id
    WHERE ssr.user_id = ? AND (ac.academic_year LIKE ? OR ac.semester LIKE ?)
    ORDER BY ac.start_date DESC
    LIMIT ? OFFSET ?
");
$query->bind_param("issii", $student_user_id, $search, $search, $limit, $offset);
$query->execute();
$result = $query->get_result();

// Count total
$countQuery = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM student_semester_result ssr
    LEFT JOIN academic_calendar ac ON ssr.academic_calendar_id = ac.id
    WHERE ssr.user_id = ? AND (ac.academic_year LIKE ? OR ac.semester LIKE ?)
");
$countQuery->bind_param("iss", $student_user_id, $search, $search);
$countQuery->execute();
$totalResult = $countQuery->get_result()->fetch_assoc();
$total = $totalResult['total'];
$totalPages = ceil($total / $limit);
?>


<main class="h-full overflow-y-auto">
    <div class="container px-6 mx-auto grid">
        <h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">Dashboard</h2>

        <?php if (!empty($suspension_message)) : ?>
            <div class="p-4 mb-4 text-sm text-yellow-800 rounded-lg bg-yellow-100 dark:bg-yellow-200 dark:text-yellow-900">
                <?php echo $suspension_message; ?>
            </div>
        <?php endif; ?>

        <!-- Dashboard Cards -->
        <div class="grid gap-6 mb-8 md:grid-cols-2 xl:grid-cols-4">
            <!-- Project Count -->
            <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
                <div class="p-3 mr-4 text-green-500 bg-green-100 rounded-full dark:text-green-100 dark:bg-green-500">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div>
                    <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">Projects Submitted</p>
                    <p class="text-lg font-semibold text-gray-700 dark:text-gray-200"></p>
                </div>
            </div>

            <!-- Competition Count -->
            <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
                <div class="p-3 mr-4 text-green-500 bg-green-100 rounded-full dark:text-green-100 dark:bg-green-500">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div>
                    <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">Competitions Participated</p>
                    <p class="text-lg font-semibold text-gray-700 dark:text-gray-200"></p>
                </div>
            </div>
        </div>

        <!-- College Info and Course Overview Cards -->
        <div class="flex justify-between gap-6 mb-6">
    <!-- College Details -->
    <div class="w-full max-w-4xl p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
    <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-200 mb-4">College Details</h2>
    <div class="flex items-center">
        <div class="w-1/2 p-3 flex justify-center items-center">
            <div class="w-32 h-32 rounded-full overflow-hidden border-2 border-gray-200 dark:border-gray-700">
                <img src="<?php echo $logo_src; ?>" alt="College Logo" class="w-full h-full object-cover">
            </div>
        </div>
        <div class="w-1/2 flex flex-col justify-between">
            <div class="flex items-center">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Name:</p>
                <p class="ml-2 text-lg font-semibold text-gray-700 dark:text-gray-200 break-words max-w-xs"> <?php echo $college_name; ?></p>
            </div>
            <div class="flex items-start mt-2">
            <p class="w-24 text-sm font-medium text-gray-600 dark:text-gray-400">Address:</p>
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 break-words max-w-xs"><?php echo $college_address; ?></p>
            </div>
            <div class="flex items-center mt-2">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Director:</p>
                <p class="ml-2 text-sm font-semibold text-gray-700 dark:text-gray-200 truncate"><?php echo $director_name; ?> (Director)</p>
            </div>
            <div class="flex items-center mt-2">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Contact:</p>
                <p class="ml-2 text-sm font-semibold text-gray-700 dark:text-gray-200 truncate"><?php echo $college_contact; ?></p>
            </div>
        </div>
    </div>
</div>


    <!-- Course Overview -->
    <div class="w-full max-w-xl p-6 bg-white rounded-lg shadow-md dark:bg-gray-800 mt-6 mx-auto">
    <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-200 mb-4">Course Overview & Schedule</h2>

    <?php if (!empty($suspension_message)): ?>
        <div class="p-3 mb-4 text-sm text-red-600 bg-red-100 border border-red-300 rounded dark:bg-red-900 dark:text-red-200">
            <?php echo $suspension_message; ?>
        </div>
    <?php endif; ?>

    <div class="flex items-center mb-2">
        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Course Name:</p>
        <p class="ml-2 text-lg font-semibold text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($course_name); ?></p>
    </div>

    <div class="flex items-center mb-2">
        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Semester:</p>
        <p class="ml-2 text-sm font-semibold text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($current_semester); ?></p>
    </div>

    <div class="flex items-center mb-2">
        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Semester Start Date:</p>
        <p class="ml-2 text-sm font-semibold text-gray-700 dark:text-gray-200"><?php echo $formatted_start; ?></p>
    </div>

    <div class="flex items-center mb-2">
        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Semester End Date:</p>
        <p class="ml-2 text-sm font-semibold text-gray-700 dark:text-gray-200"><?php echo $formatted_end; ?></p>
    </div>

    <div class="flex items-center mb-2">
        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Allocated Guide Name: -</p>
        <p class="ml-2 text-sm font-semibold text-gray-700 dark:text-gray-200"><?php echo $current_guide_name; ?></p>
    </div>
</div>
</div>
<h4 class="mb-4 text-lg font-semibold text-gray-600 dark:text-gray-300">
    Academic Schedule List
</h4>
<div class="w-full mb-8 overflow-hidden rounded-lg shadow-xs">
    
    <!-- Table Wrapper -->
    <div class="w-full overflow-x-auto">
        <!-- Search Form -->
        <div class="flex justify-between items-center mb-4">
    <!-- Left: Add academic Button -->
    <div 
    class="inline-block px-4 py-2 "
>
   
</div>

       
    </a>

    <!-- Center: Custom Popup Message -->
    <?php if (!empty($message)): ?>
        <div id="custom-popup" class="bg-white border <?php echo ($message_type === 'success') ? 'border-green-500 text-green-600' : 'border-red-500 text-red-600'; ?> px-6 py-3 rounded-lg shadow-lg flex items-center space-x-3 mx-auto">
            
            <!-- Icon -->
            <?php if ($message_type === 'success'): ?>
                <svg class="w-6 h-6 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            <?php else: ?>
                <svg class="w-6 h-6 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M12 6a9 9 0 110 18A9 9 0 0112 6z" />
                </svg>
            <?php endif; ?>
            
            <!-- Message -->
            <span><?php echo htmlspecialchars($message); ?></span>

            <!-- Close Button -->
            <button onclick="closePopup()" class="ml-2 text-gray-700 font-bold">×</button>
        </div>

        <script>
            function closePopup() {
                document.getElementById("custom-popup").style.display = "none";
            }
            setTimeout(closePopup, 5000); // Hide popup after 5 seconds
        </script>
    <?php endif; ?>

    <!-- Right: Search Bar -->
    <form method="GET" class="flex items-center space-x-2">
        <input 
            type="text" 
            name="search" 
            placeholder="Search academics..." 
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

        <!-- academics Table -->
        <table class="w-full whitespace-no-wrap">
    <thead>
        <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800">
            <th class="px-4 py-3">COURSE NAME</th>
            <th class="px-4 py-3">SEMESTER</th>
            <th class="px-4 py-3">ACADEMIC YEAR</th>
            <th class="px-4 py-3">START DATE</th>
            <th class="px-4 py-3">END DATE</th>
            <th class="px-4 py-3">ACADEMIC STATUS</th>
            <th class="px-4 py-3">ACADEMIC RESULT</th>
            <th class="px-4 py-3">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-2"><?= htmlspecialchars($row['course_name']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($row['semester']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($row['academic_year']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($row['start_date']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($row['end_date']) ?></td>
                            <td class="px-4 py-2">
                                <?php
                                    if ($row['calendar_status'] == 0) {
                                        echo '<span class="text-yellow-600 font-semibold">Suspended</span>';
                                    } else {
                                        echo $row['is_editable'] == 1
                                            ? '<span class="text-green-600 font-semibold">In-Progress</span>'
                                            : '<span class="text-gray-600 font-semibold">Completed</span>';
                                    }
                                ?>
                            </td>
                            <td class="px-4 py-2">
    <?php
        $internal = $row['status'];
        $external = $row['external_status'];

        $getStatusText = function($val) {
            return match($val) {
                0 => 'Not Declared',
                1 => 'Pass',
                2 => 'Fail',
                3 => 'Left',
                default => 'Unknown',
            };
        };

        if (!isset($internal) && !isset($external)) {
            echo '<span class="text-gray-500">No Data</span>';
        } else {
            // Internal label
            $interText = "Inter - " . $getStatusText($internal);
            $exterText = "Exter - " . $getStatusText($external);

            // Determine combined styling and message
            if ($internal == 1 && $external == 1) {
                echo '<span class="text-green-600 font-medium">Inter & Exter - Pass</span>';
            } elseif ($internal == 2 && $external == 2) {
                echo '<span class="text-red-600 font-medium">Inter & Exter - Fail</span>';
            } elseif ($internal == 1 && $external == 2) {
                echo '<span class="text-red-600 font-medium">Inter - Pass / Exter - Fail</span>';
            } elseif ($internal == 2 && $external == 1) {
                echo '<span class="text-red-600 font-medium">Inter - Fail / Exter - Pass</span>';
            } elseif ($internal == 1 && $external == 0) {
                echo '<span class="text-yellow-600 font-medium">Inter - Pass / Exter - Not Declared</span>';
            } elseif ($internal == 2 && $external == 0) {
                echo '<span class="text-yellow-600 font-medium">Inter - Fail / Exter - Not Declared</span>';
            } elseif ($internal == 0 && $external == 1) {
                echo '<span class="text-yellow-600 font-medium">Inter - Not Declared / Exter - Pass</span>';
            } elseif ($internal == 0 && $external == 2) {
                echo '<span class="text-yellow-600 font-medium">Inter - Not Declared / Exter - Fail</span>';
            } elseif ($internal == 0 && $external == 0) {
                echo '<span class="text-blue-600 font-medium">Inter & Exter - Not Declared</span>';
            } else {
                echo "<span class='text-gray-500'>{$interText} / {$exterText}</span>";
            }
        }
    ?>
</td>

                            <td class="px-4 py-3 font-semibold text-xs">
                            <button 
    class="px-4 py-2 text-black font-semibold rounded-full shadow-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 view-academic"
    style="background-color: #ffff;"
    data-id="<?php echo $row['id']; ?>">
    View
</button>
                    </td>

                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
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
    </main>
    <div id="viewacademicModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-lg shadow-lg w-96 p-6">
        <h2 class="text-lg font-semibold mb-4">Academic Details</h2>

        <div class="mb-2">
            <label class="font-semibold">Course Name: </label>
            <input type="text" id="coursename" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">Semester: </label>
            <input type="text" id="totalSemesters" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">Academic Year: </label>
            <input type="text" id="academicYear" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">Start Date: </label>
            <input type="text" id="startDate" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">End Date: </label>
            <input type="text" id="endDate" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-4">
            <label class="font-semibold">Created By University Admin: </label>
            <input type="text" id="createdBy" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-4">
            <label class="font-semibold">Status: </label>
            <input type="text" id="status" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <button id="closeModal" class="w-full bg-blue-500 text-white py-2 rounded font-semibold mt-2">OK</button>
    </div>
</div>


<script>
document.addEventListener("DOMContentLoaded", () => {
    const viewButtons = document.querySelectorAll(".view-academic");
    const modal = document.getElementById("viewacademicModal");
    const closeModal = document.getElementById("closeModal");

    viewButtons.forEach(button => {
        button.addEventListener("click", () => {
            const academicId = button.dataset.id;

            fetch("fetch_academics_details.php?id=" + academicId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById("coursename").value = data.academic.course_name || '';
                        document.getElementById("totalSemesters").value = data.academic.semester || '';
                        document.getElementById("academicYear").value = data.academic.academic_year || '';
                        document.getElementById("startDate").value = data.academic.start_date ? new Date(data.academic.start_date).toLocaleDateString() : '';
                        document.getElementById("endDate").value = data.academic.end_date ? new Date(data.academic.end_date).toLocaleDateString() : '';
                        document.getElementById("createdBy").value = data.academic.created_by_name || '';
                        document.getElementById("status").value = data.academic.is_editable ? 'Ongoing' : 'Completed';

                        modal.classList.remove("hidden");
                    } else {
                        alert("Failed to fetch academic details.");
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

    <!-- Success Notification Popup -->



</div>


</body>
</html>
