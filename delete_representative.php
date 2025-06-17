<?php
include('include/db_connect.php');

if (isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];

    $sql_delete = "DELETE FROM cso_representative WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id);

    if ($stmt_delete->execute()) {
        echo 'success';
    } else {
        echo 'error';
    }

    $stmt_delete->close();
}

$conn->close();
?>
