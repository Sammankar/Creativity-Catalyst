<?php
include "connection.php";
include "header.php"; // assuming this includes <head> assets
$sub_admin_id = $_SESSION['user_id'];
$college_id = null;
$college_sql = "SELECT college_id FROM users WHERE user_id = $sub_admin_id";
$college_result = $conn->query($college_sql);
if ($college_result->num_rows > 0) {
    $college_id = $college_result->fetch_assoc()['college_id'];
}

$competitions = [];
$sql = "SELECT * FROM competitions ORDER BY created_at DESC";
$result = $conn->query($sql);
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
    <!-- Filters and Search in same row -->
<div class="mb-6 flex flex-wrap-reverse items-center justify-between gap-4">
  <!-- Search Left -->
  <input type="text" id="searchInput" placeholder="Search by name..."
         class="px-4 py-2 border rounded-md shadow-sm w-full sm:w-auto sm:min-w-[250px] focus:ring focus:ring-indigo-200" />

  <!-- Filters Right -->
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
<!-- Cards Grid -->
<div id="competitionCards" class="grid gap-6" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
    <?php foreach ($competitions as $comp): ?>
        <?php
            $status = intval($comp['competition_status']);
            $statusText = $status === 0 ? "Not Started" : ($status === 1 ? "In Progress" : "Completed");
            $statusStyle = $status === 0 ? 'background-color: #FFD700; color: black;' : ($status === 1 ? 'background-color: #90EE90; color: black;' : 'background-color: #FF7F7F; color: black;');
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
                  <?php
// check participation
$competition_id = $comp['competition_id'];
$alreadyParticipated = false;

$check_sql = "SELECT 1 FROM college_competitions WHERE competition_id = $competition_id AND sub_admin_id = $sub_admin_id";
$check_res = $conn->query($check_sql);
if ($check_res->num_rows > 0) {
    $alreadyParticipated = true;
}
?>
                <button class="view-btn bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition" onclick="window.location.href='competition_details.php?competition_id=<?= $comp['competition_id'] ?>'">
                    View
                </button>
                <?php if ($alreadyParticipated): ?>
                    <button class="px-4 py-2 font-semibold rounded-lg text-sm ml-2 bg-gray-400 text-white cursor-not-allowed opacity-70" style="background-color:rgb(197, 222, 255); color: black;">Already Participated</button>
                <?php else: ?>
                    <button class="participate-btn px-4 py-2 font-semibold rounded-lg text-sm ml-2 bg-blue-500 hover:bg-blue-600 text-white transition"
                            data-details='<?= json_encode($comp) ?>'>Participate</button>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

    </div>
  </div>

  <!-- View Popup -->
  <!-- View Academic Modal -->
  <div id="viewPopup" class="fixed inset-0 flex items-center justify-center backdrop-blur-sm bg-white/30 hidden z-50">
  <div class="bg-white rounded-lg shadow-lg w-96 p-6 relative animate-fade-in">
    <button id="closePopup" class="absolute top-2 right-3 text-gray-500 hover:text-gray-700 text-xl font-bold">&times;</button>
    <h2 class="text-lg font-semibold mb-4">Competition Details</h2>

    <div class="mb-2">
      <label class="font-semibold">Competition Name:</label>
      <input type="text" id="popupname" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
    </div>

    <div class="mb-2">
      <label class="font-semibold">Status:</label>
      <input type="text" id="status" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
    </div>

    <div class="mb-2">
      <label class="font-semibold">College Registration Start Date:</label>
      <input type="text" id="collegeStartDate" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
    </div>

    <div class="mb-2">
      <label class="font-semibold">College Registration End Date:</label>
      <input type="text" id="collegeEndDate" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
    </div>

    <div class="mb-2">
      <label class="font-semibold">Result Declaration Date:</label>
      <input type="text" id="resultDate" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
    </div>

    <div class="mb-4">
      <label class="font-semibold">Max Submissions Per College:</label>
      <input type="text" id="maxSubmissions" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
    </div>

    <button id="confirmClose" class="w-full bg-blue-500 text-white py-2 rounded font-semibold">OK</button>
  </div>
</div>

