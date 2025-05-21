<?php
include "header.php";
include "connection.php";
// Auto update academic calendars where end_date is less than today
$today = date('Y-m-d');
$updateQuery = "UPDATE academic_calendar SET is_editable = 0 WHERE end_date < '$today' AND is_editable = 1";
$conn->query($updateQuery);

// Pagination setup
$limit = 5; // Number of rows per page
$page = isset($_GET['page']) ? intval($_GET['page']) : 1; // Current page
$offset = ($page - 1) * $limit; // Offset for SQL query

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : "";
$searchQuery = $search ? "WHERE id LIKE '%$search%' OR semester LIKE '%$search%' OR academic_year LIKE '%$search%'" : "";

// Fetch total records for pagination
$totalQuery = "SELECT COUNT(*) AS total FROM academic_calendar $searchQuery";
$totalResult = $conn->query($totalQuery);
$totalRow = $totalResult->fetch_assoc();
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $limit);

$query = "
    SELECT 
        ac.id, 
        ac.course_id, 
        c.name AS course_name,  -- Get course name from courses table
        ac.semester, 
        ac.academic_year, 
        ac.start_date, 
        ac.end_date, 
        ac.is_editable, 
        ac.declare_result,
        u.full_name AS created_by_name,  -- Get user full name from users table
        ac.created_by, 
        ac.created_at, 
        ac.updated_at,
        ac.status
    FROM 
        academic_calendar ac
    LEFT JOIN 
        courses c ON ac.course_id = c.course_id  -- Join with courses table
    LEFT JOIN 
        users u ON ac.created_by = u.user_id  -- Join with users table
    $searchQuery 
    ORDER BY ac.created_at DESC
    LIMIT $limit OFFSET $offset
";
$result = $conn->query($query);


// Fetch academic statistics
$totalacademicsQuery = "SELECT COUNT(*) AS total FROM academic_calendar";
$activeacademicsQuery = "SELECT COUNT(*) AS active FROM academic_calendar WHERE is_editable = 1";
$deactiveacademicsQuery = "SELECT COUNT(*) AS deactive FROM academic_calendar WHERE is_editable = 0";

$totalacademicsResult = $conn->query($totalacademicsQuery);
$activeacademicsResult = $conn->query($activeacademicsQuery);
$deactiveacademicsResult = $conn->query($deactiveacademicsQuery);

$totalacademics = $totalacademicsResult->fetch_assoc()['total'];
$activeacademics = $activeacademicsResult->fetch_assoc()['active'];
$deactiveacademics = $deactiveacademicsResult->fetch_assoc()['deactive'];

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
              Academic Calender
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
                    Total Scheduled
                  </p>
                  <p
                    class="text-lg font-semibold text-gray-700 dark:text-gray-200"
                  >
                  <?php echo $totalacademics; ?>
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
                    Ongoing Schedule
                  </p>
                  <p
                    class="text-lg font-semibold text-gray-700 dark:text-gray-200"
                  >
                  <?php echo $activeacademics; ?>
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
                    Completed Schedule
                  </p>
                  <p
                    class="text-lg font-semibold text-gray-700 dark:text-gray-200"
                  >
                  <?php echo $deactiveacademics; ?>
                  </p>
                </div>
              </div>
              <!-- Card -->
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
    <a 
        href="create_academic_calender.php" 
        class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-md shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2"
    >
        + Create Academic Calender
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
            <th class="px-4 py-3">COURSE</th>
            <th class="px-1 py-3">SEMESTER</th>
            <th class="px-2 py-3">ACADEMIC YEAR</th>
            <th class="px-4 py-3">START DATE</th>
            <th class="px-4 py-3">END DATE</th>
            <th class="px-4 py-3">ACADEMIC STATUS</th>
            <th class="px-4 py-3">ACADEMIC ACTION</th>
            <th class="px-4 py-3">ACTION</th>
            <th class="px-4 py-3">RESULT</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
        <?php if ($result->num_rows > 0): ?>
            <?php $srNo = $offset + 1; ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr class="text-gray-700 dark:text-gray-400">
                    <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($row['course_name']); ?></td>
                    <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400"><?php echo $row['semester']; ?></td>
                    <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400"><?php echo $row['academic_year']; ?></td>
                    <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400">
    <?php echo date("d F Y", strtotime($row['start_date'])); ?>
</td>
<td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400">
    <?php echo date("d F Y", strtotime($row['end_date'])); ?>
