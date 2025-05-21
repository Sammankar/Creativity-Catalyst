<?php
ob_start();
include "header.php";
include "connection.php";
$competition_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

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
               WHERE u.role = 6 
                 AND u.users_status = 1 
                 AND u.user_id NOT IN (
                     SELECT user_id FROM evaluators WHERE competition_id = $competition_id
                 )
                 $searchQuery";

$totalResult = $conn->query($totalQuery);
$totalRow = $totalResult->fetch_assoc();
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $limit);
// Fetch paginated data
$query = "
SELECT u.user_id, u.full_name, u.username, u.email, u.phone_number, 
       u.users_status, u.email_verified, u.created_at, u.access_status
FROM users u
JOIN evaluators e ON u.user_id = e.user_id
WHERE u.role = 6 
  AND u.users_status = 1 
  AND e.competition_id = $competition_id
$searchQuery 
ORDER BY u.created_at DESC
LIMIT $limit OFFSET $offset";

$result = $conn->query($query);
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_evaluators'])) {
    $competition_id = $_GET['id'];
    $selected_evaluators = $_POST['evaluators'];

    if (!empty($selected_evaluators)) {
        foreach ($selected_evaluators as $user_id) {
            $stmt = $conn->prepare("DELETE FROM evaluators WHERE competition_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $competition_id, $user_id);
            $stmt->execute();
        }
        $_SESSION['message'] = "Evaluators deallocated successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Please select at least one evaluator to deallocate.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: competition_assign_evaluator.php?id=$competition_id");
    exit();
}

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
              DeAllocate evaluators
            </h2>
            <!-- CTA -->

            <!-- Cards -->
            <div class="grid gap-6 mb-8 md:grid-cols-2 xl:grid-cols-4">
              <!-- Card -->
             
              <!-- Card -->
            </div>

            <h4 class="mb-4 text-lg font-semibold text-gray-600 dark:text-gray-300">
            Allocated Evaluators List
        </h4>

        <div class="w-full mb-8 overflow-hidden rounded-lg shadow-xs">
        <div class="w-full overflow-x-auto">
        
    <!-- Main Form for Assign Button -->
    
        <div class="flex flex-wrap justify-between items-center mb-4 gap-4">

            <!-- Left: Assign Evaluators Button -->
            <div class="flex-shrink-0 ml-auto">
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

            <!-- Middle: Message -->
            <?php if (!empty($message)): ?>
                <div id="custom-popup" class="flex-1 text-center bg-white border <?php echo ($message_type === 'success') ? 'border-green-500 text-green-600' : 'border-red-500 text-red-600'; ?> px-6 py-3 rounded-lg shadow-lg flex justify-center items-center space-x-3">
                    <?php if ($message_type === 'success'): ?>
                        <svg class="w-6 h-6 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    <?php else: ?>
                        <svg class="w-6 h-6 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M12 6a9 9 0 110 18A9 9 0 0112 6z" />
                        </svg>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($message); ?></span>
                    <button onclick="closePopup()" class="ml-2 text-gray-700 font-bold">×</button>
                </div>

                <script>
                    function closePopup() {
                        document.getElementById("custom-popup").style.display = "none";
                    }
                    setTimeout(closePopup, 5000);
                </script>
            <?php endif; ?>
            <form method="POST" action="">
            <!-- Right: Search Bar (separate GET form) -->
            <div class="flex-shrink-0">
            <a 
        href="competition_assign_evaluator.php" 
        class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-md shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 mr-3"
    >
        - Back To Competitons List
    </a>

            <button 
    type="submit" 
    name="assign_evaluators" 
    id="assignButton"
    class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-md shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 ml-3"
    disabled
>
    DeAllocate Evaluators
</button>

            </div>

        </div>
