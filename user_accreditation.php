<?php
session_start();
include('include/db_connect.php');

// Validate session to ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ------------------------
// Fetch Profile and Chairperson info
// ------------------------
$sql_fetch_profile = "SELECT * FROM cso_representative WHERE id = ?";
$stmt_fetch_profile = $conn->prepare($sql_fetch_profile);
$stmt_fetch_profile->bind_param("i", $user_id);
$stmt_fetch_profile->execute();
$result_profile = $stmt_fetch_profile->get_result();
$profile = $result_profile->fetch_assoc();

$cso_name = $profile['cso_name'] ?? '';

// Fetch chairperson info based on cso_name
$sql_fetch_chairperson = "
    SELECT *
    FROM cso_chairperson
    WHERE cso_name = ?";
$stmt_fetch_chairperson = $conn->prepare($sql_fetch_chairperson);
$stmt_fetch_chairperson->bind_param("s", $cso_name);
$stmt_fetch_chairperson->execute();
$result_chairperson = $stmt_fetch_chairperson->get_result();
$chairperson = $result_chairperson->fetch_assoc();

// Define document requirements (same as before)
$file_fields = [
    'datasheet_org' => [
        'title' => 'Accomplished Data Sheet with Organizational <br> Set-up',
        'description' => 'A fully completed data sheet outlining the organizational structure of the CSO <br>
            <a href="http://localhost/DA/files/DNT/DA-CSO_ApplicationForm.pdf" target="_blank" style="color:#8f919f; text-decoration: underline;">
              Download Data Sheet
            </a>'
    ],
    'goodstanding_lce' => [
        'title' => 'Certificate of Good Standing from Local Chief Executive',
        'description' => 'An official certificate from the Local Chief Executive...'
    ],
    'permit_mayor' => [
        'title' => 'Mayor’s Permit',
        'description' => 'A valid, up-to-date Mayor\'s Permit...'
    ],
    'permit_bir' => [
        'title' => 'BIR Registration',
        'description' => 'The Bureau of Internal Revenue (BIR) registration certificate...'
    ],
    'certificate_reg' => [
        'title' => 'Certificate of Registration from SEC, CDA, or DOLE-BRW',
        'description' => 'Issued by SEC, CDA, or DOLE-BRW certifying the CSO\'s registration.'
    ],
    'goodstanding_ga' => [
        'title' => 'Certificate of Good Standing from Government Agencies',
        'description' => 'Certification from relevant government agencies...'
    ],
    'omnibus' => [
        'title' => 'Omnibus Sworn Statement (duly Notarized)',
        'description' => 'A sworn statement, duly notarized...'
    ],
    'bio_data' => [
        'title' => 'Bio-data Sheet of Current President/Chairman',
        'description' => 'An updated bio-data sheet...'
    ],
    'articles_of_incorporation' => [
        'title' => 'Articles of Incorporation/Cooperation',
        'description' => 'The latest amended Articles of Incorporation...'
    ],
    'incumbent_officers' => [
        'title' => 'Secretary’s Certificate of Incumbent Officers',
        'description' => 'A formal certification by the Secretary listing current officers...'
    ],
    'accomplishment_reports' => [
        'title' => 'Report of Accomplishment for Last Three Years',
        'description' => 'An accomplishment report for the last three years...'
    ],
    'disclosure' => [
        'title' => 'Directors’ and Trustees’ Business Disclosure Statement',
        'description' => 'A disclosure statement by the CSO’s Directors and Trustees...'
    ],
    'affidavit' => [
        'title' => 'Sworn Affidavit of the Secretary of CSO',
        'description' => 'A sworn affidavit from the Secretary...'
    ],
];

