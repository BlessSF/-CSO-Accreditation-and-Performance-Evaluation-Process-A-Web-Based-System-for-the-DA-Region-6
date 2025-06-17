<?php
// cso_project_status.php

session_start();
include('include/db_connect.php');

// Set the response header to plain text
header('Content-Type: text/plain');

// Ensure the session variable 'cso_name' is set
if (!isset($_SESSION['cso_name'])) {
    echo 'error';
    exit;
}

$cso_name = $_SESSION['cso_name'];

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo 'error';
    exit;
}

// Retrieve and sanitize input
$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
$new_status = isset($_POST['status']) ? trim($_POST['status']) : '';

$valid_statuses = ['Accepted', 'Denied', 'Ongoing', 'Done'];

if ($project_id <= 0 || !in_array($new_status, $valid_statuses)) {
    echo 'error';
    exit;
}

// Fetch CSO ID
$sql_fetch_cso_id = "SELECT id FROM cso_chairperson WHERE cso_name = ?";
$stmt_fetch_cso_id = $conn->prepare($sql_fetch_cso_id);
$stmt_fetch_cso_id->bind_param("s", $cso_name);
$stmt_fetch_cso_id->execute();
$result_cso_id = $stmt_fetch_cso_id->get_result();

if ($result_cso_id->num_rows > 0) {
    $cso_row = $result_cso_id->fetch_assoc();
    $cso_id = $cso_row['id'];
} else {
    echo 'error';
    exit;
}

$stmt_fetch_cso_id->close();

// Verify that the project belongs to the CSO
$sql_verify = "SELECT id FROM projects WHERE id = ? AND cso_id = ?";
$stmt_verify = $conn->prepare($sql_verify);
$stmt_verify->bind_param("ii", $project_id, $cso_id);
$stmt_verify->execute();
$result_verify = $stmt_verify->get_result();

if ($result_verify->num_rows === 0) {
    echo 'error';
    exit;
}

$stmt_verify->close();

// Update the project's status
$sql_update = "UPDATE projects SET status = ? WHERE id = ?";
$stmt_update = $conn->prepare($sql_update);
$stmt_update->bind_param("si", $new_status, $project_id);

if ($stmt_update->execute()) {
    echo 'success';
} else {
    echo 'error';
}

$stmt_update->close();
?>