</td>

                    <td class="px-4 py-3 font-semibold text-xs">
                    <button 
    class="px-4 py-2 font-semibold rounded-full shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2"
    data-id="<?php echo $row['id']; ?>"
    data-status="<?php echo $row['is_editable']; ?>"
    style="background-color: <?php echo $row['is_editable'] ? '#10b981' : '#10b981'; ?>; color: white; <?php echo !$row['is_editable'] ? 'pointer-events: none; opacity: 0.6;' : ''; ?>">
    <?php echo $row['is_editable'] ? 'Ongoing' : 'Completed'; ?>
</button>


                    </td>
                    <td class="px-4 py-3 font-semibold text-xs">
                    <button 
    class="px-4 py-2 font-semibold rounded-full shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 status-toggle"
    data-id="<?php echo $row['id']; ?>"
    data-status="<?php echo $row['status']; ?>"
    style="background-color: <?php echo $row['status'] == 1 ? '#10b981' : '#ef4444'; ?>; color: white; 
           <?php echo ($row['status'] == 1 || $row['is_editable'] == 0) ? 'pointer-events: none; opacity: 0.6;' : ''; ?>">
    <?php 
        if ($row['is_editable'] == 0) {
            echo 'COMPLETED';
        } else {
            echo $row['status'] == 1 ? 'RELEASE' : 'NOT-RELEASE';
        }
    ?>
</button>



                    </td>
                    <td class="px-4 py-3 font-semibold text-xs">
                    <a href="update_academics.php?id=<?php echo $row['id']; ?>" 
   class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-full shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">
   Edit
</a>
              <button 
                  class="px-4 py-2 text-black font-semibold rounded-full shadow-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 view-academic"
                  style="background-color: #ffff;"
                  data-id="<?php echo $row['id']; ?>">
                  View
              </button>

             <!-- Delete Button -->
<?php if ($row['status'] == 0): ?>
    <button  
        class="px-4 py-2 text-white font-semibold rounded-full shadow-md hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2 delete-academic"
        style="background-color: #ef4444;"
        data-id="<?= $row['id']; ?>">
        Delete
    </button>
<?php else: ?>
    <button class="px-4 py-2 bg-gray-400 text-white rounded-full cursor-not-allowed" style="background-color:rgb(237, 106, 106);" disabled>
        Delete
    </button>
<?php endif; ?>




                    </td>
                    <td class="px-4 py-3">
    <?php
        $today = date('Y-m-d');
        if ($row['end_date'] < $today) {
            if ($row['declare_result'] == 0) {
                echo '<button 
                        class="px-4 py-2 text-white font-semibold rounded-full shadow-md hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2 delete-academic"
                        style="background-color:rgb(39, 164, 232);"
                        onclick="openResultPopup(' . htmlspecialchars(json_encode($row['id'])) . ')"
                      >
                        Declare
                      </button>';
            } else {
                echo '<button 
                        class="px-4 py-2 font-semibold rounded-full shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2"
                        style="background-color: #10b981; color: white; pointer-events: none; opacity: 0.6;">
                        Declared
                      </button>';
            }
        } else {
            echo '<button 
                        class="px-4 py-2 font-semibold rounded-full shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2"
                        style="background-color: #FFFFC5; color: black; pointer-events: none; opacity: 0.6;">
                        Pending
                      </button>';
        }
    ?>
</td>

                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">No academics found</td>
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
    <!-- Delete Confirmation Popup -->
<div id="deletePopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96 relative">
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 text-center">Confirm Deletion</h2>
        <p class="text-gray-600 dark:text-gray-300"><strong>Academic Year:</strong> <span id="popupacademicYear"></span></p>
        <p class="text-gray-600 dark:text-gray-300"><strong>Semester:</strong> <span id="popupacademicSemesters"></span></p>
        <p class="text-gray-600 dark:text-gray-300"><strong>Start Date:</strong> <span id="popupStartDate"></span></p>
        <p class="text-gray-600 dark:text-gray-300"><strong>End Date:</strong> <span id="popupEndDate"></span></p>
        <p class="text-gray-600 dark:text-gray-300"><strong>Course:</strong> <span id="popupCourseName"></span></p>

        <div class="mt-6 flex justify-end space-x-3">
            <button id="cancelDelete" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-gray-600">Cancel</button>
            <button id="confirmDelete" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-red-600">Confirm</button>
        </div>
    </div>
</div>

<!-- Delete Success Popup -->
<div id="deleteSuccessPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 text-center">Academic Deleted</h2>
        <p class="text-gray-600 dark:text-gray-300 text-center">The academic has been deleted successfully.</p>
        <div class="mt-6 flex justify-center">
            <button id="closeDeleteSuccess" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">OK</button>
        </div>
    </div>
