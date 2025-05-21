<?php
session_start();

// Redirect if user is already verified
if (isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'] === true) {
    header("Location: dashboard.php");
    exit();
}

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Prevent browser cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$message = "";
$message_type = "";

// Show success message when user lands on OTP page
if (!isset($_POST['otp'])) {
    $message = "OTP sent to registered email, please wait!";
    $message_type = "success";
}

// When OTP is submitted
if (isset($_POST['otp'])) {
    $otp = $_POST['otp'];

    if ($otp == $_SESSION['otp']) {
        // OTP is correct, user is verified
        $_SESSION['otp_verified'] = true; // Mark OTP as verified
        unset($_SESSION['otp']); // Remove OTP after verification
        header("Location: dashboard.php"); // Redirect to dashboard after OTP verification
        exit();
    } else {
        // OTP is incorrect
        $message = "Invalid OTP!";
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/tailwind.output.css">
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>
    <script src="assets/js/init-alpine.js"></script>
</head>
<body>
    <div class="flex items-center min-h-screen p-6 bg-gray-50 dark:bg-gray-900">
        <div class="flex-1 h-full max-w-4xl mx-auto overflow-hidden bg-white rounded-lg shadow-xl dark:bg-gray-800">
            <div class="flex flex-col overflow-y-auto md:flex-row">
                <div class="h-32 md:h-auto md:w-1/2">
                    <img aria-hidden="true" class="object-cover w-full h-full dark:hidden" src="images/banners/verify_otp.png" alt="Img">
                    <img aria-hidden="true" class="hidden object-cover w-full h-full dark:block" src="images/banners/verify_otp.png" alt="Img">
                </div>
                <div class="flex items-center justify-center p-6 sm:p-12 md:w-1/2">
                    <div class="w-full">
                        <h1 class="mb-4 text-xl font-semibold text-gray-700 dark:text-gray-200">Verify OTP</h1>

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
    <?php 
        // Unset the message variables after displaying
        unset($message); 
        unset($message_type); 
    ?>
<?php endif; ?>


                        <form method="POST" action="verify_otp.php">
                            <label class="block text-sm">
                                <span class="text-gray-700 dark:text-gray-400">Enter OTP</span>
                                <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 
                                    focus:border-purple-400 focus:outline-none focus:shadow-outline-purple 
                                    dark:text-gray-300 dark:focus:shadow-outline-gray form-input"
                                    placeholder="Enter OTP" type="text" name="otp" id="otp" required />
                            </label>
                            <button type="submit" class="block w-full px-4 py-2 mt-4 text-sm font-medium leading-5 text-center 
                                text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg 
                                active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple">
                                Verify OTP
                            </button>
                        </form>

                        <hr class="my-8">
                        <p class="mt-1">
                            <a class="text-sm font-medium text-purple-600 dark:text-purple-400 hover:underline" href="index.php">
                                Back to Login
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    // Prevent browser back button after verifying OTP
    window.history.pushState(null, "", window.location.href);
    window.onpopstate = function () {
        window.history.pushState(null, "", window.location.href);
    };
</script>

</body>
</html>

