<?php
session_start();
include('include/header.php');
include('include/navbar.php');
include 'include/db_connect.php';
?>


<div class="section-background">
        <div class="container">
            <div class="login-section">
                <div class="login-form">
                    <img src="images/logo.png" alt="Department of Agriculture">
                    <h2>Reset Password</h2>
                           <?php
                    if (isset($_SESSION['message'])) {
                        echo "<p class='error-message'>" . $_SESSION['message'] . "</p>";
                        unset($_SESSION['message']);
                    }
                    ?>
                        <form class="user" action="resetcode.php" method="POST">
                        <input type="hidden" name = "password_token" value = "<?php if(isset($_GET['token'])){echo $_GET['token'];}  ?>">

                           
                           <div class="form-group mb-3">
                           <label for="">Email Address</label>
                                <input type="text" name="email"  value = "<?php if(isset($_GET['email'])){echo $_GET['email'];}  ?>" class="form-control form-control-user" placeholder="Enter Email">
                            </div>
                          
                            <div class="form-group mb-3">
                            <div class="password-wrapper">
                                <label for="">New Password</label>
                                <input type="password" name = "new_password" class="form-control form-control-user" id="passwordField" placeholder="Enter New Password" onkeyup="trigger()" required>
                                <i class="far fa-eye-slash password-toggle" id="passwordToggle"></i>
                                </div>
                            </div>


                            <div class="form-group mb-3" >
                                <label for="">Confirm Password</label>
                                <input type="password" name = "confirm_password" class="form-control form-control-user" placeholder="Confirm Password">
                            </div>

                                <button type="submit" name = "password_update" class="btn-custom">Update</button>
                                <br>
                            <div class="redirect-message">
                            Account already accessible? <a href="login.php">Log In</a>
                            </div>   

                        </form>

                        
                     </div>
                  </div>
               </div>
            </div>
   




<?php
include('include/script.php');
include('include/footer.php');
?>
