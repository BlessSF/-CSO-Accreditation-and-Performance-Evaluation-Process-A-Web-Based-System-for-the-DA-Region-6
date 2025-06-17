<?php
include('include/db_connect.php');

if (isset($_POST['project_id'], $_POST['checklist'])) {
    $project_id = $_POST['project_id'];
    $checklist = $_POST['checklist'];

    foreach ($checklist as $item) {
        $label = $item['label'];
        $done = $item['done'] ? 1 : 0;

        $sql = "UPDATE project_checklist SET done = ? WHERE project_id = ? AND label = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $done, $project_id, $label);
        $stmt->execute();
    }

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
}
?>