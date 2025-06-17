<?php
// cso_assign_task.php

include('include/db_connect.php');
session_start();

// Check if the form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['message'] = "Invalid request method.";
    header("Location: cso_projects_list.php");
    exit();
}

// Function to sanitize input data
function sanitize_input($data) {
    return htmlspecialchars(trim($data));
}

// Initialize variables
$project_id   = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
$title        = isset($_POST['title']) ? sanitize_input($_POST['title']) : '';
$description  = isset($_POST['description']) ? sanitize_input($_POST['description']) : '';
$duration     = isset($_POST['duration']) ? intval($_POST['duration']) : 0;
$spent        = isset($_POST['spent']) ? floatval($_POST['spent']) : 0.00;
$predecessors = isset($_POST['predecessors']) ? $_POST['predecessors'] : [];
$comments     = isset($_POST['comments']) ? sanitize_input($_POST['comments']) : '';
$status       = isset($_POST['status']) ? sanitize_input($_POST['status']) : 'Pending';
$proposed_start_date = $_POST['proposed_start_date'] ?? '';
$proposed_end_date = $_POST['proposed_end_date'] ?? '';
$actual_start_date = $_POST['actual_start_date'] ?? NULL;
$actual_end_date = $_POST['actual_end_date'] ?? NULL;

// Validate required fields
if (empty($title) || empty($description) || empty($status)) {
    $_SESSION['message'] = "Please fill in all required fields.";
    header("Location: cso_view_project.php?id=" . $project_id);
    exit();
}

// Validate status
$allowed_statuses = ['Pending', 'In Progress', 'Done'];
if (!in_array($status, $allowed_statuses)) {
    $_SESSION['message'] = "Invalid task status.";
    header("Location: cso_view_project.php?id=" . $project_id);
    exit();
}

// If status is 'Done', ensure duration and spent are > 0 and a file is uploaded
$file_uploaded = false;
$file_path = null;

if (strtolower($status) === 'done') {
    if ($duration <= 0 || $spent <= 0) {
        $_SESSION['message'] = "For tasks marked as 'Done', 'Duration' and 'Budget Spent' must be greater than zero.";
        header("Location: cso_view_project.php?id=" . $project_id);
        exit();
    }

    if (!isset($_FILES['supporting_doc']) || $_FILES['supporting_doc']['error'] === UPLOAD_ERR_NO_FILE) {
        $_SESSION['message'] = "Supporting Document is required for tasks marked as 'Done'.";
        header("Location: cso_view_project.php?id=" . $project_id);
        exit();
    }
}

// Handle file upload if a file was provided
if (isset($_FILES['supporting_doc']) && $_FILES['supporting_doc']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['supporting_doc'];

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['message'] = "Error uploading file: " . $file['error'];
        header("Location: cso_view_project.php?id=" . $project_id);
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
        header("Location: cso_view_project.php?id=" . $project_id);
        exit();
    }

    // Verify MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);

    if (!in_array($mime_type, $allowed_mime_types)) {
        $_SESSION['message'] = "Invalid file type. Allowed types: PDF, DOC, DOCX, JPEG, PNG, GIF.";
        header("Location: cso_view_project.php?id=" . $project_id);
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
        header("Location: cso_view_project.php?id=" . $project_id);
        exit();
    }

    // Set the file path (you might want to adjust this based on your directory structure)
    $file_path = $destination;
    $file_uploaded = true;
}

// If a file was uploaded but status is not 'Done', optionally handle it
if (isset($_FILES['supporting_doc']) && $_FILES['supporting_doc']['error'] !== UPLOAD_ERR_NO_FILE) {
    // You can decide whether to allow file uploads for other statuses
    // For this example, we'll allow it and store the file_path
    if (!$file_uploaded) {
        // If file was uploaded but not required, still process it
        // This block is optional based on your requirements
        $file = $_FILES['supporting_doc'];

        // Check for upload errors
        if ($file['error'] === UPLOAD_ERR_OK) {
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
                header("Location: cso_view_project.php?id=" . $project_id);
                exit();
            }

            // Verify MIME type
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime_type = $finfo->file($file['tmp_name']);

            if (!in_array($mime_type, $allowed_mime_types)) {
                $_SESSION['message'] = "Invalid file type. Allowed types: PDF, DOC, DOCX, JPEG, PNG, GIF.";
                header("Location: cso_view_project.php?id=" . $project_id);
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
                header("Location: cso_view_project.php?id=" . $project_id);
                exit();
            }

            // Set the file path (you might want to adjust this based on your directory structure)
            $file_path = $destination;
        }
    }
}

// Prepare the SQL statement
$sql_insert_task = "
    INSERT INTO tasks (project_id, title, description, duration, spent, comments, status, file_path,
                      proposed_start_date, proposed_end_date, actual_start_date, actual_end_date)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql_insert_task);

if (!$stmt) {
    $_SESSION['message'] = "Database error: " . $conn->error;
    header("Location: cso_view_project.php?id=" . $project_id);
    exit();
}

// Bind parameters
// Assuming file_path can be NULL
$stmt->bind_param(
    "issddsssssss", 
    $project_id,
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
    $actual_end_date
);

// Execute the statement
if ($stmt->execute()) {
    // Get the ID of the newly inserted task
    $task_id = $stmt->insert_id;
    
    // Store task dependencies (predecessors) if any exist
    if (!empty($predecessors)) {
        // Prepare the SQL for inserting dependencies
        $sql_insert_dependency = "INSERT INTO task_dependencies (task_id, predecessor_id, project_id) VALUES (?, ?, ?)";
        $dep_stmt = $conn->prepare($sql_insert_dependency);
        
        if ($dep_stmt) {
            // For each predecessor, create a relationship
            foreach ($predecessors as $predecessor_id) {
                $predecessor_id = intval($predecessor_id); // Sanitize
                $dep_stmt->bind_param("iii", $task_id, $predecessor_id, $project_id);
                $dep_stmt->execute();
            }
            $dep_stmt->close();
        } else {
            // Log error for dependencies but don't stop the flow
            $_SESSION['message'] .= " Warning: Could not add task dependencies: " . $conn->error;
        }
    }
    
    $_SESSION['message'] = "Task '{$title}' has been successfully assigned.";
} else {
    // If there was an error, optionally delete the uploaded file to prevent orphan files
    if ($file_uploaded && file_exists($file_path)) {
        unlink($file_path);
    }
    $_SESSION['message'] = "Failed to assign task: " . $stmt->error;
}

// Close the statement and connection
$stmt->close();
$conn->close();

// Redirect back to the project view page
header("Location: cso_view_project.php?id=" . $project_id);
exit();
?>
