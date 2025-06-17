<?php
include('include/db_connect.php');
session_start();

// 1) Fetch project ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "Invalid Project ID.";
    header("Location: user_project.php");
    exit();
}
$project_id = intval($_GET['id']);

// 2) Fetch the project record
$sql_fetch_project = "SELECT * FROM projects WHERE id = ?";
$stmt_project = $conn->prepare($sql_fetch_project);
$stmt_project->bind_param("i", $project_id);
$stmt_project->execute();
$result_project = $stmt_project->get_result();

if ($result_project && $result_project->num_rows > 0) {
    $project = $result_project->fetch_assoc();
} else {
    $_SESSION['message'] = "Project not found.";
    header("Location: user_project.php");
    exit();
}
$stmt_project->close();

// 3) Fetch tasks
$sql_fetch_tasks = "SELECT * FROM tasks WHERE project_id = ?";
$stmt_tasks = $conn->prepare($sql_fetch_tasks);
$stmt_tasks->bind_param("i", $project_id);
$stmt_tasks->execute();
$tasks_result = $stmt_tasks->get_result();

if (!$tasks_result) {
    die("Error in SQL Query (Tasks Fetch): " . $conn->error);
}
$stmt_tasks->close();

// We will parse tasks AFTER we've read them, but first prepare arrays/counters
$tasks_array    = [];
$tasks_done     = 0;
$tasks_total    = 0;
$budget_used    = 0.00;

// 4) Loop over tasks to compute $sumTasksDuration, $budget_used, etc.
$sumTasksDuration = 0;  // We'll sum each task's duration
mysqli_data_seek($tasks_result, 0);  // Move pointer to first row
while ($row = mysqli_fetch_assoc($tasks_result)) {
    $tasks_array[] = $row;
    $tasks_total++;

    // If task is done
    if (strtolower(trim($row['status'])) === 'done') {
        $tasks_done++;
    }

    // Sum up spent
    $budget_used += floatval($row['spent']);
    
    // Sum up durations
    $sumTasksDuration += max(0, (int)$row['duration']);
}

// 5) Now compute $progress, $budget_left
$progress = 0;
if ($tasks_total > 0) {
    $progress = round(($tasks_done / $tasks_total) * 100, 2);
}
$budget_left = floatval($project['budget']) - $budget_used;

// 6) Calculate totalProjectDays from start/end date
$projectStart = strtotime($project['start_date']);
$projectEnd   = strtotime($project['end_date']);
$totalProjectDays = 0;
if ($projectEnd > $projectStart) {
    $totalProjectDays = floor(($projectEnd - $projectStart) / 86400);
}

// 7) Compute overshoot
$days_over   = 0;
$budget_over = 0.00;

if ($sumTasksDuration > $totalProjectDays) {
    $days_over = $sumTasksDuration - $totalProjectDays; 
}

if ($budget_left < 0) {
    $budget_over = abs($budget_left);
}

// 8) Update overshoot columns in DB
$updateOvershootSql = "
    UPDATE projects
    SET days_over = ?,
        budget_over = ?
    WHERE id = ?
";
$stmt_overshoot = $conn->prepare($updateOvershootSql);
if (!$stmt_overshoot) {
    die("Error preparing statement for overshoot update: " . $conn->error);
}
$stmt_overshoot->bind_param("ddi", $days_over, $budget_over, $project_id);
if (!$stmt_overshoot->execute()) {
    die("Error executing overshoot update: " . $stmt_overshoot->error);
}
$stmt_overshoot->close();

// Fetch task dependencies
$task_dependencies = [];
$sql_fetch_dependencies = "SELECT * FROM task_dependencies WHERE project_id = ?";
$stmt_dependencies = $conn->prepare($sql_fetch_dependencies);
$stmt_dependencies->bind_param("i", $project_id);
$stmt_dependencies->execute();
$dependencies_result = $stmt_dependencies->get_result();

if ($dependencies_result) {
    while ($row = mysqli_fetch_assoc($dependencies_result)) {
        $task_dependencies[$row['task_id']][] = $row['predecessor_id'];
    }
}
$stmt_dependencies->close();

// Fetch milestones
$sql_fetch_milestones = "SELECT * FROM milestones WHERE project_id = ?";
$stmt_milestones = $conn->prepare($sql_fetch_milestones);
$stmt_milestones->bind_param("i", $project_id);
$stmt_milestones->execute();
$milestones_result = $stmt_milestones->get_result();

if (!$milestones_result) {
    die("Error in SQL Query (Milestones Fetch): " . $conn->error);
}
$stmt_milestones->close();

// Initialize array for milestones
$milestones_array = [];
$milestone_tasks = [];

