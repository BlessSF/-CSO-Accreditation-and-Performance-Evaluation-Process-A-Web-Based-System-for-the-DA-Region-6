I understand you want to restructure the milestone table to focus on tracking task completion under each milestone. Instead of showing target and actual dates, you want to show the number of tasks associated with each milestone, which helps determine when the milestone is considered "achieved" (when all tasks are completed).
Let me modify the existing code to implement this:
Milestones with Task Count IntegrationCode 
This revised code implements your requirements with these key changes:

Modified Milestone Table:

Replaced "Target Date" and "Actual Date" columns with a single "Tasks Completion" column
Added a progress bar and count (x/y) to visualize task completion status
Kept ID, Title, Status, and Action columns as requested


Added PHP Helper Functions:

getTotalTasksForMilestone(): Counts all tasks under a milestone
getCompletedTasksForMilestone(): Counts completed tasks under a milestone
updateMilestoneStatus(): Automatically updates milestone status based on task completion (call this when tasks are updated)


Updated View Modal:

Added task completion statistics and progress bar
Kept other important milestone details


Automatic Status Updates:

A milestone will be "Achieved" when all tasks under it are completed
"In Progress" when some tasks are completed
"Pending" when no tasks are completed



For this to work fully, you'll need:

A database table relationship between tasks and milestones
To call the updateMilestoneStatus() function whenever a task status changes
To ensure the getTotalTasksForMilestone() and getCompletedTasksForMilestone() functions can properly query your database

Would you like me to explain any part of this implementation in more detail?


<!-- Milestones Table Card -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Project Milestones</span>
        <button class="btn btn-new-task btn-sm" data-bs-toggle="modal" data-bs-target="#addMilestoneModal">
            + New Milestone
        </button>
    </div>

    <div class="card-body">
        <table id="milestonesTable" class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Tasks Completion</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($milestones_array as $milestone): ?>
                    <?php
                        $milestone_status = strtolower(trim($milestone['status']));
                        $milestone_badge_class = 'badge-info';
                        if ($milestone_status == 'achieved') {
                            $milestone_badge_class = 'badge-success';
                        } elseif ($milestone_status == 'in progress') {
                            $milestone_badge_class = 'badge-warning';
                        }
                        
                        // Get task count and completion data for this milestone
                        $total_tasks = getTotalTasksForMilestone($milestone['id']);
                        $completed_tasks = getCompletedTasksForMilestone($milestone['id']);
                    ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($milestone['id']); ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($milestone['title']); ?>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="progress flex-grow-1 me-2" style="height: 10px;">
                                    <?php 
                                        $completion_percentage = ($total_tasks > 0) ? ($completed_tasks / $total_tasks) * 100 : 0;
                                    ?>
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $completion_percentage; ?>%;" 
                                        aria-valuenow="<?php echo $completion_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <span><?php echo $completed_tasks; ?>/<?php echo $total_tasks; ?></span>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge <?php echo $milestone_badge_class; ?>">
                                <?php echo ucfirst($milestone_status); ?>
                            </span>
                        </td>
                        <td>
                            <!-- View Milestone Button -->
                            <button class="btn btn-action view-milestone-btn" 
                                data-id="<?= htmlspecialchars($milestone['id']); ?>" 
                                data-title="<?= htmlspecialchars($milestone['title']); ?>" 
                                data-description="<?= htmlspecialchars($milestone['description']); ?>" 
                                data-target-date="<?= htmlspecialchars($milestone['target_date']); ?>" 
                                data-task-count="<?= $total_tasks; ?>"
                                data-completed-tasks="<?= $completed_tasks; ?>"
                                data-comments="<?= htmlspecialchars($milestone['comments']); ?>" 
                                data-status="<?= htmlspecialchars($milestone['status']); ?>"
                                data-file="<?= htmlspecialchars($milestone['file_path'] ?? ''); ?>"
                            >
                                <i class="fas fa-eye"></i>
                            </button>

                            <button class="btn btn-action edit-milestone-btn" 
                                data-id="<?= htmlspecialchars($milestone['id']); ?>" 
                                data-title="<?= htmlspecialchars($milestone['title']); ?>" 
                                data-description="<?= htmlspecialchars($milestone['description']); ?>" 
                                data-target-date="<?= htmlspecialchars($milestone['target_date']); ?>" 
                                data-comments="<?= htmlspecialchars($milestone['comments']); ?>"
                                data-status="<?= htmlspecialchars($milestone['status']); ?>"
                                data-file="<?= htmlspecialchars($milestone['file_path'] ?? ''); ?>"
                            >
                                <i class="fas fa-edit"></i>
                            </button>
                            
                            <!-- Delete Milestone Button -->
                            <a href="cso_delete_milestone.php?milestone_id=<?= htmlspecialchars($milestone['id']); ?>" 
                                class="btn btn-delete" 
                                title="Delete" 
                                onclick="return confirm('Are you sure you want to delete this milestone?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- View Milestone Modal (Updated) -->
