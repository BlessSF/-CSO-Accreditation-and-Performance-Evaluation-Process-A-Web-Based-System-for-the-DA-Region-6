<?php
include('include/db_connect.php');
session_start();

if (!isset($_GET['milestone_id']) || empty($_GET['milestone_id'])) {
    echo json_encode(['error' => 'Invalid milestone ID']);
    exit();
}

$milestone_id = intval($_GET['milestone_id']);

// Fetch milestone's associated tasks
$sql = "SELECT mt.task_id, t.title 
        FROM milestone_tasks mt
        JOIN tasks t ON mt.task_id = t.id
        WHERE mt.milestone_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $milestone_id);
$stmt->execute();
$result = $stmt->get_result();

$tasks = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tasks[] = [
            'id' => $row['task_id'],
            'title' => $row['title']
        ];
    }
}
$stmt->close();

echo json_encode(['tasks' => $tasks]);
?>