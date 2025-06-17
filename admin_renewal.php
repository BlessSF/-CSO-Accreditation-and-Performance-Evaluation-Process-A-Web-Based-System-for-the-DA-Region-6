<?php
include('admin_include/header.php');
include('admin_include/navbar.php');
include('include/db_connect.php');

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Fetch applications by a given status
function fetchApplicationsByStatus($status, $conn) {
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
        aa.status, aa.remarks, aa.created_at, aa.schedule,
        cr.first_name, cr.last_name, cr.middle_name, cr.suffix, cr.email,
        cc.cso_name, CONCAT(cc.cso_address, ', ', cc.street, ', ', cc.barangay, ', ', cc.city, ', ', 
        cc.province, ', ', cc.zip_code, ', ', cc.region) AS cso_full_address,
        cc.first_name AS chair_first_name, cc.middle_name AS chair_middle_name, cc.last_name AS chair_last_name,
        cc.suffix AS chair_suffix, cc.email AS chair_email
    FROM renewal_application aa
    JOIN cso_representative cr ON aa.cso_representative_id = cr.id
    JOIN cso_chairperson cc ON cr.cso_name = cc.cso_name
    WHERE aa.status = ? AND aa.cso_status = 'Approved'
    ORDER BY aa.created_at DESC";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Define document fields
$documents = [
    'datasheet_org' => [
        'title' => 'Completed Data Sheet with Organizational Structure',
        'description' => 'A fully completed data sheet detailing the CSO’s organizational structure, including hierarchy, roles, responsibilities, and reporting lines.'
    ],
    'goodstanding_lce' => [
        'title' => 'Certificate of Good Standing from Local Chief Executive/Religious Leader',
        'description' => 'An official certificate issued by the Local Chief Executive or the head of a local religious organization confirming that the CSO is in good standing and complies with local regulatory standards.'
    ],
    'permit_mayor' => [
        'title' => 'Valid Mayor’s Permit',
        'description' => 'A current and valid Mayor’s Permit issued by the local government, serving as legal proof that the CSO is authorized to operate within the municipality.'
    ],
    'permit_bir' => [
        'title' => 'BIR Registration Certificate',
        'description' => 'An official registration certificate from the Bureau of Internal Revenue (BIR) confirming that the CSO is duly registered for tax purposes.'
    ],
    'certificate_reg' => [
        'title' => 'Certificate of Registration/Certificate of Filing (SEC, CDA, or DOLE-BRW)',
        'description' => 'An official certificate or filing document issued by the Securities and Exchange Commission (SEC), Cooperative Development Authority (CDA), or DOLE-BRW verifying the CSO’s legal registration and compliance with applicable laws.'
    ],
    'goodstanding_ga' => [
        'title' => 'Certificate of Good Standing from Government Agencies / Alternative Certification',
        'description' => 'Either an official certificate from the government agencies that have provided public funds or a certification signed by the CSO’s President and Treasurer confirming that no funds have been received from other agencies, thereby attesting to the CSO’s good standing.'
    ],
    'omnibus' => [
        'title' => 'Notarized Omnibus Sworn Statement',
        'description' => 'A comprehensive, duly notarized sworn statement affirming the authenticity of the submitted documents and declaring the CSO’s compliance with all applicable regulations.'
    ],
    'bio_data' => [
        'title' => 'Bio-data Sheet with Recent Photograph of President/Chairman',
        'description' => 'An updated bio-data sheet that includes detailed personal and professional information along with a recent passport-sized photograph of the current President or Chairman.'
    ],
    'articles_of_incorporation' => [
        'title' => 'Latest Amended Articles of Incorporation/Cooperation',
        'description' => 'The most recent version of the Articles of Incorporation or Cooperation, duly amended by law and clearly listing the original incorporators or organizers as well as any subsequent amendments.'
    ],
    'incumbent_officers' => [
        'title' => 'Secretary’s Certificate of Incumbent Officers',
        'description' => 'A formal certification provided by the Corporate Secretary listing all current officers of the CSO along with their respective designations and roles.'
    ],
    'accomplishment_reports' => [
        'title' => 'Accomplishment Report and Latest Audited Financial Statement',
        'description' => 'A detailed report covering the CSO’s accomplishments over the past three years, accompanied by the most recent audited financial statement, both certified by the CSO’s President and Secretary.'
    ],
    'disclosure' => [
        'title' => 'Directors’ and Trustees’ Business Disclosure Statement',
        'description' => 'A comprehensive disclosure statement by the CSO’s Directors and Trustees that details any involvement in related business ventures, including the extent of ownership or interest therein.'
    ],
    'affidavit' => [
        'title' => 'Sworn Affidavit of Non-Relationship',
        'description' => 'A notarized affidavit provided by the CSO’s Secretary, declaring that none of the incorporators, organizers, directors, or officials are agents of or related by consanguinity or affinity (up to the fourth civil degree) to any officials of the implementing agency responsible for processing the accreditation application.'
    ],
];
$pendingApplication = fetchApplicationsByStatus('Pending', $conn); 
$accreditedApplication = fetchApplicationsByStatus('Accredited', $conn); 
$deniedApplication = fetchApplicationsByStatus('Denied', $conn); 
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
            <h2>Application for Renewal of Accreditation</h2>
            <div class="yellow-line"></div>
            <br>
            <!-- Tabs navigation -->
            <ul class="nav nav-tabs" id="applicationTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="pending-tab" data-bs-toggle="tab" href="#pending" role="tab">Pending</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="accredited-tab" data-bs-toggle="tab" href="#accredited" role="tab">Approved</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="denied-tab" data-bs-toggle="tab" href="#denied" role="tab">Denied</a>
                </li>
            </ul>

            <!-- Tab content -->
            <div class="tab-content">
                <div class="tab-pane fade show active" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                    <br>
                    <div class="table-responsive">
                        <table id="pendingTable" class="display table table-bordered dt-responsive wrap" width="100%">
                            <thead>
                                <tr>
                                    <th>Date Submitted</th>
                                    <th>CSO Name</th>
                                    <th>Representative</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($pendingApplication as $app): $appId = htmlspecialchars($app['application_id']); ?>
                            <tr>
                                <td><?= htmlspecialchars($app['created_at']) ?></td>
                                <td><?= htmlspecialchars($app['cso_name']) ?></td>
                                <td><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></td>
                                <td class="center-text">
                                    <a href="#" 
                                    class="text-primary"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#appModal<?= $appId; ?>">View Application</a>
                                </td>                            
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="tab-pane fade" id="accredited" role="tabpanel" aria-labelledby="accredited-tab">
                    <br>
                    <div class="table-responsive">
                        <table id="accreditedTable" class="display table table-bordered dt-responsive wrap" width="100%">
                            <thead>
                                <tr>
                                    <th>Date Submitted</th>
                                    <th>CSO Name</th>
                                    <th>Representative</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($accreditedApplication as $app): $appId = htmlspecialchars($app['application_id']); ?>
                            <tr>
                                <td><?= htmlspecialchars($app['created_at']) ?></td>
                                <td><?= htmlspecialchars($app['cso_name']) ?></td>
                                <td><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></td>
                                <td class="center-text">
                                    <a href="#" 
                                    class="text-primary"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#appModal<?= $appId; ?>">View Application</a>
                                </td>                            
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="tab-pane fade" id="denied" role="tabpanel" aria-labelledby="denied-tab">
                    <br>
                    <div class="table-responsive">
                        <table id="deniedTable" class="display table table-bordered dt-responsive wrap" width="100%">
                            <thead>
                                <tr>
                                    <th>Date Submitted</th>
                                    <th>CSO Name</th>
                                    <th>Representative</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($deniedApplication as $app): $appId = htmlspecialchars($app['application_id']); ?>
                            <tr>
                                <td><?= htmlspecialchars($app['created_at']) ?></td>
                                <td><?= htmlspecialchars($app['cso_name']) ?></td>
                                <td><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></td>
                                <td class="center-text">
                                    <a href="#" 
                                    class="text-primary"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#appModal<?= $appId; ?>">View Application</a>
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

<div id="dynamicModalContainer"></div>

<?php include ('admin_include/script.php'); ?>

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
    $('#pendingTable, #accreditedTable, #deniedTable').DataTable({
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
document.addEventListener('DOMContentLoaded', function() {
    // Get all "View Application" links
    const viewLinks = document.querySelectorAll('[data-bs-toggle="modal"][data-bs-target^="#appModal"]');
    
    // Create a single modal template that will be reused for all applications
    const modalTemplate = `
        <div class="modal fade" id="dynamicAppModal" tabindex="-1" aria-labelledby="dynamicAppModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <!-- Modal Header -->
                    <div class="modal-header" style="position: relative;">
                        <h5 class="modal-title" id="dynamicAppModalLabel">Application Details</h5>
                        <button type="button" class="custom-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 20%; right: 0; font-size: 24px; color: white; background: transparent; border: none; padding: 0 15px; line-height: 1;">×</button>
                    </div>
                    <!-- Modal Body will be populated dynamically -->
                    <div class="modal-body" id="dynamicModalBody"></div>
                    <div class="modal-footer" id="dynamicModalFooter"></div>
                </div>
            </div>
        </div>
    `;
    
    // Add the modal template to the page
    document.getElementById('dynamicModalContainer').innerHTML = modalTemplate;
    
    // Initialize the modal object
    const dynamicModal = new bootstrap.Modal(document.getElementById('dynamicAppModal'));
    
    // Store application data by ID
    const applications = {};
    
    <?php
    // Combine all applications into a single array for JavaScript
    $allApplications = array_merge($pendingApplication, $accreditedApplication, $deniedApplication);
    foreach ($allApplications as $app) {
        $appId = htmlspecialchars($app['application_id']);
        echo "applications['{$appId}'] = " . json_encode($app) . ";\n";
    }
    
    // Also provide the documents array for JavaScript
    echo "const documentsList = " . json_encode($documents) . ";\n";
    ?>
    
    // Add click event listener to all "View Application" links
    viewLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            // Extract the application ID from the target attribute
            const modalId = this.getAttribute('data-bs-target');
            const appId = modalId.replace('#appModal', '');
            
            // Get the application data
            const app = applications[appId];
            
            if (app) {
                // Populate the modal with application data
                populateModal(app, appId);
                
                // Show the modal
                dynamicModal.show();
            } else {
                console.error('Application data not found for ID: ' + appId);
            }
        });
    });
    
    // Function to populate the modal with application data
    function populateModal(app, appId) {
        const modalTitle = document.getElementById('dynamicAppModalLabel');
        const modalBody = document.getElementById('dynamicModalBody');
        const modalFooter = document.getElementById('dynamicModalFooter');
        
        // Set the modal title
        modalTitle.textContent = `Application Details - ${app.cso_name}`;
        
        // Generate modal body content
        let bodyContent = `
            <h5 class="mb-2">CSO Information</h5>
            <p><strong>CSO Name:</strong> ${app.cso_name}</p>
            <p><strong>Chairperson:</strong> ${app.chair_first_name} ${app.chair_last_name}</p>
            <p><strong>Address:</strong> ${app.cso_full_address}</p>
            
            <hr>
            <h5 class="mb-2">Submitted Documents</h5>
            <ul class="list-unstyled">
        `;
        
        // Add each document
        for (const field in documentsList) {
            const info = documentsList[field];
            const file_url = app[field] || '';
            const file_status = app[field + '_status'] || 'Pending';
            const adminValue = (app[field + '_admin_remarks'] || '').trim();
            const placeholder = "None";
            const disabled = (app.status !== 'Pending') ? " disabled" : "";
            
            bodyContent += `
                <li class="mb-3">
                    <p><strong>${info.title}</strong></p>
                    <p class="text-muted">${info.description}</p>
                    
                    ${file_url ? `<a href="${file_url}" target="_blank" class="view-document-link">View Document</a>` : '<p class="text-muted">No document uploaded</p>'}
                    
                    <p>Status:
                        <select class="form-control status-dropdown" data-id="${appId}" data-field="${field}" ${disabled}>
                            <option value="Pending" ${file_status === 'Pending' ? 'selected' : ''}>Pending</option>
                            <option value="Approved" ${file_status === 'Approved' ? 'selected' : ''}>Approved</option>
                            <option value="Denied" ${file_status === 'Denied' ? 'selected' : ''}>Denied</option>
                        </select>
                    </p>
                    
                    <p><strong>Remarks:</strong><br>
                        <textarea class="form-control admin-remarks-input" data-id="${appId}" data-field="${field}" placeholder="${placeholder}" ${disabled}>${adminValue}</textarea>
                    </p>
                </li>
            `;
        }
        
        // Add assessment section
        const scheduleDisabled = (app.status !== 'Pending') ? " disabled" : "";
        const disabled = (app.status !== 'Pending') ? " disabled" : "";
        
        bodyContent += `
            </ul>
            <br>
            <hr>
            <h5>Application Assessment</h5>
            <p>Status: 
                <select class="form-control overall-status" data-id="${appId}" ${disabled}>
                    <option value="Pending" ${app.status === 'Pending' ? 'selected' : ''}>Pending</option>
                    <option value="Accredited" ${app.status === 'Accredited' ? 'selected' : ''}>Approved</option>
                    <option value="Denied" ${app.status === 'Denied' ? 'selected' : ''}>Denied</option>
                </select>
            </p>
            <p>Hardcopy Submission Schedule: <input type="date" class="form-control overall-schedule" data-id="${appId}" value="${app.schedule || ''}" ${scheduleDisabled}></p>
            <p>Overall Remarks: <textarea class="form-control overall-remarks" data-id="${appId}" placeholder="None" ${disabled}>${app.remarks || ''}</textarea></p>
        `;
        
        // Set the modal body content
        modalBody.innerHTML = bodyContent;
        
        // Set footer buttons
        let footerContent = '';
        if (app.status === 'Pending') {
            footerContent += `<button type="button" class="btn btn-secondary" onclick="saveChanges('${appId}')">Save Changes</button>`;
        }
        footerContent += `<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>`;
        modalFooter.innerHTML = footerContent;
    }
    
    // 2) Overall status change logic - moved inside the DOMContentLoaded event
    $(document).on('change', '.overall-status', function() {
        const appId = $(this).data('id');
        const selectedStatus = $(this).val();
        const scheduleInput = $(`.overall-schedule[data-id="${appId}"]`);
        console.log("Overall status changed for appId", appId, "to", selectedStatus);
        if (selectedStatus !== 'Pending') {
            scheduleInput.prop('disabled', false);
        } else {
            scheduleInput.prop('disabled', true);
        }
    });
});

