<?php 
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

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

// Fetch user role and access status
$query = "SELECT role, access_status FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$role = $user['role'];
$access_status = $user['access_status'];

// Ensure only Admins can access this page
if ($role != 5) {
    header("Location: dashboard.php");
    exit();
}

// Fetch reports relevant to the Admin
$filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$query = "SELECT 
            adr.*, 
            sa.full_name AS changed_by_name, 
            student.full_name AS user_name, 
            student.access_status,
            adr.previous_access_status,
            adr.current_access_status,
            sa.user_id AS admin_id  -- Fetch super-admin ID
          FROM student_access_denial_reports adr
          JOIN users sa ON adr.changed_by = sa.user_id AND sa.role = 3
          JOIN users student ON adr.user_id = student.user_id AND student.role = 5
          WHERE adr.user_id = ?";  // Only fetch reports related to this Admin

$conditions = [];
$params = [$user_id];
$types = 'i';

if ($filter !== '') {
    $conditions[] = "adr.access_denial_report_status = ?";
    $params[] = $filter;
    $types .= "i";
}

if (!empty($search)) {
    $conditions[] = "(adr.subject LIKE ? OR adr.body LIKE ?)";
    $searchTerm = "%" . $search . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

if (!empty($conditions)) {
    $query .= " AND " . implode(" AND ", $conditions);
}

$query .= " ORDER BY adr.updated_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$reports = $result->fetch_all(MYSQLI_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>student Access Reports</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="max-w-6xl mx-auto p-4">
        <h1 class="text-3xl font-extrabold text-gray-800 mb-6">student Access Reports</h1>
        
        <?php if (isset($_GET['message'])): ?>
            <script>alert('<?php echo htmlspecialchars($_GET['message']); ?>');</script>
        <?php endif; ?>

        <div class="bg-gradient-to-r from-blue-400 to-teal-400 p-6 shadow-lg rounded-lg mb-6">
            <form method="GET" action="" class="flex flex-wrap gap-4 items-center">
                <input type="text" name="search" placeholder="Search reports..." 
                    class="w-full md:w-1/2 p-4 border-2 border-gray-300 rounded-lg shadow-lg focus:ring-2 focus:ring-blue-400 focus:outline-none transition duration-300" 
                    value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="bg-blue-600 text-white px-5 py-3 rounded-lg shadow-xl hover:bg-blue-700 transition-all duration-300">Search</button>
                <button type="submit" name="status" value="0" class="bg-yellow-500 text-white px-5 py-3 rounded-lg shadow-xl hover:bg-yellow-600 transition-all duration-300">Ongoing</button>
                <button type="submit" name="status" value="1" class="bg-green-500 text-white px-5 py-3 rounded-lg shadow-xl hover:bg-green-600 transition-all duration-300">Resolved</button>
                <a href="access_report_logs.php" class="bg-gray-500 text-white px-5 py-3 rounded-lg shadow-xl hover:bg-gray-600 transition-all duration-300">Reset</a>
            </form>
            <a href="export_student_access_logs.php" class="bg-red-500 text-white px-5 py-3 rounded-lg shadow-xl mt-3 inline-block hover:bg-red-600 transition-all duration-300">Export as PDF</a>
            <a href="dashboard.php" 
                class="px-5 py-3 mr-3 bg-blue-600 text-white font-semibold rounded-lg shadow-xl hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
                Back To Dashboard
            </a>

            <a href="logout.php" class="px-5 py-3 mr-3 bg-blue-600 text-white font-semibold rounded-lg shadow-xl hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
                Log Out
            </a>

            <?php if ($access_status == 0): ?>
            <div class="bg-red-500 text-white text-xs font-bold px-4 py-2 rounded-lg flex items-center mt-4 sm:mt-0 sm:absolute sm:top-4 sm:left-1/2 sm:transform sm:-translate-x-1/2 sm:w-auto sm:max-w-full">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M4.93 19h14.14a2 2 0 001.732-3l-7.07-12a2 2 0 00-3.464 0l-7.07 12A2 2 0 004.93 19z"></path>
                </svg>
                <span>Dashboard Access Restricted. Contact Support via Chat or Mail.</span>
            </div>
            <?php endif; ?>

            <!-- Cards Container -->
            <div id="report-list" class="grid grid-cols-1 gap-6 mt-6 max-h-[500px] overflow-y-auto p-4">
    <?php if (count($reports) > 0): ?>
        <?php foreach ($reports as $report) : ?>
            <div class="bg-gradient-to-r from-blue-100 via-teal-100 to-blue-50 w-full p-6 rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-300 flex justify-between space-x-6 max-w-full ml-30 mr-10">
                <!-- Left Section: Report Details -->
                <div class="flex flex-col space-y-3 w-2/5">
                    <h2 class="text-lg font-bold text-gray-900">
                        <?php echo htmlspecialchars($report['subject']); ?>
                    </h2>
                    <p class="text-sm text-gray-600">Created on: 
                        <span class="font-semibold"><?php echo date('d M Y, g:i A', strtotime($report['updated_at'])); ?></span>
                    </p>
                    <p class="text-sm text-gray-600">Access Restricted by: 
                        <span class="font-semibold"><?php echo htmlspecialchars($report['changed_by_name']); ?></span>
                    </p>
                    <p class="text-sm text-gray-600">student Name: 
                        <span class="font-semibold"><?php echo htmlspecialchars($report['user_name']); ?></span>
                    </p>
                    <p class="text-sm text-gray-600">Previous Access Status: 
                        <span class="font-semibold"><?php echo $report['previous_access_status'] == 1 ? 'Granted' : 'Restricted'; ?></span>
                    </p>
                    <p class="text-sm text-gray-600">Updated Access Status: 
                        <span class="font-semibold"><?php echo $report['current_access_status'] == 1 ? 'Granted' : 'Restricted'; ?></span>
                    </p>
                </div>

                <!-- Center Section: Access Status Button -->
                <div class="flex flex-col justify-center items-center w-1/3 space-y-4">
                    <!-- Status Text (Ongoing/Resolved) -->
                    <p class="text-sm font-bold inline-flex items-center justify-center px-4 py-2 rounded-full 
                        <?php echo $report['access_denial_report_status'] == 0 ? 'bg-yellow-400 text-white animate-pulse' : 'bg-green-500 text-white'; ?>">
                        <?php echo $report['access_denial_report_status'] == 0 ? 'Ongoing' : 'Resolved'; ?>
                    </p>
                    <!-- Access Status Button -->
                    <button class="<?php echo $report['access_status'] == 1 ? 'bg-green-500' : 'bg-red-500'; ?> text-white px-4 py-2 rounded-lg shadow-md cursor-not-allowed" disabled>
                        <?php echo $report['access_status'] == 1 ? 'Access Granted' : 'Access Restricted'; ?>
                    </button>
                </div>

                <!-- Right Section: Expand and Chat Buttons -->
                <div class="flex flex-col space-y-4 justify-center w-1/3">
                    <button class="bg-blue-500 text-white px-6 py-3 rounded-lg shadow-md hover:bg-blue-600 transition duration-300"
                            onclick="expandReport(<?php echo $report['id']; ?>)">
                        Expand
                    </button>
                    <button class="bg-green-500 text-white px-6 py-3 rounded-lg shadow-md hover:bg-green-600 transition duration-300"
                            onclick="openChat(<?php echo $report['id']; ?>, <?php echo $report['admin_id']; ?>)">
                        Chat
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-center text-gray-500 col-span-full">No reports found.</p>
    <?php endif; ?>
</div>





        <!-- Chat Popup (Hidden Initially) -->
        <div id="chat-container" class="hidden fixed bottom-5 right-5 w-96 h-96 bg-white shadow-xl rounded-3xl flex flex-col">
    <div class="bg-blue-600 text-white p-4 flex justify-between items-center rounded-t-3xl">
        <span id="chat-header">Chat</span>
        <button onclick="closeChat()" class="text-white">&times;</button>
    </div>
    <div id="chat-messages" class="flex-1 p-3 overflow-y-auto">
        <p class="text-gray-500 text-sm text-center">No messages yet...</p>
    </div>
    <div class="p-3 border-t">
        <input type="text" id="chat-input" placeholder="Type a message..." class="w-full p-3 border-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" onkeydown="if(event.key==='Enter') sendMessage()">
    </div>
</div>


    </div>
    <script>
        function expandReport(reportId) {
            window.location.href = "student_access_report_log_details.php?report_id=" + reportId;
        }
        function openChat(reportId) {
            window.location.href = "student_chat.php?report_id=" + reportId;
        }
    </script>
<script>
    function openChat(reportId, superAdminId) {
        document.getElementById("chat-container").classList.remove("hidden");
        document.getElementById("chat-header").innerText = "Chat for Report #" + reportId;
        document.getElementById("chat-container").setAttribute("data-report", reportId);
        document.getElementById("chat-container").setAttribute("data-admin-id", superAdminId);  // Store super-admin ID
        loadMessages(reportId);
    }

    function closeChat() {
        document.getElementById("chat-container").classList.add("hidden");
    }

    function loadMessages(reportId) {
    fetch("get_messages.php?report_id=" + reportId)
        .then(response => response.json())
        .then(data => {
            let chatBox = document.getElementById("chat-messages");
            chatBox.innerHTML = ""; // Clear old messages

            data.forEach(msg => {
                let messageElement = document.createElement("div");
                messageElement.classList.add("p-2", "my-1", "rounded-lg", "max-w-xs");

                // Get the date and time from the created_at field and format it
                let messageDate = new Date(msg.timestamp); // Assuming 'timestamp' is the field name
                let formattedDate = messageDate.toLocaleString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: 'numeric',
                    minute: 'numeric',
                    second: 'numeric',
                    hour12: true // Use 12-hour format (AM/PM)
                });

                // If the sender is the logged-in user (Admin), style the message accordingly
                if (msg.sender_id == <?php echo $_SESSION['user_id']; ?>) {
                    messageElement.classList.add("bg-green-300", "text-black", "ml-auto"); // Admin's message
                } else {
                    messageElement.classList.add("bg-white-200", "text-black-900", "mr-auto"); // Super-Admin's message
                }

                // Add message content with the formatted date
                messageElement.innerHTML = `
                    <strong>${msg.sender_name}:</strong> ${msg.message} 
                    <br><span class="text-xs text-gray-500">${formattedDate}</span>
                `;
                chatBox.appendChild(messageElement);
            });

            chatBox.scrollTop = chatBox.scrollHeight; // Auto-scroll to bottom of chat box
        });
}


    // Auto-refresh chat every 5 seconds
    setInterval(() => {
        let reportId = document.getElementById("chat-container").getAttribute("data-report");
        if (reportId) {
            loadMessages(reportId);
        }
    }, 5000);

    function sendMessage() {
        let message = document.getElementById("chat-input").value.trim();
        let reportId = document.getElementById("chat-container").getAttribute("data-report");
        let receiverId = document.getElementById("chat-container").getAttribute("data-admin-id");  // Get super-admin ID dynamically

        if (message === "") return;

        fetch("send_message.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `report_id=${reportId}&receiver_id=${receiverId}&message=${message}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById("chat-input").value = "";
                loadMessages(reportId); // Refresh chat after sending
            }
        });
    }
</script>
</body>
</html>
