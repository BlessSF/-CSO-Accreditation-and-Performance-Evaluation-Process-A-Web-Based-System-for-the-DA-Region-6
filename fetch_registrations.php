<?php
// include db_connect.php or establish connection here

$sql = "SELECT COUNT(*) AS num_registrations FROM your_table_name WHERE ...";
$result = $conn->query($sql);

if ($result) {
    $row = $result->fetch_assoc();
    $data = array('num_registrations' => (int)$row['num_registrations']);
    echo json_encode($data);
} else {
    echo json_encode(['error' => 'Failed to fetch registrations']);
}

$conn->close();
?>
