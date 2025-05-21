<?php
include "header.php";
include "connection.php"; // Database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details from database
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

// Profile image path (default if not set)
$profileImage = !empty($user['profile_photo']) ? "images/profile_photo/" . $user['profile_photo'] : "https://via.placeholder.com/150";

$message = "";
$messageType = "";

// Handle profile image upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["uploadImage"])) {
    $targetDir = "images/profile_photo/";
    $fileName = basename($_FILES["uploadImage"]["name"]);
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $newFileName = "profile_" . $user_id . "." . $fileType;
    $targetFilePath = $targetDir . $newFileName;
    $allowedTypes = ['jpg', 'jpeg', 'png'];

    if (!in_array($fileType, $allowedTypes)) {
        $message = "❌ Invalid file format! Only JPG, JPEG, and PNG are allowed.";
        $messageType = "error";
    } elseif ($_FILES["uploadImage"]["size"] > 15 * 1024 * 1024) {
        $message = "❌ File size exceeds 15MB limit.";
        $messageType = "error";
    } else {
        if (move_uploaded_file($_FILES["uploadImage"]["tmp_name"], $targetFilePath)) {
            $updateSQL = "UPDATE users SET profile_photo = ? WHERE user_id = ?";
            $stmt = $conn->prepare($updateSQL);
            $stmt->bind_param("si", $newFileName, $user_id);
            $stmt->execute();
            $message = "✔️ Profile image updated successfully!";
            $messageType = "success";
            $profileImage = $targetFilePath; // Update displayed image immediately
        } else {
            $message = "❌ Error uploading file.";
            $messageType = "error";
        }
    }
}
// Initialize message variables from session
$message = $_SESSION['message'] ?? '';
$messageType = $_SESSION['message_type'] ?? '';

// Clear the message session variable after displaying the message
unset($_SESSION['message']);
unset($_SESSION['message_type']);
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
                <div class="flex flex-col md:flex-row">
                    <!-- Left Section -->
                    <div class="md:w-1/2 flex flex-col items-center p-4">
                        <div class="relative w-32 h-32">
                            <img id="profileImage" class="w-full h-full rounded-full object-cover" src="<?= $profileImage; ?>" alt="Profile Picture">
                        </div>
                        <form method="POST" enctype="multipart/form-data">
    <label for="uploadImage" class="mt-2 flex items-center gap-2 text-gray-700 cursor-pointer border border-gray-300 px-3 py-2 rounded-lg hover:bg-gray-100 transition-all">
        <i class="fas fa-camera text-blue-500"></i> <!-- Font Awesome Camera Icon -->
        <span class="font-medium ml-2">Change Picture</span>
        <input type="file" id="uploadImage" name="uploadImage" class="hidden" accept="image/jpeg, image/png, image/jpg" onchange="this.form.submit()">
    </label>
</form>

                        <div class="mt-4 w-full">
                            <label class="block text-sm font-medium text-gray-700">Full Name</label>
                            <input type="text" class="w-full mt-1 p-2 border rounded-md" value="<?= htmlspecialchars($user['full_name']); ?>" readonly />
                            <label class="block mt-2 text-sm font-medium text-gray-700">Email</label>
                            <input type="email" class="w-full mt-1 p-2 border rounded-md" value="<?= htmlspecialchars($user['email']); ?>" readonly />
                            <label class="block mt-2 text-sm font-medium text-gray-700">Phone</label>
                            <input type="text" class="w-full mt-1 p-2 border rounded-md" value="<?= htmlspecialchars($user['phone_number']); ?>" readonly />
                        </div>
                    </div>
                    <div class="w-px bg-gray-300"></div>
                    <div class="md:w-1/2 p-4">
                        <h2 class="text-lg font-semibold text-gray-700">Profile Details</h2>
                        <label class="block mt-4 text-sm font-medium text-gray-700">Username</label>
                        <input type="text" class="w-full mt-1 p-2 border rounded-md" value="<?= htmlspecialchars($user['username']); ?>" readonly />
                        <label class="block mt-2 text-sm font-medium text-gray-700">Course ID</label>
                        <input type="text" class="w-full mt-1 p-2 border rounded-md" value="<?= htmlspecialchars($user['course_id']); ?>" readonly />
                        <label class="block mt-2 text-sm font-medium text-gray-700">College ID</label>
                        <input type="text" class="w-full mt-1 p-2 border rounded-md" value="<?= htmlspecialchars($user['college_id']); ?>" readonly />
                    </div>
                </div>
                <div class="flex justify-center mt-6">
                    <a href="update_profile.php" class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-lg shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">Update Profile</a>
                </div>
            </div>
        </div>
    </div>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</main>
                </body>
                </html>