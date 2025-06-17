<?php
// cso_update_task.php

include('include/db_connect.php');
session_start();

// Check if the form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['message'] = "Invalid request method.";
    header("Location: user_project.php");
    exit();
}

// Function to sanitize input data
function sanitize_input($data) {
    return htmlspecialchars(trim($data));
}

// Initialize variables
$task_id      = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
$title        = isset($_POST['title']) ? sanitize_input($_POST['title']) : '';
$description  = isset($_POST['description']) ? sanitize_input($_POST['description']) : '';
$duration     = isset($_POST['duration']) ? intval($_POST['duration']) : 0;
$spent        = isset($_POST['spent']) ? floatval($_POST['spent']) : 0.00;
$predecessors = isset($_POST['predecessors']) ? $_POST['predecessors'] : [];
$comments     = isset($_POST['comments']) ? sanitize_input($_POST['comments']) : '';
$status       = isset($_POST['status']) ? sanitize_input($_POST['status']) : 'Pending';
// For proposed dates, validate but don't sanitize with htmlspecialchars
$proposed_start_date = isset($_POST['proposed_start_date']) ? trim($_POST['proposed_start_date']) : '';
$proposed_end_date = isset($_POST['proposed_end_date']) ? trim($_POST['proposed_end_date']) : '';

// For actual dates, handle NULL values properly
$actual_start_date = !empty($_POST['actual_start_date']) ? trim($_POST['actual_start_date']) : NULL;
$actual_end_date = !empty($_POST['actual_end_date']) ? trim($_POST['actual_end_date']) : NULL;

// Fetch the existing task to get project_id and existing file_path
$sql_fetch_task = "SELECT project_id, file_path FROM tasks WHERE id = ?";
$stmt_fetch = $conn->prepare($sql_fetch_task);
if (!$stmt_fetch) {
    $_SESSION['message'] = "Database error: " . $conn->error;
    header("Location:  user_project.php");
    exit();
}
$stmt_fetch->bind_param("i", $task_id);
$stmt_fetch->execute();
$result_fetch = $stmt_fetch->get_result();

if ($result_fetch && $result_fetch->num_rows > 0) {
    $existing_task = $result_fetch->fetch_assoc();
    $project_id   = intval($existing_task['project_id']);
    $existing_file_path = $existing_task['file_path'];
} else {
    $_SESSION['message'] = "Task not found.";
    header("Location:  user_project.php");
    exit();
}
$stmt_fetch->close();

// Validate required fields
if (empty($title) || empty($description) || empty($status)) {
    $_SESSION['message'] = "Please fill in all required fields.";
    header("Location: user_view_project.php?id=" . $project_id);
    exit();
}

// Validate status
$allowed_statuses = ['Pending', 'In Progress', 'Done'];
if (!in_array($status, $allowed_statuses)) {
    $_SESSION['message'] = "Invalid task status.";
    header("Location: user_view_project.php?id=" . $project_id);
    exit();
}

// Initialize variables for file handling
$file_uploaded = false;
$file_path = $existing_file_path; // Default to existing file path

// If status is 'Done', ensure duration and spent are > 0
if (strtolower($status) === 'done') {
    if ($duration <= 0 || $spent <= 0) {
        $_SESSION['message'] = "For tasks marked as 'Done', 'Duration' and 'Budget Spent' must be greater than zero.";
        header("Location: user_view_project.php?id=" . $project_id);
        exit();
    }

    // If there's no existing file, require a new file upload
    if (empty($existing_file_path)) {
        if (!isset($_FILES['supporting_doc']) || $_FILES['supporting_doc']['error'] === UPLOAD_ERR_NO_FILE) {
            $_SESSION['message'] = "Supporting Document is required for tasks marked as 'Done'.";
            header("Location: user_view_project.php?id=" . $project_id);
            exit();
        }
    }
}

