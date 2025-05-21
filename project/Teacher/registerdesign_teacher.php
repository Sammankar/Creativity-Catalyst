<?php
// Database connection
include 'connection.php';

// Fetch all colleges (active and inactive)
$collegesQuery = "SELECT college_id, name FROM colleges WHERE college_status = 1 ORDER BY name";
$collegesResult = mysqli_query($conn, $collegesQuery);
$colleges = mysqli_fetch_all($collegesResult, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en" :class="{ 'theme-dark': dark }" x-data="data()">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Teacher/Guide Registration Page</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/tailwind.output.css" />
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>
    <script src="assets/js/init-alpine.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  </head>

  <body class="bg-gray-50 dark:bg-gray-900">
    <div class="flex items-center min-h-screen p-6">
      <div class="flex-1 h-full max-w-4xl mx-auto overflow-hidden bg-white rounded-lg shadow-xl dark:bg-gray-800">
        <div class="flex flex-col overflow-y-auto md:flex-row">
          <div class="h-32 md:h-auto md:w-1/2">
            <img aria-hidden="true" class="object-cover w-full h-full dark:hidden" src="" alt="College Image" />
            <img aria-hidden="true" class="hidden object-cover w-full h-full dark:block" src="" alt="College Image" />
          </div>

          <div class="flex items-center justify-center p-6 sm:p-12 md:w-1/2">
            <div class="w-full">
              <h1 class="mb-4 text-xl font-semibold text-gray-700 dark:text-gray-200">Teacher/Guide Registration</h1>

              <!-- Display Success/Error Message -->
              <?php if (isset($_GET['message'])): ?>
    <div class="flex items-center p-4 mb-4 text-sm rounded-lg 
        <?php echo $_GET['type'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
        
        <?php if ($_GET['type'] === 'success'): ?>
            <svg class="w-5 h-5 mr-2 text-green-700" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 00-2 0v3a1 1 0 002 0V7zm-1 5a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/>
            </svg>
        <?php else: ?>
            <svg class="w-5 h-5 mr-2 text-red-700" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 00-2 0v3a1 1 0 002 0V7zm-1 5a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/>
            </svg>
        <?php endif; ?>
        
        <span><?php echo htmlspecialchars($_GET['message']); ?></span>
    </div>
<?php endif; ?>

              <form method="POST" action="register_teacher.php">
                <!-- College Dropdown -->
                <label class="block text-sm">
                  <span class="text-gray-700 dark:text-gray-400">Select College</span>
                  <select id="college" name="college_id" class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-select" required>
                    <option value="">-- Select College --</option>
                    <?php foreach ($colleges as $college) { ?>
                      <option value="<?php echo $college['college_id']; ?>"><?php echo $college['name']; ?></option>
                    <?php } ?>
                  </select>
                </label>

                <!-- Course Dropdown -->
                <label class="block text-sm mt-4">
                  <span class="text-gray-700 dark:text-gray-400">Select Course</span>
                  <select id="course" name="course_id" class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-select" required>
                    <option value="">-- Select Course --</option>
                    <!-- Courses will be populated dynamically using JavaScript -->
                  </select>
                </label>

                <!-- Full Name -->
                <label class="block text-sm mt-4">
                  <span class="text-gray-700 dark:text-gray-400">Full Name</span>
                  <input type="text" name="name" class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" required />
                </label>

                <!-- Email -->
                <label class="block text-sm mt-4">
                  <span class="text-gray-700 dark:text-gray-400">Email</span>
                  <input type="email" name="email" class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" required />
                </label>

                <!-- Password -->
                <label class="block text-sm mt-4">
                  <span class="text-gray-700 dark:text-gray-400">Password</span>
                  <input type="password" name="password" class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" required />
                </label>

                <!-- Guide Permission Checkbox -->
                <label class="block text-sm mt-4">
                  <input type="checkbox" name="guide_permission" value="1" class="mr-2">
                  <span class="text-gray-700 dark:text-gray-400">Request Guide Permission</span>
                </label>

                <!-- Submit Button -->
                <button type="submit" class="block w-full px-4 py-2 mt-4 text-sm font-medium leading-5 text-center text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple">
                  Register
                </button>
              </form>

              <hr class="my-8" />
              <p class="mt-4">
                <a class="text-sm font-medium text-purple-600 dark:text-purple-400 hover:underline" href="index.php">
                  Already have an account? Login
                </a>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- JavaScript to Fetch Courses -->
    <script>
    document.getElementById('college').addEventListener('change', function() {
        var collegeId = this.value;
        var courseDropdown = document.getElementById('course');
        courseDropdown.innerHTML = '<option value="">-- Select Course --</option>';

        if (collegeId) {
            fetch(`get_courses.php?college_id=${collegeId}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(course => {
                        var option = document.createElement('option');
                        option.value = course.course_id;
                        option.text = course.name;
                        courseDropdown.appendChild(option);
                    });
                });
        }
    });
    </script>
  </body>
</html>