<?php
// admin_post_projects.php
// This script creates a new project from an approved proposal.

include('include/db_connect.php');
session_start();

// Enable error reporting for debugging (remove in production)
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['proposal_id'])) {
    echo "Invalid request.";
    exit();
}

$proposal_id = intval($_POST['proposal_id']);

// Fetch proposal details from the proposals table
// Adjust the field list as needed. We require that the proposal is approved.
$sql_fetch_proposal = "
    SELECT 
        id,
        title,
        content AS project_desc,
        objectives,
        budget,
        location,
        team,
        start_date,
        end_date,
        file_path,
        remarks,
        status,
        funding_status
    FROM proposal
    WHERE id = ? 
      AND status = 'Approved'
      AND funding_status = 'Approved'
";

if ($stmt = $conn->prepare($sql_fetch_proposal)) {
    $stmt->bind_param("i", $proposal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $proposal = $result->fetch_assoc();
    } else {
        echo "Error: Proposal not found or not approved.";
        exit();
    }
    $stmt->close();
} else {
    echo "Error preparing proposal fetch: " . $conn->error;
    exit();
}

// Calculate duration (in days) from start_date to end_date
$start_date = $proposal['start_date'];
$end_date   = $proposal['end_date'];
$duration   = 0;
if ($start_date && $end_date && strtotime($end_date) > strtotime($start_date)) {
    $duration = ceil((strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24));
} else {
    echo "Error: Invalid dates in proposal.";
    exit();
}

// Decide on a file_path for the project record. Here we simply use the proposal's file_path,
// but you could also set a default if needed.
$file_path = $proposal['file_path'];

// Set default status for new project
$status = "Assigned";

// Prepare the INSERT statement for the projects table.
// Adjust the field list to match your projects table.
// In this example, we insert: title, project_desc, budget, start_date, end_date, duration, file_path, status, created_at, and objectives.
$sql_insert_project = "
    INSERT INTO projects (
        title, project_desc, budget, start_date, end_date, duration, file_path, status, created_at, objectives, remarks, location, team
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?
    )
";

if ($stmt = $conn->prepare($sql_insert_project)) {
    // Bind parameters:
    // s = string, d = double, i = integer
    $stmt->bind_param(
        "ssdssissssss",
        $proposal['title'],
        $proposal['project_desc'],
        $proposal['budget'],
        $proposal['start_date'],
        $proposal['end_date'],
        $duration,
        $file_path,
        $status,
        $proposal['objectives'],
        $proposal['remarks'],
        $proposal['location'],
        $proposal['team']
    );

    if ($stmt->execute()) {
        $project_id = $stmt->insert_id;
        // (Optional) If you want to mark that this project came from a proposal,
        // you can update the project record with the proposal_id.
        // For example, if your projects table has a column `proposal_id`:
        // $sql_update = "UPDATE projects SET proposal_id = ? WHERE id = ?";
        // $update_stmt = $conn->prepare($sql_update);
        // $update_stmt->bind_param("ii", $proposal_id, $project_id);
        // $update_stmt->execute();
        // $update_stmt->close();

        // (Optional) You might also want to automatically assign CSO(s)
        // from the proposal's representative. If needed, you can query the
        // representative's associated CSO(s) and insert into project_cso junction table.
        // For now, we assume that the admin will assign CSOs later, or you can modify as needed.

        echo "success";
    } else {
        echo "Error inserting project: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "Error preparing project insert: " . $conn->error;
}

$conn->close();
exit();
?>
