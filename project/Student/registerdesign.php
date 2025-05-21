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
    <title>Student Registration Page</title>
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
            <img aria-hidden="true" class="object-cover w-full h-full dark:hidden" src="images/banners/register.png" alt="College Image" />
            <img aria-hidden="true" class="hidden object-cover w-full h-full dark:block" src="images/banners/register.png" alt="College Image" />
          </div>

          <div class="flex items-center justify-center p-6 sm:p-12 md:w-1/2">
            <div class="w-full">
              <h1 class="mb-4 text-xl font-semibold text-gray-700 dark:text-gray-200">Student Registration</h1>
              
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
              <form method="POST" action="register.php" id="registrationForm">
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
                  </select>
                </label>

                <!-- Semester Dropdown -->
                <label class="block text-sm mt-4">
                  <span class="text-gray-700 dark:text-gray-400">Select Semester</span>
                  <select id="semester" name="semester" class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-select" required>
                    <option value="">-- Select Semester --</option>
                  </select>
                </label>

                <!-- Full Name -->
                <label class="block text-sm mt-4">
                  <span class="text-gray-700 dark:text-gray-400">Full Name</span>
                  <input type="text" name="name" class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" required />
                </label>

                <label class="block text-sm mt-4">
                  <span class="text-gray-700 dark:text-gray-400">Email</span>
                  <input type="email" name="email" class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" required />
                </label>

                <!-- Password -->
                <label class="block text-sm mt-4">
                  <span class="text-gray-700 dark:text-gray-400">Password</span>
                  <input type="password" name="password" class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" required />
                </label>

                <!-- Submit Button -->
                <button type="button" id="showConfirmPopup" class="block w-full px-4 py-2 mt-4 text-sm font-medium leading-5 text-center text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple">
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

    <!-- Success Popup -->
    <div id="statusSuccessPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
      <div class="bg-white p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-semibold text-gray-700 mb-4 text-center">Confirm Registration</h2>
        <p id="statusSuccessMessage" class="text-gray-600 text-center mb-4"></p>
        <div class="flex justify-center mt-4">
        <button id="cancelStatusSuccess" class="px-4 py-2 bg-gray-500 text-black rounded-md ml-4">Cancel</button>
          <button id="closeStatusSuccess" class="px-4 py-2 bg-blue-500 text-white rounded-md">OK</button>
          
        </div>
      </div>
    </div>

    <!-- JavaScript to Handle Popup and Course Selection -->
    <script>
    // Event listener to show the confirmation popup
    document.getElementById('showConfirmPopup').addEventListener('click', function() {
        // Get selected values
        const collegeId = document.getElementById('college').value;
        const collegeName = document.querySelector(`#college option[value="${collegeId}"]`).textContent;
        const courseId = document.getElementById('course').value;
        const courseName = document.querySelector(`#course option[value="${courseId}"]`).textContent;
        const semester = document.getElementById('semester').value;

        // Check if all fields are selected
        if (collegeId && courseId && semester) {
            // Show confirmation message in popup
            document.getElementById('statusSuccessMessage').textContent = 
                `Are you sure you want to register with:
                College: ${collegeName}
                Course: ${courseName}
                Semester: ${semester}`;

            // Show the popup
            document.getElementById('statusSuccessPopup').classList.remove('hidden');
        } else {
            alert('Please select all fields to proceed.');
        }
    });

    // Close the popup when the user clicks "OK"
    document.getElementById('closeStatusSuccess').addEventListener('click', function() {
        document.getElementById('statusSuccessPopup').classList.add('hidden');
        document.getElementById('registrationForm').submit(); // Submit the form after confirmation
    });

    // Cancel the popup
    document.getElementById('cancelStatusSuccess').addEventListener('click', function() {
        document.getElementById('statusSuccessPopup').classList.add('hidden');
    });

    // Fetch courses based on selected college
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

    // Fetch semesters based on selected course
    document.getElementById('course').addEventListener('change', function() {
        var courseId = this.value;
        var semesterDropdown = document.getElementById('semester');
        semesterDropdown.innerHTML = '<option value="">-- Select Semester --</option>';

        if (courseId) {
            fetch(`get_course_semesters.php?course_id=${courseId}`)
                .then(response => response.json())
                .then(data => {
                    for (let i = 1; i <= data.total_semesters; i++) {
                        var option = document.createElement('option');
                        option.value = i;
                        option.text = `Semester ${i}`;
                        semesterDropdown.appendChild(option);
                    }
                });
        }
    });
    </script>
  </body>
</html>
