<?php
include 'header.php';
include 'connection.php';

if (!isset($_POST['submission_ids'])) {
    echo "No submission IDs passed.";
    exit;
}
$student_user_id = $_SESSION['user_id']; 

// Fetch the current guide for the student
$guide_sql = "SELECT guide_user_id 
              FROM guide_allocations 
              WHERE student_user_id = ? AND is_current = 1";

$guide_stmt = $conn->prepare($guide_sql);
$guide_stmt->bind_param("i", $student_user_id);
$guide_stmt->execute();
$guide_result = $guide_stmt->get_result();

// Fetch the current guide's user ID
if ($guide_result->num_rows > 0) {
    $guide = $guide_result->fetch_assoc();
    $_SESSION['receiver_id'] = $guide['guide_user_id']; // Store the receiver_id (guide's user ID) in the session
} else {
    // Handle case if no current guide is found
    $_SESSION['receiver_id'] = null;
}

$guide_stmt->close();
$receiver_id = $_SESSION['receiver_id'];

if ($receiver_id) {
    $guide_name_sql = "SELECT full_name FROM users WHERE user_id = ?";
    $guide_name_stmt = $conn->prepare($guide_name_sql);
    $guide_name_stmt->bind_param("i", $receiver_id);
    $guide_name_stmt->execute();
    $guide_name_result = $guide_name_stmt->get_result();
    
    if ($guide_name_result->num_rows > 0) {
        $guide_name = $guide_name_result->fetch_assoc()['full_name'];
    } else {
        $guide_name = "Unknown Guide"; // Fallback if no name is found
    }
    $guide_name_stmt->close();
} else {
    $guide_name = "No Guide Assigned"; // Fallback if receiver_id is not set
}

$submission_ids = $_POST['submission_ids'];
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
                <?php if ($is_locked): ?>
                    <span class="bg-red-600 px-2 py-1 rounded">Chat Locked by Guide</span>
                <?php endif; ?>
                <span>Guide Name: <?= htmlspecialchars($guide_name) ?></span>
                <a href="project_submission_schedule.php" 
           class="inline-block px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 ml-2">
            Back
        </a>
            </div>
        </div>

        <!-- Chat Messages -->
        <div id="chatWindow" class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="flex <?= $row['is_guide_message'] ? 'justify-start' : 'justify-end' ?>">
                    <div class="max-w-[70%] p-3 rounded-lg <?= $row['is_guide_message'] ? 'bg-white' : 'bg-green-200' ?> shadow">
                        <p class="font-medium text-sm">
                            <?= $row['is_guide_message'] ? 'Guide' : 'You' ?> - Stage <?= $row['stage_number'] ?>
                        </p>
                        <p><?= nl2br(htmlspecialchars($row['message'])) ?></p>
                        <?php if (!empty($row['file_path'])): ?>
                            <p class="mt-1 text-sm">
                                📎 <a href="<?= htmlspecialchars($row['file_path']) ?>" target="_blank" class="text-blue-600 underline">View Attachment</a>
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
                You cannot send messages. Chat is locked by the guide.
            </div>
        <?php endif; ?>
    </main>
</div>
