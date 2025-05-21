<?php
ob_start();
include 'header.php';
include "connection.php";

$evaluator_id = $_SESSION['user_id'] ?? null;

$submission_id = $_GET['submission_id'] ?? null;
if (!$submission_id) {
    die("Invalid submission ID.");
}

// ✅ Step 1: Fetch submission info
$query = "
    SELECT 
        ss.submission_id,
        ss.submission_date,
        ss.submitted_files,
        ss.status,
        ss.competition_id,
        ss.student_user_id,
        u.full_name,
        u.email,
        u.college_id,
        u.course_id,
        u.current_semester,
        c.name AS competition_title,
        c.evaluation_end_date,
        clg.name AS college_name,
        crs.name AS course_name
    FROM student_submissions ss
    JOIN users u ON ss.student_user_id = u.user_id
    JOIN competitions c ON ss.competition_id = c.competition_id
    LEFT JOIN colleges clg ON u.college_id = clg.college_id
    LEFT JOIN courses crs ON u.course_id = crs.course_id
    WHERE ss.submission_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$result = $stmt->get_result();
$submission = $result->fetch_assoc();
$stmt->close();

if (!$submission) {
    die("Submission not found.");
}

$submitted_files = !empty($submission['submitted_files']) ? json_decode($submission['submitted_files'], true) : [];
$submission_date = $submission['submission_date'];
$statusText = ($submission['status'] == 0) ? 'Submitted' : htmlspecialchars($submission['status']);

$compID = $submission['competition_id'];
$evaluation_end_date = $submission['evaluation_end_date'];
$today = date('Y-m-d');
$evaluation_closed = (strtotime($today) > strtotime($evaluation_end_date));

// Handle Verify
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify']) && !$evaluation_closed) {
    $check = $conn->prepare("SELECT * FROM evaluations WHERE submission_id = ? AND evaluator_id = ?");
    $check->bind_param("ii", $submission_id, $evaluator_id);
    $check->execute();
    $checkResult = $check->get_result();

    if ($checkResult->num_rows > 0) {
        $conn->query("UPDATE evaluations SET is_verified = 1 WHERE submission_id = $submission_id AND evaluator_id = $evaluator_id");
    } else {
        $conn->query("INSERT INTO evaluations (submission_id, evaluator_id, is_verified) VALUES ($submission_id, $evaluator_id, 1)");
    }
}

// Handle Score Submission (Only if before deadline)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_score']) && !$evaluation_closed) {
    $score = intval($_POST['score']);
    $feedback = $_POST['feedback'];

    $check = $conn->prepare("SELECT * FROM evaluations WHERE submission_id = ? AND evaluator_id = ?");
    $check->bind_param("ii", $submission_id, $evaluator_id);
    $check->execute();
    $checkResult = $check->get_result();

    if ($checkResult->num_rows > 0) {
        $conn->query("UPDATE evaluations SET score = $score, feedback = '$feedback', evaluated_at = NOW() WHERE submission_id = $submission_id AND evaluator_id = $evaluator_id");
    } else {
        $conn->query("INSERT INTO evaluations (submission_id, evaluator_id, score, feedback, evaluated_at, is_verified) VALUES ($submission_id, $evaluator_id, $score, '$feedback', NOW(), 1)");
    }

    $_SESSION['message'] = "Score submitted successfully.";
    header("Location: view_submission_details_v2.php?submission_id=$submission_id");
    exit();
}

// Fetch evaluation record
$evalCheck = $conn->query("SELECT * FROM evaluations WHERE submission_id = $submission_id AND evaluator_id = $evaluator_id LIMIT 1");
$evaluation = $evalCheck->fetch_assoc();
$isVerified = $evaluation['is_verified'] ?? 0;
$existingScore = $evaluation['score'] ?? '';
$existingFeedback = $evaluation['feedback'] ?? '';

// Fetch total marks
$compRes = $conn->query("SELECT total_marks FROM competitions WHERE competition_id = $compID");
$totalMarks = $compRes->fetch_assoc()['total_marks'] ?? 100;

