<?php
session_start();
require 'connection.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['reset_email'])) {
        $_SESSION['message'] = "Session expired! Please request a new password reset.";
        $_SESSION['message_type'] = "error";
        header("Location: reset_password.php");
        exit();
    } else {
        $new_password = trim($_POST['new_password']);
        $confirm_password = trim($_POST['confirm_password']);
        $email = $_SESSION['reset_email'];

        if ($new_password !== $confirm_password) {
            $_SESSION['message'] = "Passwords do not match!";
            $_SESSION['message_type'] = "error";
            header("Location: reset_password.php");
            exit();
        } elseif (strlen($new_password) < 6) {
            $_SESSION['message'] = "Password must be at least 6 characters!";
            $_SESSION['message_type'] = "error";
            header("Location: reset_password.php");
            exit();
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashed_password, $email);

            if ($stmt->execute()) {
                $_SESSION['message'] = "Password updated successfully!";
                $_SESSION['message_type'] = "success";
                unset($_SESSION['reset_email']); // Clear session for security
                session_write_close(); // Ensure session is saved before redirecting

                header("Location: index.php?reset=success", true, 303);
                exit();
            } else {
                $_SESSION['message'] = "Failed to update password! " . $stmt->error;
                $_SESSION['message_type'] = "error";
                header("Location: reset_password.php");
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/tailwind.output.css">
</head>
<body>
    <div class="flex items-center min-h-screen p-6 bg-gray-50 dark:bg-gray-900">
        <div class="flex-1 h-full max-w-4xl mx-auto overflow-hidden bg-white rounded-lg shadow-xl dark:bg-gray-800">
            <div class="flex flex-col overflow-y-auto md:flex-row">
                <div class="h-32 md:h-auto md:w-1/2">
                    <img aria-hidden="true" class="object-cover w-full h-full dark:hidden" src="" alt="Img">
                    <img aria-hidden="true" class="hidden object-cover w-full h-full dark:block" src="" alt="Img">
                </div>
                <div class="flex items-center justify-center p-6 sm:p-12 md:w-1/2">
                    <div class="w-full">
                        <h1 class="mb-4 text-xl font-semibold text-gray-700 dark:text-gray-200">Reset Password</h1>

                        <!-- Display Messages -->
                        <?php if (isset($_SESSION['message'])): ?>
                            <div class="p-4 mb-4 text-sm rounded-lg flex items-center 
                                <?php echo ($_SESSION['message_type'] === 'success') ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                <span><?php echo htmlspecialchars($_SESSION['message']); ?></span>
                            </div>
                            <?php unset($_SESSION['message']); // Clear message after displaying ?>
                        <?php endif; ?>

                        <form method="POST" action="reset_password.php" onsubmit="return validatePassword()">
                            <label class="block text-sm">
                                <span class="text-gray-700 dark:text-gray-400">Enter New Password</span>
                                <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 
                                    focus:border-purple-400 focus:outline-none focus:shadow-outline-purple 
                                    dark:text-gray-300 dark:focus:shadow-outline-gray form-input"
                                    placeholder="Enter New Password" type="password" name="new_password" id="new_password" required />
                            </label>

                            <label class="block text-sm mt-4">
                                <span class="text-gray-700 dark:text-gray-400">Confirm Password</span>
                                <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 
                                    focus:border-purple-400 focus:outline-none focus:shadow-outline-purple 
                                    dark:text-gray-300 dark:focus:shadow-outline-gray form-input"
                                    placeholder="Confirm Password" type="password" name="confirm_password" id="confirm_password" required />
                            </label>

                            <button type="submit" class="block w-full px-4 py-2 mt-4 text-sm font-medium leading-5 text-center 
                                text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg 
                                active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple">
                                Reset Password
                            </button>
                        </form>

                        <hr class="my-8">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function validatePassword() {
        let newPassword = document.getElementById("new_password").value;
        let confirmPassword = document.getElementById("confirm_password").value;

        if (newPassword.length < 6) {
            alert("Password must be at least 6 characters long.");
            return false;
        }

        if (newPassword !== confirmPassword) {
            alert("Passwords do not match!");
            return false;
        }
        return true;
    }
    </script>

</body>
</html>
