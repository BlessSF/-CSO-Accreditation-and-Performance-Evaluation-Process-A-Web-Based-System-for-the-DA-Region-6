<?php
include('admin_include/header.php');
include('admin_include/navbar.php');
include('include/db_connect.php');

// 1) (OPTIONAL) If you truly need a $user_id for the representative, fetch it here.
//    Otherwise, if $user_id is not set and you only need to show proposals, you can remove.
$sql_fetch_profile = "SELECT * FROM cso_representative WHERE id = ?";
$user_id = 0; // Example fallback, or fetch from $_SESSION
$stmt_fetch_profile = $conn->prepare($sql_fetch_profile);
if ($stmt_fetch_profile === false) {
    die("Error preparing query: " . $conn->error);
}
$stmt_fetch_profile->bind_param("i", $user_id);
$stmt_fetch_profile->execute();
$result = $stmt_fetch_profile->get_result();
$profile = $result->fetch_assoc();

if ($profile) {
    $cso_name = $profile['cso_name'] ?? '';
    $sql_fetch_chairperson = "SELECT * FROM cso_chairperson WHERE cso_name = ?";
    $stmt_fetch_chairperson = $conn->prepare($sql_fetch_chairperson);
    if ($stmt_fetch_chairperson === false) {
        die("Error preparing query: " . $conn->error);
    }
    $stmt_fetch_chairperson->bind_param("s", $cso_name);
    $stmt_fetch_chairperson->execute();
    $result_chairperson = $stmt_fetch_chairperson->get_result();
    $chairperson = $result_chairperson->fetch_assoc();
}

// 2) Fetch proposals by status
function fetchApplicationsByStatus($status, $conn)
{
    $sql_fetch_applications = "
    SELECT 
        p.id, 
        p.title,
        p.content,
        p.objectives,
        p.file_path,
        p.start_date,
        p.end_date,
        p.cso_representative_id,
        p.status,
        p.created_at,
        p.status_updated_at,  
        p.date_submitted,     
        p.budget,             
        p.location,      
        p.team,
        p.outcomes,         
        p.milestones,        
        p.risks,         
        p.remarks, 
        cr.first_name,
        cr.last_name,
        cr.suffix,
        cr.middle_name,
        cr.email,
        cc.cso_name,
        cc.first_name AS chair_first_name,
        cc.middle_name AS chair_middle_name,
        cc.last_name AS chair_last_name,
        cc.suffix AS chair_suffix,
        cc.email AS chair_email,
        p.funding_status,
        p.cso_status,
        p.cso_status_updated_at
    FROM proposal p
    JOIN cso_representative cr ON p.cso_representative_id = cr.id
    JOIN cso_chairperson cc ON cr.cso_name = cc.cso_name
    WHERE p.status = ?
      AND p.cso_status = 'Approved'
    ORDER BY p.cso_status_updated_at DESC
    ";

    $stmt = $conn->prepare($sql_fetch_applications);
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $result = $stmt->get_result();

    $applications = [];
    while ($row = $result->fetch_assoc()) {
        $applications[] = $row;
    }
    return $applications;
}
?>

<!-- Custom Styles -->
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
    .remarks-section {
        margin-top: 15px;
    }
</style>

<div class="container-fluid full-height-container">
    <div class="row">
        <div class="col-md-12">
            <h2>Proposals</h2>
            <div class="yellow-line"></div>
            <br>

            <!-- Tabs for Pending / Approved / Denied -->
            <ul class="nav nav-tabs" id="proposalTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="new-applications-tab" data-bs-toggle="tab"
                       href="#new-applications" role="tab">
                        Pending
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="approved-tab" data-bs-toggle="tab"
                       href="#approved" role="tab">
                        Approved
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="denied-tab" data-bs-toggle="tab"
                       href="#denied" role="tab">
                        Denied
                    </a>
                </li>
            </ul>

            <div class="tab-content" id="proposalTabsContent">

                <!-- PENDING TAB -->
                <div class="tab-pane fade show active"
                     id="new-applications"
                     role="tabpanel"
                     aria-labelledby="new-applications-tab">
                    <br>
                    <?php displayApplicationsTable('pending', $conn); ?>
                </div>

                <!-- APPROVED TAB -->
                <div class="tab-pane fade"
                     id="approved"
                     role="tabpanel"
                     aria-labelledby="approved-tab">
                    <br>
                    <?php displayApplicationsTable('approved', $conn); ?>
                </div>

                <!-- DENIED TAB -->
                <div class="tab-pane fade"
                     id="denied"
                     role="tabpanel"
                     aria-labelledby="denied-tab">
                    <br>
                    <?php displayApplicationsTable('denied', $conn); ?>
                </div>

            </div> <!-- end tab-content -->
        </div>
    </div>
