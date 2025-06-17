<?php
include('include/db_connect.php');

if (isset($_POST['id']) && isset($_POST['status'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];

    $sql_update = "UPDATE proposal SET status = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("si", $status, $id);

    if ($stmt_update->execute()) {
        echo 'success';
    } else {
        echo 'error';
    }

    $stmt_update->close();
} else {
    echo 'error';
}

$conn->close();
?>
