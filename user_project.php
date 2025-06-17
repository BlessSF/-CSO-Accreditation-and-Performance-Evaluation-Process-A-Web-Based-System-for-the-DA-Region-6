<?php
include('include/db_connect.php');
session_start();

if (!isset($_SESSION['cso_representative_id']) && !isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "CSO representative or User ID not set in session.";
    header("Location: login.php");
    exit();
}

$cso_representative_id = $_SESSION['cso_representative_id'] ?? $_SESSION['user_id'];

$sql_cso = "SELECT cso_name FROM cso_representative WHERE id = ?";
$stmt_cso = $conn->prepare($sql_cso);
$stmt_cso->bind_param('i', $cso_representative_id);
$stmt_cso->execute();
$result_cso = $stmt_cso->get_result();
if ($result_cso->num_rows > 0) {
    $cso_row = $result_cso->fetch_assoc();
    $cso_name = $cso_row['cso_name'];
} else {
    $_SESSION['message'] = "CSO not found for this representative.";
    header("Location: user_dashboard.php");
    exit();
}

$sql_projects = "
    SELECT p.*, 
           GROUP_CONCAT(DISTINCT c.cso_name SEPARATOR ', ') AS collaborating_csos
    FROM projects p
    LEFT JOIN project_cso pc ON p.id = pc.project_id
    LEFT JOIN cso_chairperson c ON pc.cso_id = c.id
    WHERE pc.project_id IN (
        SELECT pc2.project_id 
        FROM project_cso pc2
        INNER JOIN cso_chairperson c2 ON pc2.cso_id = c2.id
        WHERE c2.cso_name = ?
    )
    GROUP BY p.id
    ORDER BY p.id DESC
";
$stmt_projects = $conn->prepare($sql_projects);
$stmt_projects->bind_param("s", $cso_name);
$stmt_projects->execute();
$result_projects = $stmt_projects->get_result();

$projects = [];
if ($result_projects) {
    while ($row = $result_projects->fetch_assoc()) {
        // Remove the logged-in CSO from the list of collaborating CSOs
        $all_csos = explode(', ', $row['collaborating_csos']);
        $filtered_csos = array_filter($all_csos, function ($cso) use ($cso_name) {
            return trim($cso) !== $cso_name;
        });
        $row['collaborating_csos'] = implode(', ', $filtered_csos);
        $projects[] = $row;
    }
}
$stmt_projects->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Department of Agriculture Region 6 | CSO Projects</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link rel="icon" type="image/x-icon" href="img/logo.png">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body id="page-top">
    <style>
        .container-fluid { 
            padding: 20px; 
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
        .success {
            color: blue;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .status {
            color: red;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .error-message {
            color: red;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .btn-custom {
            background-color: rgb(1, 82, 51);
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            transition: background-color 0.3s, color 0.3s;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
            width: 100%;
        }

        .btn-custom:hover {
            color: white;
            text-decoration: underline;
        }

        th,
        .card-header {
            background-color: #0A593A;
            color: white;
        }

        h5,
        label {
            font-weight: bold;
            color: #0A593A;
        }

        .yellow-line {
        background-color: rgb(253, 199, 5);
        height: 7px;
        width: 100%;
        }

        h2, h3 { 
            color: #0A593A; 
            font-weight: bold; 
        }
        .full-height-container {
            height: 90vh;
            display: flex;
            flex-direction: column;
        }
        .btn-action {
            border: 1px solid #0A593A;
            color: #0A593A;
            background-color: transparent;
            font-weight: bold;
            padding: 6px 12px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        .btn-action:hover {
            background-color: #0A593A;
            color: white;
        }
        .btn-delete {
            border: 1px solid red;
            color: red;
            background-color: transparent;
            font-weight: bold;
            padding: 6px 12px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .btn-delete:hover {
            background-color: red;
            color: white;
        }

        .badge {
            border: none; 
            background-color: transparent;
            font-weight: normal;
            padding: 0; 
            font-size: inherit;
        }

        .badge-success {
            color: #28a745;
        }

        .badge-warning {
            color: #ffc107;
        }

        .badge-info {
            color: #17a2b8;
        }
    </style>

    <div id="wrapper">
        <?php include('user_include/navbar.php'); ?>
        <div class="container-fluid full-height-container">
            <div class="row">
                <div class="col-md-12">
                    <h2>Projects</h2>
                    <div class="yellow-line"></div>
                    <br>
                    
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-info">
                            <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered" style="white-space: nowrap;" id="dataTable" width="100%">
                            <thead>
                                <tr>
                                    <th style="color:white">Project Title</th>
                                    <th style="color:white">Date Started</th>
                                    <th style="color:white">Due Date</th>
                                    <th style="color:white">Project Status</th>
                                    <th style="color:white; text-align:center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projects as $project): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($project['title']); ?></td>
                                        <td><?php echo htmlspecialchars($project['start_date']); ?></td>
                                        <td><?php echo htmlspecialchars($project['end_date']); ?></td>
                                        <td style="text-align: center;">
                                            <span class="badge <?php echo $project['status'] == 'Completed' || 'Completed with Delay' ? 'badge-success' : 
                                                                            ($project['status'] == 'In Progress' ? 'badge-warning' : 'badge-info'); ?>">
                                                <?php echo htmlspecialchars($project['status']); ?>
                                            </span>
                                        </td>
                                        <td style="text-align:center;">
                                            <a href="user_view_project.php?id=<?= htmlspecialchars($project['id']); ?>" 
                                            class="btn btn-action" 
                                            title="View or Update">
                                                <i class="fas fa-clipboard"></i>
                                            </a>
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

    <?php include('user_include/script.php'); ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">

    <script>
        $(document).ready(function () {
            $('#dataTable').DataTable({
                responsive: false,
                autoWidth: false,
                pageLength: 10,
                language: {
                    emptyTable: "No projects found.",
                    zeroRecords: "No matching records found"
                }
            });
        });
    </script>
</body>
</html>
