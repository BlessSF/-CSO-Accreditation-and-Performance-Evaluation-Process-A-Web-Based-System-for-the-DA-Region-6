<?php
// fetch_checklists.php

session_start();
include('include/db_connect.php');

// Set the response header to JSON
header('Content-Type: application/json');

// Ensure the session variable 'cso_name' is set
if (!isset($_SESSION['cso_name'])) {
    echo json_encode(['status' => 'error', 'message' => 'Session not set.']);
    exit;
}

$cso_name = $_SESSION['cso_name'];

// Ensure the request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// Retrieve and sanitize input
$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

if ($project_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid project ID.']);
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
    echo json_encode(['status' => 'error', 'message' => 'CSO ID not found.']);
    exit;
}

$stmt_fetch_cso_id->close();

// Define statuses that allow checklists
$allowed_statuses = ['Accepted', 'Ongoing', 'Done'];

// Verify that the project belongs to the CSO and has an allowed status
$sql_verify = "SELECT id, status FROM projects WHERE id = ? AND cso_id = ? AND status IN ('Accepted', 'Ongoing', 'Done')";
$stmt_verify = $conn->prepare($sql_verify);
$stmt_verify->bind_param("ii", $project_id, $cso_id);
$stmt_verify->execute();
$result_verify = $stmt_verify->get_result();

if ($result_verify->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid project or insufficient permissions.']);
    exit;
}

$project = $result_verify->fetch_assoc();
$project_status = $project['status'];

$stmt_verify->close();

// Fetch all checklist items for the project
$sql_fetch_checklists = "SELECT * FROM project_checklists WHERE project_id = ?";
$stmt_fetch_checklists = $conn->prepare($sql_fetch_checklists);
$stmt_fetch_checklists->bind_param("i", $project_id);
$stmt_fetch_checklists->execute();
$result_checklists = $stmt_fetch_checklists->get_result();

$checklists = [];
while ($row = $result_checklists->fetch_assoc()) {
    $checklists[] = $row;
}

$stmt_fetch_checklists->close();

// Return the checklist items as JSON
echo json_encode(['status' => 'success', 'checklists' => $checklists]);
?>
