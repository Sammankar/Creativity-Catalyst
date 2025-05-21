<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Request Logs</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100 p-6">

    <div class="max-w-7xl mx-auto">
        <h1 class="text-2xl font-bold mb-4">📋 Course Request Logs</h1>

        <!-- Filters Section -->
        <div class="flex gap-4 mb-6">
            <button class="filter-btn bg-blue-500 text-white px-4 py-2 rounded" data-status="all">All</button>
            <button class="filter-btn bg-yellow-500 text-white px-4 py-2 rounded" data-status="0">Pending</button>
            <button class="filter-btn bg-green-500 text-white px-4 py-2 rounded" data-status="1">Approved</button>
            <button class="filter-btn bg-red-500 text-white px-4 py-2 rounded" data-status="2">Rejected</button>
            <input type="date" id="start_date" class="border px-2 py-1" />
            <input type="date" id="end_date" class="border px-2 py-1" />
            <input type="text" id="search" placeholder="Search Admin/College" class="border px-2 py-1" />
            <button id="resetFilters" class="bg-blue-500 text-white px-4 py-2 rounded">Reset</button>
            <a id="exportPdfBtn" 
                href="export_pdf.php" 
                target="_blank"
                class="bg-red-500 text-white px-4 py-2 rounded">
                Export PDF
                </a>
        </div>

        <!-- Logs Display Section -->
        <div id="logsContainer" class="grid grid-cols-1 gap-4">
            <!-- Logs will be loaded here via AJAX -->
        </div>

        <!-- Pagination -->
        <div class="mt-6 flex justify-center">
            <button id="prevPage" class="bg-blue-500 text-white px-4 py-2 rounded mr-2"> Prev</button>
            <span id="currentPage" class="text-lg">1</span>
            <button id="nextPage" class="bg-blue-500 text-white px-4 py-2 rounded ml-2">Next </button>
        </div>

    </div>
    <div class="flex justify-end">
                <a href="dashboard.php" 
                   class="px-4 py-2 mr-2 bg-blue-500 text-white font-semibold rounded-lg shadow-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
                   Back To Dashboard
                </a>
            </div>

    <script>
        $(document).ready(function() {
    let statusFilter = 'all';
    let startDate = '';
    let endDate = '';
    let searchQuery = '';
    let currentPage = 1;

    function fetchLogs() {
        $.ajax({
            url: "fetch_logs.php",
            method: "POST",
            data: { 
                status: statusFilter, 
                start_date: startDate, 
                end_date: endDate, 
                search: searchQuery, 
                page: currentPage 
            },
            success: function(response) {
                $("#logsContainer").html(response);
            }
        });
    }

    fetchLogs(); // Load logs on page load

    $(".filter-btn").click(function() {
        statusFilter = $(this).data("status");
        fetchLogs();
    });

    $("#search").on("keyup", function() {
        searchQuery = $(this).val();
        fetchLogs();
    });

    $("#start_date, #end_date").change(function() {
        startDate = $("#start_date").val();
        endDate = $("#end_date").val();
        fetchLogs();
    });

    $("#resetFilters").click(function() {
        statusFilter = 'all';
        startDate = '';
        endDate = '';
        searchQuery = '';
        $("#start_date, #end_date, #search").val('');
        fetchLogs();
    });

    $("#prevPage").click(function() {
        if (currentPage > 1) {
            currentPage--;
            fetchLogs();
            $("#currentPage").text(currentPage);
        }
    });

    $("#nextPage").click(function() {
        currentPage++;
        fetchLogs();
        $("#currentPage").text(currentPage);
    });
});

    </script>
    <script>
    $(document).ready(function() {
    function updateStatus(requestId, status) {
        $.ajax({
            url: "update_status.php",
            method: "POST",
            data: { request_id: requestId, status: status },
            success: function(response) {
                if (response == "success") {
                    let statusText = status == 1 ? "Approved" : "Rejected";
                    let statusColor = status == 1 ? "bg-green-500" : "bg-red-500";
                    
                    $(".status-text-" + requestId).text(statusText).removeClass("bg-yellow-500").addClass(statusColor);
                    $(".approve-btn[data-id='" + requestId + "'], .reject-btn[data-id='" + requestId + "']").remove();
                } else {
                    alert("Error updating status!");
                }
            }
        });
    }

    $(document).on("click", ".approve-btn", function() {
        let requestId = $(this).data("id");
        updateStatus(requestId, 1);
    });

    $(document).on("click", ".reject-btn", function() {
        let requestId = $(this).data("id");
        updateStatus(requestId, 2);
    });
});

    </script>
    <script>
    function updateExportLink() {
    let status = statusFilter;
    let startDate = $("#start_date").val();
    let endDate = $("#end_date").val();
    let searchQuery = $("#search").val();

    let exportUrl = `export_pdf.php?status=${status}&start_date=${startDate}&end_date=${endDate}&search=${searchQuery}`;
    $("#exportPdfBtn").attr("href", exportUrl);
}

// Update Export PDF button when filters change
$(".filter-btn, #start_date, #end_date, #search").on("change keyup", updateExportLink);
$("#resetFilters").click(function() {
    setTimeout(updateExportLink, 100); // Delay to reset values first
});

    </script>
</body>
</html>