// ✅ Auto-assign default marks after deadline if none submitted
if ($evaluation_closed && $evaluation === null) {
    $conn->query("INSERT INTO evaluations (submission_id, evaluator_id, score, feedback, evaluated_at, is_verified) 
                  VALUES ($submission_id, $evaluator_id, 50, 'Good', NOW(), 1)");

    // Re-fetch to reflect changes
    $evalCheck = $conn->query("SELECT * FROM evaluations WHERE submission_id = $submission_id AND evaluator_id = $evaluator_id LIMIT 1");
    $evaluation = $evalCheck->fetch_assoc();
    $isVerified = $evaluation['is_verified'] ?? 0;
    $existingScore = $evaluation['score'] ?? '';
    $existingFeedback = $evaluation['feedback'] ?? '';
}
?>

<main class="h-full overflow-y-auto mt-8">
    <div class="container px-6 mx-auto grid">
        <div class="bg-white p-6 rounded shadow-lg border">
            <h2 class="text-xl font-bold mb-4">Submission Details - <?= htmlspecialchars($submission['competition_title']) ?></h2>
            <?php if (isset($_SESSION['message'])): ?>
    <div class="mt-4 text-green-700 font-medium"><?= $_SESSION['message']; unset($_SESSION['message']); ?></div>
<?php endif; ?>

            <div class="mb-4">
                <strong>Student Name:</strong> <?= htmlspecialchars($submission['full_name']) ?>
            </div>
            <div class="mb-4">
                <strong>Email:</strong> <?= htmlspecialchars($submission['email']) ?>
            </div>
            <div class="mb-4">
    <strong>College:</strong> <?= htmlspecialchars($submission['college_name'] ?? 'N/A') ?>
</div>
<div class="mb-4">
    <strong>Course:</strong> <?= htmlspecialchars($submission['course_name'] ?? 'N/A') ?>
</div>

            <div class="mb-4">
                <strong>Semester:</strong> <?= htmlspecialchars($submission['current_semester']) ?>
            </div>
            <div class="mb-4">
                <strong>Submission Date:</strong> <?= htmlspecialchars($submission_date) ?>
            </div>
            <div class="mb-4">
                <strong>Status:</strong> <?= $statusText ?>
            </div>

            <div class="mb-6">
                <strong>Submitted Files:</strong>
                <div class="bg-gray-100 p-3 rounded mt-2">
                    <?php if (is_array($submitted_files) && count($submitted_files)): ?>
                        <ul class="list-disc pl-5">
                            <?php foreach ($submitted_files as $file): ?>
                                <li><a href="../Student/<?= htmlspecialchars($file) ?>" target="_blank" class="text-blue-600 hover:underline"><?= htmlspecialchars(basename($file)) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>No files uploaded.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mt-8 bg-white p-6 rounded shadow border mb-4">
    <h3 class="text-lg font-bold mb-4">Evaluator Panel (Ends on <?= htmlspecialchars($evaluation_end_date) ?>)</h3>

    <?php if ($evaluation_closed): ?>
        <div class="mb-4 text-red-600 font-semibold">
            ⚠️ Evaluation period has ended.
        </div>
    <?php endif; ?>

    <!-- ✅ Show verification status -->
    <?php if ($isVerified): ?>
        <div class="mb-4">
            <span class="text-green-600 font-semibold">✅ Evaluator Verification Status: Verified</span>
        </div>
    <?php endif; ?>

    <!-- ✅ If evaluation is over, just show the result -->
    <?php if ($evaluation_closed): ?>
        <div class="mb-4">
            <strong>Total Marks:</strong> <?= htmlspecialchars($totalMarks) ?>
        </div>
        <div class="mb-4">
            <strong>Evaluator Score:</strong> <span class="text-blue-700 font-semibold"><?= htmlspecialchars($existingScore) ?></span>
        </div>
        <div class="mb-4">
            <strong>Evaluator Feedback:</strong>
            <div class="bg-gray-100 p-3 rounded mt-1"><?= nl2br(htmlspecialchars($existingFeedback)) ?></div>
        </div>

    <!-- ✅ Show Verify Button before deadline -->
    <?php elseif (!$isVerified): ?>
        <form method="POST">
            <button type="submit" name="verify" class="bg-green-100 text-black px-4 py-2 rounded hover:bg-yellow-600">
                ✅ Verify Submission
            </button>
        </form>

    <!-- ✅ Show Score/Feedback Form after verify -->
    <?php elseif ($isVerified && ($existingScore === '' || $existingScore === null)): ?>
        <div class="mb-4">
            <strong>Total Marks:</strong> <?= htmlspecialchars($totalMarks) ?>
        </div>
        <form method="POST">
            <div class="mb-4">
                <label class="block mb-2 font-medium">Score (out of <?= $totalMarks ?>)</label>
                <input type="number" name="score" min="0" max="<?= $totalMarks ?>" value="<?= htmlspecialchars($existingScore) ?>"
                       class="border border-gray-300 rounded px-4 py-2 w-full" required>
            </div>

            <div class="mb-4">
                <label class="block mb-2 font-medium">Feedback</label>
                <textarea name="feedback" rows="4"
                          class="border border-gray-300 rounded px-4 py-2 w-full"><?= htmlspecialchars($existingFeedback) ?></textarea>
            </div>

            <button type="submit" name="submit_score"
                    class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-green-700">
                💾 Submit Score & Feedback
            </button>
        </form>

    <!-- ✅ Read-only if score exists -->
    <?php else: ?>
        <div class="mb-4">
            <strong>Total Marks:</strong> <?= htmlspecialchars($totalMarks) ?>
        </div>
        <div class="mb-4">
            <strong>Evaluator Score:</strong> <span class="text-blue-700 font-semibold"><?= htmlspecialchars($existingScore) ?></span>
        </div>
        <div class="mb-4">
            <strong>Evaluator Feedback:</strong>
            <div class="bg-gray-100 p-3 rounded mt-1"><?= nl2br(htmlspecialchars($existingFeedback)) ?></div>
        </div>
    <?php endif; ?>
</div>


            <!-- Go back button -->
            <a href="view_submission_details.php?competition_id=<?= $submission['competition_id'] ?>" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mt-2">Back</a>
        </div>
    </div>
</main>
