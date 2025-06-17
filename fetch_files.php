<?php
include('include/db_connect.php'); // Ensure this includes the connection to the database

// Check if the file ID is provided
if (isset($_GET['id'])) {
    $projectId = $_GET['id'];
    
    // Prepare the SQL statement to fetch the file details
    $sql = "SELECT id, title, project_desc, file_path FROM projects WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        // Bind the parameter
        mysqli_stmt_bind_param($stmt, "i", $fileId);
        
        // Execute the statement
        mysqli_stmt_execute($stmt);
        
        // Bind the result variables
        mysqli_stmt_bind_result($stmt, $title, $project_desc, $file_path);
        
        // Fetch the results
        if (mysqli_stmt_fetch($stmt)) {
            // Output the project details
            echo "Title: " . $title . "<br>";
            echo "Description: " . $project_desc . "<br>";
            echo "File Path: " . $file_path . "<br>";
        } else {
        }
        
        // Close the statement
        mysqli_stmt_close($stmt);
    } else {
        echo "Error preparing the statement: " . mysqli_error($conn);
    }
} else {
    echo "No file ID provided.";
}

// Close the database connection
mysqli_close($conn);
?>