</div>



<!-- Confirmation Popup -->
<div id="statusPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 text-center">Confirm Status Change</h2>
        <p class="text-gray-600 dark:text-gray-300 text-center">
            Are you sure you want to <span id="statusActionText" class="font-bold"></span> this academic?
        </p>
        <div class="mt-6 flex justify-center space-x-3">
            <button id="cancelStatusChange" class="px-4 py-2 bg-gray-500 text-Black rounded-md hover:bg-gray-600">
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
            The academic status has been updated successfully.
        </p>
        <div class="mt-6 flex justify-center">
            <button id="closeStatusSuccess" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                OK
            </button>
        </div>
    </div>
</div>

<!-- Modal for viewing details -->
<div id="viewacademicModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
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
            <label class="font-semibold">Created By: </label>
            <input type="text" id="createdBy" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-4">
            <label class="font-semibold">Status: </label>
            <input type="text" id="status" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <button id="closeModal" class="w-full bg-blue-500 text-black py-2 rounded font-semibold">OK</button>
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

    let selectedacademicId = null;
    let newStatus = null;
    let currentButton = null;

    // Handle clicking the status toggle button
    toggleButtons.forEach(button => {
        button.addEventListener("click", () => {
            selectedacademicId = button.dataset.id;
            newStatus = button.dataset.status === "1" ? 0 : 1; // Toggle status

            if (button.dataset.status == 0 && button.dataset.editable == 0) {
                return;
            }

            // Update popup action text
            statusActionText.textContent = newStatus === 1 ? "Activate" : "Deactivate";

            // Show confirmation popup
            statusPopup.classList.remove("hidden");
            currentButton = button; // Store the current button to update it later
        });
    });

    // Cancel status change action
    cancelStatusChange.addEventListener("click", () => {
        statusPopup.classList.add("hidden");
    });

    // Confirm the status change
    confirmStatusChange.addEventListener("click", () => {
        if (selectedacademicId !== null) {
            fetch("update_academic_status.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    id: selectedacademicId,
                    status: newStatus,
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the button style and text based on new status
                    currentButton.dataset.status = data.newStatus;
                    currentButton.style.backgroundColor = data.newStatus === 1 ? "#10b981" : "#ef4444";
                    currentButton.textContent = data.newStatus === 1 ? "RELEASE" : "NOT-RELEASE";

                    // Hide confirmation popup and show success popup
                    statusPopup.classList.add("hidden");
                    statusSuccessPopup.classList.remove("hidden");
                } else {
                    alert("Failed to update academic status.");
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("An error occurred.");
            });
        }
    });

    // Close the success popup
    closeStatusSuccess.addEventListener("click", () => {
        statusSuccessPopup.classList.add("hidden");
    });
});
</script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const deleteButtons = document.querySelectorAll(".delete-academic");
    const popup = document.getElementById("deletePopup");
    const cancelDelete = document.getElementById("cancelDelete");
    const confirmDelete = document.getElementById("confirmDelete");
    const deleteSuccessPopup = document.getElementById("deleteSuccessPopup");
    const closeDeleteSuccess = document.getElementById("closeDeleteSuccess");

    const popupacademicYear = document.getElementById("popupacademicYear");
    const popupacademicSemesters = document.getElementById("popupacademicSemesters");
    const popupStartDate = document.getElementById("popupStartDate");
    const popupEndDate = document.getElementById("popupEndDate");
    const popupCourseName = document.getElementById("popupCourseName");

    let selectedacademicId = null;

    deleteButtons.forEach(button => {
        button.addEventListener("click", () => {
            const id = button.getAttribute("data-id");
            selectedacademicId = id;

            // Fetch details for popup
            fetch(`get_calendar_details.php?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (!data.error) {
                        popupacademicYear.textContent = data.academic_year;
                        popupacademicSemesters.textContent = data.semester;
                        popupStartDate.textContent = data.start_date;
                        popupEndDate.textContent = data.end_date;
                        popupCourseName.textContent = data.course_name;

                        popup.classList.remove("hidden");
                    } else {
                        alert("Unable to fetch academic calendar details.");
                    }
                });
        });
    });

    cancelDelete.addEventListener("click", () => {
        popup.classList.add("hidden");
    });

    confirmDelete.addEventListener("click", () => {
        if (selectedacademicId) {
            fetch(`delete_academic_calendar.php?id=${selectedacademicId}`, {
                method: 'POST'
            }).then(res => res.json())
              .then(data => {
                  if (data.success) {
                      popup.classList.add("hidden");
                      deleteSuccessPopup.classList.remove("hidden");
                  } else {
                      alert("Failed to delete. Reason: " + data.message);
                  }
              });
        }
    });

    closeDeleteSuccess.addEventListener("click", () => {
        deleteSuccessPopup.classList.add("hidden");
        location.reload(); // Refresh the page
    });
});
</script>


<script>
document.addEventListener("DOMContentLoaded", () => {
    const viewButtons = document.querySelectorAll(".view-academic");
    const modal = document.getElementById("viewacademicModal");
    const closeModal = document.getElementById("closeModal");

    // Event listener for each "View" button
    viewButtons.forEach(button => {
        button.addEventListener("click", () => {
            const academicId = button.dataset.id; // Get the academic ID

            // Fetch the data from the server using the academic ID
            fetch("fetch_academic_details.php?id=" + academicId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Populate the modal fields with fetched data
                        document.getElementById("coursename").value = data.academic.course_name;
                        document.getElementById("totalSemesters").value = data.academic.semester;
                        document.getElementById("academicYear").value = data.academic.academic_year;
                        document.getElementById("startDate").value = new Date(data.academic.start_date).toLocaleDateString();
                        document.getElementById("endDate").value = new Date(data.academic.end_date).toLocaleDateString();
                        document.getElementById("createdBy").value = data.academic.created_by_name;
                        document.getElementById("status").value = data.academic.is_editable ? 'Ongoing' : 'Completed';
                        
                        // Show the modal
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

    // Close the modal
    closeModal.addEventListener("click", () => {
        modal.classList.add("hidden");
    });
});

</script>

<!-- Result Declare Popup -->
<div id="resultPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96 relative">
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 text-center">Confirm Result Declaration</h2>
        <p class="text-gray-600 dark:text-gray-300"><strong>Academic Year:</strong> <span id="popupacademicYear"></span></p>
        <p class="text-gray-600 dark:text-gray-300"><strong>Semester:</strong> <span id="popupacademicSemesters"></span></p>
        <p class="text-gray-600 dark:text-gray-300"><strong>Start Date:</strong> <span id="popupStartDate"></span></p>
        <p class="text-gray-600 dark:text-gray-300"><strong>End Date:</strong> <span id="popupEndDate"></span></p>
        <p class="text-gray-600 dark:text-gray-300"><strong>Course:</strong> <span id="popupCourseName"></span></p>

        <input type="hidden" id="popupAcademicId">

        <div class="mt-6 flex justify-end space-x-3">
            <button id="cancelResultDeclare" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-gray-600">Cancel</button>
            <button id="confirmResultDeclare" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Declare</button>
        </div>
    </div>
</div>
<div id="resultSuccessPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 text-center">Result Declared</h2>
        <p class="text-gray-600 dark:text-gray-300 text-center">The result has been successfully declared.</p>
        <div class="mt-6 flex justify-center">
            <button id="closeResultSuccess" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">OK</button>
        </div>
    </div>
</div>
<script>
    function openResultPopup(data) {
        document.getElementById('popupAcademicId').value = data.id;
        document.getElementById('popupacademicYear').textContent = data.academic_year;
        document.getElementById('popupacademicSemesters').textContent = data.semester;
        document.getElementById('popupStartDate').textContent = data.start_date;
        document.getElementById('popupEndDate').textContent = data.end_date;
        document.getElementById('popupCourseName').textContent = data.course_name;
        document.getElementById('resultPopup').classList.remove('hidden');
    }

    document.getElementById('cancelResultDeclare').addEventListener('click', function () {
        document.getElementById('resultPopup').classList.add('hidden');
    });

    document.getElementById('closeResultSuccess').addEventListener('click', function () {
        document.getElementById('resultSuccessPopup').classList.add('hidden');
        location.reload(); // Refresh page to reflect status change
    });

    document.getElementById('confirmResultDeclare').addEventListener('click', function () {
        const id = document.getElementById('popupAcademicId').value;

        fetch('declare_result.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${id}`
        })
        .then(response => response.text())
        .then(data => {
            if (data.trim() === 'success') {
                document.getElementById('resultPopup').classList.add('hidden');
                document.getElementById('resultSuccessPopup').classList.remove('hidden');
            } else {
                alert('Something went wrong: ' + data);
            }
        });
    });
</script>

</body>
</html>