</div>

<?php
// 3) The function that outputs the proposals in a table + modals
function displayApplicationsTable($status, $conn)
{
    $applications = fetchApplicationsByStatus($status, $conn);

    echo "<div class='table-responsive'>";
    echo "<table id='{$status}Table' class='display table table-bordered dt-responsive wrap' width='100%'>";
    echo "<thead>
            <tr>
                <th>Date Submitted</th>
                <th>Title</th>
                <th>Budget Requirement</th>
                <th>Location</th>
                <th>Key Stakeholders</th>
                <th>Funding Status</th>
                <th>Details</th>
            </tr>
          </thead>
          <tbody>";

    if (empty($applications)) {
        // If none found
        echo "<tr>
                <td colspan='7' class='text-center text-muted'>No proposals found</td>
              </tr>";
    } else {
        // For each proposal, output row + modal
        foreach ($applications as $application) {
            $appId = htmlspecialchars($application['id']);

            // For the table row
            echo "<tr>
                    <td>" . htmlspecialchars($application['date_submitted']) . "</td>
                    <td>" . htmlspecialchars($application['title']) . "</td>
                    <td>PHP " . number_format($application['budget'], 2) . "</td>
                    <td>" . htmlspecialchars($application['location']) . "</td>
                    <td>" . htmlspecialchars($application['team']) . "</td>
                    <td>" . htmlspecialchars($application['funding_status']) . "</td>
                    <td>
                        <a href='#'
                           class='text-primary'
                           data-bs-toggle='modal'
                           data-bs-target='#appModal{$appId}'>
                           View Details
                        </a>
                    </td>
                  </tr>";

            // Decide if remarks field is readonly
            $remarksReadonly = (
                $application['status'] !== 'Pending' &&
                $application['funding_status'] !== 'Pending'
            ) ? "readonly" : "";

            // Now output the modal for this row
            echo "
            <div class='modal fade'
                 id='appModal{$appId}'
                 tabindex='-1'
                 aria-labelledby='appModalLabel{$appId}'
                 aria-hidden='true'>
              <div class='modal-dialog modal-lg'>
                <div class='modal-content'>

                  <!-- Modal Header -->
                 <div class='modal-header' style='position: relative;'>
                    <h5 class='modal-title' id='appModalLabel{$appId}'>
                        Proposal Details - " . htmlspecialchars($application['title']) . "
                    </h5>
                    <button type='button' 
                            class='custom-close' 
                            data-bs-dismiss='modal'
                            aria-label='Close'
                            style='
                                position: absolute;
                                top: 20%;
                                right: 0;
                                font-size: 24px;
                                color: white;
                                background: transparent;
                                border: none;
                                padding: 0 15px;
                                line-height: 1;
                            '>×</button>
                </div>

                  <!-- Modal Body -->
                  <div class='modal-body'>
                    <div class='row'>
                      <div class='col-md-6'>
                        <p><label>Submitted By:</label> " . htmlspecialchars($application['cso_name']) . "</p>
                        <p><label>Submitted On:</label> " . htmlspecialchars($application['date_submitted']) . "</p>
                        <p><label>Proposal Status:</label> " . htmlspecialchars($application['status']) . "</p>
                        <p><label>Funding Status:</label> " . htmlspecialchars($application['funding_status']) . "</p>
                        <p><label>Proposed Project Title:</label><br>" . htmlspecialchars($application['title']) . "</p>
                        <p><label>Project Description:</label><br>" . htmlspecialchars($application['content']) . "</p>
                        <p><label>Project Objective/s:</label><br>" . htmlspecialchars($application['objectives']) . "</p>
                        <p><label>Expected Outcome/s:</label><br>" . htmlspecialchars($application['outcomes']) . "</p>
                      </div>
                      <div class='col-md-6'>
                        <p><label>Milestones/Phases:</label><br>" . htmlspecialchars($application['milestones']) . "</p>
                        <p><label>Key Stakeholder/s:</label><br>" . htmlspecialchars($application['team']) . "</p>
                        <p><label>Project Location:</label><br>" . htmlspecialchars($application['location']) . "</p>
                        <p><label>Risk Assessment and Mitigation Plan:</label><br>" . htmlspecialchars($application['risks']) . "</p>
                        <p><label>Budgetary Requirement:</label> PHP " . number_format($application['budget'], 2) . "</p>
                        <p><label>Start Date:</label> " . htmlspecialchars($application['start_date']) . "</p>
                        <p><label>End Date:</label> " . htmlspecialchars($application['end_date']) . "</p>
                        <p><label>Supporting File/s:</label><br> <a href='" . htmlspecialchars($application['file_path']) . "' target='_blank'>View File</a> </p>
                      </div>
                    </div>

                    <hr>
                    <div class='row'>
                      <div class='col-md-12'>
                        <div class='status_updation'>
                          <p><label>Proposal Status:</label>
                            <select name='status'
                                    class='form-control status-dropdown'
                                    data-id='{$appId}'
                                    " . (
                                        $application['status'] === 'Approved' ||
                                        $application['status'] === 'Denied'
                                        ? 'disabled'
                                        : ''
                                    ) . ">
                              <option value='Pending'" . ($application['status'] === 'Pending' ? ' selected' : '') . ">Pending</option>
                              <option value='Approved'" . ($application['status'] === 'Approved' ? ' selected' : '') . ">Approved</option>
                              <option value='Denied'" . ($application['status'] === 'Denied' ? ' selected' : '') . ">Denied</option>
                            </select>
                          </p>

                          <p><label>Funding Status:</label>
                            <select name='funding_status'
                                    class='form-control funding-status-dropdown'
                                    data-id='{$appId}'
                                    " . (
                                        $application['funding_status'] === 'Approved' ||
                                        $application['status'] === 'Pending' ||
                                        $application['status'] === 'Denied'
                                        ? 'disabled'
                                        : ''
                                    ) . ">
                              <option value='Pending'" . ($application['funding_status'] === 'Pending' ? ' selected' : '') . ">Pending</option>
                              <option value='Approved'" . ($application['funding_status'] === 'Approved' ? ' selected' : '') . ">Approved</option>"
                              . (
                                     $application['status'] !== 'Approved'
                                     ? "<option value='Denied'" . ($application['funding_status'] === 'Denied' ? ' selected' : '') . ">Denied</option>"
                                     : ""
                                 ) .
                            "</select>
                          </p>

                          <p class='remarks-section'>
                            <label>Remarks:</label>
                            <textarea name='remarks'
                                      class='form-control remarks-textarea'
                                      data-id='{$appId}'
                                      placeholder='None'
                                      {$remarksReadonly}>"
                              . htmlspecialchars($application['remarks']) .
                              "</textarea>
                          </p>
                        </div>
                      </div>
                    </div>
                  </div> <!-- end modal-body -->
            ";

            // Decide if we show the "Update Status" button or not:
            // Hide if (status=Approved & funding=Approved) OR status=Denied
            $showButtons = !(
                ($application['status'] === 'Approved' &&
                 $application['funding_status'] === 'Approved') ||
                ($application['status'] === 'Denied')
            );

            echo "
                  <div class='modal-footer'>";

            if ($showButtons) {
                echo "
                    <button type='button'
                            class='btn btn-secondary'
                            id='saveChangesBtn'
                            data-id='{$appId}'
                            onclick='confirmSaveChanges({$appId})'>
                      Update Status
                    </button>
                    <button type='button'
                            class='btn btn-secondary'
                            data-id='{$appId}'
                            data-bs-dismiss='modal'>
                      Close
                    </button>
                ";
            }

            echo "</div> <!-- end modal-footer -->
                </div> <!-- end .modal-content -->
              </div> <!-- end .modal-dialog -->
            </div> <!-- end .modal fade -->";
        }
    }

    echo "</tbody></table>";
    echo "</div>";
}
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
$(document).ready(function() {
    // Initialize DataTables for each (pendingTable, approvedTable, deniedTable)
    $('#pendingTable, #approvedTable, #deniedTable').DataTable({
        responsive: true,
        autoWidth: false,
        scrollX: true,
        scrollY: '62vh',
        pageLength: 20,
        stripeClasses: [],
        language: {
            emptyTable: "No data available in table",
            zeroRecords: "No matching records found"
        }
    });
});

