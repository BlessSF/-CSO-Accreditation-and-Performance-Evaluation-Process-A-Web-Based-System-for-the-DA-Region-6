<?php
// get_project_tasks.php

include('include/db_connect.php');
session_start();

// Check if user is logged in and has appropriate permissions
// Add your authentication check here...

// Get project ID from query parameter
$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

if ($project_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid project ID']);
    exit();
}

// Query to get tasks for this project
$sql = "
    SELECT 
        t.id, 
        t.title, 
        t.description, 
        t.duration, 
        t.spent, 
        t.comments, 
        t.status,
        t.proposed_start_date,
        t.proposed_end_date,
        t.actual_start_date,
        t.actual_end_date
    FROM 
        tasks t
    WHERE 
        t.project_id = ?
    ORDER BY 
        t.id ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $project_id);
$stmt->execute();
$result = $stmt->get_result();

$tasks = [];
while ($row = $result->fetch_assoc()) {
    $tasks[] = $row;
}

// For each task, get its predecessors
$sql_deps = "
    SELECT 
        task_id, 
        predecessor_id
    FROM 
        task_dependencies
    WHERE 
        project_id = ?
";

$stmt_deps = $conn->prepare($sql_deps);
$stmt_deps->bind_param('i', $project_id);
$stmt_deps->execute();
$result_deps = $stmt_deps->get_result();

$dependencies = [];
while ($row = $result_deps->fetch_assoc()) {
    $task_id = $row['task_id'];
    $predecessor_id = $row['predecessor_id'];
    
    if (!isset($dependencies[$task_id])) {
        $dependencies[$task_id] = [];
    }
    
    $dependencies[$task_id][] = $predecessor_id;
}

// Add predecessors to each task
foreach ($tasks as &$task) {
    $task_id = $task['id'];
    $task['predecessors'] = isset($dependencies[$task_id]) ? $dependencies[$task_id] : [];
}

// Return tasks as JSON
header('Content-Type: application/json');
echo json_encode($tasks);

$stmt->close();
$stmt_deps->close();
$conn->close();
?>