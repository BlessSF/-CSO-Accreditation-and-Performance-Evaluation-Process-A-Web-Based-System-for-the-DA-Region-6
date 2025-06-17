<?php
include('include/db_connect.php');
session_start();

// Fetch project ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "Invalid Project ID.";
    header("Location: admin_projects_list.php");
    exit();
}

$project_id = intval($_GET['id']);

// Fetch project details
$sql_fetch_project = "SELECT * FROM projects WHERE id = $project_id";
$result = mysqli_query($conn, $sql_fetch_project);

if ($result && mysqli_num_rows($result) > 0) {
    $project = mysqli_fetch_assoc($result);
} else {
    $_SESSION['message'] = "Project not found.";
    header("Location: admin_projects_list.php");
    exit();
}

// Fetch current designated CSOs
$sql_current_csos = "SELECT cso_id FROM project_cso WHERE project_id = $project_id";
$current_csos_result = mysqli_query($conn, $sql_current_csos);
$current_cso_ids = [];
if ($current_csos_result) {
    while ($row = mysqli_fetch_assoc($current_csos_result)) {
        $current_cso_ids[] = $row['cso_id'];
    }
}

// Fetch all available CSOs
$sql_all_csos = "SELECT id, cso_name FROM cso_chairperson";
$all_csos_result = mysqli_query($conn, $sql_all_csos);
$all_csos = [];
if ($all_csos_result) {
    while ($row = mysqli_fetch_assoc($all_csos_result)) {
        $all_csos[] = $row;
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Preserve existing file path unless replaced
    $file_path = isset($project['file_path']) ? $project['file_path'] : '';

    // Sanitize inputs
    $title         = mysqli_real_escape_string($conn, $_POST['title']);
    $project_desc  = mysqli_real_escape_string($conn, $_POST['project_desc']);
    $budget        = mysqli_real_escape_string($conn, $_POST['budget']);
    $cso_ids       = $_POST['cso_id']; // Array of CSO IDs
    $start_date    = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date      = mysqli_real_escape_string($conn, $_POST['end_date']);
    $duration      = mysqli_real_escape_string($conn, $_POST['duration']);
    $location      = mysqli_real_escape_string($conn, $_POST['location']);
    $latitude      = mysqli_real_escape_string($conn, $_POST['latitude']);
    $longitude     = mysqli_real_escape_string($conn, $_POST['longitude']);
    $objectives    = mysqli_real_escape_string($conn, $_POST['objectives']);
    $status        = mysqli_real_escape_string($conn, $_POST['status']);

    // Additional fields you’re now editing:
    $outcomes        = mysqli_real_escape_string($conn, $_POST['outcomes']);
    $milestones      = mysqli_real_escape_string($conn, $_POST['milestones']);
    $risks           = mysqli_real_escape_string($conn, $_POST['risks']);
    $team            = mysqli_real_escape_string($conn, $_POST['team']);
    $proposal_status = mysqli_real_escape_string($conn, $_POST['proposal_status']);
    $funding_status  = mysqli_real_escape_string($conn, $_POST['funding_status']);
    $date_submitted  = mysqli_real_escape_string($conn, $_POST['date_submitted']);

    // Handle file uploads (if a new file is provided)
    if (!empty($_FILES['file']['name'])) {
        $file_name = $_FILES['file']['name'];
        $file_tmp  = $_FILES['file']['tmp_name'];

        $upload_dir = 'projects/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        if (move_uploaded_file($file_tmp, $upload_dir . $file_name)) {
            $file_path = $upload_dir . $file_name;
        } else {
            $_SESSION['message'] = "Failed to upload the file.";
            header("Location: " . $_SERVER['PHP_SELF'] . "?id=$project_id");
            exit();
        }
    }

    // Ensure end date is after start date
    if (strtotime($end_date) <= strtotime($start_date)) {
        $_SESSION['message'] = "End date must be after the start date.";
        header("Location: " . $_SERVER['PHP_SELF'] . "?id=$project_id");
        exit();
    }

    // Update project in the database
    // Make sure your projects table has columns for the new fields: outcomes, milestones, risks, team, etc.
    $sql_update = "
        UPDATE projects
        SET 
            title            = '$title',
            project_desc     = '$project_desc',
            budget           = '$budget',
            start_date       = '$start_date',
            end_date         = '$end_date',
            duration         = '$duration',
            location         = '$location',
            latitude         = '$latitude',
            longitude        = '$longitude',
            objectives       = '$objectives',
            status           = '$status',
            file_path        = '$file_path',
            outcomes         = '$outcomes',
            milestones       = '$milestones',
            risks            = '$risks',
            team             = '$team',
            proposal_status  = '$proposal_status',
            funding_status   = '$funding_status',
            date_submitted   = '$date_submitted'
        WHERE id = $project_id
    ";

    if (mysqli_query($conn, $sql_update)) {
        // Update CSO associations
        mysqli_query($conn, "DELETE FROM project_cso WHERE project_id = $project_id");
        foreach ($cso_ids as $cso_id) {
            $cso_id = intval($cso_id); // Sanitize
            mysqli_query($conn, "INSERT INTO project_cso (project_id, cso_id) VALUES ($project_id, $cso_id)");
        }

        $_SESSION['message'] = "Project updated successfully!";
        header("Location: admin_projects_list.php");
        exit();
    } else {
        $_SESSION['message'] = "Error updating project: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Department of Agriculture Region 6 | Projects</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link rel="icon" type="image/x-icon" href="img/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" 
          rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body id="page-top">
   

<style>
        .container-fluid { 
            padding: 20px; 
        }
        .nav-tabs .nav-link {
            background-color: lightgray; 
            color: #0A593A; 
            padding: 12px 16px;
            font-size: 14px; 
            transition: all 0.3s; 
        }
        .nav-tabs .nav-link.active {
            background-color: #0A593A;
            color: white;
            padding: 14px 18px; 
            font-size: 16px; 
            margin-bottom: -1px; 
            border-bottom: 2px solid white;
        }
        .nav-tabs .nav-link.inactive {
            background-color: gray; 
            color: white;
        }
        .success {
            color: blue;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .status {
            color: red;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .error-message {
            color: red;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .btn-custom {
            background-color: rgb(1, 82, 51);
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            transition: background-color 0.3s, color 0.3s;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
            width: 100%;
        }

        .btn-custom:hover {
            color: white;
            text-decoration: underline;
        }

        th,
        .card-header {
            background-color: #0A593A;
            color: white;
        }

        h5,
        label {
            font-weight: bold;
            color: #0A593A;
        }

        .yellow-line {
        background-color: rgb(253, 199, 5);
        height: 7px;
        width: 100%;
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
        .btn-action {
            border: 1px solid #0A593A;
            color: #0A593A;
            background-color: transparent;
            font-weight: bold;
            padding: 6px 12px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        .btn-action:hover {
            background-color: #0A593A;
            color: white;
        }
        .btn-delete {
            border: 1px solid red;
            color: red;
            background-color: transparent;
            font-weight: bold;
            padding: 6px 12px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .btn-delete:hover {
            background-color: red;
            color: white;
        }

        .select2-results__option--selected {
            background-color: #0A593A !important;
            color: white !important;
        }

        .select2-selection__choice {
            background-color: #0A593A !important;
            color: white !important;
            border: none !important;
        }

        .select2-results__option--highlighted {
            background-color: rgba(10, 89, 58, 0.8) !important;
            color: white !important;
        }
    </style>
    
    <div id="wrapper">
        <?php include('admin_include/navbar.php'); ?>
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <h2>Edit Project</h2>
                    <div class="yellow-line"></div>
                    <br>
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-info">
                            <?php 
                                echo $_SESSION['message']; 
                                unset($_SESSION['message']); 
                            ?>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow mb-12">
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <!-- TITLE -->
                                <div class="mb-3">
                                    <label for="title" class="form-label">Project Title <span style="color:red;">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?= htmlspecialchars($project['title']); ?>" required>
                                </div>

                                <!-- PROJECT DESCRIPTION -->
                                <div class="mb-3">
                                    <label for="project_desc" class="form-label">Project Description <span style="color:red;">*</span></label>
                                    <textarea class="form-control" id="project_desc" name="project_desc" rows="8" required><?= htmlspecialchars($project['project_desc']); ?></textarea>
                                </div>

                                <!-- OBJECTIVES -->
                                <div class="mb-3">
                                    <label for="objectives" class="form-label">Project Objective(s) <span style="color:red;">*</span></label>
                                    <textarea class="form-control" id="objectives" name="objectives" rows="4" required><?= htmlspecialchars($project['objectives']); ?></textarea>
                                </div>

                                <!-- OUTCOMES -->
                                <div class="mb-3">
                                    <label for="outcomes" class="form-label">Expected Outcome(s)</label>
                                    <textarea class="form-control" id="outcomes" name="outcomes" rows="4"><?= htmlspecialchars($project['outcomes']); ?></textarea>
                                </div>

                                <!-- MILESTONES -->
                                <div class="mb-3">
                                    <label for="milestones" class="form-label">Milestones/Phases</label>
                                    <textarea class="form-control" id="milestones" name="milestones" rows="4"><?= htmlspecialchars($project['milestones']); ?></textarea>
                                </div>

                                <!-- RISKS -->
                                <div class="mb-3">
                                    <label for="risks" class="form-label">Risk Assessment and Mitigation Plan</label>
                                    <textarea class="form-control" id="risks" name="risks" rows="4"><?= htmlspecialchars($project['risks']); ?></textarea>
                                </div>

                                <!-- TEAM -->
                                <div class="mb-3">
                                    <label for="team" class="form-label">Key Stakeholder(s)</label>
                                    <textarea class="form-control" id="team" name="team" rows="4"><?= htmlspecialchars($project['team']); ?></textarea>
                                </div>

                                <!-- LOCATION -->
                                <div class="mb-3">
                                    <label for="location" class="form-label">Project Location <span style="color:red;">*</span></label>
                                    <input type="text" class="form-control" id="location" name="location" 
                                           value="<?= htmlspecialchars($project['location']); ?>" required>
                                </div>

                                <!-- LAT/LONG -->
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="latitude" class="form-label">Latitude <span style="color:red;">*</span></label>
                                        <input type="number" step="0.000001" class="form-control" id="latitude" name="latitude"
                                               value="<?= htmlspecialchars($project['latitude']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="longitude" class="form-label">Longitude <span style="color:red;">*</span></label>
                                        <input type="number" step="0.000001" class="form-control" id="longitude" name="longitude"
                                               value="<?= htmlspecialchars($project['longitude']); ?>" required>
                                    </div>
                                </div>

                                <!-- BUDGET -->
                                <div class="mb-3">
                                    <label for="budget" class="form-label">Budget Allotted <span style="color:red;">*</span></label>
                                    <input type="number" class="form-control" id="budget" name="budget" 
                                           value="<?= htmlspecialchars($project['budget']); ?>" required>
                                </div>

                                <!-- START/END DATES -->
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="start_date" class="form-label">Start Date <span style="color:red;">*</span></label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" 
                                               value="<?= htmlspecialchars($project['start_date']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="end_date" class="form-label">End Date <span style="color:red;">*</span></label>
                                        <input type="date" class="form-control" id="end_date" name="end_date"
                                               value="<?= htmlspecialchars($project['end_date']); ?>" required>
                                    </div>
                                </div>

                                <!-- DURATION (READ-ONLY, auto-calculated) -->
                                <div class="mb-3">
                                    <label for="duration" class="form-label">Duration (in days) <span style="color:red;">*</span></label>
                                    <input type="number" class="form-control" id="duration" name="duration" 
                                           value="<?= htmlspecialchars($project['duration']); ?>" readonly>
                                </div>

                                <div id="dateError" class="error-message"></div>
                                <script>
                                    document.getElementById('start_date').addEventListener('change', validateDates);
                                    document.getElementById('end_date').addEventListener('change', validateDates);

                                    function validateDates() {
                                        const startDateInput = document.getElementById('start_date');
                                        const endDateInput = document.getElementById('end_date');
                                        const durationInput = document.getElementById('duration');
                                        const errorMessage = document.getElementById('dateError');

                                        const startDate = new Date(startDateInput.value);
                                        const endDate = new Date(endDateInput.value);

                                        if (startDate && endDate) {
                                            if (endDate >= startDate) {
                                                // Valid dates: Calculate the duration
                                                const timeDiff = endDate - startDate; // ms difference
                                                const days = Math.ceil(timeDiff / (1000 * 60 * 60 * 24));
                                                durationInput.value = days;
                                                errorMessage.textContent = "";
                                                durationInput.style.borderColor = "";
                                            } else {
                                                // Invalid dates
                                                durationInput.value = 0;
                                                errorMessage.textContent = "End date must be after the start date.";
                                                durationInput.style.borderColor = "red";
                                            }
                                        }
                                    }
                                </script>

                                <!-- CSO(s) -->
                                <div class="mb-3">
                                    <label for="cso_id" class="form-label">CSO In-Charge <span style="color:red;">*</span></label>
                                    <select class="form-control" id="cso_id" name="cso_id[]" multiple required>
                                        <?php foreach ($all_csos as $cso): ?>
                                            <option value="<?= $cso['id']; ?>" 
                                                <?= in_array($cso['id'], $current_cso_ids) ? 'selected' : ''; ?>>
                                                <?= htmlspecialchars($cso['cso_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- PROJECT STATUS -->
                                <div class="mb-3">
                                    <label for="status" class="form-label">Project Status <span style="color:red;">*</span></label>
                                    <select class="form-control" id="status" name="status" required>
                                        <option value="Assigned"   <?= ($project['status'] == 'Assigned')   ? 'selected' : ''; ?>>Assigned</option>
                                        <option value="In Progress"<?= ($project['status'] == 'In Progress')? 'selected' : ''; ?>>In Progress</option>
                                        <option value="Completed"  <?= ($project['status'] == 'Completed')  ? 'selected' : ''; ?>>Completed</option>
                                        <option value="Completed with Delay"  <?= ($project['status'] == 'Completed with Delay')  ? 'selected' : ''; ?>>Completed with Delay</option>
                                    </select>
                                </div>

                                <!-- PROPOSAL STATUS -->
                                <div class="mb-3">
                                    <label for="proposal_status" class="form-label">Proposal Status</label>
                                    <select class="form-control" id="proposal_status" name="proposal_status">
                                        <option value="Pending"  <?= ($project['proposal_status'] == 'Pending')  ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Approved" <?= ($project['proposal_status'] == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                                        <option value="Denied"   <?= ($project['proposal_status'] == 'Denied')   ? 'selected' : ''; ?>>Denied</option>
                                    </select>
                                </div>

                                <!-- FUNDING STATUS -->
                                <div class="mb-3">
                                    <label for="funding_status" class="form-label">Funding Status</label>
                                    <select class="form-control" id="funding_status" name="funding_status">
                                        <option value="Pending"  <?= ($project['funding_status'] == 'Pending')  ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Approved" <?= ($project['funding_status'] == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                                        <option value="Denied"   <?= ($project['funding_status'] == 'Denied')   ? 'selected' : ''; ?>>Denied</option>
                                    </select>
                                </div>

                                <!-- DATE SUBMITTED -->
                                <div class="mb-3">
                                    <label for="date_submitted" class="form-label">Submitted On</label>
                                    <input type="date" class="form-control" id="date_submitted" name="date_submitted" 
                                           value="<?= htmlspecialchars($project['date_submitted']); ?>">
                                </div>

                                <!-- FILE UPLOAD (REPLACE FILE) -->
                                <div class="mb-3">
                                    <label for="file" class="form-label">Replace Project Plan</label>
                                    <input type="file" name="file" class="form-control" style="border:none">
                                    <small class="text-muted">
                                        Current File: 
                                        <?= htmlspecialchars(basename($project['file_path'] ?? 'No file')); ?>
                                    </small>
                                </div>

                                <!-- SUBMIT BUTTONS -->
                                 <br>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary mr-2" 
                                            style="background-color: #0A593A; border-color: #0A593A;">
                                        Update Project
                                    </button>
                                    <a href="admin_projects_list.php" class="btn btn-danger" 
                                       style="background-color: #0A593A; border-color: #0A593A;">
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div> <!-- card -->
                </div> <!-- col-md-12 -->
            </div> <!-- row -->
        </div> <!-- container-fluid -->
    </div> <!-- wrapper -->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">

    <script>
        $(document).ready(function () {
            // Initialize Select2 for multiple CSO selection
            $('#cso_id').select2({
                placeholder: "Select CSO(s)",
                allowClear: true,
            });
        });
    </script>
</body>
</html>
