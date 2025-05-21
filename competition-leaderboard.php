<?php
include 'connection.php';

if (!isset($_GET['id'])) {
    echo "Competition ID missing.";
    exit;
}

$competitionId = intval($_GET['id']);

$competition = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM competitions WHERE competition_id = $competitionId"));
if (!$competition) {
    echo "Competition not found.";
    exit;
}

function formatDate($date) {
    return date("j F Y", strtotime($date));
}

$resultQuery = "
SELECT 
    r.rank,
    u.full_name,
    c.name AS college_name,
    cr.name AS course_name,
    r.prize_awarded
FROM competition_results r
JOIN student_submissions ss ON r.submission_id = ss.submission_id
JOIN users u ON ss.student_user_id = u.user_id
JOIN colleges c ON u.college_id = c.college_id
JOIN courses cr ON u.course_id = cr.course_id
WHERE r.competition_id = $competitionId
ORDER BY r.rank ASC
";

$results = mysqli_query($conn, $resultQuery);
?>
<!DOCTYPE html>
<html class="wide wow-animation" lang="en">
<head>
    <title>CC</title>
    <meta name="format-detection" content="telephone=no">
    <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <link rel="icon" href="images/titleicon.png" type="image/x-icon">
    <!-- Stylesheets-->
    <link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Poppins:400,500,600%7CTeko:300,400,500%7CMaven+Pro:500">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/fonts.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .ie-panel{display: none;background: #212121;padding: 10px 0;box-shadow: 3px 3px 5px 0 rgba(0,0,0,.3);clear: both;text-align:center;position: relative;z-index: 1;}
        html.ie-10 .ie-panel, html.lt-ie-10 .ie-panel {display: block;}

        .leaderboard-table-wrapper {
            max-height: 400px;
            overflow-y: auto;
        }

        .leaderboard-table-wrapper::-webkit-scrollbar {
            width: 8px;
        }

        .leaderboard-table-wrapper::-webkit-scrollbar-thumb {
            background-color: #d1d5db;
            border-radius: 4px;
        }

        thead th {
            position: sticky;
            top: 0;
            background-color: #4f46e5; /* Indigo-600 */
            z-index: 1;
        }
    </style>
</head>
<body>
<div class="ie-panel"><a href="http://windows.microsoft.com/en-US/internet-explorer/"><img src="images/ie8-panel/warning_bar_0000_us.jpg" height="42" width="820" alt="You are using an outdated browser. For a faster, safer browsing experience, upgrade for free today."></a></div>
<div class="preloader">
    <div class="preloader-body">
        <div class="cssload-container"><span></span><span></span><span></span><span></span></div>
    </div>
