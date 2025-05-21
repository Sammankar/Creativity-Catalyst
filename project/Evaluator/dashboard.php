<?php
include "header.php";
include "connection.php";

// Pagination setup
$limit = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Search (by name or description)
$search = isset($_GET['search']) ? $_GET['search'] : "";
$searchQuery = $search ? "WHERE name LIKE '%$search%' OR description LIKE '%$search%'" : "";

// Count total records
$totalQuery = "SELECT COUNT(*) AS total FROM competitions $searchQuery";
$totalResult = $conn->query($totalQuery);
$totalRecords = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

// Fetch competitions
$query = "SELECT competition_id, name, description, college_registration_start_date, result_declaration_date, competition_status, created_at
          FROM competitions 
          $searchQuery
          ORDER BY created_at DESC
          LIMIT $limit OFFSET $offset";
$result = $conn->query($query);
// Competition stats
$totalCompetitionsQuery = "SELECT COUNT(*) AS total FROM competitions";
$activeCompetitionsQuery = "SELECT COUNT(*) AS active FROM competitions WHERE competition_status = 1";
$inactiveCompetitionsQuery = "SELECT COUNT(*) AS inactive FROM competitions WHERE competition_status = 0";
$completedCompetitionsQuery = "SELECT COUNT(*) AS completed FROM competitions WHERE competition_status = 2";

$totalCompetitions = $conn->query($totalCompetitionsQuery)->fetch_assoc()['total'];
$activeCompetitions = $conn->query($activeCompetitionsQuery)->fetch_assoc()['active'];
$inactiveCompetitions = $conn->query($inactiveCompetitionsQuery)->fetch_assoc()['inactive'];
$completedCompetitions = $conn->query($completedCompetitionsQuery)->fetch_assoc()['completed'];

