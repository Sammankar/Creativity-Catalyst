<?php
session_start();
require 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $address = trim($_POST['address']);
    $contact_number = trim($_POST['contact_number']);
    $director_name = trim($_POST['director']);
    $website = trim($_POST['website']);
    $admin_id = $_SESSION['user_id'];

    // Validate inputs
    if (empty($name) || empty($address) || empty($contact_number) || empty($director_name) || empty($website)) {
        $_SESSION['message'] = "All fields are required.";
        $_SESSION['message_type'] = "error";
        header("Location: college_registration.php");
        exit();
    }

    // Validate logo file
    $target_dir = "images/college_logo/";
    $allowed_types = ['image/jpeg', 'image/png'];
    $max_size = 10 * 1024 * 1024; // 10MB

    if (!isset($_FILES['college_logo']) || $_FILES['college_logo']['error'] != UPLOAD_ERR_OK) {
        $_SESSION['message'] = "Error uploading the college logo.";
        $_SESSION['message_type'] = "error";
        header("Location: college_registration.php");
        exit();
    }

    $logo_tmp_name = $_FILES['college_logo']['tmp_name'];
    $logo_name = basename($_FILES['college_logo']['name']);
    $logo_size = $_FILES['college_logo']['size'];
    $logo_type = mime_content_type($logo_tmp_name);

    // Check file type and size
    if (!in_array($logo_type, $allowed_types)) {
        $_SESSION['message'] = "Invalid file type. Only JPG and PNG are allowed.";
        $_SESSION['message_type'] = "error";
        header("Location: college_registration.php");
        exit();
    }
    if ($logo_size > $max_size) {
        $_SESSION['message'] = "File is too large. Maximum size is 10MB.";
        $_SESSION['message_type'] = "error";
        header("Location: college_registration.php");
        exit();
    }

    // Generate unique filename
    $extension = pathinfo($logo_name, PATHINFO_EXTENSION);
    $new_logo_name = uniqid("college_", true) . "." . $extension;
    $logo_path = $target_dir . $new_logo_name;

    // Move uploaded file
    if (!move_uploaded_file($logo_tmp_name, $logo_path)) {
        $_SESSION['message'] = "Failed to save the college logo.";
        $_SESSION['message_type'] = "error";
        header("Location: college_registration.php");
        exit();
    }

    // Check if the admin already registered a college
    $stmt_check = $conn->prepare("SELECT college_id FROM colleges WHERE admin_id = ?");
    $stmt_check->bind_param("i", $admin_id);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        $_SESSION['message'] = "You have already registered a college.";
        $_SESSION['message_type'] = "error";
        header("Location: college_registration.php");
        exit();
    }
    $stmt_check->close();

    // Insert into colleges table
    $stmt = $conn->prepare("INSERT INTO colleges (name, address, contact_number, director_name, website, college_logo, created_at, admin_id) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
    $stmt->bind_param("ssssssi", $name, $address, $contact_number, $director_name, $website, $logo_path, $admin_id);

    if ($stmt->execute()) {
        $college_id = $stmt->insert_id;

        // Update the user's `college_id` field
        $stmt_update = $conn->prepare("UPDATE users SET college_id = ? WHERE user_id = ?");
        $stmt_update->bind_param("ii", $college_id, $admin_id);
        $stmt_update->execute();
        $stmt_update->close();

        $_SESSION['message'] = "College registered successfully.";
        $_SESSION['message_type'] = "success";
        header("Location: dashboard.php");
        exit();
    } else {
        $_SESSION['message'] = "Error registering the college. Please try again.";
        $_SESSION['message_type'] = "error";
        header("Location: college_registration.php");
        exit();
    }
}
?>
