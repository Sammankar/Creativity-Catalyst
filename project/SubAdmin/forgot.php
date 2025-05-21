<?php
session_start();
require 'connection.php';
require 'mail_otp.php';  // Function to send OTP via email

// Prevent browser caching (Stops back navigation)
// Prevent back button cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to the same page with a timestamp to prevent back navigation
if (!isset($_SESSION['visited_forgot'])) {
    $_SESSION['visited_forgot'] = true;
    header("Location: forgot.php?" . time()); 
    exit();
}

$message = isset($_SESSION['message']) ? $_SESSION['message'] : "";
$message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : "";
unset($_SESSION['message'], $_SESSION['message_type']); // Clear after displaying


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = "❌ Invalid email format!";
        $_SESSION['message_type'] = "error";
        header("Location: forgot.php");
        exit();
    }

    // Check if email exists in the database
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Generate a cryptographically secure OTP
        $otp = random_int(100000, 999999);  
        
        // Store OTP & expiry in session
        $_SESSION['otp'] = $otp;
        $_SESSION['reset_email'] = $email;
        $_SESSION['otp_time'] = time(); // Store OTP generation time

        // Send OTP to email
        if (sendOtpEmail($email, $otp)) {
            $_SESSION['message'] = "✅ OTP sent to your email!";
            $_SESSION['message_type'] = "success";

            // Prevent back navigation
            echo "<script>
                sessionStorage.setItem('otp_sent', 'true');
                window.location.href = 'verify_forgot_otp.php';
            </script>";
            exit();
        } else {
            $_SESSION['message'] = "❌ Failed to send OTP!";
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "❌ Email not found!";
        $_SESSION['message_type'] = "error";
    }
    header("Location: forgot.php");
    exit();
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/tailwind.output.css">
</head>
<body>
    <div class="flex items-center min-h-screen p-6 bg-gray-50 dark:bg-gray-900">
        <div class="flex-1 h-full max-w-4xl mx-auto overflow-hidden bg-white rounded-lg shadow-xl dark:bg-gray-800">
            <div class="flex flex-col overflow-y-auto md:flex-row">
                <div class="h-32 md:h-auto md:w-1/2">
                    <img aria-hidden="true" class="object-cover w-full h-full dark:hidden" src="images/banners/forgot.png" alt="Img">
                    <img aria-hidden="true" class="hidden object-cover w-full h-full dark:block" src="images/banners/forgot.png" alt="Img">
                </div>
                <div class="flex items-center justify-center p-6 sm:p-12 md:w-1/2">
                    <div class="w-full">
                        <h1 class="mb-4 text-xl font-semibold text-gray-700 dark:text-gray-200">Forgot Password</h1>

                        <!-- Display Messages -->
                        <?php if (!empty($message)): ?>
                            <div class="p-4 mb-4 text-sm rounded-lg flex items-center 
                                <?php echo ($message_type === 'success') ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                <?php if ($message_type === 'error'): ?>
                                    <svg class="w-5 h-5 mr-2 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 115.636 5.636a9 9 0 0112.728 12.728zM12 8v4m0 4h.01"></path>
                                    </svg>
                                <?php endif; ?>
                                <span><?php echo htmlspecialchars($message); ?></span>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="forgot.php">
                            <label class="block text-sm">
                                <span class="text-gray-700 dark:text-gray-400">Enter Your Registered Email</span>
                                <input
                                    class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input"
                                    type="email" name="email" required placeholder="example@example.com">
                            </label>

                            <button type="submit" class="block w-full px-4 py-2 mt-4 text-sm font-medium leading-5 text-center text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple">
                                Send OTP
                            </button>
                        </form>

                        <p class="mt-4">
                            <a class="text-sm font-medium text-purple-600 dark:text-purple-400 hover:underline" href="index.php">
                                Back to Login
                            </a>
                        </p>

                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
