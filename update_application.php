<?php
include('include/db_connect.php'); 

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    
    if (isset($_POST['status'])) {
        $status = $_POST['status'];
        $sql_update = "UPDATE renewal_application SET status = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $status, $id);

        if ($stmt_update->execute()) {
            echo 'success';
        } else {
            echo 'Error updating status: ' . $stmt_update->error;
        }

        $stmt_update->close();
    }

    if (isset($_POST['remarks'])) {
        $remarks = $_POST['remarks'];
        $sql_update = "UPDATE renewal_application SET remarks = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $remarks, $id);

        if ($stmt_update->execute()) {
            echo 'success';
        } else {
            echo 'Error updating remarks: ' . $stmt_update->error;
        }

        $stmt_update->close();
    }

    if (isset($_POST['checklist'])) {
        $checklist = $_POST['checklist'];
        $sql_update = "UPDATE renewal_application SET checklist = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $checklist, $id);

        if ($stmt_update->execute()) {
            echo 'success';
        } else {
            echo 'Error updating checklist: ' . $stmt_update->error;
        }

        $stmt_update->close();
    }

    if (isset($_POST['hardcopy_status'])) {
        $hardcopy_status = $_POST['hardcopy_status'];
        $sql_update = "UPDATE renewal_application SET hardcopy_status = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $hardcopy_status, $id);

        if ($stmt_update->execute()) {
            echo 'success';
        } else {
            echo 'Error updating status: ' . $stmt_update->error;
        }

        $stmt_update->close();
    }
}

$conn->close();
?>
