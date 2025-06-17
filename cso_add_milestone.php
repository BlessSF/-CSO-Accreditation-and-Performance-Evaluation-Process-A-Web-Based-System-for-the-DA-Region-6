<?php
// cso_add_milestone.php
include('include/db_connect.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate required fields
    $required_fields = ['project_id', 'title', 'description', 'target_date', 'status'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        $_SESSION['message'] = "Error: Missing required fields: " . implode(', ', $missing_fields);
        header("Location: cso_view_project.php?id=" . $_POST['project_id']);
        exit();
    }
    
    // Get form data
    $project_id = intval($_POST['project_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $target_date = trim($_POST['target_date']);
    $actual_date = !empty($_POST['actual_date']) ? trim($_POST['actual_date']) : NULL;
    $status = trim($_POST['status']);
    $comments = !empty($_POST['comments']) ? trim($_POST['comments']) : '';
    
    // Handle file upload
    $file_path = '';
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
            $file_path = $target_file;
        }
    }

    // Insert milestone into database
    $sql = "INSERT INTO milestones (project_id, title, description, target_date, actual_date, status, comments, file_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssssss", $project_id, $title, $description, $target_date, $actual_date, $status, $comments, $file_path);
    
    if ($stmt->execute()) {
        $milestone_id = $stmt->insert_id;
        
        // Handle associated tasks
        if (isset($_POST['associated_tasks']) && is_array($_POST['associated_tasks'])) {
            $task_sql = "INSERT INTO milestone_tasks (milestone_id, task_id) VALUES (?, ?)";
            $task_stmt = $conn->prepare($task_sql);
            
            foreach ($_POST['associated_tasks'] as $task_id) {
                $task_stmt->bind_param("ii", $milestone_id, $task_id);
                $task_stmt->execute();
            }
            
            $task_stmt->close();
        }
        
        $_SESSION['message'] = "Milestone added successfully!";
    } else {
        $_SESSION['message'] = "Error adding milestone: " . $stmt->error;
    }
    
    $stmt->close();
    header("Location: cso_view_project.php?id=" . $project_id);
    exit();
}
?>