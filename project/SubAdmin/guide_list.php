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

// Fetch Guides with Course Name for the specific college
$query = "
    SELECT 
        u.user_id,
        u.full_name AS guide_name,  
        u.email AS guide_email, 
        u.phone_number AS guide_phone, 
        u.role AS guide_role,
        u.access_status,
        u.users_status AS guide_status,
        u.created_at, 
        u.project_head,
        c.name AS course_name,
        c.course_id  -- Now we are also fetching course_id
    FROM users u
    LEFT JOIN courses c ON u.course_id = c.course_id
    WHERE u.role = 4 AND u.guide_permission = 1 AND u.college_id = $college_id  AND u.course_id= $course_id $searchQuery
    LIMIT $limit OFFSET $offset";

$result = $conn->query($query);

// Fetch Data
$guides = [];
while ($row = $result->fetch_assoc()) {
    $guides[] = $row;
}

// Total Records Count for Pagination
$countQuery = "SELECT COUNT(*) as total FROM users u WHERE u.role = 4 AND u.guide_permission = 1 AND u.college_id = $college_id  AND u.course_id= $course_id $searchQuery";
$countResult = $conn->query($countQuery);
$countRow = $countResult->fetch_assoc();
$totalRecords = $countRow['total'];
$totalPages = ceil($totalRecords / $limit);

// Check if there are any guides
if (count($guides) > 0) {
    // Get the course_id from the first guide (since all guides have the same course_id in the same college)
    $course_id = $guides[0]['course_id'];

    // Check if there is already a project head for this course and college
    $query = "
        SELECT user_id 
        FROM users 
        WHERE college_id = $college_id 
        AND course_id = $course_id 
        AND project_head = 1";
    
    $result = $conn->query($query);

    // Handle SQL errors if they occur
    if ($result === false) {
        die('SQL Error: ' . $conn->error);
    }

    $row = $result->fetch_assoc();
    $projectHeadUserId = $row ? $row['user_id'] : null; // Store the user_id of the current project head, or null if none

    // Fetch Project Head details if a project head exists
    $projectHeadDetails = null;
    if ($projectHeadUserId) {
        $projectHeadQuery = "
            SELECT 
                u.full_name AS guide_name,
                u.email AS guide_email,
                u.phone_number AS guide_phone,
                c.name AS course_name
            FROM users u
            LEFT JOIN courses c ON u.course_id = c.course_id
            WHERE u.user_id = $projectHeadUserId";
        
        $projectHeadResult = $conn->query($projectHeadQuery);
        if ($projectHeadResult && $projectHeadResult->num_rows > 0) {
            $projectHeadDetails = $projectHeadResult->fetch_assoc();
        }
    }
}
 else {
    // Handle the case when there are no guides (e.g., no guides available for this college)
    $course_id = null; // No course_id available
    $projectHeadDetails = null; // No project head available
}

?>

