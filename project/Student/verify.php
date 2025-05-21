<?php
require_once 'connection.php'; // Ensure you have a config file for DB connection
require_once 'mail.php'; // Common mail file

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Database Connection
    $conn = new mysqli("localhost", "root", "", "project");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Verify Token
    $query = "SELECT * FROM users WHERE verification_token = '$token' LIMIT 1";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $updateQuery = "UPDATE users SET email_verified = 1 WHERE user_id = " . $row['user_id'];

        if ($conn->query($updateQuery) === TRUE) {
            echo "Your email has been verified. You have to wait for sub-admin approval.";
        } else {
            echo "Verification failed. Try again.";
        }
    } else {
        echo "Invalid verification link.";
    }

    $conn->close();
}
?>
