<?php
include('include/db_connect.php'); // Adjust the path as per your project structure

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch file details from the database
    $sql_fetch = "SELECT * FROM files WHERE id = ?";
    $stmt_fetch = $conn->prepare($sql_fetch);
    $stmt_fetch->bind_param("i", $id);
    $stmt_fetch->execute();
    $result_fetch = $stmt_fetch->get_result();

    if ($result_fetch->num_rows > 0) {
        $row = $result_fetch->fetch_assoc();
        $file_path = $row['file_path'];
        $file_name = $row['document_name'];

        // Ensure file path is valid
        if (file_exists($file_path)) {
            // Get the file mime type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_path);
            finfo_close($finfo);

            // Set headers for file viewing
            header('Content-Type: ' . $mime_type);
            header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
            header('Content-Length: ' . filesize($file_path));

            // Output the file for viewing
            readfile($file_path);
            exit;
        } else {
            echo "File not found.";
        }
    } else {
        echo "Document not found.";
    }

    $stmt_fetch->close();
}

$conn->close();
?>
