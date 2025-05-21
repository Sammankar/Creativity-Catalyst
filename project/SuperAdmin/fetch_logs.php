<?php
session_start();
include 'connection.php'; // Include DB connection

$user_id = $_SESSION['user_id']; 

$status = $_POST['status'] ?? 'all';
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$search = $_POST['search'] ?? '';
$page = $_POST['page'] ?? 1;
$limit = 5;
$offset = ($page - 1) * $limit;

$where = "WHERE 1";
if ($status !== 'all') $where .= " AND cr.status = '$status'";
if (!empty($start_date) && !empty($end_date)) {
    $where .= " AND cr.created_at BETWEEN '$start_date' AND '$end_date'";
}
if (!empty($search)) {
    $where .= " AND (u.full_name LIKE '%$search%' OR cl.name LIKE '%$search%' OR co.name LIKE '%$search%')";
}

$query = "SELECT 
    cr.request_id, cr.admin_id, u.full_name AS admin_name, u.email AS admin_email,
    cr.status, cr.created_at, cr.status_updated_at, GROUP_CONCAT(co.name SEPARATOR ', ') AS requested_courses,
    cl.name AS college_name
FROM course_requests cr
LEFT JOIN users u ON cr.admin_id = u.user_id
LEFT JOIN courses co ON cr.requested_course_id = co.course_id
LEFT JOIN colleges cl ON cr.college_id = cl.college_id
$where
GROUP BY cr.request_id
ORDER BY cr.created_at DESC
LIMIT $limit OFFSET $offset";


$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $statusColor = ($row['status'] == 0) ? "bg-yellow-500" : (($row['status'] == 1) ? "bg-green-500" : "bg-red-500");
        $statusText = ($row['status'] == 0) ? "Pending" : (($row['status'] == 1) ? "Approved" : "Rejected");
        $status_updated_at = !empty($row['status_updated_at']) 
        ? date("d M Y, h:i A", strtotime($row['status_updated_at'])) 
        : "Not Updated Yet";
    
    echo "<div class='bg-white p-4 rounded shadow-md flex justify-between items-center'>
            <div>
                <p><strong>College Name:</strong> {$row['college_name']}</p>
                <p><strong>Admin:</strong> {$row['admin_name']} ({$row['admin_email']})</p>
                <p><strong>Requested Courses:</strong> {$row['requested_courses']}</p>
                <p><strong>Requested Date:</strong> " . date("d M Y, h:i A", strtotime($row['created_at'])) . "</p>
                <p><strong>Status Updated At:</strong> {$status_updated_at}</p>
            </div>
    
            <div class='flex items-center space-x-4'>
                <p class='text-white px-2 py-1 inline-block rounded $statusColor status-text-{$row['request_id']}'>$statusText</p>
                " . ($row['status'] == 0 ? "
                    <div class='flex space-x-2'>
                        <button class='approve-btn bg-green-500 text-white px-3 py-1 rounded' data-id='{$row['request_id']}'>Approve</button>
                        <button class='reject-btn bg-red-500 text-white px-3 py-1 rounded' data-id='{$row['request_id']}'>Reject</button>
                    </div>
                " : "") . "
            </div>
        </div>";
    
    }
} else {
    echo "<p class='text-center text-gray-600'>No logs found.</p>";
}
?>
