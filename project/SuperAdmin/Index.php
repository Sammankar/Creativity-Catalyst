<?php 
session_start();
// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
  header("Location: dashboard.php");
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
    <title>Super-Admin Dashboard</title>
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
              src="images/banners/Log.png"
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
                Login
              </h1>
            <!-- Display Messages -->
            <?php if (!empty($message)): ?>
  <div class="p-4 mb-4 text-sm rounded-lg <?php echo ($message_type === 'success') ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
    <span><?php echo htmlspecialchars($message); ?></span>
  </div>
<?php endif; ?>

              <form method="POST" action="login.php">
                <label class="block text-sm">
                  <span class="text-gray-700 dark:text-gray-400">Email</span>
                  <input
                    class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input"
                    placeholder="Enter Your Email ID"
                    type="email"
                    name="lemailphone"
                    id="lemailphone"
                    required
                  />
                </label>
                <label class="block mt-4 text-sm">
                  <span class="text-gray-700 dark:text-gray-400">Password</span>
                  <input
                    class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input"
                    placeholder="***************"
                    type="password"
                    name="lpassword"
                    id="lpassword"
                    required
                  />
                </label>
                <div class="flex items-center mb-4 mt-4">
                  <input id="remember-me" type="checkbox" name="remember_me" id="remember_me" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                  <label for="remember-me" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Remember me</label>
                </div>
                <button type="submit" class="block w-full px-4 py-2 mt-4 text-sm font-medium leading-5 text-center text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple">
                  Sign In
                </button>
              </form>
              <hr class="my-8" />
              <p class="mt-4">
                <a
                  class="text-sm font-medium text-purple-600 dark:text-purple-400 hover:underline"
                  href="forgot.php"
                >
                  Forgot your password?
                </a>
              </p>
              <p class="mt-1">
                <a
                  class="text-sm font-medium text-purple-600 dark:text-purple-400 hover:underline"
                  href="registerdesign.php"
                >
                  Create account
                </a>
              </p>
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