<main class="h-full overflow-y-auto">
    <div class="container px-6 mx-auto grid">
        <h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">Guides</h2>
        
        <div class="grid gap-6 mb-8 md:grid-cols-2 xl:grid-cols-4">
            <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
                <div class="p-3 mr-4 text-orange-500 bg-orange-100 rounded-full dark:text-orange-100 dark:bg-orange-500">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 2L2 6l8 4 8-4-8-4zM2 9l8 4 8-4v2l-8 4-8-4V9zM2 14v2h16v-2H2z"></path>
                    </svg>
                </div>
                <div>
                    <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">Total Guides</p>
                    <p class="text-lg font-semibold text-gray-700 dark:text-gray-200"><?php echo $totalRecords; ?></p>
                </div>
            </div>
        </div>

        <div class="grid gap-6 mb-8">
            <div class="p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
                <?php if ($projectHeadDetails): ?>
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">Project Head Details</h3>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Full Name: <span class="font-semibold"><?php echo $projectHeadDetails['guide_name']; ?></span></p>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Email: <span class="font-semibold"><?php echo $projectHeadDetails['guide_email']; ?></span></p>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Phone: <span class="font-semibold"><?php echo $projectHeadDetails['guide_phone']; ?></span></p>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Course: <span class="font-semibold"><?php echo $projectHeadDetails['course_name']; ?></span></p>
                <?php else: ?>
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">Project Head Details</h3>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">No Project Head assigned to this course yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <h4 class="mb-4 text-lg font-semibold text-gray-600 dark:text-gray-300">Guide List</h4>
        
        <div class="w-full mb-8 overflow-hidden rounded-lg shadow-xs">
            <div class="w-full overflow-x-auto">
                <!-- Search Form -->    <!-- Left: Add Course Button -->
  
                <div class="flex justify-between items-center mb-4">
                    <div class="w-1/3"></div>

                    <form method="GET" class="flex items-center space-x-2 w-1/3 justify-end">
                        <input 
                            type="text" 
                            name="search" 
                            placeholder="Search guides..." 
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
                            <th class="px-4 py-3">Name</th>   
                            <th class="px-4 py-3">ALLOCATED COURSE</th>
                            <th class="px-4 py-3">STATUS</th>
                            <th class="px-4 py-3">ACCESS</th>
                            <th class="px-4 py-3">ACTION</th>
                            <th class="px-4 py-3">PROMOTION</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
                        <?php foreach ($guides as $guide): ?>
                            <tr class="text-gray-700 dark:text-gray-400">
                                <td class="px-4 py-3 font-semibold text-xs"><?php echo $guide['guide_name']; ?></td>
                                <td class="px-4 py-3 font-semibold text-xs"><?php echo $guide['course_name']; ?></td>
                                <td class="px-4 py-3 font-semibold text-xs">
                                    <button 
                                        class="px-4 py-2 font-semibold rounded-full shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 status-toggle"
                                        data-id="<?php echo $guide['user_id']; ?>"
                                        data-status="<?php echo $guide['guide_status']; ?>"
                                        style="background-color: <?php echo ($guide['guide_status'] == 1) ? '#10b981' : '#ef4444'; ?>;
                                               color: <?php echo ($guide['guide_status'] == 1) ? 'white' : 'white'; ?>;">
                                        <?php echo ($guide['guide_status'] == 1) ? 'Active' : 'Inactive'; ?>
                                    </button>
                                </td>
                                <td class="px-4 py-3 font-semibold text-xs">
    <?php if ($guide['access_status'] == 0): ?>
        <!-- Disable the button if access is restricted -->
        <button class="px-4 py-2 font-semibold rounded-full shadow-md bg-red-500 text-white cursor-not-allowed" disabled>
            Restricted
        </button>
    <?php else: ?>
        <!-- Enable button if access is granted -->
        <button 
            class="px-4 py-2 font-semibold rounded-full shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 access-toggle"
            data-id="<?php echo $guide['user_id']; ?>"
            data-status="<?php echo $guide['access_status']; ?>"
            style="background-color: <?php echo $guide['access_status'] ? '#10b981' : '#ef4444'; ?>; color: white;">
            <?php echo $guide['access_status'] ? 'Access' : 'Restricted'; ?>
        </button>
    <?php endif; ?>
