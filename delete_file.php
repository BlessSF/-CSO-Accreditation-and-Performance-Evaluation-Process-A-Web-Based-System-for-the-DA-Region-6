<?php
include('include/db_connect.php');

// Initialize response array
$response = ['success' => false, 'message' => ''];

// Check if file ID is provided
if (isset($_POST['file_id']) && !empty($_POST['file_id'])) {
    $file_id = $_POST['file_id'];
    
    // First get the file path to delete the actual file
    $stmt = $conn->prepare("SELECT file_path FROM files WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $file_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $file_path = $row['file_path'];
                
                // Delete the file from the server if it exists
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                
                // Delete the file record from the database
                $delete_stmt = $conn->prepare("DELETE FROM files WHERE id = ?");
                if ($delete_stmt) {
                    $delete_stmt->bind_param("i", $file_id);
                    if ($delete_stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'File deleted successfully';
                    } else {
                        $response['message'] = 'Error deleting file from database: ' . $delete_stmt->error;
                    }
                    $delete_stmt->close();
                } else {
                    $response['message'] = 'Error preparing delete statement: ' . $conn->error;
                }
            } else {
                $response['message'] = 'File not found';
            }
        } else {
            $response['message'] = 'Error executing query: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $response['message'] = 'Error preparing statement: ' . $conn->error;
    }
} else {
    $response['message'] = 'File ID is required';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>