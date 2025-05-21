<?php
// Start output buffering at the beginning
ob_start();

include "header.php";  // Assuming you are including header.php for your session and other setup
include "connection.php"; // Database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details from the database
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "User not found.";
    exit();
}
$users_status = $user['users_status'];
$access_status = $user['access_status'];

if ($users_status == 0) {
    // Destroy the session and clear the "Remember Me" cookie
    session_destroy();
    setcookie('user_id', '', time() - 3600, '/'); // Expire the cookie
    header("Location: index.php"); // Redirect to login page
    exit();
}

if ($access_status == 0) {
  header("Location: Access_reports_logs.php"); // Redirect to Access_reports_logs.php
  exit();
}

$profileImage = !empty($user['profile_photo']) ? "images/profile_photo/" . $user['profile_photo'] : "https://via.placeholder.com/150";

// Initialize message variables
$message = "";
$messageType = "";
$phoneExistsMessage = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Fetch form values
    $fullName = $_POST['full_name'] ?? $user['full_name'];
    $phone = $_POST['phone'] ?? $user['phone_number'];
    $username = $_POST['username'] ?? $user['username'];

    // Check if phone number exists in the database
    if ($phone !== $user['phone_number']) {
        $checkPhoneSQL = "SELECT * FROM users WHERE phone_number = ?";
        $stmt = $conn->prepare($checkPhoneSQL);
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Phone number already exists
            $phoneExistsMessage = "❌ Phone number already exists.";
        } else {
            // Proceed with profile update
            $updateSQL = "UPDATE users SET full_name = ?, phone_number = ?, username = ? WHERE user_id = ?";
            $stmt = $conn->prepare($updateSQL);
            $stmt->bind_param("sssi", $fullName, $phone, $username, $user_id);

            if ($stmt->execute()) {
                $_SESSION['message'] = "✔️ Profile updated successfully!";
                $_SESSION['message_type'] = "success";
                // Redirect to the profile page with the success message
                header("Location: myprofile.php");
                exit();
            } else {
                $_SESSION['message'] = "❌ Error updating profile.";
                $_SESSION['message_type'] = "error";
                header("Location: myprofile.php");
                exit();
            }
        }
    } else {
        // If phone number is unchanged, just proceed with updating the other fields
        $updateSQL = "UPDATE users SET full_name = ?, username = ? WHERE user_id = ?";
        $stmt = $conn->prepare($updateSQL);
        $stmt->bind_param("ssi", $fullName, $username, $user_id);

        if ($stmt->execute()) {
            $_SESSION['message'] = "✔️ Profile updated successfully!";
            $_SESSION['message_type'] = "success";
            // Redirect to the profile page with the success message
            header("Location: myprofile.php");
            exit();
        } else {
            $_SESSION['message'] = "❌ Error updating profile.";
            $_SESSION['message_type'] = "error";
            header("Location: myprofile.php");
            exit();
        }
    }
}

// End output buffering and flush the output
ob_end_flush();
?>



<main class="h-full overflow-y-auto mt-8">
    <div class="container px-6 mx-auto grid">
        <div class="container mx-auto p-6 mt-8">
            <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-200">
                <?php if (!empty($message)): ?>
                    <div class="p-4 mb-4 text-sm rounded-lg <?php echo ($messageType === 'success') ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($phoneExistsMessage)): ?>
                    <div class="p-4 mb-4 text-sm rounded-lg bg-red-100 text-red-700">
                        <span><?php echo htmlspecialchars($phoneExistsMessage); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="flex flex-col md:flex-row">
                        <!-- Left Section (Profile Image, Phone, Email) -->
                        <div class="md:w-1/2 flex flex-col items-center p-4">
                            <div class="relative w-32 h-32 mb-4">
                                <img class="w-full h-full rounded-full object-cover" src="<?= $profileImage; ?>" alt="Profile Picture">
                            </div>

                            <!-- Email -->
                            <div class="flex justify-center mt-4">
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                            </div>
                            <div class="flex justify-center mt-1">
                                <input type="email" name="email" class="w-full p-2 border rounded-md text-center" value="<?= htmlspecialchars($user['email']); ?>" readonly />
                            </div>

                            <!-- Phone -->
                            <div class="flex justify-center mt-4">
                                <label class="block mt-2 text-sm font-medium text-gray-700">Phone</label>
                            </div>
                            <div class="flex justify-center mt-1">
                                <input type="text" name="phone" id="phone" class="w-full mt-1 p-2 border rounded-md" value="<?= htmlspecialchars($user['phone_number']); ?>" onkeyup="validatePhone()" />
                            </div>
                        </div>

                        <div class="w-px bg-gray-300"></div>

                        <!-- Right Section (Profile Update: Full Name, Username, Course, College) -->
                        <div class="md:w-1/2 p-4">
                            <h2 class="text-lg font-semibold text-gray-700">Update Profile</h2>

                            <label class="block mt-4 text-sm font-medium text-gray-700">Full Name</label>
                            <input type="text" name="full_name" class="w-full mt-1 p-2 border rounded-md" value="<?= htmlspecialchars($user['full_name']); ?>" />

                            <label class="block mt-2 text-sm font-medium text-gray-700">Username</label>
                            <input type="text" name="username" class="w-full mt-1 p-2 border rounded-md" value="<?= htmlspecialchars($user['username']); ?>" />

                        </div>
                    </div>

                    <div class="flex justify-center mt-6">
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-lg shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">Save Changes</button>
                    </div>
                </form>

                <!-- Back Button -->
                <div class="flex justify-center mt-6">
                    <a href="myprofile.php" class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-lg shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">Back to My Profile</a>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    // Validate phone number and check if it exists in the database
    function validatePhone() {
        var phoneInput = document.getElementById('phone').value;
        
        if (phoneInput.length === 10 && /^[0-9]{10}$/.test(phoneInput)) {
            // The phone number is valid
            // If you want to perform an Ajax check here for phone existence, you can add it
        }
    }
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</body>
</html>
