<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cso";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = $_POST['email'];
$password = $_POST['password'];

if ($email == 'admin123@gmail.com' && $password == 'admin123') {
    $_SESSION['user_id'] = 0;
    $_SESSION['first_name'] = 'RTS';
    $_SESSION['last_name'] = '';
    $_SESSION['is_admin'] = true;
    header("Location: admin_page.php");
    exit();
}

$sql_representative = "
    SELECT *
    FROM cso_representative
    WHERE email = ?
";
$stmt_representative = $conn->prepare($sql_representative);
$stmt_representative->bind_param("s", $email);
$stmt_representative->execute();
$result_representative = $stmt_representative->get_result();

if ($result_representative->num_rows > 0) {
    $user = $result_representative->fetch_assoc();
    if (password_verify($password, $user['password'])) {
        if ($user['verify_status'] == '1' && $user['status'] == 'Verified') {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['is_admin'] = false;
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['cso_name'] = $user['cso_name'];
            header("Location: dashboard.php");
            exit();
        } elseif ($user['verify_status'] == '0') {
            $_SESSION['message'] = "Email not verified. <br> Please check your email for the verification link.";
            header("Location: login.php");
            exit();
        } else {
            $_SESSION['message'] = "Account pending approval. <br> You’ll be notified by email once it’s approved.";
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION['message'] = "Incorrect email or password.";
        header("Location: login.php");
        exit();
    }
}

$stmt_representative->close();

$sql_chairperson = "
    SELECT *
    FROM cso_chairperson
    WHERE email = ?
";
$stmt_chairperson = $conn->prepare($sql_chairperson);
$stmt_chairperson->bind_param("s", $email);
$stmt_chairperson->execute();
$result_chairperson = $stmt_chairperson->get_result();

if ($result_chairperson->num_rows > 0) {
    $user = $result_chairperson->fetch_assoc();
    if (password_verify($password, $user['password'])) {
        if ($user['verify_status'] == '1') {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['is_admin'] = false;
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['cso_name'] = $user['cso_name'];
            header("Location: cso_dashboard.php");
            exit();
        } else {
            $_SESSION['message'] = "Email not verified. Please verify to log in.";
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION['message'] = "The password you entered is incorrect.";
        header("Location: login.php");
        exit();
    }
}

$stmt_chairperson->close();

$_SESSION['message'] = "No account associated with this email.";
header("Location: login.php");
exit();

$conn->close();
?>
