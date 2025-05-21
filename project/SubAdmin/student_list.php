<?php
include('header.php');

// Fetch user_id from session
$user_id = $_SESSION['user_id'];

// Fetch college_id and course_id from users table
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
    $search = $_GET['search'];
    $searchQuery = " AND (u.full_name LIKE '%$search%' OR u.email LIKE '%$search%')";
} else {
    $searchQuery = "";
}

// Fetch Students along with academic year info
$query = "
    SELECT 
        u.user_id,
        u.full_name AS student_name,
        u.email AS student_email,
        u.phone_number AS student_phone,
        u.role AS student_role,
        u.access_status,
        u.users_status,
        u.current_semester,
        u.created_at,
        c.name AS course_name,
        sa.current_academic_year
    FROM users u
    LEFT JOIN courses c ON u.course_id = c.course_id
    LEFT JOIN student_academics sa 
        ON u.user_id = sa.user_id 
        AND sa.course_id = u.course_id 
        AND sa.current_semester = u.current_semester
    WHERE u.role = 5 
      AND u.college_id = $college_id 
      AND u.course_id = $course_id
      $searchQuery
    GROUP BY u.user_id
    LIMIT $limit OFFSET $offset";
$result = $conn->query($query);

// Fetch data into array
$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

// Total Records Count for Pagination
$countQuery = "SELECT COUNT(*) as total FROM users u WHERE u.role = 5 AND u.college_id = $college_id AND u.course_id = $course_id $searchQuery";
$countResult = $conn->query($countQuery);
$countRow = $countResult->fetch_assoc();
$totalRecords = $countRow['total'];
$totalPages = ceil($totalRecords / $limit);
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
                    <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">Total Students</p>
                    <p class="text-lg font-semibold text-gray-700 dark:text-gray-200"><?php echo $totalRecords; ?></p>
                </div>
            </div>
        </div>

        <h4 class="mb-4 text-lg font-semibold text-gray-600 dark:text-gray-300">Students List</h4>
        
        <div class="w-full mb-8 overflow-hidden rounded-lg shadow-xs">
            <div class="w-full overflow-x-auto">
                <!-- Search Form -->
                <div class="flex justify-between items-center mb-4">
                    <div class="w-1/3">
                    <a 
        href="manual_select_academic.php" 
        class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-md shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 mr-2"
    >
        + Manual Select Academic 
    </a>
    <a 
        href="student_data.php" 
        class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-md shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2"
    >
        All Student Data 
    </a>
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
                            <th class="px-4 py-3">STATUS</th>
                            <th class="px-4 py-3">ACCESS</th>
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
                                    <button 
                                        class="px-4 py-2 font-semibold rounded-full shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 status-toggle"
                                        data-id="<?php echo $student['user_id']; ?>"
                                        data-status="<?php echo $student['users_status']; ?>"
                                        style="background-color: <?php echo ($student['users_status'] == 1) ? '#10b981' : '#ef4444'; ?>;
                                               color: <?php echo ($student['users_status'] == 1) ? 'white' : 'white'; ?>;">
                                        <?php echo ($student['users_status'] == 1) ? 'Active' : 'Inactive'; ?>
                                    </button>
                                </td>
                                <td class="px-4 py-3 font-semibold text-xs">
                                <?php if ($student['access_status'] == 0): ?>
        <!-- Disable the button if access is restricted -->
        <button class="px-4 py-2 font-semibold rounded-full shadow-md bg-red-500 text-black cursor-not-allowed" disabled>
            Restricted
        </button>
    <?php else: ?>
                                    <button 
                                        class="px-4 py-2 font-semibold rounded-full shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 access-toggle"
                                        data-id="<?php echo $student['user_id']; ?>"
                                        data-status="<?php echo $student['access_status']; ?>"
                                        style="background-color: <?php echo $student['access_status'] ? '#10b981' : '#ef4444'; ?>; color: white;">
                                        <?php echo $student['access_status'] ? 'Access' : 'Restricted'; ?>
                                    </button>
                                    <?php endif; ?>
                                </td>  
                                <td class="px-4 py-3 font-semibold text-xs"> <?= $student['current_academic_year'] ?? '<span class="text-red-500">N/A</span>' ?></td>
                                <td class="px-4 py-3 font-semibold text-xs">
                                    <button 
                                        class="px-4 py-2 text-white bg-blue-500 font-semibold rounded-full shadow-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 view-Student"
                                        data-id="<?php echo $student['user_id']; ?>"> <!-- Correct variable for Student -->
                                        View
                                    </button>
                               
                                    <a href="update_student.php?id=<?php echo $student['user_id']; ?>" 
   class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-full shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">
   Edit
