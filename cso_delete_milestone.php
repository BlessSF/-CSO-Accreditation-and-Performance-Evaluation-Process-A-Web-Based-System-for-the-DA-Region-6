<?php
// cso_delete_milestone.php
include('include/db_connect.php');
session_start();

if (isset($_GET['milestone_id']) && !empty($_GET['milestone_id'])) {
    $milestone_id = intval($_GET['milestone_id']);
    
    // Get project_id and file_path before deletion
    $query = "SELECT project_id, file_path FROM milestones WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $milestone_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $milestone_data = $result->fetch_assoc();
        $project_id = $milestone_data['project_id'];
        $file_path = $milestone_data['file_path'];
        
        // Delete milestone_tasks associations
        $del_tasks_sql = "DELETE FROM milestone_tasks WHERE milestone_id = ?";
        $del_tasks_stmt = $conn->prepare($del_tasks_sql);
        $del_tasks_stmt->bind_param("i", $milestone_id);
        $del_tasks_stmt->execute();
        $del_tasks_stmt->close();
        
        // Delete milestone
        $del_sql = "DELETE FROM milestones WHERE id = ?";
        $del_stmt = $conn->prepare($del_sql);
        $del_stmt->bind_param("i", $milestone_id);
        
        if ($del_stmt->execute()) {
            // Delete associated file if it exists
            if (!empty($file_path) && file_exists($file_path)) {
                unlink($file_path);
            }
            
            $_SESSION['message'] = "Milestone deleted successfully!";
        } else {
            $_SESSION['message'] = "Error deleting milestone: " . $del_stmt->error;
        }
        
        $del_stmt->close();
    } else {
        $_SESSION['message'] = "Milestone not found!";
        $project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
    }
    
    $stmt->close();
    
    if ($project_id > 0) {
        header("Location: cso_view_project.php?id=" . $project_id);
    } else {
        header("Location: cso_projects_list.php");
    }
    exit();
} else {
    $_SESSION['message'] = "Invalid milestone ID!";
    header("Location: cso_projects_list.php");
    exit();
}
?>