// When user changes proposal status or funding status, adjust funding status dropdown & remarks
$(document).on('change', '.status-dropdown, .funding-status-dropdown', function() {
    const applicationId = $(this).data('id');
    const statusDropdown = $(`.status-dropdown[data-id="${applicationId}"]`);
    const fundingStatusDropdown = $(`.funding-status-dropdown[data-id="${applicationId}"]`);
    const remarksTextarea = $(`.remarks-textarea[data-id="${applicationId}"]`);

    const currentStatus = statusDropdown.val();
    const currentFundingStatus = fundingStatusDropdown.val();

    // If proposal is Denied => auto-set funding Denied
    if (currentStatus === 'Denied') {
        fundingStatusDropdown.val('Denied').prop('disabled', true);
    }
    // If proposal Approved => allow changing funding, but remove Denied
    else if (currentStatus === 'Approved') {
        fundingStatusDropdown.prop('disabled', false);
        fundingStatusDropdown.find('option[value="Denied"]').remove();
        if (currentFundingStatus === 'Denied') {
            fundingStatusDropdown.val('Pending');
        }
    }
    // If proposal Pending => funding must be Pending & disabled
    else if (currentStatus === 'Pending') {
        fundingStatusDropdown.val('Pending').prop('disabled', true);
    }

    // Make remarks read-only only if both statuses are not “Pending”
    if (currentStatus !== 'Pending' && fundingStatusDropdown.val() !== 'Pending') {
        remarksTextarea.prop('readonly', true);
    } else {
        remarksTextarea.prop('readonly', false);
    }
});

