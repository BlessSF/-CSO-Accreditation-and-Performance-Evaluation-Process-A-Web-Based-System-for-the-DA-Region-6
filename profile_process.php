<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";  // Change if needed
$password = "";      // Change if needed
$dbname = "cso";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$user_id = $_SESSION['user_id'];
$fullName = $_POST['fullName'];
$dateOfBirth = $_POST['dateOfBirth'];
$sex = $_POST['sex'];
$sexualOrientation = $_POST['sexualOrientation'];
$civilStatus = $_POST['civilStatus'];
$emailAddress = $_POST['emailAddress'];
$mobileNumber = $_POST['mobileNumber'];
$telephoneNumber = $_POST['telephoneNumber'];
$disability = $_POST['disability'];
$region = $_POST['region'];
$province = $_POST['province'];
$city = $_POST['city'];
$barangay = $_POST['barangay'];
$zipCode = $_POST['zipCode'];
$address = $_POST['address'];
$department = $_POST['department'];

// Check if profile already exists
$sql = "SELECT id FROM profiles WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // Profile exists, update it
    $sql = "UPDATE profiles SET full_name = ?, date_of_birth = ?, sex = ?, sexual_orientation = ?, civil_status = ?, email_address = ?, mobile_number = ?, telephone_number = ?, disability = ?, region = ?, province = ?, city = ?, barangay = ?, zip_code = ?, address = ?, department = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssssssssi", $fullName, $dateOfBirth, $sex, $sexualOrientation, $civilStatus, $emailAddress, $mobileNumber, $telephoneNumber, $disability, $region, $province, $city, $barangay, $zipCode, $address, $department, $user_id);
} else {
    // Profile does not exist, insert it
    $sql = "INSERT INTO profiles (user_id, full_name, date_of_birth, sex, sexual_orientation, civil_status, email_address, mobile_number, telephone_number, disability, region, province, city, barangay, zip_code, address, department) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssssssssssssss", $user_id, $fullName, $dateOfBirth, $sex, $sexualOrientation, $civilStatus, $emailAddress, $mobileNumber, $telephoneNumber, $disability, $region, $province, $city, $barangay, $zipCode, $address, $department);
}

if ($stmt->execute()) {
    // Redirect to the document upload page
    header("Location: upload_documents.php");
    exit();
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

// Close connection
$stmt->close();
$conn->close();
?>
