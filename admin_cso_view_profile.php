<?php
ob_start();

include('admin_include/header.php');
include('admin_include/navbar.php');
include('include/db_connect.php');

$emailErr = "";
$imageErr = "";

$id = $_GET['id'] ?? '';
$user_type = $_GET['usertype'] ?? '';

if (empty($id) || $user_type !== 'cso') {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Invalid user information.</div></div>";
    include('admin_include/script.php');
    exit;
}

$query = "SELECT *, CONCAT(first_name, ' ', last_name, IFNULL(CONCAT(' ', suffix), '')) AS full_name FROM cso_chairperson WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>User not found.</div></div>";
    include('admin_include/script.php');
    exit;
}

$chairperson = ($user_type === 'cso') ? $user : [];

// Store for reuse
$cso_name = $user['cso_name'];
$first_name = $user['first_name'];
$last_name = $user['last_name'];
$chairperson_name = $first_name . ' ' . $last_name;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"] ?? '';
    $emailErr = '';

    // Validate Email
    if (empty($email)) {
        $emailErr = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailErr = "Invalid email format";
    }

    if (empty($emailErr)) {
        // Update Profile Info including both files in one query
        $sql = "UPDATE cso_chairperson SET status=?, verified_at=NOW()
            WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $_POST['status'], $id
        );

        if ($stmt->execute()) {
            $_SESSION['success'] = "Profile updated successfully!";
            header("Location: admin_cso_users.php?id=" . $id . "&usertype=" . $user_type);
            exit();
        }
        $stmt->close();
    }
}
ob_end_flush();
?>


<style>
    .container-fluid { 
        padding: 20px; 
    }
    h5 {
        color:#0A593A;
    }
    .btn-customs{
        background-color: #0A593A;
        color: white;
    }
    .profile-image-container {
        width: 100%;
        text-align: center; 
        margin-bottom: 30px;
    }

    .profile-image-container img {
        width: 150px;
        height: 150px;
        object-fit: cover; 
        border-radius: 50%; /* Makes the image circular */
        margin: 0 auto;
        display: block;
    }

    .btn-back {
        background-color: #0A593A;
        color: white;
        border: none;
        padding: 10px 16px;
        margin-bottom: 20px;
        border-radius: 5px;
        text-decoration: none;
    }

    .btn-back:hover {
        background-color: #0A593A;
        color: white;
        border: none;
        text-decoration: none;
    }

    .btn-customs:hover {
        background-color: #0A593A;
        color: white;
    }
</style>

