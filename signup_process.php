<?php
session_start();
include('include/db_connect.php'); 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendemail_verify($first_name, $email, $verify_token)
{
    $mail = new PHPMailer(true);
    try {
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
        $mail->Subject = "Email Verification for Account Registration";

        $email_template = "
            <div style='font-family: Arial, sans-serif; color: #333; padding: 20px; line-height: 1.6;'>
                <div style='border: 1px solid #0A593A; padding: 15px; border-radius: 8px; max-width: 600px; margin: auto;'>
                    <div style='padding: 20px; background-color: #f9f9f9; border-radius: 0 0 8px 8px;'>
                        <p style='font-size: 16px;'>
                            Hello <strong>{$first_name}</strong>,
                        </p>
                        <br>
                        <p>
                            Thank you for registering! To complete your registration, please verify your email address by clicking the button below.
                        </p>
                        <div style='text-align: center; margin: 20px 0;'>
                            <a href='http://localhost/DA/email_utility.php?token=$verify_token' 
                            style='background-color: #0A593A; color: #fff; text-decoration: none; padding: 12px 25px; border-radius: 5px; font-size: 16px; font-weight: bold;'>
                            Verify Your Email
                            </a>
                        </div>
                        <br>
                        <p>
                            Once your email is verified, please wait for the Department of Agriculture to review and approve your account. 
                            You will receive another email notification once your account status has been updated.
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
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // CSO Representative Form Submission
    if (isset($_POST['signin_btn'])) {
        // Extract CSO Representative form data
        $last_name = $_POST['last_name'];
        $first_name = $_POST['first_name'];
        $middle_name = $_POST['middle_name'];
        $suffix = $_POST['suffix'];
        $birthday = $_POST['birthday'];
        $birth_place = $_POST['birth_place'];
        $nationality = $_POST['nationality'];
        $religion = $_POST['religion'];
        $sex = $_POST['sex'];
        $civil_status = $_POST['civil_status'];
        $email = $_POST['email'];
        $cso_name = $_POST['cso_name'];
        $mobile_number = $_POST['mobile_number'];
        $telephone_number = $_POST['telephone_number'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $rep_id_type = $_POST['rep_id_type'];
        $verify_token = md5(rand());

        // Password confirmation check
        if ($password !== $confirm_password) {
            $_SESSION['status'] = "Passwords do not match. Please try again.";
            header("Location: signup.php");
            exit();
        }

        // Check for unique email
        $sql_check_email = "SELECT email FROM cso_representative WHERE email = ? UNION SELECT email FROM cso_chairperson WHERE email = ?";
        $stmt_check_email = $conn->prepare($sql_check_email);
        $stmt_check_email->bind_param("ss", $email, $email);
        $stmt_check_email->execute();
        $result_check_email = $stmt_check_email->get_result();

        if ($result_check_email->num_rows > 0) {
            $_SESSION['status'] = "Email already in use.";
            header("Location: signup.php");
            exit();
        }

        // Password hashing
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Handle file uploads for Representative's Government ID
        $uploadDir = 'id_uploads/';
        $repDir = $uploadDir . basename($cso_name) . '/' . $last_name . '_' . $first_name;
        if (!is_dir($repDir)) mkdir($repDir, 0777, true);

        $rep_gov_id_file = $_FILES['rep_gov_id_file']['name'];
        $rep_gov_id_path = $repDir . '/' . basename($rep_gov_id_file);
        move_uploaded_file($_FILES['rep_gov_id_file']['tmp_name'], $rep_gov_id_path);

        // Insert CSO Representative data into the database
        $sql_insert_representative = "INSERT INTO cso_representative (
            last_name, first_name, middle_name, suffix, birthday, birth_place, nationality, religion, 
            sex, civil_status, email, cso_name, mobile_number, telephone_number, password, verify_token, 
            gov_id_type, gov_id_file
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt_insert_representative = $conn->prepare($sql_insert_representative);
        $stmt_insert_representative->bind_param(
            "ssssssssssssssssss",
            $last_name, $first_name, $middle_name, $suffix, $birthday, $birth_place, $nationality, 
            $religion, $sex, $civil_status, $email, $cso_name, $mobile_number, $telephone_number, 
            $hashed_password, $verify_token, $rep_id_type, $rep_gov_id_path
        );

        if ($stmt_insert_representative->execute()) {
            sendemail_verify($first_name, $email, $verify_token);
            $_SESSION['success'] = "CSO Representative registered successfully!";
        } else {
            $_SESSION['status'] = "Error: " . $stmt_insert_representative->error;
        }

        $stmt_insert_representative->close();
        header("Location: signup.php");
        exit();
    }

    // CSO Chairperson Form Submission
    if (isset($_POST['signin_btn2'])) {
        // CSO Chairperson form data
        $cso_name = $_POST['cso_name'];
        $cso_address = $_POST['cso_address'];
        $region = "Region VI"; 
        $province = $_POST['province'];
        $city = $_POST['city'];
        $barangay = $_POST['barangay'];
        $street = $_POST['street'];
        $zip_code = $_POST['zip_code'];
        $last_name = $_POST['last_name'];
        $first_name = $_POST['first_name'];
        $middle_name = $_POST['middle_name'];
        $suffix = $_POST['suffix'];
        $birthday = $_POST['birthday'];
        $birth_place = $_POST['birth_place'];
        $nationality = $_POST['nationality'];
        $religion = $_POST['religion'];
        $sex = $_POST['sex'];
        $civil_status = $_POST['civil_status'];
        $email = $_POST['email'];
        $mobile_number = $_POST['mobile_number'];
        $telephone_number = $_POST['telephone_number'];
        $office_telephone_number = $_POST['office_telephone_number'];
        $latitude = $_POST['latitude'];
        $longitude = $_POST['longitude'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $verify_token = md5(rand());
        $chair_id_type = $_POST['chair_id_type'];
        $chair_certificate_type = $_POST['chair_certificate_type'];

        // Password confirmation check
        if ($password !== $confirm_password) {
            $_SESSION['status'] = "Passwords do not match. Please try again.";
            header("Location: signup.php");
            exit();
        }

        // Check for unique email
        $sql_check_email = "SELECT email FROM cso_representative WHERE email = ? UNION SELECT email FROM cso_chairperson WHERE email = ?";
        $stmt_check_email = $conn->prepare($sql_check_email);
        $stmt_check_email->bind_param("ss", $email, $email);
        $stmt_check_email->execute();
        $result_check_email = $stmt_check_email->get_result();

        if ($result_check_email->num_rows > 0) {
            $_SESSION['status'] = "Email already in use.";
            header("Location: signup.php");
            exit();
        }

        // Unique CSO Office name check
        $sql_check_cso_name = "SELECT cso_name FROM cso_representative WHERE cso_name = ? UNION SELECT cso_name FROM cso_chairperson WHERE cso_name = ?";
        $stmt_check_cso_name = $conn->prepare($sql_check_cso_name);
        $stmt_check_cso_name->bind_param("ss", $cso_name, $cso_name);
        $stmt_check_cso_name->execute();
        $result_check_cso_name = $stmt_check_cso_name->get_result();

        if ($result_check_cso_name->num_rows > 0) {
            $_SESSION['status'] = "CSO Office already registered.";
            header("Location: signup.php");
            exit();
        }

        // Password hashing
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Handle file uploads for Chairperson
        $uploadDir = 'id_uploads/';
        $chairDir = $uploadDir . basename($cso_name) . '/' . $last_name . '_' . $first_name;
        if (!is_dir($chairDir)) mkdir($chairDir, 0777, true);

        $chair_gov_id_file = $_FILES['chair_gov_id_file']['name'];
        $chair_gov_id_path = $chairDir . '/' . basename($chair_gov_id_file);
        move_uploaded_file($_FILES['chair_gov_id_file']['tmp_name'], $chair_gov_id_path);

        $chair_certification_file = $_FILES['chair_certification_file']['name'];
        $chair_certification_path = $chairDir . '/' . basename($chair_certification_file);
        move_uploaded_file($_FILES['chair_certification_file']['tmp_name'], $chair_certification_path);

        // Insert into database with updated fields
        $sql_insert_chairperson = "INSERT INTO cso_chairperson (
            cso_name, cso_address, region, province, city, barangay, street, zip_code, 
            last_name, first_name, middle_name, suffix, birthday, birth_place, nationality, 
            religion, sex, civil_status, email, mobile_number, telephone_number, office_telephone_number, 
            latitude, longitude, password, verify_token, gov_id_type, gov_id_file, certificate_type, certificate_file
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt_insert_chairperson = $conn->prepare($sql_insert_chairperson);
        $stmt_insert_chairperson->bind_param(
            "ssssssssssssssssssssssssssssss", 
            $cso_name, $cso_address, $region, $province, $city, $barangay, $street, $zip_code, 
            $last_name, $first_name, $middle_name, $suffix, $birthday, $birth_place, $nationality, 
            $religion, $sex, $civil_status, $email, $mobile_number, $telephone_number, 
            $office_telephone_number, $latitude, $longitude, $hashed_password, $verify_token, 
            $chair_id_type, $chair_gov_id_path, $chair_certificate_type, $chair_certification_path
        );

        if ($stmt_insert_chairperson->execute()) {
            sendemail_verify($first_name, $email, $verify_token);
            $_SESSION['success'] = "CSO Main Office registered successfully!";
        } else {
            $_SESSION['status'] = "Error: " . $stmt_insert_chairperson->error;
        }
        $stmt_insert_chairperson->close();
        header("Location: signup.php");
        exit();
    }
}
?>