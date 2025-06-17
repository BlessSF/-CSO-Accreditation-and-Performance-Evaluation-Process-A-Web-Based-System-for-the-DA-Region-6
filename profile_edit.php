<?php
session_start();
include ('include/db_connect.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: user_profile.php");
    exit();
}

$user_id = $_GET['id'];

$sql_fetch_profile = "SELECT * FROM cso_representative WHERE id = ?";
$stmt_fetch_profile = $conn->prepare($sql_fetch_profile);
$stmt_fetch_profile->bind_param("i", $user_id);
$stmt_fetch_profile->execute();
$result = $stmt_fetch_profile->get_result();
$profile = $result->fetch_assoc();

if (!$profile) {
    header("Location: user_profile.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
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

    $sql_update_profile = "UPDATE cso_representative SET 
                            first_name = ?, 
                            last_name = ?, 
                            middle_name = ?, 
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
        "ssssssssssssi",
        $first_name,
        $last_name,
        $middle_name,
        $suffix,
        $birthday,
        $birth_place,
        $nationality,
        $religion,
        $sex,
        $civil_status,
        $mobile_number,
        $telephone_number,
        $user_id
    );

    if ($stmt_update_profile->execute()) {
        $_SESSION['success'] = "<span style='color: green; font-weight: bold;'>Profile updated successfully!</span>";
        header("Location: user_profile.php");
        exit();
    } else {
        echo "Error updating profile: " . $conn->error;
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
                <h2>Edit Representative Profile</h2>
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
    <form id="editProfileForm" action="profile_edit.php?id=<?php echo $user_id; ?>" method="POST">
        <div class="form-section">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label for="first_name">First Name <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="text" class="form-control" id="first_name" name="first_name"
                        value="<?php echo $profile['first_name']; ?>" required>
                </div>
                <div class="form-group col-md-3">
                    <label for="last_name">Last Name <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="text" class="form-control" id="last_name" name="last_name"
                        value="<?php echo $profile['last_name']; ?>" required>
                </div>
                <div class="form-group col-md-3">
                    <label for="middle_name">Middle Name <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="text" class="form-control" id="middle_name" name="middle_name"
                        value="<?php echo $profile['middle_name']; ?>" required>
                </div>
                <div class="form-group col-md-3">
                    <label for="suffix">Suffix</label>
                    <input type="text" class="form-control" id="suffix" name="suffix"
                        value="<?php echo $profile['suffix']; ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="birthday">Birthday <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="date" class="form-control" id="birthday" name="birthday"
                        value="<?php echo $profile['birthday']; ?>" required>
                </div>
                <div class="form-group col-md-6">
                    <label for="birth_place">Birth Place <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="text" class="form-control" id="birth_place" name="birth_place"
                        value="<?php echo $profile['birth_place']; ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="nationality">Nationality <span style="color:red; font-weight:bold;">*</span></label>
                    <select id="nationality" name="nationality" class="form-control" required>
                        <option value="" disabled>Select your nationality</option>
                        <option value="Filipino" <?php echo ($profile['nationality'] === 'Filipino') ? 'selected' : ''; ?>>Filipino</option>
                        <option value="American" <?php echo ($profile['nationality'] === 'American') ? 'selected' : ''; ?>>American</option>
                        <option value="Canadian" <?php echo ($profile['nationality'] === 'Canadian') ? 'selected' : ''; ?>>Canadian</option>
                        <option value="British" <?php echo ($profile['nationality'] === 'British') ? 'selected' : ''; ?>>
                            British</option>
                        <option value="Australian" <?php echo ($profile['nationality'] === 'Australian') ? 'selected' : ''; ?>>Australian</option>
                        <option value="Indian" <?php echo ($profile['nationality'] === 'Indian') ? 'selected' : ''; ?>>
                            Indian</option>
                        <option value="Chinese" <?php echo ($profile['nationality'] === 'Chinese') ? 'selected' : ''; ?>>
                            Chinese</option>
                        <option value="Japanese" <?php echo ($profile['nationality'] === 'Japanese') ? 'selected' : ''; ?>>Japanese</option>
                        <option value="German" <?php echo ($profile['nationality'] === 'German') ? 'selected' : ''; ?>>
                            German</option>
                        <option value="French" <?php echo ($profile['nationality'] === 'French') ? 'selected' : ''; ?>>
                            French</option>
                        <option value="Other" <?php echo ($profile['nationality'] === 'Other') ? 'selected' : ''; ?>>Other
                        </option>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label for="religion">Religion <span style="color:red; font-weight:bold;">*</span></label>
                    <select id="religion" name="religion" class="form-control" required>
                        <option value="" disabled>Select your religion</option>
                        <option value="Christianity" <?php echo ($profile['religion'] === 'Christianity') ? 'selected' : ''; ?>>Christianity</option>
                        <option value="Islam" <?php echo ($profile['religion'] === 'Islam') ? 'selected' : ''; ?>>Islam
                        </option>
                        <option value="Hinduism" <?php echo ($profile['religion'] === 'Hinduism') ? 'selected' : ''; ?>>
                            Hinduism</option>
                        <option value="Buddhism" <?php echo ($profile['religion'] === 'Buddhism') ? 'selected' : ''; ?>>
                            Buddhism</option>
                        <option value="Judaism" <?php echo ($profile['religion'] === 'Judaism') ? 'selected' : ''; ?>>
                            Judaism</option>
                        <option value="Sikhism" <?php echo ($profile['religion'] === 'Sikhism') ? 'selected' : ''; ?>>
                            Sikhism</option>
                        <option value="Atheism" <?php echo ($profile['religion'] === 'Atheism') ? 'selected' : ''; ?>>
                            Atheism</option>
                        <option value="Agnosticism" <?php echo ($profile['religion'] === 'Agnosticism') ? 'selected' : ''; ?>>Agnosticism</option>
                        <option value="Other" <?php echo ($profile['religion'] === 'Other') ? 'selected' : ''; ?>>Other
                        </option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="sex">Sex at Birth <span style="color:red; font-weight:bold;">*</span></label>
                    <select id="sex" name="sex" class="form-control" required>
                        <option value="">- Select Sex -</option>
                        <option value="Male" <?php echo ($profile['sex'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo ($profile['sex'] === 'Female') ? 'selected' : ''; ?>>Female
                        </option>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label for="civil_status">Civil Status <span style="color:red; font-weight:bold;">*</span></label>
                    <select id="civil_status" name="civil_status" class="form-control" required>
                        <option value="">- Select Civil Status -</option>
                        <option value="Single" <?php echo ($profile['civil_status'] === 'Single') ? 'selected' : ''; ?>>
                            Single</option>
                        <option value="Married" <?php echo ($profile['civil_status'] === 'Married') ? 'selected' : ''; ?>>
                            Married</option>
                        <option value="Divorced" <?php echo ($profile['civil_status'] === 'Divorced') ? 'selected' : ''; ?>>Divorced</option>
                        <option value="Widowed" <?php echo ($profile['civil_status'] === 'Widowed') ? 'selected' : ''; ?>>
                            Widowed</option>
                        <option value="Separated" <?php echo ($profile['civil_status'] === 'Separated') ? 'selected' : ''; ?>>Separated</option>
                        <option value="Annulled" <?php echo ($profile['civil_status'] === 'Annulled') ? 'selected' : ''; ?>>Annulled</option>
                        <option value="Others" <?php echo ($profile['civil_status'] === 'Others') ? 'selected' : ''; ?>>
                            Others</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="mobile_number">Mobile Number <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="text" class="form-control" id="mobile_number" name="mobile_number"
                        value="<?php echo $profile['mobile_number']; ?>" required>
                </div>
                <div class="form-group col-md-6">
                    <label for="telephone_number">Telephone Number</label>
                    <input type="text" class="form-control" id="telephone_number" name="telephone_number"
                        value="<?php echo $profile['telephone_number']; ?>">
                </div>
                <div class="form-group col-md-12">
                <button type="submit" class="btn btn-custom">Save Changes</button>  
            </div>
            </div>
        </form>
</div>
</div>
<?php include ('user_include/script.php'); ?>