// 3) Save changes logic - defined outside the DOMContentLoaded event for global access
function saveChanges(appId) {
    const status = $(`.overall-status[data-id="${appId}"]`).val();
    const remarks = $(`.overall-remarks[data-id="${appId}"]`).val();
    const schedule = $(`.overall-schedule[data-id="${appId}"]`).val();

    let documents = {};
    let allApproved = true;
    
    // Gather document data and check if all are approved
    $(`.status-dropdown[data-id="${appId}"], .admin-remarks-input[data-id="${appId}"]`).each(function() {
        const field = $(this).data('field');
        if (!documents[field]) documents[field] = {};
        
        if ($(this).hasClass('status-dropdown')) {
            const docStatus = $(this).val();
            documents[field]['status'] = docStatus;
            if (docStatus !== 'Approved') {
                allApproved = false;
            }
        } else if ($(this).hasClass('admin-remarks-input')) {
            documents[field]['admin_remarks'] = $(this).val();
        }
    });

    // Check if at least one document is Denied
    let hasDenied = false;
    $(`.status-dropdown[data-id="${appId}"]`).each(function() {
        if ($(this).val() === 'Denied') {
            hasDenied = true;
        }
    });

    // Validate overall status
    if (status === 'Accredited' && !allApproved) {
        alert('Cannot set overall status to Approved unless all submitted documents are approved.');
        return;
    }
    if (status === 'Denied' && !hasDenied) {
        alert('Cannot set overall status to Denied unless at least one submitted document is denied.');
        return;
    }
    if (status === 'Accredited' && (!schedule || schedule.trim() === '')) {
        alert('All documents are approved. Please set a Hardcopy Submission Schedule before saving.');
        return;
    }
    
    if (!confirm("Are you sure you want to save these changes?")) {
        return;
    }

    const postData = {
        id: appId,
        status: status,
        remarks: remarks,
        schedule: schedule,
        documents: documents
    };
    
    // POST updates via AJAX
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