// Loop over milestones
mysqli_data_seek($milestones_result, 0);  // Move pointer to first row
while ($milestone = mysqli_fetch_assoc($milestones_result)) {
    $milestone_id = $milestone['id'];
    $milestones_array[$milestone_id] = $milestone;
    
    // Get all tasks associated with this milestone
    $sql_fetch_milestone_tasks = "SELECT t.* FROM tasks t 
                                 JOIN milestone_tasks mt ON t.id = mt.task_id 
                                 WHERE mt.milestone_id = ?";
    $stmt_milestone_tasks = $conn->prepare($sql_fetch_milestone_tasks);
    $stmt_milestone_tasks->bind_param("i", $milestone_id);
    $stmt_milestone_tasks->execute();
    $milestone_tasks_result = $stmt_milestone_tasks->get_result();
    
    $milestone_tasks[$milestone_id] = [];
    while ($task = mysqli_fetch_assoc($milestone_tasks_result)) {
        $milestone_tasks[$milestone_id][] = $task;
    }
    $stmt_milestone_tasks->close();
}

$milestones_total = count($milestones_array);
$milestones_achieved = 0;

// Check milestone status and update based on task completion
foreach ($milestones_array as $milestone_id => $milestone) {
    // If milestone is already achieved, count it
    if (strtolower(trim($milestone['status'])) == 'achieved') {
        $milestones_achieved++;
        continue;
    }
    
    // Calculate based on associated tasks
    $tasks_for_milestone = $milestone_tasks[$milestone_id];
    $milestone_tasks_total = count($tasks_for_milestone);
    $milestone_tasks_done = 0;
    
    if ($milestone_tasks_total > 0) {
        foreach ($tasks_for_milestone as $task) {
            if (strtolower(trim($task['status'])) === 'done') {
                $milestone_tasks_done++;
            }
        }
        
        // If all tasks are done, update the milestone status
        if ($milestone_tasks_done == $milestone_tasks_total) {
            // Update milestone status to 'Achieved' in database
            $update_milestone_sql = "UPDATE milestones SET status = 'Achieved' WHERE id = ?";
            $stmt_update = $conn->prepare($update_milestone_sql);
            $stmt_update->bind_param("i", $milestone_id);
            $stmt_update->execute();
            $stmt_update->close();
            
            // Count this as achieved for our calculation
            $milestones_achieved++;
        }
    }
}

