<?php
ob_start();
include 'header.php';
include 'connection.php';

// Ensure 'id' is always passed correctly, otherwise, display an error
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("❌ Invalid or missing competition ID.");
}

$competition_id = (int) $_GET['id'];

// Step 1: Fetch competition details
$comp = $conn->query("SELECT * FROM competitions WHERE competition_id = $competition_id")->fetch_assoc();
if (!$comp) {
    die("❌ Competition not found or invalid ID.");
}

// Step 2: Fetch prizes
$prizes = [];
$prize_result = $conn->query("SELECT rank, prize_description FROM competition_prizes WHERE competition_id = $competition_id");
while ($row = $prize_result->fetch_assoc()) {
    $prizes[(int)$row['rank']] = $row['prize_description'];
}

// Step 3: Function to generate results
function generateCompetitionResults($competition_id, $conn) {
    $query = "
        SELECT e.submission_id, AVG(e.score) AS avg_score
        FROM evaluations e
        INNER JOIN student_submissions ss ON e.submission_id = ss.submission_id
        WHERE ss.competition_id = $competition_id
        GROUP BY e.submission_id
        ORDER BY avg_score DESC
    ";
    $result = $conn->query($query);

    $scores = [];
    while ($row = $result->fetch_assoc()) {
        $scores[] = $row;
    }

    $total = count($scores);
    if ($total === 0) return;

    $top_score = $scores[0]['avg_score'];

    foreach ($scores as $index => $row) {
        $submission_id = $row['submission_id'];
        $avg_score = $row['avg_score'];
        $percentile = ($index / max($total - 1, 1)) * 100;
        $adjusted_score = $top_score * ($percentile / 100);

        $stmt = $conn->prepare("INSERT INTO adjusted_scores (submission_id, adjusted_score, percentile) VALUES (?, ?, ?)");
        $stmt->bind_param("idd", $submission_id, $adjusted_score, $percentile);
        $stmt->execute();

        $rank = $index + 1;
        
        // Check if a prize is available for this rank
        $prize_awarded = isset($GLOBALS['prizes'][$rank]) ? $GLOBALS['prizes'][$rank] : '';

        // Insert into competition_results with prize_awarded
        $stmt2 = $conn->prepare("INSERT INTO competition_results (competition_id, submission_id, rank, prize_awarded, result_declared_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt2->bind_param("iiis", $competition_id, $submission_id, $rank, $prize_awarded);
        $stmt2->execute();
    }
}

// Step 4: Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generate_result'])) {
        generateCompetitionResults($competition_id, $conn);
        $conn->query("UPDATE competitions SET result_generated = 1 WHERE competition_id = $competition_id");
        $_SESSION['message'] = "✅ Result Generated Successfully.";
        $_SESSION['message_type'] = "success";
        header("Location: ?id=$competition_id");
        exit();
    }

    if (isset($_POST['verify_submission'])) {
        $sub_id = (int) $_POST['submission_id'];
        $conn->query("UPDATE student_submissions SET is_verified_by_super_admin = 1 WHERE submission_id = $sub_id");
        $_SESSION['message'] = "✅ Submission #$sub_id verified.";
        $_SESSION['message_type'] = "success";
        header("Location: ?id=$competition_id");
        exit();
    }

    if (isset($_POST['verify_all'])) {
        $conn->query("UPDATE student_submissions SET is_verified_by_super_admin = 1 WHERE competition_id = $competition_id");
        $_SESSION['message'] = "✅ All submissions verified successfully.";
        $_SESSION['message_type'] = "success";
        header("Location: ?id=$competition_id");
        exit();
    }

    if (isset($_POST['release_now'])) {
        $conn->query("UPDATE competitions SET result_released = 1 WHERE competition_id = $competition_id");
        $_SESSION['message'] = "🎉 Result Released Successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: ?id=$competition_id");
        exit();
    }
}

$message = isset($_SESSION['message']) ? $_SESSION['message'] : "";
$message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : "";
unset($_SESSION['message'], $_SESSION['message_type']);

// Pagination settings
$limit = 5; // Number of rows per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchEscaped = $conn->real_escape_string($search);
$searchCondition = '';

if (!empty($searchEscaped)) {
    $searchCondition = " AND (ss.submission_id LIKE '%$searchEscaped%' OR u.full_name LIKE '%$searchEscaped%' OR crs.name LIKE '%$searchEscaped%')";
}

