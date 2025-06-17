<?php
session_start();
include('security.php');

$connection = mysqli_connect("localhost","root","","cso");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function send_password_reset($get_fname, $get_email, $token)
{
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->SMTPAuth = true;

    $mail->Host = 'smtp.gmail.com'; 
    $mail->Username = 'eastonmpc@gmail.com'; 
    $mail->Password = 'kzxxtuyzempvsxnj';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
    $mail->Port = 587; 

    $mail->setFrom('eastonmpc@gmail.com', "CSO Accreditation");
    $mail->addAddress($get_email); 

    $mail->isHTML(true);
    $mail->Subject = "Password Reset Request";

    $email_template = "
    <div style='font-family: Arial, sans-serif; color: #333; padding: 20px; line-height: 1.6;'>
        <div style='border: 1px solid #0A593A; padding: 15px; border-radius: 8px; max-width: 600px; margin: auto;'>
            <div style='padding: 20px; background-color: #f9f9f9; border-radius: 0 0 8px 8px;'>
                <p style='font-size: 16px;'>
                    Hello <strong>User</strong>,
                </p>
                <br>
                <p>
                    We received a request to reset your password. To proceed, please click the button below:
                </p>
                <div style='text-align: left; margin: 20px 0;'>
                    <a href='http://localhost/DA/change_pass.php?token=$token&email=$get_email' 
                    style='background-color: #0A593A; color: #fff; text-decoration: none; padding: 12px 25px; border-radius: 5px; font-size: 16px; font-weight: bold; transition: background-color 0.3s ease;'>
                    Reset Your Password
                    </a>
                </div>
                <br>
                <p>
                    If you did not request a password reset, please ignore this email.
                </p>
            </div>
        </div>
        <footer style='text-align: center; padding: 10px; font-size: 12px; color: #888; margin-top: 10px;'>
            &copy; " . date("Y") . " Department of Agriculture. All rights reserved.
        </footer>
    </div>
";

    $mail->Body = $email_template;
    $mail->send();
}

if (isset($_POST['reset_btn'])) {
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $token = md5(rand());

    $check_email = "SELECT first_name, email FROM cso_representative WHERE email = '$email' LIMIT 1";
    $check_email_run = mysqli_query($connection, $check_email);

    if (mysqli_num_rows($check_email_run) > 0) {
        $row = mysqli_fetch_array($check_email_run);
        $get_fname = $row['first_name'];
        $get_email = $row['email'];

        $update_token = "UPDATE cso_representative SET verify_token = '$token' WHERE email = '$get_email' LIMIT 1";
        $update_token_run = mysqli_query($connection, $update_token);

        if ($update_token_run) {
            send_password_reset($get_fname, $get_email, $token);
            $_SESSION['message'] = "<span style='color: green; font-weight: bold;'>We emailed you a password reset link.</span>";
            header("Location: reset_pass.php");
            exit(0);        
        } else {
            $_SESSION['message'] = "Something went wrong!";
            header("Location: reset_pass.php");
            exit(0);
        }
    } else {
        $check_email = "SELECT first_name, email FROM cso_chairperson WHERE email = '$email' LIMIT 1";
        $check_email_run = mysqli_query($connection, $check_email);

        if (mysqli_num_rows($check_email_run) > 0) {
            $row = mysqli_fetch_array($check_email_run);
            $get_fname = $row['first_name'];
            $get_email = $row['email'];

            $update_token = "UPDATE cso_chairperson SET verify_token = '$token' WHERE email = '$get_email' LIMIT 1";
            $update_token_run = mysqli_query($connection, $update_token);

            if ($update_token_run) {
                send_password_reset($get_fname, $get_email, $token);
                $_SESSION['message'] = "<span style='color: green; font-weight: bold;'>We emailed you a password reset link.</span>";
                header("Location: reset_pass.php");
                exit(0);
            } else {
                $_SESSION['message'] = "Something went wrong!";
                header("Location: reset_pass.php");
                exit(0);
            }
        } else {
            $_SESSION['message'] = "No Email Found";
            header("Location: password_reset.php");
            exit(0);
        }
    }
}

