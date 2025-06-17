<?php
session_start();
include('include/db_connect.php');

if (!isset($_SESSION['cso_name'])) {
    exit('Session cso_name not set');
}

$cso_name = $_SESSION['cso_name'];

// Fetch applications by cso_status for this specific CSO, including admin remarks columns
function fetchApplicationsByCSOStatus($cso_status, $conn, $documents, $cso_name) {
    $placeholders = implode(',', array_fill(0, count($cso_status), '?'));
    $sql = "
    SELECT
        aa.application_id, aa.cso_representative_id,
        aa.datasheet_org, aa.datasheet_org_status, aa.datasheet_org_remarks, aa.datasheet_org_admin_remarks,
        aa.goodstanding_lce, aa.goodstanding_lce_status, aa.goodstanding_lce_remarks, aa.goodstanding_lce_admin_remarks,
        aa.permit_mayor, aa.permit_mayor_status, aa.permit_mayor_remarks, aa.permit_mayor_admin_remarks,
        aa.permit_bir, aa.permit_bir_status, aa.permit_bir_remarks, aa.permit_bir_admin_remarks,
        aa.certificate_reg, aa.certificate_reg_status, aa.certificate_reg_remarks, aa.certificate_reg_admin_remarks,
        aa.goodstanding_ga, aa.goodstanding_ga_status, aa.goodstanding_ga_remarks, aa.goodstanding_ga_admin_remarks,
        aa.omnibus, aa.omnibus_status, aa.omnibus_remarks, aa.omnibus_admin_remarks,
        aa.bio_data, aa.bio_data_status, aa.bio_data_remarks, aa.bio_data_admin_remarks,
        aa.articles_of_incorporation, aa.articles_of_incorporation_status, aa.articles_of_incorporation_remarks, aa.articles_of_incorporation_admin_remarks,
        aa.incumbent_officers, aa.incumbent_officers_status, aa.incumbent_officers_remarks, aa.incumbent_officers_admin_remarks,
        aa.accomplishment_reports, aa.accomplishment_reports_status, aa.accomplishment_reports_remarks, aa.accomplishment_reports_admin_remarks,
        aa.disclosure, aa.disclosure_status, aa.disclosure_remarks, aa.disclosure_admin_remarks,
        aa.affidavit, aa.affidavit_status, aa.affidavit_remarks, aa.affidavit_admin_remarks,
        aa.status, aa.cso_status, aa.remarks, aa.created_at, aa.schedule,
        cr.first_name, cr.last_name, cr.middle_name, cr.suffix, cr.email,
        cc.cso_name, CONCAT(cc.cso_address, ', ', cc.street, ', ', cc.barangay, ', ', cc.city, ', ',
        cc.province, ', ', cc.zip_code, ', ', cc.region) AS cso_full_address,
        cc.first_name AS chair_first_name, cc.middle_name AS chair_middle_name, cc.last_name AS chair_last_name,
        cc.suffix AS chair_suffix, cc.email AS chair_email

    FROM renewal_application aa
    JOIN cso_representative cr ON aa.cso_representative_id = cr.id
    JOIN cso_chairperson cc ON cr.cso_name = cc.cso_name
    WHERE aa.cso_status IN ($placeholders) AND cr.cso_name = ?
    ORDER BY aa.created_at DESC";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    $statusParams = array_merge($cso_status, [$cso_name]);
    $stmt->bind_param(str_repeat('s', count($cso_status) + 1), ...$statusParams);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Define document fields
$documents = [
    'datasheet_org' => [
        'title' => 'Accomplished Data Sheet with Organizational Set-up',
        'description' => 'A fully completed data sheet outlining the organizational structure of the CSO...'
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
        'description' => 'Issued by SEC, CDA, or DOLE-BRW...'
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
        'title' => 'Disclosure by CSO Directors and Trustees',
        'description' => 'A disclosure statement by the CSO’s Directors and Trustees...'
    ],
    'affidavit' => [
        'title' => 'Sworn Affidavit of the Secretary of CSO',
        'description' => 'A sworn affidavit from the Secretary...'
    ],
];

$pendingApproval = fetchApplicationsByCSOStatus(['Pending'], $conn, $documents, $cso_name); 
$approvedApproval = fetchApplicationsByCSOStatus(['Approved'], $conn, $documents, $cso_name); 
$deniedApproval = fetchApplicationsByCSOStatus(['Denied'], $conn, $documents, $cso_name); 

include('cso_include/header.php');
include('cso_include/navbar.php');
?>

<style>
    .container-fluid {
        padding: 20px;
    }
    .card-header {
        background-color: #0A593A;
        color: white;
    }
    .yellow-line {
        background-color: rgb(253, 199, 5);
        height: 7px;
        width: 100%;
        margin: 0;
        padding: 0;
        position: relative;
        z-index: 1;
    }
    h2, h3 {
        color: #0A593A;
        font-weight: bold;
    }
    h5, label {
        font-weight: bold;
        color: #0A593A;
    }
    th {
        background-color: #0A593A;
        color: white;
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
        margin-bottom: 10px;
    }
    .modal-body .row {
        margin-bottom: 15px;
    }
    .modal-header {
        background-color: #0A593A;
        color: white;
    }
    .modal-header h5 {
        font-weight: bold;
        font-size: 18px;
    }
    .modal-footer {
        border-top: 1px solid #dee2e6;
        padding-top: 15px;
        text-align: right;
    }
    .modal-content {
        border-radius: 8px;
    }
    .modal-header .btn-close:hover {
        color: #007bff;
    }
    .modal-title {
        font-weight: bold;
        color: white;
    }
    .modal-footer .btn-secondary {
        background-color: #0A593A;
    }
    .modal-normal {
        width: 100%;
        max-width: 800px;
        transition: all 0.3s ease;
    }
    .full-height-container {
        height: 90vh;
        display: flex;
        flex-direction: column;
    }
    .view-document-link {
        color: #0A593A;
        display: inline-block;
        margin-top: 1rem;
        margin-bottom: 0.5rem;
        text-decoration: underline;
    }
    .view-document-link:hover {
        color: #0A593A;
        text-decoration: underline;
        font-weight: bold;
    }
</style>

<div class="container-fluid full-height-container">
    <div class="row">
        <div class="col-md-12">
            <h2>Submissions for Renewal of Accreditation</h2>
            <div class="yellow-line"></div>
            <br>

            <!-- Tabs navigation -->
            <ul class="nav nav-tabs" id="applicationTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="approval-tab" data-bs-toggle="tab" href="#approval-applications" role="tab">Pending</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="submitted-tab" data-bs-toggle="tab" href="#submitted-applications" role="tab">Approved</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="denied-tab" data-bs-toggle="tab" href="#denied-applications" role="tab">Denied</a>
                </li>
            </ul>
            
            <!-- Tab content -->
            <div class="tab-content">
                <div class="tab-pane fade show active" id="approval-applications" role="tabpanel" aria-labelledby="approval-tab">
                    <br>
                    <div class="table-responsive">
                        <table id="pendingTable" class="display table table-bordered dt-responsive wrap" width="100%">
                            <thead>
                                <tr>
                                <th>Date Submitted</th>
                                <th>Representative</th>
                                <th>CSO Status</th>
                                <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($pendingApproval as $application): $applicationId = htmlspecialchars($application['application_id']); ?>
                                    <tr>
                                        <td><?= $application['created_at'] ?></td>
                                        <td><?= $application['first_name'] ?> <?= $application['last_name'] ?></td>
                                        <td><?= $application['cso_status'] ?></td>
                                        <td class="center-text">
                                            <a href="#" 
                                            class="text-primary"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#appModal<?= $application['application_id'] ?>">View Application</a>
                                        </td>
                                    </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="tab-pane fade" id="submitted-applications" role="tabpanel" aria-labelledby="submitted-tab">
                    <br>
                    <div class="table-responsive">
                        <table id="approvedTable" class="display table table-bordered dt-responsive wrap" width="100%">
                            <thead>
                                <tr>
                                <th>Date Submitted</th>
                                <th>Representative</th>
                                <th>Hardcopy Submission Schedule</th>
                                <th>Status</th>
                                <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($approvedApproval as $application): $applicationId = htmlspecialchars($application['application_id']); ?>
                            <?php
                                $hardcopySchedule = (!empty($application['schedule']) && $application['schedule'] != '0000-00-00 00:00:00')
                                ? htmlspecialchars($application['schedule'])
                                : 'Not yet set.';
                            ?>
                                <tr>
                                        <td><?= $application['created_at'] ?></td>
                                        <td><?= $application['first_name'] ?> <?= $application['last_name'] ?></td>
                                        <td><?= $hardcopySchedule ?></td>
                                        <td><?= $application['status'] ?></td>
                                        <td class="center-text">
                                            <a href="#" 
                                            class="text-primary"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#appModal<?= $application['application_id'] ?>">View Application</a>
                                        </td>
                                    </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="tab-pane fade" id="denied-applications" role="tabpanel" aria-labelledby="denied-tab">
                    <br>
                    <div class="table-responsive">
                        <table id="deniedTable" class="display table table-bordered dt-responsive wrap" width="100%">
                            <thead>
                                <tr>
                                <th>Date Submitted</th>
                                <th>Representative</th>
                                <th>CSO Status</th>
                                <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($deniedApproval as $application): $applicationId = htmlspecialchars($application['application_id']); ?>
                                    <tr>
                                        <td><?= $application['created_at'] ?></td>
                                        <td><?= $application['first_name'] ?> <?= $application['last_name'] ?></td>
                                        <td><?= $application['cso_status'] ?></td>
                                        <td class="center-text">
                                            <a href="#" 
                                            class="text-primary"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#appModal<?= $application['application_id'] ?>">View Application</a>
                                        </td>
                                    </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="appModal<?php echo $application['application_id']; ?>" tabindex="-1" aria-labelledby="appModalLabel<?php echo $application['application_id']; ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        <!-- Modal Header -->
        <div class="modal-header" style="position: relative;">
            <h5 class="modal-title" id="appModalLabel<?php echo $application['application_id']; ?>">
            Application Details - <?php echo htmlspecialchars($application['cso_name']) ?>
            </h5>
        <button type="button" class="custom-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 20%; right: 0; font-size: 24px; color: white; background: transparent; border: none; padding: 0 15px; line-height: 1;">×</button>
        </div>
        <!-- Modal Body -->
        <div class="modal-body">
            <h5 class="mb-2">CSO Information</h5>
                <p><strong>CSO Name:</strong> <?= $application['cso_name'] ?></p>
                <p><strong>Chairperson:</strong> <?= $application['chair_first_name'] . ' ' . $application['chair_last_name'] ?></p>
                <p><strong>Address:</strong> <?= $application['cso_full_address'] ?></p>
                                    
                <hr>
            <h5 class="mb-2">Submitted Documents</h5>
            <ul class="list-unstyled">
                <?php foreach ($documents as $field => $info): 
                    $file_url = $application[$field] ?? '';
                    $file_status = $application["{$field}_status"] ?? 'Pending';
                    $file_remarks = $application["{$field}_remarks"] ?? '';
                    $file_admin_remarks = $application["{$field}_admin_remarks"] ?? '';
                    $display_remarks = !empty($file_remarks) ? htmlspecialchars($file_remarks) : 'No remarks';
                    $is_editable = $application['cso_status'] === 'Pending';
                ?>
                    <li class="mb-3">
                    <p><strong><?= htmlspecialchars($info['title']) ?></strong></p>
                    <p class="text-muted"><?= htmlspecialchars($info['description']) ?></p>

                    <?php if (!empty($file_url)): ?>
                        <a href="<?= htmlspecialchars($file_url) ?>" target="_blank" class="view-document-link">View Document</a>
                    <?php else: ?>
                        <p class="text-muted">No document uploaded</p>
                    <?php endif; ?>

                    <p>Status:
                        <select class="form-control status-dropdown" data-id="<?= htmlspecialchars($application['application_id']) ?>" data-field="<?= htmlspecialchars($field) ?>" <?= !$is_editable ? 'disabled' : '' ?>>
                        <option value="Pending" <?= $file_status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Approved" <?= $file_status === 'Approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="Denied" <?= $file_status === 'Denied' ? 'selected' : '' ?>>Denied</option>
                        </select>
                    </p>

                    <p>Remarks:
                        <textarea class="form-control remarks-input" data-id="<?= htmlspecialchars($application['application_id']) ?>" data-field="<?= htmlspecialchars($field) ?>" <?= !$is_editable ? 'readonly' : '' ?>><?= htmlspecialchars($file_remarks) ?></textarea>
                    </p>

                    <?php if ($application['cso_status'] === 'Approved'): ?>
                        <p><strong>Admin Remarks (RTS):</strong><br>
                        <textarea class="form-control" disabled><?= htmlspecialchars($file_admin_remarks) ?></textarea>
                        </p>
                    <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <h5>Overall CSO Application Status</h5>
            <p>CSO Status:
            <select class="form-control cso-overall-status" data-id="<?= htmlspecialchars($application['application_id']) ?>" data-field="<?= htmlspecialchars($application['cso_status']) ?>" <?= !$is_editable ? 'disabled' : '' ?>>
                <option value="Pending" <?= $application['cso_status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="Approved" <?= $application['cso_status'] === 'Approved' ? 'selected' : '' ?>>Approved</option>
                <option value="Denied" <?= $application['cso_status'] === 'Denied' ? 'selected' : '' ?>>Denied</option>
            </select>
            </p>
            <?php if ($application['cso_status'] === 'Approved'): ?>
            <hr>
            <h5>RTS Application Status</h5>
            <p><strong>Application Status:</strong> <?= $application['status'] ?></p>
            <p><strong>Application Remarks:</strong>
            <textarea class="form-control" readonly> <?= htmlspecialchars($application['remarks']) ?></textarea>
            </p>
            <p><strong>Hardcopy Submission Schedule: </strong><?= $hardcopySchedule ?></p>
            <?php endif; ?>
        </div>
        <div class="modal-footer">
            <?php if ($application['cso_status'] === 'Pending'): ?>
            <button type="button" class="btn btn-secondary"  onclick="saveCsoChanges(<?= htmlspecialchars($application['application_id']) ?>)">Save Changes</button>
            <?php endif; ?>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
        </div>
    </div>
</div>

<?php include('cso_include/script.php'); ?>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>

<!-- DataTables + Responsive CSS/JS -->
<script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap5.min.css">

<script>
$(document).ready(function() {
    // Initialize DataTables for each (pendingTable, approvedTable, deniedTable)
    $('#pendingTable, #approvedTable, #deniedTable').DataTable({
        responsive: true,
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
});

function saveCsoChanges(applicationId) {
    if (!applicationId || isNaN(applicationId)) {
        alert("Missing or invalid application ID.");
        return;
    }

    if (!confirm("Are you sure you want to save these changes?")) {
        return;
    }

    const cso_status = $(`.cso-overall-status[data-id='${applicationId}']`).val();
    const schedule = $(`.overall-schedule[data-id='${applicationId}']`).val() || '';

    let documents = {};

    // Loop through document statuses and remarks
    $(`.status-dropdown[data-id='${applicationId}'], .remarks-input[data-id='${applicationId}']`).each(function () {
        const field = $(this).data('field');
        if (!documents[field]) documents[field] = {};

        if ($(this).hasClass('status-dropdown')) {
            documents[field]['status'] = $(this).val();
        } else if ($(this).hasClass('remarks-input')) {
            let docRemarksVal = $(this).val();
            docRemarksVal = (docRemarksVal === 'No remarks') ? '' : docRemarksVal;
            documents[field]['remarks'] = docRemarksVal;
        }
    });

    const postData = {
        id: applicationId,
        cso_status: cso_status,
        schedule: schedule,
        documents: documents,
        cso_review: true
    };

    // Send JSON properly via AJAX
    $.ajax({
        url: 'update_renewal_application.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(postData),
        success: function (response) {
            try {
                const res = (typeof response === "object") ? response : JSON.parse(response);
                if (res.success) {
                    alert('Changes saved successfully.');
                    location.reload();
                } else {
                    alert('Failed to save changes: ' + (res.message || 'No error message provided.'));
                }
            } catch (e) {
                alert('Unexpected response: ' + response);
            }
        },
        error: function (xhr, status, error) {
            alert('An error occurred while saving changes: ' + error);
        }
    });
}
</script>