// Fetch total records for pagination
$totalQuery = "SELECT COUNT(*) AS total FROM student_submissions ss
               JOIN users u ON ss.student_user_id = u.user_id
               LEFT JOIN courses crs ON u.course_id = crs.course_id
               LEFT JOIN competition_results cr ON ss.submission_id = cr.submission_id
               $searchCondition";
$totalResult = $conn->query($totalQuery);
$totalRow = $totalResult->fetch_assoc();
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $limit);

// Fetch paginated and filtered records
$query = "
    SELECT ss.submission_id, ss.student_user_id, ss.is_verified_by_super_admin,
           u.full_name, u.college_id, u.course_id,
           col.name AS college_name, crs.name AS course_name,
           cr.rank, adj.adjusted_score
    FROM student_submissions ss
    JOIN users u ON ss.student_user_id = u.user_id
    LEFT JOIN colleges col ON u.college_id = col.college_id
    LEFT JOIN courses crs ON u.course_id = crs.course_id
    LEFT JOIN adjusted_scores adj ON ss.submission_id = adj.submission_id
    LEFT JOIN competition_results cr ON ss.submission_id = cr.submission_id
    WHERE ss.competition_id = $competition_id
    $searchCondition
    ORDER BY cr.rank ASC
    LIMIT $limit OFFSET $offset";
$subs = $conn->query($query);

$not_verified = $conn->query("SELECT COUNT(*) AS pending FROM student_submissions WHERE competition_id = $competition_id AND is_verified_by_super_admin = 0")->fetch_assoc()['pending'];

?>

