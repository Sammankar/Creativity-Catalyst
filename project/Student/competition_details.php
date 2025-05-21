<?php
include "connection.php";
include "header.php";

// Get the competition ID from the URL
$competition_id = $_GET['competition_id'] ?? null;

if ($competition_id) {
    // Fetch competition details
    $sql = "SELECT * FROM competitions WHERE competition_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $competition_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $competition = $result->fetch_assoc();
    } else {
        echo "Competition not found.";
        exit;
    }

    // Get creator's name from users table
    $created_by = $competition['created_by'];
    $creator_name = 'Unknown';
    $creator_stmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = ?");
    $creator_stmt->bind_param("i", $created_by);
    $creator_stmt->execute();
    $creator_result = $creator_stmt->get_result();
    if ($creator_result->num_rows > 0) {
        $creator_name = $creator_result->fetch_assoc()['full_name'];
    }

    // Get file labels
    $file_labels = [];
    $files_stmt = $conn->prepare("SELECT file_number, label FROM competition_file_labels WHERE competition_id = ?");
    $files_stmt->bind_param("i", $competition_id);
    $files_stmt->execute();
    $files_result = $files_stmt->get_result();
    while ($row = $files_result->fetch_assoc()) {
        $file_labels[] = $row;
    }

    // Get prize distribution
    $prizes = [];
    $prizes_stmt = $conn->prepare("SELECT rank, prize_description FROM competition_prizes WHERE competition_id = ?");
    $prizes_stmt->bind_param("i", $competition_id);
    $prizes_stmt->execute();
    $prizes_result = $prizes_stmt->get_result();
    while ($row = $prizes_result->fetch_assoc()) {
        $prizes[] = $row;
    }

} else {
    echo "Invalid competition ID.";
    exit;
}

function formatDate($date) {
    return $date ? date("d M Y", strtotime($date)) : 'N/A';
}
?>

<main class="h-full overflow-y-auto mt-8">
    <div class="container px-6 mx-auto grid">
        <div class="container mx-auto p-6 mt-8">
            <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-200">
                <h1 class="text-xl font-semibold mb-4">Competition Details</h1>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Competition Name</label>
                    <input type="text" readonly value="<?= htmlspecialchars($competition['name']) ?>" class="mt-1 block w-full px-3 py-2 border bg-gray-100 rounded-md shadow-sm">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <input type="text" readonly value="<?= $competition['competition_status'] == 0 ? 'Not Started' : ($competition['competition_status'] == 1 ? 'In Progress' : 'Completed') ?>" class="mt-1 block w-full px-3 py-2 border bg-gray-100 rounded-md shadow-sm">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea readonly class="mt-1 block w-full px-3 py-2 border bg-gray-100 rounded-md shadow-sm"><?= htmlspecialchars($competition['description']) ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Rules</label>
                    <textarea readonly class="mt-1 block w-full px-3 py-2 border bg-gray-100 rounded-md shadow-sm"><?= htmlspecialchars($competition['rules']) ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Recommended Submissions</label>
                    <input type="text" readonly value="<?= htmlspecialchars($competition['recommended_submissions']) ?>" class="mt-1 block w-full px-3 py-2 border bg-gray-100 rounded-md shadow-sm">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Max Submissions Per College</label>
                    <input type="text" readonly value="<?= $competition['max_submissions_per_college'] ?? 'N/A' ?>" class="mt-1 block w-full px-3 py-2 border bg-gray-100 rounded-md shadow-sm">
                </div>

                <?php
                $date_fields = [
                    'College Registration Start' => 'college_registration_start_date',
                    'College Registration End' => 'college_registration_end_date',
                    'Student Submission Start' => 'student_submission_start_date',
                    'Student Submission End' => 'student_submission_end_date',
                    'Evaluation Start' => 'evaluation_start_date',
                    'Evaluation End' => 'evaluation_end_date',
                    'Result Declaration Date' => 'result_declaration_date',
                ];
                foreach ($date_fields as $label => $key):
                ?>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700"><?= $label ?></label>
                        <input type="text" readonly value="<?= formatDate($competition[$key]) ?>" class="mt-1 block w-full px-3 py-2 border bg-gray-100 rounded-md shadow-sm">
                    </div>
                <?php endforeach; ?>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Total Prize Pool</label>
                    <input type="text" readonly value="<?= htmlspecialchars($competition['total_prize_pool']) ?>" class="mt-1 block w-full px-3 py-2 border bg-gray-100 rounded-md shadow-sm">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Top Ranks Awarded</label>
                    <input type="text" readonly value="<?= htmlspecialchars($competition['top_ranks_awarded']) ?>" class="mt-1 block w-full px-3 py-2 border bg-gray-100 rounded-md shadow-sm">
                </div>

                <!-- File Labels -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">File Labels</label>
                    <ul class="list-disc pl-5 text-gray-700 bg-gray-100 p-3 rounded">
                        <?php foreach ($file_labels as $file): ?>
                            <li><?= htmlspecialchars($file['file_number']) ?> - <?= htmlspecialchars($file['label']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Prize Distribution -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Prize Distribution</label>
                    <ul class="list-disc pl-5 text-gray-700 bg-gray-100 p-3 rounded">
                        <?php foreach ($prizes as $prize): ?>
                            <li>Rank <?= $prize['rank'] ?>: <?= htmlspecialchars($prize['prize_description']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Creator and Dates -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Created By</label>
                    <input type="text" readonly value="<?= htmlspecialchars($creator_name) ?>" class="mt-1 block w-full px-3 py-2 border bg-gray-100 rounded-md shadow-sm">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Created At</label>
                    <input type="text" readonly value="<?= formatDate($competition['created_at']) ?>" class="mt-1 block w-full px-3 py-2 border bg-gray-100 rounded-md shadow-sm">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700">Updated At</label>
                    <input type="text" readonly value="<?= formatDate($competition['updated_at']) ?>" class="mt-1 block w-full px-3 py-2 border bg-gray-100 rounded-md shadow-sm">
                </div>

                <div class="flex justify-end">
                    <a href="competitions.php" class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-lg shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">
                        Back
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>