// Confirm & Save Changes via AJAX
function confirmSaveChanges(applicationId) {
    const statusVal = $(`.status-dropdown[data-id="${applicationId}"]`).val();
    const fundingVal = $(`.funding-status-dropdown[data-id="${applicationId}"]`).val();
    const remarksVal = $(`.remarks-textarea[data-id="${applicationId}"]`).val();

    // Determine if the proposal is fully approved (both statuses are "Approved")
    const moveToProject = (statusVal === 'Approved' && fundingVal === 'Approved') ? 1 : 0;

    if (!confirm("Are you sure you want to save these changes?")) {
        alert("Changes not saved.");
        return;
    }

    $.ajax({
        url: 'update_proposal_status.php', // Endpoint to handle updates and move proposals
        type: 'POST',
        data: {
            id: applicationId,
            status: statusVal,
            funding_status: fundingVal,
            remarks: remarksVal,
            moveToProject: moveToProject
        },
        success: function (response) {
            console.log('Update successful:', response);
            if (moveToProject) {
                alert("Proposal approved and moved to the projects list successfully.");
            } else {
                alert("Proposal updated successfully.");
            }
            location.reload();
        },
        error: function (xhr, status, error) {
            console.error('Update failed:', error);
            alert("Error updating the proposal. Please try again.");
        }
    });
}
</script>
