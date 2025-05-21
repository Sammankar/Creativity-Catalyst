<?php
include "header.php";
include "connection.php";

if (!isset($_GET['id'])) {
    echo "<script>alert('Invalid request'); window.location.href='competition_list.php';</script>";
    exit;
}

$competition_id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM competitions WHERE competition_id = ?");
$stmt->bind_param("i", $competition_id);
$stmt->execute();
$result = $stmt->get_result();
$competition = $result->fetch_assoc();
$stmt->close();

if (!$competition) {
    echo "<script>alert('Competition not found'); window.location.href='competition_list.php';</script>";
    exit;
}

$editable = $competition['competition_status'] == 0;
?>

<!-- HTML START -->
<div class="container mx-auto mt-10 px-6">
    <h1 class="text-2xl font-bold mb-4">Competition: <?php echo htmlspecialchars($competition['name']); ?></h1>

    <!-- Readonly View -->
    <div class="bg-white rounded shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4">Current Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div><strong>Name:</strong> <?php echo htmlspecialchars($competition['name']); ?></div>
            <div class="md:col-span-2"><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($competition['description'])); ?></div>
            <div class="md:col-span-2"><strong>Rules:</strong> <?php echo nl2br(htmlspecialchars($competition['rules'])); ?></div>
            <div class="md:col-span-2"><strong>Recommended Submissions:</strong> <?php echo nl2br(htmlspecialchars($competition['recommended_submissions'])); ?></div>
            <div><strong>Top Ranks:</strong> <?php echo $competition['top_ranks_awarded']; ?></div>
            <div><strong>College Reg. Start:</strong> <?php echo $competition['college_registration_start_date']; ?></div>
            <div><strong>College Reg. End:</strong> <?php echo $competition['college_registration_end_date']; ?></div>
            <div><strong>Student Sub. Start:</strong> <?php echo $competition['student_submission_start_date']; ?></div>
            <div><strong>Student Sub. End:</strong> <?php echo $competition['student_submission_end_date']; ?></div>
            <div><strong>Evaluation Start:</strong> <?php echo $competition['evaluation_start_date']; ?></div>
            <div><strong>Evaluation End:</strong> <?php echo $competition['evaluation_end_date']; ?></div>
            <div><strong>Result Date:</strong> <?php echo $competition['result_declaration_date']; ?></div>
        </div>
    </div>

    <!-- Editable Form -->
    <form method="POST" action="update_competition.php" class="bg-white mt-8 p-6 rounded shadow-md" onsubmit="return validateForm();">
        <input type="hidden" name="competition_id" value="<?php echo $competition_id; ?>" />

        <h2 class="text-xl font-semibold mb-4">Edit Competition</h2>

        <!-- Name -->
        <div class="mb-4">
            <label class="block text-gray-700">Competition Name</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($competition['name']); ?>"
                   required class="w-full p-2 border rounded" <?php echo !$editable ? 'readonly' : ''; ?>>
        </div>

        <!-- Description -->
        <div class="mb-4">
            <label class="block text-gray-700">Description</label>
            <textarea name="description" class="w-full p-2 border rounded" required <?php echo !$editable ? 'readonly' : ''; ?>><?php echo htmlspecialchars($competition['description']); ?></textarea>
        </div>

        <!-- Rules -->
        <div class="mb-4">
            <label class="block text-gray-700">Rules</label>
            <textarea name="rules" class="w-full p-2 border rounded" required <?php echo !$editable ? 'readonly' : ''; ?>><?php echo htmlspecialchars($competition['rules']); ?></textarea>
        </div>

        <!-- Recommended Submissions -->
        <div class="mb-4">
            <label class="block text-gray-700">Recommended Submissions</label>
            <textarea name="recommended_submissions" class="w-full p-2 border rounded" <?php echo !$editable ? 'readonly' : ''; ?>><?php echo htmlspecialchars($competition['recommended_submissions']); ?></textarea>
        </div>

        <?php if ($editable): ?>
        <!-- Editable Dates (only if status = 0) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-gray-700">College Reg. Start</label>
                <input type="date" name="college_registration_start_date" id="college_start" value="<?php echo $competition['college_registration_start_date']; ?>" class="w-full p-2 border rounded" required>
            </div>
            <div>
                <label class="block text-gray-700">College Reg. End</label>
                <input type="date" name="college_registration_end_date" id="college_end" value="<?php echo $competition['college_registration_end_date']; ?>" class="w-full p-2 border rounded" required>
            </div>
            <div>
                <label class="block text-gray-700">Student Submission Start</label>
                <input type="date" name="student_submission_start_date" id="student_start" value="<?php echo $competition['student_submission_start_date']; ?>" class="w-full p-2 border rounded" required>
            </div>
            <div>
                <label class="block text-gray-700">Student Submission End</label>
                <input type="date" name="student_submission_end_date" id="student_end" value="<?php echo $competition['student_submission_end_date']; ?>" class="w-full p-2 border rounded" required>
            </div>
            <div>
                <label class="block text-gray-700">Evaluation Start</label>
                <input type="date" name="evaluation_start_date" id="eval_start" value="<?php echo $competition['evaluation_start_date']; ?>" class="w-full p-2 border rounded" required>
            </div>
            <div>
                <label class="block text-gray-700">Evaluation End</label>
                <input type="date" name="evaluation_end_date" id="eval_end" value="<?php echo $competition['evaluation_end_date']; ?>" class="w-full p-2 border rounded" required>
            </div>
            <div class="md:col-span-2">
                <label class="block text-gray-700">Result Declaration Date</label>
                <input type="date" name="result_declaration_date" id="result_date" value="<?php echo $competition['result_declaration_date']; ?>" class="w-full p-2 border rounded" required>
            </div>
        </div>
        <?php endif; ?>

        <div class="mt-6 text-right">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Update</button>
            <a href="competition_list.php" class="ml-4 text-blue-600 hover:underline">Cancel</a>
        </div>
    </form>
</div>

<!-- JS VALIDATION -->
<script>
function validateForm() {
    const collegeStart = new Date(document.getElementById('college_start').value);
    const collegeEnd = new Date(document.getElementById('college_end').value);
    const studentStart = new Date(document.getElementById('student_start').value);
    const studentEnd = new Date(document.getElementById('student_end').value);
    const evalStart = new Date(document.getElementById('eval_start').value);
    const evalEnd = new Date(document.getElementById('eval_end').value);
    const resultDate = new Date(document.getElementById('result_date').value);

    // College Dates
    if (collegeEnd < collegeStart) {
        alert("College registration end date must be after start date.");
        return false;
    }

    // Student Dates
    if (studentStart < collegeStart || studentEnd < studentStart) {
        alert("Student submission dates must be after college registration start date and in correct order.");
        return false;
    }

    // Evaluation Dates
    if (evalStart < studentEnd || evalEnd < evalStart) {
        alert("Evaluation must start after student submission ends and end after it starts.");
        return false;
    }

    // Result
    if (resultDate < evalEnd) {
        alert("Result declaration must be after evaluation ends.");
        return false;
    }

    return true;
}
</script>
