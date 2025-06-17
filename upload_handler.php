<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cso";

// Create mysqli connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Current date for folder name
$current_date = date('Y-m-d');
$upload_dir = "uploads/$current_date";

// Create directory if it doesn't exist
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Function to sanitize file names (replace spaces with underscores and remove special characters)
function sanitize_filename($filename) {
    $filename = preg_replace("/[^A-Za-z0-9\_\-\.]/", '', $filename);
    return str_replace(' ', '_', $filename);
}

foreach ($_FILES as $key => $file) {
    if ($file['error'] === UPLOAD_ERR_OK) {
        $document_name = str_replace('file_', '', $key);
        $document_name = str_replace('_', ' ', $document_name);
        $sanitized_filename = sanitize_filename($file['name']);
        $file_path = "$upload_dir/$sanitized_filename";
        
        // Move the uploaded file
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            // Insert into database
            $stmt = $conn->prepare("INSERT INTO documents (cso_representative_id, document_name, file_name, file_path, uploaded_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("isss", $user_id, $document_name, $sanitized_filename, $file_path);
            $stmt->execute();
            $stmt->close();
        }
    }
}

$conn->close();

// Redirect to my_documents.php
header("Location: my_documents.php");
exit();
?>
