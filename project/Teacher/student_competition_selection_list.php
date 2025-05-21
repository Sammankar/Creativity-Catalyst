<?php
include "connection.php";
include "header.php";

$project_head_id = $_SESSION['user_id'];
$college_id = null;
$college_sql = "SELECT college_id FROM users WHERE user_id = $project_head_id";
$college_result = $conn->query($college_sql);
if ($college_result->num_rows > 0) {
    $college_id = $college_result->fetch_assoc()['college_id'];
}

// Fetch competitions where this college is registered
$sql = "
SELECT c.*, cc.college_competition_id, cc.sub_admin_id
FROM competitions c
JOIN college_competitions cc ON cc.competition_id = c.competition_id
WHERE cc.college_id = $college_id
ORDER BY c.created_at DESC
";

$competitions = [];
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    // Count current selected participants
    $cid = $row['competition_id'];
    $check_sql = "SELECT COUNT(*) as total FROM competition_participants 
                  WHERE competition_id = $cid AND college_id = $college_id";
    $check_res = $conn->query($check_sql);
    $row['selected_count'] = $check_res->fetch_assoc()['total'] ?? 0;

    $competitions[] = $row;
}
?>

<main class="h-full overflow-y-auto">
  <div class="container px-6 mx-auto grid">
    <h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">
      Competition Selection (Project Head Panel)
    </h2>

    <!-- Search & Filter -->
    <div class="mb-6 flex flex-wrap-reverse items-center justify-between gap-4">
        <input type="text" id="searchInput" placeholder="Search competitions..."
            class="px-4 py-2 border rounded-md shadow-sm w-full sm:w-auto sm:min-w-[250px] focus:ring focus:ring-indigo-200" />
            <div class="flex flex-wrap gap-2 justify-end">
            <button class="filter-btn px-4 py-2 font-semibold rounded-full shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2"
                style="background-color:rgb(197, 222, 255); color: black;" data-status="-1">All</button>
            <button class="filter-btn px-4 py-2 font-semibold rounded-full shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2"
                style="background-color: #FFD700; color: black;" data-status="0">Not Started</button>
            <button class="filter-btn px-4 py-2 font-semibold rounded-full shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2"
                style="background-color: #90EE90; color: black;" data-status="1">In Progress</button>
            <button class="filter-btn px-4 py-2 font-semibold rounded-full shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2"
                style="background-color: #FF7F7F; color: black;" data-status="2">Completed</button>
            <button id="resetFilters" class="px-4 py-2 font-semibold rounded-full shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2"
                style="background-color: #333; color: white;">Reset</button>
        </div>
    </div>

    <!-- Competition Cards -->
    <div id="competitionCards" class="grid gap-6" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
        <?php foreach ($competitions as $comp): ?>
            <?php
                $status = (int) $comp['competition_status'];
                $statusText = $status === 0 ? "Not Started" : ($status === 1 ? "In Progress" : "Completed");
                $statusStyle = $status === 0 ? '#FFD700' : ($status === 1 ? '#90EE90' : '#FF7F7F');

                $now = date('Y-m-d');
                $canSelectStudents = ($status === 1 && $now >= $comp['college_registration_start_date'] && $now <= $comp['college_registration_end_date']);

                $max_allowed = (int) $comp['max_submissions_per_college']; // max per course
                $current_selected = (int) $comp['selected_count'];
            ?>
            <div class="card group bg-white border border-gray-200 p-6 rounded-xl shadow-md hover:shadow-xl transition" 
                data-name="<?= strtolower($comp['name']) ?>" 
                data-status="<?= $status ?>">
                <div>
                    <div class="flex justify-between items-center mb-3">
                        <span class="font-semibold text-lg text-indigo-700"><?= htmlspecialchars($comp['name']) ?></span>
                        <span class="px-2 py-1 text-xs rounded-full shadow" style="background-color: <?= $statusStyle ?>;"><?= $statusText ?></span>
                    </div>
                    <div class="text-sm text-gray-600 space-y-1 mb-4">
                        <p><strong>College Registration:</strong> <?= $comp['college_registration_start_date'] ?> to <?= $comp['college_registration_end_date'] ?></p>
                        <p><strong>Submission Period:</strong> <?= $comp['student_submission_start_date'] ?> to <?= $comp['student_submission_end_date'] ?></p>
                        <p><strong>Result Date:</strong> <?= $comp['result_declaration_date'] ?></p>
                        <p><strong>Selected:</strong> <?= $current_selected ?>/<?= $max_allowed ?></p>
                    </div>
                </div>

                <div class="flex gap-2 mt-4">
                    <a href="competition_details.php?competition_id=<?= $comp['competition_id'] ?>" 
                       class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700 transition">View</a>

                       <?php
    $now = date('Y-m-d');
    $registrationEnded = ($now > $comp['college_registration_end_date']);
    $canSelectStudents = (
        $status === 1 &&
        !$registrationEnded &&
        $now >= $comp['college_registration_start_date']
    );
?>

<?php if ($canSelectStudents): ?>
    <?php if ($current_selected < $max_allowed): ?>
        <a href="select_students.php?competition_id=<?= $comp['competition_id'] ?>" 
           class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700 transition ml-2">
            Select Students
        </a>
    <?php else: ?>
        <a href="view_selected_students.php?competition_id=<?= $comp['competition_id'] ?>" 
           class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-green-700 transition ml-2">
            View Students
        </a>
    <?php endif; ?>
<?php elseif ($current_selected > 0): ?>
    <a href="view_selected_students.php?competition_id=<?= $comp['competition_id'] ?>" 
       class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-green-700 transition ml-2">
        View Students
    </a>
<?php else: ?>
    <button class="bg-green-100 text-gray-600 px-4 py-2 rounded-md text-sm cursor-not-allowed ml-2" disabled>
        Selection Closed
    </button>
<?php endif; ?>


                </div>
            </div>
        <?php endforeach; ?>
    </div>
  </div>
</main>

<script>
const filterButtons = document.querySelectorAll(".filter-btn");
const searchInput = document.getElementById("searchInput");
const cards = document.querySelectorAll(".card");
const resetFilters = document.getElementById("resetFilters");
let currentFilter = null;

function filterCards() {
    const searchTerm = searchInput.value.toLowerCase();
    cards.forEach(card => {
        const name = card.dataset.name;
        const status = parseInt(card.dataset.status);
        const matchesSearch = name.includes(searchTerm);
        const matchesStatus = currentFilter === null || currentFilter === status;
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
    currentFilter = null;
    filterCards();
});
</script>

<style>
.card:hover {
  transform: scale(1.02);
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
</style>
