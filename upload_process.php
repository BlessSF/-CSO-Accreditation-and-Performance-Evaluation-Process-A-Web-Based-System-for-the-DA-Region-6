<?php
session_start();
require 'vendor/autoload.php';
use thiagoalessio\TesseractOCR\TesseractOCR;

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cso";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$target_dir = "uploads/";
$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
$fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
    $user_id = $_SESSION['user_id'];
    $file_name = basename($_FILES["fileToUpload"]["name"]);
    $file_path = $target_file;

    // Extract text if the file is an image or PDF
    $extracted_text = "";
    if ($fileType == "pdf") {
        // Use pdftotext to extract text from PDF
        $pdfText = shell_exec("pdftotext $target_file -");
        $extracted_text = $pdfText ? $pdfText : 'Error extracting text from PDF';
    } elseif (in_array($fileType, ['jpg', 'jpeg', 'png'])) {
        $extracted_text = (new TesseractOCR($target_file))->run();
    }

    // Save file info and extracted text to database
    $sql = "INSERT INTO documents (user_id, file_name, file_path, extracted_text) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $user_id, $file_name, $file_path, $extracted_text);
    $stmt->execute();
} else {
    $error = "Sorry, there was an error uploading your file.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Upload and OCR Processing</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            width: 100%;
            text-align: center;
        }
        h2 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }
        h3 {
            margin-top: 20px;
            font-size: 20px;
            color: #333;
        }
        .extracted-text {
            background-color: #e0f7fa;
            padding: 15px;
            border-radius: 5px;
            text-align: left;
            white-space: pre-wrap;
            margin-top: 10px;
            color: #333;
            overflow-wrap: break-word;
        }
        .btn {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            margin: 15px 10px 0 10px;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .btn-secondary {
            background-color: #007bff;
        }
        .btn-secondary:hover {
            background-color: #0069d9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Document Upload and OCR Processing</h2>
        <?php if (isset($file_name)): ?>
            <p>The file <strong><?php echo htmlspecialchars($file_name); ?></strong> has been uploaded and processed.</p>
            <h3>Extracted Text</h3>
            <div class="extracted-text"><?php echo nl2br(htmlspecialchars($extracted_text)); ?></div>
        <?php elseif (isset($error)): ?>
            <p><?php echo $error; ?></p>
        <?php endif; ?>
        <a href="admin_page.php" class="btn">Back to Admin Page</a>
        <a href="profile.php" class="btn btn-secondary">Back to User Page</a>
    </div>
</body>
</html>