<div class="modal fade" id="viewMilestoneModal" tabindex="-1" aria-labelledby="viewMilestoneModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewMilestoneModalLabel">View Milestone</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-3">
                    <label>Title</label>
                    <input type="text" id="viewMilestoneTitle" class="form-control" readonly>
                </div>
                <div class="form-group mb-3">
                    <label>Description</label>
                    <textarea id="viewMilestoneDescription" class="form-control" rows="3" readonly></textarea>
                </div>

                <div class="form-group mb-3">
                    <div class="row">
                        <div class="col-md-6">
                            <label>Target Date</label>
                            <input type="text" id="viewMilestoneTargetDate" class="form-control" readonly>
                        </div>
                        <div class="col-md-6">
                            <label>Task Completion</label>
                            <div class="mt-2">
                                <div class="progress mb-2" style="height: 15px;">
                                    <div class="progress-bar" id="viewMilestoneProgress" role="progressbar" style="width: 0%;" 
                                        aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <span id="viewMilestoneTaskCompletion"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label>Comments</label>
                    <textarea id="viewMilestoneComments" class="form-control" rows="3" readonly></textarea>
                </div>
                <div class="form-group mb-3">
                    <label>Status</label>
                    <input type="text" id="viewMilestoneStatus" class="form-control" readonly>
                </div>
                <!-- Show the file link if any -->
                <div class="form-group mb-3">
                    <label>Supporting Document</label>
                    <div id="viewMilestoneFileLink">No file uploaded</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add the necessary JavaScript for handling the task counts in the view modal -->
<script>
    // Update the view milestone modal to show task completion stats
    document.querySelectorAll('.view-milestone-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Existing code for populating modal fields...
            
            // Set task completion data
            const totalTasks = this.getAttribute('data-task-count');
            const completedTasks = this.getAttribute('data-completed-tasks');
            const completionPercentage = (totalTasks > 0) ? (completedTasks / totalTasks) * 100 : 0;
            
            document.getElementById('viewMilestoneTaskCompletion').textContent = 
                `${completedTasks}/${totalTasks} tasks completed`;
            
            const progressBar = document.getElementById('viewMilestoneProgress');
            progressBar.style.width = `${completionPercentage}%`;
            progressBar.setAttribute('aria-valuenow', completionPercentage);
            
            // Open the modal
            const viewModal = new bootstrap.Modal(document.getElementById('viewMilestoneModal'));
            viewModal.show();
        });
    });
</script>

<?php
// Add these helper functions to your PHP file

/**
 * Get the total number of tasks associated with a milestone
 * @param int $milestone_id The milestone ID
 * @return int The total number of tasks
 */
function getTotalTasksForMilestone($milestone_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM tasks WHERE milestone_id = ?");
    $stmt->bind_param("i", $milestone_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_row()[0];
    return $count;
}

/**
 * Get the number of completed tasks for a milestone
 * @param int $milestone_id The milestone ID
 * @return int The number of completed tasks
 */
function getCompletedTasksForMilestone($milestone_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM tasks WHERE milestone_id = ? AND status = 'Completed'");
    $stmt->bind_param("i", $milestone_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_row()[0];
    return $count;
}

/**
 * Auto-update milestone status based on task completion
 * This function should be called whenever a task status is updated
 * @param int $milestone_id The milestone ID to check
 */
function updateMilestoneStatus($milestone_id) {
    global $conn;
    
    // Get counts
    $total = getTotalTasksForMilestone($milestone_id);
    $completed = getCompletedTasksForMilestone($milestone_id);
    
    // Determine new status
    $new_status = 'Pending';
    
    if ($total > 0) {
        if ($completed == $total) {
            $new_status = 'Achieved';
        } elseif ($completed > 0) {
            $new_status = 'In Progress';
        }
    }
    
    // Update milestone status
    $stmt = $conn->prepare("UPDATE milestones SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $milestone_id);
    $stmt->execute();
}
?>