</td>

                                <td class="px-4 py-3 font-semibold text-xs">
                                    <button 
                                        class="px-4 py-2 text-white bg-blue-500 font-semibold rounded-full shadow-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 view-guide"
                                        data-id="<?php echo $guide['user_id']; ?>"> <!-- Correct variable for guide -->
                                        View
                                    </button>
                                    <button 
        class="px-4 py-2 text-white bg-blue-500 font-semibold rounded-full shadow-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 remove-guide"
        data-id="<?php echo $guide['user_id']; ?>"
        data-name="<?php echo $guide['guide_name']; ?>"
        data-projecthead="<?php echo $guide['project_head']; ?>">
        Remove
    </button>
                                    </td>

                                   
                                
    
                                    <td class="px-4 py-3 font-semibold text-xs">
    <?php
        $isProjectHead = ($guide['user_id'] == $projectHeadUserId);
        $someoneIsAlreadyHead = !empty($projectHeadUserId);
        $disablePromotion = !$isProjectHead && $someoneIsAlreadyHead;

        // Set styles
        $buttonColor = $isProjectHead ? '#10b981' : '#ef4444';
        $cursorStyle = $disablePromotion ? 'cursor: not-allowed;' : 'cursor: pointer;';
        $titleAttr = $disablePromotion ? 'title="There is Already Project Head"' : '';
    ?>

    <button 
        class="px-4 py-2 text-white font-semibold rounded-full shadow-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 promotion-guide"
        data-id="<?php echo $guide['user_id']; ?>"
        data-course="<?php echo $guide['course_name']; ?>"
        data-college="<?php echo $college_id; ?>"
        style="background-color: <?php echo $buttonColor; ?>; <?php echo $cursorStyle; ?>"
        <?php echo $disablePromotion ? 'disabled' : ''; ?>
        <?php echo $titleAttr; ?>>
        
        <?php echo $isProjectHead ? 'Demotion' : 'Promotion'; ?>
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
    </div>
</main>
<!-- Status Confirmation Popup -->
<div id="statusPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 text-center">Confirm Status Change</h2>
        <p class="text-gray-600 dark:text-gray-300 text-center">
            Are you sure you want to <span id="statusActionText" class="font-bold"></span> this Guide?
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
            <button id="cancelStatusChange" class="px-4 py-2 bg-gray-500 text-black rounded-md hover:bg-gray-600">
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
            The Guide status has been updated successfully.
        </p>
        <p class="text-gray-600 dark:text-gray-300 text-center mt-2" id="statusSuccessMessage"></p> <!-- Success message for email sent -->
        <div class="mt-6 flex justify-center">
            <button id="closeStatusSuccess" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                OK
            </button>
        </div>
    </div>
</div>

