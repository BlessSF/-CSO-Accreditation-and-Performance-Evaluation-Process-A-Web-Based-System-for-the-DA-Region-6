<?php
include ('include/db_connect.php');

// Check if cso_id is set
if (isset($_GET['cso_id']) && !empty($_GET['cso_id'])) {
    $cso_id = mysqli_real_escape_string($conn, $_GET['cso_id']);
    
    // Fetch projects based on CSO ID
    $sql = "SELECT p.*, cc.cso_name 
            FROM projects p 
            JOIN cso_chairperson cc ON p.cso_id = cc.id 
            WHERE p.cso_id = '$cso_id' 
            ORDER BY p.created_at DESC";
} else {
    // If no CSO ID is provided, fetch all projects
    $sql = "SELECT p.*, cc.cso_name 
            FROM projects p 
            JOIN cso_chairperson cc ON p.cso_id = cc.id 
            ORDER BY p.created_at DESC";
}

$result = mysqli_query($conn, $sql);

$projects = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Format budget as currency
        $row['budget'] = number_format($row['budget'], 2);
        // Format date
        $row['created_at'] = date('Y-m-d', strtotime($row['created_at']));
        // Handle status display
        $status_color = '';
        switch ($row['status']) {
            case 'Active':
                $status_color = 'success';
                break;
            case 'Completed':
                $status_color = 'primary';
                break;
            case 'On Hold':
                $status_color = 'warning';
                break;
            case 'Cancelled':
                $status_color = 'danger';
                break;
            default:
                $status_color = 'secondary';
        }
        $row['status'] = '<span class="badge badge-' . $status_color . '">' . htmlspecialchars($row['status']) . '</span>';
        
        // Add to projects array
        $projects[] = $row;
    }
}

// Return projects as JSON
header('Content-Type: application/json');
echo json_encode($projects);
?>
