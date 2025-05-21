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
$searchQuery = $search ? "AND (u.full_name LIKE '%$search%' OR u.email LIKE '%$search%' OR c.name LIKE '%$search%')" : "";

// Fetch total records for pagination
$totalQuery = "SELECT COUNT(*) AS total 
               FROM users u 
               WHERE u.role = 6  $searchQuery";

$totalResult = $conn->query($totalQuery);
$totalRow = $totalResult->fetch_assoc();
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $limit);

// Fetch paginated data
$query = "SELECT u.user_id, u.full_name, u.username, u.email, u.phone_number, 
                 u.users_status,  u.email_verified, u.created_at, u.access_status
          FROM users u 
          WHERE u.role = 6  $searchQuery 
          ORDER BY u.created_at DESC
          LIMIT $limit OFFSET $offset";

$result = $conn->query($query);

// Fetch evaluator statistics
$totalevaluatorsQuery = "SELECT COUNT(*) AS total FROM users WHERE role = 6";
$activeevaluatorsQuery = "SELECT COUNT(*) AS active FROM users WHERE role = 6 AND users_status = 1";
$deactiveevaluatorsQuery = "SELECT COUNT(*) AS deactive FROM users WHERE role = 6 AND users_status = 0";
$accessevaluatorsQuery = "SELECT COUNT(*) AS access FROM users WHERE role = 6 AND access_status = 1";
$restrictedevaluatorsQuery = "SELECT COUNT(*) AS restricted FROM users WHERE role = 6 AND access_status = 0";

$totalevaluatorsResult = $conn->query($totalevaluatorsQuery);
$activeevaluatorsResult = $conn->query($activeevaluatorsQuery);
$deactiveevaluatorsResult = $conn->query($deactiveevaluatorsQuery);
$accessevaluatorsResult = $conn->query($accessevaluatorsQuery);
$restrictedevaluatorsResult = $conn->query($restrictedevaluatorsQuery);


$totalevaluators = $totalevaluatorsResult->fetch_assoc()['total'];
$activeevaluators = $activeevaluatorsResult->fetch_assoc()['active'];
$deactiveevaluators = $deactiveevaluatorsResult->fetch_assoc()['deactive'];
$accessevaluators = $accessevaluatorsResult->fetch_assoc()['access'];
$restrictedevaluators = $restrictedevaluatorsResult->fetch_assoc()['restricted'];

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
              All evaluators
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
                    Total evaluators
                  </p>
                  <p
                    class="text-lg font-semibold text-gray-700 dark:text-gray-200"
                  >
                  <?php echo $totalevaluators; ?>
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
                    Active evaluators
                  </p>
                  <p
                    class="text-lg font-semibold text-gray-700 dark:text-gray-200"
                  >
                  <?php echo $activeevaluators; ?>
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
                    Deacitve evaluators
                  </p>
                  <p
                    class="text-lg font-semibold text-gray-700 dark:text-gray-200"
                  >
                  <?php echo $deactiveevaluators; ?>
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
                    Access evaluators
                  </p>
                  <p
                    class="text-lg font-semibold text-gray-700 dark:text-gray-200"
                  >
                  <?php echo $accessevaluators; ?>
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
                    Restricted evaluators
                  </p>
                  <p
                    class="text-lg font-semibold text-gray-700 dark:text-gray-200"
                  >
                  <?php echo $restrictedevaluators; ?>
                  </p>
                </div>
              </div>
              
              <!-- Card -->
            </div>

            <h4 class="mb-4 text-lg font-semibold text-gray-600 dark:text-gray-300">
    All evaluators List
