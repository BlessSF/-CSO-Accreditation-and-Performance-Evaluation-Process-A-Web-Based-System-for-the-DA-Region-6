<?php
session_start();
include('include/db_connect.php'); 

if (!isset($_SESSION['user_id'])) {
    die('Error: CSO representative not logged in.');
}

$cso_representative_id = $_SESSION['user_id'];
$allowedTypes = ["pdf", "doc", "docx"];

// ===========================
// Handle Form Submission
// ===========================
if (isset($_POST['submit_data'])) {
    // Financial data fields
    $net_operating_income = $_POST['net_operating_income'];
    $avg_operating_assets = $_POST['avg_operating_assets'];
    $current_assets       = $_POST['current_assets'];
    $current_liabilities  = $_POST['current_liabilities'];
    $total_liabilities    = $_POST['total_liabilities'];
    $total_assets         = $_POST['total_assets'];

    // Calculate ROI, Liquidity, and Solvency
    $roi       = ($avg_operating_assets != 0) ? ($net_operating_income / $avg_operating_assets) * 100 : null;
    $liquidity = ($current_liabilities != 0)  ? ($current_assets / $current_liabilities) : null;
    $solvency  = ($total_assets != 0)         ? ($total_liabilities / $total_assets) * 100 : null;

    // Fetch the CSO name for directory creation
    $stmt_cso_name = $conn->prepare("SELECT cso_name FROM cso_representative WHERE id = ?");
    $stmt_cso_name->bind_param("i", $cso_representative_id);
    $stmt_cso_name->execute();
    $stmt_cso_name->bind_result($cso_name);
    $stmt_cso_name->fetch();
    $stmt_cso_name->close();

    // Directories
    $accomplishment_dir = "accomplishment/" . $cso_name . "/";
    $financial_dir      = "financial_reports/" . $cso_representative_id . "/";

    if (!is_dir($accomplishment_dir)) {
        mkdir($accomplishment_dir, 0777, true);
    }
    if (!is_dir($financial_dir)) {
        mkdir($financial_dir, 0777, true);
    }

    // Make sure both files are present
    if (!isset($_FILES['file']) || !isset($_FILES['financial_file'])) {
        $_SESSION['status'] = "Both files are required for submission.";
    } else {
        // File paths
        $accomplishment_file    = $accomplishment_dir . basename($_FILES["file"]["name"]);
        $accomplishmentFileType = strtolower(pathinfo($accomplishment_file, PATHINFO_EXTENSION));

        $financial_file    = $financial_dir . basename($_FILES["financial_file"]["name"]);
        $financialFileType = strtolower(pathinfo($financial_file, PATHINFO_EXTENSION));

        // Validate file types
        if (!in_array($accomplishmentFileType, $allowedTypes) || !in_array($financialFileType, $allowedTypes)) {
            $_SESSION['status'] = "Only PDF, DOC & DOCX files are allowed for both uploads.";
        } else {
            // Move and insert
            if (
                move_uploaded_file($_FILES["file"]["tmp_name"], $accomplishment_file) &&
                move_uploaded_file($_FILES["financial_file"]["tmp_name"], $financial_file)
            ) {
                // Insert with admin_status & cso_status
                $stmt_insert = $conn->prepare("
                    INSERT INTO financial_report (
                        cso_representative_id, 
                        accomplishment_file_path, 
                        financial_file_path, 
                        net_operating_income, 
                        avg_operating_assets, 
                        current_assets, 
                        current_liabilities, 
                        total_liabilities, 
                        total_assets, 
                        roi, 
                        liquidity, 
                        solvency, 
                        upload_date,
                        admin_status,
                        cso_status
                    ) 
                    VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Pending', 'Pending'
                    )
                ");
                
                $stmt_insert->bind_param(
                    "issddddddddd",
                    $cso_representative_id, 
                    $accomplishment_file, 
                    $financial_file, 
                    $net_operating_income, 
                    $avg_operating_assets, 
                    $current_assets,
                    $current_liabilities, 
                    $total_liabilities, 
                    $total_assets, 
                    $roi, 
                    $liquidity, 
                    $solvency
                );

                if ($stmt_insert->execute()) {
                    $_SESSION['success'] = "Financial data and required files uploaded successfully!";
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit();
                } else {
                    $_SESSION['status'] = "Error submitting data.";
                }
                $stmt_insert->close();
            } else {
                $_SESSION['status'] = "Error uploading one or both files.";
            }
        }
    }
}

