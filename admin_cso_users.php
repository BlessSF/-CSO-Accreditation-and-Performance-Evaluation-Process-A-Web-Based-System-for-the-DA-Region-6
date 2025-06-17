<?php
include('admin_include/header.php');
include('admin_include/navbar.php');
include('include/db_connect.php');

$sql_fetch_chairpersons = "
    SELECT 
        'cso' AS user_type,
        cc.id,
        cc.cso_name,
        CONCAT(cc.first_name, ' ', cc.last_name, IFNULL(CONCAT(' ', cc.suffix), '')) AS user_name,
        cc.*
    FROM cso_chairperson cc
";

$result_users = $conn->query($sql_fetch_chairpersons);

$unverified_chairpersons = [];
$verified_chairpersons = [];

if ($result_users->num_rows > 0) {
    while ($row = $result_users->fetch_assoc()) {
        if ($row['status'] == 'Unverified') {
            $unverified_chairpersons[] = $row;
        } elseif ($row['status'] == 'Verified') {
            $verified_chairpersons[] = $row;
        }
    }
}
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
    }
    h2, h3, h5 { 
        color: #0A593A; 
        font-weight: bold; 
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
    .full-height-container {
    height: 90vh;
    display: flex;
    flex-direction: column;
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
    .btn-delete {
        border: 1px solid #dc3545;
        color: #dc3545;
        background-color: transparent;
        font-weight: bold;
        padding: 6px 12px;
        border-radius: 4px;
        transition: all 0.3s ease;
    }
    .btn-delete:hover {
        background-color: #dc3545;
        color: white;
    }
    .action-cell {
        text-align: center;
        vertical-align: middle;
    }
</style>

<div class="container-fluid full-height-container">
    <div class="row">
        <div class="col-md-12">
            <h2>CSO Accounts</h2>
        </div>
    </div>
    <div class="yellow-line"></div>
        <br>
        <ul class="nav nav-tabs" id="userTabs" role="tablist">
            <li class="nav-item"><a class="nav-link active" id="unverified-tab" data-toggle="tab" href="#unverified" role="tab" aria-controls="unverified" aria-selected="true">Pending Approval</a></li>
            <li class="nav-item"><a class="nav-link" id="verified-tab" data-toggle="tab" href="#verified" role="tab" aria-controls="verified" aria-selected="false">Verified Users</a></li>
        </ul>

        <div class="tab-content">
            <!-- Unverified Users Tab -->
            <div class="tab-pane fade show active" id="unverified" role="tabpanel" aria-labelledby="unverified-tab">
                <br>
                <div class="table-responsive">
                    <table class="table table-bordered" id="unverifiedChairTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Date Registered</th>
                                <th>CSO Name</th>
                                <th>Chairperson / President</th>
                                <th>Telephone / Email</th>
                                <th>Gov't ID </th>
                                <th>CSO Certification</th>
                                <th style="text-align: center !important;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($unverified_chairpersons as $user): ?>
                            <tr id="row-<?php echo $user['id']; ?>">
                                <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($user['date_created']))); ?></td>
                                <td><?php echo htmlspecialchars($user['cso_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['office_telephone_number']); ?> <hr> <?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['gov_id_type']); ?> <hr> <a href="<?php echo htmlspecialchars($user['gov_id_file']); ?>" target="_blank">View File</a></td>
                                <td><?php echo htmlspecialchars($user['certificate_type']); ?> <hr> <a href="<?php echo htmlspecialchars($user['certificate_file']); ?>" target="_blank">View File</a></td>
                                <td class="action-cell">
                                    <a href="admin_cso_view_profile.php?id=<?= $user['id']; ?>&usertype=<?= $user['user_type']; ?>" class="btn btn-view" title="View Profile">
                                    <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-view delete-btn" style="border-color: #dc3545; color: #dc3545;" 
                                        onmouseover="this.style.backgroundColor='#dc3545'; this.style.color='white';" 
                                        onmouseout="this.style.backgroundColor='transparent'; this.style.color='#dc3545';" 
                                        title="Delete" data-id="<?php echo $user['id']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Verified Users Tab -->
            <div class="tab-pane fade" id="verified" role="tabpanel" aria-labelledby="verified-tab">
                <br>
                <div class="table-responsive">
                    <table class="table table-bordered" id="verifiedChairTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Date Registered</th>
                                <th>CSO Name</th>
                                <th>Chairperson / President</th>
                                <th>Telephone / Email</th>
                                <th style="text-align: center !important;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($verified_chairpersons as $user): ?>
                            <tr id="row-<?php echo $user['id']; ?>">
                                <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($user['date_created']))); ?></td>
                                <td><?php echo htmlspecialchars($user['cso_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['office_telephone_number']); ?> <hr> <?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="action-cell">
                                    <a href="admin_cso_view_profile.php?id=<?= $user['id']; ?>&usertype=<?= $user['user_type']; ?>" class="btn btn-view" title="View Profile">
                                    <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-view delete-btn" style="border-color: #dc3545; color: #dc3545;" 
                                        onmouseover="this.style.backgroundColor='#dc3545'; this.style.color='white';" 
                                        onmouseout="this.style.backgroundColor='transparent'; this.style.color='#dc3545';" 
                                        title="Delete" data-id="<?php echo $user['id']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
</div>

<?php include('admin_include/script.php'); ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">

<script>
    $(document).ready(function () {
        $('#unverifiedChairTable, #verifiedChairTable').DataTable({
            responsive: true,
            autoWidth: false,
            scrollX: true,
            scrollY: '100%',
            pageLength: 10,
            stripeClasses: [],
            language: {
                emptyTable: "No CSO chairperson accounts found",
                zeroRecords: "No matching records found"
            }
        });

        $('.status-dropdown').change(function () {
            var id = $(this).data('id');
            var userType = $(this).data('type');
            var newStatus = $(this).val();
            
            // Confirm before proceeding with the status update
            if (confirm("Are you sure you want to change the status of this user?")) {
                $.ajax({
                    url: 'update_status.php',
                    type: 'POST',
                    data: { id: id, status: newStatus, user_type: userType },
                    success: function(response) {
                        if (response.trim() === 'success') {
                            alert('Status updated successfully!');
                            location.reload(); // Reload only after showing alert
                        } else {
                            alert('Error: ' + response); // Show error if update fails
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('AJAX Error: ' + error); // Handle server or network error
                    }
                });
            }
        });

        $('.delete-btn').click(function () {
            var id = $(this).data('id');
            if (confirm("Are you sure you want to delete this user?")) {
                $.ajax({
                    url: 'delete_user.php',
                    type: 'POST',
                    data: { id: id },
                    success: function(response) {
                        if (response === 'success') {
                            $('#row-' + id).remove();
                        } else {
                            alert('Error: ' + response);
                        }
                    }
                });
            }
        });
    });
</script>