<?php
include('include/db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $proposalId = $_POST['id'];
    $csoStatus = $_POST['cso_status'];

    // First, update the cso_status
    $sql = "UPDATE proposal SET cso_status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $csoStatus, $proposalId);

    if ($stmt->execute()) {
        // If csoStatus is "Denied", automatically set proposal status and funding_status to "Denied"
        if ($csoStatus === 'Denied') {
            $sql2 = "UPDATE proposal SET status = 'Denied', funding_status = 'Denied' WHERE id = ?";
            $stmt2 = $conn->prepare($sql2);
            $stmt2->bind_param("i", $proposalId);
            $stmt2->execute();
            $stmt2->close();
        }

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }

    $stmt->close();
    $conn->close();
}
?>
