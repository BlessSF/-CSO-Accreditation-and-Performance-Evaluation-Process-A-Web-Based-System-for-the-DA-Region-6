<?php
session_start();
include ('include/db_connect.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$sql_fetch_profile = "SELECT * FROM cso_representative WHERE id = ?";
$stmt_fetch_profile = $conn->prepare($sql_fetch_profile);
$stmt_fetch_profile->bind_param("i", $user_id);
$stmt_fetch_profile->execute();
$result = $stmt_fetch_profile->get_result();
$profile = $result->fetch_assoc();

$cso_name = $profile['cso_name'];

$sql_fetch_chairperson = "
    SELECT *
    FROM cso_chairperson
    WHERE cso_name = ?";
$stmt_fetch_chairperson = $conn->prepare($sql_fetch_chairperson);
$stmt_fetch_chairperson->bind_param("s", $cso_name);
$stmt_fetch_chairperson->execute();
$result_chairperson = $stmt_fetch_chairperson->get_result();
$chairperson = $result_chairperson->fetch_assoc();


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_image"])) {
    $current_image = $profile['profile_image'];

    $sql_delete_image = "UPDATE cso_representative SET profile_image = NULL WHERE id = ?";
    $stmt_delete_image = $conn->prepare($sql_delete_image);
    $stmt_delete_image->bind_param("i", $user_id);
    $stmt_delete_image->execute();

    if (file_exists($current_image) && $current_image != 'uploads/default.png') {
        unlink($current_image);
    }

    header("Location: user_profile.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $target_dir = "profile/user/";
    $target_file = $target_dir . basename($_FILES["profileImage"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    $check = getimagesize($_FILES["profileImage"]["tmp_name"]);
    if ($check !== false) {
        $uploadOk = 1;
    } else {
        $_SESSION['status'] = "File is not an image.";
        $uploadOk = 0;
    }

    if ($_FILES["profileImage"]["size"] > 5000000) {
        $_SESSION['status'] = "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        $_SESSION['status'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }

    if ($uploadOk == 0) {
        $_SESSION['status'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
    } else {
        if (move_uploaded_file($_FILES["profileImage"]["tmp_name"], $target_file)) {
            $sql_update_image = "UPDATE cso_representative SET profile_image = ? WHERE id = ?";
            $stmt_update_image = $conn->prepare($sql_update_image);
            $stmt_update_image->bind_param("si", $target_file, $user_id);
            $stmt_update_image->execute();
            $_SESSION['success'] = "Profile image updated successfully.";
            header("Location: user_profile.php");
            exit();
        } else {
            $_SESSION['status'] = "Sorry, there was an error uploading your file.";
            
        }
    }
}
?>
<?php
include ('user_include/header.php');
include ('user_include/navbar.php');
?>

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

    .profile-image img {
        display: block;
        margin-left: auto;
        margin-right: auto;
        border-radius: 50%;
        width: 150px;
        height: 150px;
    }

    .btn-icon {
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
    }
    h5{
        color: #0A593A;
    }
</style>
<div id="wrapper">
<div class="container-fluid">
    <div class="row">
            <div class="col-md-12">
                <h2>Profile</h2>
            </div>
        </div>
        <div class="yellow-line"></div>
        <br>

    <?php 
    if (isset($_SESSION['success'])) {
        echo $_SESSION['success'];
        unset($_SESSION['success']);
    }
    ?>
    <div class="profile-image text-center">
        <br>
        <?php
        $profileImagePath = (!empty($profile['profile_image']) && file_exists($profile['profile_image'])) 
            ? $profile['profile_image'] 
            : 'profile/default.jpg';
        ?>
        <img id="profileImagePreview" src="<?php echo $profileImagePath; ?>" alt="Profile Image">
        <form action="user_profile.php" method="post" enctype="multipart/form-data" class="d-inline-block mt-2">
            <input type="file" name="profileImage" id="profileImage" required onchange="previewImage(event)">
            <button type="submit" name="upload_image" class="btn"
                style="background-color:  rgb(1, 82, 51); color: white;">Upload</button>
        </form>
        <form action="user_profile.php" method="post" class="d-inline-block mt-2">
            <button type="submit" name="delete_image" class="btn btn-danger btn-icon">
                <i class="fas fa-trash-alt"></i>
            </button>
        </form>
        <br>
        <p class="text-muted mt-2"><i>Choose an image file to upload as your profile picture.</i></p>
    </div>
    <br>
    <hr>
    <?php
    if (isset($_SESSION['status'])) {
        echo "<p class='status'>" . $_SESSION['status'] . "</p>";
        unset($_SESSION['status']);
    }
    if (isset($_SESSION['success'])) {
        echo "<p class='success'>" . $_SESSION['success'] . "</p>";
        unset($_SESSION['success']);
    }
    ?>
    <form id="profileForm" action="profile_edit.php" method="POST">
        <div class="form-section">
            <h5><strong>Representative Information</strong></h5>
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label for="last_name">Last Name <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="text" class="form-control" id="last_name" name="last_name"
                        value="<?php echo $profile['last_name'] ?? ''; ?>" readonly>
                </div>
                <div class="form-group col-md-3">
                    <label for="first_name">First Name <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="text" class="form-control" id="first_name" name="first_name"
                        value="<?php echo $profile['first_name'] ?? ''; ?>" readonly>
                </div>
                <div class="form-group col-md-3">
                    <label for="middle_name">Middle Name <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="text" class="form-control" id="middle_name" name="middle_name"
                        value="<?php echo $profile['middle_name'] ?? ''; ?>" readonly>
                </div>

                <div class="form-group col-md-3">
                    <label for="suffix">Suffix</label>
                    <input type="text" class="form-control" id="suffix" name="suffix"
                        value="<?php echo $profile['suffix'] ?? ''; ?>" readonly>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="birthday">Birthday <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="date" class="form-control" id="birthday" name="birthday"
                        value="<?php echo $profile['birthday'] ?? ''; ?>" readonly>
                </div>
                <div class="form-group col-md-6">
                    <label for="birth_place">Birth Place <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="text" class="form-control" id="birth_place" name="birth_place"
                        value="<?php echo $profile['birth_place'] ?? ''; ?>" readonly>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="nationality">Nationality <span style="color:red; font-weight:bold;">*</span></label>
                    <select id="nationality" name="nationality" class="form-control" readonly>
                        <option value="" disabled selected>Select your nationality</option>
                        <option value="Filipino" <?php echo ($profile['nationality'] ?? '') === 'Filipino' ? 'selected' : ''; ?>>Filipino</option>
                        <option value="American" <?php echo ($profile['nationality'] ?? '') === 'American' ? 'selected' : ''; ?>>American</option>
                        <option value="Canadian" <?php echo ($profile['nationality'] ?? '') === 'Canadian' ? 'selected' : ''; ?>>Canadian</option>
                        <option value="British" <?php echo ($profile['nationality'] ?? '') === 'British' ? 'selected' : ''; ?>>British</option>
                        <option value="Australian" <?php echo ($profile['nationality'] ?? '') === 'Australian' ? 'selected' : ''; ?>>Australian</option>
                        <option value="Indian" <?php echo ($profile['nationality'] ?? '') === 'Indian' ? 'selected' : ''; ?>>Indian</option>
                        <option value="Chinese" <?php echo ($profile['nationality'] ?? '') === 'Chinese' ? 'selected' : ''; ?>>Chinese</option>
                        <option value="Japanese" <?php echo ($profile['nationality'] ?? '') === 'Japanese' ? 'selected' : ''; ?>>Japanese</option>
                        <option value="German" <?php echo ($profile['nationality'] ?? '') === 'German' ? 'selected' : ''; ?>>German</option>
                        <option value="French" <?php echo ($profile['nationality'] ?? '') === 'French' ? 'selected' : ''; ?>>French</option>
                        <option value="Other" <?php echo ($profile['nationality'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label for="religion">Religion <span style="color:red; font-weight:bold;">*</span></label>
                    <select id="religion" name="religion" class="form-control" readonly>
                        <option value="" disabled selected>Select your religion</option>
                        <option value="Christianity" <?php echo ($profile['religion'] ?? '') === 'Christianity' ? 'selected' : ''; ?>>Christianity</option>
                        <option value="Islam" <?php echo ($profile['religion'] ?? '') === 'Islam' ? 'selected' : ''; ?>>
                            Islam</option>
                        <option value="Hinduism" <?php echo ($profile['religion'] ?? '') === 'Hinduism' ? 'selected' : ''; ?>>Hinduism</option>
                        <option value="Buddhism" <?php echo ($profile['religion'] ?? '') === 'Buddhism' ? 'selected' : ''; ?>>Buddhism</option>
                        <option value="Judaism" <?php echo ($profile['religion'] ?? '') === 'Judaism' ? 'selected' : ''; ?>>Judaism</option>
                        <option value="Sikhism" <?php echo ($profile['religion'] ?? '') === 'Sikhism' ? 'selected' : ''; ?>>Sikhism</option>
                        <option value="Atheism" <?php echo ($profile['religion'] ?? '') === 'Atheism' ? 'selected' : ''; ?>>Atheism</option>
                        <option value="Agnosticism" <?php echo ($profile['religion'] ?? '') === 'Agnosticism' ? 'selected' : ''; ?>>Agnosticism</option>
                        <option value="Other" <?php echo ($profile['religion'] ?? '') === 'Other' ? 'selected' : ''; ?>>
                            Other</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="sex">Sex at Birth <span style="color:red; font-weight:bold;">*</span></label>
                    <select id="sex" name="sex" class="form-control" readonly>
                        <option value="">- Select Sex -</option>
                        <option value="Male" <?php echo ($profile['sex'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male
                        </option>
                        <option value="Female" <?php echo ($profile['sex'] ?? '') === 'Female' ? 'selected' : ''; ?>>
                            Female</option>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label for="civil_status">Civil Status <span style="color:red; font-weight:bold;">*</span></label>
                    <select id="civil_status" name="civil_status" class="form-control" readonly>
                        <option value="">- Select Civil Status -</option>
                        <option value="Single" <?php echo ($profile['civil_status'] ?? '') === 'Single' ? 'selected' : ''; ?>>Single</option>
                        <option value="Married" <?php echo ($profile['civil_status'] ?? '') === 'Married' ? 'selected' : ''; ?>>Married</option>
                        <option value="Legally Separated" <?php echo ($profile['civil_status'] ?? '') === 'Legally Separated' ? 'selected' : ''; ?>>Legally Separated</option>
                        <option value="Widowed" <?php echo ($profile['civil_status'] ?? '') === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="mobile_number">Mobile Number <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="text" id="mobile_number" class="form-control" name="mobile_number"
                        value="<?php echo $profile['mobile_number'] ?? ''; ?>" readonly>
                </div>
                <div class="form-group col-md-6">
                    <label for="telephone_number">Telephone Number</label>
                    <input type="text" id="telephone_number" class="form-control" name="telephone_number"
                        value="<?php echo $profile['telephone_number'] ?? ''; ?>" readonly>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="email">Email Address <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="email" class="form-control" id="email" name="email"
                        value="<?php echo $profile['email'] ?? ''; ?>" readonly>
                </div>
            </div>
        </div>

        <br>
        <div class="row">
            <div class="col text-right">
                <div class="form-buttons">
                    <a href="profile_edit.php?id=<?php echo $profile['id']; ?>" class="btn btn-custom">Edit Profile</a>
                </div>
                <br>
            </div>
        </div>

        <hr>
        <div class="form-section">
            <h5><strong>Office Information</strong></h5>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="cso_name">Name</label>
                    <input type="text" class="form-control" id="cso_name" name="cso_name"
                        value="<?php echo $profile['cso_name'] ?? ''; ?>" readonly>
                </div>
                <div class="form-group col-md-6">
                    <label for="chairperson_cso_address">Registered Address</label>
                    <input type="text" class="form-control" id="chairperson_cso_address" name="cso_address"
                        value="<?php     echo $chairperson['cso_address'] ?? ''; ?>" readonly>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="region">Region</label>
                    <input type="text" class="form-control" id="region" name="region"
                        value="<?php echo $chairperson['region'] ?? ''; ?>" readonly>
                </div>
                <div class="form-group col-md-4">
                    <label for="province">Province</label>
                    <input type="text" class="form-control" id="province" name="province"
                        value="<?php echo $chairperson['province'] ?? ''; ?>" readonly>
                </div>
                <div class="form-group col-md-4">
                    <label for="city">City</label>
                    <input type="text" class="form-control" id="city" name="city"
                        value="<?php echo $chairperson['city'] ?? ''; ?>" readonly>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="barangay">Barangay</label>
                    <input type="text" class="form-control" id="barangay" name="barangay"
                        value="<?php echo $chairperson['barangay'] ?? ''; ?>" readonly>
                </div>
                <div class="form-group col-md-4">
                    <label for="street">Street</label>
                    <input type="text" class="form-control" id="street" name="street"
                        value="<?php echo $chairperson['street'] ?? ''; ?>" readonly>
                </div>
                <div class="form-group col-md-4">
                    <label for="zip_code">Zip Code</label>
                    <input type="text" class="form-control" id="zip_code" name="zip_code"
                        value="<?php echo $chairperson['zip_code'] ?? ''; ?>" readonly>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="office_telephone_number">Telephone Number</label>
                    <input type="text" id="office_telephone_number" class="form-control" name="office_telephone_number"
                        value="<?php echo $chairperson['office_telephone_number'] ?? ''; ?>" readonly>
                </div>
            </div>
        </div>

        <br>
        <hr>
        <div class="form-section">
            <h5><strong>Chairperson Information</strong></h5>
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label for="last_name">Last Name</label>
                    <input type="text" class="form-control" id="last_name" name="last_name"
                        value="<?php echo $chairperson['last_name'] ?? ''; ?>" readonly>
                </div>
                <div class="form-group col-md-3">
                    <label for="first_name">First Name</label>
                    <input type="text" class="form-control" id="first_name" name="first_name"
                        value="<?php echo $chairperson['first_name'] ?? ''; ?>" readonly>
                </div>
                <div class="form-group col-md-3">
                    <label for="middle_name">Middle Name</label>
                    <input type="text" class="form-control" id="middle_name" name="middle_name"
                        value="<?php echo $chairperson['middle_name'] ?? ''; ?>" readonly>
                </div>
                <div class="form-group col-md-3">
                    <label for="suffix">Suffix</label>
                    <input type="text" class="form-control" id="suffix" name="suffix"
                        value="<?php echo $chairperson['suffix'] ?? ''; ?>" readonly>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="sex">Sex at Birth</label>
                    <select id="sex" name="sex" class="form-control" disabled>
                        <option value="">- Select Sex -</option>
                        <option value="Male" <?php echo ($chairperson['sex'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male
                        </option>
                        <option value="Female" <?php echo ($chairperson['sex'] ?? '') === 'Female' ? 'selected' : ''; ?>>
                            Female</option>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label for="civil_status">Civil Status</label>
                    <select id="civil_status" name="civil_status" class="form-control" disabled>
                        <option value="">- Select Civil Status -</option>
                        <option value="Single" <?php echo ($chairperson['civil_status'] ?? '') === 'Single' ? 'selected' : ''; ?>>Single</option>
                        <option value="Married" <?php echo ($chairperson['civil_status'] ?? '') === 'Married' ? 'selected' : ''; ?>>Married</option>
                        <option value="Legally Separated" <?php echo ($chairperson['civil_status'] ?? '') === 'Legally Separated' ? 'selected' : ''; ?>>Legally Separated</option>
                        <option value="Widowed" <?php echo ($chairperson['civil_status'] ?? '') === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="mobile_number">Mobile Number</label>
                    <input type="text" id="mobile_number" class="form-control" name="mobile_number"
                        value="<?php echo $chairperson['mobile_number'] ?? ''; ?>" readonly>
                </div>
                <div class="form-group col-md-6">
                    <label for="telephone_number">Telephone Number</label>
                    <input type="text" id="telephone_number" class="form-control" name="telephone_number"
                        value="<?php echo $chairperson['telephone_number'] ?? ''; ?>" readonly>
                </div>
            </div>
        </div>
        <br>
    </form>
</div>
</div>
<script>
    function previewImage(event) {
        var reader = new FileReader();
        reader.onload = function () {
            var output = document.getElementById('profileImagePreview');
            output.src = reader.result;
        }
        reader.readAsDataURL(event.target.files[0]);
    }
</script>

<?php
include ('user_include/script.php');
?>