</a>
                            
<button 
    class="px-4 py-2 text-white bg-blue-500 font-semibold rounded-full shadow-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 update-Semester"
    data-id="<?php echo $student['user_id']; ?>"> 
    Update
</button>

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

<!-- Modal for updating semester -->
<div id="updateSemesterModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-lg shadow-lg w-96 p-6">
        <h2 class="text-lg font-semibold mb-4">Update Semester</h2>
        
        <div class="mb-2">
            <label class="font-semibold">Student Name:</label>
            <input type="text" id="updateStudentName" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">Email:</label>
            <input type="text" id="updateStudentEmail" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">Course:</label>
            <input type="text" id="updateStudentCourse" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">Current Semester:</label>
            <input type="text" id="currentSemester" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">Update Semester:</label>
            <select id="semesterDropdown" class="w-full px-3 py-2 border rounded">
                <option value="">Select Semester</option>
            </select>
        </div>

        <div class="mb-2">
            <label class="font-semibold">Warning (Optional):</label>
            <input type="text" id="warningField" class="w-full px-3 py-2 border rounded bg-gray-100" placeholder="Enter warning message (optional)">
        </div>

        <button id="confirmUpdate" class="w-full bg-blue-500 text-white py-2 rounded font-semibold">Update</button>
        <button id="closeUpdateModal" class="w-full bg-gray-500 text-black py-2 rounded font-semibold mt-2">Cancel</button>
    </div>
</div>

<!-- Custom confirmation popup -->
<div id="statusConfirmPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-semibold text-gray-700 mb-4 text-center">Confirm Update</h2>
        <p id="statusConfirmMessage" class="text-gray-600 text-center mb-4"></p>
        <div class="flex justify-center space-x-4">
            <button id="confirmYes" class="px-4 py-2 bg-blue-500 text-white rounded-md">Yes</button>
            <button id="confirmNo" class="px-4 py-2 bg-gray-500 text-black rounded-md">No</button>
        </div>
    </div>
</div>

<!-- Confirmation Popup -->