<div class="container">
    <!-- Profile image section -->
    <div class="profile-image-container">
        <img id="preview-image" src="<?php echo htmlspecialchars(!empty($chairperson['profile_image']) ? $chairperson['profile_image'] : 'profile/default.jpg'); ?>" alt="Profile Image">
    </div>
    
    <!-- Form content -->
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . ($_GET['id'] ?? '') . '&usertype=cso'); ?>" enctype="multipart/form-data">
        <div class="form-group">
            <span class="text-danger"><?php echo $imageErr; ?></span>
        </div>
        <h5><strong>CSO Office Information</strong></h5>
        <div class="form-row">
            <div class="form-group col-md-12">
                <label for="cso_name">CSO Name</label>
                <input type="text" class="form-control" id="cso_name" name="cso_name" value="<?php echo $chairperson['cso_name'] ?? ''; ?>" readonly>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="cso_address">CSO Address</label>
                <input type="text" class="form-control" id="cso_address" name="cso_address" value="<?php echo $chairperson['cso_address'] ?? ''; ?>" readonly>
            </div>
            <div class="form-group col-md-3">
                <label for="cso_latitude">Latitude</label>
                <input type="number" class="form-control" id="cso_latitude" name="cso_latitude" value="<?php echo $chairperson['latitude'] ?? ''; ?>" readonly>
            </div>
            <div class="form-group col-md-3">
                <label for="cso_longitude">Longitude</label>
                <input type="number" class="form-control" id="cso_longitude" name="cso_longitude" value="<?php echo $chairperson['longitude'] ?? ''; ?>" readonly>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="region">Region</label>
                <input type="text" class="form-control" id="region" name="region" value="<?php echo $chairperson['region'] ?? ''; ?>" readonly>
            </div>
            <div class="form-group col-md-4">
                <label for="province">Province</label>
                <input type="text" class="form-control" id="province" name="province" value="<?php echo $chairperson['province'] ?? ''; ?>" readonly>
            </div>
            <div class="form-group col-md-4">
                <label for="zip_code">Zip Code</label>
                <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?php echo $chairperson['zip_code'] ?? ''; ?>" readonly>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="city">City</label>
                <input type="text" class="form-control" id="city" name="city" value="<?php echo $chairperson['city'] ?? ''; ?>" readonly>
            </div>
            <div class="form-group col-md-4">
                <label for="barangay">Barangay</label>
                <input type="text" class="form-control" id="barangay" name="barangay" value="<?php echo $chairperson['barangay'] ?? ''; ?>" readonly>
            </div>
            <div class="form-group col-md-4">
                <label for="street">Street</label>
                <input type="text" class="form-control" id="street" name="street" value="<?php echo $chairperson['street'] ?? ''; ?>" readonly>
            </div>
            <div class="form-group col-md-6">
                <label for="office_telephone_number">Telephone Number</label>
                <input type="text" class="form-control" id="office_telephone_number" name="office_telephone_number" value="<?php echo $chairperson['office_telephone_number'] ?? ''; ?>" readonly>
            </div>
            <div class="form-group col-md-6">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo $chairperson['email'] ?? ''; ?>" readonly>
                <input type="hidden" name="email" value="<?php echo $chairperson['email'] ?? ''; ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="certificate_type">Type of Certification</label>
                <input type="text" class="form-control" id="certificate_type" name="certificate_type" value="<?php echo $chairperson['certificate_type'] ?? ''; ?>" readonly>
            </div>
            <div class="form-group col-md-6">
                <label for="certificate_file">Certification File</label><br>
                <?php if (!empty($chairperson['certificate_file'])): ?>
                    <a href="<?php echo htmlspecialchars($chairperson['certificate_file']); ?>" target="_blank">View File</a><br>
                <?php endif; ?>
            </div>
        </div>
        <hr>
        <h5><strong>CSO Chairperson Information</strong></h5>
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="first_name">First Name</label>
                <input type="text" class="form-control" id="first_name" name="first_name"
                    value="<?php echo $chairperson['first_name'] ?? ''; ?>" readonly>
            </div>
            <div class="form-group col-md-3">
                <label for="middle_name">Middle Name</label>
                <input type="text" class="form-control" id="middle_name" name="middle_name"
                    value="<?php echo $chairperson['middle_name'] ?? ''; ?>" readonly>
            </div>
            <div class="form-group col-md-4">
                <label for="last_name">Last Name</label>
                <input type="text" class="form-control" id="last_name" name="last_name"
                    value="<?php echo $chairperson['last_name'] ?? ''; ?>" readonly>
            </div>
            <div class="form-group col-md-1">
                <label for="suffix">Suffix</label>
                <input type="text" class="form-control" id="suffix" name="suffix"
                    value="<?php echo $chairperson['suffix'] ?? ''; ?>" readonly>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-3">
                <label for="sex">Sex</label>
                <input type="text" class="form-control" id="sex" name="sex" value="<?php echo $chairperson['sex'] ?? ''; ?>" readonly>
            </div>
            <div class="form-group col-md-3">
                <label for="birthday">Birthdate</label>
                <input type="date" class="form-control" id="birthday" name="birthday" value="<?php echo htmlspecialchars($chairperson['birthday']) ?? ''; ?>" readonly>
                </div>
            <div class="form-group col-md-6">
                <label for="birth_place">Birthplace</label>
                <input type="text" class="form-control" id="birth_place" name="birth_place"
                    value="<?php echo $chairperson['birth_place'] ?? ''; ?>" readonly>
            </div>
        </div>
        <div class="form-row">
        <div class="form-group col-md-4">
            <label for="civil_status">Civil Status</label>
            <input type="text" class="form-control" id="civil_status" name="civil_status" value="<?php echo $chairperson['civil_status']?>" readonly>
        </div>
        <div class="form-group col-md-4">
            <label for="nationality">Nationality</label>
            <input type="text" class="form-control" id="nationality" name="nationality" value="<?php echo $chairperson['nationality']?>" readonly>
        </div>
        <div class="form-group col-md-4">
            <label for="religion">Religion</label>
            <input type="text" class="form-control" id="religion" name="religion" value="<?php echo $chairperson['religion']?>" readonly>
        </div>
    </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="mobile_number">Mobile Number</label>
                <input type="text" class="form-control" id="mobile_number" name="mobile_number"
                    value="<?php echo $chairperson['mobile_number'] ?? ''; ?>" readonly>
            </div>
            <div class="form-group col-md-6">
                <label for="telephone_number">Telephone Number</label>
                <input type="text" class="form-control" id="telephone_number" name="telephone_number"
                    value="<?php echo $chairperson['telephone_number'] ?? ''; ?>" readonly>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="gov_id_type">Government ID Type</label>
                <input type="text" class="form-control" id="gov_id_type" name="gov_id_type" value="<?php echo $chairperson['gov_id_type']?>" readonly>
            </div>
            <div class="form-group col-md-6">
                <label for="gov_id_file" class="d-block">Government ID File</label>
                <?php if (!empty($chairperson['gov_id_file'])): ?>
                    <a href="<?php echo htmlspecialchars($chairperson['gov_id_file']); ?>" target="_blank">View File</a><br>
                <?php endif; ?>
            </div>
        </div>
        <hr>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="status">Status</label>
                <select class="form-control" id="status" name="status" required>
                    <?php
                        $statusOptions = ['Unverified', 'Verified'];
                        $currentStatus = $chairperson['status'] ?? '';
                        if (!in_array($currentStatus, $statusOptions) && $currentStatus !== '') {
                            echo "<option value=\"$currentStatus\" selected>$currentStatus</option>";
                        }
                        foreach ($statusOptions as $option) {
                            $selected = $currentStatus === $option ? 'selected' : '';
                            echo "<option value=\"$option\" $selected>$option</option>";
                        }
                    ?>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="date_created">Date Registered</label>
                <input type="text" class="form-control" id="date_created" name="date_created" value="<?php echo isset($chairperson['date_created']) ? date('F d, Y', strtotime($chairperson['date_created'])) : ''; ?>" readonly>
            </div>
        </div>
        <br><br>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-customs mr-2">Save Changes</button>
            <a href="admin_cso_users.php" class="btn btn-customs">Back</a>    
        </div>
        <br><br>
        <script>
            document.querySelector('form').onsubmit = function(event) {
                // Confirm the update
                var confirmation = confirm("Save changes in the profile of <?php echo htmlspecialchars($cso_name); ?>?");
                
                if (!confirmation) {
                    event.preventDefault();  // Prevent the form from being submitted if not confirmed
                } else {
                    alert("CSO account verification status updated successfully!");  // Display success message after submission
                }
            }
        </script>
    </form>
</div>

<?php include('admin_include/script.php'); ?>
<script>
    document.getElementById("profile_image").addEventListener("change", function() {
        var preview = document.getElementById('preview-image');
        var file = document.querySelector('input[type=file]').files[0];
        var reader = new FileReader();

        reader.onloadend = function() {
            preview.src = reader.result;
        }

        if (file) {
            reader.readAsDataURL(file);
        } else {
            preview.src = "<?php echo htmlspecialchars($chairperson['profile_image'] ?? 'profile/default.jpg'); ?>";
        }
    });
</script>