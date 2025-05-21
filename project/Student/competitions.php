<?php
include "connection.php";
include "header.php"; // assuming this includes <head> assets

// Fetch student and college details
$student_id = $_SESSION['user_id'];
$college_id = null;
$college_sql = "SELECT college_id FROM users WHERE user_id = $student_id";
$college_result = $conn->query($college_sql);
if ($college_result->num_rows > 0) {
    $college_id = $college_result->fetch_assoc()['college_id'];
}

// Fetch all competitions
$competitions = [];
$sql = "SELECT * FROM competitions ORDER BY created_at DESC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $competitions[] = $row;
}

// Get current date and time
$current_datetime = new DateTime("now", new DateTimeZone('Asia/Kolkata')); // Set timezone to your region
$current_datetime->setTimezone(new DateTimeZone('Asia/Kolkata')); // Set the correct timezone
$current_timestamp = $current_datetime->getTimestamp();
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
                style="background-color: #FFD700; color: black;" data-status="0">Not Started</button>
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
// Get competition status
$status = intval($comp['competition_status']);
$statusText = $status === 0 ? "Not Started" : ($status === 1 ? "In Progress" : "Completed");
$statusStyle = $status === 0 ? 'background-color: #FFD700; color: black;' : ($status === 1 ? 'background-color: #90EE90; color: black;' : 'background-color: #FF7F7F; color: black;');

// Check participation status
$competition_id = $comp['competition_id'];
$alreadyParticipated = false;

$check_sql = "SELECT 1 FROM college_competitions WHERE competition_id = $competition_id AND college_id = $college_id";
$check_res = $conn->query($check_sql);
if ($check_res->num_rows > 0) {
    $alreadyParticipated = true;
}

// Submission check
$submission_start_date = $comp['student_submission_start_date'];
$submission_end_date = $comp['student_submission_end_date'];

// Force start date at midnight (00:00:00) for comparison
$startDatetime = new DateTime($submission_start_date . ' 00:00:00', new DateTimeZone('Asia/Kolkata'));
$endDatetime = new DateTime($submission_end_date . ' 23:59:59', new DateTimeZone('Asia/Kolkata'));

// Get the timestamps for comparison
$startTimestamp = $startDatetime->getTimestamp();
$endTimestamp = $endDatetime->getTimestamp();

// Logic for enabling/disabling submission button
$canSubmit = $alreadyParticipated && ($current_timestamp >= $startTimestamp && $current_timestamp <= $endTimestamp);
$isBeforeStart = $current_timestamp < $startTimestamp;
$isAfterEnd = $current_timestamp > $endTimestamp;

// Check if student already submitted
$submissionExists = false;
$submittedFiles = ''; // Initialize variable
$check_submission_sql = "SELECT submitted_files, is_verified_by_project_head FROM student_submissions WHERE competition_id = ? AND student_user_id = ?";
$check_stmt = $conn->prepare($check_submission_sql);
$check_stmt->bind_param("ii", $competition_id, $student_id);
$check_stmt->execute();
$check_stmt->store_result();
$submissionExists = $check_stmt->num_rows > 0;

// Check if verification status is 2 (verified)
$isVerified = false;
if ($submissionExists) {
    $check_stmt->bind_result($submittedFiles, $is_verified_by_project_head);
    $check_stmt->fetch();
    $isVerified = ($is_verified_by_project_head == 2);

    // Assuming `submitted_files` is stored as a JSON string, decode it
    $submittedFiles = json_decode($submittedFiles, true); // Decode as associative array
    if ($submittedFiles === null) {
        $submittedFiles = []; // If decoding fails, treat it as empty
    }
}
?>

            <div class="card group transition-all duration-300 transform hover:scale-[1.015] hover:shadow-2xl bg-white border border-gray-200 p-6 rounded-2xl shadow-md"
                 data-name="<?= strtolower($comp['name']) ?>"
                 data-status="<?= $status ?>"
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
                        <p><strong>College Registration Start:</strong> <?= $comp['college_registration_start_date'] ?? 'N/A' ?></p>
                        <p><strong>End:</strong> <?= $comp['college_registration_end_date'] ?? 'N/A' ?></p>
                        <p><strong>Result Date:</strong> <?= $comp['result_declaration_date'] ?? 'N/A' ?></p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-2 mt-2">
                    <button class="view-btn bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition" onclick="window.location.href='competition_details.php?competition_id=<?= $comp['competition_id'] ?>'">
                        View
                    </button>
                    <?php if ($alreadyParticipated): ?>
                        <button class="px-4 py-2 font-semibold rounded-lg text-sm ml-2 bg-gray-400 text-white cursor-not-allowed opacity-70" style="background-color:rgb(197, 222, 255); color: black;">College Participated</button>
                    <?php else: ?>
                        <button class="participate-btn px-4 py-2 font-semibold rounded-lg text-sm ml-2 bg-blue-500 hover:bg-blue-600 text-white transition" style="background-color:rgb(197, 222, 255); color: black;">
                            Not Participate
                        </button>
                    <?php endif; ?>
                    <?php if ($submissionExists && $isVerified && empty($submittedFiles)): ?>
    <!-- Case: Submission exists, verified but no files -->
    <button class="submit-btn bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm transition ml-2"
            onclick="window.location.href='submit_again.php?competition_id=<?= $competition_id ?>'">
        Submit Again
    </button>

<?php elseif ($canSubmit && !$submissionExists): ?>
    <!-- Case: Allowed to submit and not submitted yet -->
    <button class="submit-btn bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition ml-2"
            onclick="window.location.href='submit_competition.php?competition_id=<?= $competition_id ?>'">
        Submit
    </button>

<?php elseif ($submissionExists): ?>
    <!-- Case: Submission exists -->
    <?php if ($comp['result_released'] == 1): ?>
        <button class="submit-btn bg-blue-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition ml-2"
                onclick="window.location.href='leaderboard.php?competition_id=<?= $competition_id ?>'">
            View Result
        </button>
    <?php else: ?>
        <button class="submit-btn bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition ml-2"
            onclick="window.location.href='view_submission.php?competition_id=<?= $competition_id ?>'">
        View Submission
    </button>
    <?php endif; ?>
<?php elseif ($isBeforeStart): ?>
    <!-- Case: Not Started yet -->
    <button class="submit-btn bg-blue-500 text-white px-4 py-2 rounded-lg text-sm cursor-not-allowed ml-2" disabled>
        Not Open Yet
    </button>

<?php elseif ($isAfterEnd): ?>
    <!-- Case: Submission ended -->
    <button class="submit-btn bg-blue-500 text-white px-4 py-2 rounded-lg text-sm cursor-not-allowed ml-2" disabled>
        Submission Closed
    </button>

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
<script>
// Ensure the button is enabled if the submission is within the allowed date range
const currentDate = new Date("<?= $current_datetime->format('Y-m-d H:i:s') ?>");
const canSubmitButton = document.querySelectorAll('.submit-btn');

canSubmitButton.forEach(button => {
    const isBeforeStart = button.classList.contains('bg-gray-400');
    const isAfterEnd = button.classList.contains('cursor-not-allowed');

    if (isBeforeStart || isAfterEnd) {
        button.disabled = true;
    }
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
