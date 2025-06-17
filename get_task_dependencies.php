<?php
include('include/db_connect.php');
session_start();

if (!isset($_GET['task_id']) || empty($_GET['task_id'])) {
    echo json_encode(['error' => 'Invalid task ID']);
    exit();
}

$task_id = intval($_GET['task_id']);

// Fetch task dependencies (predecessors)
$sql = "SELECT td.predecessor_id, t.title 
        FROM task_dependencies td
        JOIN tasks t ON td.predecessor_id = t.id
        WHERE td.task_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $task_id);
$stmt->execute();
$result = $stmt->get_result();

$dependencies = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $dependencies[] = [
            'id' => $row['predecessor_id'],
            'title' => $row['title']
        ];
    }
}
$stmt->close();

echo json_encode(['dependencies' => $dependencies]);
?>