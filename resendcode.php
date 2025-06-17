<?php
session_start();
include('security.php');

$connection = mysqli_connect("localhost","root","","cso");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function resend_email_verify($first_name, $email, $verify_token)
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
    $mail->addAddress($email); 

    $mail->isHTML(true);
    $mail->Subject = "Verification Resend Request";

    $email_template = "
    <div style='font-family: Arial, sans-serif; color: #333; padding: 20px; line-height: 1.6;'>
        <div style='border: 1px solid #0A593A; padding: 15px; border-radius: 8px; max-width: 600px; margin: auto;'>
            <div style='padding: 20px; background-color: #f9f9f9; border-radius: 0 0 8px 8px;'>
                <p style='font-size: 16px;'>
                    Hello <strong>User</strong>,
                </p>
                <br>
                <p>
                    We received a request to resend a verification link to your email address. To complete your registration, please click the button below:
                </p>
                <div style='text-align: left; margin: 20px 0;'>
                    <a href='http://localhost/DA/email_utility.php?token=$verify_token' 
                    style='background-color: #0A593A; color: #fff; text-decoration: none; padding: 12px 25px; border-radius: 5px; font-size: 16px; font-weight: bold; transition: background-color 0.3s ease;'>
                    Verify Your Email
                    </a>
                </div>
                <br>
                <p>
                    Once verified, you'll be able to log in and access all features of our website. If you did not request this, please ignore this email.
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

if (isset($_POST['resend_btn'])) 
{
    if(!empty(trim($_POST['email'])))
    {
        $email = mysqli_real_escape_string($connection, $_POST['email']);

        $checkemail_query = "SELECT * FROM cso_representative WHERE email = '$email' LIMIT 1";
        $checkemail_query_run = mysqli_query($connection, $checkemail_query);

        if(mysqli_num_rows($checkemail_query_run) > 0 )
        {
            $row = mysqli_fetch_array($checkemail_query_run);
            if($row['verify_status'] == "0")
            {
                $first_name = $row['first_name'];
                $email = $row['email'];
                $verify_token = $row['verify_token'];

                resend_email_verify($first_name, $email, $verify_token);
                $_SESSION['message'] = "<span style='color: green; font-weight: bold;'>A verification email has been sent. <br> Check your inbox to verify your account.</span>";
                header("Location: login.php");
                exit(0);
                
            }
            else
            {
                $_SESSION['message'] = "Email already verified. Please log in.";
                header("Location: resend.php");
                exit(0); 
            }
        }
        else
        {
            $checkemail_query = "SELECT * FROM cso_chairperson WHERE email = '$email' LIMIT 1";
            $checkemail_query_run = mysqli_query($connection, $checkemail_query);

            if(mysqli_num_rows($checkemail_query_run) > 0 )
            {
                $row = mysqli_fetch_array($checkemail_query_run);
                if($row['verify_status'] == "0")
                {
                    $first_name = $row['first_name'];
                    $email = $row['email'];
                    $verify_token = $row['verify_token'];

                    resend_email_verify($first_name, $email, $verify_token);
                    $_SESSION['message'] = "<span style='color: green; font-weight: bold;'>A verification email has been sent. <br> Check your inbox to verify your account.</span>";
                    header("Location: login.php");
                    exit(0);
                }
                else
                {
                    $_SESSION['message'] = "Email already verified. Please log in.";
                    header("Location: resend.php");
                    exit(0); 
                }
            }
            else
            {
                $_SESSION['message'] = "Email not registered. Please register.";
                header("Location: signup.php");
                exit(0);
            }
        }
    }
    else
    {
        $_SESSION['message'] = "Email is required.";
        header("Location: resend.php");
        exit(0);
    }
}
?>
