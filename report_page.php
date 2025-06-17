<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

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

// Fetch total number of users
$sql_total_users = "SELECT COUNT(*) as total_users FROM users";
$result_total_users = $conn->query($sql_total_users);
$total_users = $result_total_users->fetch_assoc()['total_users'];

// Fetch total number of documents
$sql_total_documents = "SELECT COUNT(*) as total_documents FROM documents";
$result_total_documents = $conn->query($sql_total_documents);
$total_documents = $result_total_documents->fetch_assoc()['total_documents'];

// Fetch total number of OCR performed
$sql_total_ocr = "SELECT COUNT(*) as total_ocr FROM documents WHERE extracted_text IS NOT NULL";
$result_total_ocr = $conn->query($sql_total_ocr);
$total_ocr = $result_total_ocr->fetch_assoc()['total_ocr'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reports</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            margin: 0;
            height: 100vh;
            background-color: #f0f2f5;
        }
        .sidebar {
            width: 250px;
            background-color: #333;
            color: white;
            padding-top: 20px;
            position: fixed;
            height: 100%;
            overflow-y: auto;
        }
        .sidebar a {
            padding: 15px;
            text-decoration: none;
            font-size: 18px;
            color: white;
            display: block;
        }
        .sidebar a:hover {
            background-color: #575757;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
        }
        .card-container {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .card {
            flex: 1 1 calc(25% - 20px);
            background: #fff;
            padding: 20px;
            margin: 10px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            position: relative;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .card:hover {
            transform: scale(1.05);
        }
        .card h3 {
            margin: 0;
            font-size: 48px;
        }
        .card p {
            margin: 10px 0;
            font-size: 18px;
        }
        .card.red {
            background-color: #f44336;
            color: white;
        }
        .card.orange {
            background-color: #ff9800;
            color: white;
        }
        .card.green {
            background-color: #4caf50;
            color: white;
        }
        .card.blue {
            background-color: #2196f3;
            color: white;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Admin Dashboard</h2>
    <a href="admin_page.php">Dashboard</a>
    <a href="report_page.php">Reports</a>
    <a href="settings.php">Settings</a>
    <a href="logout.php" class="logout-button">Logout</a>
</div>

<div class="content">
    <h2>Reports</h2>
    <div class="card-container">
        <div class="card red">
            <h3><?php echo $total_documents; ?></h3>
            <p>Total Documents</p>
        </div>
        <div class="card orange">
            <h3><?php echo $total_users; ?></h3>
            <p>Total Users</p>
        </div>
        <div class="card green">
            <h3><?php echo $total_ocr; ?></h3>
            <p>Total OCR Performed</p>
        </div>
    </div>
</div>

</body>
</html>

<?php
// Close connection
$conn->close();
?>
