<?php
session_start();
include ('include/db_connect.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user data from the session
$user_id = $_SESSION['user_id'];

// Fetch the profile information from the database
$sql_fetch_profile = "SELECT * FROM cso_chairperson WHERE id = ?";
$stmt_fetch_profile = $conn->prepare($sql_fetch_profile);
$stmt_fetch_profile->bind_param("i", $user_id);
$stmt_fetch_profile->execute();
$result = $stmt_fetch_profile->get_result();
$chairperson = $result->fetch_assoc();

// Handle form submission for profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_profile"])) {
    $email = $_POST['email'];
    $cso_name = $_POST['cso_name'];
    $cso_address = $_POST['cso_address'];
    $region = $_POST['region'];
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
    $mobile_number = $_POST['mobile_number'];
    $telephone_number = $_POST['telephone_number'];

    $sql_update_profile = "UPDATE cso_chairperson SET email=?, cso_name=?, cso_address=?, region=?, province=?, city=?, barangay=?, street=?, zip_code=?, last_name=?, first_name=?, middle_name=?, suffix=?, birthday=?, birth_place=?, nationality=?, religion=?, sex=?, civil_status=?, mobile_number=?, telephone_number=? WHERE id=?";
    $stmt_update_profile = $conn->prepare($sql_update_profile);
    $stmt_update_profile->bind_param("sssssssssssssssssssssi", $email, $cso_name, $cso_address, $region, $province, $city, $barangay, $street, $zip_code, $last_name, $first_name, $middle_name, $suffix, $birthday, $birth_place, $nationality, $religion, $sex, $civil_status, $mobile_number, $telephone_number, $user_id);
    $stmt_update_profile->execute();

    // Set success message for profile update
    $_SESSION['success'] = "<span style='color: green; font-weight: bold;'>Profile updated successfully!</span>";
    header("Location: cso_profile.php");
    exit();
}
?>

<?php
include('cso_include/header.php');
include('cso_include/navbar.php');
?>
<style>
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
        font-size: 1.25rem;
    }
    h5{
        color: #0A593A;
    }
</style>

