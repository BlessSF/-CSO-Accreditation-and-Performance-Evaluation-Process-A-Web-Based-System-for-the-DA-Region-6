<?php
// delete_project.php

// Start the session
session_start();

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database connection file
include('include/db_connect.php');

// Function to sanitize input (optional but recommended)
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Check if the 'id' parameter is set in the URL
if (!isset($_GET['id'])) {
    $_SESSION['message'] = "No project ID provided.";
    header("Location: admin_projects_list.php");
    exit();
}

// Sanitize and validate the 'id' parameter
$project_id = intval($_GET['id']);
if ($project_id <= 0) {
    $_SESSION['message'] = "Invalid project ID.";
    header("Location: admin_projects_list.php");
    exit();
}

// Begin a transaction to ensure data integrity
mysqli_begin_transaction($conn);

try {
    // Fetch the project's file path to delete the file from the server
    $stmt_file = $conn->prepare("SELECT file_path FROM projects WHERE id = ?");
    if (!$stmt_file) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt_file->bind_param("i", $project_id);
    $stmt_file->execute();
    $result_file = $stmt_file->get_result();

    if ($result_file->num_rows === 0) {
        throw new Exception("Project not found.");
    }

    $project = $result_file->fetch_assoc();
    $file_path = $project['file_path'];

    // Delete associated entries from the 'project_cso' junction table
    $stmt_junction = $conn->prepare("DELETE FROM project_cso WHERE project_id = ?");
    if (!$stmt_junction) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt_junction->bind_param("i", $project_id);
    $stmt_junction->execute();

    // Delete the project from the 'projects' table
    $stmt_delete = $conn->prepare("DELETE FROM projects WHERE id = ?");
    if (!$stmt_delete) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt_delete->bind_param("i", $project_id);
    $stmt_delete->execute();

    // Commit the transaction
    mysqli_commit($conn);

    // Delete the associated file from the server if it exists
    if (!empty($file_path) && file_exists($file_path)) {
        if (!unlink($file_path)) {
            // If file deletion fails, log the error and inform the user
            $_SESSION['message'] = "Project deleted, but failed to delete the associated file.";
        } else {
            $_SESSION['message'] = "Project and associated file deleted successfully.";
        }
    } else {
        $_SESSION['message'] = "Project deleted successfully.";
    }
} catch (Exception $e) {
    // Rollback the transaction in case of error
    mysqli_rollback($conn);
    $_SESSION['message'] = "Error deleting project: " . $e->getMessage();
}

// Close all prepared statements
if (isset($stmt_file)) $stmt_file->close();
if (isset($stmt_junction)) $stmt_junction->close();
if (isset($stmt_delete)) $stmt_delete->close();

// Close the database connection
$conn->close();

// Redirect back to the projects list
header("Location: admin_projects_list.php");
exit();
?>
