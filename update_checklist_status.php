<?php
// update_checklist_status.php

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

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// Retrieve and sanitize input
$checklist_id = isset($_POST['checklist_id']) ? intval($_POST['checklist_id']) : 0;
$is_done = isset($_POST['is_done']) ? intval($_POST['is_done']) : -1;

// Validate input
if ($checklist_id <= 0 || ($is_done !== 0 && $is_done !== 1)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid checklist ID or status.']);
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

// Verify that the checklist item belongs to a project of the CSO
$sql_verify = "SELECT pc.id FROM project_checklists pc 
               JOIN projects p ON pc.project_id = p.id 
               JOIN cso_chairperson c ON p.cso_id = c.id 
               WHERE pc.id = ? AND c.cso_name = ?";
$stmt_verify = $conn->prepare($sql_verify);
$stmt_verify->bind_param("is", $checklist_id, $cso_name);
$stmt_verify->execute();
$result_verify = $stmt_verify->get_result();

if ($result_verify->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid checklist item or insufficient permissions.']);
    exit;
}

$stmt_verify->close();

// Update the checklist item's status
$sql_update = "UPDATE project_checklists SET is_done = ? WHERE id = ?";
$stmt_update = $conn->prepare($sql_update);
$stmt_update->bind_param("ii", $is_done, $checklist_id);

if ($stmt_update->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Checklist item status updated.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error updating checklist item.']);
}

$stmt_update->close();
?>
