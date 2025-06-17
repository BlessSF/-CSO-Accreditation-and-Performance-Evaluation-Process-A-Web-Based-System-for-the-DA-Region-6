<?php
session_start();
include('include/db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'];
    $label = $_POST['label'];

    // Save the checklist item in the database
    $sql_insert_checklist = "INSERT INTO project_checklist (project_id, label, done) VALUES (?, ?, 0)";
    $stmt_insert_checklist = $conn->prepare($sql_insert_checklist);
    $stmt_insert_checklist->bind_param("is", $project_id, $label);
    $stmt_insert_checklist->execute();
    $stmt_insert_checklist->close();

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}
?>
