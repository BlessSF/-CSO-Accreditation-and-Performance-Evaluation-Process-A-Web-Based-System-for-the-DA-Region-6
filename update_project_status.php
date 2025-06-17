<?php
include('include/db_connect.php');

if (isset($_POST['project_id'], $_POST['status'])) {
    $projectId = intval($_POST['project_id']); 
    $status = htmlspecialchars(trim($_POST['status'])); 

    // Update both the status and status_updated_at fields
    $sql = "UPDATE projects SET status = ?, status_updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("si", $status, $projectId);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to execute statement: ' . $stmt->error]);
        }

        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement: ' . $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
