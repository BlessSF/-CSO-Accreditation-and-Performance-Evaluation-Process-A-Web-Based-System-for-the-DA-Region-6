<?php
session_start();
include('cso_include/header.php');
include('cso_include/navbar.php');
include('include/db_connect.php'); 

if (!isset($_SESSION['cso_name'])) {
    exit('Session cso_name not set');
}

$cso_name = $_SESSION['cso_name'];

// Function to fetch proposals by status and CSO name
function fetchProposalsByStatus($status, $conn, $cso_name) {
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

    // If no representatives found, return empty array
    if (empty($representative_ids)) {
        $sql_proposals = "SELECT * FROM proposal WHERE 1=0";
        $stmt_proposals = $conn->prepare($sql_proposals);
        $stmt_proposals->execute();
        $result_proposals = $stmt_proposals->get_result(); 
    } else {
        // Otherwise, build placeholders like "?, ?, ?" for all rep IDs
        $rep_placeholders = implode(',', array_fill(0, count($representative_ids), '?'));

        $sql_proposals = "
        SELECT * FROM proposal
        WHERE cso_representative_id IN ($rep_placeholders)
        AND status = ?
        ORDER BY id DESC
        ";

        $stmt = $conn->prepare($sql_proposals);

        $types = str_repeat('i', count($representative_ids)) . 's';
        $values = array_merge($representative_ids, [$status]);

        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $result_proposals = $stmt->get_result(); 
    }

    $proposals = [];
    while ($row = $result_proposals->fetch_assoc()) {
        $proposals[] = $row;
    }
    return $proposals;
}

$pendingProposals = fetchProposalsByStatus('Pending', $conn, $cso_name);
$approvedProposals = fetchProposalsByStatus('Approved', $conn, $cso_name);
$deniedProposals = fetchProposalsByStatus('Denied', $conn, $cso_name);
?>

