<?php
session_start();
include 'connection.php';
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'connection.php'; // make sure this file connects to your DB

    $name = $_POST['name'];
    $description = $_POST['description'];
    $rules = $_POST['rules'];
    $recommended_submissions = $_POST['recommended_submissions'];
    $number_of_files = $_POST['number_of_files'];
    $max_submissions_per_college = $_POST['max_submissions_per_college'];
    $college_registration_start = $_POST['college_registration_start_date'];
    $college_registration_end = $_POST['college_registration_end_date'];
    $student_submission_start = $_POST['student_submission_start_date'];
    $student_submission_end = $_POST['student_submission_end_date'];
    $evaluation_start = $_POST['evaluation_start_date'];
    $evaluation_end = $_POST['evaluation_end_date'];
    $result_declaration_date = $_POST['result_declaration_date'];
    $total_prize_pool = $_POST['total_prize_pool'];
    $top_ranks_awarded = $_POST['top_ranks_awarded'];

    // Insert into competitions
    $stmt = $conn->prepare("INSERT INTO competitions (
        name, description, rules, recommended_submissions,
        number_of_files, max_submissions_per_college,
        college_registration_start_date, college_registration_end_date,
        student_submission_start_date, student_submission_end_date,
        evaluation_start_date, evaluation_end_date,
        result_declaration_date, total_prize_pool, top_ranks_awarded
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("ssssisssssssssi",
        $name, $description, $rules, $recommended_submissions,
        $number_of_files, $max_submissions_per_college,
        $college_registration_start, $college_registration_end,
        $student_submission_start, $student_submission_end,
        $evaluation_start, $evaluation_end,
        $result_declaration_date, $total_prize_pool, $top_ranks_awarded
    );

    if ($stmt->execute()) {
        $competition_id = $stmt->insert_id;

        // Insert file labels
        if (isset($_POST['file_labels']) && is_array($_POST['file_labels'])) {
            $file_stmt = $conn->prepare("INSERT INTO competition_file_labels (competition_id, file_number, label) VALUES (?, ?, ?)");
            foreach ($_POST['file_labels'] as $index => $label) {
                $file_number = (int)$index;
                $file_stmt->bind_param("iis", $competition_id, $file_number, $label);
                $file_stmt->execute();
            }
        }

        // Insert prize info
        if (isset($_POST['prizes']) && is_array($_POST['prizes'])) {
            $prize_stmt = $conn->prepare("INSERT INTO competition_prizes (competition_id, rank, prize_description) VALUES (?, ?, ?)");
            foreach ($_POST['prizes'] as $rank => $desc) {
                $prize_stmt->bind_param("iis", $competition_id, $rank, $desc);
                $prize_stmt->execute();
            }
        }

        echo "<script>alert('Competition created successfully!'); window.location.href='create_competition.php';</script>";
        exit;
    } else {
        echo "<script>alert('Error occurred while creating competition.');</script>";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <title>Create Competition</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    function updateFileLabels() {
        const count = parseInt(document.getElementById("number_of_files").value);
        const container = document.getElementById("file_labels_container");
        container.innerHTML = "";
        for (let i = 1; i <= count; i++) {
            container.innerHTML += `
                <div class="mb-2">
                    <label class="block font-semibold mb-1">Label for File ${i}</label>
                    <input type="text" name="file_labels[${i}]" required
                        class="w-full border px-3 py-2 rounded" placeholder="e.g., Upload Demo Video" />
                </div>`;
        }
    }

    function updatePrizes() {
        const count = parseInt(document.getElementById("top_ranks_awarded").value);
        const container = document.getElementById("prize_fields");
        container.innerHTML = "";
        for (let i = 1; i <= count; i++) {
            container.innerHTML += `
                <div class="mb-2">
                    <label class="block font-semibold mb-1">Prize for Rank ${i}</label>
                    <input type="text" name="prizes[${i}]" required
                        class="w-full border px-3 py-2 rounded" placeholder="e.g., ₹3000 or Trophy + Internship" />
                </div>`;
        }
    }

    function validateDateOrder(startDateId, endDateId) {
        const startDate = document.getElementById(startDateId).value;
        const endDate = document.getElementById(endDateId).value;
        if (startDate && endDate) {
            if (new Date(startDate) > new Date(endDate)) {
                alert("End date must be after the start date.");
                document.getElementById(endDateId).value = "";
            }
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        const datePairs = [
            ['college_registration_start_date', 'college_registration_end_date'],
            ['student_submission_start_date', 'student_submission_end_date'],
            ['evaluation_start_date', 'evaluation_end_date']
        ];

        datePairs.forEach(pair => {
            document.getElementById(pair[0]).addEventListener("change", function() {
                validateDateOrder(pair[0], pair[1]);
            });
            document.getElementById(pair[1]).addEventListener("change", function() {
                validateDateOrder(pair[0], pair[1]);
            });
        });
    });
    </script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-4xl mx-auto bg-white p-8 rounded shadow">
        <h1 class="text-2xl font-bold mb-6">Create New Competition</h1>
        <form method="POST" class="space-y-4">
            <div class="mb-4">
                <label class="block font-semibold mb-1" for="name">Competition Name</label>
                <input name="name" id="name" type="text" placeholder="Competition Name" required class="w-full border p-2 rounded" />
            </div>

            <div class="mb-4">
                <label class="block font-semibold mb-1" for="description">Description</label>
                <textarea name="description" id="description" required class="w-full border p-2 rounded" placeholder="Description"></textarea>
            </div>

            <div class="mb-4">
                <label class="block font-semibold mb-1" for="rules">Rules</label>
                <textarea name="rules" id="rules" required class="w-full border p-2 rounded" placeholder="Rules"></textarea>
            </div>

            <div class="mb-4">
                <label class="block font-semibold mb-1" for="recommended_submissions">Recommended Submission Formats</label>
                <textarea name="recommended_submissions" id="recommended_submissions" class="w-full border p-2 rounded" placeholder="Recommended Submission Formats"></textarea>
            </div>

            <div class="mb-4">
                <label class="block font-semibold mb-1" for="number_of_files">Number of Files Required</label>
                <input type="number" name="number_of_files" id="number_of_files" min="1" required
                    class="w-full border p-2 rounded" onchange="updateFileLabels()" />
            </div>

            <div id="file_labels_container" class="mt-4"></div>

            <div class="mb-4">
                <label class="block font-semibold mb-1" for="max_submissions_per_college">Max Submissions per College</label>
                <input type="number" name="max_submissions_per_college" id="max_submissions_per_college" min="1" required
                    class="w-full border p-2 rounded" placeholder="Max Submissions per College" />
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block font-semibold mb-1" for="college_registration_start_date">College Registration Start</label>
                    <input type="date" name="college_registration_start_date" id="college_registration_start_date" required class="w-full border p-2 rounded" />
                </div>
                <div>
                    <label class="block font-semibold mb-1" for="college_registration_end_date">College Registration End</label>
                    <input type="date" name="college_registration_end_date" id="college_registration_end_date" required class="w-full border p-2 rounded" />
                </div>
                <div>
                    <label class="block font-semibold mb-1" for="student_submission_start_date">Student Submission Start</label>
                    <input type="date" name="student_submission_start_date" id="student_submission_start_date" required class="w-full border p-2 rounded" />
                </div>
                <div>
                    <label class="block font-semibold mb-1" for="student_submission_end_date">Student Submission End</label>
                    <input type="date" name="student_submission_end_date" id="student_submission_end_date" required class="w-full border p-2 rounded" />
                </div>
                <div>
                    <label class="block font-semibold mb-1" for="evaluation_start_date">Evaluation Start</label>
                    <input type="date" name="evaluation_start_date" id="evaluation_start_date" required class="w-full border p-2 rounded" />
                </div>
                <div>
                    <label class="block font-semibold mb-1" for="evaluation_end_date">Evaluation End</label>
                    <input type="date" name="evaluation_end_date" id="evaluation_end_date" required class="w-full border p-2 rounded" />
                </div>
                <div>
                    <label class="block font-semibold mb-1" for="result_declaration_date">Result Declaration Date</label>
                    <input type="date" name="result_declaration_date" id="result_declaration_date" required class="w-full border p-2 rounded" />
                </div>
            </div>

            <div class="mb-4">
                <label class="block font-semibold mb-1" for="total_prize_pool">Total Prize Pool</label>
                <input name="total_prize_pool" id="total_prize_pool" type="text" placeholder="e.g., ₹5000 or Certificates" required class="w-full border p-2 rounded" />
            </div>

            <div class="mb-4">
                <label class="block font-semibold mb-1" for="top_ranks_awarded">Number of Top Ranks to Award</label>
                <input type="number" name="top_ranks_awarded" id="top_ranks_awarded" min="1" required
                    class="w-full border p-2 rounded" onchange="updatePrizes()" />
            </div>

            <div id="prize_fields" class="mt-4"></div>

            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">Create Competition</button>
        </form>
    </div>
</body>
</html>
