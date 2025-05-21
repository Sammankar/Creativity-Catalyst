<?php
include 'header.php';
include 'connection.php';

if (!isset($_POST['submission_ids']) || !isset($_POST['student_id'])) {
    echo "Submission IDs or Student ID missing.";
    exit;
}

$submission_ids = $_POST['submission_ids'];
$student_user_id = $_POST['student_id']; // Provided via hidden input
$guide_user_id = $_SESSION['user_id']; // Logged-in guide
$academic_year = $_POST['academic_year'] ?? '';
$semester = $_POST['semester'] ?? '';
$course = $_POST['course'] ?? '';

// Construct the back link
$back_to_view_url = "view_submissions.php?student_id=$student_user_id&academic_year=" . urlencode($academic_year) . "&semester=" . urlencode($semester) . "&course=" . urlencode($course);


// Fetch the student name
$student_sql = "SELECT full_name FROM users WHERE user_id = ?";
$student_stmt = $conn->prepare($student_sql);
$student_stmt->bind_param("i", $student_user_id);
$student_stmt->execute();
$student_result = $student_stmt->get_result();

if ($student_result->num_rows > 0) {
    $student_name = $student_result->fetch_assoc()['full_name'];
} else {
    $student_name = "Unknown Student";
}
$student_stmt->close();

$receiver_id = $student_user_id; // The student is the receiver

$placeholders = implode(',', array_fill(0, count($submission_ids), '?'));

// Fetch chat lock status
$lock_sql = "SELECT * FROM stage_chat_locks WHERE submission_id IN ($placeholders)";
$lock_stmt = $conn->prepare($lock_sql);
$lock_stmt->bind_param(str_repeat('i', count($submission_ids)), ...$submission_ids);
$lock_stmt->execute();
$lock_result = $lock_stmt->get_result();
$is_locked = $lock_result->num_rows > 0;
$lock_stmt->close();

// Fetch all messages along with stage numbers
$sql = "SELECT sc.message, sc.file_path, sc.sent_at, sc.is_guide_message, ps.stage_number 
        FROM project_stage_chats sc
        INNER JOIN project_stage_submissions s ON sc.submission_id = s.id
        INNER JOIN project_submission_stages ps ON s.stage_id = ps.id
        WHERE sc.submission_id IN ($placeholders)
        GROUP BY sc.message, sc.file_path, sc.sent_at, sc.is_guide_message, ps.stage_number
        ORDER BY sc.sent_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat('i', count($submission_ids)), ...$submission_ids);
$stmt->execute();
$result = $stmt->get_result();

?>

<!-- Outer Wrapper for Centering -->
<div class="flex justify-center items-top min-h-screen bg-gray-100 p-4">
<main class="flex flex-col w-full max-w-3xl h-[100vh] bg-white rounded-lg shadow-md overflow-hidden border border-gray-300">

    <!-- Header -->
    <div class="bg-purple-700 text-black p-4 text-lg font-semibold flex flex-wrap items-center justify-between">
        <div>Submission Chat Window</div>
        <div class="flex flex-wrap gap-2 items-center text-sm">
            <!-- Lock Chat Button -->
            <?php if (!$is_locked): ?>
    <button id="lockChatBtn" class="bg-red-600 text-white px-2 py-1 rounded hover:bg-red-600 mr-2">
        🔒 Lock Chat
    </button>
<?php else: ?>
    <button id="unlockChatBtn" class="bg-blue-600 text-white px-2 py-1 rounded hover:bg-green-700 mr-2">
        🔓 Unlock Chat
    </button>
<?php endif; ?>
<span>Student Name: <?= htmlspecialchars($student_name) ?></span>

<a href="<?= $back_to_view_url ?>" 
   class="inline-block px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 ml-2">
    ← Back to Student Submissions
</a>

        </div>
    </div>

    <!-- Chat Messages -->
    <div id="chatWindow" class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50">
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="flex <?= $row['is_guide_message'] ? 'justify-end' : 'justify-start' ?>">
                <div class="max-w-[70%] p-3 rounded-lg <?= $row['is_guide_message'] ? 'bg-green-200' : 'bg-white' ?> shadow">
                    <p class="font-medium text-sm">
                        <?= $row['is_guide_message'] ? 'You' : 'Student' ?> - Stage <?= $row['stage_number'] ?>
                    </p>
                    <p><?= nl2br(htmlspecialchars($row['message'])) ?></p>
                    <?php if (!empty($row['file_path'])): ?>
                        <p class="mt-1 text-sm">
    <?php
        $full_file_path = '../Student/' . htmlspecialchars($row['file_path']);
    ?>
    📎 <a href="<?= $full_file_path ?>" target="_blank" class="text-blue-600 underline">View Attachment</a>