// Handle file uploads and form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $allowed_file_types = ['pdf', 'doc', 'docx'];
    $target_dir = "uploads/accreditation/" . $user_id . "/";
    $file_data = [];
    $upload_status = [];

    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    foreach ($file_fields as $field_name => $details) {
        if (!empty($_FILES[$field_name]['name'])) {
            $target_file = $target_dir . basename($_FILES[$field_name]['name']);
            $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            if ($_FILES[$field_name]['size'] > 5000000) {
                $upload_status[$field_name] = "File too large.";
            } elseif (!in_array($file_type, $allowed_file_types)) {
                $upload_status[$field_name] = "Invalid file type.";
            } elseif (move_uploaded_file($_FILES[$field_name]["tmp_name"], $target_file)) {
                $file_data[$field_name] = $target_file;
                $upload_status[$field_name] = "Uploaded successfully.";
            } else {
                $upload_status[$field_name] = "Error uploading file.";
            }
        }
    }

    if (!empty($file_data)) {
        $columns = implode(", ", array_keys($file_data)) . ", cso_representative_id, cso_status, status";
        $placeholders = implode(", ", array_fill(0, count($file_data), "?")) . ", ?, ?, ?";

        $sql_insert = "INSERT INTO accreditation_application ($columns) VALUES ($placeholders)";
        $stmt_insert = $conn->prepare($sql_insert);

        if ($stmt_insert === false) {
            die("Error preparing statement: " . $conn->error);
        }

        $cso_status_val = 'Pending';
        $status_val = 'Pending';

        $types = str_repeat("s", count($file_data)) . "iss";
        $params = array_values($file_data);
        $params[] = $user_id;
        $params[] = $cso_status_val;
        $params[] = $status_val;

        $stmt_insert->bind_param($types, ...$params);

        if ($stmt_insert->execute()) {
            $_SESSION['success'] = "Files uploaded successfully. Your application is now pending CSO review.";
        } else {
            $_SESSION['status'] = "Database error.";
        }
    }

    $_SESSION['upload_status'] = $upload_status;
    header("Location: user_accreditation.php");
    exit();
}

// Fetch all user applications
$sql_applications = "SELECT * FROM accreditation_application 
                     WHERE cso_representative_id = ? 
                     ORDER BY created_at DESC";
$stmt_applications = $conn->prepare($sql_applications);
$stmt_applications->bind_param("i", $user_id);
$stmt_applications->execute();
$result_applications = $stmt_applications->get_result();

$user_applications = [];
while ($row = $result_applications->fetch_assoc()) {
    $user_applications[] = $row;
}

$showSubmitTab = true;
$showStatusTab = false;

if (!empty($user_applications)) {
    $latestStatus = strtolower($user_applications[0]['status']);

    if ($latestStatus === 'pending' || $latestStatus === 'accredited') {
        $showSubmitTab = false;
        $showStatusTab = true;
    } elseif ($latestStatus === 'denied') {
        $showSubmitTab = true;
        $showStatusTab = true;
    }
}

// Include your user header/nav
include('user_include/header.php');
include('user_include/navbar.php');
?>

<style>
.container-fluid {
    padding: 20px;
    max-width: 100%;
    position: relative;
}
h2, h5 {
    color: #0A593A;
    font-weight: bold;
}
.yellow-line {
    background-color: rgb(253, 199, 5);
    height: 7px;
    width: 100%;
    margin: 0;
    padding: 0;
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
}
.btn-submit {
    background-color: #0A593A;
    color: white;
    padding: 10px 20px;
    font-weight: bold;
    border: none;
    border-radius: 5px;
    width: 100%;
}
.table thead th {
    background-color: #0A593A;
    color: white;
    text-align: center;
}
.status, .remarks {
    color: gray;
    font-size: 14px;
    font-style: italic;
}
.full-height-container {
    height: 90vh;
    display: flex;
    flex-direction: column;
}
.alert-success {
    background-color: #28a745;
    color: white;
    padding: 15px;
    margin-bottom: 20px;
    font-size: 1.1em;
    border-radius: 5px;
}
</style>

