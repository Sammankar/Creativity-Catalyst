<?php
include 'connection.php';

if (isset($_GET['college_id'])) {
    $college_id = $_GET['college_id'];
    $query = "SELECT cc.course_id, c.name 
              FROM college_courses cc 
              JOIN courses c ON cc.course_id = c.course_id 
              WHERE cc.college_id = $college_id AND cc.college_course_status = 1";
    $result = mysqli_query($conn, $query);
    $courses = mysqli_fetch_all($result, MYSQLI_ASSOC);
    echo json_encode($courses);
}
?>