<?php
session_start();
include 'connection.php';

if (!isset($_GET['report_id'])) {
    header("Location: Access_reports_logs.php");
    exit();
}

$report_id = $_GET['report_id'];
$user_id = $_SESSION['user_id'];

$conn = new mysqli("localhost", "root", "", "project"); // Update with your DB credentials
$query = "SELECT users_status FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($users_status);
$stmt->fetch();
$stmt->close();

// If the user's account is inactive (users_status = 0), log them out
if ($users_status == 0) {
    // Destroy the session and clear the "Remember Me" cookie
    session_destroy();
    setcookie('user_id', '', time() - 3600, '/'); // Expire the cookie
    header("Location: index.php"); // Redirect to login page
    exit();
}

// Fetch user role and access status
$query = "SELECT role, access_status FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$role = $user['role'];
$access_status = $user['access_status'];

if ($access_status == 0) {
    header("Location: sub_admin_access_logs.php"); // Redirect to Access_reports_logs.php
    exit();
  }

// Fetch the report details with Admin and Super-Admin names, including attachment
$query = "SELECT 
            adr.*, 
            sa.full_name AS changed_by_name, 
            sub_admin.full_name AS user_name
          FROM teacher_access_denial_reports adr
          JOIN users sa ON adr.changed_by = sa.user_id AND sa.role = 2
          JOIN users sub_admin ON adr.user_id = sub_admin.user_id AND sub_admin.role = 3
          WHERE adr.report_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $report_id);
$stmt->execute();
$result = $stmt->get_result();
$report = $result->fetch_assoc();

if (!$report) {
    echo "Report not found.";
    exit();
}

$attachments = json_decode($report['attachment'], true); // Decode JSON into an array
$attachment = isset($attachments[0]) ? $attachments[0] : ''; // Get the first file (if exists) // Assuming `attachment` column contains file path
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function noAttachmentAlert() {
            alert("No attachment available.");
        }
    </script>
</head>
<body class="bg-gray-100">
    <div class="max-w-4xl mx-auto p-6 bg-white shadow-md rounded-lg mt-10">
        <h1 class="text-2xl font-bold text-gray-800">Report Details</h1>

        <div class="mt-4">
            <p class="text-lg font-semibold">Subject: <span class="font-normal"><?php echo htmlspecialchars($report['subject']); ?></span></p>
            <p class="text-lg font-semibold">Description: <span class="font-normal"><?php echo nl2br(htmlspecialchars($report['body'])); ?></span></p>
            <p class="text-lg font-semibold">Created On: <span class="font-normal"><?php echo date('d M Y, g:i A', strtotime($report['created_at'])); ?></span></p>
            <p class="text-lg font-semibold">Status: 
                <span class="font-normal <?php echo $report['access_denial_report_status'] == 0 ? 'text-yellow-500' : 'text-green-500'; ?>">
                    <?php echo $report['access_denial_report_status'] == 0 ? 'Ongoing' : 'Resolved'; ?>
                </span>
            </p>
            <p class="text-lg font-semibold">Sub-Admin Name: <span class="font-normal"><?php echo htmlspecialchars($report['user_name']); ?></span></p>
            <p class="text-lg font-semibold">Access Restricted by (Admin): <span class="font-normal"><?php echo htmlspecialchars($report['changed_by_name']); ?></span></p>
        </div>

        <div class="mt-4">
            <p class="text-lg font-semibold mb-4">Attachment:</p>
            <?php if (!empty($attachment)) : ?>
    <?php
    $file_ext = pathinfo($attachment, PATHINFO_EXTENSION);
    $attachment_url = htmlspecialchars($attachment, ENT_QUOTES, 'UTF-8'); // Ensure proper escaping
    ?>
    <a href="download.php?file=<?php echo urlencode(basename($attachment)); ?>" class="ml-2 px-4 py-2 bg-blue-500 text-white font-semibold rounded-lg shadow-md hover:bg-blue-600">
        Download Attachment
    </a>
<?php else : ?>
    <button onclick="noAttachmentAlert()" class="px-4 py-2 bg-red-500 text-white font-semibold rounded-lg shadow-md hover:bg-red-600">
        No Attachment Available
    </button>
<?php endif; ?>

        </div>

        <div class="mt-6 flex justify-end">
            <a href="sub_admin_access_logs.php" class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-lg shadow-md hover:bg-blue-600">Back to Reports</a>
        </div>
    </div>
</body>
</html>