</p>

                    <?php endif; ?>
                    <p class="text-xs text-gray-500 mt-1"><?= $row['sent_at'] ?></p>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- Chat Form -->
    <?php if (!$is_locked): ?>
        <form id="chatForm" class="p-4 bg-white border-t flex gap-2 flex-wrap" enctype="multipart/form-data">
            <!-- Dropdown for Stage Selection -->
            <select name="selected_submission_id" required class="border p-2 rounded">
                <option value="">Select Stage</option>
                <?php
                $submission_info_sql = "SELECT s.id as submission_id, ps.stage_number 
                                        FROM project_stage_submissions s
                                        INNER JOIN project_submission_stages ps ON s.stage_id = ps.id
                                        WHERE s.id IN (" . implode(',', array_map('intval', $submission_ids)) . ")";
                $info_result = $conn->query($submission_info_sql);
                while ($info = $info_result->fetch_assoc()): ?>
                    <option value="<?= $info['submission_id'] ?>">Stage <?= $info['stage_number'] ?></option>
                <?php endwhile; ?>
            </select>

            <textarea name="message" rows="2" required placeholder="Type a message..." class="flex-1 p-2 border rounded resize-none"></textarea>
            <input type="file" name="attachment" class="border rounded p-1">

            <!-- Hidden Fields -->
            <input type="hidden" name="receiver_id" value="<?= htmlspecialchars($receiver_id) ?>">
            <input type="hidden" name="is_guide" value="1">

            <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">Send</button>
        </form>

        <script>
        document.getElementById('chatForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const form = e.target;
            const formData = new FormData(form);

            fetch('send_chat_message.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const msgHTML = `
                        <div class="flex justify-end">
                            <div class="max-w-[70%] p-3 rounded-lg shadow" style="background-color: #00a04e;">
                                <p class="font-medium text-sm">You - Stage ${data.stage_number}</p>
                                <p>${data.message.replace(/\n/g, '<br>')}</p>
                                ${data.attachment ? `<p class="mt-1 text-sm">📎 <a href="${data.attachment}" target="_blank" class="text-blue-600 underline">View Attachment</a></p>` : ''}
                                <p class="text-xs text-gray-100 mt-1">${data.sent_at}</p>
                            </div>
                        </div>`;
                    document.getElementById('chatWindow').insertAdjacentHTML('beforeend', msgHTML);
                    form.reset();
                    document.getElementById('chatWindow').scrollTop = document.getElementById('chatWindow').scrollHeight;
                } else {
                    alert("Failed to send message.");
                }
            })
            .catch(err => {
                console.error(err);
                alert("Error sending message.");
            });
        });
        </script>
    <?php else: ?>
        <div class="p-4 bg-gray-200 text-center text-gray-500">
            You cannot send messages. Chat is locked.
        </div>
    <?php endif; ?>
</main>
</div>
<script>
document.getElementById('lockChatBtn')?.addEventListener('click', function () {
    if (!confirm("Are you sure you want to lock this chat? This will prevent further messages.")) return;

    fetch('lock_chat.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ submission_ids: <?= json_encode($submission_ids) ?> })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("Chat locked successfully.");
            location.reload();
        } else {
            alert("Failed to lock chat.");
        }
    })
    .catch(err => {
        console.error(err);
        alert("Error locking chat.");
    });
});
</script>
<script>
    document.getElementById('unlockChatBtn')?.addEventListener('click', function () {
    if (!confirm("Are you sure you want to unlock this chat? Students will be able to send messages again.")) return;

    fetch('unlock_chat.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ submission_ids: <?= json_encode($submission_ids) ?> })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("Chat unlocked successfully.");
            location.reload();
        } else {
            alert("Failed to unlock chat.");
        }
    })
    .catch(err => {
        console.error(err);
        alert("Error unlocking chat.");
    });
});

</script>