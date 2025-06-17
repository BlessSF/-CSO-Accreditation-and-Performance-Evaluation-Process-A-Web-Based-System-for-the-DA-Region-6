<?php
session_start();
include('include/db_connect.php'); 

// ---------------------------------------------------
// 1) If this is an AJAX POST to update cso_status
// ---------------------------------------------------
if (isset($_POST['report_id'], $_POST['new_status'])) {
    $reportId  = (int)$_POST['report_id'];
    $newStatus = $_POST['new_status'];

    // Update the cso_status
    $stmt = $conn->prepare("UPDATE financial_report SET cso_status = ? WHERE id = ?");
    $stmt->bind_param('si', $newStatus, $reportId);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "Error: " . $conn->error;
    }
    $stmt->close();
    $conn->close();
    exit;
}

// ---------------------------------------------------
// 2) Otherwise, this is a normal GET request to show the page
// ---------------------------------------------------
if (!isset($_SESSION['cso_name'])) {
    die('Error: CSO not logged in.');
}

$cso_name = $_SESSION['cso_name'];

// If the page is reloaded after some action, 
// show any success or error messages from SESSION
$successMessage = '';
$errorMessage   = '';
if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['status'])) {
    $errorMessage = $_SESSION['status'];
    unset($_SESSION['status']);
}

// Fetch all representative IDs for this CSO
$sql_fetch_representatives = "
    SELECT id
    FROM cso_representative
    WHERE cso_name = ?
";
$stmt_representatives = $conn->prepare($sql_fetch_representatives);
$stmt_representatives->bind_param("s", $cso_name);
$stmt_representatives->execute();
$result_representatives = $stmt_representatives->get_result();

$representative_ids = [];
while ($row = $result_representatives->fetch_assoc()) {
    $representative_ids[] = $row['id'];
}
$stmt_representatives->close();

// ---------------------------------------------------
// 3) Build the query. If no reps, do a dummy query returning zero rows
// ---------------------------------------------------
if (empty($representative_ids)) {
    // No reps => no data => DataTables will show “No applications found.”
    $sql = "SELECT * FROM financial_report WHERE 1=0";
    $stmt = $conn->prepare($sql);
} else {
    // Use placeholders for the IN clause
    $placeholders = implode(',', array_fill(0, count($representative_ids), '?'));

    $sql = "
        SELECT 
            id,
            cso_representative_id,
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
            indication,
            cso_status
        FROM financial_report
        WHERE cso_representative_id IN ($placeholders)
        ORDER BY upload_date DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('i', count($representative_ids)), ...$representative_ids);
}

// Execute and fetch all rows
$stmt->execute();
$result = $stmt->get_result();
$uploaded_files = [];
while ($row = $result->fetch_assoc()) {
    $uploaded_files[] = $row;
}
$stmt->close();
$conn->close();

// ---------------------------------------------------
// 4) Now output the page HTML + JS
// ---------------------------------------------------
?>

<?php
include('cso_include/header.php');
include('cso_include/navbar.php');
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
    .btn-primary,
    .btn-success {
        background-color: #0A593A;
        color: white;
        border: none;
    }
    .btn-primary:hover,
    .btn-success:hover {
        text-decoration: underline;
        background-color: #084C2E;
    }
</style>

<div id="wrapper">
<div class="container-fluid">
    <div class="header-section">
        <div class="row">
            <div class="col-md-12">
                <h2>Submitted Requirements</h2>
            </div>
        </div>
        <div class="yellow-line"></div>
        <br>
    </div>

    <!-- Display any status messages -->
    <?php if ($successMessage): ?>
        <p style="color: green; font-weight: bold;"><?php echo $successMessage; ?></p>
    <?php endif; ?>
    <?php if ($errorMessage): ?>
        <p style="color: red; font-weight: bold;"><?php echo $errorMessage; ?></p>
    <?php endif; ?>

    <div class="table-responsive">
        <table id="financialTable" class="table table-bordered">
            <thead>
                <tr>
                    <th>Date Submitted</th>
                    <th>Accomplishment Report</th>
                    <th>ROI (>=7%)</th>
                    <th>Liquidity (>1.0)</th>
                    <th>Solvency (<=60%)</th>
                    <th>Financial Statement</th>
                    <th>Financial Indication</th>
                    <th>CSO Verification Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($uploaded_files as $file): ?>
                <tr>
                    <td><?php echo htmlspecialchars($file['upload_date']); ?></td>
                    <td>
                        <a href="<?php echo htmlspecialchars($file['accomplishment_file_path']); ?>" target="_blank">
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
                        <a href="<?php echo htmlspecialchars($file['financial_file_path']); ?>" target="_blank">
                            View Statement
                        </a>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($file['indication']); ?>
                    </td>
                    <!-- cso_status with no Update button. The dropdown triggers an AJAX call -->
                    <td>
                        <select 
                            class="form-control form-control-sm cso-status-dropdown" 
                            data-report-id="<?php echo (int)$file['id']; ?>"
                            <?php if ($file['cso_status'] === 'Approved' || $file['cso_status'] === 'Denied') echo 'disabled'; ?>
                        >
                            <option value="Pending"  <?php if ($file['cso_status'] === 'Pending') echo 'selected'; ?>>Pending</option>
                            <option value="Approved" <?php if ($file['cso_status'] === 'Approved') echo 'selected'; ?>>Approved</option>
                            <option value="Denied"   <?php if ($file['cso_status'] === 'Denied') echo 'selected'; ?>>Denied</option>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</div>

<?php
include('cso_include/script.php');
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" type="text/css" 
      href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">

<script>
$(document).ready(function() {
    // Initialize DataTables
    $('#financialTable').DataTable({
        responsive: false,
        autoWidth: false,
        scrollX: true,
        pageLength: 10,
        stripeClasses: [],
        language: {
            emptyTable: "No applications found.",
            zeroRecords: "No matching records found"
        }
    });

    // When user changes dropdown, auto-update cso_status via AJAX
    $('.cso-status-dropdown').change(function() {
        var reportId  = $(this).data('report-id');
        var newStatus = $(this).val();

        // AJAX POST back to the same file
        $.ajax({
            url: '', // same file
            type: 'POST',
            data: {
                report_id:  reportId,
                new_status: newStatus
            },
            success: function(response) {
                if (response.trim() === 'success') {
                    alert('Status updated to ' + newStatus);
                    location.reload();
                } else {
                    alert('Error updating status: ' + response);
                }
            },
            error: function(xhr, status, error) {
                alert('Error updating status: ' + error);
            }
        });
    });
});
</script>