<div class="container-fluid full-height-container">
    <div class="row">
        <div class="col-md-12">
            <h2>Application for Accreditation</h2>
            <div class="yellow-line"></div>
            <br>

            <?php
            if (isset($_SESSION['success'])) {
                echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
                unset($_SESSION['success']); 
            }

            if (isset($_SESSION['status'])) {
                echo '<div class="alert alert-danger">' . $_SESSION['status'] . '</div>';
                unset($_SESSION['status']);
            }
            ?>

            <!-- TABS: Submit Application vs. View Application Status -->
            <ul class="nav nav-tabs" id="applicationTabs" role="tablist">
                <?php if ($showSubmitTab): ?>
                <li class="nav-item">
                    <a class="nav-link active" 
                       id="submission-tab" 
                       data-bs-toggle="tab" 
                       href="#submission" 
                       role="tab" 
                       aria-controls="submission" 
                       aria-selected="true">
                       Submit Application
                    </a>
                </li>
                <?php endif; ?>

                <?php if ($showStatusTab): ?>
                <li class="nav-item">
                    <a class="nav-link" 
                       id="status-tab" 
                       data-bs-toggle="tab" 
                       href="#status" 
                       role="tab" 
                       aria-controls="status" 
                       aria-selected="false">
                       View Application Status
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <div class="tab-content">
                <?php if ($showSubmitTab): ?>
                <!-- SUBMIT APPLICATION TAB -->
                <div class="tab-pane fade show active" 
                     id="submission" 
                     role="tabpanel" 
                     aria-labelledby="submission-tab">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <br>
                        <div class="alert alert-info" role="alert">
                            Please upload only <strong>.pdf, .docx, or .doc</strong> files under <strong>5 MB</strong>.
                        </div>
                        <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                            <th>Documentary Requirements</th>
                            <th>File Upload</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($file_fields as $field_name => $requirement): ?>
                            <tr>
                                <td>
                                <strong>
                                    <?= $requirement['title']; ?>
                                    <span style="color:red; font-weight:bold;">*</span>
                                </strong>
                                <p style="font-size: 0.9em; font-style: italic;">
                                    <?= $requirement['description']; ?>
                                </p>
                                </td>
                                <td>
                                <input type="file" name="<?= $field_name; ?>" required>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        </table>

                        <br>
                        <button type="submit" class="btn btn-submit">Submit Application</button>
                        <br>
                    </form>
                    <br><br>
                </div>
                <?php endif; ?>

                <?php if ($showStatusTab): ?>
                <!-- VIEW STATUS TAB -->
                <div class="tab-pane fade" 
                     id="status" 
                     role="tabpanel" 
                     aria-labelledby="status-tab">
                     <br>
                    <div class="table-responsive">
                        <table 
                          class="table table-bordered" 
                          id="statusTable" 
                          style="width:100%">
                            <thead>
                                <tr>
                                    <th>Submission Date</th>
                                    <th>Application Status</th>
                                    <th>Hardcopy Submission Schedule</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php 
                            foreach ($user_applications as $application):
                                $appId = htmlspecialchars($application['application_id']);
                                // Info from $profile or $chairperson
                                $csoName = htmlspecialchars($profile['cso_name'] ?? '');
                                $chairFullName = htmlspecialchars($chairperson['first_name'] ?? '')
                                                 . ' '
                                                 . htmlspecialchars($chairperson['last_name'] ?? '');
                                $fullAddress = htmlspecialchars(
                                    ($chairperson['cso_address'] ?? '') . ', ' .
                                    ($chairperson['street'] ?? '') . ', ' .
                                    ($chairperson['barangay'] ?? '') . ', ' .
                                    ($chairperson['city'] ?? '') . ', ' .
                                    ($chairperson['province'] ?? '') . ', ' .
                                    ($chairperson['zip_code'] ?? '') . ', ' .
                                    ($chairperson['region'] ?? '')
                                );
                                $hardcopySchedule = (!empty($application['schedule']) && $application['schedule'] != '0000-00-00 00:00:00')
                                ? htmlspecialchars($application['schedule'])
                                : 'Not yet set.';
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars(date('Y-m-d', strtotime($application['created_at']))) ?></td>
                                    <td><?= ucfirst($application['status']) ?> <i>(<?= htmlspecialchars(date('Y-m-d', timestamp: strtotime($application['status_updated_at']))) ?>)</i></td>
                                    <td>
                                        <?= $hardcopySchedule ?>
                                    </td>
                                    <td>
                                        <a href="#"
                                           class="text-primary"
                                           data-bs-toggle="modal"
                                           data-bs-target="#appModal<?= $appId ?>">
                                           View Details
                                        </a>

                                        <!-- Modal for viewing details -->
                                        <div class="modal fade" 
                                             id="appModal<?= $appId ?>" 
                                             tabindex="-1" 
                                             aria-labelledby="appModalLabel<?= $appId ?>" 
                                             aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header" style="background-color:#0A593A;">
                                                        <h4 class="modal-title" 
                                                            id="appModalLabel<?= $appId ?>" 
                                                            style="color:white; font-weight:bold;">
                                                            Application Details - <?= $csoName ?>
                                                        </h4>
                                                        <button type="button" 
                                                                class="btn-close" 
                                                                data-bs-dismiss="modal" 
                                                                aria-label="Close">
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <h5 style="color:#0A593A; font-weight:bold; margin-bottom:0.5rem;">
                                                            CSO Information
                                                        </h5>
                                                        <p><strong>CSO Name:</strong> <?= $csoName ?></p>
                                                        <p><strong>Chairperson:</strong> <?= $chairFullName ?></p>
                                                        <p><strong>Address:</strong> <?= $fullAddress ?></p>
                                                        <hr>
                                                        <h5 style="color:#0A593A; font-weight:bold;">
                                                            Submitted Documents
                                                        </h5>
                                                        <?php
                                                        foreach ($file_fields as $field_name => $details) {
                                                            $file_url = htmlspecialchars($application[$field_name] ?? '');
                                                            $file_status = htmlspecialchars($application[$field_name . "_status"] ?? 'Pending');
                                                            $file_remarks = htmlspecialchars($application[$field_name . "_remarks"] ?? 'No remarks from CSO');
                                                            $file_admin_remarks = htmlspecialchars($application[$field_name . "_admin_remarks"] ?? 'No remarks from admin');

                                                            echo "<p><strong>{$details['title']}</strong>
                                                                <br><span class='text-muted'><i>{$details['description']}</i></span></p>";

                                                            if (!empty($file_url)) {
                                                                echo "<a href='{$file_url}' 
                                                                        target='_blank' 
                                                                        style='color:#0A593A; text-decoration:underline;'>
                                                                        View Document
                                                                    </a><br>";
                                                            } else {
                                                                echo "<p class='text-muted'>No document uploaded</p>";
                                                            }

                                                            echo "<p><strong>Status: </strong>
                                                                    <select class='form-control' disabled>
                                                                        <option ".($file_status=='Pending'?'selected':'').">Pending</option>
                                                                        <option ".($file_status=='Approved'?'selected':'').">Approved</option>
                                                                        <option ".($file_status=='Denied'?'selected':'').">Denied</option>
                                                                    </select>
                                                                </p>
                                                                <p><strong>CSO Remarks:</strong><br>
                                                                    <textarea class='form-control' disabled>{$file_remarks}</textarea>
                                                                </p>";

                                                            if ($application['cso_status'] == 'Approved') {
                                                                echo "<p><strong>Admin Remarks (RTS):</strong><br>
                                                                        <textarea class='form-control' disabled>{$file_admin_remarks}</textarea>
                                                                    </p>";
                                                            }

                                                            echo "<hr>";
                                                        }

                                                        echo '
                                                            <h5 style="color:#0A593A; font-weight:bold;">CSO Approval Status</h5>
                                                            <input type="text" class="form-control" value="' . htmlspecialchars($application['cso_status']) . '" readonly>
                                                            <hr>
                                                        ';
                                                        
                                                        if ($application['cso_status'] == 'Approved') {
                                                            echo"<h5 style='color:#0A593A; font-weight:bold;'>Administrative Assessment</h5>";
                                                            echo '<p><strong>Application Status: </strong><br><input type="text" class="form-control" value="' . htmlspecialchars($application['status']) . '" readonly></p>';                                                                                                                        
                                                            echo "<p><strong>RTS Application Remarks: </strong></p>";
                                                            echo "<p> <textarea class='form-control' disabled>" . htmlspecialchars($application['remarks']) . "</textarea></p>";
                                                            echo "<p><strong>Hardcopy Submission Schedule: </strong>". $hardcopySchedule ."</p>";
                                                        }
                                                        ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" 
                                                                class="btn btn-secondary" 
                                                                style="color:white; background-color: #0A593A; border: #0A593A;" 
                                                                data-bs-dismiss="modal">
                                                            Close
                                                        </button>
                                                    </div>
                                                </div><!-- /modal-content -->
                                            </div><!-- /modal-dialog -->
                                        </div><!-- /modal fade -->
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div><!-- /.table-responsive -->
                </div><!-- end #status tab -->
                <?php endif; ?>
            </div><!-- end .tab-content -->
        </div><!-- end col-md-12 -->
    </div><!-- end row -->
</div><!-- end container-fluid -->
<?php
include ('user_include/script.php');
?>
<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>

<!-- DataTables + Responsive CSS/JS -->
<script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap5.min.css">

<script>
$(document).ready(function () {
  $('#statusTable').DataTable({
    dom:
      // First row: places "Show entries" and "Search" side-by-side, aligned at bottom
      "<'row align-items-end'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
      // Next row: the table
      "rt" +
      // Last row: info and pagination
      "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    responsive: true,
    autoWidth: false,
    pageLength: 10,
    lengthMenu: [[5, 10, 25, 50, 100], [5, 10, 25, 50, 100]],
    language: {
      emptyTable: "No applications found.",
      zeroRecords: "No matching records found",
      search: "Search:",
      paginate: {
        next: "Next",
        previous: "Previous"
      }
    }
  });
});

</script>
