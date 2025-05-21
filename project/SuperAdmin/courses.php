<?php
include "header.php";
?>
<?php
include "connection.php";

// Pagination setup
$limit = 5; // Number of rows per page
$page = isset($_GET['page']) ? intval($_GET['page']) : 1; // Current page
$offset = ($page - 1) * $limit; // Offset for SQL query

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : "";
$searchQuery = $search ? "WHERE name LIKE '%$search%'" : "";

// Fetch total records for pagination
$totalQuery = "SELECT COUNT(*) AS total FROM courses $searchQuery";
$totalResult = $conn->query($totalQuery);
$totalRow = $totalResult->fetch_assoc();
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $limit);

// Fetch paginated data
$query = "SELECT * FROM courses $searchQuery LIMIT $limit OFFSET $offset";
$result = $conn->query($query);

// Fetch course statistics
$totalCoursesQuery = "SELECT COUNT(*) AS total FROM courses";
$activeCoursesQuery = "SELECT COUNT(*) AS active FROM courses WHERE course_status = 1";
$deactiveCoursesQuery = "SELECT COUNT(*) AS deactive FROM courses WHERE course_status = 0";

$totalCoursesResult = $conn->query($totalCoursesQuery);
$activeCoursesResult = $conn->query($activeCoursesQuery);
$deactiveCoursesResult = $conn->query($deactiveCoursesQuery);

$totalCourses = $totalCoursesResult->fetch_assoc()['total'];
$activeCourses = $activeCoursesResult->fetch_assoc()['active'];
$deactiveCourses = $deactiveCoursesResult->fetch_assoc()['deactive'];

$message = isset($_SESSION['message']) ? $_SESSION['message'] : "";
$message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : "";

// Clear the message after displaying it
unset($_SESSION['message'], $_SESSION['message_type']);
?>
<main class="h-full overflow-y-auto">
          <div class="container px-6 mx-auto grid">
            <h2
              class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200"
            >
              All Courses
            </h2>
            <!-- CTA -->

            <!-- Cards -->
            <div class="grid gap-6 mb-8 md:grid-cols-2 xl:grid-cols-4">
              <!-- Card -->
              <div
                class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800"
              >
                <div
                  class="p-3 mr-4 text-orange-500 bg-orange-100 rounded-full dark:text-orange-100 dark:bg-orange-500"
                >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
  <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V4a2 2 0 00-2-2H6zm0 2h8v12H6V4zm2 2h4v2H8V6zm0 4h4v2H8v-2z" clip-rule="evenodd"></path>
</svg>


                </div>
                <div>
                  <p
                    class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400"
                  >
                    Total Courses
                  </p>
                  <p
                    class="text-lg font-semibold text-gray-700 dark:text-gray-200"
                  >
                  <?php echo $totalCourses; ?>
                  </p>
                </div>
              </div>
              <!-- Card -->
              <div
                class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800"
              >
                <div
                  class="p-3 mr-4 text-green-500 bg-green-100 rounded-full dark:text-green-100 dark:bg-green-500"
                >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
  <path fill-rule="evenodd" d="M10 2a8 8 0 110 16 8 8 0 010-16zm-1 9.293l3.293-3.293a1 1 0 011.414 1.414l-4 4a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L9 11.293z" clip-rule="evenodd"></path>
</svg>

                </div>
                <div>
                  <p
                    class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400"
                  >
                    Active Courses
                  </p>
                  <p
                    class="text-lg font-semibold text-gray-700 dark:text-gray-200"
                  >
                  <?php echo $activeCourses; ?>
                  </p>
                </div>
              </div>
              <!-- Card -->
              <div
                class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800"
              >
                <div
                  class="p-3 mr-4 text-blue-500 bg-blue-100 rounded-full dark:text-blue-100 dark:bg-blue-500"
                >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
  <path fill-rule="evenodd" d="M10 2a8 8 0 110 16 8 8 0 010-16zm3.293 6.293a1 1 0 10-1.414-1.414L10 8.586 8.121 6.707a1 1 0 10-1.414 1.414L8.586 10l-1.879 1.879a1 1 0 101.414 1.414L10 11.414l1.879 1.879a1 1 0 101.414-1.414L11.414 10l1.879-1.879z" clip-rule="evenodd"></path>
</svg>

                </div>
                <div>
                  <p
                    class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400"
                  >
                    Deacitve Courses
                  </p>
                  <p
                    class="text-lg font-semibold text-gray-700 dark:text-gray-200"
                  >
                  <?php echo $deactiveCourses; ?>
                  </p>
                </div>
              </div>
              <!-- Card -->
            </div>

            <h4 class="mb-4 text-lg font-semibold text-gray-600 dark:text-gray-300">
    All Courses List