</div>


        <table class="w-full whitespace-no-wrap">
    <thead>
        <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800">
        <th class="px-4 py-3">Select</th>
                                <th class="px-4 py-3">SR NO.</th>
                                <th class="px-4 py-3">Full NAME</th>
                                <th class="px-4 py-3">Email</th>
                                <th class="px-4 py-3">Evaluator Data</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
                            <?php if ($result->num_rows > 0): ?>
                                <?php $srNo = $offset + 1; ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr class="text-gray-700 dark:text-gray-400">
                                        <td class="px-4 py-3">
                                            <input 
                                                type="checkbox" 
                                                name="evaluators[]" 
                                                value="<?php echo $row['user_id']; ?>"
                                                class="form-checkbox"
                                            >
                                        </td>
                                        <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400"><?php echo $srNo++; ?></td>
                                        <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                        <td class="px-4 py-3 font-semibold text-xs text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td class="px-4 py-3 font-semibold text-xs">
                                        <button 
  type="button"
  class="view-btn px-4 py-2 text-black font-semibold rounded-full shadow-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2"
  style="background-color: #ffff;" 
  data-user-id="<?php echo $row['user_id']; ?>"
>
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
    <div class="flex justify-end mt-4">
        <nav class="flex items-center space-x-2">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>"
                   class="px-4 py-2 border border-gray-300 text-gray-600 rounded-md hover:bg-blue-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">
                    Back
                </a>
            <?php endif; ?>
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
            </form>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('input[name="evaluators[]"]');
    const assignButton = document.getElementById('assignButton');

    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const selected = Array.from(checkboxes).some(chk => chk.checked);
            assignButton.disabled = !selected;
        });
    });
});

        </script>
        <div id="viewevaluatorModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-lg shadow-lg w-96 p-6">
        <h2 class="text-lg font-semibold mb-4">Evaluator Details</h2>
        <div class="mb-2">
            <label class="font-semibold">Evaluator Name:</label>
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
        <div class="mb-2">
            <label class="font-semibold">Active Competitions:</label>
            <input type="text" id="activeCompetitions" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>
        <div class="mb-2">
  <label class="font-semibold block">Active Competitions Details:</label>
  <div id="activeCompetitionDetails" class="text-sm text-gray-700 space-y-2 mt-2"></div>
</div>
        <div class="mb-2">
            <label class="font-semibold">Completed Competitions:</label>
            <input type="text" id="completedCompetitions" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
        </div>
        <button id="closeevaluatorModal" class="w-full bg-blue-500 text-white py-2 rounded font-semibold">OK</button>
    </div>
</div>
<script>
    document.querySelectorAll('.view-btn').forEach(button => {
    button.addEventListener('click', function() {
        const userId = this.getAttribute('data-user-id');
        fetchEvaluatorDetails(userId);
    });
});

function fetchEvaluatorDetails(userId) {
    fetch('fetch_evaluator_details.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ user_id: userId }),
    })
    .then(response => response.json())
    .then(data => {
    if (data.error) {
        alert(data.error);
        return;
    }

    // Fill basic info
    document.getElementById('evaluatorName').value = data.name;
    document.getElementById('evaluatorEmail').value = data.email;
    document.getElementById('evaluatorPhone').value = data.phone;
    document.getElementById('activeCompetitions').value = data.active_count;
    document.getElementById('completedCompetitions').value = data.completed_count;

    // Fill active competitions details
    const detailDiv = document.getElementById('activeCompetitionDetails');
    detailDiv.innerHTML = ''; // Clear previous data

    if (data.active_competitions.length > 0) {
        data.active_competitions.forEach((comp, index) => {
            const compInfo = `
                <div class="p-2 border rounded bg-gray-50">
                    <strong>${index + 1}. ${comp.name}</strong><br>
                    <span><b>Start:</b> ${comp.evaluation_start_date}</span><br>
                    <span><b>End:</b> ${comp.evaluation_end_date}</span><br>
                    <span><b>Result:</b> ${comp.result_declaration_date}</span>
                </div>
            `;
            detailDiv.innerHTML += compInfo;
        });
    } else {
        detailDiv.innerHTML = '<em>No active competitions</em>';
    }

    // Show modal
    document.getElementById('viewevaluatorModal').classList.remove('hidden');
})

    .catch(error => {
        console.error('Error fetching evaluator details:', error);
        alert('Something went wrong. Please try again.');
    });
}


document.getElementById('closeevaluatorModal').addEventListener('click', () => {
    document.getElementById('viewevaluatorModal').classList.add('hidden');
});

</script>

</body>
</html>
