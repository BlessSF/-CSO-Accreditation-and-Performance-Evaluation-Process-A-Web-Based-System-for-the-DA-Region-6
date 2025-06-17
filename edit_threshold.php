<?php
include ('include/db_connect.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'edit_threshold') {
        $roi_threshold = $_POST['roi_threshold'];
        $liability_threshold = $_POST['liability_threshold'];
        $solvency_threshold = $_POST['solvency_threshold'];

        $sql_update = "UPDATE thresholds SET roi_threshold = ?, liability_threshold = ?, solvency_threshold = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ddd", $roi_threshold, $liability_threshold, $solvency_threshold);

        if ($stmt_update->execute()) {
            echo 'success';
        } else {
            echo 'error';
        }

        $stmt_update->close();
        exit; 
    }
}
?>