// ===========================
// Fetch Uploaded Files
// ===========================
$uploaded_files = [];
$sql = "
    SELECT 
        id,
        accomplishment_file_path,
        financial_file_path,
        DATE(upload_date) AS upload_date,
        net_operating_income,
        avg_operating_assets,
        current_assets,
        current_liabilities,
        total_liabilities,
        total_assets,
        roi,
        liquidity,
        solvency,
        admin_status,
        cso_status
    FROM financial_report
    WHERE cso_representative_id = ?
    ORDER BY upload_date DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $cso_representative_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $uploaded_files[] = $row;
}
$stmt->close();

$conn->close();
?>

<?php
include('user_include/header.php');
include('user_include/navbar.php');
?>
<style>
.container-fluid { 
    padding: 20px; 
}
th {
    background-color: #0A593A;
    color: white;
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
h5 {
    color: #0A593A;
}
.btn-primary,
.btn-success {
    background-color: #0A593A;
    color: white;
    width: 100%;
    border: none;
}
.btn-primary:hover,
.btn-success:hover {
    text-decoration: underline;
    background-color: #084C2E;
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
.full-height-container {
    height: 90vh; 
    display: flex;
    flex-direction: column;
}

/* Optional color-coded text classes (if you want custom styling) */
.rating-excellent { color: #28a745; font-weight: bold; }
.rating-verygood  { color: #20c997; font-weight: bold; }
.rating-good      { color: #17a2b8; font-weight: bold; }
.rating-fair      { color: #ffc107; font-weight: bold; }
.rating-poor      { color: #dc3545; font-weight: bold; }
</style>

<div id="wrapper">
<div class="container-fluid full-height-container">
    <div class="header-section">
        <div class="row">
            <div class="col-md-12">
                <h2>Submission of Requirements</h2>
            </div>
        </div>
        <div class="yellow-line"></div>
        <br>
    </div>

    <?php
    // Display session messages
    if (isset($_SESSION['success'])) {
        echo "<p class='success'>" . $_SESSION['success'] . "</p>";
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['status'])) {
        echo "<p class='status'>" . $_SESSION['status'] . "</p>";
        unset($_SESSION['status']);
    }
    ?>

    <ul class="nav nav-tabs" id="tabMenu" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" 
               id="upload-tab" 
               data-toggle="tab" 
               href="#upload" 
               role="tab" 
               aria-controls="upload" 
               aria-selected="true">
               Upload Requirements
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" 
               id="reports-tab" 
               data-toggle="tab" 
               href="#reports" 
               role="tab" 
               aria-controls="reports" 
               aria-selected="false">
               Submitted Requirements
            </a>
        </li>
    </ul>

    <div class="tab-content" id="tabContent">
        <!-- ==================================== -->
        <!-- Upload Section -->
        <!-- ==================================== -->
        <div class="tab-pane fade show active" id="upload" role="tabpanel" aria-labelledby="upload-tab">
            <div class="row">
                <div class="col-md-6">
                    <br>
                    <form action="" method="post" enctype="multipart/form-data">
                        <h5><strong>Accomplishment Report</strong></h5>
                        <div class="form-group">
                            <label for="file">
                                Accomplishment Report (.pdf) 
                                <span style="color:red; font-weight:bold;">*</span>
                            </label>
                            <br>
                            <input type="file" name="file" id="file" onchange="previewFile(event)">
                        </div>

                        <hr>
                        <h5><strong>Financial Statement</strong></h5>
                        <h6><strong>Return on Investment (ROI)</strong></h6>
                        <div class="form-group">
                            <label for="net_operating_income">
                                Net Operating Income 
                                <span style="color:red; font-weight:bold;">*</span>
                            </label>
                            <input type="number" class="form-control" name="net_operating_income" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="avg_operating_assets">
                                Average Operating Assets 
                                <span style="color:red; font-weight:bold;">*</span>
                            </label>
                            <input type="number" class="form-control" name="avg_operating_assets" step="0.01" required>
                        </div>

                        <h6><strong>Liquidity</strong></h6>
                        <div class="form-group">
                            <label for="current_assets">
                                Current Assets 
                                <span style="color:red; font-weight:bold;">*</span>
                            </label>
                            <input type="number" class="form-control" name="current_assets" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="current_liabilities">
                                Current Liabilities 
                                <span style="color:red; font-weight:bold;">*</span>
                            </label>
                            <input type="number" class="form-control" name="current_liabilities" step="0.01" required>
                        </div>

                        <h6><strong>Solvency</strong></h6>
                        <div class="form-group">
                            <label for="total_liabilities">
                                Total Liabilities 
                                <span style="color:red; font-weight:bold;">*</span>
                            </label>
                            <input type="number" class="form-control" name="total_liabilities" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="total_assets">
                                Total Assets 
                                <span style="color:red; font-weight:bold;">*</span>
                            </label>
                            <input type="number" class="form-control" name="total_assets" step="0.01" required>
                        </div>

                        <div class="form-group">
                            <label for="financial_file">
                                Financial Statement (.pdf) 
                                <span style="color:red; font-weight:bold;">*</span>
                            </label>
                            <br>
                            <input type="file" name="financial_file" id="financial_file" onchange="previewFile(event)" required>
                        </div>

                        <br>
                        <button type="submit" class="btn btn-success" name="submit_data">
                            Submit
                        </button>
                    </form>
                    <br><br>
                </div>

                <div class="col-md-6">
                    <div class="pdf-viewer-container mt-3">
                        <p style="background-color: #0A593A; color: white; padding: 5px; text-align: center;">
                            File Preview
                        </p>
                        <embed 
                            id="pdf-viewer" 
                            type="application/pdf" 
                            width="100%" 
                            height="767"
                        >
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================================== -->
        <!-- Financial Reports Table Section -->
        <!-- ==================================== -->
        <div class="tab-pane fade" id="reports" role="tabpanel" aria-labelledby="reports-tab">
            <br>
            <h4 style="color: #0A593A;"><strong>Submitted Proposals</strong></h4>
            <br>
            <div class="table-responsive">
                <div class="row">
                    <div class="col-md-12"></thead>
                        <table id="financialTable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Date Submitted</th>
                                    <th>Accomplishment Report</th>
                                    <th>ROI (>=7%)</th>
                                    <th>Liquidity (>1.0)</th>
                                    <th>Solvency (<=60%)</th>
                                    <th>Financial Statement</th>
                                    <th>Admin Status</th>
                                    <th>CSO Verficiation Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($uploaded_files as $file): ?>
                                <tr>
                                    <td><?php echo $file['upload_date']; ?></td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($file['accomplishment_file_path']); ?>" 
                                           target="_blank">
                                           View Report
                                        </a>
                                    </td>
                                    <td>
                                        <?php 
                                        echo ($file['avg_operating_assets'] != 0)
                                            ? number_format(($file['net_operating_income'] / $file['avg_operating_assets']) * 100, 2) . '%'
                                            : 'N/A'; 
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        echo ($file['current_liabilities'] != 0)
                                            ? number_format($file['current_assets'] / $file['current_liabilities'], 2)
                                            : 'N/A'; 
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        echo ($file['total_assets'] != 0)
                                            ? number_format(($file['total_liabilities'] / $file['total_assets']) * 100, 2) . '%'
                                            : 'N/A'; 
                                        ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($file['financial_file_path']); ?>" 
                                           target="_blank">
                                           View Statement
                                        </a>
                                    </td>
                                    <!-- Admin & CSO Status columns -->
                                    <td><?php echo htmlspecialchars($file['admin_status']); ?></td>
                                    <td><?php echo htmlspecialchars($file['cso_status']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div> <!-- end .tab-pane (reports) -->
    </div> <!-- end .tab-content -->
</div> <!-- end .container-fluid -->
</div> <!-- end #wrapper -->
</div> <!-- extra closing div? -->

<?php
include('user_include/script.php');
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" type="text/css" 
      href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">

<script>
function previewFile(event) {
    const file = event.target.files[0];
    if (file && file.type === 'application/pdf') {
        const fileURL = URL.createObjectURL(file);
        document.getElementById('pdf-viewer').src = fileURL;
    } else {
        alert("Please select a PDF file for preview.");
    }
}

$(document).ready(function() {
    $('#financialTable').DataTable({
        responsive: true,
        autoWidth: false,
        pageLength: 10,
        language: {
            emptyTable: "No applications found.",
            zeroRecords: "No matching records found"
        },
        stripeClasses: []
    });
});
</script>
