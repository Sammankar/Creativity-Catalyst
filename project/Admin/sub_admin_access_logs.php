<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch college_id from users table using user_id
$collegeQuery = "SELECT college_id FROM users WHERE user_id = ?";
$stmt = $conn->prepare($collegeQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$collegeResult = $stmt->get_result();
$collegeRow = $collegeResult->fetch_assoc();
$college_id = $collegeRow['college_id'];

// Fetch user role and access status
$query = "SELECT role, access_status FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$role = $user['role'];
$access_status = $user['access_status'];

// Fetch access reports with filtering and searching
$filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Start building the query to fetch teacher access reports
$query = "SELECT 
            adr.*, 
            sa.full_name AS changed_by_name, 
            sub_admin.full_name AS user_name, 
            sub_admin.access_status,
            adr.previous_access_status,
            adr.current_access_status,
            sa.user_id AS admin_id
          FROM teacher_access_denial_reports adr
          JOIN users sa ON adr.changed_by = sa.user_id AND sa.role = 2
          JOIN users sub_admin ON adr.user_id = sub_admin.user_id AND sub_admin.role = 3
          WHERE sub_admin.college_id = ?";  // Filter by college_id of the logged-in admin

// Prepare conditions for additional filters
$conditions = [];
$params = [];
$types = 'i';  // First condition is college_id, which is an integer

$params[] = $college_id;  // Add college_id to parameters

if ($filter !== '') {  // Ensure the filter check includes `0`
    $conditions[] = "adr.access_denial_report_status = ?";
    $params[] = $filter;
    $types .= "i";  // Append integer type for the filter
}

if (!empty($search)) {
    $conditions[] = "(adr.subject LIKE ? OR adr.body LIKE ?)";
    $searchTerm = "%" . $search . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";  // Append string type for the search term
}

// Combine conditions properly
if (!empty($conditions)) {
    $query .= " AND " . implode(" AND ", $conditions);
}

// Add ordering
$query .= " ORDER BY adr.created_at DESC";

// Prepare and execute the statement with the dynamically created parameters
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
    <title>Access Reports Logs</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="max-w-6xl mx-auto p-4">
        <h1 class="text-3xl font-extrabold text-gray-800 mb-6">Access Reports Logs</h1>
        <?php
if (isset($_GET['message'])) {
    echo "<script>alert('" . htmlspecialchars($_GET['message']) . "');</script>";
}
?>

<div class="bg-gradient-to-r from-blue-400 to-teal-400 p-6 shadow-lg rounded-lg mb-6">
    <form method="GET" action="" class="flex flex-wrap gap-4 items-center">
        <input type="text" name="search" placeholder="Search reports..." 
            class="w-full md:w-1/2 p-4 border-2 border-gray-300 rounded-lg shadow-lg focus:ring-2 focus:ring-blue-400 focus:outline-none transition duration-300" 
            value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="bg-blue-600 text-white px-5 py-3 rounded-lg shadow-xl hover:bg-blue-700 transition-all duration-300">Search</button>
        <button type="submit" name="status" value="0" class="bg-yellow-500 text-white px-5 py-3 rounded-lg shadow-xl hover:bg-yellow-600 transition-all duration-300">Ongoing</button>
        <button type="submit" name="status" value="1" class="bg-green-500 text-white px-5 py-3 rounded-lg shadow-xl hover:bg-green-600 transition-all duration-300">Resolved</button>
        <a href="access_reports_logs.php" class="bg-gray-500 text-white px-5 py-3 rounded-lg shadow-xl hover:bg-gray-600 transition-all duration-300">Reset</a>
    </form>
    <a href="export_sub_admin_access_logs.php" class="bg-red-500 text-white px-5 py-3 rounded-lg shadow-xl mt-3 inline-block hover:bg-red-600 transition-all duration-300">Export as PDF</a>
    <a href="dashboard.php" 
       class="px-5 py-3 mr-3 bg-blue-600 text-white font-semibold rounded-lg shadow-xl hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
       Back To Dashboard
    </a>


<div id="report-list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-6 justify-items-center max-h-[500px] overflow-y-auto">
    <?php if (count($reports) > 0): ?>
        <?php foreach ($reports as $report) : ?>
            <div class="bg-white p-6 rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-300 flex flex-col justify-between max-w-sm mx-auto space-y-4">
                <div class="flex flex-col space-y-3">
                    <h2 class="text-lg font-bold text-gray-900">
                        <?php echo htmlspecialchars($report['subject']); ?>
                    </h2>
                    <p class="text-sm text-gray-600">Created on: 
                        <span class="font-semibold"><?php echo date('d M Y, g:i A', strtotime($report['created_at'])); ?></span>
                    </p>
                    <p class="text-sm text-gray-600">Access Restricted by: 
                        <span class="font-semibold"><?php echo htmlspecialchars($report['changed_by_name']); ?></span>
                    </p>
                    <p class="text-sm text-gray-600">Sub-Admin Name: 
                        <span class="font-semibold"><?php echo htmlspecialchars($report['user_name']); ?></span>
                    </p>
                    <p class="text-sm text-gray-600">Previous Access Status: 
                        <span class="font-semibold"><?php echo $report['previous_access_status'] == 1 ? 'Granted' : 'Restricted'; ?></span>
                    </p>
                    <p class="text-sm text-gray-600">Updated Access Status: 
                        <span class="font-semibold"><?php echo $report['current_access_status'] == 1 ? 'Granted' : 'Restricted'; ?></span>
                    </p>
                    <p class="text-sm font-bold inline-flex items-center justify-center px-4 py-2 rounded-full 
    <?php echo $report['access_denial_report_status'] == 0 ? 'bg-yellow-400 text-white animate-pulse' : 'bg-green-500 text-white'; ?>">
    <?php echo $report['access_denial_report_status'] == 0 ? 'Ongoing' : 'Resolved'; ?>
</p>

                </div>

                <div class="flex justify-between items-center mt-4">


                    <div class="flex space-x-4">
                    <?php if ($report['access_denial_report_status'] == 0): ?>
        <button class="give-access-btn bg-blue-500 text-white px-4 py-2 rounded-lg shadow-md hover:bg-blue-600 transition" data-report-id="<?php echo $report['report_id']; ?>" data-user-id="<?php echo $report['user_id']; ?>">Grant</button>
        <button class="restrict-access-btn bg-green-500 text-white px-4 py-2 rounded-lg shadow-md hover:bg-green-600 transition" data-report-id="<?php echo $report['report_id']; ?>" data-user-id="<?php echo $report['user_id']; ?>">Restrict</button>
    <?php else: ?>
        <button class="<?php echo $report['access_status'] == 1 ? 'bg-green-500' : 'bg-red-500'; ?> text-white px-4 py-2 rounded-lg shadow-md cursor-not-allowed" disabled>
            <?php echo $report['access_status'] == 1 ? 'Access Granted' : 'Access Restricted'; ?>
        </button>
                            <button class="access-update-btn bg-yellow-500 text-white px-3 py-3 rounded-lg shadow-xl hover:bg-yellow-600 transition-all duration-300" 
                                    data-report-id="<?php echo $report['report_id']; ?>" data-user-id="<?php echo $report['user_id']; ?>">Access Update</button>
                        <?php endif; ?>
                        
                    </div>
                </div>

                <!-- Action buttons for expand and chat -->
                <div class="flex space-x-4 mt-4 mr-4">
                    <button class="bg-blue-500 text-white px-6 py-3 rounded-lg shadow-md hover:bg-blue-600 transition duration-300"
                            onclick="expandReport(<?php echo $report['report_id']; ?>)">
                        Expand
                    </button>
                    <button class="bg-green-500 text-white px-6 py-3 rounded-lg shadow-md hover:bg-green-600 transition duration-300"
                            onclick="openChat(<?php echo $report['report_id']; ?>)">
                        Chat
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-center text-gray-500 col-span-full">No reports found.</p>
    <?php endif; ?>
</div>
</div>



<!-- Chat Popup -->
<div id="chat-container" class="hidden fixed bottom-5 right-5 w-96 h-96 bg-white shadow-xl rounded-3xl flex flex-col">
    <div class="bg-blue-600 text-white p-4 flex justify-between items-center rounded-t-3xl">
        <span id="chat-header">Chat</span>
        <button id="lock-chat-btn" class="bg-red-500 text-white px-4 py-2 rounded-lg shadow-md hover:bg-red-600 transition-all" onclick="toggleChatLock()">Lock Chat</button>
        <button onclick="closeChat()" class="text-white">&times;</button>
    </div>
    <div id="chat-messages" class="flex-1 p-3 overflow-y-auto">
        <p class="text-gray-500 text-sm text-center">No messages yet...</p>
    </div>
    <div class="p-3 border-t">
        <input type="text" id="chat-input" placeholder="Type a message..." class="w-full p-3 border-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" onkeydown="if(event.key==='Enter') sendMessage()">
    </div>
</div>

<!-- Access Update Popup Modal -->
<div id="accessUpdatePopup" class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-60 flex justify-center items-center hidden">
    <div class="bg-white p-6 rounded-lg shadow-lg w-80">
        <h3 class="text-xl font-bold mb-4">Access Update</h3>
        <button class="access-update-action-btn bg-blue-600 text-white px-5 py-3 rounded-lg shadow-xl mb-4 hover:bg-blue-700 transition-all" id="grantBtn">Grant Access</button>
        <button class="access-update-action-btn bg-red-600 text-white px-5 py-3 rounded-lg shadow-xl mb-4 hover:bg-red-700 transition-all" id="restrictBtn">Restrict Access</button>
        <button class="bg-gray-500 text-white px-5 py-3 rounded-lg shadow-xl hover:bg-gray-600 transition-all" onclick="closeAccessUpdatePopup()">Cancel</button>
    </div>
</div>


            <script>
        function expandReport(reportId) {
            window.location.href = "sub_admin_report_details.php?report_id=" + reportId;
        }
        function openChat(reportId) {
            window.location.href = "chat.php?report_id=" + reportId;
        }
    </script>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(".give-access-btn, .restrict-access-btn").forEach(button => {
            button.addEventListener("click", function () {
                const reportId = this.getAttribute("data-report-id");
                const userId = this.getAttribute("data-user-id");
                const action = this.classList.contains("give-access-btn") ? "give_access" : "restrict";
                if (!confirm(`Are you sure you want to ${action === "give_access" ? "grant access" : "restrict access"}?`)) return;
                fetch("update_sub_admin_report_access_status.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `report_id=${reportId}&user_id=${userId}&action=${action}`
                })
                .then(response => response.json())
                .then(data => { alert(data.message); window.location.reload(); })
                .catch(error => alert("Error: " + error));
            });
        });
    });
    </script>
    <script>
    // Open chat for a specific report
    function openChat(reportId) {
        // Super-Admin's user_id is taken from the session
        const superAdminId = <?php echo $_SESSION['user_id']; ?>; // Fetch from session
        
        // Fetch Admin's user_id from the report data
        const adminUserId = <?php echo $report['user_id']; ?>; // Admin user_id associated with this report
        
        document.getElementById("chat-container").classList.remove("hidden");
        document.getElementById("chat-header").innerText = "Chat for Report #" + reportId;
        document.getElementById("chat-container").setAttribute("data-report", reportId);
        document.getElementById("chat-container").setAttribute("data-super-admin-id", superAdminId);  // Store Super-Admin ID
        document.getElementById("chat-container").setAttribute("data-admin-id", adminUserId);  // Store Admin's ID
        loadMessages(reportId);  // Load messages when the chat is opened
    }

    // Load messages for a specific report
    function loadMessages(reportId) {
    fetch("sub_admin_get_message.php?report_id=" + reportId)
        .then(response => response.json())
        .then(data => {
            let chatBox = document.getElementById("chat-messages");
            chatBox.innerHTML = ""; // Clear previous messages

            data.forEach(msg => {
                let messageElement = document.createElement("div");
                messageElement.classList.add("p-2", "my-1", "rounded-lg", "max-w-xs");

                // Get the date and time from the created_at field and format it
                let messageDate = new Date(msg.timestamp); // Convert timestamp to Date object
                let formattedDate = messageDate.toLocaleString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: 'numeric',
                    minute: 'numeric',
                    second: 'numeric',
                    hour12: true // Use 12-hour format (AM/PM)
                });

                // If the sender is the logged-in user (Super-Admin), style the message accordingly
                if (msg.sender_id == <?php echo $_SESSION['user_id']; ?>) {
                    messageElement.classList.add("bg-green-300", "text-black", "ml-auto"); // Super-Admin's message
                } else {
                    messageElement.classList.add("bg-white-200", "text-black-900", "mr-auto"); // Admin's message
                }

                // Add message content with the formatted date
                messageElement.innerHTML = ` 
                    <strong>${msg.sender_name}:</strong> ${msg.message} 
                    <br><span class="text-xs text-gray-500">${formattedDate}</span>
                `;
                
                chatBox.appendChild(messageElement);
            });

            chatBox.scrollTop = chatBox.scrollHeight; // Auto-scroll to bottom of chat box

            // Disable input if the chat is locked
            fetch("sub_admin_access_logs.php?report_id=" + reportId)
                .then(response => response.json())
                .then(data => {
                    if (data.locked) {
                        document.getElementById("chat-input").disabled = true;
                    }
                });
        });
}





    // Send a message in the chat
    function sendMessage() {
        let message = document.getElementById("chat-input").value.trim();
        let reportId = document.getElementById("chat-container").getAttribute("data-report");
        let superAdminId = document.getElementById("chat-container").getAttribute("data-super-admin-id"); // Super-Admin ID from session
        let adminUserId = document.getElementById("chat-container").getAttribute("data-admin-id");  // Admin User ID
        
        if (message === "") return;

        fetch("sub_admin_send_message.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `report_id=${reportId}&super_admin_id=${superAdminId}&admin_user_id=${adminUserId}&message=${message}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById("chat-input").value = "";
                loadMessages(reportId); // Refresh chat after sending
            }
        });
    }
    function closeChat() {
        document.getElementById("chat-container").classList.add("hidden");
    }

    // Auto-refresh chat every 5 seconds
    setInterval(() => {
        let reportId = document.getElementById("chat-container").getAttribute("data-report");
        if (reportId) {
            loadMessages(reportId);
        }
    }, 5000);
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    // Access Update Button Click
    document.querySelectorAll(".access-update-btn").forEach(button => {
        button.addEventListener("click", function () {
            // Show the Access Update popup
            document.getElementById("accessUpdatePopup").classList.remove("hidden");
            
            // Pass the report ID and user ID to the action buttons inside the popup
            const reportId = this.getAttribute("data-report-id");
            const userId = this.getAttribute("data-user-id");
            
            document.getElementById("grantBtn").onclick = () => updateAccess(reportId, userId, "grant");
            document.getElementById("restrictBtn").onclick = () => updateAccess(reportId, userId, "restrict");
        });
    });

    // Close the Access Update Popup
    function closeAccessUpdatePopup() {
        document.getElementById("accessUpdatePopup").classList.add("hidden");
    }

    // Update Access (Grant or Restrict)
    function updateAccess(reportId, userId, action) {
        fetch("update_sub_admin_report_access_status_v2.php", { // Use the new PHP file here
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `report_id=${reportId}&user_id=${userId}&action=${action}`
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            window.location.reload();  // Refresh the page to reflect changes
        })
        .catch(error => alert("Error: " + error));

        closeAccessUpdatePopup(); // Close the popup
    }

    // Close the popup if the user clicks outside of it
    window.onclick = function (event) {
        const popup = document.getElementById("accessUpdatePopup");
        if (event.target === popup) {
            closeAccessUpdatePopup();
        }
    }
});

</script>
<script>
    // Function to toggle chat lock/unlock
    function toggleChatLock() {
    const reportId = document.getElementById("chat-container").getAttribute("data-report"); // Get the report ID
    const lockButton = document.getElementById("lock-chat-btn");
    
    fetch("sub_admin_toggle_lock.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ report_id: reportId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.locked !== undefined) {
            if (data.locked) {
                lockButton.innerText = "Unlock Chat";
                alert("Chat is now locked.");
                document.getElementById("chat-input").disabled = true;  // Disable input
            } else {
                lockButton.innerText = "Lock Chat";
                alert("Chat is now unlocked.");
                document.getElementById("chat-input").disabled = false;  // Enable input
            }
        } else {
            alert("Error toggling chat lock status.");
        }
    })
    .catch(error => console.error('Error locking/unlocking chat:', error));
}


</script>


</body>
</html>