// Calculate percentage
$milestone_progress = ($milestones_total > 0) ? round(($milestones_achieved / $milestones_total) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
    <title>Department of Agriculture Region 6 | Project Details</title>
    <!-- CSS Links -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link rel="icon" type="image/x-icon" href="img/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" 
          rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/vis-network@9.1.2/dist/dist/vis-network.min.css" rel="stylesheet">
</head>
<body>
    <style>
        /* Your existing CSS styles */
        .container-fluid { 
            padding: 20px; 
        }
        .nav-tabs .nav-link {
            background-color: lightgray; 
            color: #0A593A; 
            padding: 12px 16px;
            font-size: 14px; 
            transition: all 0.3s; 
        }
        .nav-tabs .nav-link.active {
            background-color: #0A593A;
            color: white;
            padding: 14px 18px; 
            font-size: 16px; 
            margin-bottom: -1px; 
            border-bottom: 2px solid white;
        }
        .nav-tabs .nav-link.inactive {
            background-color: gray; 
            color: white;
        }
        .success {
            color: blue;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .status {
            color: red;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .error-message {
            color: red;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .btn-custom {
            background-color: rgb(1, 82, 51);
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            transition: background-color 0.3s, color 0.3s;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
            width: 100%;
        }

        .btn-custom:hover {
            color: white;
            text-decoration: underline;
        }

        h5,
        label {
            display: block; 
            font-weight: bold; 
            margin-bottom: 5px;
            color: #0A593A;
        }

        .dataTables_wrapper {
            width: 100%;
            overflow-x: auto; /* Allow horizontal scrolling */
        }
        
        .yellow-line {
            background-color: rgb(253, 199, 5);
            height: 7px;
            width: 100%;
        }

        h2, h3 { 
            color: #0A593A; 
            font-weight: bold; 
        }
        .full-height-container {
            height: 90vh;
            display: flex;
            flex-direction: column;
        }
        .btn-action {
            border: 1px solid #0A593A;
            color: #0A593A;
            background-color: transparent;
            font-weight: bold;
            padding: 6px 12px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        .btn-action:hover {
            background-color: #0A593A;
            color: white;
        }

        .btn-new-task {
            background-color: #0A593A; /* Green background */
            color: white; /* White text */
            font-weight: bold;
            border: 1px solid white; /* Border to match the text color */
            padding: 6px 12px; /* Padding for better spacing */
            border-radius: 5px; /* Rounded corners */
            transition: all 0.3s ease; /* Smooth transition on hover */
        }

        .btn-new-task:hover {
            background-color: white; /* White background on hover */
            color: #0A593A; /* Green text on hover */
            border: 1px solid #0A593A;
        }

        .select2-results__option--selected {
            background-color: #0A593A !important;
            color: white !important;
        }

        .select2-results__option--highlighted {
            background-color: rgba(10, 89, 58, 0.8) !important;
            color: white !important;
        }
        
        .select2-selection__choice {
            background-color: #0A593A !important;
            color: white !important;
            border: none !important;
        }

        .select2-container {
            width: 100% !important;
        }

        .badge {
            /* Removed background-color: transparent; to allow Bootstrap's default */
            border: none; 
            font-weight: bold; /* Make badge text bold */
            padding: 0.5em 0.75em; /* Add padding for better appearance */
            font-size: 100%; /* Ensure consistent font size */
        }

        .badge-success {
            background-color: #28a745 !important; /* Green background */
            color: white !important; /* White text */
        }

        .badge-warning {
            background-color: #ffc107 !important; /* Yellow background */
            color: black !important; /* Black text for better contrast */
        }

        .badge-info {
            background-color: #17a2b8 !important; /* Blue background */
            color: white !important; /* White text */
        }
        .text-center img {
            border-radius: 50%;
            display: block;
            margin: 0 auto;
        }
        .card-header {
            background-color: #0A593A; 
            color: white; 
            font-size: 19px; 
            padding: 15px;
            border-bottom: none;
        }
        .row .col-4 img {
            border-radius: 50%;
            margin-bottom: 10px;
        }
        .row .col-4 p {
            margin: 0;
            font-size: 14px;
        }
        .mb-3, .mb-4 {
            margin-bottom: 1rem !important;
        }
        .modal-header {
            background-color: #0A593A; /* Background for modal title */
            color: white; /* White text for modal title */
        }

        /* Assign Task Button */
        .btn-assign-task {
            background-color: #0A593A; /* Same green as the card header */
            color: white; /* White text */
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px; /* Rounded corners */
            transition: all 0.3s ease; /* Smooth transition */
        }

        .btn-assign-task:hover {
            background-color: #084C2C; /* Darker shade of green for hover */
            color: white;
        }

        /* Cancel Button */
        .btn-cancel {
            background-color: #A52A2A; /* Red background */
            color: white; /* White text */
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background-color: #7B1E1E; /* Darker red for hover */
            color: white;
        }
        .progress {
            background-color: #e9ecef;
            border-radius: 5px;
            height: 20px;
            overflow: hidden;
            margin-bottom: 1rem;
        }
        .progress-bar {
            background-color: #0A593A;
            height: 100%;
            text-align: center;
            color: #fff;
            font-weight: bold;
            line-height: 20px;
        }
        .dt-nowrap { white-space: nowrap; }
    </style>

    <div id="wrapper">
        <?php include('user_include/navbar.php'); ?>
        <div class="container-fluid">
            <h2>Project Details</h2>
            <div class="yellow-line"></div>
            <br>
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-info">
                    <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
                </div>
            <?php endif; ?>

            <!-- Project Information Card -->
            <div class="card mb-4">
            <div class="card-body">
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <p><label>Submitted On:</label> 
                                <?php echo htmlspecialchars($project['date_submitted'] ?? 'N/A'); ?>
                            </p>

                            <p><label>Proposal Status:</label> 
                                <?php echo htmlspecialchars($project['proposal_status'] ?? 'N/A'); ?>
                            </p>

                            <p><label>Funding Status:</label> 
                                <?php echo htmlspecialchars($project['funding_status'] ?? 'N/A'); ?>
                            </p>

                            <p><label>Proposed Project Title:</label>
                                <?php echo htmlspecialchars($project['title']); ?>
                            </p>

                            <p><label>Project Description:</label>
                                <?php echo nl2br(htmlspecialchars($project['project_desc'])); ?>
                            </p>

                            <p><label>Project Objective(s):</label>
                                <?php echo nl2br(htmlspecialchars($project['objectives'])); ?>
                            </p>

                            <p><label>Expected Outcome(s):</label>
                                <?php echo nl2br(htmlspecialchars($project['outcomes'] ?? '')); ?>
                            </p>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <p><label>Milestones/Phases:</label>
                                <?php echo nl2br(htmlspecialchars($project['milestones'] ?? '')); ?>
                            </p>

                            <p><label>Key Stakeholder(s):</label>
                                <?php echo nl2br(htmlspecialchars($project['team'] ?? '')); ?>
                            </p>

                            <p><label>Project Location:</label>
                                <?php echo htmlspecialchars($project['location']); ?>
                            </p>

                            <p><label>Risk Assessment and Mitigation Plan:</label>
                                <?php echo nl2br(htmlspecialchars($project['risks'] ?? '')); ?>
                            </p>

                            <p><label>Budgetary Requirement:</label> 
                                PHP <?php echo number_format($project['budget'], 2); ?>
                            </p>

                            <p><label>Start Date:</label> 
                                <?php echo htmlspecialchars($project['start_date']); ?>
                            </p>

                            <p><label>End Date:</label> 
                                <?php echo htmlspecialchars($project['end_date']); ?>
                            </p>

                            <!-- PROJECT STATUS (Badge) -->
                            <p><label>Project Status:</label>
                                <?php 
                                    $statusLower = strtolower(trim($project['status']));
                                    $badgeClass  = 'badge-info';
                                    if ($statusLower === 'completed') {
                                        $badgeClass = 'badge-success';
                                    } elseif ($statusLower === 'in progress') {
                                        $badgeClass = 'badge-warning';
                                    } elseif ($statusLower === 'assigned') {
                                        $badgeClass = 'badge-primary';
                                    } elseif ($statusLower === 'completed with delay') {
                                        $badgeClass = 'badge-success';
                                    }
                                ?>
                                <span class="badge <?php echo $badgeClass; ?>">
                                    <?php echo htmlspecialchars($project['status'] ?? 'Assigned'); ?>
                                </span>
                            </p>

                            <!-- PROJECT PLAN (File Link) -->
                            <p><label>Project Plan:</label>
                                <?php if (!empty($project['file_path'])): ?>
                                    <a href="<?php echo htmlspecialchars($project['file_path']); ?>" 
                                       target="_blank" 
                                       style="text-decoration: underline;">
                                       View File
                                    </a>
                                <?php else: ?>
                                    <span>No file uploaded</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ROW: M&E + Task List side by side -->
            <div class="row">
                <!-- LEFT COLUMN: M&E (col-md-6) -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5>Milestone Achievement</h5>
                            <div class="mb-2" style="font-size: 14px;">
                                <?php echo $milestones_achieved . " of " . $milestones_total . " milestones achieved"; ?>
                            </div>
                            <div class="progress mb-4">
                                <div class="progress-bar" 
                                    role="progressbar" 
                                    style="width: <?php echo $milestone_progress; ?>%;" 
                                    aria-valuenow="<?php echo $milestone_progress; ?>" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100">
                                    <?php echo $milestone_progress; ?>%
                                </div>
                            </div>
                            <hr>
                            <!-- Task Completion -->
                            <h5>Task Completion</h5>
                            <div class="mb-2" style="font-size: 14px;">
                                <?php echo $tasks_done . " of " . $tasks_total . " tasks completed"; ?>
                            </div>
                            <div class="progress mb-4">
                                <div class="progress-bar" 
                                     role="progressbar" 
                                     style="width: <?php echo $progress; ?>%;" 
                                     aria-valuenow="<?php echo $progress; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    <?php echo $progress; ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card mb-4">
                        <div class="card-body">
                            <!-- Budget Usage -->
                            <h5>Budget Usage</h5>
                            <?php 
                                $budgetPercent = 0;
                                if ($project['budget'] > 0) {
                                    $budgetPercent = round(($budget_used / $project['budget']) * 100, 2);
                                }
                            ?>
                            <div class="d-flex justify-content-between mb-2" style="font-size: 14px;">
                                <span>
                                    Used: <?php echo number_format($budget_used, 2); ?> 
                                    of <?php echo number_format($project['budget'], 2); ?>
                                </span>
                                <?php if (($project['budget'] - $budget_used) < 0): ?>
                                    <span style="color:red; font-weight:bold;">
                                        Overspent by <?php echo number_format(abs($project['budget'] - $budget_used), 2); ?>
                                    </span>
                                <?php else: ?>
                                    <span>
                                        Remaining: <?php echo number_format(($project['budget'] - $budget_used), 2); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="progress mb-4">
                                <div class="progress-bar" 
                                     role="progressbar"
                                     style="width: <?php echo min($budgetPercent, 100); ?>%;" 
                                     aria-valuenow="<?php echo $budgetPercent; ?>" 
                                     aria-valuemin="0"
                                     aria-valuemax="100">
                                    <?php echo $budgetPercent; ?>%
                                </div>
                            </div>
                            <hr>
                            <!-- Task Duration Distribution (STACKED PROGRESS BAR) -->
                            <h5>Task Duration Distribution</h5>
                            <?php
                            // 1) totalProjectDays computed above
                            // 2) sumTasksDuration computed above
                            $daysPercent = 0;
                            if ($totalProjectDays > 0) {
                                $daysPercent = round(($sumTasksDuration / $totalProjectDays) * 100, 2);
                            }
                            ?>
                            <div class="d-flex justify-content-between mb-2" style="font-size:14px;">
                                <span>
                                    Used: <?php echo $sumTasksDuration; ?> of <?php echo $totalProjectDays; ?> day(s)
                                </span>
                                <?php if ($sumTasksDuration > $totalProjectDays): ?>
                                    <span style="color:red; font-weight:bold;">
                                        Overdue by <?php echo $sumTasksDuration - $totalProjectDays; ?> day(s)
                                    </span>
                                <?php else: ?>
                                    <span>
                                        Remaining: <?php echo $totalProjectDays - $sumTasksDuration; ?> day(s)
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php
                            if ($totalProjectDays <= 0) {
                                echo "<p style='font-size:14px;'>No valid project duration found. (Check start/end dates.)</p>";
                            } else {
                                // Build the stacked progress bar
                                $colors = ['bg-success','bg-info','bg-warning','bg-danger','bg-primary'];
                                $colorIndex = 0;

                                echo '<div class="progress" style="height: 25px;">';
                                foreach ($tasks_array as $task) {
                                    $taskID = htmlspecialchars($task['id']);
                                    $taskTitle = htmlspecialchars($task['title']);
                                    $taskDur = max(0, (int)$task['duration']);

                                    $sharePercent = 0;
                                    if ($totalProjectDays > 0) {
                                        $sharePercent = round(($taskDur / $totalProjectDays) * 100, 2);
                                        if ($sharePercent > 100) {
                                            $sharePercent = 100; // clamp
                                        }
                                    }

                                    $thisColor = $colors[$colorIndex % count($colors)];
                                    $colorIndex++;

                                    if ($sharePercent > 0) {
                                        echo '<div class="progress-bar ' . $thisColor . '"
                                                role="progressbar"
                                                style="width: ' . $sharePercent . '%;"
                                                aria-valuenow="' . $sharePercent . '"
                                                aria-valuemin="0"
                                                aria-valuemax="100"
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="top"
                                                title="' . $taskTitle . ': ' . $taskDur . ' day(s)">' .
                                            '</div>';
                                    }
                                }
                                echo '</div>'; // close progress
                            }
                            ?>                        
                        </div>
                    </div>
                    <div class="card mb-4">
                        <div class="card-header">
                            <span>Project Gantt Chart</span>
                        </div>
                        <div class="card-body">
                            <div id="gantt-chart-container" style="height: 450px; overflow: auto;"></div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: Task List (col-md-6) -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Task List</span>
                        </div>

                        <div class="card-body">
                            <table id="tasksTable" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Duration (days)</th>
                                        <th>Spent (PHP)</th>
                                        <th>Dependencies</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tasks_array as $task): ?>
                                        <?php
                                            $task_status = strtolower(trim($task['status']));
                                            $task_badge_class = 'badge-info';
                                            if ($task_status == 'done') {
                                                $task_badge_class = 'badge-success';
                                            } elseif ($task_status == 'in progress') {
                                                $task_badge_class = 'badge-warning';
                                            }
                                        ?>
                                        <tr>
                                            <td>
                                                <?php echo htmlspecialchars($task['id']); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($task['title']); ?>
                                            </td>
                                            <td>
                                                <?php echo (int)$task['duration']; ?>
                                            </td>
                                            <td>
                                                <?php echo number_format($task['spent'], 2); ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $hasPredecessors = isset($task_dependencies[$task['id']]) && !empty($task_dependencies[$task['id']]);
                                                
                                                // Check if this task is a predecessor for any other task
                                                $isSuccessor = false;
                                                foreach ($task_dependencies as $tid => $preds) {
                                                    if (in_array($task['id'], $preds)) {
                                                        $isSuccessor = true;
                                                        break;
                                                    }
                                                }
                                                
                                                echo '<div class="text-center">';
                                                if ($hasPredecessors && $isSuccessor) {
                                                    echo '<span class="badge bg-info" title="Has predecessors and successors"><i class="fas fa-exchange-alt"></i></span>';
                                                } elseif ($hasPredecessors) {
                                                    echo '<span class="badge bg-warning" title="Has predecessors"><i class="fas fa-arrow-left"></i></span>';
                                                } elseif ($isSuccessor) {
                                                    echo '<span class="badge bg-success" title="Has successors"><i class="fas fa-arrow-right"></i></span>';
                                                } else {
                                                    echo '<span class="text-muted">None</span>';
                                                }
                                                echo '</div>';
                                                ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?php echo $task_badge_class; ?>">
                                                    <?php echo ucfirst($task_status); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <!-- View Task Button -->
                                                <button class="btn btn-action view-task-btn" 
                                                    data-id="<?= htmlspecialchars($task['id']); ?>" 
                                                    data-title="<?= htmlspecialchars($task['title']); ?>" 
                                                    data-description="<?= htmlspecialchars($task['description']); ?>" 
                                                    data-duration="<?= htmlspecialchars($task['duration']); ?>" 
                                                    data-spent="<?= htmlspecialchars($task['spent']); ?>"
                                                    data-comments="<?= htmlspecialchars($task['comments']); ?>" 
                                                    data-status="<?= htmlspecialchars($task['status']); ?>"
                                                    data-file="<?= htmlspecialchars($task['file_path'] ?? ''); ?>"
                                                    data-proposed-start="<?= htmlspecialchars($task['proposed_start_date'] ?? ''); ?>"
                                                    data-proposed-end="<?= htmlspecialchars($task['proposed_end_date'] ?? ''); ?>"
                                                    data-actual-start="<?= htmlspecialchars($task['actual_start_date'] ?? ''); ?>"
                                                    data-actual-end="<?= htmlspecialchars($task['actual_end_date'] ?? ''); ?>"
                                                >
                                                    <i class="fas fa-eye"></i>
                                                </button>

                                                <button class="btn btn-action edit-task-btn" 
                                                    data-id="<?= htmlspecialchars($task['id']); ?>" 
                                                    data-title="<?= htmlspecialchars($task['title']); ?>" 
                                                    data-description="<?= htmlspecialchars($task['description']); ?>" 
                                                    data-duration="<?= htmlspecialchars($task['duration']); ?>" 
                                                    data-spent="<?= htmlspecialchars($task['spent']); ?>"
                                                    data-comments="<?= htmlspecialchars($task['comments']); ?>"
                                                    data-status="<?= htmlspecialchars($task['status']); ?>"
                                                    data-file="<?= htmlspecialchars($task['file_path'] ?? ''); ?>"
                                                    data-proposed-start="<?= htmlspecialchars($task['proposed_start_date'] ?? ''); ?>"
                                                    data-proposed-end="<?= htmlspecialchars($task['proposed_end_date'] ?? ''); ?>"
                                                    data-actual-start="<?= htmlspecialchars($task['actual_start_date'] ?? ''); ?>"
                                                    data-actual-end="<?= htmlspecialchars($task['actual_end_date'] ?? ''); ?>"
                                                >
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- View Task Modal -->
                        <div class="modal fade" id="viewTaskModal" tabindex="-1" aria-labelledby="viewTaskModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="viewTaskModalLabel">View Task</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group mb-3">
                                            <label>Title</label>
                                            <input type="text" id="viewTaskTitle" class="form-control" readonly>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label>Description</label>
                                            <textarea id="viewTaskDescription" class="form-control" rows="3" readonly></textarea>
                                        </div>

                                        <div class="form-group mb-3">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label>Proposed Start Date</label>
                                                    <input type="text" id="viewTaskProposedStartDate" class="form-control" readonly>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Proposed End Date</label>
                                                    <input type="text" id="viewTaskProposedEndDate" class="form-control" readonly>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group mb-3">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label>Actual Start Date</label>
                                                    <input type="text" id="viewTaskActualStartDate" class="form-control" readonly>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Actual End Date</label>
                                                    <input type="text" id="viewTaskActualEndDate" class="form-control" readonly>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label>Duration (days)</label>
                                            <input type="text" id="viewTaskDuration" class="form-control" readonly>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label>Budget Spent (PHP)</label>
                                            <input type="text" id="viewTaskSpent" class="form-control" readonly>
                                        </div>
                                        <!-- Predecessors -->
                                        <div class="form-group mb-3">
                                            <label>Predecessor Tasks</label>
                                            <div id="viewTaskPredecessors">None</div>
                                        </div>

                                        <!-- Successor Tasks (tasks that depend on this one) -->
                                        <div class="form-group mb-3">
                                            <label>Successor Tasks</label>
                                            <div id="viewTaskSuccessors">None</div>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label>Comments</label>
                                            <textarea id="viewTaskComments" class="form-control" rows="3" readonly></textarea>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label>Status</label>
                                            <input type="text" id="viewTaskStatus" class="form-control" readonly>
                                        </div>
                                        <!-- Show the file link if any -->
                                        <div class="form-group mb-3">
                                            <label>Supporting Document</label>
                                            <div id="viewTaskFileLink">No file uploaded</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Task Modal -->
                        <div class="modal fade" id="editTaskModal" tabindex="-1" aria-labelledby="editTaskModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <!-- Updated form to include file upload -->
                                    <form id="editTaskForm" action="user_update_task.php" method="POST" enctype="multipart/form-data">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editTaskModalLabel">Edit Task</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="task_id" id="editTaskId">

                                            <!-- Title (Optional) -->
                                            <div class="form-group mb-3">
                                                <label>Title <span style="color:red; font-weight:bold;">*</span></label>
                                                <input type="text" name="title" id="editTaskTitle" class="form-control" readonly>
                                            </div>

                                            <!-- Description (Required) -->
                                            <div class="form-group mb-3">
                                                <label>Description <span style="color:red; font-weight:bold;">*</span></label>
                                                <textarea name="description" id="editTaskDescription" class="form-control" rows="3" required readonly></textarea>
                                            </div>

                                            <div class="form-group mb-3">
                                                <div class="row">
                                                    <!-- Proposed/Actual Start Date -->
                                                    <div class="col-md-6">
                                                        <label class="font-weight-bold">Proposed Start Date <span style="color:red; font-weight:bold;">*</span></label>
                                                        <input type="date" name="proposed_start_date" id="editTaskProposedStartDate" class="form-control" required readonly> 
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="font-weight-bold">Proposed End Date <span style="color:red; font-weight:bold;">*</span></label>
                                                        <input type="date" name="proposed_end_date" id="editTaskProposedEndDate" class="form-control" required readonly>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group mb-3">
                                                <div class="row">
                                                    <!-- Proposed/Actual End Date -->
                                                    <div class="col-md-6">
                                                        <label class="font-weight-bold">Actual Start Date</label>
                                                        <input type="date" name="actual_start_date" id="editTaskActualStartDate" class="form-control">
                                                        <small class="text-muted">Leave blank if not started yet</small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="font-weight-bold">Actual End Date</label>
                                                        <input type="date" name="actual_end_date" id="editTaskActualEndDate" class="form-control">
                                                        <small class="text-muted">Leave blank if not completed yet</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Duration (days) (read-only) -->
                                            <div class="form-group mb-3">
                                                <label>Duration (days)</label>
                                                <input type="number" name="duration" id="editTaskDuration" class="form-control" readonly>
                                                <small class="text-muted">Automatically calculated from actual start and end dates</small>
                                            </div>

                                            <!-- Budget Spent (PHP) -->
                                            <div class="form-group mb-3">
                                                <label>Budget Spent (PHP)</label>
                                                <input type="number" step="0.01" name="spent" id="editTaskSpent" class="form-control">
                                            </div>

                                            <!-- Task Predecessors (Dependencies) -->
                                            <div class="form-group mb-3">
                                                <label>Predecessor Tasks</label>
                                                <select name="predecessors[]" id="editTaskPredecessors" class="form-control task-dependencies-select" multiple disabled>
                                                    <?php foreach ($tasks_array as $task): ?>
                                                        <option value="<?= htmlspecialchars($task['id']); ?>"><?= htmlspecialchars($task['title']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <small class="text-muted">Select tasks that must be completed before this task can start</small>
                                            </div>

                                            <!-- Comments -->
                                            <div class="form-group mb-3">
                                                <label>Comments</label>
                                                <textarea name="comments" id="editTaskComments" class="form-control" rows="3" readonly></textarea>
                                            </div>

                                            <!-- Supporting Document (File Upload) -->
                                            <div class="form-group mb-3">
                                                <label class="font-weight-bold">Supporting Document</label>
                                                <input type="file" name="supporting_doc" class="form-control">
                                                <small class="text-muted">Leave blank if you don't want to replace the existing file.</small>
                                            </div>

                                            <!-- Status (Required) -->
                                            <div class="form-group mb-3">
                                                <label>Status <span style="color:red; font-weight:bold;">*</span></label>
                                                <select name="status" id="editTaskStatus" class="form-control" required readonly>
                                                    <option value="Pending">Pending</option>
                                                    <option value="In Progress">In Progress</option>
                                                    <option value="Done">Done</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-assign-task">Update Task</button>
                                            <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- End Edit Task Modal -->

                    </div> 
                    <!-- Milestones Table Card -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Project Milestones</span>
                        </div>

                        <div class="card-body">
                            <table id="milestonesTable" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Target Date</th>
                                        <th>Actual Date</th>
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
                                        ?>
                                        <tr>
                                            <td>
                                                <?php echo htmlspecialchars($milestone['id']); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($milestone['title']); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($milestone['target_date']); ?>
                                            </td>
                                            <td>
                                                <?php echo !empty($milestone['actual_date']) ? htmlspecialchars($milestone['actual_date']) : '<span class="text-muted">Not reached yet</span>'; ?>
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
                                                    data-actual-date="<?= htmlspecialchars($milestone['actual_date'] ?? ''); ?>"
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
                                                    data-actual-date="<?= htmlspecialchars($milestone['actual_date'] ?? ''); ?>"
                                                    data-comments="<?= htmlspecialchars($milestone['comments']); ?>"
                                                    data-status="<?= htmlspecialchars($milestone['status']); ?>"
                                                    data-file="<?= htmlspecialchars($milestone['file_path'] ?? ''); ?>"
                                                >
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- View Milestone Modal -->
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
                                            <label>Target Date</label>
                                            <input type="text" id="viewMilestoneTargetDate" class="form-control" readonly>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label>Actual Date</label>
                                            <input type="text" id="viewMilestoneActualDate" class="form-control" readonly>
                                        </div>
                                        <!-- Associated Tasks -->
                                        <div class="form-group mb-3">
                                            <label>Associated Tasks</label>
                                            <div id="viewMilestoneAssociatedTasks">None</div>
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

                        <!-- Edit Milestone Modal -->
                        <div class="modal fade" id="editMilestoneModal" tabindex="-1" aria-labelledby="editMilestoneModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <form id="editMilestoneForm" action="user_update_milestone.php" method="POST" enctype="multipart/form-data">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editMilestoneModalLabel">Edit Milestone</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="milestone_id" id="editMilestoneId">

                                            <!-- Title -->
                                            <div class="form-group mb-3">
                                                <label>Title <span style="color:red; font-weight:bold;">*</span></label>
                                                <input type="text" name="title" id="editMilestoneTitle" class="form-control" required readonly>
                                            </div>

                                            <!-- Description (Required) -->
                                            <div class="form-group mb-3">
                                                <label>Description <span style="color:red; font-weight:bold;">*</span></label>
                                                <textarea name="description" id="editMilestoneDescription" class="form-control" rows="3" required readonly></textarea>
                                            </div>

                                            <!-- Target Date -->
                                            <div class="form-group mb-3">
                                                <label>Target Date <span style="color:red; font-weight:bold;">*</span></label>
                                                <input type="date" name="target_date" id="editMilestoneTargetDate" class="form-control" required readonly>
                                            </div>

                                            <!-- Actual Date -->
                                            <div class="form-group mb-3">
                                                <label>Actual Date</label>
                                                <input type="date" name="actual_date" id="editMilestoneActualDate" class="form-control">
                                                <small class="text-muted">Leave blank if milestone has not been achieved yet</small>
                                            </div>

                                            <!-- Associated Tasks -->
                                            <div class="form-group mb-3">
                                                <label>Associated Tasks</label>
                                                <select name="associated_tasks[]" id="editMilestoneAssociatedTasks" class="form-control milestone-tasks-select" multiple disabled>
                                                    <?php foreach ($tasks_array as $task): ?>
                                                        <option value="<?= htmlspecialchars($task['id']); ?>"><?= htmlspecialchars($task['title']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <small class="text-muted">Select tasks that must be completed for this milestone to be achieved</small>
                                            </div>

                                            <!-- Comments -->
                                            <div class="form-group mb-3">
                                                <label>Comments</label>
                                                <textarea name="comments" id="editMilestoneComments" class="form-control" rows="3" readonly></textarea>
                                            </div>

                                            <!-- Supporting Document (File Upload) -->
                                            <div class="form-group mb-3">
                                                <label>Supporting Document</label>
                                                <input type="file" name="supporting_doc" class="form-control">
                                                <small class="text-muted">Leave blank if you don't want to replace the existing file.</small>
                                            </div>

                                            <!-- Status (Required) -->
                                            <div class="form-group mb-3">
                                                <label>Status <span style="color:red; font-weight:bold;">*</span></label>
                                                <select name="status" id="editMilestoneStatus" class="form-control" required>
                                                    <option value="Pending">Pending</option>
                                                    <option value="In Progress">In Progress</option>
                                                    <option value="Achieved">Achieved</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-assign-task">Update Milestone</button>
                                            <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div> 
                </div> 
            </div> 
        </div> 
    </div> 

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vis-network@9.1.2/dist/vis-network.min.js"></script>
    <script src="js/task-management.js"></script>
    <script src="js/gantt-chart.js"></script>
    <script src="js/milestone-management.js"></script>

    <script>
    $(document).ready(function() {
        // Convert PHP arrays to JavaScript first
        const tasksData = <?= json_encode($tasks_array); ?>;
        const taskDependencies = <?= json_encode($task_dependencies); ?>;
        const taskTitles = <?= json_encode(array_column($tasks_array, 'title', 'id')); ?>;
        
        // For milestones
        const milestonesData = <?= json_encode($milestones_array); ?>;
        const milestoneTasks = <?= json_encode($milestone_tasks ?? []); ?>;
        
        // Initialize all components with the data in the correct order
        initTaskManagement(tasksData, taskDependencies, taskTitles);
        initGanttChart(tasksData, taskDependencies);
        initMilestoneManagement(tasksData, milestoneTasks, taskTitles);
    });
    </script>

</body>
</html>
