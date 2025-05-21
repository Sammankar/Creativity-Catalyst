<?php
include "header.php";
include "connection.php";

$competition_id = intval($_GET['competition_id']);

// Pagination setup
$limit = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchQuery = $search ? "AND (u.full_name LIKE '%$search%' OR col.name LIKE '%$search%')" : '';

// Get competition details (e.g., name, top ranks)
$competitionDetailsQuery = "SELECT name, top_ranks_awarded FROM competitions WHERE competition_id = $competition_id";
$competitionDetailsResult = $conn->query($competitionDetailsQuery);
$competition = $competitionDetailsResult->fetch_assoc();
$topRanksAwarded = (int)$competition['top_ranks_awarded'];

// Count total matching records
$totalQuery = "
    SELECT COUNT(*) AS total
    FROM competition_results cr
    JOIN student_submissions ss ON cr.submission_id = ss.submission_id
    JOIN users u ON ss.student_user_id = u.user_id
    JOIN colleges col ON ss.college_id = col.college_id
    WHERE cr.competition_id = $competition_id $searchQuery
";
$totalResult = $conn->query($totalQuery);
$totalRecords = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

// Fetch leaderboard data
$sql = "
    SELECT 
        cr.rank,
        u.full_name AS student_name,
        col.name AS college_name,
        cp.prize_description
    FROM competition_results cr
    JOIN student_submissions ss ON cr.submission_id = ss.submission_id
    JOIN users u ON ss.student_user_id = u.user_id
    JOIN colleges col ON ss.college_id = col.college_id
    LEFT JOIN competition_prizes cp 
        ON cr.competition_id = cp.competition_id AND cr.rank = cp.rank
    WHERE cr.competition_id = $competition_id $searchQuery
    ORDER BY cr.rank ASC
    LIMIT $limit OFFSET $offset
";
$result = $conn->query($sql);
?>

<main class="h-full overflow-y-auto">
  <div class="container px-6 mx-auto grid">
    <!-- Page Title -->
    <h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">
      Leaderboard
    </h2>

    <!-- Competition Title and Back Button -->
    <div class="flex items-center justify-between mb-4">
      <h4 class="text-lg font-semibold text-gray-600 dark:text-gray-300">
        Competition Name: <?= htmlspecialchars($competition['name']) ?>
      </h4>
      <a 
        href="competitions.php" 
        class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-md shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2"
      >
        Back To Competition Page
      </a>
    </div>

    <!-- Search Bar -->
    <div class="flex justify-between items-center mb-4">
      <div class="w-1/3"></div>
      <form method="GET" class="flex items-center space-x-2">
        <input type="hidden" name="competition_id" value="<?= $competition_id ?>">
        <input type="text" name="search" placeholder="Search student or college..." value="<?= htmlspecialchars($search) ?>" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        <button type="submit" class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-md shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">
          Search
        </button>
      </form>
    </div>

    <!-- Leaderboard Table -->
    <div class="w-full mb-8 overflow-hidden rounded-lg shadow-xs">
      <div class="w-full overflow-x-auto">
        <table class="w-full whitespace-no-wrap">
          <thead>
            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800">
              <th class="px-4 py-3">Rank</th>
              <th class="px-4 py-3">Student Name</th>
              <th class="px-4 py-3">College</th>
              <th class="px-4 py-3">Prize</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
            <?php if ($result->num_rows > 0): ?>
              <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                  $rank = (int)$row['rank'];
                  $bgClass = '';
                  $badge = '';

                  if ($rank === 1) {
                    $bgClass = 'bg-yellow-600 dark:bg-yellow-900';
                    $badge = '🥇';
                  } elseif ($rank === 2) {
                    $bgClass = 'bg-gray-200 dark:bg-gray-700';
                    $badge = '🥈';
                  } elseif ($rank === 3) {
                    $bgClass = 'bg-amber-200 dark:bg-yellow-800';
                    $badge = '🥉';
                  } elseif ($rank <= $topRanksAwarded) {
                    $bgClass = 'bg-green-100 dark:bg-green-800';
                    $badge = '🏅';
                  }
                ?>
                <tr class="text-gray-700 dark:text-gray-400 <?= $bgClass ?>">
                  <td class="px-4 py-3 font-semibold text-sm"><?= $badge ?> <?= $rank ?></td>
                  <td class="px-4 py-3 text-sm"><?= htmlspecialchars($row['student_name']) ?></td>
                  <td class="px-4 py-3 text-sm"><?= htmlspecialchars($row['college_name']) ?></td>
                  <td class="px-4 py-3 text-sm"><?= $row['prize_description'] ?: '—' ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="4" class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">No results found</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="flex justify-end mt-4">
        <nav class="flex items-center space-x-2">
          <?php if ($page > 1): ?>
            <a href="?competition_id=<?= $competition_id ?>&page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" class="px-4 py-2 border border-gray-300 text-gray-600 rounded-md hover:bg-blue-500 hover:text-white focus:outline-none">Back</a>
          <?php endif; ?>
          <?php 
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            for ($i = $startPage; $i <= $endPage; $i++): 
          ?>
            <a href="?competition_id=<?= $competition_id ?>&page=<?= $i ?>&search=<?= urlencode($search) ?>" class="px-4 py-2 border border-gray-300 text-gray-600 rounded-md focus:outline-none <?= $i == $page ? 'bg-blue-500 text-white' : 'hover:bg-blue-100' ?>">
              <?= $i ?>
            </a>
          <?php endfor; ?>
          <?php if ($page < $totalPages): ?>
            <a href="?competition_id=<?= $competition_id ?>&page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" class="px-4 py-2 border border-gray-300 text-gray-600 rounded-md hover:bg-blue-500 hover:text-white focus:outline-none">Next</a>
          <?php endif; ?>
        </nav>
      </div>
    </div>
  </div>
</main>