</h4>
<div class="w-full mb-8 overflow-hidden rounded-lg shadow-xs">
    
    <!-- Table Wrapper -->
    <div class="w-full overflow-x-auto">
        <!-- Search Form -->
        <div class="flex justify-between items-center mb-4">
    <!-- Left: Add Course Button -->
    <a 
        href="add_courses.php" 
        class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-md shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2"
    >
        + Add Course
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
            placeholder="Search courses..." 
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

        <!-- Courses Table -->
        <table class="w-full whitespace-no-wrap">
    <thead>
        <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800">
            <th class="px-4 py-3">SR NO.</th>
            <th class="px-4 py-3">COURSE NAME</th>
            <th class="px-4 py-3">NO. OF SEMESTERS</th>
            <th class="px-4 py-3">DURATION (IN YEARS)</th>
            <th class="px-4 py-3">STATUS</th>
            <th class="px-4 py-3">ACTION</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
        <?php if ($result->num_rows > 0): ?>
            <?php $srNo = $offset + 1; ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr class="text-gray-700 dark:text-gray-400">
                    <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400"><?php echo $srNo++; ?></td>
                    <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($row['name']); ?></td>
                    <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400"><?php echo $row['total_semesters']; ?></td>
                    <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400"><?php echo $row['duration']; ?></td>
                    <td class="px-4 py-3 font-semibold text-xs">
                        <button 
                            class="px-4 py-2 font-semibold rounded-full shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 status-toggle"
                            data-id="<?php echo $row['course_id']; ?>"
                            data-status="<?php echo $row['course_status']; ?>"
                            style="background-color: <?php echo $row['course_status'] ? '#10b981' : '#ef4444'; ?>; color: white;">
                            <?php echo $row['course_status'] ? 'Active' : 'Inactive'; ?>
                        </button>
                    </td>
                    <td class="px-4 py-3 font-semibold text-xs">
                    <a href="update_courses.php?id=<?php echo $row['course_id']; ?>" 
   class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-full shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">
   Edit
</a>
              <button 
                  class="px-4 py-2 text-black font-semibold rounded-full shadow-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 view-course"
                  style="background-color: #ffff;"
                  data-id="<?php echo $row['course_id']; ?>">
                  View
              </button>

              <!-- <button 
                  class="px-4 py-2 text-white font-semibold rounded-full shadow-md hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2 delete-course"
                  style="background-color: #ef4444;"
                  data-id="<?php echo $row['course_id']; ?>"
                  data-name="<?php echo htmlspecialchars($row['name']); ?>"
                  data-semesters="<?php echo $row['total_semesters']; ?>"
                  data-duration="<?php echo $row['duration']; ?>">
                  Delete
              </button> -->


                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">No courses found</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

    </div>

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

    <!-- Success Notification Popup -->
<div id="deleteSuccessPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 text-center">Course Deleted</h2>
        <p class="text-gray-600 dark:text-gray-300 text-center">The course has been deleted successfully.</p>
        <div class="mt-6 flex justify-center">
            <button id="closeDeleteSuccess" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                OK
            </button>
        </div>
    </div>
</div>


<!-- Delete Confirmation Popup -->
<div id="deletePopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96 relative">
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 text-center">Confirm Deletion</h2>

        <!-- Course Details -->
        <p class="text-gray-600 dark:text-gray-300"><strong>Course:</strong> <span id="popupCourseName"></span></p>
        <p class="text-gray-600 dark:text-gray-300"><strong>Semesters:</strong> <span id="popupCourseSemesters"></span></p>
        <p class="text-gray-600 dark:text-gray-300"><strong>Duration:</strong> <span id="popupCourseDuration"></span> Years</p>

        <div class="mt-6 flex justify-end space-x-3">
            <button id="cancelDelete" class="px-4 py-2 bg-gray-500 text-black rounded-md hover:bg-gray-600">
                Cancel
            </button>
            <button id="confirmDelete" class="px-4 py-2 bg-red-500 text-black rounded-md hover:bg-red-600">
                Confirm
            </button>
        </div>
    </div>
</div>


<!-- Status Confirmation Popup -->
<div id="statusPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 text-center">Confirm Status Change</h2>
        <p class="text-gray-600 dark:text-gray-300 text-center">
            Are you sure you want to <span id="statusActionText" class="font-bold"></span> this course?
        </p>
        <div class="mt-6 flex justify-center space-x-3">
            <button id="cancelStatusChange" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">
                Cancel
            </button>
            <button id="confirmStatusChange" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                Confirm
            </button>
        </div>
    </div>
</div>

<!-- Status Success Popup -->
<div id="statusSuccessPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 text-center">Status Updated</h2>
        <p class="text-gray-600 dark:text-gray-300 text-center">
            The course status has been updated successfully.
        </p>
        <div class="mt-6 flex justify-center">
            <button id="closeStatusSuccess" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                OK
            </button>
        </div>
    </div>
</div>

