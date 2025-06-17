<?php
session_start();
include('include/db_connect.php');  // Include database connection

if (isset($_POST['submit'])) {
    $file = $_FILES['file'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    $fileType = $file['type'];

    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['xls', 'xlsx'];

    if (in_array($fileExt, $allowed)) {
        if ($fileError === 0) {
            // Move uploaded file to a directory
            $uploadPath = 'ocr_documents/' . $fileName;
            move_uploaded_file($fileTmpName, $uploadPath);

            // Process the uploaded Excel file
            require_once 'PHPExcel/PHPExcel.php'; // Include PHPExcel library
            
            $objPHPExcel = PHPExcel_IOFactory::load($uploadPath);
            $sheet = $objPHPExcel->getActiveSheet();
            
            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
            
            // Assume the structure of your Excel file (adjust accordingly)
            $data = [];
            for ($row = 1; $row <= $highestRow; $row++) {
                for ($col = 0; $col < $highestColumnIndex; $col++) {
                    $data[$row][] = $sheet->getCellByColumnAndRow($col, $row)->getValue();
                }
            }
            
            // Example: Insert data into database
            foreach ($data as $row) {
                $stmt = $conn->prepare("INSERT INTO financial_statements (date, account, amount) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $row[0], $row[1], $row[2]); // Adjust columns as per your Excel structure
                $stmt->execute();
                $stmt->close();
            }
            
            $_SESSION['message'] = "File uploaded successfully.";
            header("Location: ocr_documents.php"); // Redirect to upload form
            exit();
        } else {
            $_SESSION['message'] = "Error uploading file.";
        }
    } else {
        $_SESSION['message'] = "Invalid file type. Please upload a valid Excel file.";
    }
}
?>
