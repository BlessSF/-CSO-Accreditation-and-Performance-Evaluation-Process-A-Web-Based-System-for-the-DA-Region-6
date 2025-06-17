<?php
session_start();
$connection = mysqli_connect("localhost", "root", "", "cso");

if (isset($_GET['token'])) {
    $token = mysqli_real_escape_string($connection, $_GET['token']);
    
    $verify_query = "SELECT verify_token, verify_status FROM cso_representative WHERE verify_token = '$token' LIMIT 1";
    $verify_query_run = mysqli_query($connection, $verify_query);

    if (mysqli_num_rows($verify_query_run) > 0) {
        $row = mysqli_fetch_array($verify_query_run);
        if ($row['verify_status'] == "0") {
            $clicked_token = $row['verify_token'];
            $update_query = "UPDATE cso_representative SET verify_status = '1' WHERE verify_token = '$clicked_token' LIMIT 1";
            $update_query_run = mysqli_query($connection, $update_query);

            if ($update_query_run) {
                $_SESSION['message'] = "<span style='color: green; font-weight: bold;'>Verification complete. Please log in.</span>";
                header('Location: login.php');
                exit(0);
            } else {
                $_SESSION['message'] = "Something went wrong with verification. Please try again.";
                header('Location: login.php');
                exit(0);
            }
        } else {
            $_SESSION['message'] = "Email is already verified. Please log in.";
            header('Location: login.php');
            exit(0);
        }
    } else {
        $verify_query = "SELECT verify_token, verify_status FROM cso_chairperson WHERE verify_token = '$token' LIMIT 1";
        $verify_query_run = mysqli_query($connection, $verify_query);

        if (mysqli_num_rows($verify_query_run) > 0) {
            $row = mysqli_fetch_array($verify_query_run);
            if ($row['verify_status'] == "0") {
                $clicked_token = $row['verify_token'];
                $update_query = "UPDATE cso_chairperson SET verify_status = '1' WHERE verify_token = '$clicked_token' LIMIT 1";
                $update_query_run = mysqli_query($connection, $update_query);

                if ($update_query_run) {
                    $_SESSION['message'] = "<span style='color: green; font-weight: bold;'>Verification complete. Please log in.</span>";
                    header('Location: login.php');
                    exit(0);
                } else {
                    $_SESSION['message'] = "Something went wrong with verification. Please try again.";
                    header('Location: login.php');
                    exit(0);
                }
            } else {
                $_SESSION['message'] = "Email is already verified. Please log in.";
                header('Location: login.php');
                exit(0);
            }
        } else {
            $_SESSION['message'] = "This Token does not exist.";
            header('Location: login.php');
            exit(0);
        }
    }
} else {
    $_SESSION['message'] = "Not Allowed";
    header('Location: login.php');
    exit(0);
}
?>