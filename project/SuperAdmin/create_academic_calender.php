<?php
ob_start();
include "header.php";

if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "Unauthorized access! Please log in as Super-Admin.";
    $_SESSION['message_type'] = "error";
    header("Location: index.php");
    exit();
}

include("connection.php");

$created_by = $_SESSION['user_id'];

$query_courses = "SELECT course_id, name, total_semesters, duration, created_at, course_status, created_by FROM courses WHERE course_status = 1";
$result_courses = $conn->query($query_courses);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $course_id = (int)$_POST['course_id'];
    $semester = (int)$_POST['semester'];
    $academic_year = $_POST['academic_year'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // Validate academic year format
    if (!preg_match("/^\d{4}-\d{2}$/", $academic_year)) {
        $_SESSION['message'] = "Invalid academic year format.";
        $_SESSION['message_type'] = "error";
        header("Location: academic_calendar.php");
        exit();
    }

    // Validate date range within academic year
    $start_year = (int)substr($academic_year, 0, 4);
    $end_year = $start_year + 1;

    $min_date = "$start_year-01-01";
    $max_date = "$end_year-12-31";

    if ($start_date < $min_date || $start_date > $max_date || $end_date < $min_date || $end_date > $max_date) {
        $_SESSION['message'] = "Start and end date must be within academic year range.";
        $_SESSION['message_type'] = "error";
        header("Location: academic_calendar.php");
        exit();
    }

    // Prevent duplicate semester for same course + academic year + semester
    $check_sem = "SELECT id FROM academic_calendar WHERE course_id = ? AND academic_year = ? AND semester = ?";
    $stmt_sem = $conn->prepare($check_sem);
    $stmt_sem->bind_param("isi", $course_id, $academic_year, $semester);
    $stmt_sem->execute();
    $stmt_sem->store_result();
    if ($stmt_sem->num_rows > 0) {
        $_SESSION['message'] = "Semester already exists for this academic year.";
        $_SESSION['message_type'] = "error";
        header("Location: academic_calendar.php");
        exit();
    }
    $stmt_sem->close();

    // Insert calendar
    $stmt = $conn->prepare("INSERT INTO academic_calendar (course_id, semester, academic_year, start_date, end_date, created_by) 
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissss", $course_id, $semester, $academic_year, $start_date, $end_date, $created_by);

    if ($stmt->execute()) {
        $calendar_id = $stmt->insert_id;

        // ✅ Match and update students registered earlier with NULL academic_year
        $update_students_query = "
            SELECT u.user_id as user_id
            FROM users u
            JOIN student_academics sa ON sa.user_id = u.user_id
            WHERE u.course_id = ?
              AND u.current_semester = ?
              AND sa.current_academic_year IS NULL
              AND DATE(u.created_at) BETWEEN ? AND ?
        ";
        $stmt_students = $conn->prepare($update_students_query);
        $stmt_students->bind_param("iiss", $course_id, $semester, $start_date, $end_date);
        $stmt_students->execute();
        $result_students = $stmt_students->get_result();

        while ($student = $result_students->fetch_assoc()) {
            $user_id = $student['user_id'];

            // Update student_academics
            $conn->query("UPDATE student_academics SET current_academic_year = '$academic_year' WHERE user_id = $user_id");

            // Update student_semester_result
            $conn->query("UPDATE student_semester_result 
                          SET academic_year = '$academic_year' 
                          WHERE user_id = $user_id AND course_id = $course_id AND semester = $semester");
        }

        $_SESSION['message'] = "Academic calendar scheduled successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error scheduling academic calendar.";
        $_SESSION['message_type'] = "error";
    }

    $stmt->close();
    header("Location: academic_calendar.php");
    exit();
}
?>



<main class="h-full overflow-y-auto mt-8">
    <div class="container px-6 mx-auto grid">

        <?php if (isset($_SESSION['message'])) { ?>
            <div class="p-3 mb-3 text-sm text-<?php echo $_SESSION['message_type'] == 'success' ? 'green' : 'red'; ?>-700 bg-<?php echo $_SESSION['message_type'] == 'success' ? 'green' : 'red'; ?>-100 rounded">
                <?php echo $_SESSION['message']; ?>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        <?php } ?>

        <div class="container mx-auto p-6 mt-8">
            <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-200">
                <h1 class="text-xl font-semibold mb-4">Schedule Academic Calendar</h1>
                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="course_id" class="block text-sm font-medium text-gray-700">Select Course</label>
                        <select name="course_id" id="course_id" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select Course</option>
                            <?php while ($row = $result_courses->fetch_assoc()) { ?>
                                <option value="<?php echo $row['course_id']; ?>" data-total-semesters="<?php echo $row['total_semesters']; ?>" 
                                        data-duration="<?php echo $row['duration']; ?>" data-created-at="<?php echo $row['created_at']; ?>"
                                        data-course-status="<?php echo $row['course_status']; ?>" data-created-by="<?php echo $row['created_by']; ?>">
                                    <?php echo $row['name']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="semester" class="block text-sm font-medium text-gray-700">Select Semester</label>
                        <select name="semester" id="semester" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select Semester</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="academic_year" class="block text-sm font-medium text-gray-700">Select Academic Year</label>
                        <select name="academic_year" id="academic_year" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select Academic Year</option>
                            <?php for ($i = 2000; $i <= 4000; $i++) {
                                $next = $i + 1;
                                $display = $i . '-' . substr($next, -2); ?>
                                <option value="<?php echo $display; ?>"><?php echo $display; ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input type="date" name="start_date" id="start_date" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="mb-4">
                        <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                        <input type="date" name="end_date" id="end_date" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="flex justify-end">
                        <a href="academic_calendar.php" 
                            class="px-4 py-2 mr-2 bg-gray-500 text-black font-semibold rounded-lg shadow-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
                            Cancel
                        </a>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-lg shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">
                                Schedule Calendar
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</main>

<!-- JavaScript -->
<script>
    document.getElementById('course_id').addEventListener('change', function () {
        const courseId = this.value;
        const semesterDropdown = document.getElementById('semester');

        if (courseId) {
            const selectedOption = this.options[this.selectedIndex];
            const totalSemesters = selectedOption.getAttribute('data-total-semesters');

            semesterDropdown.innerHTML = '';
            for (let i = 1; i <= totalSemesters; i++) {
                const option = document.createElement("option");
                option.value = i;
                option.text = 'Semester ' + i;
                semesterDropdown.appendChild(option);
            }
        } else {
            semesterDropdown.innerHTML = '<option value="">Select Semester</option>';
        }
    });

    document.getElementById('academic_year').addEventListener('change', function () {
        const value = this.value;
        if (value.match(/^\d{4}-\d{2}$/)) {
            const startYear = parseInt(value.split('-')[0]);
            const endYear = startYear + 1;

            document.getElementById('start_date').setAttribute('min', `${startYear}-01-01`);
            document.getElementById('start_date').setAttribute('max', `${endYear}-12-31`);
            document.getElementById('end_date').setAttribute('min', `${startYear}-01-01`);
            document.getElementById('end_date').setAttribute('max', `${endYear}-12-31`);
        }
    });
</script>
