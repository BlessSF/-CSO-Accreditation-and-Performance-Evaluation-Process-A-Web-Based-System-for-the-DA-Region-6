<?php
include('include/db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;

    if (!$id) {
        echo 'Invalid input data';
        exit;
    }

    try {
        // Prepare and execute the delete statement
        $stmt = $conn->prepare("DELETE FROM cso_chairperson WHERE id = ?");
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            echo 'success';
        } else {
            echo 'Error: Could not delete the account.';
        }

        $stmt->close();
    } catch (Exception $e) {
        error_log('Error: ' . $e->getMessage());
        echo 'Error: Unable to process request.';
    }

    $conn->close();
}
?>