<div id="wrapper">
<div class="container-fluid">
    <div class="row">
            <div class="col-md-12">
                <h2>Edit CSO Profile</h2>
            </div>
        </div>
        <div class="yellow-line"></div>
        <br>
    <?php
    if (isset($_SESSION['message'])) {
        echo "<p class='success'>" . $_SESSION['message'] . "</p>";
        unset($_SESSION['message']);
    }
    ?>
    <form id="profileForm" action="" method="POST">
        <div class="form-section">
            <h5><strong>Account Information</strong></h5>

            <p style="display: none;"><strong>User ID:</strong> <?php echo $_SESSION['user_id']; ?></p>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="email">Email Address <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="email" class="form-control" id="email" name="email"
                        value="<?php echo $chairperson['email'] ?? ''; ?>" required>
                </div>
                <div class="form-group col-md-6">
                    <label for="created_at">Account Created</label>
                    <input type="text" class="form-control" id="created_at" name="created_at"
                        value="<?php echo $chairperson['created_at'] ?? ''; ?>" readonly>
                </div>
            </div>

            <hr>

            <div class="form-section">
            <h5><strong>Main Office Information</strong></h5>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="cso_name">Name <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="text" class="form-control" id="cso_name" name="cso_name"
                        value="<?php echo $chairperson['cso_name'] ?? ''; ?>" readonly>
                </div>
                <div class="form-group col-md-6">
                    <label for="cso_address">Address <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="text" class="form-control" id="cso_address" name="cso_address"
                        value="<?php echo $chairperson['cso_address'] ?? ''; ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="region">Region <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="text" class="form-control" id="region" name="region"
                        value="<?php echo $chairperson['region'] ?? ''; ?>" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="province">Province <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="text" class="form-control" id="province" name="province"
                        value="<?php echo $chairperson['province'] ?? ''; ?>" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="city">City <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="text" class="form-control" id="city" name="city"
                        value="<?php echo $chairperson['city'] ?? ''; ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="barangay">Barangay <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="text" class="form-control" id="barangay" name="barangay"
                        value="<?php echo $chairperson['barangay'] ?? ''; ?>" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="street">Street <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="text" class="form-control" id="street" name="street"
                        value="<?php echo $chairperson['street'] ?? ''; ?>" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="zip_code">Zip Code <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="text" class="form-control" id="zip_code" name="zip_code"
                        value="<?php echo $chairperson['zip_code'] ?? ''; ?>" required>
                </div>
                <div class="form-group col-md-6">
                    <label for="latitude">Latitude <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="number" id="latitude" name="latitude" class="form-control" 
                        value="<?php echo $chairperson['latitude'] ?? ''; ?>" readonly>
                </div>
                <div class="form-group col-md-6">
                    <label for="longitude">Longitude <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="number" id="longitude" name="longitude" class="form-control" 
                        value="<?php echo $chairperson['longitude'] ?? ''; ?>" readonly>
                </div>
                <div class="form-group col-md-12">
                    <label for="office_telephone_number">Telephone Number <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="text" class="form-control" id="office_telephone_number" name="office_telephone_number"
                        value="<?php echo $chairperson['office_telephone_number'] ?? ''; ?>" required>
                </div>
            </div>

            <hr>

            <div class="form-section">
            <h5><strong>Chairperson Information</strong></h5>
            
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label for="last_name">Last Name <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="text" class="form-control" id="last_name" name="last_name"
                        value="<?php echo $chairperson['last_name'] ?? ''; ?>" required>
                </div>
                <div class="form-group col-md-3">
                    <label for="first_name">First Name <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="text" class="form-control" id="first_name" name="first_name"
                        value="<?php echo $chairperson['first_name'] ?? ''; ?>" required>
                </div>
                <div class="form-group col-md-3">
                    <label for="middle_name">Middle Name <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="text" class="form-control" id="middle_name" name="middle_name"
                        value="<?php echo $chairperson['middle_name'] ?? ''; ?>" required>
                </div>
                <div class="form-group col-md-3">
                    <label for="suffix">Suffix</label>
                    <input type="text" class="form-control" id="suffix" name="suffix"
                        value="<?php echo $chairperson['suffix'] ?? ''; ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="birthday">Date of Birth <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="date" class="form-control" id="birthday" name="birthday"
                        value="<?php echo $chairperson['birthday'] ?? ''; ?>" required>
                </div>
                <div class="form-group col-md-6">
                    <label for="birth_place">Place of Birth <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="text" class="form-control" id="birth_place" name="birth_place"
                        value="<?php echo $chairperson['birth_place'] ?? ''; ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label for="nationality">Nationality <span style="color:red; font-weight:bold;">*</span></label>
                    <select id="nationality" name="nationality" class="form-control" required>
                        <option value="" disabled>Select your nationality</option>
                        <option value="Filipino" <?php echo ($chairperson['nationality'] === 'Filipino') ? 'selected' : ''; ?>>Filipino</option>
                        <option value="American" <?php echo ($chairperson['nationality'] === 'American') ? 'selected' : ''; ?>>American</option>
                        <option value="Canadian" <?php echo ($chairperson['nationality'] === 'Canadian') ? 'selected' : ''; ?>>Canadian</option>
                        <option value="British" <?php echo ($chairperson['nationality'] === 'British') ? 'selected' : ''; ?>>
                            British</option>
                        <option value="Australian" <?php echo ($chairperson['nationality'] === 'Australian') ? 'selected' : ''; ?>>Australian</option>
                        <option value="Indian" <?php echo ($chairperson['nationality'] === 'Indian') ? 'selected' : ''; ?>>
                            Indian</option>
                        <option value="Chinese" <?php echo ($chairperson['nationality'] === 'Chinese') ? 'selected' : ''; ?>>
                            Chinese</option>
                        <option value="Japanese" <?php echo ($chairperson['nationality'] === 'Japanese') ? 'selected' : ''; ?>>Japanese</option>
                        <option value="German" <?php echo ($chairperson['nationality'] === 'German') ? 'selected' : ''; ?>>
                            German</option>
                        <option value="French" <?php echo ($chairperson['nationality'] === 'French') ? 'selected' : ''; ?>>
                            French</option>
                        <option value="Other" <?php echo ($chairperson['nationality'] === 'Other') ? 'selected' : ''; ?>>Other
                        </option>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label for="religion">Religion <span style="color:red; font-weight:bold;">*</span></label>
                    <select id="religion" name="religion" class="form-control" required>
                        <option value="" disabled>Select your religion</option>
                        <option value="Christianity" <?php echo ($chairperson['religion'] === 'Christianity') ? 'selected' : ''; ?>>Christianity</option>
                        <option value="Islam" <?php echo ($chairperson['religion'] === 'Islam') ? 'selected' : ''; ?>>Islam
                        </option>
                        <option value="Hinduism" <?php echo ($chairperson['religion'] === 'Hinduism') ? 'selected' : ''; ?>>
                            Hinduism</option>
                        <option value="Buddhism" <?php echo ($chairperson['religion'] === 'Buddhism') ? 'selected' : ''; ?>>
                            Buddhism</option>
                        <option value="Judaism" <?php echo ($chairperson['religion'] === 'Judaism') ? 'selected' : ''; ?>>
                            Judaism</option>
                        <option value="Sikhism" <?php echo ($chairperson['religion'] === 'Sikhism') ? 'selected' : ''; ?>>
                            Sikhism</option>
                        <option value="Atheism" <?php echo ($chairperson['religion'] === 'Atheism') ? 'selected' : ''; ?>>
                            Atheism</option>
                        <option value="Agnosticism" <?php echo ($chairperson['religion'] === 'Agnosticism') ? 'selected' : ''; ?>>Agnosticism</option>
                        <option value="Other" <?php echo ($chairperson['religion'] === 'Other') ? 'selected' : ''; ?>>Other
                        </option>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label for="sex">Sex <span style="color:red; font-weight:bold;">*</span></label>
                    <select id="sex" name="sex" class="form-control" required>
                        <option value="Male" <?php echo ($chairperson['sex'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo ($chairperson['sex'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label for="civil_status">Civil Status <span style="color:red; font-weight:bold;">*</span></label>
                    <select id="civil_status" name="civil_status" class="form-control" required>
                        <option value="Single" <?php echo ($chairperson['civil_status'] == 'Single') ? 'selected' : ''; ?>>Single</option>
                        <option value="Married" <?php echo ($chairperson['civil_status'] == 'Married') ? 'selected' : ''; ?>>Married</option>
                        <option value="Widowed" <?php echo ($chairperson['civil_status'] == 'Widowed') ? 'selected' : ''; ?>>Widowed</option>
                        <option value="Legally Separated" <?php echo ($chairperson['civil_status'] == 'Legally Separated') ? 'selected' : ''; ?>>Legally Separated</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="mobile_number">Mobile Number <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="text" class="form-control" id="mobile_number" name="mobile_number"
                        value="<?php echo $chairperson['mobile_number'] ?? ''; ?>" required>
                </div>
                <div class="form-group col-md-6">
                    <label for="telephone_number">Telephone Number</label>
                    <input type="text" class="form-control" id="telephone_number" name="telephone_number"
                        value="<?php echo $chairperson['telephone_number'] ?? ''; ?>">
                </div>
                <div class="form-group col-md-12">
                    <button type="submit" name="update_profile" class="btn btn-custom">Update Profile</button>
                </div>
            </div>
    </form>
</div>
</div>

<?php
include('cso_include/script.php');
?>
