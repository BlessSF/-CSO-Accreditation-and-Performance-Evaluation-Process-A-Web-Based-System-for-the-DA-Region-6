<?php
include('include/db_connect.php');

if (isset($_GET['id'])) {
    $announcement_id = $_GET['id'];
    $response = array();
    
    // Get announcement data - add updated_at to the query
    $stmt = $conn->prepare("SELECT id, title, ann_content, status FROM announcements WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->bind_param("i", $announcement_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Get announcement details
        $response['success'] = true;
        $response['data'] = $result->fetch_assoc();
        
        // Get associated files
        $files_query = "SELECT id, document_name, file_path FROM files WHERE announcement_id = ?";
        $files_stmt = $conn->prepare($files_query);
        $files_stmt->bind_param("i", $announcement_id);
        $files_stmt->execute();
        $files_result = $files_stmt->get_result();
        
        $response['files'] = array();
        while ($file = $files_result->fetch_assoc()) {
            $response['files'][] = $file;
        }
        
        $files_stmt->close();
    } else {
        $response['success'] = false;
        $response['message'] = "Announcement not found";
    }
    
    $stmt->close();
    $conn->close();
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    // Invalid request
    header('Content-Type: application/json');
    echo json_encode(array('success' => false, 'message' => 'Invalid request, announcement ID is required'));
}
?>