<script>
// Show the Status Popup when 'active/inactive' button is clicked
document.querySelectorAll('.status-toggle').forEach(button => {
    button.addEventListener('click', function() {
        const guideId = this.dataset.id;
        const statusAction = this.dataset.status == '1' ? 'deactivate' : 'activate';
        triggerStatusChange(guideId, statusAction);
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
function showStatusPopup(guideId, statusAction) {
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
        formData.append('guide_id', guideId);
        formData.append('status_action', statusAction === 'activate' ? 1 : 0);

        // Send an AJAX request to the server to update status and send email
        fetch('update_guide_status.php', {
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

    // Close success popup and redirect to adminlist.php when OK is clicked
    document.getElementById('closeStatusSuccess').onclick = function () {
        statusSuccessPopup.classList.add('hidden');
        window.location.href = 'guide_list.php';  // Redirect to adminlist.php
    };
}


// Close the status popup when Cancel is clicked
document.getElementById('cancelStatusChange').onclick = function () {
    document.getElementById('statusPopup').classList.add('hidden');
};

// Trigger the status change for a specific admin
function triggerStatusChange(guideId, action) {
    showStatusPopup(guideId, action);
}

</script>

<!-- Access Confirmation Popup -->
<div id="accessPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 text-center">Confirm Access Change</h2>
        <p class="text-gray-600 dark:text-gray-300 text-center">
            Are you sure you want to <span id="accessActionText" class="font-bold"></span> this Guide?
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
            <button id="cancelAccessChange" class="px-4 py-2 bg-gray-500 text-black rounded-md hover:bg-gray-600">
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
            The guide access has been updated successfully.
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
        const guideId = this.dataset.id;
        const accessAction = this.dataset.status == '1' ? 'restrict' : 'grant';
        triggerAccessChange(guideId, accessAction);
    });
});

// Show the confirmation popup
function showAccessPopup(guideId, accessAction) {
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
        formData.append('guide_id', guideId);
        formData.append('access_action', accessAction === 'grant' ? 1 : 0);

        // Send an AJAX request to the server to update access and send email
        fetch('update_guide_access.php', {
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
        window.location.href = 'guide_list.php';  // Redirect to user list page
    };
}

// Close the access popup when Cancel is clicked
document.getElementById('cancelAccessChange').onclick = function () {
    document.getElementById('accessPopup').classList.add('hidden');
};

// Trigger the access change for a specific user
function triggerAccessChange(guideId, action) {
    showAccessPopup(guideId, action);
}

</script>

<!-- View Admin Details Modal -->
<div id="viewAdminModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-lg shadow-lg w-96 p-6">
        <h2 class="text-lg font-semibold mb-4">Guide Details</h2>
        
        <div class="mb-2">
            <label class="font-semibold">Guide Name:</label>
            <input type="text" id="guideName" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">Email:</label>
            <input type="text" id="guideEmail" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">Phone Number:</label>
            <input type="text" id="guidePhone" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div class="mb-2">
            <label class="font-semibold">College:</label>
            <input type="text" id="guideCollege" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>

        <button id="closeAdminModal" class="w-full bg-blue-500 text-white py-2 rounded font-semibold">OK</button>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const viewButtons = document.querySelectorAll(".view-guide");
    const modal = document.getElementById("viewAdminModal");
    const closeModal = document.getElementById("closeAdminModal");

    viewButtons.forEach(button => {
        button.addEventListener("click", () => {
            const guide_id = button.dataset.id;

            fetch("view_guide_details.php?id=" + guide_id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById("guideName").value = data.admin.full_name;
                        document.getElementById("guideEmail").value = data.admin.email;
                        document.getElementById("guidePhone").value = data.admin.phone_number;
                        document.getElementById("guideCollege").value = data.admin.college_name;
                        modal.classList.remove("hidden");
                    } else {
                        alert("Failed to fetch admin details.");
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

<!-- Project Head Promotion Confirmation Popup -->
<!-- Project Head Promotion/Demotion Confirmation Popup -->
<div id="promotionPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 text-center">Promote/Demote Guide to Project Head</h2>
        <p class="text-gray-600 dark:text-gray-300 text-center">
            Are you sure you want to <span id="actionText">Promote</span> <span id="guideName" class="font-bold"></span> to Project Head in the course <span id="courseName" class="font-bold"></span>?
        </p>

        <!-- Confirmation Button -->
        <div class="mt-6 flex justify-center space-x-3">
            <button id="cancelPromotion" class="px-4 py-2 bg-gray-500 text-black rounded-md hover:bg-gray-600">
                Cancel
            </button>
            <button id="confirmPromotion" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                Confirm Promotion
            </button>
        </div>
    </div>
</div>




<script>
document.addEventListener('DOMContentLoaded', () => {
    const popup = document.getElementById('promotionPopup');
    const confirmBtn = document.getElementById('confirmPromotion');
    const cancelBtn = document.getElementById('cancelPromotion');
    const actionTextEl = document.getElementById('actionText');
    const guideNameEl = document.getElementById('guideName');
    const courseNameEl = document.getElementById('courseName');

    let currentGuideId = null;
    let currentCourseName = '';
    let currentCollegeId = '';
    let isPromotionAction = true;

    // Bind cancel button once
    cancelBtn.addEventListener('click', () => {
        popup.classList.add('hidden');
    });

    // Attach main promotion/demotion logic
    confirmBtn.addEventListener('click', () => {
        fetch('promote_guide.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                guide_id: currentGuideId,
                course_name: currentCourseName,
                college_id: currentCollegeId,
                action: isPromotionAction ? 'promote' : 'demote'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error with promotion/demotion action.');
            }
        });

        popup.classList.add('hidden');
    });

    // Loop through each guide button
    document.querySelectorAll('.promotion-guide').forEach(button => {
        button.addEventListener('click', (e) => {
            currentGuideId = button.dataset.id;
            currentCourseName = button.dataset.course;
            currentCollegeId = button.dataset.college;
            isPromotionAction = button.innerText.trim() === 'Promotion';

            const guideRow = button.closest('tr');
            const guideName = guideRow.querySelector('td:nth-child(1)').innerText;

            actionTextEl.innerText = isPromotionAction ? 'Promote' : 'Demote';
            confirmBtn.innerText = isPromotionAction ? 'Confirm Promotion' : 'Confirm Demotion';
            guideNameEl.innerText = guideName;
            courseNameEl.innerText = currentCourseName;

            popup.classList.remove('hidden');
        });
    });
});
</script>

<!-- Confirmation Popup -->
<!-- 🧨 Remove Guide Popup -->
<div id="removePopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
  <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96">
    <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 text-center">Remove Guide</h2>
    <div id="popupContent" class="text-gray-600 dark:text-gray-300 text-center text-sm mb-4"></div>
    <div class="flex justify-center space-x-3 mt-4">
      <button id="cancelRemove" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-gray-600">Cancel</button>
      <button id="confirmRemove" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-red-600 ml-2" disabled>Confirm Removal</button>
    </div>
  </div>
</div>


<!-- ✅ Success Popup -->
<!-- ✅ Success Popup -->
<div id="statusSuccessPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
  <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96">
    <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 text-center">Success</h2>
    <p id="statusSuccessMessage" class="text-gray-600 dark:text-gray-300 text-center">Guide removed successfully.</p>
    <div class="flex justify-center mt-4">
      <button id="closeStatusSuccess" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">OK</button>
    </div>
  </div>
</div>


<script>
document.querySelectorAll('.remove-guide').forEach(button => {
    button.addEventListener('click', function () {
        const userId = this.getAttribute('data-id');
        const guideName = this.getAttribute('data-name');
        const isProjectHead = this.getAttribute('data-projecthead') == '1';

        const popup = document.getElementById('removePopup');
        const content = document.getElementById('popupContent');
        const confirmBtn = document.getElementById('confirmRemove');

        confirmBtn.setAttribute('data-user-id', userId);
        confirmBtn.disabled = true;
        content.innerHTML = `<p class="text-sm">Checking assignment status...</p>`;
        popup.style.display = 'flex';

        // If project head
        if (isProjectHead) {
            content.innerHTML = `<strong>${guideName}</strong> is a <span class="text-red-500 font-bold">Project Head</span> and cannot be removed.`;
            confirmBtn.disabled = true;
            return;
        }

        // Check assignment data
        fetch('check_guide_assignments.php?user_id=' + userId)
            .then(res => res.json())
            .then(data => {
                if (data.has_assignments) {
                    content.innerHTML = `
                        <p><strong>${guideName}</strong> is assigned to students in:</p>
                        <ul class="mt-2 list-disc list-inside text-sm text-left">
                            <li><strong>Academic Year:</strong> ${data.academic_year}</li>
                            <li><strong>Semester:</strong> ${data.semester}</li>
                            <li><strong>Start Date:</strong> ${data.start_date}</li>
                            <li><strong>End Date:</strong> ${data.end_date}</li>
                            <li><strong>Students Assigned:</strong> ${data.student_count}</li>
                        </ul>
                        <p class="mt-2 text-red-500 text-center">You cannot remove this guide while students are assigned.</p>
                    `;
                    confirmBtn.disabled = true;
                } else {
                    content.innerHTML = `<p><strong>${guideName}</strong> is not assigned to any students. You can proceed with removal.</p>`;
                    confirmBtn.disabled = false;
                }
            });
    });
});

// Cancel removal
document.getElementById('cancelRemove').addEventListener('click', () => {
    document.getElementById('removePopup').style.display = 'none';
});

// Confirm removal
document.getElementById('confirmRemove').addEventListener('click', function () {
    const userId = this.getAttribute('data-user-id');

    fetch('remove_permission.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'user_id=' + userId
    })
    .then(res => res.text())
    .then(response => {
        document.getElementById('removePopup').style.display = 'none';
        document.getElementById('statusSuccessPopup').style.display = 'flex';
    });
});

// Close success popup
document.getElementById('closeStatusSuccess').addEventListener('click', () => {
    document.getElementById('statusSuccessPopup').style.display = 'none';
    location.reload();
});
</script>
