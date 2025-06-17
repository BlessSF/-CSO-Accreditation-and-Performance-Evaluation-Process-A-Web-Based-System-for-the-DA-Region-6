<?php
session_start();
include ('include/db_connect.php');

$emailErr = "";

if (isset($_GET['id'])) {
    $representative_id = $_GET['id'];

    $sql_fetch_representative = "SELECT * FROM cso_representative WHERE id = ?";
    $stmt_fetch_representative = $conn->prepare($sql_fetch_representative);
    $stmt_fetch_representative->bind_param("i", $representative_id);
    $stmt_fetch_representative->execute();
    $result_representative = $stmt_fetch_representative->get_result();

    if ($result_representative->num_rows > 0) {
        $profile = $result_representative->fetch_assoc();
    } else {
        $_SESSION['status'] = "Representative not found.";
        header("Location: view_profile.php");
        exit;
    }
} else {
    $_SESSION['status'] = "Representative ID not specified.";
    header("Location: view_profile.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_POST["email"])) {
        $emailErr = "Email is required.";
    } else {
        $email = $_POST["email"];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = "Invalid email.";
        }
    }

    if (empty($emailErr)) {
        $sql_update_profile = "UPDATE cso_representative SET 
            email = ?, 
            first_name = ?, 
            middle_name = ?, 
            last_name = ?, 
            suffix = ?, 
            birthday = ?, 
            birth_place = ?, 
            nationality = ?, 
            religion = ?, 
            sex = ?, 
            civil_status = ?, 
            mobile_number = ?, 
            telephone_number = ? 
            WHERE id = ?";

        $stmt_update_profile = $conn->prepare($sql_update_profile);
        $stmt_update_profile->bind_param(
            "sssssssssssssi",
            $_POST['email'],
            $_POST['first_name'],
            $_POST['middle_name'],
            $_POST['last_name'],
            $_POST['suffix'],
            $_POST['birthday'],
            $_POST['birth_place'],
            $_POST['nationality'],
            $_POST['religion'],
            $_POST['sex'],
            $_POST['civil_status'],
            $_POST['mobile_number'],
            $_POST['telephone_number'],
            $representative_id
        );

        if (isset($_SESSION['message'])) {
            echo "<p class='error-message'>" . $_SESSION['message'] . "</p>";
            unset($_SESSION['message']);
        }      

        // Set success message for profile update
        $_SESSION['success1'] = "<span style='color: green; font-weight: bold;'>Profile updated successfully!</span>";
        header("Location: cso_profile.php");
        exit();
    }

    if ($_FILES['profile_image']['size'] > 0) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["profile_image"]["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
        if ($check !== false) {
            if ($_FILES["profile_image"]["size"] > 500000) {
                $_SESSION['status'] = "Sorry, your file is too large.";
                $uploadOk = 0;
            }
            if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
                $_SESSION['status'] = "Only JPG, JPEG, PNG, and GIF files are allowed.";
                $uploadOk = 0;
            }
            if ($uploadOk == 1) {
                if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                    $sql_update_image = "UPDATE cso_representative SET profile_image = ? WHERE id = ?";
                    $stmt_update_image = $conn->prepare($sql_update_image);
                    $stmt_update_image->bind_param("si", $target_file, $representative_id);
                    if ($stmt_update_image->execute()) {
                        $_SESSION['success'] = "Profile image updated successfully!";
                        $profile['profile_image'] = $target_file;
                    } else {
                        $_SESSION['status'] = "Failed to update profile image: " . $stmt_update_image->error;
                    }
                } else {
                    $_SESSION['status'] = "File upload error.";
                }
            }
        } else {
            $_SESSION['status'] = "File is not an image.";
        }
    }

    header("Location: view_profile.php?id=" . $representative_id);
    exit();
}
?>

<?php include ('cso_include/header.php'); ?>
<?php include ('cso_include/navbar.php'); ?>