</h4>
<div class="w-full mb-8 overflow-hidden rounded-lg shadow-xs">
    
    <!-- Table Wrapper -->
    <div class="w-full overflow-x-auto">
        <!-- Search Form -->
        <div class="flex justify-between items-center mb-4">
    <!-- Left: Placeholder (Empty Div for Alignment) -->
    <div class="w-1/3"></div>



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
            <th class="px-4 py-3">Full NAME</th>
            <th class="px-4 py-3">Email</th>
            <th class="px-4 py-3">STATUS</th>
            <th class="px-4 py-3">DASHBOARD ACCESS</th>
            <th class="px-4 py-3">ACTION</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
        <?php if ($result->num_rows > 0): ?>
            <?php $srNo = $offset + 1; ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr class="text-gray-700 dark:text-gray-400">
                    <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400"><?php echo $srNo++; ?></td>
                    <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($row['full_name']); ?></td>
                    <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($row['email']); ?></td>
                    <td class="px-4 py-3 font-semibold text-xs">
                        <button 
                            class="px-4 py-2 font-semibold rounded-full shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 status-toggle"
                            data-id="<?php echo $row['user_id']; ?>"
                            data-status="<?php echo $row['users_status']; ?>"
                            style="background-color: <?php echo $row['users_status'] ? '#10b981' : '#ef4444'; ?>; color: white;">
                            <?php echo $row['users_status'] ? 'Active' : 'Inactive'; ?>
                        </button>
                    </td>
                    <td class="px-4 py-3 font-semibold text-xs">
    <?php if ($row['access_status'] == 0): ?>
        <!-- Disable the button if access is restricted -->
        <button class="px-4 py-2 font-semibold rounded-full shadow-md bg-red-500 text-black cursor-not-allowed" disabled>
            Restricted
        </button>
    <?php else: ?>
        <!-- Enable button if access is granted -->
        <button 
            class="px-4 py-2 font-semibold rounded-full shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 access-toggle"
            data-id="<?php echo $row['user_id']; ?>"
            data-status="<?php echo $row['access_status']; ?>"
            style="background-color: <?php echo $row['access_status'] ? '#10b981' : '#ef4444'; ?>; color: white;">
            <?php echo $row['access_status'] ? 'Access' : 'Restricted'; ?>
        </button>
    <?php endif; ?>
</td>

                    <td class="px-4 py-3 font-semibold text-xs">
                    <a href="report.php?id=<?php echo $row['user_id']; ?>" 
   class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-full shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">
   Report
</a>
<button 
            class="px-4 py-2 text-black font-semibold rounded-full shadow-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 view-evaluator"
            style="background-color: #ffff;"
            data-id="<?php echo $row['user_id']; ?>">
            View
        </button>

                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">No evaluator found</td>
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

<!-- Delete Confirmation Popup -->


<!-- Status Confirmation Popup -->
<div id="statusPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 text-center">Confirm Status Change</h2>
        <p class="text-gray-600 dark:text-gray-300 text-center">
            Are you sure you want to <span id="statusActionText" class="font-bold"></span> this evaluator?
        </p>

        <!-- Status Change Form -->
        <form id="statusChangeForm" class="mt-6 space-y-4">
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
            The evaluator status has been updated successfully.
        </p>
        <p class="text-gray-600 dark:text-gray-300 text-center mt-2" id="statusSuccessMessage"></p> <!-- Success message for email sent -->
        <div class="mt-6 flex justify-center">
            <button id="closeStatusSuccess" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                OK
            </button>
        </div>
    </div>
</div>


<!-- Access Confirmation Popup -->
<div id="accessPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 text-center">Confirm Access Change</h2>
        <p class="text-gray-600 dark:text-gray-300 text-center">
            Are you sure you want to <span id="accessActionText" class="font-bold"></span> this evaluator?
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
            The evaluator access has been updated successfully.
        </p>
        <p class="text-gray-600 dark:text-gray-300 text-center mt-2" id="accessSuccessMessage"></p> <!-- Success message for email sent -->
        <div class="mt-6 flex justify-center">
            <button id="closeAccessSuccess" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                OK
            </button>
        </div>
    </div>
</div>



<!-- View Course Popup -->
<!-- View evaluator Details Modal -->
<div id="viewevaluatorModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-lg shadow-lg w-96 p-6">
        <h2 class="text-lg font-semibold mb-4">evaluator Details</h2>
        
        <div class="mb-2">
            <label class="font-semibold">evaluator Name:</label>
            <input type="text" id="evaluatorName" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">Email:</label>
            <input type="text" id="evaluatorEmail" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">Phone Number:</label>
            <input type="text" id="evaluatorPhone" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <button id="closeevaluatorModal" class="w-full bg-blue-500 text-white py-2 rounded font-semibold">OK</button>
    </div>
</div>





</div>

<?php $conn->close(); ?>
<script>
// Show the Status Popup when 'active/inactive' button is clicked
document.querySelectorAll('.status-toggle').forEach(button => {
    button.addEventListener('click', function() {
        const evaluatorId = this.dataset.id;
        const statusAction = this.dataset.status == '1' ? 'deactivate' : 'activate';
        triggerStatusChange(evaluatorId, statusAction);
    });
});