<!-- Participate Popup -->
<div id="participatePopup" class="fixed inset-0 flex items-center justify-center backdrop-blur-sm bg-white/30 hidden z-50">
  <div class="bg-white rounded-lg shadow-lg w-96 p-6 relative animate-fade-in">
    <button id="closeParticipate" class="absolute top-2 right-3 text-gray-500 hover:text-gray-700 text-xl font-bold">&times;</button>
    <h2 class="text-lg font-semibold mb-4">Confirm Participation</h2>

    <div class="mb-2">
      <label class="font-semibold">Competition Name:</label>
      <input type="text" id="participateName" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
    </div>

    <div class="mb-2">
      <label class="font-semibold">College Registration Start:</label>
      <input type="text" id="participateStart" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
    </div>

    <div class="mb-2">
      <label class="font-semibold">College Registration End:</label>
      <input type="text" id="participateEnd" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
    </div>

    <div class="mb-2">
      <label class="font-semibold">Result Date:</label>
      <input type="text" id="participateResult" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
    </div>

    <div class="mb-4">
      <label class="font-semibold">Created At:</label>
      <input type="text" id="participateCreatedAt" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
    </div>

    <button id="confirmParticipate" class="w-full bg-blue-600 text-white py-2 rounded font-semibold">OK</button>
    <div class="flex justify-center mt-4">
  <button id="cancelParticipate" class="w-full bg-blue-600 text-white py-2 rounded font-semibold">
    Cancel
  </button>
</div>

  </div>
</div>

</main>


<!-- Scripts -->
<script>
const filterButtons = document.querySelectorAll(".filter-btn");
const searchInput = document.getElementById("searchInput");
const cards = document.querySelectorAll(".card");
const resetFilters = document.getElementById("resetFilters");

const viewButtons = document.querySelectorAll(".view-btn");
const popup = document.getElementById("viewPopup");
const closePopup = document.getElementById("closePopup");
const popupname = document.getElementById("popupname");
const popupDesc = document.getElementById("popupDesc");
const popupDetails = document.getElementById("popupDetails");

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

viewButtons.forEach(button => {
  button.addEventListener("click", () => {
    const data = JSON.parse(button.dataset.details);

    document.getElementById("popupname").value = data.name || '';
    document.getElementById("status").value =
      data.competition_status == 0
        ? 'Not Started'
        : data.competition_status == 1
        ? 'In Progress'
        : 'Completed';

    document.getElementById("collegeStartDate").value = data.college_registration_start_date || 'N/A';
    document.getElementById("collegeEndDate").value = data.college_registration_end_date || 'N/A';
    document.getElementById("resultDate").value = data.result_declaration_date || 'N/A';
    document.getElementById("maxSubmissions").value = data.max_submissions_per_college || 'N/A';

    popup.classList.remove("hidden");
  });
});


document.getElementById("closePopup").addEventListener("click", () => {
  popup.classList.add("hidden");
});

document.getElementById("confirmClose").addEventListener("click", () => {
  popup.classList.add("hidden");
});


closePopup.addEventListener("click", () => {
  popup.classList.add("hidden");
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

<script>
const participatePopup = document.getElementById("participatePopup");
const participateBtns = document.querySelectorAll(".participate-btn");

participateBtns.forEach(btn => {
  btn.addEventListener("click", () => {
    const data = JSON.parse(btn.dataset.details);
    document.getElementById("participateName").value = data.name;
    document.getElementById("participateStart").value = data.college_registration_start_date || 'N/A';
    document.getElementById("participateEnd").value = data.college_registration_end_date || 'N/A';
    document.getElementById("participateResult").value = data.result_declaration_date || 'N/A';

    // format created_at
    const date = new Date(data.created_at);
    const formatted = date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    document.getElementById("participateCreatedAt").value = formatted;

    participatePopup.dataset.cid = data.competition_id; // store id for submission
    participatePopup.classList.remove("hidden");
  });
});

// Close the popup when "X" or close button is clicked
document.getElementById("closeParticipate").addEventListener("click", () => {
  participatePopup.classList.add("hidden");
});

// Close the popup and redirect to competitions.php when "Cancel" is clicked
document.getElementById("cancelParticipate").addEventListener("click", () => {
  window.location.href = 'competitions.php'; // Redirect to competitions page
});

// Participation Confirm (AJAX)
document.getElementById("confirmParticipate").addEventListener("click", () => {
  const competition_id = participatePopup.dataset.cid;

  fetch("participate_competition.php", {
    method: "POST",
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `competition_id=${competition_id}`
  })
  .then(res => res.text())
  .then(data => {
    if (data === "success") {
      alert("Participation successful!");
      location.reload(); // Refresh to update button
    } else {
      alert("Error: " + data);
    }
  });
});
</script>

