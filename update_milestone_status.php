<?php
// This file can be run as a cron job or triggered after task updates
// update_milestone_status.php
include('include/db_connect.php');

// Function to update milestone status based on associated tasks
function updateMilestoneStatuses($conn) {
    // Get all milestones with their associated tasks
    $sql = "SELECT m.id, m.status, m.project_id FROM milestones m WHERE m.status != 'Achieved'";
    $result = $conn->query($sql);
    
    while ($milestone = $result->fetch_assoc()) {
        $milestone_id = $milestone['id'];
        
        // Get all tasks associated with this milestone
        $task_sql = "SELECT t.id, t.status FROM tasks t JOIN milestone_tasks mt ON t.id = mt.task_id WHERE mt.milestone_id = ?";
        $task_stmt = $conn->prepare($task_sql);
        $task_stmt->bind_param("i", $milestone_id);
        $task_stmt->execute();
        $task_result = $task_stmt->get_result();
        
        $total_tasks = 0;
        $completed_tasks = 0;
        $in_progress_tasks = 0;
        
        while ($task = $task_result->fetch_assoc()) {
            $total_tasks++;
            
            if ($task['status'] == 'Completed') {
                $completed_tasks++;
            } elseif ($task['status'] == 'In Progress') {
                $in_progress_tasks++;
            }
        }
        
        // Determine new milestone status
        $new_status = 'Not Started';
        
        if ($total_tasks > 0) {
            if ($completed_tasks == $total_tasks) {
                $new_status = 'Achieved';
            } elseif ($completed_tasks > 0 || $in_progress_tasks > 0) {
                $new_status = 'In Progress';
                
                // Calculate completion percentage
                $completion_percentage = ($completed_tasks / $total_tasks) * 100;
                
                // If more than 75% complete, mark as "Nearly Complete"
                if ($completion_percentage >= 75) {
                    $new_status = 'Nearly Complete';
                }
            }
        }
        
        // Update milestone status if changed
        if ($new_status != $milestone['status']) {
            $update_sql = "UPDATE milestones SET status = ?, last_updated = NOW() WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $new_status, $milestone_id);
            $update_stmt->execute();
            
            // Log the status change
            logMilestoneStatusChange($conn, $milestone_id, $milestone['status'], $new_status, $milestone['project_id']);
        }
        
        $task_stmt->close();
    }
}

// Function to log milestone status changes
function logMilestoneStatusChange($conn, $milestone_id, $old_status, $new_status, $project_id) {
    $log_sql = "INSERT INTO activity_log (entity_type, entity_id, action, old_value, new_value, created_at, project_id) 
                VALUES ('milestone', ?, 'status_change', ?, ?, NOW(), ?)";
    $log_stmt = $conn->prepare($log_sql);
    $log_stmt->bind_param("issi", $milestone_id, $old_status, $new_status, $project_id);
    $log_stmt->execute();
    $log_stmt->close();
}

// Execute the update function
updateMilestoneStatuses($conn);

// Close the database connection
$conn->close();

echo "Milestone statuses updated successfully.";
?>