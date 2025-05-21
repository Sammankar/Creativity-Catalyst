<?php 
include "connection.php";
include "header.php"; // assuming this includes <head> assets

$evaluator_id = $_SESSION['user_id']; // Assuming evaluator's user_id is stored in session

// Fetch all competitions and left join evaluator assignment
$sql = "
    SELECT 
        c.competition_id, c.name, c.description, c.rules, c.recommended_submissions, 
        c.number_of_files, c.max_submissions_per_college, c.college_registration_start_date, 
        c.college_registration_end_date, c.student_submission_start_date, c.student_submission_end_date, 
        c.evaluation_start_date, c.evaluation_end_date, c.result_declaration_date, 
        c.total_prize_pool, c.top_ranks_awarded, c.created_by, c.created_at, c.updated_at, 
        c.competition_status,
        e.evaluator_id AS assigned_evaluator_id, e.assigned_at
    FROM competitions c
    LEFT JOIN evaluators e ON c.competition_id = e.competition_id AND e.user_id = ?
    ORDER BY c.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $evaluator_id);
$stmt->execute();
$result = $stmt->get_result();

$competitions = [];
while ($row = $result->fetch_assoc()) {
    $competitions[] = $row;
}
?>
<main class="h-full overflow-y-auto">
  <div class="container px-6 mx-auto grid">
    <h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">
      Competitions Dashboard
    </h2>

    <!-- Filters and Search in same row -->
    <div class="mb-6 flex flex-wrap-reverse items-center justify-between gap-4">
        <input type="text" id="searchInput" placeholder="Search by name..."
            class="px-4 py-2 border rounded-md shadow-sm w-full sm:w-auto sm:min-w-[250px] focus:ring focus:ring-indigo-200" />

        <div class="flex flex-wrap gap-2 justify-end">
            <button class="filter-btn px-4 py-2 font-semibold rounded-full shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2"
                style="background-color:rgb(197, 222, 255); color: black;" data-status="-1">All</button>
            <button class="filter-btn px-4 py-2 font-semibold rounded-full shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2"
                style="background-color: #FFD700; color: black;" data-status="0">Not Assigned</button>
            <button class="filter-btn px-4 py-2 font-semibold rounded-full shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2"
                style="background-color: #90EE90; color: black;" data-status="1">In Progress</button>
            <button class="filter-btn px-4 py-2 font-semibold rounded-full shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2"
                style="background-color: #FF7F7F; color: black;" data-status="2">Completed</button>
            <button id="resetFilters" class="px-4 py-2 font-semibold rounded-full shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2"
                style="background-color: #333; color: white;">Reset</button>
        </div>
    </div>

    <!-- Cards Grid -->
    <div id="competitionCards" class="grid gap-6" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
    <?php foreach ($competitions as $comp): ?>
        <?php
          $assigned = !empty($comp['assigned_evaluator_id']);
          $compStatus = $comp['competition_status'];

          if (!$assigned) {
              $filterStatus = 0; // Not Assigned
              $statusText = 'Not Assigned';
              $statusStyle = "background-color: #FFD700; color: black;";
          } else if ($compStatus == 1) {
              $filterStatus = 1; // In Progress
              $statusText = 'In Progress';
              $statusStyle = "background-color: #90EE90; color: black;";
          } else if ($compStatus == 2) {
              $filterStatus = 2; // Completed
              $statusText = 'Completed';
              $statusStyle = "background-color: #FF7F7F; color: black;";
          } else {
              $filterStatus = -1;
              $statusText = 'Unknown';
              $statusStyle = "background-color: gray; color: white;";
          }
        ?>
            <div class="card group transition-all duration-300 transform hover:scale-[1.015] hover:shadow-2xl bg-white border border-gray-200 p-6 rounded-2xl shadow-md"
                 data-name="<?= strtolower($comp['name']) ?>"
                 data-status="<?= $filterStatus ?>"
                 style="min-height: 160px; min-width: 240px; display: flex; flex-direction: column; justify-content: space-between;">
                <!-- Header -->
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="font-semibold text-lg text-indigo-700 group-hover:text-indigo-900"><?= htmlspecialchars($comp['name']) ?></span>
                        <button class="px-3 py-1 text-xs font-semibold rounded-full shadow-md focus:outline-none" style="<?= $statusStyle ?> pointer-events: none; opacity: 0.9;">
                            <?= $statusText ?>
                        </button>
                    </div>

                    <!-- Dates Info -->
                    <div class="text-sm text-gray-600 space-y-1 mb-5">
                        <p><strong>College Registration Start:</strong> <?= $comp['evaluation_start_date'] ?? 'N/A' ?></p>
                        <p><strong>End:</strong> <?= $comp['evaluation_end_date'] ?? 'N/A' ?></p>
                        <p><strong>Result Date:</strong> <?= $comp['result_declaration_date'] ?? 'N/A' ?></p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-2 mt-2">
                    <button class="view-btn bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition" onclick="window.location.href='competition_details.php?competition_id=<?= $comp['competition_id'] ?>'">
                        View
                    </button>
                    <?php if ($assigned): ?>
                        <button class="px-4 py-2 font-semibold rounded-lg text-sm ml-2 bg-gray-400 text-white cursor-not-allowed opacity-70"  style="background-color:rgb(197, 222, 255); color: black;">Allocated</button>
                    <?php else: ?>
                        <button class="participate-btn px-4 py-2 font-semibold rounded-lg text-sm ml-2 bg-blue-500 hover:bg-blue-600 text-white transition" style="background-color:rgb(197, 222, 255); color: black;">Not Allocated</button>
                    <?php endif; ?>
                    <?php
        $today = new DateTime();
        $evalStart = !empty($comp['evaluation_start_date']) ? new DateTime($comp['evaluation_start_date']) : null;
        $evalEnd = !empty($comp['evaluation_end_date']) ? new DateTime($comp['evaluation_end_date']) : null;

        $canView = $evalStart && $evalEnd && $today >= $evalStart && $today <= $evalEnd;
    ?><?php if ($canView): ?>
        <button class="view-btn bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition"
                onclick="window.location.href='view_submission_details.php?competition_id=<?= $comp['competition_id'] ?>'">
                View Submission
        </button>
    <?php else: ?>
        <button class="px-4 py-2 font-semibold rounded-lg text-sm bg-gray-300 text-gray-600 cursor-not-allowed" disabled
         title="Enabled after evaluation starts (<?= $evalStart ? $evalStart->format('Y-m-d') : 'N/A' ?>)">View Submission (Unavailable)</button>
    
    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
  </div>
</main>

<!-- Scripts -->
<script>
const filterButtons = document.querySelectorAll(".filter-btn");
const searchInput = document.getElementById("searchInput");
const cards = document.querySelectorAll(".card");
const resetFilters = document.getElementById("resetFilters");
let currentFilter = -1;
function filterCards() {
  const search = searchInput.value.toLowerCase();
  cards.forEach(card => {
    const name = card.dataset.name;
    const status = parseInt(card.dataset.status);
    const matchesSearch = name.includes(search);
    const matchesStatus = currentFilter === -1 || currentFilter === status;
    card.style.display = matchesSearch && matchesStatus ? "block" : "none";
  });
}
filterButtons.forEach(btn => {
  btn.addEventListener("click", () => {
    currentFilter = parseInt(btn.dataset.status);
    filterCards();
  });
});
searchInput.addEventListener("input", filterCards);
resetFilters.addEventListener("click", () => {
  searchInput.value = "";
  currentFilter = -1;
  filterCards();
});
</script>

<style>
.animate-fade-in {
  animation: fadeIn 0.3s ease-in-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}
.card:hover {
  transform: scale(1.03);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}
</style>