<!DOCTYPE html>
<html>
<head>
    <title>Declare Result - <?= htmlspecialchars($comp['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<main class="h-full overflow-y-auto">
  <div class="container px-6 mx-auto grid">
    <h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">
      📊 Declare Result: <?= htmlspecialchars($comp['name']) ?>
    </h2>

    <!-- 🔍 Search & Action Buttons Row -->
    <div class="flex justify-between items-center mb-6 flex-wrap gap-4">
      <!-- Left Side: Action Buttons -->
      <div class="flex flex-wrap gap-4">
        <?php if ($not_verified > 0): ?>
            <form method="POST">
                <button name="verify_all" class="px-4 py-2 bg-orange-600 text-white font-semibold rounded-md shadow-md hover:bg-orange-700">
                    ✅ Verify All Submissions (<?= $not_verified ?>)
                </button>
            </form>
        <?php else: ?>
            <button class="px-4 py-2 bg-green-500 text-white font-semibold rounded-md shadow-md cursor-default">
                ✔ Verified
            </button>
        <?php endif; ?>

        <?php if (!$comp['result_generated'] && $not_verified == 0): ?>
            <form method="POST">
                <button name="generate_result" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-md shadow-md hover:bg-blue-700">
                    🚀 Generate Result
                </button>
            </form>
        <?php elseif ($comp['result_generated']): ?>
            <button class="px-4 py-2 bg-blue-400 text-white font-semibold rounded-md shadow-md cursor-default">
                📊 Result Generated
            </button>
        <?php endif; ?>

        <?php if ($comp['result_generated'] && !$comp['result_released']): ?>
            <form method="POST">
                <button name="release_now" class="px-4 py-2 bg-purple-600 text-white font-semibold rounded-md shadow-md hover:bg-purple-700">
                    📢 Release Leaderboard
                </button>
            </form>
        <?php elseif ($comp['result_released']): ?>
            <button class="px-4 py-2 bg-purple-400 text-white font-semibold rounded-md shadow-md cursor-default">
                🏆 Leaderboard Released
            </button>
        <?php endif; ?>
        <a 
        href="competition_list.php" 
        class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-md shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2"
    >
        Back To Competition List
    </a>

      </div>

      <!-- Right Side: Search (if applicable in future) -->
      <form method="GET" class="flex items-center space-x-2">
    <input type="hidden" name="id" value="<?= $competition_id ?>">
    <input 
        type="text" 
        name="search" 
        value="<?= htmlspecialchars($search) ?>" 
        placeholder="Search students..." 
        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
    >
    <button 
        type="submit" 
        class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-md shadow-md hover:bg-blue-600"
    >
        Search
    </button>
</form>
</div>

    <!-- ✅ Toast Message -->
    <?php if (!empty($message)): ?>
        <div id="custom-popup" class="bg-white border <?= ($message_type === 'success') ? 'border-green-500 text-green-600' : 'border-red-500 text-red-600'; ?> px-6 py-3 rounded-lg shadow-lg flex items-center space-x-3 mb-4">
            <?php if ($message_type === 'success'): ?>
                <svg class="w-6 h-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            <?php else: ?>
                <svg class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M12 6a9 9 0 110 18A9 9 0 0112 6z" />
                </svg>
            <?php endif; ?>
            <span><?= htmlspecialchars($message) ?></span>
            <button onclick="closePopup()" class="ml-2 text-gray-700 font-bold">&times;</button>
        </div>

        <script>
            function closePopup() {
                document.getElementById("custom-popup").style.display = "none";
            }
            setTimeout(closePopup, 5000);
        </script>
    <?php endif; ?>

    <!-- Submissions Table -->
    <div class="bg-white p-6 rounded shadow">
    <h2 class="text-xl font-semibold mb-4">📄 Submissions</h2>

    <div class="w-full overflow-x-auto">
        <table class="w-full whitespace-no-wrap">
            <thead>
                <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                    <th class="px-4 py-3">Rank</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">College</th>
                    <th class="px-4 py-3">Course</th>
                    <th class="px-4 py-3">Adjusted Score</th>
                    <th class="px-4 py-3">Prize</th>
                    <th class="px-4 py-3">Verified</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y">
                <?php while ($row = $subs->fetch_assoc()): ?>
                    <tr class="text-gray-700">
                        <td class="px-4 py-3 font-semibold text-sm">
                            <?= $row['rank'] ?? '-' ?>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <?= htmlspecialchars($row['full_name']) ?>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <?= htmlspecialchars($row['college_name']) ?>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <?= htmlspecialchars($row['course_name']) ?>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <?= isset($row['adjusted_score']) ? number_format($row['adjusted_score'], 2) : '-' ?>
                        </td>
                        <td class="px-4 py-3 text-sm font-semibold text-indigo-700">
                            <?= isset($row['rank']) && isset($prizes[$row['rank']]) ? htmlspecialchars($prizes[$row['rank']]) : '-' ?>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <?php if ($row['is_verified_by_super_admin']): ?>
                                <span class="inline-block px-3 py-1 text-xs font-medium leading-5 text-green-700 bg-green-100 rounded-full">
                                    Verified
                                </span>
                            <?php else: ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="submission_id" value="<?= $row['submission_id'] ?>">
                                    <button name="verify_submission" 
                                            class="px-4 py-2 text-xs font-semibold text-white bg-yellow-500 rounded-full hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-300">
                                        Verify
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <!-- Pagination Section -->
<div class="flex justify-end mt-6">
  <nav class="flex items-center space-x-2">
    <!-- Back -->
    <?php if ($page > 1): ?>
        <a href="?id=<?= $competition_id ?>&page=<?= $page - 1 ?>&search=<?= urlencode($search ?? '') ?>"
           class="px-4 py-2 border border-gray-300 text-gray-600 rounded-md hover:bg-blue-500 hover:text-white">
            Back
        </a>
    <?php endif; ?>

    <!-- Page Numbers -->
    <?php 
    $start = max(1, $page - 2);
    $end = min($totalPages, $page + 2);
    for ($i = $start; $i <= $end; $i++): ?>
        <a href="?id=<?= $competition_id ?>&page=<?= $i ?>&search=<?= urlencode($search ?? '') ?>"
           class="px-4 py-2 border border-gray-300 rounded-md <?= $i == $page ? 'bg-blue-500 text-white' : 'text-gray-600 hover:bg-blue-500 hover:text-white' ?>">
            <?= $i ?>
        </a>
    <?php endfor; ?>

    <!-- Next -->
    <?php if ($page < $totalPages): ?>
        <a href="?id=<?= $competition_id ?>&page=<?= $page + 1 ?>&search=<?= urlencode($search ?? '') ?>"
           class="px-4 py-2 border border-gray-300 text-gray-600 rounded-md hover:bg-blue-500 hover:text-white">
            Next
        </a>
    <?php endif; ?>
  </nav>
</div>

    </div>
</div>