// Mapping status
function getStatusLabel($status) {
    switch ($status) {
        case 0: return '<span class="bg-red-600 text-white px-3 py-1 rounded-full text-xs font-semibold">Not-Started</span>';
        case 1: return '<span class="bg-green-100 text-black px-3 py-1 rounded-full text-xs font-semibold">In-Progress</span>';
        case 2: return '<span class="bg-blue-500 text-white px-3 py-1 rounded-full text-xs font-semibold">Finished</span>';
        default: return '<span class="bg-blue-500 text-white px-3 py-1 rounded-full text-xs font-semibold">Unknown</span>';
    }
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
              All Competitions
            </h2>
    <!-- Competition Cards -->
<div class="grid gap-6 mb-8 md:grid-cols-2 xl:grid-cols-4">
    <!-- Total Competitions -->
    <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
        <div class="p-3 mr-4 text-purple-500 bg-purple-100 rounded-full dark:text-purple-100 dark:bg-purple-500">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M13 7H7v6h6V7z"></path>
                <path fill-rule="evenodd" d="M5 3a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V5a2 2 0 00-2-2H5zm10 12H5V5h10v10z" clip-rule="evenodd"></path>
            </svg>
        </div>
        <div>
            <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">Total Competitions</p>
            <p class="text-lg font-semibold text-gray-700 dark:text-gray-200"><?php echo $totalCompetitions; ?></p>
        </div>
    </div>

    <!-- Active Competitions -->
    <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
        <div class="p-3 mr-4 text-green-500 bg-green-100 rounded-full dark:text-green-100 dark:bg-green-500">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 2a8 8 0 110 16 8 8 0 010-16zm-1 9.293l3.293-3.293a1 1 0 011.414 1.414l-4 4a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L9 11.293z" clip-rule="evenodd"></path>
            </svg>
        </div>
        <div>
            <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">Active Competitions</p>
            <p class="text-lg font-semibold text-gray-700 dark:text-gray-200"><?php echo $activeCompetitions; ?></p>
        </div>
    </div>

    <!-- Inactive Competitions -->
    <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
        <div class="p-3 mr-4 text-red-500 bg-red-100 rounded-full dark:text-red-100 dark:bg-red-500">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 2a8 8 0 110 16 8 8 0 010-16zm3.293 6.293a1 1 0 10-1.414-1.414L10 8.586 8.121 6.707a1 1 0 10-1.414 1.414L8.586 10l-1.879 1.879a1 1 0 101.414 1.414L10 11.414l1.879 1.879a1 1 0 101.414-1.414L11.414 10l1.879-1.879z" clip-rule="evenodd"></path>
            </svg>
        </div>
        <div>
            <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">Inactive Competitions</p>
            <p class="text-lg font-semibold text-gray-700 dark:text-gray-200"><?php echo $inactiveCompetitions; ?></p>
        </div>
    </div>

    <!-- Completed Competitions -->
    <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
        <div class="p-3 mr-4 text-blue-500 bg-blue-100 rounded-full dark:text-blue-100 dark:bg-blue-500">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M13 7H7v6h6V7z"></path>
                <path fill-rule="evenodd" d="M5 3a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V5a2 2 0 00-2-2H5zm10 12H5V5h10v10z" clip-rule="evenodd"></path>
            </svg>
        </div>
        <div>
            <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">Completed Competitions</p>
            <p class="text-lg font-semibold text-gray-700 dark:text-gray-200"><?php echo $completedCompetitions; ?></p>
        </div>
    </div>
</div>

        <h4 class="mb-4 text-lg font-semibold text-gray-600 dark:text-gray-300">
            All Competitions List
        </h4>

        <div class="w-full mb-8 overflow-hidden rounded-lg shadow-xs">
            <div class="w-full overflow-x-auto">
                <!-- Search -->
                <div class="flex justify-between items-center mb-4">
    <!-- Left: Add Course Button -->
    <a 
        href="#" 
        class="px-4 py-2"
    >
        
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
                <!-- Competitions Table -->
                <table class="w-full whitespace-no-wrap">
                    <thead>
                        <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                            <th class="px-4 py-3">Sr No.</th>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3">Start Date</th>
                            <th class="px-4 py-3">Result Date</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y dark:divide-gray-700">
                        <?php if ($result->num_rows > 0): ?>
                            <?php $srNo = $offset + 1; ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="text-gray-700">
                                    <td class="px-4 py-3 text-sm"><?php echo $srNo++; ?></td>
                                    <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars(substr($row['description'], 0, 50)) . '...'; ?></td>
                                    <td class="px-4 py-3 text-sm"><?php echo date("d M Y", strtotime($row['college_registration_start_date'])); ?></td>
                                    <td class="px-4 py-3 text-sm"><?php echo date("d M Y", strtotime($row['result_declaration_date'])); ?></td>
                                    <td class="px-4 py-3 text-sm"><?php echo getStatusLabel($row['competition_status']); ?></td>
                                    <td class="px-4 py-3 text-sm">
                                    <button 
                  class="px-4 py-2 text-black font-semibold rounded-full shadow-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 view-competition"
                  style="background-color: #ffff;"
                  data-id="<?php echo $row['competition_id']; ?>">
                  View
              </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="px-4 py-3 text-center text-sm text-gray-600">No competitions found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex justify-end mt-4">
                <nav class="flex items-center space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>"
                           class="px-4 py-2 border border-gray-300 text-gray-600 rounded-md hover:bg-blue-500 hover:text-white">
                            Back
                        </a>
                    <?php endif; ?>
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"
                           class="px-4 py-2 border border-gray-300 rounded-md <?php echo $i == $page ? 'bg-blue-500 text-white' : 'text-gray-600 hover:bg-blue-500 hover:text-white'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>"
                           class="px-4 py-2 border border-gray-300 text-gray-600 rounded-md hover:bg-blue-500 hover:text-white">
                            Next
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </div>
    <div id="viewCompetitionModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50 mt-50">
    <div class="bg-white rounded-lg shadow-lg w-[90%] max-w-4xl p-6 overflow-y-auto max-h-[90vh]">
        <h2 class="text-lg font-semibold mb-4">Competition Details</h2>

        <!-- Use flex to divide content into two columns -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Left Column -->
            <div class="space-y-4">
                <div>
                    <label class="font-semibold">Name:</label>
                    <input type="text" id="compName" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
                </div>
                <div>
                    <label class="font-semibold">College Reg. Start:</label>
                    <input type="text" id="collegeStart" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
                </div>
                <div>
                    <label class="font-semibold">Student Sub. Start:</label>
                    <input type="text" id="studentStart" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
                </div>
                <div>
                    <label class="font-semibold">Evaluation Start:</label>
                    <input type="text" id="evalStart" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
                </div>
                <div>
                    <label class="font-semibold">Rules:</label>
                    <textarea id="compRules" class="w-full px-3 py-2 border rounded bg-gray-100" readonly></textarea>
                </div>
                
            </div>

            <!-- Right Column -->
            <div class="space-y-4">
            <div>
                    <label class="font-semibold">Result Date:</label>
                    <input type="text" id="resultDate" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
                </div>           
                <div>
                    <label class="font-semibold">College Reg. End:</label>
                    <input type="text" id="collegeEnd" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
                </div>
                <div>
                    <label class="font-semibold">Student Sub. End:</label>
                    <input type="text" id="studentEnd" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
                </div>
                <div>
                    <label class="font-semibold">Evaluation End:</label>
                    <input type="text" id="evalEnd" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
                </div>
                <div>
                    <label class="font-semibold">Prize Pool:</label>
                    <input type="text" id="prizePool" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
                </div>
            </div>

        </div>

        <!-- Close Button -->
        <div class="mt-6">
            <button onclick="closeCompetitionModal()" class="w-full bg-blue-500 text-white py-2 rounded font-semibold">OK</button>
        </div>
    </div>
</div>

</div>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const viewButtons = document.querySelectorAll(".view-competition");
    const modal = document.getElementById("viewCompetitionModal");
    const closeModal = document.getElementById("closeCompetitionModal");

    viewButtons.forEach(button => {
        button.addEventListener("click", () => {
            const competitionId = button.dataset.id;

            fetch("fetch_competition_details.php?id=" + competitionId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const comp = data.competition;

                        document.getElementById("compName").value = comp.name;
                        document.getElementById("compRules").value = comp.rules;
                        document.getElementById("collegeStart").value = comp.college_registration_start_date;
                        document.getElementById("collegeEnd").value = comp.college_registration_end_date;
                        document.getElementById("studentStart").value = comp.student_submission_start_date;
                        document.getElementById("studentEnd").value = comp.student_submission_end_date;
                        document.getElementById("evalStart").value = comp.evaluation_start_date;
                        document.getElementById("evalEnd").value = comp.evaluation_end_date;
                        document.getElementById("resultDate").value = comp.result_declaration_date;
                        document.getElementById("prizePool").value = comp.total_prize_pool;

                        modal.classList.remove("hidden");
                    } else {
                        alert("Failed to fetch competition details.");
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("An error occurred.");
                });
        });
    });

    // Optional: Attach to global for inline onclick="closeCompetitionModal()"
    window.closeCompetitionModal = () => {
        modal.classList.add("hidden");
    };

    function getStatusText(status) {
        switch (parseInt(status)) {
            case 0: return "Inactive";
            case 1: return "Active";
            case 2: return "Completed";
            default: return "Unknown";
        }
    }
});
</script>
