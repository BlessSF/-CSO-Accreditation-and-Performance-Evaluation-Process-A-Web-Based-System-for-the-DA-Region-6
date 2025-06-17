<?php
session_start(); 
include('include/db_connect.php');  // Make sure this path is correct

// 1) SESSION CHECKS
if (!isset($_SESSION['cso_representative_id']) && !isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "CSO representative or User ID not set in session.";
    header("Location: login.php");
    exit();
}

$cso_representative_id = $_SESSION['cso_representative_id'] ?? $_SESSION['user_id'];

// 2) RETRIEVE CSO NAME
$sql_cso = "SELECT cso_name FROM cso_representative WHERE id = ?";
$stmt_cso = $conn->prepare($sql_cso);
$stmt_cso->bind_param('i', $cso_representative_id);
$stmt_cso->execute();
$result_cso = $stmt_cso->get_result();
if ($result_cso->num_rows > 0) {
    $cso_row = $result_cso->fetch_assoc();
    $cso_name = $cso_row['cso_name'];
} else {
    $_SESSION['message'] = "CSO not found for this representative.";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// 3) PROCESS FORM SUBMIT
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Current date (YYYY-mm-dd)
    $date_submitted = date("Y-m-d");

    // Retrieve inputs - using direct input instead of htmlspecialchars
    // IMPORTANT: We'll escape HTML entities during display, not storage
    $title       = $_POST['title'];
    $content     = $_POST['content'];
    $objectives  = $_POST['objectives'];
    $outcomes    = $_POST['outcomes'];
    $milestones  = $_POST['milestones'];
    $team        = $_POST['team'];
    $location    = $_POST['user_address'];
    $risks       = $_POST['risks'];
    $budget      = (float)$_POST['budget'];
    $start_date  = $_POST['start_date'];
    $end_date    = $_POST['end_date'];
    $latitude    = (float)$_POST['latitude'];
    $longitude   = (float)$_POST['longitude'];

    // 3a) FILE UPLOAD LOGIC
    $allowed_types = ['pdf', 'jpg', 'jpeg', 'png'];
    // Create a safe folder name from the CSO name
    $cso_folder = preg_replace('/[^a-zA-Z0-9_-]/', '_', $cso_name);
    $upload_dir = 'proposals/' . $cso_folder . '/';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $uploaded_files = [];
    if (!empty($_FILES['files']['name'][0])) {
        // At least one file was uploaded
        foreach ($_FILES['files']['name'] as $key => $file_name) {
            $file_tmp = $_FILES['files']['tmp_name'][$key];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $file_path = $upload_dir . basename($file_name);

            // Check type + size (2MB limit in your code)
            if (in_array($file_ext, $allowed_types) && $_FILES['files']['size'][$key] <= 2000000) {
                if (move_uploaded_file($file_tmp, $file_path)) {
                    $uploaded_files[] = $file_path;
                } else {
                    $_SESSION['message'] = "Error uploading file: " . $file_name;
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                }
            } else {
                $_SESSION['message'] = "Invalid file type or file size for: " . $file_name;
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            }
        }
    } else {
        // No files were actually uploaded
        $_SESSION['message'] = "Please select at least one file.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Combine file paths into single string if needed
    $file_path = implode(',', $uploaded_files);

    // 3b) INSERT QUERY: includes latitude/longitude columns
    $sql_insert = "
        INSERT INTO proposal (
            title, 
            content, 
            file_path, 
            objectives, 
            outcomes, 
            milestones, 
            team, 
            location,
            latitude,
            longitude,
            risks, 
            cso_representative_id, 
            status, 
            start_date, 
            end_date, 
            budget, 
            date_submitted
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?, ?, ?
        )
    ";

    $stmt = $conn->prepare($sql_insert);
    if (!$stmt) {
        $_SESSION['message'] = "Error preparing statement: " . $conn->error;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Bind the parameters (16 placeholders => 16 variables)
    $stmt->bind_param(
        'ssssssssddsissds',
        $title,
        $content,
        $file_path,
        $objectives,
        $outcomes,
        $milestones,
        $team,
        $location,
        $latitude,
        $longitude,
        $risks,
        $cso_representative_id,
        $start_date,
        $end_date,
        $budget,
        $date_submitted
    );

    // 3c) EXECUTE & FEEDBACK
    if ($stmt->execute()) {
        $_SESSION['message'] = "Proposal submitted successfully!";
    } else {
        $_SESSION['message'] = "Error submitting proposal: " . $stmt->error;
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<?php include ('user_include/header.php'); ?> 
<?php include ('user_include/navbar.php'); ?>

<style>
    /* --- SAMPLE STYLES --- */
    .container-fluid { 
        padding: 20px; 
    }
    .btn-primary {
        background-color: #0A593A;
        border-color: #0A593A;
        width: 100%;
    }
    .btn-primary:hover{
        background-color: #0A593A;
        text-decoration: underline;
    }
    .table th, .table td { vertical-align: middle; }
    .table-responsive { overflow-x: auto; }
    .alert-success {
        background-color: #D4EDDA;
        border-color: #C3E6CB;
        color: #155724;
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 4px;
    }
    h2, h5, label {
        font-weight: bold;
        color: #0A593A;
    }
    .table th, .table td { text-align: center; }
    th {
        background-color: #0A593A;
        color: white;
    }
    .pdf-viewer {
        width: 100%;
        height: 600px;
        border: 1px solid #ccc;
        margin-top: 20px;
        padding: 10px;
        box-sizing: border-box;
    }
    .image-viewer { width: 100%; height: 100%; display: flex; justify-content: center; align-items: center; }
    .image-viewer img { max-width: 100%; max-height: 100%; }
    .pdf-viewer iframe, .pdf-viewer object, .pdf-viewer embed { width: 100%; height: 100%; }
    .pdf-viewer-container {
        height: 400px;
        border: 1px solid #ccc;
        overflow: auto;
        background-color: #f8f9fa;
    }
    .pdf-viewer-container iframe,
    .pdf-viewer-container img {
        width: 100%;
        height: 100%;
        border: none;
    }
    .table {
        width: 100%;
        margin-bottom: 20px;
    }
    @media (max-width: 768px) {
        .col-md-7, .col-md-5 {
            width: 100%;
            margin-bottom: 20px;
        }
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
    .modal-body p { margin-bottom: 10px; }
    .modal-body .row { margin-bottom: 15px; }
    .modal-header { background-color: #0A593A; color: white; }
    .modal-header h5 { font-weight: bold; font-size: 18px; }
    .modal-footer {
        border-top: 1px solid #dee2e6;
        padding-top: 15px;
        text-align: right;
    }
    .modal-content { border-radius: 8px; }
    .modal-header { background-color: #0A593A; color: white; }
    .modal-header .btn-close:hover { color: #007bff; }
    .modal-title { font-weight: bold; color: white; }
    .modal-footer .btn-secondary { background-color: #0A593A; }
    .modal-normal {
        width: 100%;
        max-width: 800px;
        transition: all 0.3s ease;
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
                <h2>Endorse Proposal</h2>
                <div class="yellow-line"></div>
                <br>
                <?php
                // Display any messages
                if (isset($_SESSION['message'])) {
                    echo "<div class='alert alert-success'>" . htmlspecialchars($_SESSION['message']) . "</div>";
                    unset($_SESSION['message']);
                }
                ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card shadow mb-12" style="height: 1650px; overflow-y: auto;">
                            <div class="card-body">
                                <form action="" method="POST" enctype="multipart/form-data">
                                    <!-- Title -->
                                    <div class="form-group">
                                        <label for="title">Project Title<span style="color:red; font-weight:bold;">*</span></label>
                                        <input type="text" name="title" class="form-control" required>
                                    </div>

                                    <!-- Description -->
                                    <div class="form-group">
                                        <label for="content">Project Description <span style="color:red; font-weight:bold;">*</span></label>
                                        <textarea name="content" class="form-control" rows="5" required></textarea>
                                    </div>

                                    <!-- Objectives -->
                                    <div class="form-group">
                                        <label for="objectives">Project Objective(s) <span style="color:red; font-weight:bold;">*</span></label>
                                        <textarea name="objectives" class="form-control" rows="5" required></textarea>
                                    </div>  

                                    <!-- Outcomes -->
                                    <div class="form-group">
                                        <label for="outcomes">Expected Outcome(s) <span style="color:red; font-weight:bold;">*</span></label>
                                        <textarea name="outcomes" class="form-control" rows="5" required></textarea>
                                    </div>

                                    <!-- Milestones/Phases -->
                                    <div class="form-group">
                                        <label for="milestones">Milestones/Phases <span style="color:red; font-weight:bold;">*</span></label>
                                        <textarea name="milestones" class="form-control" rows="5" required></textarea>
                                    </div>

                                    <!-- Team / Stakeholders -->
                                    <div class="form-group">
                                        <label for="team">Key Stakeholder(s)</label>
                                        <textarea name="team" class="form-control" rows="3"></textarea>
                                    </div>

                                    <!-- Address + Geocoding -->
                                    <div class="form-group">
                                        <label for="user_address">Address <span style="color:red; font-weight:bold;">*</span></label>
                                        <div class="input-group">
                                            <input type="text" id="user_address" name="user_address" class="form-control" 
                                                   placeholder="Enter address (e.g., 123 Main St, Iloilo City)" required>
                                            <div class="input-group-append">
                                                <button type="button" id="geocode_btn" class="btn btn-primary">Detect Coordinates</button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Latitude + Longitude -->
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="latitude">Latitude <span style="color:red; font-weight:bold;">*</span></label>
                                            <input type="text" id="latitude" name="latitude" class="form-control" 
                                                   placeholder="e.g. 14.5995" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="longitude">Longitude <span style="color:red; font-weight:bold;">*</span></label>
                                            <input type="text" id="longitude" name="longitude" class="form-control" 
                                                   placeholder="e.g. 120.9842" required>
                                        </div>
                                    </div>

                                    <!-- Risk Assessment -->
                                    <div class="form-group">
                                        <label for="risks">Risk Assessment and Mitigation Plan</label>
                                        <textarea name="risks" class="form-control" rows="3"></textarea>
                                    </div>

                                    <!-- Budget -->
                                    <div class="form-group">
                                        <label for="budget">Budgetary Requirement <span style="color:red; font-weight:bold;">*</span></label>
                                        <input type="number" step="0.01" name="budget" class="form-control" required>
                                    </div>

                                    <!-- Dates -->
                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label for="start_date">Start Date <span style="color:red; font-weight:bold;">*</span></label>
                                            <input type="date" name="start_date" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="end_date">End Date <span style="color:red; font-weight:bold;">*</span></label>
                                            <input type="date" name="end_date" class="form-control" required>
                                        </div>
                                    </div>
                                    
                                    <!-- File Upload -->
                                    <br>
                                    <div class="form-group">
                                        <label for="files">Upload Supporting File (e.g., Budget Breakdown, GANTT chart, etc.) 
                                            <span style="color:red; font-weight:bold;">*</span>
                                        </label>
                                        <input type="file" name="files[]" class="form-control" multiple required 
                                               style="border:none; background-color: transparent;">
                                    </div>
                                    <br>

                                    <!-- Submit -->
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">Submit Proposal</button>
                                    </div>
                                </form>
                            </div> <!-- end card-body -->
                        </div> <!-- end card -->
                        <br>
                    </div> <!-- end col-md-12 -->
                </div> <!-- end row -->
            </div> <!-- end col-md-12 -->
        </div> <!-- end row -->
    </div> <!-- end container-fluid -->
</div> <!-- end wrapper -->

<?php include('user_include/script.php'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">

<script>
$(document).ready(function () {
    // If you have a table with ID="proposalTable", you can init DataTables like this:
    $('#proposalTable').DataTable({
        responsive: false,
        autoWidth: false,
        scrollX: true,
        scrollY: true,
        pageLength: 10,
        stripeClasses: [],
        language: {
            emptyTable: "No proposals found.",
            zeroRecords: "No matching records found"
        }
    });

    // Geocode Address on Button Click using Nominatim (OpenStreetMap)
    document.getElementById('geocode_btn').addEventListener('click', function () {
        var address = document.getElementById('user_address').value;
        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    var lat = parseFloat(data[0].lat);
                    var lon = parseFloat(data[0].lon);
                    // Update input fields
                    document.getElementById('latitude').value = lat.toFixed(6);
                    document.getElementById('longitude').value = lon.toFixed(6);
                    // If you have a Leaflet or Google map, update the marker here
                } else {
                    alert('Address not found. Please try again.');
                }
            })
            .catch(err => console.error('Error with Geocoding:', err));
    });

    // Example click handler if you want to preview uploaded files
    $(document).on('click', '.file-link', function(e) {
        e.preventDefault();
        var filePath = $(this).data('file');
        var extension = filePath.split('.').pop().toLowerCase();
        var content = '';
        if (extension === 'pdf') {
            content = '<embed src="' + filePath + '" type="application/pdf" class="pdf-viewer">';
        } else {
            content = '<div class="image-viewer"><img src="' + filePath + '" alt="File"></div>';
        }
        $('#modalFileViewer').html(content);  
        $('#fileModal').modal('show');  
    });
});
</script>