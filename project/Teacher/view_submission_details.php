<?php
ob_start();
include 'header.php';
include "connection.php";
// Set the timezone to Asia/Kolkata
date_default_timezone_set('Asia/Kolkata');

// Get current date and time
$now = new DateTime();

$participant_id = $_GET['participant_id'] ?? null;
$competition_id = $_GET['competition_id'] ?? null;

if (!$participant_id || !$competition_id) {
    die("Invalid competition or participant.");
}

// Step 1: Get student_user_id
$query = "SELECT student_user_id FROM competition_participants WHERE participant_id = ? AND competition_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $participant_id, $competition_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) die("No participant found.");
$student_user_id = $row['student_user_id'];



// ✅ Handle Approve/Reject Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'approve') {
        // Approve both tables
        $stmt1 = $conn->prepare("UPDATE competition_participants SET is_verified_by_project_head = 1, verified_at = NOW() WHERE participant_id = ? AND competition_id = ?");
        $stmt1->bind_param("ii", $participant_id, $competition_id);
        $stmt1->execute();
        $stmt1->close();

        $stmt2 = $conn->prepare("UPDATE student_submissions SET is_verified_by_project_head = 1, updated_at = NOW() WHERE student_user_id = ? AND competition_id = ?");
        $stmt2->bind_param("ii", $student_user_id, $competition_id);
        $stmt2->execute();
        $stmt2->close();

        header("Location: view_submission_details.php?competition_id=$competition_id&participant_id=$participant_id&status=approved");
        exit();

    } elseif ($action === 'reject') {
        $query = "SELECT submitted_files FROM student_submissions WHERE student_user_id = ? AND competition_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $student_user_id, $competition_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $submission = $result->fetch_assoc();
        $stmt->close();

        $files = json_decode($submission['submitted_files'] ?? '', true);
        if (is_array($files)) {
            foreach ($files as $file) {
                $file_path = "../Student/" . $file;
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
        }

        $empty_files = json_encode([]); // returns "[]"
        $stmt2 = $conn->prepare("UPDATE student_submissions SET submitted_files = ?, title = '', is_verified_by_project_head = 2, updated_at = NOW() WHERE student_user_id = ? AND competition_id = ?");
        $stmt2->bind_param("sii", $empty_files, $student_user_id, $competition_id);
        
        $stmt2->execute();
        $stmt2->close();
        
        header("Location: view_submission_details.php?participant_id=$participant_id&competition_id=$competition_id&status=rejected");
        exit();
    }
}

// Student Info
$query = "SELECT full_name, email, current_semester FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_user_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

// Submission Details
$query = "SELECT submitted_files, submission_date, status, is_verified_by_project_head FROM student_submissions WHERE student_user_id = ? AND competition_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $student_user_id, $competition_id);
$stmt->execute();
$result = $stmt->get_result();
$submission = $result->fetch_assoc();
$stmt->close();

// Verification Status
$query = "SELECT is_verified_by_project_head FROM competition_participants WHERE participant_id = ? AND competition_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $participant_id, $competition_id);
$stmt->execute();
$result = $stmt->get_result();
$participant = $result->fetch_assoc();
$stmt->close();

$isVerified = ($submission['is_verified_by_project_head'] == 1 || $participant['is_verified_by_project_head'] == 1);
$verificationStatus = $isVerified ? 'Verified' : 'Not Verified';
$statusText = ($submission['status'] == 0) ? 'Submitted' : htmlspecialchars($submission['status']);
$submitted_files = !empty($submission['submitted_files']) ? json_decode($submission['submitted_files'], true) : [];
$hasSubmission = $submission && is_array($submitted_files) && count($submitted_files) > 0;
$canVerify = false;

$compQuery = "SELECT student_submission_start_date, student_submission_end_date FROM competitions WHERE competition_id = ?";
$stmt = $conn->prepare($compQuery);
$stmt->bind_param("i", $competition_id);
$stmt->execute();
$compResult = $stmt->get_result();
$compData = $compResult->fetch_assoc();
$stmt->close();

$submissionWindowStatus = ""; // default

