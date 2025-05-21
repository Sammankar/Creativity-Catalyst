<?php
include 'connection.php';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/fontawesome.min.css">
    <style>.ie-panel{display: none;background: #212121;padding: 10px 0;box-shadow: 3px 3px 5px 0 rgba(0,0,0,.3);clear: both;text-align:center;position: relative;z-index: 1;} html.ie-10 .ie-panel, html.lt-ie-10 .ie-panel {display: block;}</style>
  </head>
  <body>
    <div class="ie-panel"><a href="http://windows.microsoft.com/en-US/internet-explorer/"><img src="images/ie8-panel/warning_bar_0000_us.jpg" height="42" width="820" alt="You are using an outdated browser. For a faster, safer browsing experience, upgrade for free today."></a></div>
    <div class="preloader">
      <div class="preloader-body">
        <div class="cssload-container"><span></span><span></span><span></span><span></span>
        </div>
      </div>
    </div>
    <div class="page">
      <div id="home">
        <!-- Page Header-->
        <header class="section page-header">
          <!-- RD Navbar-->
          <div class="rd-navbar-wrap">
            <nav class="rd-navbar rd-navbar-classic" data-layout="rd-navbar-fixed" data-sm-layout="rd-navbar-fixed" data-md-layout="rd-navbar-fixed" data-md-device-layout="rd-navbar-fixed" data-lg-layout="rd-navbar-static" data-lg-device-layout="rd-navbar-fixed" data-xl-layout="rd-navbar-static" data-xl-device-layout="rd-navbar-static" data-xxl-layout="rd-navbar-static" data-xxl-device-layout="rd-navbar-static" data-lg-stick-up-offset="46px" data-xl-stick-up-offset="46px" data-xxl-stick-up-offset="46px" data-lg-stick-up="true" data-xl-stick-up="true" data-xxl-stick-up="true">
              <div class="rd-navbar-main-outer">
                <div class="rd-navbar-main">
                  <!-- RD Navbar Panel-->
                  <div class="rd-navbar-panel">
                    <!-- RD Navbar Toggle-->
                    <button class="rd-navbar-toggle" data-rd-navbar-toggle=".rd-navbar-nav-wrap"><span></span></button>
                    <!-- RD Navbar Brand-->
                    <div class="rd-navbar-brand"><a class="brand" href="index.html"><img src="images/cc.png" alt="" width="223" height="30"/></a></div>
                  </div>
                  <div class="rd-navbar-main-element">
                    <div class="rd-navbar-nav-wrap">
                      <ul class="rd-navbar-nav">
                      <li class="rd-nav-item"><a class="rd-nav-link" href="index.html#home">Home</a></li>
<li class="rd-nav-item"><a class="rd-nav-link" href="index.html#services">Services</a></li>
<li class="rd-nav-item"><a class="rd-nav-link" href="index.html#projects">Product</a></li>
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
        <section class="section swiper-container swiper-slider swiper-slider-classic" data-loop="true" data-autoplay="4859" data-simulate-touch="true" data-direction="vertical" data-nav="false">
          <div class="swiper-wrapper text-center">
            <div class="swiper-slide ml-50" data-slide-bg="images/ban.png">
              <div class="swiper-slide-caption section-md">
                <div class="container">
                </div>
              </div>
            </div>
            <div class="swiper-slide" data-slide-bg="images/mainbanner2.png">
              <div class="swiper-slide-caption section-md">
              </div>
            </div>
            <div class="swiper-slide" data-slide-bg="images/mainbanner3.png">
              <div class="swiper-slide-caption section-md">
              </div>
            </div>
          </div>
          <!-- Swiper Pagination-->
          <div class="swiper-pagination__module">
            <div class="swiper-pagination__fraction"><span class="swiper-pagination__fraction-index">00</span><span class="swiper-pagination__fraction-divider">/</span><span class="swiper-pagination__fraction-count">00</span></div>
            <div class="swiper-pagination__divider"></div>
            <div class="swiper-pagination"></div>
          </div>
        </section>

      <!-- See all services-->
      <!-- Latest Projects-->
      <section class="section section-sm section-fluid bg-default text-center" id="projects">
        <div class="container-fluid">
        <h2 class="wow fadeInLeft text-3xl md:text-4xl font-semibold mb-2">Competitions</h2>

          <p class="" data-wow-delay=".1s">In our system, you can explore various competitions organized for different purposes.<br> You can view competition details, participate, and track the progress on the live leaderboard.</p></div>
            <section class="section section-sm section-bottom-70 section-fluid bg-default">
        
      </section>
      <?php 
$query = "SELECT * FROM competitions ORDER BY FIELD(competition_status, 1) DESC, created_at DESC";
$result = mysqli_query($conn, $query);

function formatDate($date) {
  return date("j F Y", strtotime($date));
}
?>

<div class="competition-slider-container">
  <div class="competition-slider">
    <?php
    $counter = 0;
    while ($row = mysqli_fetch_assoc($result)) {
      if ($counter % 3 === 0) echo '<div class="competition-row">';

      $compId = $row['competition_id'];
      $resultReleased = $row['result_released'];
    ?>
      <div class="group competition-card transform transition-transform duration-300 hover:scale-105 [perspective:1000px] ml-2">
        <div class="relative h-[420px] w-full transition-transform duration-700 [transform-style:preserve-3d] group-hover:[transform:rotateY(180deg)]">

          <!-- Front -->
          <div class="absolute inset-0 bg-white p-6 rounded-lg shadow-2xl border border-gray-200 [backface-visibility:hidden] flex flex-col justify-between text-left overflow-y-auto">
            <div>
              <h5 class="text-xl font-semibold text-gray-800 mb-2 flex items-center gap-2">
                <i class="fa-solid fa-trophy text-yellow-500"></i>
                <?= htmlspecialchars($row['name']) ?>
              </h5>
              <p class="text-sm text-gray-600 mb-2">
                <i class="fa-solid fa-circle-info text-indigo-600 mr-1"></i>
                <?= substr(htmlspecialchars($row['description']), 0, 80) . '...' ?>
              </p>
              <p class="text-sm text-gray-500">
                <i class="fa-solid fa-book text-indigo-600 mr-1"></i>
                <strong>Rules:</strong> <?= htmlspecialchars($row['rules']) ?>
              </p>
            </div>
            <div class="text-right text-xs text-gray-400 italic mt-4">Hover to flip</div>
          </div>

          <!-- Back -->
          <div class="absolute inset-0 bg-gradient-to-r from-indigo-700 to-purple-600 text-white p-5 rounded-lg overflow-y-auto [transform:rotateY(180deg)] [backface-visibility:hidden] text-left flex flex-col justify-between">
            <div>
              <h5 class="text-lg font-bold mb-4 flex items-center gap-2">
                <i class="fa-solid fa-clipboard-list"></i>
                <?= htmlspecialchars($row['name']) ?>
              </h5>
              <div class="space-y-1 text-sm mb-4">
                <div><i class="fa-solid fa-file-lines mr-1 text-white/80"></i> <strong>Max Files:</strong> <?= $row['number_of_files'] ?></div>
                <div><i class="fa-solid fa-school mr-1 text-white/80"></i> <strong>Max/Course:</strong> <?= $row['max_submissions_per_college'] ?></div>
                <div><i class="fa-regular fa-calendar-days mr-1 text-white/80"></i> <strong>Registration:</strong> <?= formatDate($row['college_registration_start_date']) ?> - <?= formatDate($row['college_registration_end_date']) ?></div>
                <div><i class="fa-solid fa-upload mr-1 text-white/80"></i> <strong>Submission:</strong> <?= formatDate($row['student_submission_start_date']) ?> - <?= formatDate($row['student_submission_end_date']) ?></div>
                <div><i class="fa-solid fa-scale-balanced mr-1 text-white/80"></i> <strong>Evaluation:</strong> <?= formatDate($row['evaluation_start_date']) ?> - <?= formatDate($row['evaluation_end_date']) ?></div>
                <div><i class="fa-solid fa-medal mr-1 text-white/80"></i> <strong>Results:</strong> <?= formatDate($row['result_declaration_date']) ?></div>
                <div><i class="fa-solid fa-sack-dollar mr-1 text-white/80"></i> <strong>Prize Pool:</strong> ₹<?= number_format($row['total_prize_pool']) ?></div>
                <div><i class="fa-solid fa-ranking-star mr-1 text-white/80"></i> <strong>Top Ranks:</strong> <?= $row['top_ranks_awarded'] ?></div>

                <?php
                $prizeQuery = "SELECT rank, prize_description FROM competition_prizes WHERE competition_id = " . $compId;
                $prizeResult = mysqli_query($conn, $prizeQuery);
                if (mysqli_num_rows($prizeResult) > 0) {
                  echo '<div class="mt-2">';
                  echo '<table class="w-full text-sm text-left mt-1 border border-white/30 rounded overflow-hidden">';
                  echo '<thead><tr class="bg-white/20 text-white text-xs uppercase tracking-wider"><th class="py-1 px-2">Rank</th><th class="py-1 px-2">Prize</th></tr></thead><tbody>';
                  while ($prize = mysqli_fetch_assoc($prizeResult)) {
                    echo "<tr class='border-t border-white/20'><td class='py-1 px-2'>#" . $prize['rank'] . "</td><td class='py-1 px-2'>" . htmlspecialchars($prize['prize_description']) . "</td></tr>";
                  }
                  echo '</tbody></table></div>';
                }
                ?>
              </div>
            </div>

            <div class="flex justify-between gap-2 mt-4">
              <button onclick="openModal(<?= htmlspecialchars(json_encode($row)) ?>)" class="text-white bg-white/20 hover:bg-white/30 px-3 py-1 rounded text-sm transition">
                <i class="fa-solid fa-eye mr-1"></i> View Details
              </button>

              <?php if ($resultReleased == 1): ?>
                <a href="competition-leaderboard.php?id=<?= $compId ?>" class="text-white border border-white hover:bg-white hover:text-indigo-700 px-3 py-1 rounded text-sm transition">
                  <i class="fa-solid fa-list-ol mr-1"></i> Leaderboard
                </a>
              <?php else: ?>
                <span class="cursor-not-allowed border border-white px-3 py-1 rounded text-sm text-white opacity-60" title="Result not released yet">
                  <i class="fa-solid fa-lock mr-1"></i> Leaderboard
                </span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php
      if ($counter % 3 === 2) echo '</div>';
      $counter++;
    }
    ?>
  </div>
</div>

<!-- Modal -->
<!-- Modal -->
<div id="detailsModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
  <div class="bg-white w-[90%] max-w-3xl max-h-[90vh] overflow-y-auto p-6 rounded-xl shadow-2xl space-y-4 relative">
    <div class="p-6">
      <h2 class="text-xl font-semibold mb-4 text-indigo-700 flex items-center gap-2">
        <i class="fa-solid fa-info-circle"></i> Competition Details
      </h2>
      <div id="modalContent" class="space-y-2 text-sm text-gray-700"></div>
      <div class="text-right mt-4">
        <button onclick="closeModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-1.5 rounded">Close</button>
      </div>
    </div>
  </div>
</div>


<!-- Buttons -->
<div class="competition-slider-nav mt-4">
  <button class="prev-btn" onclick="moveSlide(-1)">Prev</button>
  <button class="next-btn" onclick="moveSlide(1)">Next</button>
</div>

<!-- Styles -->
<style>
  .competition-slider-container {
    width: 100%;
    overflow: hidden;
  }

  .competition-slider {
    display: flex;
    transition: transform 0.3s ease-in-out;
  }

  .competition-row {
    display: flex;
    width: 100%;
    justify-content: space-between;
  }

  .competition-card {
    width: 30%;
    margin: 10px;
  }

  .prev-btn, .next-btn {
    background: linear-gradient(to right, #4f46e5, #8b5cf6);
    color: white;
    padding: 10px 20px;
    border: none;
    font-weight: bold;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.3s;
  }

  .prev-btn:hover, .next-btn:hover {
    background: linear-gradient(to right, #4338ca, #7c3aed);
  }

  .overflow-y-auto::-webkit-scrollbar {
    width: 6px;
  }

  .overflow-y-auto::-webkit-scrollbar-thumb {
    background-color: rgba(255, 255, 255, 0.4);
    border-radius: 3px;
  }
</style>

<!-- Scripts -->
<script>
  let currentIndex = 0;
  function moveSlide(direction) {
    const slider = document.querySelector('.competition-slider');
    const totalRows = document.querySelectorAll('.competition-row').length;
    currentIndex += direction;

    if (currentIndex < 0) currentIndex = totalRows - 1;
    if (currentIndex >= totalRows) currentIndex = 0;

    const offset = -(currentIndex * 100);
    slider.style.transform = `translateX(${offset}%)`;
  }

  function openModal(data) {
    const modal = document.getElementById('detailsModal');
    const content = document.getElementById('modalContent');
    content.innerHTML = `
      <div><strong>Name:</strong> ${data.name}</div>
      <div><strong>Description:</strong> ${data.description}</div>
      <div><strong>Rules:</strong> ${data.rules}</div>
      <hr class="my-2"/>
      <div><strong>College Registration:</strong> ${data.college_registration_start_date} to ${data.college_registration_end_date}</div>
      <div><strong>Student Submission:</strong> ${data.student_submission_start_date} to ${data.student_submission_end_date}</div>
      <div><strong>Evaluation:</strong> ${data.evaluation_start_date} to ${data.evaluation_end_date}</div>
      <div><strong>Result Declaration:</strong> ${data.result_declaration_date}</div>
      <div><strong>Prize Pool:</strong> ₹${data.total_prize_pool}</div>
    `;
    modal.classList.remove('hidden');
  }

  function closeModal() {
    document.getElementById('detailsModal').classList.add('hidden');
  }
</script>


</div>

        </div>
      </section>
      <footer class="section section-fluid footer-minimal context-dark">
        <div class="bg-gray-15">
          <div class="container-fluid">
            <div class="footer-minimal-bottom-panel text-sm-left">
              <div class="row row-10 align-items-md-center">
                <div class="col-sm-6 col-md-4 text-sm-right text-md-center">
                </div>
                <div class="col-sm-6 col-md-4 order-sm-first">
                  <p class="rights"><span>&copy;&nbsp;</span><span class="copyright-year"></span> <span>CC</span>
                  </p>
                </div>
                <div class="col-sm-6 col-md-4 text-md-right"><span><a href="https://genericworld.co.in/">All rights reserved.Design&nbsp;by&nbsp;CC</a></span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </footer>
    <!-- Global Mailform Output-->
    <div class="snackbars" id="form-output-global"></div>
    <!-- Javascript-->
    <script src="js/core.min.js"></script>
    <script src="js/script.js"></script>
    <!-- Include this in your HTML HEAD or before the closing </body> tag -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  </body>
</html>