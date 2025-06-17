<?php
include('admin_include/header.php');
include('admin_include/navbar.php');
include('include/db_connect.php');

// 1) Handle AJAX for admin_status updates.
if ($_SERVER['REQUEST_METHOD'] === 'POST' 
    && isset($_POST['action']) 
    && $_POST['action'] === 'update_admin_status') 
{
    // This block is no longer needed if you're removing admin status,
    // but you can leave it in case you need it later.
    if (isset($_POST['report_id'], $_POST['new_admin_status'])) {
        $reportId       = (int) $_POST['report_id'];
        $newAdminStatus = $_POST['new_admin_status'];

        $stmtUpdate = $conn->prepare("
            UPDATE financial_report
               SET admin_status = ?
             WHERE id = ?
        ");
        if (!$stmtUpdate) {
            echo "Error preparing statement: " . $conn->error;
            $conn->close();
            exit;
        }

        $stmtUpdate->bind_param('si', $newAdminStatus, $reportId);

        if ($stmtUpdate->execute()) {
            echo "success"; // We still echo "success" so the front end knows it worked
        } else {
            echo "Error updating admin_status: " . $conn->error;
        }
        $stmtUpdate->close();
    } else {
        echo "Invalid request: missing report_id or new_admin_status.";
    }

    $conn->close();
    exit; // stop rendering the rest of the page
}

// 2) On normal GET, fetch data to display.
function fetchFinancialReports($conn)
{
    $sql = "
        SELECT 
            f.id,
            f.accomplishment_file_path,
            f.financial_file_path,
            DATE(f.upload_date) AS upload_date,
            f.roi, 
            f.liquidity, 
            f.solvency,
            f.admin_status,
            f.cso_status,
            f.indication,
            cr.cso_name
        FROM financial_report f
        JOIN cso_representative cr 
          ON f.cso_representative_id = cr.id
        JOIN cso_chairperson cc 
          ON cr.cso_name = cc.cso_name
        ORDER BY f.upload_date DESC
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error preparing query: " . $conn->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $reports = [];
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
    $stmt->close();
    return $reports;
}

$financial_reports = fetchFinancialReports($conn);

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
        background-color: rgb(253,199,5);
        height: 7px;
        width: 100%;
        margin: 0;
        padding: 0;
    }
    h2 {
        color: #0A593A;
        font-weight: bold;
    }
    .full-height-container {
        height: 90vh;
        display: flex;
        flex-direction: column;
    }
    .btn-sm {
        padding: 2px 8px;
        font-size: 14px;
    }
    .form-control-sm {
        font-size: 14px;
        padding: 2px 8px;
    }
</style>

<div id="wrapper">
    <div class="container-fluid full-height-container">
        <div class="row">
            <div class="col-md-12">
                <h2>Submission of Requirements</h2>
                <div class="yellow-line"></div>
                <br>

                <div class="table-responsive">
                    <table id="financialTable" class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Date Submitted</th>
                                <th>CSO Name</th>
                                <th>Accomplishment Report</th>
                                <th>ROI (>=7%)</th>
                                <th>Liquidity (>1.0)</th>
                                <th>Solvency (<=60%)</th>
                                <th>Financial Statement</th>
                                <th>Financial Indication</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($financial_reports as $report): ?>
                            <tr>
                                <td><?= htmlspecialchars($report['upload_date']) ?></td>
                                <td><?= htmlspecialchars($report['cso_name']) ?></td>
                                <td>
                                    <a href="<?= htmlspecialchars($report['accomplishment_file_path']) ?>"
                                       target="_blank">View Report</a>
                                </td>
                                <td>
                                    <?= is_null($report['roi'])
                                        ? 'N/A'
                                        : number_format($report['roi'], 2) . '%' ?>
                                </td>
                                <td>
                                    <?= is_null($report['liquidity'])
                                        ? 'N/A'
                                        : number_format($report['liquidity'], 2) ?>
                                </td>
                                <td>
                                    <?= is_null($report['solvency'])
                                        ? 'N/A'
                                        : number_format($report['solvency'], 2) . '%' ?>
                                </td>
                                <td>
                                    <a href="<?= htmlspecialchars($report['financial_file_path']) ?>"
                                       target="_blank">View Statement</a>
                                </td>
                                <td><?= htmlspecialchars($report['indication']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div><!-- end .table-responsive -->
            </div>
        </div>
    </div>
</div>

<?php include('admin_include/script.php'); ?>

<!-- JS + DataTables scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet"
      type="text/css"
      href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">

<script>
$(document).ready(function() {
    // Initialize DataTables
    $('#financialTable').DataTable({
        responsive: false,
        autoWidth: false,
        scrollX: true,
        scrollY: true,
        pageLength: 10,
        stripeClasses: [],
        language: {
            emptyTable: "No data available in table",
            zeroRecords: "No matching records found"
        }
    });

    // The following AJAX code for admin_status updates has been removed since the column is removed.
});
</script>