// Show the confirmation popup
// Show success popup after status change
function showStatusSuccessPopup(message) {
    const statusSuccessPopup = document.getElementById('statusSuccessPopup');
    const statusSuccessMessage = document.getElementById('statusSuccessMessage');
    
    // Set success message
    statusSuccessMessage.textContent = message;

    // Show success popup
    statusSuccessPopup.classList.remove('hidden');
    
    // Close success popup and refresh page when OK is clicked
    document.getElementById('closeStatusSuccess').onclick = function () {
        statusSuccessPopup.classList.add('hidden');
        location.reload();  // Refresh the page
    };
}

// Handle confirmation of the status change
function showStatusPopup(evaluatorId, statusAction) {
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

        // Add evaluatorId and statusAction to the form data
        formData.append('evaluator_id', evaluatorId);
        formData.append('status_action', statusAction === 'activate' ? 1 : 0);

        // Send an AJAX request to the server to update status and send email
        fetch('update_evaluator_status.php', {
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


// Show success popup after status change
// Show success popup after status change
function showStatusSuccessPopup(message) {
    const statusSuccessPopup = document.getElementById('statusSuccessPopup');
    const statusSuccessMessage = document.getElementById('statusSuccessMessage');
    
    // Set success message
    statusSuccessMessage.textContent = message;

    // Show success popup
    statusSuccessPopup.classList.remove('hidden');

    // Close success popup and redirect to evaluatorlist.php when OK is clicked
    document.getElementById('closeStatusSuccess').onclick = function () {
        statusSuccessPopup.classList.add('hidden');
        window.location.href = 'evaluatorlist.php';  // Redirect to evaluatorlist.php
    };
}


// Close the status popup when Cancel is clicked
document.getElementById('cancelStatusChange').onclick = function () {
    document.getElementById('statusPopup').classList.add('hidden');
};

// Trigger the status change for a specific evaluator
function triggerStatusChange(evaluatorId, action) {
    showStatusPopup(evaluatorId, action);
}

</script>

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
        fetch('update_evaluator_access.php', {
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
        window.location.href = 'evaluatorlist.php';  // Redirect to user list page
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


<script>
document.addEventListener("DOMContentLoaded", () => {
    const deleteButtons = document.querySelectorAll(".delete-evaluator");
    const deletePopup = document.getElementById("deleteevaluatorPopup");
    const cancelDeleteevaluator = document.getElementById("cancelDeleteevaluator");
    const confirmDeleteevaluator = document.getElementById("confirmDeleteevaluator");
    const deleteSuccessPopup = document.getElementById("deleteSuccessevaluatorPopup");
    const closeDeleteSuccessevaluator = document.getElementById("closeDeleteevaluatorSuccess");

    let selectedevaluatorId = null;

    deleteButtons.forEach(button => {
        button.addEventListener("click", () => {
            document.getElementById("popupevaluatorName").textContent = button.dataset.name;
            document.getElementById("popupevaluatorEmail").textContent = button.dataset.email;
            document.getElementById("popupevaluatorCollege").textContent = button.dataset.college;

            selectedevaluatorId = button.dataset.id;
            deletePopup.classList.remove("hidden");
        });
    });

    cancelDeleteevaluator.addEventListener("click", () => deletePopup.classList.add("hidden"));

    confirmDeleteevaluator.addEventListener("click", () => {
        if (selectedevaluatorId) {
            fetch("delete_evaluator.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id: selectedevaluatorId }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    deletePopup.classList.add("hidden");
                    deleteSuccessPopup.classList.remove("hidden");
                } else {
                    alert("Failed to delete evaluator.");
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("An error occurred.");
            });
        }
    });

    closeDeleteSuccessevaluator.addEventListener("click", () => {
        deleteSuccessPopup.classList.add("hidden");
        location.reload(); // Reload the page after success
    });
});

</script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const viewButtons = document.querySelectorAll(".view-evaluator");
    const modal = document.getElementById("viewevaluatorModal");
    const closeModal = document.getElementById("closeevaluatorModal");

    viewButtons.forEach(button => {
        button.addEventListener("click", () => {
            const evaluatorId = button.dataset.id;

            fetch("view_evaluator_details.php?id=" + evaluatorId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById("evaluatorName").value = data.evaluator.full_name;
                        document.getElementById("evaluatorEmail").value = data.evaluator.email;
                        document.getElementById("evaluatorPhone").value = data.evaluator.phone_number;
                        modal.classList.remove("hidden");
                    } else {
                        alert("Failed to fetch evaluator details.");
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