if ($compData) {
    $start = new DateTime($compData['student_submission_start_date']);
    $end = new DateTime($compData['student_submission_end_date']);
    $end->setTime(23, 59, 59); // Set end date to the end of the day

    if ($now < $start) {
        $submissionWindowStatus = "not_started";  // Before start date
    } elseif ($now > $end) {
        $submissionWindowStatus = "ended";  // After end date
    } else {
        $submissionWindowStatus = "open";  // Within start and end date
    }
}


if ($submissionWindowStatus === 'ended') {
    // Auto-check only if not already verified
    if ($submission['is_verified_by_project_head'] == 0) {
        if (!empty($submission['submitted_files']) && count(json_decode($submission['submitted_files'], true)) > 0) {
            // Auto-approve
            $stmt = $conn->prepare("UPDATE student_submissions SET is_verified_by_project_head = 1, updated_at = NOW() WHERE student_user_id = ? AND competition_id = ?");
            $stmt->bind_param("ii", $student_user_id, $competition_id);
            $stmt->execute();
            $stmt->close();

            $stmt2 = $conn->prepare("UPDATE competition_participants SET is_verified_by_project_head = 1, verified_at = NOW() WHERE participant_id = ? AND competition_id = ?");
            $stmt2->bind_param("ii", $participant_id, $competition_id);
            $stmt2->execute();
            $stmt2->close();

        } else {
            // Auto-reject
            $stmt = $conn->prepare("UPDATE student_submissions SET is_verified_by_project_head = 2, updated_at = NOW() WHERE student_user_id = ? AND competition_id = ?");
            $stmt->bind_param("ii", $student_user_id, $competition_id);
            $stmt->execute();
            $stmt->close();

            $stmt2 = $conn->prepare("UPDATE competition_participants SET is_verified_by_project_head = 2, verified_at = NOW() WHERE participant_id = ? AND competition_id = ?");
            $stmt2->bind_param("ii", $participant_id, $competition_id);
            $stmt2->execute();
            $stmt2->close();
        }

        // Refresh to load updated verification state
        header("Location: view_submission_details.php?competition_id=$competition_id&participant_id=$participant_id");
        exit();
    }
}

?>

<!-- HTML Starts -->
<main class="h-full overflow-y-auto mt-8">
    <div class="container px-6 mx-auto grid">
        <div class="container mx-auto p-6 mt-8">
            <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-200">
                <h1 class="text-xl font-semibold mb-6">Student and Submission Details</h1>

                <?php if (isset($_GET['status'])): ?>
                    <div class="bg-green-100 text-green-800 p-4 rounded-md mb-6">
                        <?= $_GET['status'] === 'approved' ? "✅ Submission approved successfully." : "❌ Submission rejected and files deleted." ?>
                    </div>
                <?php endif; ?>

                <!-- Student Info -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Student Name</label>
                    <input type="text" readonly value="<?= htmlspecialchars($student['full_name']) ?>" class="mt-1 block w-full px-3 py-2 border bg-gray-100 rounded-md shadow-sm">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="text" readonly value="<?= htmlspecialchars($student['email']) ?>" class="mt-1 block w-full px-3 py-2 border bg-gray-100 rounded-md shadow-sm">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Current Semester</label>
                    <input type="text" readonly value="<?= htmlspecialchars($student['current_semester']) ?>" class="mt-1 block w-full px-3 py-2 border bg-gray-100 rounded-md shadow-sm">
                </div>

                <!-- Submission Info -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Submission Date</label>
                    <input type="text" readonly value="<?= htmlspecialchars($submission['submission_date']) ?>" class="mt-1 block w-full px-3 py-2 border bg-gray-100 rounded-md shadow-sm">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <input type="text" readonly value="<?= $statusText ?>" class="mt-1 block w-full px-3 py-2 border bg-gray-100 rounded-md shadow-sm">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Verification Status</label>
                    <input type="text" readonly value="<?= $verificationStatus ?>" class="mt-1 block w-full px-3 py-2 border bg-gray-100 rounded-md shadow-sm">
                </div>

                <!-- Uploaded Files -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700">Uploaded Files</label>
                    <div class="mt-2 space-y-2 bg-gray-100 p-4 rounded-md">
                        <?php
                        if (is_array($submitted_files) && count($submitted_files)) {
                            foreach ($submitted_files as $file) {
                                $file_path = '../Student/' . $file;
                                echo '<p><a href="' . $file_path . '" target="_blank" class="text-blue-600 hover:underline">📄 ' . htmlspecialchars(basename($file)) . '</a></p>';
                            }
                        } else {
                            echo '<p>No files uploaded for this submission.</p>';
                        }
                        ?>
                    </div>
                </div>

                <!-- Approve Button -->
                <!-- Approve & Reject Buttons -->
