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
                           <h1 class="h4 text-black-900  font-weight-bold mb-4">Resend Verification Email</h1>
                           <?php
                    if (isset($_SESSION['message'])) {
                        echo "<p class='error-message'>" . $_SESSION['message'] . "</p>";
                        unset($_SESSION['message']);
                    }
                    ?>
                        <form class="user" action="resendcode.php" method="POST">
                           
                           <div class="form-group mb-3">
                                <input type="text" name="email"  class="form-control form-control-user" placeholder="Enter Email Address">
                            </div>
                          
                           <button type="submit" name="resend_btn" class="btn-custom">Resend</button>
                           <br>
                           <div class="redirect-message">
                           Account already verified? <a href="login.php">Log In</a>
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