</div>
<div class="page">
    <div id="home">
        <!-- Page Header-->
        <header class="section page-header">
            <div class="rd-navbar-wrap">
                <nav class="rd-navbar rd-navbar-classic" data-layout="rd-navbar-fixed">
                    <div class="rd-navbar-main-outer">
                        <div class="rd-navbar-main">
                            <div class="rd-navbar-panel">
                                <button class="rd-navbar-toggle" data-rd-navbar-toggle=".rd-navbar-nav-wrap"><span></span></button>
                                <div class="rd-navbar-brand">
                                    <a class="brand" href="index.html"><img src="images/cc.png" alt="" width="223" height="30"/></a>
                                </div>
                            </div>
                            <div class="rd-navbar-main-element">
                                <div class="rd-navbar-nav-wrap">
                                    <ul class="rd-navbar-nav">
                                        <li class="rd-nav-item"><a class="rd-nav-link" href="index.html#home">Home</a></li>
                                        <li class="rd-nav-item"><a class="rd-nav-link" href="index.html#services">Services</a></li>
                                        <li class="rd-nav-item"><a class="rd-nav-link" href="index.html#projects">Product</a></li>
                                        <li class="rd-nav-item"><a class="rd-nav-link" href="projects.php">Competition</a></li>
                                        <li class="rd-nav-item"><a class="rd-nav-link" href="index.html#team">Team</a></li>
                                        <li class="rd-nav-item"><a class="rd-nav-link" href="index.html#contacts">Contacts</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </nav>
            </div>
        </header>

        <!-- Competition Info and Leaderboard Combined -->
        <section class="section section-fluid bg-default text-center py-12">
            <div class="container max-w-6xl mx-auto px-4">
                <!-- Competition Info -->
                <div class="max-w-4xl mx-auto bg-white shadow-lg rounded-lg overflow-hidden mb-10">
                    <div class="p-6">
                        <h2 class="text-2xl font-semibold text-indigo-600"><?= htmlspecialchars($competition['name']) ?> - Leaderboard</h2>
                        <p class="text-sm text-gray-500 mt-2">Results declared on: <span class="font-medium text-gray-700"><?= formatDate($competition['result_declaration_date']) ?></span></p>
                        <p class="text-sm text-gray-500 mt-2">Submission Dates: <span class="font-medium text-gray-700"><?= formatDate($competition['student_submission_start_date']) ?> - <?= formatDate($competition['student_submission_end_date']) ?></span></p>
                        <p class="text-sm text-gray-500 mt-2">Evaluation: <span class="font-medium text-gray-700"><?= formatDate($competition['evaluation_start_date']) ?> - <?= formatDate($competition['evaluation_end_date']) ?></span></p>
                    </div>
                </div>

                <!-- Leaderboard Table -->
                <div class="overflow-x-auto bg-white shadow-lg rounded-lg p-4">
                    <!-- Search Box -->
                    <div class="mb-4 flex justify-end">
                        <div class="relative w-full md:w-1/3">
                            <input type="text" id="searchInput" class="block w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all" placeholder="Search by Student Name..." oninput="filterTable()">
                            <span class="absolute top-0 right-0 mt-3 mr-4 text-gray-500"><i class="fa-solid fa-search"></i></span>
                        </div>
                    </div>

                    <!-- Table Wrapper with Scroll -->
                    <div class="leaderboard-table-wrapper">
                        <table class="min-w-full table-auto divide-y divide-gray-200 bg-white">
                            <thead class="bg-indigo-600 text-white">
                                <tr>
                                    <th class="px-6 py-3 text-center text-sm font-semibold uppercase tracking-wider"><i class="fa-solid fa-ranking-star"></i> Rank</th>
                                    <th class="px-6 py-3 text-center text-sm font-semibold uppercase tracking-wider"><i class="fa-solid fa-user"></i> Student</th>
                                    <th class="px-6 py-3 text-center text-sm font-semibold uppercase tracking-wider"><i class="fa-solid fa-building-columns"></i> College</th>
                                    <th class="px-6 py-3 text-center text-sm font-semibold uppercase tracking-wider"><i class="fa-solid fa-book"></i> Course</th>
                                    <th class="px-6 py-3 text-center text-sm font-semibold uppercase tracking-wider"><i class="fa-solid fa-gift"></i> Prize</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 text-center">
                                <?php while ($row = mysqli_fetch_assoc($results)) { ?>
                                    <tr class="hover:bg-indigo-50 transition-all duration-300">
                                        <td class="px-6 py-4 font-bold text-lg text-indigo-600">#<?= $row['rank'] ?></td>
                                        <td class="px-6 py-4"><?= htmlspecialchars($row['full_name']) ?></td>
                                        <td class="px-6 py-4"><?= htmlspecialchars($row['college_name']) ?></td>
                                        <td class="px-6 py-4"><?= htmlspecialchars($row['course_name']) ?></td>
                                        <td class="px-6 py-4"><?= $row['prize_awarded'] ? htmlspecialchars($row['prize_awarded']) : '-' ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </section>

        <!-- Footer (if needed) -->

    </div>
</div>

<!-- Global Mailform Output-->
<div class="snackbars" id="form-output-global"></div>

<!-- JS -->
<script src="js/core.min.js"></script>
<script src="js/script.js"></script>
<script>
    function filterTable() {
        const searchTerm = document.getElementById("searchInput").value.toLowerCase();
        const rows = document.querySelectorAll("tbody tr");

        rows.forEach(row => {
            const studentName = row.cells[1].textContent.toLowerCase();
            if (studentName.includes(searchTerm)) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });
    }
</script>
</body>
</html>
