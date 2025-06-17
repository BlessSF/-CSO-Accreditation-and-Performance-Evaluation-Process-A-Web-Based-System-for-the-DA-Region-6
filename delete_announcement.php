<?php
include('include/db_connect.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];

    // Delete announcement from the database
    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "Announcement deleted successfully";
    } else {
        echo "Error deleting announcement";
    }
    $stmt->close();
}
$conn->close();
?>
