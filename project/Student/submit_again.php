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

    // Get file labels
    $file_labels = [];
    $files_stmt = $conn->prepare("SELECT file_number, label FROM competition_file_labels WHERE competition_id = ?");
    $files_stmt->bind_param("i", $competition_id);
    $files_stmt->execute();
    $files_result = $files_stmt->get_result();
    while ($row = $files_result->fetch_assoc()) {
        $file_labels[] = $row;
    }

    // Fetch submission details to check if files were previously submitted
    $submissionExists = false;
    $submittedFiles = '';
    $check_submission_sql = "SELECT title, submitted_files FROM student_submissions WHERE competition_id = ? AND student_user_id = ?";
    $check_stmt = $conn->prepare($check_submission_sql);
    $check_stmt->bind_param("ii", $competition_id, $student_user_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        $check_stmt->bind_result($submittedTitle, $submittedFiles);
        $check_stmt->fetch();
        $submissionExists = true;
    }
    

} else {
    echo "Invalid competition ID.";
    exit;
}

function formatDate($date) {
    return $date ? date("d M Y", strtotime($date)) : 'N/A';
}
$message = $_SESSION['message'] ?? "";
$message_type = $_SESSION['message_type'] ?? "";
unset($_SESSION['message'], $_SESSION['message_type']);
?>

<main class="h-full overflow-y-auto mt-8">
    <div class="container px-6 mx-auto grid">
        <div class="container mx-auto p-6 mt-8">
            <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-200">
                <h1 class="text-xl font-semibold mb-4">Competition Details</h1>
                <?php if (!empty($message)): ?>
        <div id="custom-popup" class="bg-white border <?php echo ($message_type === 'success') ? 'border-green-500 text-green-600' : 'border-red-500 text-red-600'; ?> px-6 py-3 rounded-lg shadow-lg flex items-center space-x-3 mx-auto">
            
            <!-- Icon -->
            <?php if ($message_type === 'success'): ?>
                <svg class="w-6 h-6 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            <?php else: ?>
                <svg class="w-6 h-6 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M12 6a9 9 0 110 18A9 9 0 0112 6z" />
                </svg>
            <?php endif; ?>
            
            <!-- Message -->
            <span><?php echo htmlspecialchars($message); ?></span>

            <!-- Close Button -->
            <button onclick="closePopup()" class="ml-2 text-gray-700 font-bold">×</button>
        </div>

        <script>
            function closePopup() {
                document.getElementById("custom-popup").style.display = "none";
            }
            setTimeout(closePopup, 5000); // Hide popup after 5 seconds
        </script>
    <?php endif; ?>
                <!-- Displaying competition details as before -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Competition Name</label>
                    <input type="text" readonly value="<?= htmlspecialchars($competition['name']) ?>" class="mt-1 block w-full px-3 py-2 border bg-gray-100 rounded-md shadow-sm">
                </div>
                <!-- Other fields omitted for brevity -->

                <!-- Form starts here -->
                <form action="submit_upload_handler_v2.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="competition_id" value="<?= $competition_id ?>">

                    <!-- Title Field before file input -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                        <input type="text" name="title" class="block w-full px-3 py-2 border rounded-md" value="" required>
                    </div>

                    <!-- File Submission Form -->
                    <?php foreach ($file_labels as $file): ?>
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($file['label']) ?></label>

                            <!-- File Type Selector -->
                            <select name="file_type_<?= $file['file_number'] ?>" class="block w-full mb-2 px-3 py-2 border rounded-md" required onchange="toggleAcceptType(this, <?= $file['file_number'] ?>)">
                                <option value="">Select File Type</option>
                                <option value="pdf">PDF</option>
                                <option value="video">Video</option>
                            </select>

                            <!-- File Input, showing previously uploaded files if any -->
                            <?php if ($submissionExists && strpos($submittedFiles, $file['file_number']) !== false): ?>
                                <div class="mb-2">
                                    <strong>Previously Submitted File:</strong>
                                    <a href="uploads/<?= htmlspecialchars($submittedFiles) ?>" target="_blank"><?= htmlspecialchars($submittedFiles) ?></a>
                                </div>
                            <?php endif; ?>

                            <!-- File Input -->
                            <input type="file" name="file_<?= $file['file_number'] ?>" id="file_input_<?= $file['file_number'] ?>" class="block w-full px-3 py-2 border rounded-md" accept="" required onchange="validateFile(this, <?= $file['file_number'] ?>)">
                        </div>
                    <?php endforeach; ?>

                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-lg shadow-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-400 focus:ring-offset-2">
                        Submit Files Again
                    </button>
                </form>

                <div class="flex justify-end">
                    <a href="competitions.php" class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-lg shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">
                        Back
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>


<script>
// Live validation for file type
function toggleAcceptType(select, number) {
    const input = document.getElementById('file_input_' + number);
    const fileType = select.value;

    if (fileType === 'pdf') {
        input.accept = ".pdf";
        input.setAttribute("data-max-size", 20 * 1024 * 1024); // 20 MB
    } else if (fileType === 'video') {
        input.accept = ".mp4,.mov,.avi,.mkv";
        input.setAttribute("data-max-size", 50 * 1024 * 1024); // 50 MB
    } else {
        input.accept = "";
        input.removeAttribute("data-max-size");
    }

    // Clear the file input value when changing the type
    input.value = "";
}

// Live file validation for size and type
function validateFile(input, number) {
    const file = input.files[0];

    // If no file is selected, do nothing
    if (!file) return;

    const fileType = document.querySelector(`select[name="file_type_${number}"]`).value;
    const maxSize = parseInt(input.getAttribute("data-max-size"));
    const fileExtension = file.name.split('.').pop().toLowerCase();
    const allowedTypes = fileType === 'pdf' ? ['pdf'] : ['mp4', 'mov', 'avi', 'mkv'];

    // Check file type
    const isValidExtension = allowedTypes.includes(fileExtension);
    if (!isValidExtension) {
        alert("Invalid file type. Allowed types: " + allowedTypes.join(", "));
        input.value = "";  // Clear the file input
        return;
    }

    // Check file size
    if (file.size > maxSize) {
        alert("File size exceeds the maximum limit of " + (maxSize / 1024 / 1024).toFixed(1) + " MB");
        input.value = "";  // Clear the file input
        return;
    }
}

// Optional: Adding real-time validation when file input changes (this runs whenever the user selects a file)
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("input[type='file']").forEach(input => {
        input.addEventListener("change", function () {
            validateFile(this);
        });
    });
});
</script>
