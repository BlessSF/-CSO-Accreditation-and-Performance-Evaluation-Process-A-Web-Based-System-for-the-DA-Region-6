<?php
session_start();
include('cso_include/header.php');
include('cso_include/navbar.php');
include('include/db_connect.php'); 

if (!isset($_GET['rep_id'])) {
    header("Location: index.php"); 
    exit();
}

$rep_id = $_GET['rep_id'];

// Function to handle SQL errors
function handle_sql_error($error_message) {
    error_log($error_message); // Log the error for debugging
    die("An error occurred while processing your request. Please try again later.");
}

// Fetch representative details
$sql_fetch_representative = "SELECT * FROM cso_representative WHERE id = ?";
$stmt_representative = $conn->prepare($sql_fetch_representative);
if (!$stmt_representative) handle_sql_error($conn->error);
$stmt_representative->bind_param("i", $rep_id);
$stmt_representative->execute();
$result_representative = $stmt_representative->get_result();

if (!$result_representative || $result_representative->num_rows == 0) {
    die("CSO representative not found."); 
}

$representative = $result_representative->fetch_assoc();

$count = 1; 

// Fetch different applications and documents for the representative
$sql_fetch_renewal = "SELECT * FROM renewal_application WHERE cso_representative_id = ?";
$stmt_renewal = $conn->prepare($sql_fetch_renewal);
if (!$stmt_renewal) handle_sql_error($conn->error);
$stmt_renewal->bind_param("i", $rep_id);
$stmt_renewal->execute();
$result_renewal = $stmt_renewal->get_result();

$sql_fetch_accreditation = "SELECT * FROM accreditation_application WHERE cso_representative_id = ?";
$stmt_accreditation = $conn->prepare($sql_fetch_accreditation);
if (!$stmt_accreditation) handle_sql_error($conn->error);
$stmt_accreditation->bind_param("i", $rep_id);
$stmt_accreditation->execute();
$result_accreditation = $stmt_accreditation->get_result();

// Check if the 'documents' table exists
$sql_fetch_documents = "SELECT * FROM documents WHERE cso_representative_id = ?";
$stmt_documents = $conn->prepare($sql_fetch_documents);
if (!$stmt_documents) {
    die("The documents table does not exist in the database. Please contact the administrator.");
}
$stmt_documents->bind_param("i", $rep_id);
$stmt_documents->execute();
$result_documents = $stmt_documents->get_result();

$sql_fetch_financial = "SELECT * FROM financial_report WHERE cso_representative_id = ?";
$stmt_financial = $conn->prepare($sql_fetch_financial);
if (!$stmt_financial) handle_sql_error($conn->error);
$stmt_financial->bind_param("i", $rep_id);
$stmt_financial->execute();
$result_financial = $stmt_financial->get_result();
?>
<style>
    th {
        background-color: #0A593A;
        color: white;
    }
    h4, h6 {
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

    h2, h3 { 
        color: #0A593A; 
        font-weight: bold; 
    }

    .btn {
        background-color: #0A593A;
        color: white;
    }
</style>

<div id="wrapper">
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <a href="cso_documents.php" class="btn btn-secondary mb-3">Back</a>
            <br><br>
            <h3>Representative <?php echo htmlspecialchars($representative['first_name'] . ' ' . $representative['last_name'] . ' ' . $representative['suffix']); ?> Files</h3>
        </div>
    </div>
    <div class="yellow-line"></div>
    <br>
    <div class="mb-4">
        <hr>
        <h4>Renewal Applications</h4>
        <?php if ($result_renewal->num_rows > 0): ?>
            <?php foreach ($result_renewal as $renewal): ?>
                <div class="card mb-4" style="border-radius: 10px; border: 1px solid #ccc; padding: 15px;">
                    <div class="card-body">
                        <p><strong>Application Date:</strong> <i><?php echo htmlspecialchars($renewal['created_at']); ?></i></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars($renewal['status']); ?> <i style="font-size:11px;">(<?php echo htmlspecialchars($renewal['status_updated_at']); ?>)</i></p>
                        <p><strong>Hardcopy Submission Date:</strong> <?php echo htmlspecialchars($renewal['hardcopy_submission']); ?></p>
                        <p><strong>Hardcopy Status:</strong> <?php echo htmlspecialchars($renewal['hardcopy_status']); ?></p>
                        <p><strong>Remarks:</strong><br><?php echo htmlspecialchars($renewal['remarks']); ?></p>
                        <h6>Submitted Documents:</h6>
                        <ul class="list-unstyled">
                            <?php
                            $documents = [
                                'application_form' => 'DA-CSO Application Form',
                                'datasheet_org' => 'Data Sheet',
                                'goodstanding_lce' => 'Certificate of Good Standing',
                                'permit_bir' => "Mayor's Permit & BIR Registration",
                                'certificate_reg' => 'Certificate of Registration',
                                'goodstanding_ga' => 'Certificate of Good Standing (GA)',
                                'omnibus' => 'Omnibus Sworn Statement',
                                'incumbent_officers' => "Secretary's Certificate",
                                'accomplishment_reports' => 'Accomplishment Reports',
                                'supplementary' => 'Supplementary Documents'
                            ];
                            foreach ($documents as $field => $label) {
                                if (!empty($renewal[$field])) {
                                    $file_url = htmlspecialchars($renewal[$field]);
                                    echo "<li><a href='{$file_url}' target='_blank'>{$label}</a></li>";
                                }
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No renewal applications available for this user.</p>
        <?php endif; ?>
    </div>
    <hr>
    <div class="mb-4">
        <h4>Accreditation Applications</h4>
        <?php if ($result_accreditation->num_rows > 0): ?>
            <!-- Display Accreditation Applications -->
        <?php else: ?>
            <p>No accreditation applications available for this user.</p>
        <?php endif; ?>
    </div>
    <hr>
    <div class="mb-4">
        <h4>Accomplishment Reports</h4>
        <?php if ($result_financial->num_rows > 0): ?>
            <!-- Display Financial Reports -->
        <?php else: ?>
            <p>No accomplishment reports available for this user.</p>
        <?php endif; ?>
    </div>
</div>
</div>
<?php
include('cso_include/footer.php'); 
?>