<div class="flex justify-end space-x-4">
<div class="flex justify-end space-x-4 mt-4">
<?php if ($isVerified): ?>
    <button class="px-4 py-2 bg-green-100 text-black font-semibold rounded-md cursor-not-allowed" disabled>Already Approved</button>

<?php elseif (!$hasSubmission): ?>
    <!-- No submission -->
    <button class="px-4 py-2 bg-gray-300 text-black font-semibold rounded-md cursor-not-allowed" disabled>Approve (After Submission)</button>
    <button class="px-4 py-2 bg-gray-300 text-black font-semibold rounded-md cursor-not-allowed" disabled>Reject (After Submission)</button>

<?php elseif ($submissionWindowStatus === 'not_started'): ?>
    <!-- Before submission window -->
    <button class="px-4 py-2 bg-yellow-300 text-black font-semibold rounded-md cursor-not-allowed" disabled>Approve (Not Started)</button>
    <button class="px-4 py-2 bg-yellow-300 text-black font-semibold rounded-md cursor-not-allowed" disabled>Reject (Not Started)</button>

<?php elseif ($submissionWindowStatus === 'ended'): ?>
    <!-- After submission window -->
    <button class="px-4 py-2 bg-red-300 text-black font-semibold rounded-md cursor-not-allowed" disabled>Approve (Submission Ended)</button>
    <button class="px-4 py-2 bg-red-300 text-black font-semibold rounded-md cursor-not-allowed" disabled>Reject (Submission Ended)</button>

<?php else: ?>
    <!-- Valid submission and within date -->
    <button id="approveBtn" class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-md hover:bg-green-700">Approve</button>
    <button id="rejectBtn" class="px-4 py-2 bg-red-600 text-white font-semibold rounded-md hover:bg-red-700">Reject</button>
<?php endif; ?>
</div>


</div>
<div class="flex justify-start">
    <a href="view_selected_students.php?competition_id=<?= urlencode($_GET['competition_id'] ?? 1) ?>" 
       class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-lg shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 mt-2">
        Back
    </a>
</div>
            </div>
            
        </div>
    </div>
</main>

<!-- Approve Popup -->
<div id="statusSuccessPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-semibold text-gray-700 text-center mb-4">Are you sure?</h2>
        <p class="text-gray-600 text-center">Do you really want to approve this submission?</p>
        <form method="POST" class="mt-6 flex justify-center gap-4">
            <input type="hidden" name="action" value="approve">
            <button type="button" id="cancelPopup" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-gray-500">Cancel</button>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-green-700 ml-2">Approve</button>
        </form>
    </div>
</div>
<div id="rejectPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-semibold text-gray-700 text-center mb-4">Are you sure?</h2>
        <p class="text-gray-600 text-center">Do you really want to reject this submission? Uploaded files will be deleted.</p>
        <form method="POST" class="mt-6 flex justify-center gap-4">
            <input type="hidden" name="action" value="reject">
            <button type="button" id="cancelRejectPopup" class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">Cancel</button>
            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Reject</button>
        </form>
    </div>
</div>


<!-- JS for Popup -->
<script>
    const approveBtn = document.getElementById('approveBtn');
    const popup = document.getElementById('statusSuccessPopup');
    const cancelPopup = document.getElementById('cancelPopup');

    const rejectBtn = document.getElementById('rejectBtn');
    const rejectPopup = document.getElementById('rejectPopup');
    const cancelRejectPopup = document.getElementById('cancelRejectPopup');

    approveBtn?.addEventListener('click', () => popup.classList.remove('hidden'));
    cancelPopup?.addEventListener('click', () => popup.classList.add('hidden'));

    rejectBtn?.addEventListener('click', () => rejectPopup.classList.remove('hidden'));
    cancelRejectPopup?.addEventListener('click', () => rejectPopup.classList.add('hidden'));
</script>
<style>.group:hover .group-hover\:block {
    display: block;
}
</style>