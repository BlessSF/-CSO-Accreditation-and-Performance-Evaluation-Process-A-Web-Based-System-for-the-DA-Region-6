<?php
session_start();
include('include/db_connect.php');

if (!isset($_GET['project_id'])) {
    exit('Project ID not provided');
}

$project_id = $_GET['project_id'];
$sql_fetch_checklist = "SELECT * FROM project_checklist WHERE project_id = ?";
$stmt_checklist = $conn->prepare($sql_fetch_checklist);
$stmt_checklist->bind_param("i", $project_id);
$stmt_checklist->execute();
$result_checklist = $stmt_checklist->get_result();

$checklist = [];
while ($row = $result_checklist->fetch_assoc()) {
    $checklist[] = [
        'label' => $row['label'],
        'done' => (bool) $row['done'] // Assuming 'done' is stored as 0 or 1
    ];
}

$stmt_checklist->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode(['checklist' => $checklist]);