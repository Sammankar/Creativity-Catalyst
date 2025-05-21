<?php
include "header.php";
include "connection.php";

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'teacher';

$loggedInUserId = $_SESSION['user_id'];
$college_id = 0;
$course_id = 0;

// Fetch both college_id and course_id
$getDetails = $conn->query("SELECT college_id, course_id FROM users WHERE user_id = $loggedInUserId");
if ($getDetails->num_rows > 0) {
  $userData = $getDetails->fetch_assoc();
  $college_id = $userData['college_id'];
  $course_id = $userData['course_id'];
}
?>

<main class="h-full overflow-y-auto bg-gray-50 dark:bg-gray-900">
  <div class="container px-4 py-6 mx-auto grid max-w-5xl">
    <h2 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-6 flex items-center gap-3">
      Notifications
    </h2>

    <!-- Filter Dropdown -->
    <div class="mb-6">
      <label for="notificationType" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Filter by Type
      </label>
      <select id="notificationType" onchange="filterNotifications(this.value)"
              class="block w-1/2 md:w-1/3 px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:ring-blue-500 focus:border-blue-500">
        <option value="teacher" <?php echo ($filter == 'teacher') ? 'selected' : ''; ?>>Teacher Login Requests</option>
        <option value="guide" <?php echo ($filter == 'guide') ? 'selected' : ''; ?>>Guide Login Requests</option>
        <option value="student" <?php echo ($filter == 'student') ? 'selected' : ''; ?>>Student Login Requests</option>
        <option value="calendar" <?php echo ($filter == 'calendar') ? 'selected' : ''; ?>>Academic Calendar</option>
        <option value="competition" <?php echo ($filter == 'competition') ? 'selected' : ''; ?>>Competitions</option>
      </select>
    </div>

    <?php if (in_array($filter, ['teacher', 'guide', 'student'])) { ?>
      <div class="space-y-4 overflow-y-auto">
        <?php
        $role = 4;
        $permissionCondition = "";
        $redirectUrl = "#";

        if ($filter == 'guide') {
          $permissionCondition = "AND guide_permission = 1";
          $redirectUrl = "guide_list.php";
        } elseif ($filter == 'teacher') {
          $permissionCondition = "AND guide_permission = 0";
          $redirectUrl = "teacher_list.php";
        } elseif ($filter == 'student') {
          $role = 5;
          $redirectUrl = "student_list.php";
        }

        $conn->query("UPDATE users SET is_read_by = 1 WHERE role = $role $permissionCondition AND college_id = $college_id AND course_id = $course_id AND is_read_by = 0");

        $query = "SELECT user_id, full_name, email, access_status, users_status, created_at, is_read_by 
                  FROM users 
                  WHERE role = $role $permissionCondition AND college_id = $college_id AND course_id = $course_id
                  ORDER BY created_at DESC";

        $result = $conn->query($query);

        if ($result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
            $access = $row['access_status'] == 1 ? "Approved" : "Restricted";
            $login = $row['users_status'] == 1 ? "Approved" : "Restricted";
            $border_class = ($row['is_read_by'] == 0) ? 'border-2 border-blue-500' : 'border border-blue-200';
        ?>
            <a href="<?php echo $redirectUrl; ?>" class="block">
              <div class="p-6 mx-auto bg-white rounded-2xl shadow hover:shadow-lg transition-all duration-200 dark:bg-gray-800 hover:bg-blue-50 dark:hover:bg-gray-700 max-w-3xl <?php echo $border_class; ?>">
                <div class="flex items-center justify-between mb-1">
                  <div class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                    <?php echo htmlspecialchars($row['full_name']); ?>
                  </div>
                  <span class="text-sm px-2 py-1 rounded-full 
                      <?php echo $row['access_status'] == 1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo $access; ?>
                  </span>
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                  <?php echo htmlspecialchars($row['email']); ?>
                </div>
                <div class="text-sm text-gray-700 dark:text-gray-300 mb-1">
                  <span class="font-medium">Dashboard Access:</span> <?php echo $access; ?> |
                  <span class="font-medium">Login Access:</span> <?php echo $login; ?>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  Requested on: <?php echo date("d M Y, h:i A", strtotime($row['created_at'])); ?>
                </div>
              </div>
            </a>
        <?php
          }
        } else {
          echo "<p class='text-gray-600 dark:text-gray-300'>No " . ucfirst($filter) . " Login Requests found.</p>";
        }
        ?>
      </div>
    <?php } ?>

    <!-- Calendar and Competition sections already handled (as you said not to touch) -->
    <?php if ($filter == 'calendar') { ?>
  <div id="calendar-notifications" class="space-y-4">
    <?php
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime($today . ' -1 day'));

    $college_courses_query = "SELECT * FROM college_courses WHERE college_id = $college_id";
    $college_courses_result = $conn->query($college_courses_query);

    $valid_course_ids = [];
    $course_status_map = [];

    while ($cc = $college_courses_result->fetch_assoc()) {
      $course_id = $cc['course_id'];
      $course_status = $cc['college_course_status'];
      $valid_course_ids[] = $course_id;
      $course_status_map[$course_id] = $course_status;
    }

    $calendar_query = "SELECT ac.*, c.name AS course_name, u.full_name AS creator_name
                       FROM academic_calendar ac
                       JOIN courses c ON ac.course_id = c.course_id
                       JOIN users u ON ac.created_by = u.user_id
                       ORDER BY ac.created_at DESC";
    $calendar_result = $conn->query($calendar_query);

    if ($calendar_result->num_rows > 0) {
      while ($calendar = $calendar_result->fetch_assoc()) {
        $course_id = $calendar['course_id'];
        $calendar_id = $calendar['id'];

        if (!in_array($course_id, $valid_course_ids)) continue;

        $start = $calendar['start_date'];
        $end = $calendar['end_date'];
        $status = $calendar['status'];
        $is_editable = $calendar['is_editable'];
        $semester = $calendar['semester'];
        $year = $calendar['academic_year'];
        $course_name = $calendar['course_name'];
        $creator_name = $calendar['creator_name'];
        $result_declared = $calendar['declare_result'];

        $notif_msg = "";
        $show = false;

        if ($course_status_map[$course_id] == 0) {
          $notif_msg = "Academic Calendar for <strong>$course_name</strong>, Semester <strong>$semester</strong>, Year <strong>$year</strong> was created by <strong>$creator_name</strong>, but your college has not activated this course.";
          $show = true;
        } else {
          if ($yesterday == $start) {
            $notif_msg = "Academic Calendar for <strong>$course_name</strong>, Semester <strong>$semester</strong>, Year <strong>$year</strong> is starting tomorrow. Created by <strong>$creator_name</strong>.";
            $show = true;
          } elseif ($yesterday == $end) {
            $notif_msg = "Academic Calendar for <strong>$course_name</strong>, Semester <strong>$semester</strong>, Year <strong>$year</strong> is ending tomorrow. Created by <strong>$creator_name</strong>.";
            $show = true;
          } elseif ($status == 0) {
            $notif_msg = "Calendar for <strong>$course_name</strong>, Semester <strong>$semester</strong>, Year <strong>$year</strong> is unreleased. Created by <strong>$creator_name</strong>. Please release it.";
            $show = true;
          } elseif ($status == 1 && $is_editable == 0) {
            $notif_msg = "Academic Calendar for <strong>$course_name</strong>, Semester <strong>$semester</strong>, Year <strong>$year</strong> is <strong>In Progress</strong>. Created by <strong>$creator_name</strong>.";
            $show = true;
          } elseif ($status == 1 && $is_editable == 1) {
            $notif_msg = "Academic Calendar for <strong>$course_name</strong>, Semester <strong>$semester</strong>, Year <strong>$year</strong> is <strong>Completed</strong>. Created by <strong>$creator_name</strong>.";
            $show = true;
          }

          if ($today > $end && $result_declared == 0) {
            $notif_msg = "Declare Result for <strong>$course_name</strong>, Semester <strong>$semester</strong>, Year <strong>$year</strong>. Created by <strong>$creator_name</strong>.";
            $show = true;
          }
        }

        if ($show) {
          $read_check = $conn->prepare("SELECT * FROM notification_reads WHERE user_id = ? AND calendar_id = ?");
          $read_check->bind_param("ii", $_SESSION['user_id'], $calendar_id);
          $read_check->execute();
          $read_result = $read_check->get_result();
          $is_read = $read_result->num_rows > 0;

          $border_class = $is_read ? 'border border-blue-200' : 'border-2 border-blue-600';
    ?>
      <a href="dashboard.php" class="block">
        <div class="p-6 bg-white rounded-2xl shadow hover:shadow-lg transition-all duration-200 dark:bg-gray-800 hover:bg-blue-50 dark:hover:bg-gray-700 <?php echo $border_class; ?>">
          <div class="flex justify-between items-center mb-2">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
              📅 Academic Calendar
            </h3>
          </div>
          <div class="text-sm text-gray-700 dark:text-gray-200">
            <?php echo $notif_msg; ?>
          </div>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Generated on: <?php echo date("d M Y, h:i A", strtotime($calendar['created_at'])); ?>
          </p>
        </div>
      </a>
    <?php
        }
      }
    } else {
      echo "<p class='text-gray-600 dark:text-gray-300'>No Academic Calendar notifications available.</p>";
    }
    ?>
  </div>
