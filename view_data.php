<?php
session_start();
include('include/db_connect.php');  // Include database connection

// Retrieve uploaded data from database
$stmt = $conn->query("SELECT * FROM financial_statements ORDER BY date DESC");
$data = $stmt->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Financial Statements</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        table, th, td {
            border: 1px solid black;
            padding: 8px;
        }
    </style>
</head>
<body>
    <h2>Uploaded Financial Statements</h2>
    <?php if (!empty($_SESSION['message'])): ?>
        <p><?php echo $_SESSION['message']; ?></p>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
    
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Account</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row): ?>
                <tr>
                    <td><?php echo $row['date']; ?></td>
                    <td><?php echo $row['account']; ?></td>
                    <td><?php echo $row['amount']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