<!-- View Course Popup -->
<!-- Course Details Modal -->
<div id="viewCourseModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-lg shadow-lg w-96 p-6">
        <h2 class="text-lg font-semibold mb-4">Course Details</h2>
        
        <div class="mb-2">
            <label class="font-semibold">Course Name:</label>
            <input type="text" id="courseName" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">No. of Semesters:</label>
            <input type="text" id="totalSemesters" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">Duration (Years):</label>
            <input type="text" id="courseDuration" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">Created By:</label>
            <input type="text" id="createdBy" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-4">
            <label class="font-semibold">Creator Email:</label>
            <input type="text" id="creatorEmail" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <button id="closeModal" class="w-full bg-blue-500 text-white py-2 rounded font-semibold">OK</button>
    </div>
</div>





</div>

<?php $conn->close(); ?>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const toggleButtons = document.querySelectorAll(".status-toggle");
    const statusPopup = document.getElementById("statusPopup");
    const statusSuccessPopup = document.getElementById("statusSuccessPopup");
    const cancelStatusChange = document.getElementById("cancelStatusChange");
    const confirmStatusChange = document.getElementById("confirmStatusChange");
    const closeStatusSuccess = document.getElementById("closeStatusSuccess");
    const statusActionText = document.getElementById("statusActionText");

    let selectedCourseId = null;
    let newStatus = null;
    let currentButton = null;

    toggleButtons.forEach(button => {
        button.addEventListener("click", () => {
            selectedCourseId = button.dataset.id;
            newStatus = button.dataset.status === "1" ? 0 : 1;
            currentButton = button;

            // Update popup text dynamically
            statusActionText.textContent = newStatus === 1 ? "Activate" : "Deactivate";

            // Show confirmation popup
            statusPopup.classList.remove("hidden");
        });
    });

    cancelStatusChange.addEventListener("click", () => {
        statusPopup.classList.add("hidden");
    });

    confirmStatusChange.addEventListener("click", () => {
        if (selectedCourseId !== null) {
            fetch("update_course_status.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    id: selectedCourseId,
                    status: newStatus,
                }),
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update button style and text
                        currentButton.dataset.status = data.newStatus;
                        currentButton.style.backgroundColor = data.newStatus === 1 ? "#10b981" : "#ef4444";
                        currentButton.textContent = data.newStatus === 1 ? "Active" : "Inactive";

                        // Hide confirmation popup and show success popup
                        statusPopup.classList.add("hidden");
                        statusSuccessPopup.classList.remove("hidden");
                    } else {
                        alert("Failed to update course status.");
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("An error occurred.");
                });
        }
    });

    closeStatusSuccess.addEventListener("click", () => {
        statusSuccessPopup.classList.add("hidden");
    });
});

</script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const deleteButtons = document.querySelectorAll(".delete-course");
    const popup = document.getElementById("deletePopup");
    const cancelDelete = document.getElementById("cancelDelete");
    const confirmDelete = document.getElementById("confirmDelete");
    const deleteSuccessPopup = document.getElementById("deleteSuccessPopup");
    const closeDeleteSuccess = document.getElementById("closeDeleteSuccess");

    let selectedCourseId = null;

    deleteButtons.forEach(button => {
        button.addEventListener("click", () => {
            document.getElementById("popupCourseName").textContent = button.dataset.name;
            document.getElementById("popupCourseSemesters").textContent = button.dataset.semesters;
            document.getElementById("popupCourseDuration").textContent = button.dataset.duration;

            selectedCourseId = button.dataset.id;
            popup.classList.remove("hidden");
        });
    });

    cancelDelete.addEventListener("click", () => popup.classList.add("hidden"));

    confirmDelete.addEventListener("click", () => {
        if (selectedCourseId) {
            fetch("delete_course.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id: selectedCourseId }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    popup.classList.add("hidden");
                    deleteSuccessPopup.classList.remove("hidden");
                } else {
                    alert("Failed to delete course.");
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("An error occurred.");
            });
        }
    });

    closeDeleteSuccess.addEventListener("click", () => {
        deleteSuccessPopup.classList.add("hidden");
        location.reload(); // Reload the page only after user acknowledges
    });
});


</script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const viewButtons = document.querySelectorAll(".view-course");
    const modal = document.getElementById("viewCourseModal");
    const closeModal = document.getElementById("closeModal");

    viewButtons.forEach(button => {
        button.addEventListener("click", () => {
            const courseId = button.dataset.id;

            fetch("fetch_course_details.php?id=" + courseId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById("courseName").value = data.course.name;
                        document.getElementById("totalSemesters").value = data.course.total_semesters;
                        document.getElementById("courseDuration").value = data.course.duration;
                        document.getElementById("createdBy").value = data.course.full_name;
                        document.getElementById("creatorEmail").value = data.course.email;
                        modal.classList.remove("hidden");
                    } else {
                        alert("Failed to fetch course details.");
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



</body>
</html>
