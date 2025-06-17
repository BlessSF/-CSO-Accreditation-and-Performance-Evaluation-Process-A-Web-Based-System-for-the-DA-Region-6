<?php
include('include/db_connect.php'); // Include your database connection
session_start(); // Start the session to use session variables

// Function to sanitize input data
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Check if the form is submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form inputs
    $task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
    $status = isset($_POST['status']) ? sanitize_input($_POST['status']) : '';
    $comments = isset($_POST['comments']) ? sanitize_input($_POST['comments']) : ''; // Add comments input
    date_default_timezone_set('Asia/Manila');
    $last_updated_by = $_SESSION['user_name'] ?? 'Unknown'; // Assuming you store the logged-in user's name in the session
    $last_updated_at = date('Y-m-d H:i:s'); // Current timestamp

    // Basic validation
    if ($task_id <= 0 || empty($status)) {
        $_SESSION['message'] = "Invalid input data.";
        header("Location: cso_projects_list.php"); // Redirect to project list if validation fails
        exit();
    }

    // Validate status value
    $valid_statuses = ['Pending', 'In Progress', 'Done'];
    if (!in_array($status, $valid_statuses)) {
        $_SESSION['message'] = "Invalid status value.";
        header("Location: cso_projects_list.php");
        exit();
    }

    // Begin transaction
    mysqli_begin_transaction($conn);

    try {
        // Update the task status, comments, last_updated_by, and last_updated_at in the 'tasks' table
        $sql_update_status = "UPDATE tasks SET status = ?, comments = ?, last_updated_by = ?, last_updated_at = ? WHERE id = ?";
        $stmt_update_status = $conn->prepare($sql_update_status);
        if (!$stmt_update_status) {
            throw new Exception("Error preparing statement: " . $conn->error);
        }

        $stmt_update_status->bind_param("ssssi", $status, $comments, $last_updated_by, $last_updated_at, $task_id);
        if (!$stmt_update_status->execute()) {
            throw new Exception("Error executing statement: " . $stmt_update_status->error);
        }
        $stmt_update_status->close();

        // Commit the transaction
        mysqli_commit($conn);

        // Set success message
        $_SESSION['message'] = "Task status updated successfully.";
    } catch (Exception $e) {
        // Rollback the transaction if an error occurs
        mysqli_rollback($conn);
        $_SESSION['message'] = "Update failed: " . $e->getMessage();
    }

    // Redirect back to the project details page
    // Fetch the project_id associated with the task to redirect correctly
    $sql_fetch_project_id = "SELECT project_id FROM tasks WHERE id = ?";
    $stmt_fetch_project = $conn->prepare($sql_fetch_project_id);
    $stmt_fetch_project->bind_param("i", $task_id);
    $stmt_fetch_project->execute();
    $stmt_fetch_project->bind_result($project_id);

    if ($stmt_fetch_project->fetch()) {
        $stmt_fetch_project->close();
        header("Location: cso_view_project.php?id=" . $project_id); // Redirect to the project page
        exit();
    } else {
        $stmt_fetch_project->close();
        // If project_id not found, redirect to project list
        $_SESSION['message'] = "Associated project not found.";
        header("Location: cso_projects_list.php");
        exit();
    }
} else {
    // If the form wasn't submitted via POST, redirect to project list
    $_SESSION['message'] = "Invalid request.";
    header("Location: cso_projects_list.php");
    exit();
}
?>