<!-- Custom success popup -->
<div id="statusSuccessPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-semibold text-gray-700 mb-4 text-center">Success</h2>
        <p id="statusSuccessMessage" class="text-gray-600 text-center"></p>
        <div class="flex justify-center mt-4">
            <button id="closeStatusSuccess" class="px-4 py-2 bg-blue-500 text-white rounded-md">OK</button>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const updateButtons = document.querySelectorAll(".update-Semester");
    const updateModal = document.getElementById("updateSemesterModal");
    const closeUpdateModal = document.getElementById("closeUpdateModal");
    const confirmUpdate = document.getElementById("confirmUpdate");
    const statusConfirmPopup = document.getElementById("statusConfirmPopup");
    const statusSuccessPopup = document.getElementById("statusSuccessPopup");
    const confirmYes = document.getElementById("confirmYes");
    const confirmNo = document.getElementById("confirmNo");

    let selectedStudentId = null;
    let selectedSemester = null;
    let selectedWarningMessage = null; // To store warning text

    // Trigger when Update button is clicked
    updateButtons.forEach(button => {
        button.addEventListener("click", () => {
            selectedStudentId = button.dataset.id;

            fetch("fetch_student_details.php?id=" + selectedStudentId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Populate student data for updating
                        document.getElementById("updateStudentName").value = data.student.full_name;
                        document.getElementById("updateStudentEmail").value = data.student.email;
                        document.getElementById("updateStudentCourse").value = data.student.course_name;
                        document.getElementById("currentSemester").value = data.student.current_semester;

                        // Populate semester dropdown
                        const semesterDropdown = document.getElementById("semesterDropdown");
                        semesterDropdown.innerHTML = '<option value="">Select Semester</option>';
                        const totalSemesters = data.total_semesters[0].total_semesters;
                        for (let i = 1; i <= totalSemesters; i++) {
                            const option = document.createElement("option");
                            option.value = i;
                            option.textContent = "Semester " + i;
                            semesterDropdown.appendChild(option);
                        }

                        updateModal.classList.remove("hidden");
                    } else {
                        alert("Failed to fetch student details.");
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("An error occurred.");
                });
        });
    });

    // Close the modal when the "Cancel" button is clicked
    closeUpdateModal.addEventListener("click", () => {
        updateModal.classList.add("hidden");
    });

    // When "Update" button inside the modal is clicked
    confirmUpdate.addEventListener("click", () => {
        selectedSemester = document.getElementById("semesterDropdown").value;
        selectedWarningMessage = document.getElementById("warningField").value;  // Get the warning message

        if (!selectedSemester) {
            alert("Please select a semester.");
            return;
        }

        // Display confirmation popup
        const studentName = document.getElementById("updateStudentName").value;
        const currentSemester = document.getElementById("currentSemester").value;
        const confirmMessage = `Are you sure you want to update ${studentName} from semester ${currentSemester} to semester ${selectedSemester}?`;
        document.getElementById("statusConfirmMessage").textContent = confirmMessage;

        statusConfirmPopup.classList.remove("hidden");
    });

    // Handle confirmation (Yes/No)
    confirmYes.addEventListener("click", () => {
        // Proceed with the semester update
        const formData = new FormData();
        formData.append('student_id', selectedStudentId);
        formData.append('new_semester', selectedSemester);
        formData.append('warning', selectedWarningMessage);  // Include the warning field in the request

        fetch("update_semester.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hide confirmation popup
                statusConfirmPopup.classList.add("hidden");

                // Show success popup
                document.getElementById("statusSuccessMessage").textContent = `Semester updated to ${selectedSemester} successfully!`;
                statusSuccessPopup.classList.remove("hidden");

                // Close the update modal after success
                updateModal.classList.add("hidden");

                // Reload or handle accordingly after success
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("An error occurred.");
        });
    });

    // Cancel status change (No button)
    confirmNo.addEventListener("click", () => {
        statusConfirmPopup.classList.add("hidden");
    });

    // Close success popup (OK button)
    document.getElementById("closeStatusSuccess").addEventListener("click", () => {
        statusSuccessPopup.classList.add("hidden");
    });
});


</script>
<!-- Popup for Status Change Confirmation -->
<div id="statusPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 text-center">Confirm Status Change</h2>
        <p class="text-gray-600 dark:text-gray-300 text-center">
            Are you sure you want to <span id="statusActionText" class="font-bold"></span> this student?
        </p>

        <!-- Status Change Form -->
        <form id="statusChangeForm" class="mt-6 space-y-4">
            <div>
                <label for="subject" class="block text-gray-700 dark:text-gray-200">Subject</label>
                <input type="text" id="subject" name="subject" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-md border border-gray-300 dark:border-gray-600" required>
            </div>
            <div>
                <label for="body" class="block text-gray-700 dark:text-gray-200">Body</label>
                <textarea id="body" name="body" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-md border border-gray-300 dark:border-gray-600" rows="4" required></textarea>
            </div>
            <div>
                <label for="warning" class="block text-gray-700 dark:text-gray-200">Warning (Optional)</label>
                <textarea id="warning" name="warning" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-md border border-gray-300 dark:border-gray-600" rows="2"></textarea>
            </div>
            <div>
                <label for="attachments" class="block text-gray-700 dark:text-gray-200">Attachment (Optional)</label>
                <input type="file" id="attachments" name="attachments[]" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-md border border-gray-300 dark:border-gray-600" multiple>
            </div>
        </form>

        <div class="mt-6 flex justify-center space-x-3">
            <button id="cancelStatusChange" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">Cancel</button>
            <button id="confirmStatusChange" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">Confirm</button>
        </div>
    </div>