<?php } ?>


<?php if ($filter == 'competition') { ?>
<div id="competition-notifications" class="space-y-4">
<?php
$today = date("Y-m-d");

// Fetch competition data
$compQuery = "SELECT * FROM competitions ORDER BY created_at DESC";
$compResult = $conn->query($compQuery);

if ($compResult->num_rows > 0) {
    while ($comp = $compResult->fetch_assoc()) {
        $notif_msgs = [];
        $comp_id = $comp['competition_id'];
        $compName = htmlspecialchars($comp['name']);

        // Match each date field with today
        $date_fields = [
            'college_registration_end_date' => "College registration for <strong>$compName</strong> ends today.",
            'college_registration_start_date' => "College registration for <strong>$compName</strong> starts today.",
            'student_submission_start_date' => "Student submissions for <strong>$compName</strong> start today.",
            'student_submission_end_date' => "Student submissions for <strong>$compName</strong> end today.",
            'evaluation_start_date' => "Evaluation for <strong>$compName</strong> starts today.",
            'evaluation_end_date' => "Evaluation for <strong>$compName</strong> ends today.",
            'result_declaration_date' => "Results for <strong>$compName</strong> declared today.",
            'created_at' => "Competition <strong>$compName</strong> has been created."
        ];

        foreach ($date_fields as $field => $message) {
            if ($today == date("Y-m-d", strtotime($comp[$field]))) {
                $notif_msgs[] = ['date' => $comp[$field], 'message' => $message];
            }
        }

        // If result released flag is set
        if ($comp['result_released'] == 1) {
            $notif_msgs[] = ['date' => $comp['updated_at'], 'message' => "Results for <strong>$compName</strong> have been released."];
        }

        // Check college participation
        $collegeCheckQuery = "SELECT * FROM college_competitions WHERE competition_id = $comp_id AND college_id = $college_id";
        $collegeCheckResult = $conn->query($collegeCheckQuery);
        $collegeParticipation = "";

        if ($collegeCheckResult->num_rows > 0) {
            $collegeData = $collegeCheckResult->fetch_assoc();
            $subAdminId = $collegeData['sub_admin_id'];

            // Get sub-admin details
            $userQuery = "SELECT full_name, course_id FROM users WHERE user_id = $subAdminId";
            $userResult = $conn->query($userQuery);
            if ($userResult->num_rows > 0) {
                $user = $userResult->fetch_assoc();
                $fullName = htmlspecialchars($user['full_name']);
                $courseId = $user['course_id'];

                // Get course name
                $courseQuery = "SELECT name FROM courses WHERE course_id = $courseId";
                $courseResult = $conn->query($courseQuery);
                $courseName = ($courseResult->num_rows > 0) ? htmlspecialchars($courseResult->fetch_assoc()['name']) : 'Unknown Course';

                $collegeParticipation = "<br><span class='text-xs text-green-600'>Participated by: $fullName ($courseName)</span>";
            }
        }

        // Sort messages by their original event date (latest first)
        usort($notif_msgs, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        // Display all matched notifications
        foreach ($notif_msgs as $notif) {
?>
    <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl shadow hover:shadow-lg transition-all duration-200 border border-blue-200">
        <div class="text-md font-medium text-gray-800 dark:text-gray-100 mb-1">
            🏆 Competition Notification
        </div>
        <div class="text-sm text-gray-700 dark:text-gray-300">
            <?php echo $notif['message'] . $collegeParticipation; ?>
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Generated on: <?php echo date("d M Y", strtotime($notif['date'])); ?>
        </div>
    </div>
<?php
        }
    }
} else {
    echo "<p class='text-gray-600 dark:text-gray-300'>No competition notifications available.</p>";
}
?>
</div>
<?php } ?>
  </div>
</main>

<script>
  function filterNotifications(type) {
    window.location.href = "notifications.php?filter=" + type;
  }
</script>
