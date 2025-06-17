<?php
// cso_delete_task.php

include('include/db_connect.php');
session_start();

// 1. Validate the request method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $_SESSION['message'] = "Invalid request method.";
    header("Location: cso_projects_list.php");
    exit();
}

// 2. Validate and retrieve the task_id from GET parameters
if (!isset($_GET['task_id']) || empty($_GET['task_id'])) {
    $_SESSION['message'] = "Invalid Task ID.";
    header("Location: cso_projects_list.php");
    exit();
}

$task_id = intval($_GET['task_id']);

// 3. Fetch the task to get project_id and file_path
$sql_fetch_task = "SELECT project_id, title, file_path FROM tasks WHERE id = ?";
$stmt_fetch = $conn->prepare($sql_fetch_task);
if (!$stmt_fetch) {
    $_SESSION['message'] = "Database error: " . $conn->error;
    header("Location: cso_projects_list.php");
    exit();
}
$stmt_fetch->bind_param("i", $task_id);
$stmt_fetch->execute();
$result_fetch = $stmt_fetch->get_result();

if ($result_fetch && $result_fetch->num_rows > 0) {
    $task = $result_fetch->fetch_assoc();
    $project_id   = intval($task['project_id']);
    $task_title   = $task['title'];
    $file_path    = $task['file_path'];
} else {
    $_SESSION['message'] = "Task not found.";
    header("Location: cso_projects_list.php");
    exit();
}
$stmt_fetch->close();

// 4. Delete the task from the database
$sql_delete_task = "DELETE FROM tasks WHERE id = ?";
$stmt_delete = $conn->prepare($sql_delete_task);
if (!$stmt_delete) {
    $_SESSION['message'] = "Database error: " . $conn->error;
    header("Location: cso_view_project.php?id=" . $project_id);
    exit();
}
$stmt_delete->bind_param("i", $task_id);
if ($stmt_delete->execute()) {
    $_SESSION['message'] = "Task '{$task_title}' has been successfully deleted.";
} else {
    $_SESSION['message'] = "Failed to delete task: " . $stmt_delete->error;
    $stmt_delete->close();
    $conn->close();
    header("Location: cso_view_project.php?id=" . $project_id);
    exit();
}
$stmt_delete->close();

// 5. Delete the associated file if it exists
if (!empty($file_path) && file_exists($file_path)) {
    if (unlink($file_path)) {
        // Optionally, you can log that the file was deleted successfully
    } else {
        // Optionally, handle the error if the file couldn't be deleted
        // For example, log the error or set a session message
        $_SESSION['message'] .= " However, failed to delete the supporting document.";
    }
}

// 6. Close the database connection
$conn->close();

// 7. Redirect back to the project view page
header("Location: cso_view_project.php?id=" . $project_id);
exit();
?>
