<?php
session_start();
include ('include/header.php');
include ('include/navbar.php');
?>


    <div class="section-background">
        <div class="container">
            <div class="login-section">
                <div class="login-form">
                    <img src="images/logo.png" alt="Department of Agriculture">
                    <h2>Account Login</h2>
                    <?php 
                    if (isset($_SESSION['message'])) {
                        // Set color based on message content
                        $message_color = strpos($_SESSION['message'], 'Verification complete') !== false ? 'green' : 'red';
                        echo "<p style='color: $message_color; font-weight: bold;'>" . $_SESSION['message'] . "</p>";
                        unset($_SESSION['message']);
                    }
                    ?>        
                    <form action="login_process.php" method="POST">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn-custom">Login</button>
                        <br>
                        <div class="redirect-message">
                        <a href="reset_pass.php">Forgot Password?</a> 
                           </div>   
                    </form>
                    <div class="redirect-message">
                        Not Registered? <a href="signup.php">Register</a>
                    <p class="para-2">No verification email?
                            <a href="resend.php">Resend</a></p>
                </div>
                </div>

            </div>
        </div>
    </div>

    <?php
include ('include/script.php');
include ('include/footer.php');
?>