<?php
// cso_update_milestone.php
include('include/db_connect.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate required fields
    $required_fields = ['milestone_id', 'title', 'description', 'target_date', 'status'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        $_SESSION['message'] = "Error: Missing required fields: " . implode(', ', $missing_fields);
        header("Location: javascript:history.back()");
        exit();
    }
    
    // Get form data
    $milestone_id = intval($_POST['milestone_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $target_date = trim($_POST['target_date']);
    $actual_date = !empty($_POST['actual_date']) ? trim($_POST['actual_date']) : NULL;
    $status = trim($_POST['status']);
    $comments = !empty($_POST['comments']) ? trim($_POST['comments']) : '';
    
    // First get the project_id and current file path
    $query = "SELECT project_id, file_path FROM milestones WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $milestone_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $milestone_data = $result->fetch_assoc();
    $project_id = $milestone_data['project_id'];
    $current_file_path = $milestone_data['file_path'];
    $stmt->close();
    
    // Handle file upload
    $file_path = $current_file_path; // Keep existing file by default
    if (isset($_FILES['supporting_doc']) && $_FILES['supporting_doc']['error'] == 0) {
        $upload_dir = 'uploads/milestones/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['supporting_doc']['name']);
        $target_file = $upload_dir . $file_name;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['supporting_doc']['tmp_name'], $target_file)) {
            // Delete old file if it exists
            if (!empty($current_file_path) && file_exists($current_file_path)) {
                unlink($current_file_path);
            }
            $file_path = $target_file;
        }
    }
    
    // Update milestone in database
    $sql = "UPDATE milestones 
    SET title = ?, description = ?, target_date = ?, actual_date = ?, 
        status = ?, comments = ?, file_path = ? 
    WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssi", $title, $description, $target_date, $actual_date, 
            $status, $comments, $file_path, $milestone_id);
    
    if ($stmt->execute()) {
        // Remove all existing associated tasks
        $delete_sql = "DELETE FROM milestone_tasks WHERE milestone_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $milestone_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        // Add new associated tasks
        if (isset($_POST['associated_tasks']) && is_array($_POST['associated_tasks'])) {
            $task_sql = "INSERT INTO milestone_tasks (milestone_id, task_id) VALUES (?, ?)";
            $task_stmt = $conn->prepare($task_sql);
            
            foreach ($_POST['associated_tasks'] as $task_id) {
                $task_stmt->bind_param("ii", $milestone_id, $task_id);
                $task_stmt->execute();
            }
            
            $task_stmt->close();
        }
        
        $_SESSION['message'] = "Milestone updated successfully!";
    } else {
        $_SESSION['message'] = "Error updating milestone: " . $stmt->error;
    }
    
    $stmt->close();
    header("Location: cso_view_project.php?id=" . $project_id);
    exit();
}
?>