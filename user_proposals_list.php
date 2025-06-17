<?php
include ('include/db_connect.php'); 
session_start(); 

if (!isset($_SESSION['cso_representative_id']) && !isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "CSO representative or User ID not set in session.";
    header("Location: login.php");
    exit();
}

$cso_representative_id = $_SESSION['cso_representative_id'] ?? $_SESSION['user_id'];

// Retrieve CSO name
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $date_submitted = date("Y-m-d");  // Only the date, no time
    // Use htmlspecialchars for security but preserve line breaks and spaces
    $title = htmlspecialchars($_POST['title'], ENT_QUOTES, 'UTF-8');
    $content = htmlspecialchars($_POST['content'], ENT_QUOTES, 'UTF-8');
    $objectives = htmlspecialchars($_POST['objectives'], ENT_QUOTES, 'UTF-8');
    $outcomes = htmlspecialchars($_POST['outcomes'], ENT_QUOTES, 'UTF-8');
    $milestones = htmlspecialchars($_POST['milestones'], ENT_QUOTES, 'UTF-8');
    $team = htmlspecialchars($_POST['team'], ENT_QUOTES, 'UTF-8');
    $location = htmlspecialchars($_POST['location'], ENT_QUOTES, 'UTF-8');
    $risks = htmlspecialchars($_POST['risks'], ENT_QUOTES, 'UTF-8');
    $budget = (float) $_POST['budget'];
    $start_date = htmlspecialchars($_POST['start_date'], ENT_QUOTES, 'UTF-8');
    $end_date = htmlspecialchars($_POST['end_date'], ENT_QUOTES, 'UTF-8');

    $allowed_types = ['pdf', 'jpg', 'jpeg', 'png'];
    $cso_folder = preg_replace('/[^a-zA-Z0-9_-]/', '_', $cso_name); // Replace special characters with '_'
    $upload_dir = 'proposals/' . $cso_folder . '/';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Handle multiple file uploads
    $uploaded_files = [];
    foreach ($_FILES['files']['name'] as $key => $file_name) {
        if (empty($file_name)) continue; // Skip empty file slots
        
        $file_tmp = $_FILES['files']['tmp_name'][$key];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $file_path = $upload_dir . basename($file_name);

        // Check for valid file types and size
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

    // Prepare file paths for database
    $file_path = implode(',', $uploaded_files);

    // Insert into database
    $sql_insert = "INSERT INTO proposal (title, content, file_path, objectives, outcomes, milestones, team, location, risks, cso_representative_id, status, start_date, end_date, budget, date_submitted) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql_insert);

    if (!$stmt) {
        $_SESSION['message'] = "Error preparing statement: " . $conn->error;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    $stmt->bind_param('ssssssssssssds', $title, $content, $file_path, $objectives, $outcomes, $milestones, $team, $location, $risks, $cso_representative_id, $start_date, $end_date, $budget, $date_submitted);

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
    .table th,
    .table td {
        vertical-align: middle;
    }

    .table-responsive {
        overflow-x: auto;
    }

    .alert-success {
        background-color: #D4EDDA;
        border-color: #C3E6CB;
        color: #155724;
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 4px;
    }

    h5 {
        font-weight: bold;
        color: #0A593A;
    }
    .table th,
    .table td {
        text-align: center;
    }

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

    .image-viewer {
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .image-viewer img {
        max-width: 100%;
        max-height: 100%;
    }


    .pdf-viewer iframe,
    .pdf-viewer object,
    .pdf-viewer embed {
        width: 100%;
        height: 100%;
    }

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

    .modal-body p {
        margin-bottom: 15px;
    }

    .modal-body .row {
        margin-bottom: 15px;
    }

    .modal-header {
        background-color: #0A593A;
        color: white;
        border-radius: 8px 8px 0 0;
        padding: 15px 20px;
    }

    .modal-header h5 {
        font-weight: bold;
        font-size: 20px;
        color: white;
        margin: 0;
    }

    .modal-footer {
        border-top: 1px solid #dee2e6;
        padding: 15px 20px;
        text-align: right;
        background-color: #f8f9fa;
        border-radius: 0 0 8px 8px;
    }
    
    .modal-content {
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .custom-close {
        position: absolute; 
        top: 2.75%; 
        right: 0; 
        font-size: 24px; 
        color: white; 
        background: transparent; 
        border: none; 
        padding: 0 15px; 
        line-height: 1;
    }
    
    .custom-close:hover {
        color: #f8f9fa;
        opacity: 0.8;
    }

    .modal-header .btn-close:hover {
        color: #fff;
        opacity: 0.8;
    }

    .modal-title {
        font-weight: bold;
        color: white;
    }

    .modal-footer .btn-secondary {
        background-color: #0A593A;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        transition: all 0.3s;
    }
    
    .modal-footer .btn-secondary:hover {
        background-color: #07422c;
        transform: translateY(-2px);
    }

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
    
    /* Add this to preserve whitespace formatting */
    .preserve-format {
        white-space: pre-wrap;
        word-wrap: break-word;
        background-color: #ffffff;
        padding: 12px;
        border-radius: 8px;
        border-left: 4px solid #0A593A;
        margin-top: 5px;
        font-size: 14px;
        line-height: 1.6;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    /* Improved modal styling */
    .modal-body {
        padding: 25px;
        max-height: 70vh;
        overflow-y: auto;
        background-color: #f8f9fa;
    }
    
    .modal-body label {
        font-weight: 600;
        color: #0A593A;
        display: block;
        margin-bottom: 8px;
        font-size: 15px;
        letter-spacing: 0.3px;
    }
    
    .modal-body p {
        margin-bottom: 20px;
    }
    
    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        color: white;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.3s;
    }
    
    .status-badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .status-pending {
        background-color: #FFC107;
        color: #000;
    }
    
    .status-approved {
        background-color: #28A745;
    }
    
    .status-rejected {
        background-color: #DC3545;
    }
    
    .status-funded {
        background-color: #17A2B8;
    }
    
    .status-not-funded {
        background-color: #6C757D;
    }
    
    .file-link {
        display: inline-block;
        margin: 5px;
        padding: 8px 15px;
        background-color: #f0f0f0;
        border-radius: 6px;
        color: #0A593A;
        text-decoration: none;
        transition: all 0.3s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border: 1px solid #e0e0e0;
    }
    
    .file-link:hover {
        background-color: #e0e0e0;
        transform: translateY(-2px);
        box-shadow: 0 3px 6px rgba(0,0,0,0.15);
        color: #074028;
    }
    
    .file-link i {
        margin-right: 5px;
    }
    
    .detail-section {
        border-bottom: 1px solid #e0e0e0;
        padding-bottom: 18px;
        margin-bottom: 18px;
        /* Removed hover transition */
    }
    
    .detail-section:last-child {
        border-bottom: none;
    }
    
    .admin-status-section {
        background-color: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 10px;
        padding: 20px;
        margin-top: 30px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    }
    
    .admin-status-title {
        font-weight: bold;
        color: #0A593A;
        margin-bottom: 15px;
        border-bottom: 2px solid #0A593A;
        padding-bottom: 8px;
        font-size: 18px;
    }
    
    .status-item {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px dashed #e0e0e0;
        align-items: flex-start;
    }
    
    .status-item:last-child {
        border-bottom: none;
    }
    
    .status-item strong {
        min-width: 180px;
        color: #555;
    }

    .status-item {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px dashed #e0e0e0;
        align-items: flex-start;
    }

    .status-item:last-child {
        border-bottom: none;
    }

    .status-item strong {
        min-width: 180px;
        color: #555;
    }

    /* Add these new styles for comment display */
    .status-item.comment-item {
        flex-direction: column;
    }

    .status-item.comment-item strong {
        margin-bottom: 8px;
        display: block;
    }

    .status-item.comment-item .preserve-format {
        width: 100%;
        margin-top: 5px;
    }

    .btn-view {
        border: 1px solid #0A593A;
        color: #0A593A;
        background-color: transparent;
        font-weight: bold;
        padding: 6px 12px;
        border-radius: 4px;
        transition: all 0.3s ease;
    }
    .btn-view:hover {
        background-color: #0A593A;
        color: white;
    }
</style>

<div id="wrapper">
<div class="container-fluid full-height-container">
    <div class="row">
        <div class="col-md-12">
            <h2>Proposals List</h2>
            <div class="yellow-line"></div>
            <br>
            <?php if(isset($_SESSION['message'])): ?>
                <div class="alert alert-success">
                    <?php 
                        echo $_SESSION['message']; 
                        unset($_SESSION['message']);
                    ?>
                </div>
            <?php endif; ?>
            <div class="table-responsive">
                <div class="row">
                    <div class="col-md-12">
                    <table id="proposalTable" class='display table table-bordered dt-responsive wrap' width='100%'>
                        <thead>
                            <tr>
                                <th>Date Submitted</th>
                                <th>Title</th>
                                <th>Budget Requirement</th>
                                <th>Location</th>
                                <th>Key Stakeholder(s)</th>
                                <th>Verification Status</th>
                                <th>Proposal Status</th>
                                <th>Funding Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Modify the SQL to fetch the funding_status and admin_status fields
                            $sql_select = "SELECT * FROM proposal WHERE cso_representative_id = ? ORDER BY date_submitted DESC";
                            $stmt = $conn->prepare($sql_select);
                            $stmt->bind_param('i', $cso_representative_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result) {
                                while ($row = $result->fetch_assoc()) {
                                    // Format dates for display (using PHP date format)
                                    $formatted_date = date('F d, Y', strtotime($row['date_submitted']));
                                    
                                    // Status badge classes
                                    $statusClass = '';
                                    switch(strtolower($row['status'])) {
                                        case 'pending': $statusClass = 'status-pending'; break;
                                        case 'approved': $statusClass = 'status-approved'; break;
                                        case 'rejected': $statusClass = 'status-rejected'; break;
                                        default: $statusClass = 'status-pending';
                                    }

                                    // CSO status badge class
                                    $csoStatusClass = '';
                                    $csoStatus = htmlspecialchars($row['cso_status'] ?? 'Pending');
                                    switch(strtolower($csoStatus)) {
                                        case 'verified': $csoStatusClass = 'status-approved'; break;  // This will be green
                                        case 'approved': $csoStatusClass = 'status-approved'; break;  // Adding this in case 'approved' is used instead of 'verified'
                                        case 'rejected': $csoStatusClass = 'status-rejected'; break;
                                        case 'pending': $csoStatusClass = 'status-pending'; break;
                                        default: $csoStatusClass = 'status-pending';
                                    }

                                    // Funding status badge class
                                    $fundingStatusClass = '';
                                    $fundingStatus = htmlspecialchars($row['funding_status'] ?? 'Pending');
                                    switch(strtolower($fundingStatus)) {
                                        case 'funded': $fundingStatusClass = 'status-approved'; break;  // Changed from 'status-funded' to 'status-approved' for green color
                                        case 'approved': $fundingStatusClass = 'status-approved'; break; // Adding this as an alternative
                                        case 'not funded': $fundingStatusClass = 'status-not-funded'; break;
                                        case 'pending': $fundingStatusClass = 'status-pending'; break;
                                        default: $fundingStatusClass = 'status-pending';
                                    }
                                    
                                    echo "<tr>
                                            <td>" . $formatted_date . "</td>
                                            <td>" . htmlspecialchars($row['title']) . "</td>
                                            <td>PHP " . number_format($row['budget'], 2) . "</td>
                                            <td>" . htmlspecialchars($row['location']) . "</td>
                                            <td>" . htmlspecialchars($row['team']) . "</td>
                                            <td><span class='status-badge $csoStatusClass'>" . $csoStatus . "</span></td>
                                            <td><span class='status-badge $statusClass'>" . htmlspecialchars($row['status']) . "</span></td>
                                            <td><span class='status-badge $fundingStatusClass'>" . $fundingStatus . "</span></td>
                                            <td>
                                                <a href='#' class='btn btn-view' data-bs-toggle='modal' data-bs-target='#viewDetails" . htmlspecialchars($row['id']) . "'>
                                                    <i class='fas fa-eye'></i>
                                                </a>
                                            </td>
                                        </tr>";
                                        
                                        // Format other dates for display
                                        $start_date = !empty($row['start_date']) ? date('F d, Y', strtotime($row['start_date'])) : 'N/A';
                                        $end_date = !empty($row['end_date']) ? date('F d, Y', strtotime($row['end_date'])) : 'N/A';
                                        $cso_updated = !empty($row['cso_status_updated_at']) ? date('F d, Y', strtotime($row['cso_status_updated_at'])) : 'Not updated yet';
                                        $status_updated = !empty($row['status_updated_at']) ? date('F d, Y', strtotime($row['status_updated_at'])) : 'Not updated yet';
                                        $funding_updated = !empty($row['funding_updated_at']) ? date('F d, Y', strtotime($row['funding_updated_at'])) : 'Not updated yet';
                                        
                                        // Improved modal with better styling
                                        echo "<div class='modal fade' id='viewDetails" . htmlspecialchars($row['id']) . "' tabindex='-1' aria-labelledby='viewDetailsLabel_" . htmlspecialchars($row['id']) . "' aria-hidden='true'>
                                        <div class='modal-dialog modal-lg'>
                                            <div class='modal-content'>
                                                <div class='modal-header'>
                                                    <h5 class='modal-title'>Proposal Details: " . htmlspecialchars($row['title']) . "</h5>
                                                    <button type='button' class='custom-close' data-bs-dismiss='modal' aria-label='Close'>×</button>
                                                </div>
                                                <div class='modal-body'>
                                                    <div class='row mb-4'>
                                                        <div class='col-md-6'>
                                                            <div class='detail-section'>
                                                                <label>Submitted On:</label>
                                                                <div>" . $formatted_date . "</div>
                                                            </div>
                                                            
                                                            <div class='detail-section'>
                                                                <label>Status Information:</label>
                                                                <div class='d-flex justify-content-between mb-2'>
                                                                    <span>Proposal Status:</span>
                                                                    <span class='status-badge $statusClass'>" . htmlspecialchars($row['status']) . "</span>
                                                                </div>
                                                                <div class='d-flex justify-content-between mb-2'>
                                                                    <span>Funding Status:</span>
                                                                    <span class='status-badge $fundingStatusClass'>" . $fundingStatus . "</span>
                                                                </div>
                                                                <div class='d-flex justify-content-between'>
                                                                    <span>Verification Status:</span>
                                                                    <span class='status-badge $csoStatusClass'>" . $csoStatus . "</span>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class='detail-section'>
                                                                <label>Project Title:</label>
                                                                <div class='preserve-format'>" . htmlspecialchars($row['title']) . "</div>
                                                            </div>
                                                            
                                                            <div class='detail-section'>
                                                                <label>Project Description:</label>
                                                                <div class='preserve-format'>" . nl2br(htmlspecialchars($row['content'])) . "</div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class='col-md-6'>
                                                            <div class='detail-section'>
                                                                <label>Project Objective(s):</label>
                                                                <div class='preserve-format'>" . nl2br(htmlspecialchars($row['objectives'])) . "</div>
                                                            </div>
                                                            
                                                            <div class='detail-section'>
                                                                <label>Expected Outcome(s):</label>
                                                                <div class='preserve-format'>" . nl2br(htmlspecialchars($row['outcomes'])) . "</div>
                                                            </div>
                                                            
                                                            <div class='detail-section'>
                                                                <label>Milestones/Phases:</label>
                                                                <div class='preserve-format'>" . nl2br(htmlspecialchars($row['milestones'])) . "</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class='row'>
                                                        <div class='col-md-6'>
                                                            <div class='detail-section'>
                                                                <label>Key Stakeholder(s):</label>
                                                                <div class='preserve-format'>" . nl2br(htmlspecialchars($row['team'])) . "</div>
                                                            </div>
                                                            
                                                            <div class='detail-section'>
                                                                <label>Project Location:</label>
                                                                <div class='preserve-format'>" . nl2br(htmlspecialchars($row['location'])) . "</div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class='col-md-6'>
                                                            <div class='detail-section'>
                                                                <label>Risk Assessment and Mitigation Plan:</label>
                                                                <div class='preserve-format'>" . nl2br(htmlspecialchars($row['risks'])) . "</div>
                                                            </div>
                                                            
                                                            <div class='detail-section'>
                                                                <label>Budget and Timeline:</label>
                                                                <div class='d-flex justify-content-between mb-2'>
                                                                    <span>Budgetary Requirement:</span>
                                                                    <span>PHP " . number_format($row['budget'], 2) . "</span>
                                                                </div>
                                                                <div class='d-flex justify-content-between mb-2'>
                                                                    <span>Start Date:</span>
                                                                    <span>" . $start_date . "</span>
                                                                </div>
                                                                <div class='d-flex justify-content-between'>
                                                                    <span>End Date:</span>
                                                                    <span>" . $end_date . "</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class='row mt-3'>
                                                        <div class='col-12'>
                                                            <div class='detail-section'>
                                                                <label>Supporting File:</label>
                                                                <div>";
                                                                // Handle multiple files with better styling
                                                                if (!empty($row['file_path'])) {
                                                                    $files = explode(',', $row['file_path']);
                                                                    foreach ($files as $index => $file) {
                                                                        $fileName = basename($file);
                                                                        echo "<a href='#' data-file-path='" . htmlspecialchars($file) . "' class='file-link'>
                                                                                <i class='fa fa-file'></i> File " . ($index+1) . ": " . htmlspecialchars($fileName) . "
                                                                              </a> ";
                                                                    }
                                                                } else {
                                                                    echo "<div class='text-muted'>No files attached</div>";
                                                                }
                                                                echo "</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class='admin-status-section mt-4'>
                                                        <h6 class='admin-status-title'>Review & Status Information</h6>
                                                        
                                                        <div class='status-item'>
                                                            <strong>Verification Status:</strong>
                                                            <div>
                                                                <span class='status-badge $csoStatusClass'>" . $csoStatus . "</span>
                                                                <small class='text-muted d-block mt-1'>
                                                                    Last updated: " . $cso_updated . "
                                                                </small>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class='status-item'>
                                                            <strong>Proposal Status:</strong>
                                                            <div>
                                                                <span class='status-badge $statusClass'>" . htmlspecialchars($row['status']) . "</span>
                                                                <small class='text-muted d-block mt-1'>
                                                                    Last updated: " . $status_updated . "
                                                                </small>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class='status-item'>
                                                            <strong>Funding Status:</strong>
                                                            <div>
                                                                <span class='status-badge $fundingStatusClass'>" . $fundingStatus . "</span>
                                                                <small class='text-muted d-block mt-1'>
                                                                    Last updated: " . htmlspecialchars($row['funding_updated_at'] ?? 'Not updated yet') . "
                                                                </small>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class='status-item' style='flex-direction: column;'>
                                                            <strong style='margin-bottom: 8px;'>Admin Comments:</strong>
                                                            <div class='preserve-format' style='width: 100%;'>" . 
                                                                (empty($row['remarks']) ? 
                                                                    "<em class='text-muted'>No comments provided</em>" : 
                                                                    nl2br(htmlspecialchars($row['remarks']))) . 
                                                            "</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class='modal-footer'>
                                                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>";                            
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                    </div>
                </div> 
            </div>
        </div>
    </div>
</div>

<?php include ('user_include/script.php'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<script>
$(document).ready(function () {
    $('#proposalTable').DataTable({
        responsive: true,
        autoWidth: false,
        pageLength: 10,
        stripeClasses: [],
        language: {
            emptyTable: "No applications found.",
            zeroRecords: "No matching records found"
        },
        columnDefs: [
            { targets: [5, 6, 7], width: "100px" }
        ],
        order: [[0, 'desc']] // Sort by date submitted, newest first
    });

    // Modified code to open files in new tab
    $(document).on('click', '.file-link', function(e) {
        e.preventDefault();
        var filePath = $(this).data('file-path');
        window.open(filePath, '_blank');
    });

    $(document).on('click', '.file-link', function(e) {
        e.preventDefault();
        var filePath = $(this).data('file-path'); // Using data attribute instead of href
        var fileName = $(this).text().trim();
        var extension = filePath.split('.').pop().toLowerCase();

        $('#fileModalLabel').text('File Viewer: ' + fileName);

        var content = '';
        if (extension === 'pdf') {
            content = '<embed src="' + filePath + '" type="application/pdf" class="pdf-viewer">';
        } else {
            content = '<div class="image-viewer"><img src="' + filePath + '" alt="File"></div>';
        }

        $('#modalFileViewer').html(content);
        
        // Bootstrap 5 modal show
        var fileModal = new bootstrap.Modal(document.getElementById('fileModal'));
        fileModal.show();
    });
});
</script>