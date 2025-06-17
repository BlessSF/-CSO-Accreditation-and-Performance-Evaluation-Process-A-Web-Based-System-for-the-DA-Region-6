<?php
session_start();

include('include/db_connect.php');

if($dbconfig)
{
   // echo "Database Connected";
}
else
{
    header("Location: include/db_connect.php");
}

if(!$_SESSION['first_name'])
{
    header('Location: login.php');
}

?>