<style>
    .container-fluid { 
        padding: 20px; 
    }
    .container {
        margin-left: auto;
        margin-right: auto;
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .yellow-line { 
        background-color: rgb(253, 199, 5); 
        height: 7px; 
        width: 100%; 
        margin: 0; 
        padding: 0; 
        }

    h2, h3 { 
        color: #0A593A; 
        font-weight: bold; 
    }

    .profile-image {
        float: left;
        margin-left: 30px;
    }

    .profile-image img {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 8px;
    }

    .btn-icon {
        padding: 0.375rem 0.75rem;
        font-size: 1.25rem;
    }

    h5 {
        color: #0A593A;
        font-weight: bold;
    }

    .btn-success {
        background-color: #0A593A;
        color: white;
        width: 5%;
    }

    .btn-success:hover{
        background-color: #0A593A;
        text-decoration: underline;
    }

    .btn-danger{
        width: 5%;
        margin-left: 5px;
    }

    .btn-danger:hover{
        text-decoration: underline;
    }
    
    .full-height-container {
        height: 90vh; 
        display: flex;
        flex-direction: column;
    }
</style>

<div id="wrapper">
<div class="container-fluid full-height-container">
    <div class="row">
            <div class="col-md-12">
                <h2>Edit CSO Representative Profile</h2>
            </div>
        </div>
        <div class="yellow-line"></div>
        <br>
    <?php
    if (isset($_SESSION['success'])) {
        echo "<p class='success'>" . $_SESSION['success'] . "</p>";
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['status'])) {
        echo "<p class='status'>" . $_SESSION['status'] . "</p>";
        unset($_SESSION['status']);
    }
    ?>
    <div class="form-section">
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $representative_id); ?>"
            enctype="multipart/form-data">
            <div class="profile-image">
                <img id="preview-image"
                    src="<?php echo htmlspecialchars($profile['profile_image'] ?? 'default_profile.jpg'); ?>"
                    alt="Profile Image">
                <div class="form-group">
                    <br>
                    <input type="file" class="form-control-file" id="profile_image" name="profile_image">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="email">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email"
                        value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>">
                    <span class="text-danger"><?php echo $emailErr; ?></span>
                </div>
                <div class="form-group col-md-6">
                    <label for="created_at">Account Created</label>
                    <input type="text" class="form-control" id="created_at" name="created_at"
                        value="<?php echo htmlspecialchars($profile['created_at'] ?? ''); ?>" readonly>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label for="last_name">Last Name</label>
                    <input type="text" class="form-control" id="last_name" name="last_name"
                        value="<?php echo htmlspecialchars($profile['last_name'] ?? ''); ?>">
                </div>
                <div class="form-group col-md-3">
                    <label for="first_name">First Name</label>
                    <input type="text" class="form-control" id="first_name" name="first_name"
                        value="<?php echo htmlspecialchars($profile['first_name'] ?? ''); ?>">
                </div>
                <div class="form-group col-md-3">
                    <label for="middle_name">Middle Name</label>
                    <input type="text" class="form-control" id="middle_name" name="middle_name"
                        value="<?php echo htmlspecialchars($profile['middle_name'] ?? ''); ?>">
                </div>
                <div class="form-group col-md-3">
                    <label for="suffix">Suffix</label>
                    <input type="text" class="form-control" id="suffix" name="suffix"
                        value="<?php echo htmlspecialchars($profile['suffix'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="birthday">Date of Birth</label>
                    <input type="date" class="form-control" id="birthday" name="birthday"
                        value="<?php echo htmlspecialchars($profile['birthday'] ?? ''); ?>">
                </div>
                <div class="form-group col-md-6">
                    <label for="birth_place">Place of Birth</label>
                    <input type="text" class="form-control" id="birth_place" name="birth_place"
                        value="<?php echo htmlspecialchars($profile['birth_place'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="nationality">Nationality</label>
                    <select class="form-control" id="nationality" name="nationality">
                        <option value="">Select Nationality</option>
                        <option value="Filipino" <?php if ($profile['nationality'] == 'Filipino')
                            echo 'selected'; ?>>
                            Filipino
                        </option>
                        <option value="American" <?php if ($profile['nationality'] == 'American')
                            echo 'selected'; ?>>
                            American
                        </option>
                        <option value="Canadian" <?php if ($profile['nationality'] == 'Canadian')
                            echo 'selected'; ?>>
                            Canadian
                        </option>
                        <option value="British" <?php if ($profile['nationality'] == 'British')
                            echo 'selected'; ?>>
                            British
                        </option>
                        <option value="Australian" <?php if ($profile['nationality'] == 'Australian')
                            echo 'selected'; ?>>
                            Australian
                        </option>
                        <option value="Indian" <?php if ($profile['nationality'] == 'Indian')
                            echo 'selected'; ?>>
                            Indian
                        </option>
                        <option value="Chinese" <?php if ($profile['nationality'] == 'Chinese')
                            echo 'selected'; ?>>
                            Chinese
                        </option>
                        <option value="Japanese" <?php if ($profile['nationality'] == 'Japanese')
                            echo 'selected'; ?>>
                            Japanese
                        </option>
                        <option value="German" <?php if ($profile['nationality'] == 'German')
                            echo 'selected'; ?>>
                            German
                        </option>
                        <option value="French" <?php if ($profile['nationality'] == 'French')
                            echo 'selected'; ?>>
                            French
                        </option>
                        <option value="Other" <?php if ($profile['nationality'] == 'Other')
                            echo 'selected'; ?>>
                            Other
                        </option>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label for="religion">Religion</label>
                    <select id="religion" name="religion" class="form-control">
                        <option value="" disabled>Select your religion</option>
                        <option value="Christianity" <?php if ($profile['religion'] === 'Christianity')
                            echo 'selected'; ?>>
                            Christianity
                        </option>
                        <option value="Islam" <?php if ($profile['religion'] === 'Islam')
                            echo 'selected'; ?>>
                            Islam
                        </option>
                        <option value="Hinduism" <?php if ($profile['religion'] === 'Hinduism')
                            echo 'selected'; ?>>
                            Hinduism
                        </option>
                        <option value="Buddhism" <?php if ($profile['religion'] === 'Buddhism')
                            echo 'selected'; ?>>
                            Buddhism
                        </option>
                        <option value="Judaism" <?php if ($profile['religion'] === 'Judaism')
                            echo 'selected'; ?>>
                            Judaism
                        </option>
                        <option value="Sikhism" <?php if ($profile['religion'] === 'Sikhism')
                            echo 'selected'; ?>>
                            Sikhism
                        </option>
                        <option value="Atheism" <?php if ($profile['religion'] === 'Atheism')
                            echo 'selected'; ?>>
                            Atheism
                        </option>
                        <option value="Agnosticism" <?php if ($profile['religion'] === 'Agnosticism')
                            echo 'selected'; ?>>
                            Agnosticism
                        </option>
                        <option value="Other" <?php if ($profile['religion'] === 'Other')
                            echo 'selected'; ?>>
                            Other
                        </option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="sex">Sex</label>
                    <select class="form-control" id="sex" name="sex">
                        <option value="">Select Sex</option>
                        <option value="Male" <?php if ($profile['sex'] == 'Male')
                            echo 'selected'; ?>>Male</option>
                        <option value="Female" <?php if ($profile['sex'] == 'Female')
                            echo 'selected'; ?>>Female</option>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label for="civil_status">Civil Status</label>
                    <select class="form-control" id="civil_status" name="civil_status">
                        <option value="">Select Civil Status</option>
                        <option value="Single" <?php if ($profile['civil_status'] == 'Single')
                            echo 'selected'; ?>>
                            Single
                        </option>
                        <option value="Married" <?php if ($profile['civil_status'] == 'Married')
                            echo 'selected'; ?>>
                            Married
                        </option>

                        <option value="Legally Separated" <?php if ($profile['civil_status'] == 'Legally Separated')
                            echo 'selected'; ?>>
                            Legally Separated
                        </option>
                        <option value="Widowed" <?php if ($profile['civil_status'] == 'Widowed')
                            echo 'selected'; ?>>
                            Widowed
                        </option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="mobile_number">Mobile Number</label>
                    <input type="text" class="form-control" id="mobile_number" name="mobile_number"
                        value="<?php echo htmlspecialchars($profile['mobile_number'] ?? ''); ?>">
                </div>
                <div class="form-group col-md-6">
                    <label for="telephone_number">Telephone Number</label>
                    <input type="text" class="form-control" id="telephone_number" name="telephone_number"
                        value="<?php echo htmlspecialchars($profile['mobile_number'] ?? ''); ?>">
                </div>
            </div>
            <br>
            <div class="form-row">
                <button type="submit" class="btn btn-success">Save</button>
                <a href="cso_profile.php" class="btn btn-danger">Back</a>
            </div>
    </div>
    </form>
</div>
</div>

<script>
    document.getElementById("profile_image").addEventListener("change", function () {
        var preview = document.getElementById('preview-image');
        var file = document.querySelector('input[type=file]').files[0];
        var reader = new FileReader();

        reader.onloadend = function () {
            preview.src = reader.result;
        }

        if (file) {
            reader.readAsDataURL(file);
        } else {
            preview.src = "<?php echo htmlspecialchars($profile['profile_image'] ?? 'default_profile.jpg'); ?>";
        }
    });
</script>

<?php include ('cso_include/footer.php'); ?>