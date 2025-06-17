<?php
// get_project_milestones.php

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

// Query to get milestones for this project
$sql = "
    SELECT 
        m.id, 
        m.title, 
        m.description, 
        m.comments, 
        m.status,
        m.target_date,
        m.actual_date,
        m.file_path
    FROM 
        milestones m
    WHERE 
        m.project_id = ?
    ORDER BY 
        m.id ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $project_id);
$stmt->execute();
$result = $stmt->get_result();

$milestones = [];
while ($row = $result->fetch_assoc()) {
    $milestones[] = $row;
}

// For each milestone, get its associated tasks
$sql_tasks = "
    SELECT 
        milestone_id, 
        task_id
    FROM 
        milestone_tasks
    WHERE 
        project_id = ?
";

$stmt_tasks = $conn->prepare($sql_tasks);
$stmt_tasks->bind_param('i', $project_id);
$stmt_tasks->execute();
$result_tasks = $stmt_tasks->get_result();

$associated_tasks = [];
while ($row = $result_tasks->fetch_assoc()) {
    $milestone_id = $row['milestone_id'];
    $task_id = $row['task_id'];
    
    if (!isset($associated_tasks[$milestone_id])) {
        $associated_tasks[$milestone_id] = [];
    }
    
    $associated_tasks[$milestone_id][] = $task_id;
}

// Add associated tasks to each milestone
foreach ($milestones as &$milestone) {
    $milestone_id = $milestone['id'];
    $milestone['associated_tasks'] = isset($associated_tasks[$milestone_id]) ? $associated_tasks[$milestone_id] : [];
}

// Return milestones as JSON
header('Content-Type: application/json');
echo json_encode($milestones);

$stmt->close();
$stmt_tasks->close();
$conn->close();
?>