// Handle file upload if a new file is provided
if (isset($_FILES['supporting_doc']) && $_FILES['supporting_doc']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['supporting_doc'];

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['message'] = "Error uploading file: " . $file['error'];
        header("Location: user_view_project.php?id=" . $project_id);
        exit();
    }

    // Define allowed file types and max size (e.g., 5MB)
    $allowed_mime_types = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png',
        'image/gif'
    ];
    $max_file_size = 5 * 1024 * 1024; // 5MB

    // Verify file size
    if ($file['size'] > $max_file_size) {
        $_SESSION['message'] = "File size exceeds the maximum allowed size of 5MB.";
        header("Location: user_view_project.php?id=" . $project_id);
        exit();
    }

    // Verify MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);

    if (!in_array($mime_type, $allowed_mime_types)) {
        $_SESSION['message'] = "Invalid file type. Allowed types: PDF, DOC, DOCX, JPEG, PNG, GIF.";
        header("Location: user_view_project.php?id=" . $project_id);
        exit();
    }

    // Define the upload directory
    $upload_dir = 'uploads/supporting_docs/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generate a unique filename to prevent overwriting
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $unique_filename = uniqid('task_', true) . '.' . $file_extension;
    $destination = $upload_dir . $unique_filename;

    // Move the uploaded file
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        $_SESSION['message'] = "Failed to move uploaded file.";
        header("Location: user_view_project.php?id=" . $project_id);
        exit();
    }

    // Set the new file path
    $file_path = $destination;
    $file_uploaded = true;

    // If there was an existing file, delete it to prevent orphaned files
    if (!empty($existing_file_path) && file_exists($existing_file_path)) {
        unlink($existing_file_path);
    }
}

// If no new file is uploaded and existing file exists, retain the existing file path
// If a new file is uploaded, $file_path is already set to the new file

// Prepare the SQL statement
$sql_update_task = "
    UPDATE tasks
    SET title = ?, 
        description = ?, 
        duration = ?, 
        spent = ?, 
        comments = ?, 
        status = ?, 
        file_path = ?,
        proposed_start_date = ?,
        proposed_end_date = ?,
        actual_start_date = ?,
        actual_end_date = ?
    WHERE id = ?
";

$stmt = $conn->prepare($sql_update_task);

if (!$stmt) {
    // If a new file was uploaded, delete it to prevent orphaned files
    if ($file_uploaded && file_exists($file_path)) {
        unlink($file_path);
    }
    $_SESSION['message'] = "Database error: " . $conn->error;
    header("Location: user_view_project.php?id=" . $project_id);
    exit();
}

// Bind parameters with the corrected type string
$stmt->bind_param(
    "ssidsssssssi",
    $title,
    $description,
    $duration,
    $spent,
    $comments,
    $status,
    $file_path,
    $proposed_start_date,
    $proposed_end_date,
    $actual_start_date,
    $actual_end_date,
    $task_id
);

// Execute the statement
if ($stmt->execute()) {
    $_SESSION['message'] = "Task '{$title}' has been successfully updated.";
} else {
    // If there was an error, optionally delete the uploaded file to prevent orphan files
    if ($file_uploaded && file_exists($file_path)) {
        unlink($file_path);
    }
    $_SESSION['message'] = "Failed to update task: " . $stmt->error;
}

$sql_delete_dependencies = "DELETE FROM task_dependencies WHERE task_id = ?";
$stmt_delete = $conn->prepare($sql_delete_dependencies);
if (!$stmt_delete) {
    $_SESSION['message'] = "Database error when deleting dependencies: " . $conn->error;
    header("Location: user_view_project.php?id=" . $project_id);
    exit();
}
$stmt_delete->bind_param("i", $task_id);
$stmt_delete->execute();
$stmt_delete->close();

// First, delete existing task dependencies
$sql_delete_dependencies = "DELETE FROM task_dependencies WHERE task_id = ?";
$stmt_delete = $conn->prepare($sql_delete_dependencies);
if (!$stmt_delete) {
    $_SESSION['message'] = "Database error when deleting dependencies: " . $conn->error;
    header("Location: user_view_project.php?id=" . $project_id);
    exit();
}
$stmt_delete->bind_param("i", $task_id);
$stmt_delete->execute();
$stmt_delete->close();

// Then, insert new task dependencies if any exist
if (!empty($predecessors)) {
    // Updated SQL to include project_id
    $sql_insert_dependency = "INSERT INTO task_dependencies (task_id, predecessor_id, project_id) VALUES (?, ?, ?)";
    $stmt_dependency = $conn->prepare($sql_insert_dependency);
    
    if (!$stmt_dependency) {
        $_SESSION['message'] = "Database error when adding dependencies: " . $conn->error;
        header("Location: user_view_project.php?id=" . $project_id);
        exit();
    }
    
    foreach ($predecessors as $predecessor_id) {
        // Ensure predecessor_id is an integer
        $predecessor_id = intval($predecessor_id);
        
        // Skip if the predecessor is the task itself
        if ($predecessor_id == $task_id) {
            continue;
        }
        
        // Modified bind_param to include project_id
        $stmt_dependency->bind_param("iii", $task_id, $predecessor_id, $project_id);
        $stmt_dependency->execute();
    }
    
    $stmt_dependency->close();
}

// Close the statement and connection
$stmt->close();
$conn->close();

// Redirect back to the project view page
header("Location: user_view_project.php?id=" . $project_id);
exit();
?>
