<?php
include('include/db_connect.php');
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id        = $_POST['id']        ?? null;
    $status    = $_POST['status']    ?? null;
    $user_type = $_POST['user_type'] ?? null;

    if (!$id || !$status || !$user_type) {
        echo 'Invalid input data';
        exit;
    }

    $valid_status = ['Unverified', 'Verified'];
    if (!in_array($status, $valid_status)) {
        echo 'Invalid status value';
        exit;
    }

    $sql_update = '';
    $sql_fetch  = '';
    $bind_types = 'si';
    $bind_values = [$status, $id];

    // Set the SQL query for Representative and Chairperson
    if ($user_type === 'representative') {
        $sql_update = "UPDATE cso_representative SET status = ?, verified_at = NOW() WHERE id = ?";
        $sql_fetch  = "SELECT email, first_name, last_name, suffix, cso_name 
                       FROM cso_representative WHERE id = ?";
    } elseif ($user_type === 'cso') {
        $sql_update = "UPDATE cso_chairperson SET status = ?, verified_at = NOW() WHERE id = ?";
        $sql_fetch  = "SELECT email, first_name, last_name, suffix, cso_name 
                       FROM cso_chairperson WHERE id = ?";
    } else {
        echo 'Invalid user type';
        exit;
    }

    // Start database transaction
    $conn->begin_transaction();
    try {
        // Prepare and execute the update query
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param($bind_types, ...$bind_values);
        $stmt_update->execute();

        // Fetch user details
        $stmt_fetch = $conn->prepare($sql_fetch);
        $stmt_fetch->bind_param('i', $id);
        $stmt_fetch->execute();
        $user_details = $stmt_fetch->get_result()->fetch_assoc();

        if ($user_details && !empty($user_details['email'])) {
            $email     = $user_details['email'];
            $full_name = trim($user_details['first_name'] . ' ' . $user_details['last_name'] . ' ' . $user_details['suffix']);
            $cso_name  = $user_details['cso_name'];

            // Set up PHPMailer
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'eastonmpc@gmail.com';
            $mail->Password   = 'kzxxtuyzempvsxnj';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('eastonmpc@gmail.com', 'Department of Agriculture');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = "CSO {$user_type} Account Status Update";
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; color: #333; padding: 20px;'>
                    Dear <strong>{$full_name}</strong>,<br><br>
                    Your account as <strong>{$user_type}</strong> for 
                    <strong>{$cso_name}</strong> has been updated to 
                    <strong>{$status}</strong>.<br><br>
                    Please <a href='http://localhost/DA/login.php'>log in</a> to view details.
                </div>
            ";
            $mail->send();
        }

        // Commit transaction
        $conn->commit();
        echo 'success';
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log('Error: ' . $e->getMessage());
        echo 'Error: Unable to process request';
    } finally {
        // Close prepared statements
        if (isset($stmt_update)) $stmt_update->close();
        if (isset($stmt_fetch)) $stmt_fetch->close();
    }

    // Close connection
    $conn->close();
}
?>