</div>

<div id="statusSuccessPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-semibold text-gray-700 mb-4 text-center">Success</h2>
        <p id="statusSuccessMessage" class="text-gray-600 text-center"></p>
        <div class="flex justify-center mt-4">
            <button id="closeStatusSuccess" class="px-4 py-2 bg-blue-500 text-white rounded-md">OK</button>
        </div>
    </div>
</div>

<script>
// Show the Status Popup when 'active/inactive' button is clicked
document.querySelectorAll('.status-toggle').forEach(button => {
    button.addEventListener('click', function() {
        const adminId = this.dataset.id;
        const statusAction = this.dataset.status == '1' ? 'deactivate' : 'activate';
        triggerStatusChange(adminId, statusAction);
    });
});

// Show success popup after status change
function showStatusSuccessPopup(message) {
    const statusSuccessPopup = document.getElementById('statusSuccessPopup');
    const statusSuccessMessage = document.getElementById('statusSuccessMessage');
    
    // Set success message
    statusSuccessMessage.textContent = message;

    // Show success popup
    statusSuccessPopup.classList.remove('hidden');

    // Close success popup and redirect to students_list.php when OK is clicked
    document.getElementById('closeStatusSuccess').onclick = function () {
        statusSuccessPopup.classList.add('hidden');
        window.location.href = 'student_list.php';  // Redirect to students_list.php
    };
}

