<?php
ob_start();

include('admin_include/header.php');
include('admin_include/navbar.php');
include('include/db_connect.php');

$emailErr = "";
$imageErr = "";

$id = $_GET['id'] ?? '';
$user_type = $_GET['usertype'] ?? '';

if (empty($id) || $user_type !== 'representative') {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Invalid user information.</div></div>";
    include('admin_include/script.php');
    exit;
}

$query = "SELECT 
    cr.*, 
    cc.cso_name as chairperson_cso_name,
    -- select specific columns you need from cc, but not status
    CONCAT(cr.first_name, ' ', cr.last_name, IFNULL(CONCAT(' ', cr.suffix), '')) AS full_name
FROM 
    cso_representative cr
LEFT JOIN 
    cso_chairperson cc ON cr.cso_name = cc.cso_name
WHERE 
    cr.id = ?";
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

$representative = ($user_type === 'representative') ? $user : [];

// Store for reuse
$cso_name = $user['cso_name'];
$first_name = $user['first_name'];
$last_name = $user['last_name'];
$representative_name = $first_name . ' ' . $last_name;

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
        $sql = "UPDATE cso_representative SET status=?, verified_at=NOW()
            WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si",$_POST['status'], $id
        );

        if ($stmt->execute()) {
            $_SESSION['success'] = "Profile updated successfully!";
            header("Location: admin_representative_users.php?id=" . $id . "&usertype=" . $user_type);
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
        <img id="preview-image" src="<?php echo htmlspecialchars(!empty($representative['profile_image']) ? $representative['profile_image'] : 'profile/default.jpg'); ?>" alt="Profile Image">
    </div>
    
    <!-- Form content -->
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . ($_GET['id'] ?? '') . '&usertype=representative'); ?>" enctype="multipart/form-data">
        <div class="form-group">
            <span class="text-danger"><?php echo $imageErr; ?></span>
        </div>
        <h5><strong>CSO Office Information</strong></h5>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="cso_name">CSO Name</label>
                <input type="text" class="form-control" id="cso_name" name="cso_name" value="<?php echo $representative['cso_name'] ?? ''; ?>" readonly>
            </div>
            <div class="form-group col-md-6">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo $representative['email'] ?? ''; ?>" readonly>
                <input type="hidden" name="email" value="<?php echo $representative['email'] ?? ''; ?>">
            </div>
        </div>
        <hr>
        <h5><strong>CSO Representative Information</strong></h5>
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="first_name">First Name</label>
                <input type="text" class="form-control" id="first_name" name="first_name"
                    value="<?php echo $representative['first_name'] ?? ''; ?>" readonly>
            </div>
            <div class="form-group col-md-3">
                <label for="middle_name">Middle Name</label>
                <input type="text" class="form-control" id="middle_name" name="middle_name"
                    value="<?php echo $representative['middle_name'] ?? ''; ?>" readonly>
            </div>
            <div class="form-group col-md-4">
                <label for="last_name">Last Name</label>
                <input type="text" class="form-control" id="last_name" name="last_name"
                    value="<?php echo $representative['last_name'] ?? ''; ?>" readonly>
            </div>
            <div class="form-group col-md-1">
                <label for="suffix">Suffix</label>
                <input type="text" class="form-control" id="suffix" name="suffix"
                    value="<?php echo $representative['suffix'] ?? ''; ?>" readonly>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-3">
                <label for="sex">Sex</label>
                <input type="text" id="sex" name="sex" class="form-control" value="<?php echo $representative['sex']?>" readonly>
            </div>
            <div class="form-group col-md-3">
                <label for="birthday">Birthdate</label>
                <input type="date" class="form-control" id="birthday" name="birthday" value="<?php echo htmlspecialchars($representative['birthday']) ?? ''; ?>" readonly>
                </div>
            <div class="form-group col-md-6">
                <label for="birth_place">Birthplace</label>
                <input type="text" class="form-control" id="birth_place" name="birth_place" value="<?php echo $representative['birth_place'] ?? ''; ?>" readonly>
            </div>
        </div>
        <div class="form-row">
        <div class="form-group col-md-4">
            <label for="civil_status">Civil Status</label>
            <input type="text" id="civil_status" name="civil_status" class="form-control" value="<?php echo $representative['civil_status']?>" readonly>
        </div>
        <div class="form-group col-md-4">
            <label for="nationality">Nationality</label>
            <input type="text" id="nationality" name="nationality" class="form-control" value="<?php echo $representative['nationality']?>" readonly>
        </div>
        <div class="form-group col-md-4">
            <label for="religion">Religion</label>
            <input type="text" id="religion" name="religion" class="form-control" value="<?php echo $representative['religion']?>" readonly>
        </div>
    </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="mobile_number">Mobile Number</label>
                <input type="text" class="form-control" id="mobile_number" name="mobile_number"
                    value="<?php echo $representative['mobile_number'] ?? ''; ?>" readonly>
            </div>
            <div class="form-group col-md-6">
                <label for="telephone_number">Telephone Number</label>
                <input type="text" class="form-control" id="telephone_number" name="telephone_number"
                    value="<?php echo $representative['telephone_number'] ?? ''; ?>" readonly>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="gov_id_type">Government ID Type</label>
                <input type="text" class="form-control" id="gov_id_type" name="gov_id_type" value="<?php echo $representative['gov_id_type']?>" readonly>
            </div>
            <div class="form-group col-md-6">
                <label for="gov_id_file" class="d-block">Government ID File</label>
                <?php if (!empty($representative['gov_id_file'])): ?>
                    <a href="<?php echo htmlspecialchars($representative['gov_id_file']); ?>" target="_blank">View File</a><br>
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
                        $currentStatus = $representative['status'] ?? '';
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
                <input type="text" class="form-control" id="date_created" name="date_created" value="<?php echo isset($representative['date_created']) ? date('F d, Y', strtotime($representative['date_created'])) : ''; ?>" readonly>
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
                var confirmation = confirm("Save changes in the profile of <?php echo htmlspecialchars($representative_name);?> under <?php echo htmlspecialchars($cso_name); ?>?");
                
                if (!confirmation) {
                    event.preventDefault();  // Prevent the form from being submitted if not confirmed
                } else {
                    alert("Representative account verification status updated successfully!");  // Display success message after submission
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
            preview.src = "<?php echo htmlspecialchars($representative['profile_image'] ?? 'profile/default.jpg'); ?>";
        }
    });
</script>