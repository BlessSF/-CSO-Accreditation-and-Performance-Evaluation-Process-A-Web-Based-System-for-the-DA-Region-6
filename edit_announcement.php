<?php
include('include/db_connect.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $ann_content = $_POST['ann_content'];

    // Update announcement in the database
    $stmt = $conn->prepare("UPDATE announcements SET title = ?, ann_content = ? WHERE id = ?");
    $stmt->bind_param("ssi", $title, $ann_content, $id);

    if ($stmt->execute()) {
        header("Location: admin_announcements.php?message=Announcement updated successfully");
    } else {
        header("Location: admin_announcements.php?message=Error updating announcement");
    }
    $stmt->close();
}
$conn->close();
?>
