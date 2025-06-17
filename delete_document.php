<?php
include('include/db_connect.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $document_id = $_POST['id'];

    // Fetch file path from database
    $sql_fetch = "SELECT file_path FROM files WHERE id = ?";
    $stmt_fetch = $conn->prepare($sql_fetch);
    $stmt_fetch->bind_param("i", $document_id);
    $stmt_fetch->execute();
    $result_fetch = $stmt_fetch->get_result();
    $file = $result_fetch->fetch_assoc();

    if ($file) {
        $file_path = $file['file_path'];

        // Delete file from server
        if (unlink($file_path)) {
            // Delete record from database
            $sql_delete = "DELETE FROM files WHERE id = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param("i", $document_id);

            if ($stmt_delete->execute()) {
                echo 'success';
            } else {
                echo 'error';
            }

            $stmt_delete->close();
        } else {
            echo 'error';
        }
    } else {
        echo 'error';
    }

    $stmt_fetch->close();
} else {
    echo 'error';
}

$conn->close();
?>
