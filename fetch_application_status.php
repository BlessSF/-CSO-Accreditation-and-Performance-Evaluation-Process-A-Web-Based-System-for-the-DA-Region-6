<?php
session_start();
include('include/db_connect.php');

$user_id = $_SESSION['user_id'];

$sql = "SELECT * FROM accreditation_application WHERE cso_representative_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$applications = [];
while ($row = $result->fetch_assoc()) {
    $applications[] = $row;
}

echo json_encode(['success' => true, 'applications' => $applications]);
?>