<!-- Custom Styles -->
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
    
    /* Status badges */
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
    
    .status-rejected, .status-denied {
        background-color: #DC3545;
    }
    
    .status-funded {
        background-color: #17A2B8;
    }
    
    .status-not-funded {
        background-color: #6C757D;
    }

    /* Modal styling */
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
                <div class="tab-pane fade show active" id="new-applications" role="tabpanel" aria-labelledby="new-applications-tab">
                    <br>
                    <div class='table-responsive'>
                        <table id='pendingTable' class='display table table-bordered dt-responsive wrap' width='100%'>
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
                            <?php foreach ($pendingProposals as $row) { 
                                // Format dates for display
                                $formatted_date = date('F d, Y', strtotime($row['date_submitted']));
                                
                                // Define status badge classes
                                $csoStatusClass = '';
                                $csoStatus = htmlspecialchars($row['cso_status'] ?? 'Pending');
                                switch(strtolower($csoStatus)) {
                                    case 'approved': $csoStatusClass = 'status-approved'; break;
                                    case 'denied': $csoStatusClass = 'status-denied'; break;
                                    case 'pending': 
                                    default: $csoStatusClass = 'status-pending';
                                }

                                $statusClass = '';
                                switch(strtolower($row['status'])) {
                                    case 'approved': $statusClass = 'status-approved'; break;
                                    case 'denied': $statusClass = 'status-denied'; break;
                                    case 'pending': 
                                    default: $statusClass = 'status-pending';
                                }
                                
                                $fundingStatusClass = '';
                                    $fundingStatus = htmlspecialchars($row['funding_status'] ?? 'Pending');
                                    switch(strtolower($fundingStatus)) {
                                        case 'funded': $fundingStatusClass = 'status-approved'; break;  // Changed from 'status-funded' to 'status-approved' for green color
                                        case 'approved': $fundingStatusClass = 'status-approved'; break; // Adding this as an alternative
                                        case 'not funded': $fundingStatusClass = 'status-not-funded'; break;
                                        case 'pending': $fundingStatusClass = 'status-pending'; break;
                                        default: $fundingStatusClass = 'status-pending';
                                    }
                            ?>
                                <tr>
                                    <td><?php echo $formatted_date; ?></td>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td>PHP <?php echo number_format($row['budget'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['location'])?></td>
                                    <td><?php echo htmlspecialchars($row['team']) ?></td>
                                    <td><span class="status-badge <?php echo $csoStatusClass; ?>"><?php echo $csoStatus; ?></span></td>
                                    <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                    <td><span class="status-badge <?php echo $fundingStatusClass; ?>"><?php echo $fundingStatus; ?></span></td>
                                    <td>
                                        <a href="#" 
                                        class="btn btn-view" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#viewDetails<?php echo $row['id']; ?>" 
                                        title="View Details">
                                        <i class="fas fa-clipboard"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- APPROVED TAB -->
                <div class="tab-pane fade" id="approved" role="tabpanel" aria-labelledby="approved-tab">
                    <br>
                    <div class='table-responsive'>
                        <table id='approvedTable' class='display table table-bordered dt-responsive wrap' width='100%'>
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
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($approvedProposals as $row) { 
                                    // Format dates for display
                                    $formatted_date = date('F d, Y', strtotime($row['date_submitted']));
                                    
                                    // Define status badge classes
                                    $csoStatusClass = '';
                                    $csoStatus = htmlspecialchars($row['cso_status'] ?? 'Pending');
                                    switch(strtolower($csoStatus)) {
                                        case 'approved': $csoStatusClass = 'status-approved'; break;
                                        case 'denied': $csoStatusClass = 'status-denied'; break;
                                        case 'pending': 
                                        default: $csoStatusClass = 'status-pending';
                                    }

                                    $statusClass = '';
                                    switch(strtolower($row['status'])) {
                                        case 'approved': $statusClass = 'status-approved'; break;
                                        case 'denied': $statusClass = 'status-denied'; break;
                                        case 'pending': 
                                        default: $statusClass = 'status-pending';
                                    }

                                    $fundingStatusClass = '';
                                    $fundingStatus = htmlspecialchars($row['funding_status'] ?? 'Pending');
                                    switch(strtolower($fundingStatus)) {
                                        case 'funded': $fundingStatusClass = 'status-approved'; break;  // Changed from 'status-funded' to 'status-approved' for green color
                                        case 'approved': $fundingStatusClass = 'status-approved'; break; // Adding this as an alternative
                                        case 'not funded': $fundingStatusClass = 'status-not-funded'; break;
                                        case 'pending': $fundingStatusClass = 'status-pending'; break;
                                        default: $fundingStatusClass = 'status-pending';
                                    }
                                ?>
                                    <tr>
                                        <td><?php echo $formatted_date; ?></td>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td>PHP <?php echo number_format($row['budget'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($row['location'])?></td>
                                        <td><?php echo htmlspecialchars($row['team']) ?></td>
                                        <td><span class="status-badge <?php echo $csoStatusClass; ?>"><?php echo $csoStatus; ?></span></td>
                                        <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                        <td><span class="status-badge <?php echo $fundingStatusClass; ?>"><?php echo $fundingStatus; ?></span></td>
                                        <td>
                                            <a href="#" 
                                            class="btn btn-view" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#viewDetails<?php echo $row['id']; ?>" 
                                            title="View Details">
                                            <i class="fas fa-clipboard"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- DENIED TAB -->
                <div class="tab-pane fade" id="denied" role="tabpanel" aria-labelledby="denied-tab">
                    <br>
                    <div class='table-responsive'>
                        <table id='deniedTable' class='display table table-bordered dt-responsive wrap' width='100%'>
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
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deniedProposals as $row) { 
                                    // Format dates for display
                                    $formatted_date = date('F d, Y', strtotime($row['date_submitted']));
                                    
                                    // Define status badge classes
                                    $csoStatusClass = '';
                                    $csoStatus = htmlspecialchars($row['cso_status'] ?? 'Pending');
                                    switch(strtolower($csoStatus)) {
                                        case 'approved': $csoStatusClass = 'status-approved'; break;
                                        case 'denied': $csoStatusClass = 'status-denied'; break;
                                        case 'pending': 
                                        default: $csoStatusClass = 'status-pending';
                                    }

                                    $statusClass = '';
                                    switch(strtolower($row['status'])) {
                                        case 'approved': $statusClass = 'status-approved'; break;
                                        case 'denied': $statusClass = 'status-denied'; break;
                                        case 'pending': 
                                        default: $statusClass = 'status-pending';
                                    }

                                    $fundingStatusClass = '';
                                    $fundingStatus = htmlspecialchars($row['funding_status'] ?? 'Pending');
                                    switch(strtolower($fundingStatus)) {
                                        case 'funded': $fundingStatusClass = 'status-approved'; break;  // Changed from 'status-funded' to 'status-approved' for green color
                                        case 'approved': $fundingStatusClass = 'status-approved'; break; // Adding this as an alternative
                                        case 'not funded': $fundingStatusClass = 'status-not-funded'; break;
                                        case 'pending': $fundingStatusClass = 'status-pending'; break;
                                        default: $fundingStatusClass = 'status-pending';
                                    }
                                ?>
                                    <tr>
                                        <td><?php echo $formatted_date; ?></td>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td>PHP <?php echo number_format($row['budget'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($row['location'])?></td>
                                        <td><?php echo htmlspecialchars($row['team']) ?></td>
                                        <td><span class="status-badge <?php echo $csoStatusClass; ?>"><?php echo $csoStatus; ?></span></td>
                                        <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                        <td><span class="status-badge <?php echo $fundingStatusClass; ?>"><?php echo $fundingStatus; ?></span></td>
                                            <td>
                                            <a href="#" 
                                            class="btn btn-view" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#viewDetails<?php echo $row['id']; ?>" 
                                            title="View Details">
                                            <i class="fas fa-clipboard"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div> <!-- end tab-content -->
        </div>
    </div>
</div>

<!-- Modal definitions - Create one for EACH proposal -->
<?php 
    // Combine all proposals for modal generation
    $allProposals = array_merge($pendingProposals, $approvedProposals, $deniedProposals);
    foreach ($allProposals as $row) { 
        $id = $row['id']; // Define $id variable for use in the modal
        
        // Format dates for display
        $formatted_date = date('F d, Y', strtotime($row['date_submitted']));
        $start_date = !empty($row['start_date']) ? date('F d, Y', strtotime($row['start_date'])) : 'N/A';
        $end_date = !empty($row['end_date']) ? date('F d, Y', strtotime($row['end_date'])) : 'N/A';
        $cso_updated = !empty($row['cso_status_updated_at']) ? date('F d, Y', strtotime($row['cso_status_updated_at'])) : 'Not updated yet';
        $status_updated = !empty($row['status_updated_at']) ? date('F d, Y', strtotime($row['status_updated_at'])) : 'Not updated yet';
        $funding_updated = !empty($row['funding_updated_at']) ? date('F d, Y', strtotime($row['funding_updated_at'])) : 'Not updated yet';
        
        // Define status badge classes
        $csoStatusClass = '';
        $csoStatus = htmlspecialchars($row['cso_status'] ?? 'Pending');
        switch(strtolower($csoStatus)) {
            case 'approved': $csoStatusClass = 'status-approved'; break;
            case 'denied': $csoStatusClass = 'status-denied'; break;
            case 'pending': 
            default: $csoStatusClass = 'status-pending';
        }

        $statusClass = '';
        switch(strtolower($row['status'])) {
            case 'approved': $statusClass = 'status-approved'; break;
            case 'denied': $statusClass = 'status-denied'; break;
            case 'pending': 
            default: $statusClass = 'status-pending';
        }

        $fundingStatusClass = '';
        $fundingStatus = htmlspecialchars($row['funding_status'] ?? 'Pending');
        switch(strtolower($fundingStatus)) {
            case 'funded': $fundingStatusClass = 'status-approved'; break;
            case 'approved': $fundingStatusClass = 'status-approved'; break;
            case 'not funded': $fundingStatusClass = 'status-not-funded'; break;
            case 'pending': $fundingStatusClass = 'status-pending'; break;
            default: $fundingStatusClass = 'status-pending';
        }
    ?>
    <div class='modal fade' id='viewDetails<?php echo $row['id']; ?>' tabindex='-1' aria-labelledby='viewDetailsLabel_<?php echo $row['id']; ?>' aria-hidden='true'>
        <div class='modal-dialog modal-lg'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title'>Proposal Details: <?php echo htmlspecialchars($row['title']); ?></h5>
                    <button type='button' class='custom-close' data-bs-dismiss='modal' aria-label='Close'>×</button>
                </div>
                <div class='modal-body'>
                    <div class='row mb-4'>
                        <div class='col-md-6'>
                            <div class='detail-section'>
                                <label>Submitted On:</label>
                                <div><?php echo $formatted_date; ?></div>
                            </div>
                            
                            <div class='detail-section'>
                                <label>Status Information:</label>
                                <div class='d-flex justify-content-between mb-2'>
                                    <span>Proposal Status:</span>
                                    <span class='status-badge <?php echo $statusClass; ?>'><?php echo htmlspecialchars($row['status']); ?></span>
                                </div>
                                <div class='d-flex justify-content-between mb-2'>
                                    <span>Funding Status:</span>
                                    <span class='status-badge <?php echo $fundingStatusClass; ?>'><?php echo $fundingStatus; ?></span>
                                </div>
                                <div class='d-flex justify-content-between'>
                                    <span>Verification Status:</span>
                                    <span class='status-badge <?php echo $csoStatusClass; ?>'><?php echo $csoStatus; ?></span>
                                </div>
                            </div>
                            
                            <div class='detail-section'>
                                <label>Project Title:</label>
                                <div class='preserve-format'><?php echo htmlspecialchars($row['title']); ?></div>
                            </div>
                            
                            <div class='detail-section'>
                                <label>Project Description:</label>
                                <div class='preserve-format'><?php echo htmlspecialchars($row['content']); ?></div>
                            </div>
                        </div>
                        
                        <div class='col-md-6'>
                            <div class='detail-section'>
                                <label>Project Objective(s):</label>
                                <div class='preserve-format'><?php echo htmlspecialchars($row['objectives']); ?></div>
                            </div>
                            
                            <div class='detail-section'>
                                <label>Expected Outcome(s):</label>
                                <div class='preserve-format'><?php echo htmlspecialchars($row['outcomes']); ?></div>
                            </div>
                            
                            <div class='detail-section'>
                                <label>Milestones/Phases:</label>
                                <div class='preserve-format'><?php echo htmlspecialchars($row['milestones']); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class='row'>
                        <div class='col-md-6'>
                            <div class='detail-section'>
                                <label>Key Stakeholder(s):</label>
                                <div class='preserve-format'><?php echo htmlspecialchars($row['team']); ?></div>
                            </div>
                            
                            <div class='detail-section'>
                                <label>Project Location:</label>
                                <div class='preserve-format'><?php echo htmlspecialchars($row['location']); ?></div>
                            </div>
                        </div>
                        
                        <div class='col-md-6'>
                            <div class='detail-section'>
                                <label>Risk Assessment and Mitigation Plan:</label>
                                <div class='preserve-format'><?php echo htmlspecialchars($row['risks']); ?></div>
                            </div>
                            
                            <div class='detail-section'>
                                <label>Budget and Timeline:</label>
                                <div class='d-flex justify-content-between mb-2'>
                                    <span>Budgetary Requirement:</span>
                                    <span>PHP <?php echo number_format($row['budget'], 2); ?></span>
                                </div>
                                <div class='d-flex justify-content-between mb-2'>
                                    <span>Start Date:</span>
                                    <span><?php echo $start_date; ?></span>
                                </div>
                                <div class='d-flex justify-content-between'>
                                    <span>End Date:</span>
                                    <span><?php echo $end_date; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class='row mt-3'>
                        <div class='col-12'>
                            <div class='detail-section'>
                                <label>Supporting File:</label>
                                <div>
                                    <?php 
                                    if(!empty($row['file_path'])) {
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
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class='admin-status-section mt-4'>
                        <h6 class='admin-status-title'>Review & Status Information</h6>
                        
                        <div class='status-item'>
                            <strong>Verification Status:</strong>
                            <div>
                                <?php if ($row['cso_status'] === 'Pending') : ?>
                                    <select name="cso_status" class="form-control cso_status-dropdown" data-id="<?php echo $id; ?>">
                                        <option value='Pending' <?php echo ($row['cso_status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value='Approved' <?php echo ($row['cso_status'] == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                                        <option value='Denied' <?php echo ($row['cso_status'] == 'Denied') ? 'selected' : ''; ?>>Denied</option>
                                    </select>
                                <?php else: ?>
                                    <span class='status-badge <?php echo $csoStatusClass; ?>'><?php echo $csoStatus; ?></span>
                                <?php endif; ?>
                                    
                                <small class='text-muted d-block mt-1'>
                                    Last updated: <?php echo $cso_updated; ?>
                                </small>
                            </div>
                        </div>
                        
                        <div class='status-item'>
                            <strong>Proposal Status:</strong>
                            <div>
                                <span class='status-badge <?php echo $statusClass; ?>'><?php echo htmlspecialchars($row['status']); ?></span>
                                <small class='text-muted d-block mt-1'>
                                    Last updated: <?php echo $status_updated; ?>
                                </small>
                            </div>
                        </div>
                        
                        <div class='status-item'>
                            <strong>Funding Status:</strong>
                            <div>
                                <span class='status-badge <?php echo $fundingStatusClass; ?>'><?php echo $fundingStatus; ?></span>
                                <small class='text-muted d-block mt-1'>
                                    Last updated: <?php echo $funding_updated; ?>
                                </small>
                            </div>
                        </div>
                        
                        <div class='status-item' style='flex-direction: column;'>
                            <strong style='margin-bottom: 8px;'>Admin Comments:</strong>
                            <div class='preserve-format' style='width: 100%;'><?php echo empty($row['remarks']) ? 
                                "<em class='text-muted'>No comments provided</em>" : 
                                nl2br(htmlspecialchars($row['remarks'])); ?></div>
                            </div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <?php if ($row['cso_status'] === 'Pending') : ?>
                        <button type='button' class='btn btn-secondary' id='saveChangesBtn<?php echo $id; ?>' data-id="<?php echo $id; ?>" onclick='confirmSaveChanges(<?php echo $id; ?>)'>Update Status</button>
                    <?php else : ?>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
        <?php } ?>

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
            
            // Debug to check if the modal trigger is working
            $('.view-details').on('click', function(e) {
                console.log('View Details clicked');
                const targetModal = $(this).data('bs-target');
                console.log('Target modal:', targetModal);
            });
            
            // Fix issue with Bootstrap 5 modals not showing
            $('.modal').on('shown.bs.modal', function() {
                console.log('Modal is shown:', this.id);
            });
            
            $('.modal').on('hidden.bs.modal', function() {
                console.log('Modal is hidden:', this.id);
            });
        });

        function confirmSaveChanges(proposalId) {
            const selectedStatus = $(`.cso_status-dropdown[data-id="${proposalId}"]`).val();
            console.log('Saving changes for proposal ID:', proposalId);
            console.log('Selected status:', selectedStatus);
            
            const confirmChanges = confirm("Are you sure you want to save these changes?");
            if (confirmChanges) {
                $.ajax({
                    url: 'submit_proposal.php', // Backend endpoint to handle status update
                    type: 'POST',
                    data: {
                        id: proposalId,
                        cso_status: selectedStatus
                    },
                    success: function(response) {
                        alert('Status updated successfully.');
                        location.reload();
                    },
                    error: function(error) {
                        console.error('Error updating status:', error);
                        alert('Failed to update status.');
                    }
                });
            }
        }

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
        </script>