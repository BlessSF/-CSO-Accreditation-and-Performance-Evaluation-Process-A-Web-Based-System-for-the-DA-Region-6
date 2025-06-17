<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
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

    // Function to sanitize file names (replace spaces with underscores and remove special characters)
    function sanitize_filename($filename) {
        $filename = preg_replace("/[^A-Za-z0-9\_\-\.]/", '', $filename);
        return str_replace(' ', '_', $filename);
    }

    // Retrieve user ID from session
    $user_id = $_SESSION['user_id'];

    // Array of document names
    $documents = [
        'Accomplished Data Sheet with Organizational Set-up',
        'Certificate of Good Standing from Local Chief Executive or Head of Local Religious Organizations',
        'Valid Mayor\'s Permit',
        'BIR Registration',
        'Certificate of Registration and/or Certificate of Filing from SEC, CDA, or DOLE-BRW',
        'Certificate of Good Standing from Government Agencies',
        'Omnibus Sworn Statement (duly Notarized)',
        'Copy of Bio-data sheet with recent photo of current President/Chairman',
        'Articles of Incorporation/Cooperation latest amend by law',
        'Secretary\'s Certificate of Incumbent Officers',
        'Report of accomplishment for the last three years and latest audited financial statement',
        'Disclosure by the CSO Directors and its Trustees of other related business',
        'Sworn Affidavit of the Secretary of CSO'
    ];

    // Process each document upload
    foreach ($documents as $document) {
        $sanitized_document_name = sanitize_filename($document);

        // Check if file is uploaded
        if (isset($_FILES["file_" . $sanitized_document_name])) {
            $file = $_FILES["file_" . $sanitized_document_name];

            // Check if file upload is successful
            if ($file['error'] === UPLOAD_ERR_OK) {
                $temp_name = $file['tmp_name'];

                // Move uploaded file to the destination folder
                $folderPath = "uploads/";
                if (!is_dir($folderPath)) {
                    mkdir($folderPath, 0755, true); // Create the folder recursively if it doesn't exist
                }

                // Generate unique filename
                $filename = $sanitized_document_name . "_" . uniqid() . "_" . basename($file['name']);
                $targetPath = $folderPath . $filename;

                if (move_uploaded_file($temp_name, $targetPath)) {
                    // Insert file information into database
                    $sql = "INSERT INTO documents 
                    
                    (cso_representative_id, document_name, file_path) VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iss", $user_id, $document, $targetPath);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    echo "Failed to move file.";
                }
            } else {
                echo "Error uploading file.";
            }
        }
    }

    $conn->close();

    // Redirect to my_documents.php after processing uploads
    header("Location: my_documents.php");
    exit();
} else {
    // Invalid request method
    echo "Invalid request.";
}
?>
