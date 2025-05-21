<?php
include "header.php";
?>
<?php
include "connection.php";

// Get Total Colleges Count
$totalCollegesQuery = "SELECT COUNT(*) AS total_colleges FROM colleges";
$totalCollegesResult = $conn->query($totalCollegesQuery);
$totalColleges = $totalCollegesResult->fetch_assoc()['total_colleges'];

// Get Total Users Count (Excluding Super Admins)
$totalUsersQuery = "SELECT COUNT(*) AS total_users FROM users WHERE role != 1";
$totalUsersResult = $conn->query($totalUsersQuery);
$totalUsers = $totalUsersResult->fetch_assoc()['total_users'];

// Get Total Competitions Count
$totalCompetitionsQuery = "SELECT COUNT(*) AS total_competitions FROM competitions";
$totalCompetitionsResult = $conn->query($totalCompetitionsQuery);
$totalCompetitions = $totalCompetitionsResult->fetch_assoc()['total_competitions'];
?>

<main class="h-full overflow-y-auto">
  <div class="container px-6 mx-auto grid">
    <h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">Dashboard</h2>

    <div class="grid gap-6 mb-8 md:grid-cols-2 xl:grid-cols-4">
      <!-- Total Colleges -->
      <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
        <div class="p-3 mr-4 text-orange-500 bg-orange-100 rounded-full dark:text-orange-100 dark:bg-orange-500">
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path d="M3 3h14v2H3V3zm0 4h14v2H3V7zm0 4h14v2H3v-2zm0 4h14v2H3v-2z"></path>
          </svg>
        </div>
        <div>
          <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">Total Colleges</p>
          <p class="text-lg font-semibold text-gray-700 dark:text-gray-200"><?php echo $totalColleges; ?></p>
        </div>
      </div>

      <!-- Total Users (excluding Super Admins) -->
      <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
        <div class="p-3 mr-4 text-green-500 bg-green-100 rounded-full dark:text-green-100 dark:bg-green-500">
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 2a4 4 0 00-4 4v4a4 4 0 008 0V6a4 4 0 00-4-4z"></path>
            <path d="M4 12a8 8 0 1112 0H4z"></path>
          </svg>
        </div>
        <div>
          <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">Total Users</p>
          <p class="text-lg font-semibold text-gray-700 dark:text-gray-200"><?php echo $totalUsers; ?></p>
        </div>
      </div>

      <!-- Total Competitions -->
      <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
        <div class="p-3 mr-4 text-blue-500 bg-blue-100 rounded-full dark:text-blue-100 dark:bg-blue-500">
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path d="M3 1a1 1 0 000 2h14a1 1 0 000-2H3zm1 4a1 1 0 000 2h12a1 1 0 000-2H4zm-1 4a1 1 0 000 2h14a1 1 0 000-2H3zm0 4a1 1 0 000 2h14a1 1 0 000-2H3z"></path>
          </svg>
        </div>
        <div>
          <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">Total Competitions</p>
          <p class="text-lg font-semibold text-gray-700 dark:text-gray-200"><?php echo $totalCompetitions; ?></p>
        </div>
      </div>
    </div>
  </div>
</main>

