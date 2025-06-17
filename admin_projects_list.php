<?php
include('include/db_connect.php');
session_start();

// ------------------ Handle "Add Project" form submission (if needed) ------------------ //
// This section is still available for manually adding projects. 
// However, projects created from approved proposals will be inserted automatically 
// via the update_proposal_status.php handler.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title        = $_POST['title'];
    $project_desc = $_POST['project_desc'];
    $budget       = $_POST['budget'];
    $cso_ids      = $_POST['cso_id']; // Array for multiple CSOs
    $start_date   = $_POST['start_date'];
    $end_date     = $_POST['end_date'];
    $duration     = $_POST['duration'];
    $file_name    = $_FILES['file']['name'];
    $file_tmp     = $_FILES['file']['tmp_name'];
    $status       = "Active"; // Default status

    if (empty($cso_ids)) {
        $_SESSION['message'] = "Please select at least one CSO.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Validate Dates
    if (strtotime($end_date) <= strtotime($start_date)) {
        $_SESSION['message'] = "End date must be after the start date.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // File Upload Logic
    $upload_dir = 'projects/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    $file_path = "";
    if (!empty($file_name) && move_uploaded_file($file_tmp, $upload_dir . $file_name)) {
        $file_path = $upload_dir . $file_name;
    } else {
        $_SESSION['message'] = "Failed to upload the file.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // ------------- Insert Project ------------- //
    $sql = "
        INSERT INTO projects (
            title, project_desc, budget, start_date, end_date, duration, 
            file_path, status, created_at
        ) VALUES (
            '$title', '$project_desc', '$budget', '$start_date', '$end_date',
            '$duration', '$file_path', '$status', NOW()
        )
    ";

    if (mysqli_query($conn, $sql)) {
        $project_id = mysqli_insert_id($conn); // Get the inserted project ID

        // Insert CSO IDs into the junction table
        foreach ($cso_ids as $cso_id) {
            $cso_id = intval($cso_id); // Sanitize input
            $junction_sql = "INSERT INTO project_cso (project_id, cso_id) VALUES ($project_id, $cso_id)";
            mysqli_query($conn, $junction_sql);
        }

        $_SESSION['message'] = "Project added successfully!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $_SESSION['message'] = "Error: " . mysqli_error($conn);
    }
}

// ------------------ Fetch Projects (including those moved from approved proposals) ------------------ //
$projects = [];
$sql = "
    SELECT 
        p.*, 
        GROUP_CONCAT(c.cso_name SEPARATOR ', ') AS cso_names 
    FROM projects p 
    LEFT JOIN project_cso pc ON p.id = pc.project_id
    LEFT JOIN cso_chairperson c ON pc.cso_id = c.id
    GROUP BY p.id
    ORDER BY p.created_at DESC
";

$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $projects[] = $row;
    }
} else {
    $_SESSION['message'] = "Error fetching projects: " . mysqli_error($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Department of Agriculture Region 6 | Projects</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link rel="icon" type="image/x-icon" href="img/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" 
          rel="stylesheet">
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
        <?php include('admin_include/navbar.php'); ?>
        
        <div class="container-fluid full-height-container">
            <div class="row">
                <div class="col-md-12">
                    <h2>Projects List</h2>
                    <div class="yellow-line"></div>
                    <br>
                    
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-info">
                            <?php 
                                echo $_SESSION['message']; 
                                unset($_SESSION['message']); 
                            ?>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-bordered" style="white-space: nowrap;" id="dataTable" width="100%">
                            <thead>
                                <tr>
                                    <th style="color:white">Project Title</th>
                                    <th style="color:white">Designated CSO In-Charge</th>
                                    <th style="color:white">Date Started</th>
                                    <th style="color:white">Due Date</th>
                                    <th style="color:white">Project Status</th>
                                    <th style="color:white; text-align:center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="projectsBody">
                                <?php foreach ($projects as $project): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($project['title']); ?></td>
                                        <td><?php echo htmlspecialchars($project['cso_names'] ?? 'No CSO Assigned'); ?></td>
                                        <td><?php echo htmlspecialchars($project['start_date']); ?></td>
                                        <td><?php echo htmlspecialchars($project['end_date']); ?></td>
                                        <td style="text-align: center;">
                                            <span class="badge 
                                                <?php 
                                                    echo $project['status'] == 'Completed' 
                                                        ? 'badge-success' 
                                                        : ($project['status'] == 'In Progress' 
                                                            ? 'badge-warning' 
                                                                : ($project['status'] == 'Completed with Delay' 
                                                                ? 'badge-success'
                                                                : 'badge-info')); 
                                                ?>">
                                                <?php echo htmlspecialchars($project['status']); ?>
                                            </span>
                                        </td>
                                        <td style="text-align:center;">
                                            <a href="admin_view_project.php?id=<?php echo htmlspecialchars($project['id']); ?>" 
                                               class="btn btn-action" 
                                               title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="admin_edit_project.php?id=<?php echo htmlspecialchars($project['id']); ?>" 
                                               class="btn btn-action" 
                                               title="Edit Project">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete_project.php?id=<?php echo htmlspecialchars($project['id']); ?>" 
                                               class="btn btn-delete" 
                                               title="Delete" 
                                               onclick="return confirm('Are you sure you want to delete this project?');">
                                                <i class="fas fa-trash"></i>
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

    <?php include('admin_include/script.php'); ?>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">

    <script>
        $(document).ready(function () {
            $('#dataTable').DataTable({
                responsive: false,
                autoWidth: false,
                scrollX: true,
                scrollY: true,
                pageLength: 10,
                stripeClasses: [],
                language: {
                    emptyTable: "No projects found",
                    zeroRecords: "No matching records found"
                }
            });
        });
    </script>
</body>
</html>
