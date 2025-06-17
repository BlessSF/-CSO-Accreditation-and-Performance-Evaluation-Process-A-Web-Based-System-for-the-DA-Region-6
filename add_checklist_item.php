<?php
session_start();
include('include/db_connect.php');

if (!isset($_POST['project_id']) || !isset($_POST['label'])) {
    exit('Invalid request');
}

$project_id = $_POST['project_id'];
$label = $_POST['label'];
$added_on = date("Y-m-d H:i:s"); // Store the checklist's addition timestamp

$sql_add_checklist = "INSERT INTO project_checklist (project_id, label, done, added_on) VALUES (?, ?, 0, ?)";
$stmt = $conn->prepare($sql_add_checklist);
$stmt->bind_param("iss", $project_id, $label, $added_on);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'added_on' => $added_on]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