if (isset($_POST['password_update'])) {
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $new_password = mysqli_real_escape_string($connection, $_POST['new_password']);
    $confirm_password = mysqli_real_escape_string($connection, $_POST['confirm_password']);
    $token = mysqli_real_escape_string($connection, $_POST['password_token']);

    if (!empty($token)) {
        if (!empty($email) && !empty($new_password) && !empty($confirm_password)) {
            $check_token_query = "SELECT verify_token FROM cso_representative WHERE verify_token = ? LIMIT 1";
            $stmt_check_token = mysqli_prepare($connection, $check_token_query);
            mysqli_stmt_bind_param($stmt_check_token, "s", $token);
            mysqli_stmt_execute($stmt_check_token);
            mysqli_stmt_store_result($stmt_check_token);

            if (mysqli_stmt_num_rows($stmt_check_token) > 0) {
                if ($new_password == $confirm_password) {
                    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

                    $update_password_query = "UPDATE cso_representative SET password = ?, verify_token = ? WHERE verify_token = ? LIMIT 1";
                    $stmt_update_password = mysqli_prepare($connection, $update_password_query);
                    $new_token = md5(random_bytes(32));
                    mysqli_stmt_bind_param($stmt_update_password, "sss", $hashed_password, $new_token, $token);
                    mysqli_stmt_execute($stmt_update_password);

                    if ($stmt_update_password) {
                        $_SESSION['message'] = "<span style='color: green; font-weight: bold;'> Password updated successfully! </span>";
                        header("Location: login.php");
                        exit(0);
                    } else {
                        $_SESSION['message'] = "Password update failed. Please try again.";
                        header("Location: change_pass.php?token=$token&email=$email");
                        exit(0);
                    }
                } else {
                    $_SESSION['message'] = "Passwords do not match.";
                    header("Location: change_pass.php?token=$token&email=$email");
                    exit(0);
                }
            } else {
                $check_token_query = "SELECT verify_token FROM cso_chairperson WHERE verify_token = ? LIMIT 1";
                $stmt_check_token = mysqli_prepare($connection, $check_token_query);
                mysqli_stmt_bind_param($stmt_check_token, "s", $token);
                mysqli_stmt_execute($stmt_check_token);
                mysqli_stmt_store_result($stmt_check_token);

                if (mysqli_stmt_num_rows($stmt_check_token) > 0) {
                    if ($new_password == $confirm_password) {
                        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

                        $update_password_query = "UPDATE cso_chairperson SET password = ?, verify_token = ? WHERE verify_token = ? LIMIT 1";
                        $stmt_update_password = mysqli_prepare($connection, $update_password_query);
                        $new_token = md5(random_bytes(32));
                        mysqli_stmt_bind_param($stmt_update_password, "sss", $hashed_password, $new_token, $token);
                        mysqli_stmt_execute($stmt_update_password);

                        if ($stmt_update_password) {
                            $_SESSION['message'] = "<span style='color: green; font-weight: bold;'> Password updated successfully! </span>";
                            header("Location: login.php");
                            exit(0);
                        } else {
                            $_SESSION['message'] = "Password update failed. Please try again.";
                            header("Location: change_pass.php?token=$token&email=$email");
                            exit(0);
                        }
                    } else {
                        $_SESSION['message'] = "Passwords do not match.";
                        header("Location: change_pass.php?token=$token&email=$email");
                        exit(0);
                    }
                } else {
                    $_SESSION['message'] = "Invalid token.";
                    header("Location: change_pass.php?token=$token&email=$email");
                    exit(0);
                }
            }
        } else {
            $_SESSION['message'] = "All fields are mandatory.";
            header("Location: change_pass.php?token=$token&email=$email");
            exit(0);
        }
    } else {
        $_SESSION['message'] = "No token available.";
        header("Location: change_pass.php");
        exit(0);
    }
}
?>