// Handle confirmation of the status change
function showStatusPopup(adminId, statusAction) {
    const statusPopup = document.getElementById('statusPopup');
    const statusActionText = document.getElementById('statusActionText');
    const confirmButton = document.getElementById('confirmStatusChange');

    // Set action text based on status
    statusActionText.textContent = statusAction === 'activate' ? 'activate' : 'deactivate';

    // Show the confirmation popup
    statusPopup.classList.remove('hidden');

    // On confirm button click, handle the status change
    confirmButton.onclick = function () {
        // Get form data
        const form = document.getElementById('statusChangeForm');
        const formData = new FormData(form);

        // Add adminId and statusAction to the form data
        formData.append('student_id', adminId);
        formData.append('status_action', statusAction === 'activate' ? 1 : 0);

        // Send an AJAX request to the server to update status and send email
        fetch('update_student_status.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())  // Get the raw response text first
        .then(data => {
            console.log(data);  // Log the response to check what is returned
            try {
                const jsonResponse = JSON.parse(data);  // Attempt to parse it as JSON

                if (jsonResponse.success) {
                    // Success: Close the status popup and show success message
                    statusPopup.classList.add('hidden');
                    showStatusSuccessPopup(jsonResponse.message);
                } else {
                    // Failure: Show the error message
                    alert('Error: ' + jsonResponse.error);
                }
            } catch (e) {
                // If the response is not valid JSON (likely HTML), show the raw response
                alert('Unexpected response: ' + data);
            }
        })
        .catch(error => {
            // If there’s an error with the fetch request itself
            alert('Error: ' + error.message);
        });
    };
}

// Close the status popup when Cancel is clicked
document.getElementById('cancelStatusChange').onclick = function () {
    document.getElementById('statusPopup').classList.add('hidden');
};

// Trigger the status change for a specific admin
function triggerStatusChange(adminId, action) {
    showStatusPopup(adminId, action);
}


</script>

<div id="accessPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 text-center">Confirm Access Change</h2>
        <p class="text-gray-600 dark:text-gray-300 text-center">
            Are you sure you want to <span id="accessActionText" class="font-bold"></span> this Sub-Admin?
        </p>

        <!-- Access Change Form -->
        <form id="accessChangeForm" class="mt-6 space-y-4">
            <div>
                <label for="subject" class="block text-gray-700 dark:text-gray-200">Subject</label>
                <input type="text" id="subject" name="subject" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-md border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div>
                <label for="body" class="block text-gray-700 dark:text-gray-200">Body</label>
                <textarea id="body" name="body" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-md border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" rows="4" required></textarea>
            </div>
            <div>
                <label for="warning" class="block text-gray-700 dark:text-gray-200">Warning (Optional)</label>
                <textarea id="warning" name="warning" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-md border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" rows="2"></textarea>
            </div>
            <div>
                <label for="attachments" class="block text-gray-700 dark:text-gray-200">Attachment (Optional)</label>
                <input type="file" id="attachments" name="attachments[]" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-md border border-gray-300 dark:border-gray-600" multiple>
            </div>
        </form>

        <div class="mt-6 flex justify-center space-x-3">
            <button id="cancelAccessChange" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">
                Cancel
            </button>
            <button id="confirmAccessChange" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                Confirm
            </button>
        </div>
    </div>
</div>

<!-- Access Success Popup -->
<div id="accessSuccessPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 text-center">Access Updated</h2>
        <p class="text-gray-600 dark:text-gray-300 text-center">
            The Sub-Admin access has been updated successfully.
        </p>
        <p class="text-gray-600 dark:text-gray-300 text-center mt-2" id="accessSuccessMessage"></p> <!-- Success message for email sent -->
        <div class="mt-6 flex justify-center">
            <button id="closeAccessSuccess" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                OK
            </button>
        </div>
    </div>
</div>

<script>
// Show the Access Status Popup when 'Access/Restricted' button is clicked
document.querySelectorAll('.access-toggle').forEach(button => {
    button.addEventListener('click', function() {
        const userId = this.dataset.id;
        const accessAction = this.dataset.status == '1' ? 'restrict' : 'grant';
        triggerAccessChange(userId, accessAction);
    });
});

// Show the confirmation popup
function showAccessPopup(userId, accessAction) {
    const accessPopup = document.getElementById('accessPopup');
    const accessActionText = document.getElementById('accessActionText');
    const confirmButton = document.getElementById('confirmAccessChange');

    // Set action text based on status
    accessActionText.textContent = accessAction === 'grant' ? 'grant access to' : 'restrict access for';

    // Show the confirmation popup
    accessPopup.classList.remove('hidden');

    // On confirm button click, handle the access change
    confirmButton.onclick = function () {
        // Get form data
        const form = document.getElementById('accessChangeForm');
        const formData = new FormData(form);

        // Add userId and accessAction to the form data
        formData.append('user_id', userId);
        formData.append('access_action', accessAction === 'grant' ? 1 : 0);

        // Send an AJAX request to the server to update access and send email
        fetch('update_student_access.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())  // Get raw response text first
        .then(data => {
            console.log(data);  // Log response to check output
            try {
                const jsonResponse = JSON.parse(data);  // Parse as JSON

                if (jsonResponse.success) {
                    // Success: Close the access popup and show success message
                    accessPopup.classList.add('hidden');
                    showAccessSuccessPopup(jsonResponse.message);
                } else {
                    // Failure: Show error message
                    alert('Error: ' + jsonResponse.error);
                }
            } catch (e) {
                // If response is not valid JSON, show raw response
                alert('Unexpected response: ' + data);
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        });
    };
}

// Show success popup after access status change
function showAccessSuccessPopup(message) {
    const accessSuccessPopup = document.getElementById('accessSuccessPopup');
    const accessSuccessMessage = document.getElementById('accessSuccessMessage');
    
    // Set success message
    accessSuccessMessage.textContent = message;

    // Show success popup
    accessSuccessPopup.classList.remove('hidden');

    // Close success popup and refresh page when OK is clicked
    document.getElementById('closeAccessSuccess').onclick = function () {
        accessSuccessPopup.classList.add('hidden');
        window.location.href = 'student_list.php';  // Redirect to user list page
    };
}

// Close the access popup when Cancel is clicked
document.getElementById('cancelAccessChange').onclick = function () {
    document.getElementById('accessPopup').classList.add('hidden');
};

// Trigger the access change for a specific user
function triggerAccessChange(userId, action) {
    showAccessPopup(userId, action);
}

</script>

    </div>
</main>
