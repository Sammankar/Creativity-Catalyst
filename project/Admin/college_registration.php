<?php
session_start();
require 'connection.php'; // Adjust path based on your structure

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = new mysqli("localhost", "root", "", "project"); // Update with your DB credentials
$query = "SELECT users_status FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($users_status);
$stmt->fetch();
$stmt->close();

// If the user's account is inactive (users_status = 0), log them out
if ($users_status == 0) {
    // Destroy the session and clear the "Remember Me" cookie
    session_destroy();
    setcookie('user_id', '', time() - 3600, '/'); // Expire the cookie
    header("Location: index.php"); // Redirect to login page
    exit();
}

// Check if college_id already exists
$stmt = $conn->prepare("SELECT college_id FROM users WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user['college_id'] !== NULL) {
    header("Location: dashboard.php"); // Redirect if already registered
    exit();
}
$message = isset($_SESSION['message']) ? $_SESSION['message'] : "";
$message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : "";

// Clear session messages after displaying
unset($_SESSION['message'], $_SESSION['message_type']);
?>
<!DOCTYPE html>
<html :class="{ 'theme-dark': dark }" x-data="data()" lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>College Register Page</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="assets/css/tailwind.output.css" />
    <script
      src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js"
      defer
    ></script>
    <script src="assets/js/init-alpine.js"></script>
  </head>
  <body>
    <div class="flex items-center min-h-screen p-6 bg-gray-50 dark:bg-gray-900">
      <div
        class="flex-1 h-full max-w-4xl mx-auto overflow-hidden bg-white rounded-lg shadow-xl dark:bg-gray-800"
      >
        <div class="flex flex-col overflow-y-auto md:flex-row">
          <div class="h-32 md:h-auto md:w-1/2">
            <img
              aria-hidden="true"
              class="object-cover w-full h-full dark:hidden"
              src="images/banners/college_registration.png"
              alt="Img"
            />
            <img
              aria-hidden="true"
              class="hidden object-cover w-full h-full dark:block"
              src=""
              alt="Img"
            />
          </div>
          <div class="flex items-center justify-center p-6 sm:p-12 md:w-1/2">
            <div class="w-full">
              <h1
                class="mb-4 text-xl font-semibold text-gray-700 dark:text-gray-200"
              >
                College Registration
              </h1>
            <!-- Display Messages -->
            <?php if (!empty($message)): ?>
  <div class="p-4 mb-4 text-sm rounded-lg <?php echo ($message_type === 'success') ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
    <span><?php echo htmlspecialchars($message); ?></span>
  </div>
<?php endif; ?>

              <form method="POST" action="register_college.php" enctype="multipart/form-data">
                <label class="block text-sm">
                  <span class="text-gray-700 dark:text-gray-400">College Name</span>
                  <input
                    class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input"
                    placeholder="Enter College Name"
                    type="text"
                    name="name"
                    id="name"
                    required
                  />
                </label>
                <label class="block mt-4 text-sm">
                  <span class="text-gray-700 dark:text-gray-400">College Address</span>
                  <input
                    class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input"
                    placeholder="Enter College Address"
                    type="text"
                    name="address"
                    id="address"
                    required
                  />
                </label>
                <label class="block mt-4 text-sm">
                  <span class="text-gray-700 dark:text-gray-400">Contact Number</span>
                  <input
                    class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input"
                    placeholder="Enter College Contact Number"
                    type="text"
                    name="contact_number"
                    id="contact_number"
                    required
                  />
                </label>
                <label class="block mt-4 text-sm">
                  <span class="text-gray-700 dark:text-gray-400">Director Name</span>
                  <input
                    class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input"
                    placeholder="Enter Director Name"
                    type="text"
                    name="director"
                    id="director"
                    required
                  />
                </label>
                <label class="block mt-4 text-sm">
                  <span class="text-gray-700 dark:text-gray-400">College Website(URL)</span>
                  <input
                    class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input"
                    placeholder="Enter College Website"
                    type="url"
                    name="website"
                    id="website"
                    required
                  />
                </label>
                <label class="block mt-4 text-sm">
                  <span class="text-gray-700 dark:text-gray-400">College Logo (JPG, PNG, max 10MB)</span>
                  <input
                    class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input"
                    type="file"
                    name="college_logo"
                    id="college_logo"
                    accept="image/png, image/jpeg"
                    required
                  />
                </label>
                <button type="submit" class="block w-full px-4 py-2 mt-4 text-sm font-medium leading-5 text-center text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple">
                  Register College
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
    <script>
    // Remove previous pages from history
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        history.pushState(null, null, location.href);
    };
</script>

  </body>
</html>
