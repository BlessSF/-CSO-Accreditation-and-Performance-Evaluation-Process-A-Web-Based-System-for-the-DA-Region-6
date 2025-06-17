<?php
session_start();
include ('include/db_connect.php');
include ('cso_include/header.php');
include ('cso_include/navbar.php');

if (!isset($_SESSION['cso_name'])) {
    exit('Session cso_name not set');
}

$cso_name = $_SESSION['cso_name'];

$hasRepresentative = false;

$repCheckQuery = "SELECT id FROM cso_representative WHERE cso_name = ?";
$stmt = $conn->prepare($repCheckQuery);
$stmt->bind_param("s", $cso_name);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $hasRepresentative = true;
}
$stmt->close();

// --- Get Total Proposals ---
$query = "
    SELECT COUNT(*) as total_proposal
    FROM proposal p
    INNER JOIN cso_representative cr ON p.cso_representative_id = cr.id
    WHERE cr.cso_name = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $cso_name);
$stmt->execute();
$result = $stmt->get_result();
$totalProposals = ($row = $result->fetch_assoc()) ? $row['total_proposal'] : "N/A";
$stmt->close();

// --- Get Total Projects ---
$query = "SELECT COUNT(*) AS total_projects 
    FROM proposal p 
    INNER JOIN cso_representative cr ON p.cso_representative_id = cr.id
    WHERE p.status = 'Approved' AND cr.cso_name = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $cso_name);
$stmt->execute();
$result = $stmt->get_result();
$totalProjects = ($row = $result->fetch_assoc()) ? $row['total_projects'] : "N/A";
$stmt->close();

// --- Get Latest Accreditation Status ---
$query = "
    SELECT aa.status, aa.status_updated_at
    FROM accreditation_application aa
    INNER JOIN cso_representative cr ON aa.cso_representative_id = cr.id
    WHERE cr.cso_name = ?
    ORDER BY aa.status_updated_at DESC
    LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $cso_name);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $latestAccreStatus = $row['status'];
    $lastUpdatedAccre = $row['status_updated_at'];
} else {
    $latestAccreStatus = "N/A";
    $lastUpdatedAccre = "N/A";
}
$stmt->close();

// --- Fetch Proposal Statuses for Chart ---
$query = "
    SELECT p.status, COUNT(*) as count
    FROM proposal p
    INNER JOIN cso_representative cr ON p.cso_representative_id = cr.id
    WHERE cr.cso_name = ?
    GROUP BY p.status";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $cso_name);
$stmt->execute();
$result = $stmt->get_result();
$proposalStatuses = [];
$proposalCounts = [];
while ($row = $result->fetch_assoc()) {
    $proposalStatuses[] = $row['status'];
    $proposalCounts[] = $row['count'];
}
$stmt->close();

$query = "
    SELECT p.status, COUNT(*) as count
    FROM projects p
    WHERE p.cso_id = (
        SELECT ch.id 
        FROM cso_chairperson ch
        INNER JOIN cso_representative cr ON ch.cso_name = cr.cso_name
        INNER JOIN proposal pr ON cr.id = pr.cso_representative_id
        WHERE pr.status = 'Approved'
        AND cr.cso_name = ?
        LIMIT 1
    )
    GROUP BY p.status";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $cso_name);  // cso_name is used for filtering
$stmt->execute();
$result = $stmt->get_result();

$projectStatuses = [];
$projectCounts = [];

while ($row = $result->fetch_assoc()) {
    $projectStatuses[] = $row['status'];
    $projectCounts[] = $row['count'];
}

$stmt->close();

// Check if data is empty
$hasProjectData = !empty($projectStatuses);

// --- Fetch Locations ---
$query = "
    SELECT p.latitude, p.longitude, p.title, p.location
    FROM proposal p
    INNER JOIN cso_representative cr ON p.cso_representative_id = cr.id
    WHERE cr.cso_name = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $cso_name);
$stmt->execute();
$result = $stmt->get_result();
$locations = [];
while ($row = $result->fetch_assoc()) {
    if (!is_null($row['latitude']) && !is_null($row['longitude'])) {
        $locations[] = [ 'project_name' => $row['title'], 'location' => $row['location'],'lat' => (float)$row['latitude'], 'lng' => (float)$row['longitude']]; 
    }
}

// --- Get CSO Rank and Performance Score ---
$query_rank = "
    SELECT 
        c.id, 
        c.cso_name, 
        (
            COALESCE(latest_fr.solvency, 0) * 0.10 +
            COALESCE(latest_fr.liquidity, 0) * 0.10 +
            COALESCE(latest_fr.roi, 0) * 0.10 +
            COUNT(DISTINCT p.id) * 0.10 +
            COUNT(DISTINCT pr.id) * 0.10 +
            COUNT(DISTINCT t.id) * 0.10 +
            COUNT(DISTINCT ct.id) * 0.10 +
            COUNT(DISTINCT ap.id) * 0.10
        ) AS performance_score
    FROM 
        cso_chairperson c
    LEFT JOIN 
        cso_representative cr ON c.cso_name = cr.cso_name
    LEFT JOIN 
        proposal p ON cr.id = p.cso_representative_id
    LEFT JOIN 
        proposal ap ON cr.id = ap.cso_representative_id AND ap.status = 'Approved'
    LEFT JOIN 
        project_cso pc ON c.id = pc.cso_id
    LEFT JOIN 
        projects pr ON pc.project_id = pr.id AND pr.status = 'Completed'
    LEFT JOIN 
        projects all_pr ON pc.project_id = all_pr.id
    LEFT JOIN 
        tasks t ON all_pr.id = t.project_id
    LEFT JOIN 
        tasks ct ON t.id = ct.id AND ct.status = 'Done'
    LEFT JOIN (
        SELECT 
            fr.cso_representative_id,
            fr.solvency,
            fr.liquidity,
            fr.roi
        FROM 
            financial_report fr
        INNER JOIN (
            SELECT 
                cso_representative_id, 
                MAX(upload_date) AS latest_date
            FROM 
                financial_report
            GROUP BY 
                cso_representative_id
        ) latest ON fr.cso_representative_id = latest.cso_representative_id 
                AND fr.upload_date = latest.latest_date
    ) latest_fr ON cr.id = latest_fr.cso_representative_id
    GROUP BY 
        c.id, c.cso_name, latest_fr.solvency, latest_fr.liquidity, latest_fr.roi
    ORDER BY 
        performance_score DESC
";

$result_rank = mysqli_query($conn, $query_rank);
$rank = "-";
$performanceScore = "NA";
$rankList = mysqli_fetch_all($result_rank, MYSQLI_ASSOC);
foreach ($rankList as $index => $row) {
    if ($row['cso_name'] === $cso_name) {
        $rank = $index + 1; 
        $performanceScore = round($row['performance_score'], 2);
        break;
    }
}

function getRankSuffix($rank) {
    if ($rank % 10 == 1 && $rank != 11) {
        return $rank . 'st';
    } elseif ($rank % 10 == 2 && $rank != 12) {
        return $rank . 'nd';
    } elseif ($rank % 10 == 3 && $rank != 13) {
        return $rank . 'rd';
    } else {
        return $rank . 'th';
    }
}

// Usage in your existing code
$rankWithSuffix = getRankSuffix($rank);

$query = "
    SELECT 
        fr.upload_date,
        fr.roi,
        fr.liquidity,
        fr.solvency
    FROM financial_report fr
    INNER JOIN cso_representative cr ON fr.cso_representative_id = cr.id
    WHERE cr.cso_name = ?
    ORDER BY fr.upload_date ASC"; // Get all available data

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $cso_name);
$stmt->execute();
$result = $stmt->get_result();

$dates = [];
$roi_values = [];
$liquidity_values = [];
$solvency_values = [];

while ($row = $result->fetch_assoc()) {
    $dates[] = date('Y', strtotime($row['upload_date'])); // Format as year only
    $roi_values[] = $row['roi'];
    $liquidity_values[] = $row['liquidity'];
    $solvency_values[] = $row['solvency'];
}
$stmt->close();

// Generate future years for forecasting (3 years ahead)
if (count($dates) >= 2) {
    $last_year = intval(end($dates));
    for ($i = 1; $i <= 3; $i++) {
        $dates[] = (string)($last_year + $i);
    }
}

$showFullDashboard = !in_array($latestAccreStatus, ['N/A', 'Pending']);
?>

<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <!-- External CSS/JS -->
        <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
        <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">

        <style>
            body {
                font-family: 'Nunito', sans-serif;
            }

            .container-fluid { 
            padding: 20px; 
            }

            .custom-height {
                margin-top: 15px;
                height: 760px; 
            }

            .chart-custom-height {
                margin-top: 15px;
                height: 373px; 
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
                color: #0A593A;
            }

            .table-container {
                max-height: 400px;
                overflow-y: auto;
                overflow-x: hidden;
            }

            .table {
                width: 100%;
                white-space: nowrap;
            }

            .card {
                border-radius: 10px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                transition: transform 0.2s ease, box-shadow 0.2s ease;
                background-color: #ffffff !important;
            }

            .card:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            }

            /* Value Card Styling */
            .value-card {
                padding: 0px;
                border-radius: 5px;
                border-left: 5px solid #0A593A;
            }

            .value-card .metric {
                font-size: 2rem;
                font-weight: bold;
                color: #0A593A;
            }

            .value-card .metric-title {
                font-size: 1rem;
                color: #6c757d;
                margin-top: 10px;
            }

            .value-card i {
                font-size: 2.5rem;
                color: #0A593A;
            }

            .value-card .card-body {
                display: flex;
                align-items: center; /* Vertically aligns the icon and text */
                justify-content: space-between; /* Space between icon and text */
                gap: 10px; 
            }

            .value-card .metric {
                font-size: 2rem;
                font-weight: bold;
                color: #0A593A;
                margin: 0; /* Remove any margin for better alignment */
            }

            .value-card .metric-title {
                font-size: 1rem;
                color: #6c757d;
                margin-top: 0; /* Adjust spacing between number and label */
            }

            /* Table Card Styling */
            .table-card {
                background-color: #f1f8f3;
                border-left: 5px solid #0A593A;
                height: 100%; /* Ensure it spans the container fully */
                overflow: hidden; /* Prevent table overflow from breaking the card */
            }

            .table-card:hover {
                transform: none;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            }

            .table-card .card-header {
                background-color: #0A593A;
                color: white;
                font-size: 1.05rem;
            }

            .table-card .table-responsive {
                padding: 15px;
                border-radius: 5px;
                background-color: white;
            }

            /* Chart Card Styling */
            .chart-card {
                background-color: #eaf6ec;
                border-left: 5px solid #0A593A;
            }

            .chart-card .card-header {
                background-color: #0A593A;
                color: white;
                font-size: 1.05rem;
            }

            .chart-card .chart-container {
                padding: 10px;
                border-radius: 5px;
                background-color: white;
            }

            .chart-card:hover {
                transform: none;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            }
            
            /* Table Adjustments */
            .table {
                font-size: 0.9rem;
                margin-bottom: 0;
            }

            .table th {
                background-color: #0A593A;
                color: white;
            }

            .table-container {
                max-height: 400px;
                overflow-y: auto;
            }

            .tab-pane canvas {
                width: 100% !important;
                height: 200px !important;
            }

            .chart-container {
                position: relative;
                width: 100%;
                height: 100%;
                display: flex;
                justify-content: center;
                align-items: center;
            }

            #map {
                height: 100% !important;
                width: 100% !important;
            }

            .btn-custom {
                background-color: #0A593A; 
                color: white;   
                border: none;
                font-size: 0.9rem;
                transition: color 0.3s ease, text-decoration 0.3s ease;
            }

            .btn-custom:hover {
                background-color: #0A593A;
                color: white; 
                text-decoration: underline;
            }

            .modal-header {
                background-color: #0A593A; 
                color: white; 
            }

            .btn-save-weights{
                background-color: #0A593A; /* Same green as the card header */
                color: white; /* White text */
                border: none;
                padding: 10px 20px;
                font-size: 16px;
                border-radius: 5px; /* Rounded corners */
                transition: all 0.3s ease; /* Smooth transition */
            }

            .btn-save-weights:hover {
                background-color: #084C2C; /* Darker shade of green for hover */
                color: white;
            }

            /* Cancel Button */
            .btn-cancel {
                background-color: #A52A2A; /* Red background */
                color: white; /* White text */
                border: none;
                padding: 10px 20px;
                font-size: 16px;
                border-radius: 5px;
                transition: all 0.3s ease;
            }

            .btn-cancel:hover {
                background-color: #7B1E1E; /* Darker red for hover */
                color: white;
            }

            .slider-arrow {
                position: absolute;
                top: 50%; /* Vertically center the button */
                transform: translateY(-50%);
                z-index: 10; /* Ensure the buttons are above other elements */
                color: #0A593A; 
                border: 1px solid #0A593A;
            }

            .slider-arrow:hover {
                background-color: #0A593A; 
                border: 1px solid #0A593A;
                color: white;
            }

            .slider-arrow.left {
                left: 15px; /* Position the left button to the left of the container */
            }

            .slider-arrow.right {
                right: 15px; /* Position the right button to the right of the container */
            }

            /* Custom styling for the Edit Scores button */
            .edit-scores-btn {
                background-color: transparent;
                color: #0A593A;
                border: 1px solid #0A593A;
                transition: background-color 0.3s ease, color 0.3s ease;
            }

            .edit-scores-btn:hover {
                background-color: #0A593A;
                color: #ffffff;
            }

            /* Responsive Adjustments */
            @media (max-width: 768px) {
                #sidebar {
                    margin-left: -250px;
                }
                #sidebar.active {
                    margin-left: 0;
                }
                #content {
                    width: 100%;
                    margin: 0;
                }
                #content.active {
                    margin-left: 250px;
                }
            }

            .full-height-container {
            height: 90vh;
            display: flex;
            flex-direction: column;
            }
        </style>
    </head>
    <body>
        <?php if (!$hasRepresentative): ?>
            <div class="alert alert-warning mt-3">
                <strong>Notice:</strong> You have no registered representative. Please register a representative to access the dashboard features.
            </div>
        <?php else: ?>
        <div class="container-fluid full-height-container">
            <div class="row">
                <!-- Accreditation Status -->
                <div class="col-md-4 col-lg-3 mb-3">
                    <a href="cso_accrediation.php" style="text-decoration: none;">
                        <div class="card value-card">
                            <div class="card-body">
                                <div>
                                    <div class="metric"><?php echo $latestAccreStatus; ?></div>
                                    <div class="metric-title">Accreditation Status</div>
                                </div>
                                <div><i class="fa-solid fa-certificate fa-3x"></i></div>
                            </div>
                        </div>
                    </a>
                </div>
                <?php if ($showFullDashboard): ?>
                <!-- Total Submitted Proposals -->
                <div class="col-md-4 col-lg-3 mb-3">
                    <a href="cso_proposal.php" style="text-decoration: none;">
                        <div class="card value-card">
                            <div class="card-body">
                                <div>
                                    <div class="metric"><?php echo $totalProposals; ?></div>
                                    <div class="metric-title">Submitted Proposals</div>
                                </div>
                                <div><i class="fas fa-user-pen fa-3x"></i></div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Total Projects Received -->
                <div class="col-md-4 col-lg-3 mb-3">
                    <a href="cso_projects_list.php" style="text-decoration: none;">
                        <div class="card value-card">
                            <div class="card-body">
                                <div>
                                    <div class="metric"><?php echo $totalProjects; ?></div>
                                    <div class="metric-title">Projects Received</div>
                                </div>
                                <div><i class="fas fa-user-tag fa-3x"></i></div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Rank -->
                <div class="col-md-4 col-lg-3 mb-3">
                        <div class="card value-card">
                            <div class="card-body">
                                <div>
                                    <div class="metric"><?php echo $performanceScore; ?><i style="font-size: 2rem">[<?php echo $rankWithSuffix; ?>]</i></div>
                                    <div class="metric-title">P-Score <i style="font-size: 1rem;">[Rank]</i></div>
                                </div>
                                <div><i class="fa-solid fa-trophy fa-3x"></i></div>
                            </div>
                        </div>
                </div>
                <?php endif; ?>
            </div>
            <?php if ($showFullDashboard): ?>
            <!-- Additional rows for charts -->
            <div class="row">
                <div class="col-lg-6">
                    <div class="card chart-card mb-3 chart-custom-height">
                        <div class="card-header">
                            CSO Financial Insights
                        </div>
                        <div class="card-body position-relative">
                            <?php
                            // Financial Insights Query
                            $sql = "SELECT fr.upload_date,
                                fr.indication AS indication,
                                fr.roi AS current_roi,
                                fr.liability AS current_liability,
                                fr.solvency AS current_solvency,
                                previous.roi AS previous_roi,
                                previous.liability AS previous_liability,
                                previous.solvency AS previous_solvency,
                                t.roi_threshold,
                                t.liability_threshold,
                                t.solvency_threshold
                            FROM (
                                SELECT cso_representative_id, 
                                    MAX(upload_date) AS max_upload_date
                                FROM financial_report
                                INNER JOIN cso_representative cr 
                                    ON financial_report.cso_representative_id = cr.id
                                WHERE cr.cso_name = '$cso_name'
                                GROUP BY cso_representative_id
                            ) AS latest
                            INNER JOIN financial_report fr 
                                ON latest.cso_representative_id = fr.cso_representative_id
                                AND latest.max_upload_date = fr.upload_date
                            LEFT JOIN financial_report previous 
                                ON fr.cso_representative_id = previous.cso_representative_id
                                AND previous.upload_date < fr.upload_date
                            LEFT JOIN thresholds t 
                                ON 1 = 1";
                            
                            $result = mysqli_query($conn, $sql);
                            
                            if ($result && mysqli_num_rows($result) > 0) {
                                echo '<div class="row">';
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo '      <div class="card-body">';
                                    echo '          <table class="table table-bordered custom-table">';
                                    echo '              <thead>';
                                    echo '                  <tr>';
                                    echo '                      <th>Metrics</th>';
                                    echo '                      <th>Previous Year</th>';
                                    echo '                      <th>For this year</th>';
                                    echo '                      <th>Difference</th>';
                                    echo '                      <th>Threshold</th>';
                                    echo '                  </tr>';
                                    echo '              </thead>';
                                    echo '              <tbody>';
                            
                                    $difference_roi = $row['current_roi'] - $row['previous_roi'];
                                    $difference_liability = $row['previous_liability'] - $row['current_liability'];
                                    $difference_solvency = $row['previous_solvency'] - $row['current_solvency'];
                            
                                    echo '                  <tr>';
                                    echo '                      <td>ROI (%)</td>';
                                    echo '                      <td>' . $row['previous_roi'] . '</td>';
                                    echo '                      <td>' . $row['current_roi'] . '</td>';
                                    echo '                      <td>' . number_format($difference_roi, 2) . '</td>';
                                    echo '                      <td style="text-align:center;"> >= ' . $row['roi_threshold'] . '</td>';
                                    echo '                  </tr>';
                            
                                    echo '                  <tr>';
                                    echo '                      <td>Liability</td>';
                                    echo '                      <td>' . $row['previous_liability'] . '</td>';
                                    echo '                      <td>' . $row['current_liability'] . '</td>';
                                    echo '                      <td>' . number_format($difference_liability, 2) . '</td>';
                                    echo '                      <td style="text-align:center;"> > ' . $row['liability_threshold'] . '</td>';
                                    echo '                  </tr>';
                            
                                    echo '                  <tr>';
                                    echo '                      <td>Solvency (%)</td>';
                                    echo '                      <td>' . $row['previous_solvency'] . '</td>';
                                    echo '                      <td>' . $row['current_solvency'] . '</td>';
                                    echo '                      <td>' . number_format($difference_solvency, 2) . '</td>';
                                    echo '                      <td style="text-align:center;"> <= ' . $row['solvency_threshold'] . '</td>';
                                    echo '                  </tr>';
                            
                                    $indication = trim($row['indication'] ?? ''); // Ensure it's not NULL

                                    if ($indication === '') {
                                        $indication = 'Error';
                                        $description = 'No indication data available.';
                                    } else {
                                        switch ($indication) {
                                            case 'Excellent':
                                                $description = 'Significant improvements across all metrics compared to last year.';
                                                break;
                                            case 'Very Good':
                                                $description = 'Metrics have improved notably, meeting or exceeding key thresholds.';
                                                break;
                                            case 'Good':
                                                $description = 'Positive improvements in key metrics.';
                                                break;
                                            case 'Fair':
                                                $description = 'Metrics have shown some improvement, though more progress is desired.';
                                                break;
                                            case 'Poor':
                                                $description = 'Metrics have declined or shown minimal improvement compared to last year.';
                                                break;
                                            default:
                                                $description = 'No description available.';
                                        }
                                    }

                                    echo '<tr>';
                                    echo '<td colspan="5"><span class="font-weight-bold">' . htmlspecialchars($indication) . '</span>: ' . htmlspecialchars($description) . '</td>';
                                    echo '</tr>';

                            
                                    echo '              </tbody>';
                                    echo '          </table>';
                                    echo '      </div>';
                                }
                                echo '</div>';
                            } else {
                                echo '<p>No financial report data available.</p>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="card chart-card mb-3 chart-custom-height">
                        <div class="card-header">
                            Project Mapping
                        </div>
                        <div class="card-body" style="padding: 0;">
                            <div id="map" style="height: 500px;"></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card chart-card mb-3 chart-custom-height">
                        <div class="card-header">
                            Financial Forecast
                        </div>
                        <div class="card-body">
                            <canvas id="financialForecastChart"></canvas>
                        </div>
                    </div>
                            
                    <div class="card chart-card chart-custom-height">
                        <div class="card-header">
                            Proposal & Project Insights
                        </div>
                        <div class="card-body">
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item">
                                    <button class="nav-link active" id="projects-status-tab" data-bs-toggle="tab" data-bs-target="#projects-status-chart" role="tab" aria-controls="projects-status-chart" aria-selected="false">Project by Status</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" id="proposals-status-tab" data-bs-toggle="tab" data-bs-target="#proposals-status-chart" role="tab" aria-controls="proposals-status-chart" aria-selected="false">Proposal by Status</button>
                                </li>
                            </ul>
                            <div class="tab-content">
                                <!-- Projects Status Bar Chart -->
                                <div class="tab-pane fade show active" id="projects-status-chart" role="tabpanel" aria-labelledby="projects-status-tab">
                                    <br>
                                    <div class="chart-container">
                                        <canvas id="projectsBarChart"></canvas>
                                    </div>
                                </div>

                                <!-- Proposals Status Bar Chart -->
                                <div class="tab-pane fade" id="proposals-status-chart" role="tabpanel" aria-labelledby="proposals-status-tab">
                                    <br>
                                    <div class="chart-container">
                                        <canvas id="proposalsBarChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    <?php include ('cso_include/script.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.heat/dist/leaflet-heat.js"></script>

    <script>
        // Projects Bar Chart
        var projectCtx = document.getElementById('projectsBarChart').getContext('2d');
        var projectChart = new Chart(projectCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($projectStatuses); ?>,  // The statuses (labels)
                datasets: [{
                    label: 'Projects by Status',
                    data: <?php echo json_encode($projectCounts); ?>,  // The counts (data)
                    backgroundColor: [
                        '#4e73df',  // Blue for "Completed"
                        '#36b9cc',  // Green for "Pending"
                        '#f6c23e',  // Yellow for "In Progress"
                        '#e74a3b',  // Red for other statuses
                    ],
                    borderColor: [
                        '#4e73df', 
                        '#36b9cc', 
                        '#f6c23e', 
                        '#e74a3b',
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,  // Ensures chart scales correctly without distortion
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Project Status'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Projects'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            generateLabels: function(chart) {
                                // Creating custom legend using x-axis labels
                                return chart.data.labels.map((label, index) => ({
                                    text: label,
                                    fillStyle: chart.data.datasets[0].backgroundColor[index],
                                    strokeStyle: chart.data.datasets[0].borderColor[index],
                                    lineWidth: 1
                                }));
                            }
                        }
                    }
                }
            }
        });

        // Proposals Bar Chart
        var proposalCtx = document.getElementById('proposalsBarChart').getContext('2d');
        var proposalChart = new Chart(proposalCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($proposalStatuses); ?>,  // The statuses (labels)
                datasets: [{
                    data: <?php echo json_encode($proposalCounts); ?>,  // The counts (data)
                    backgroundColor: [
                        '#4e73df',  // Blue for "Approved"
                        '#36b9cc',  // Green for "Pending"
                        '#f6c23e',  // Yellow for "Rejected"
                        '#e74a3b',  // Red for other statuses
                    ],
                    borderColor: [
                        '#4e73df', 
                        '#36b9cc', 
                        '#f6c23e', 
                        '#e74a3b',
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,  // Ensures chart scales correctly without distortion
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Proposal Status'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Proposals'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            generateLabels: function(chart) {
                                // Creating custom legend using x-axis labels
                                return chart.data.labels.map((label, index) => ({
                                    text: label,
                                    fillStyle: chart.data.datasets[0].backgroundColor[index],
                                    strokeStyle: chart.data.datasets[0].borderColor[index],
                                    lineWidth: 1
                                }));
                            }
                        }
                    }
                }
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var map; // Declare map variable globally

            // Initialize map only if it hasn't been created yet
            if (!map) {
                map = L.map('map').setView([10.792999, 122.442999], 8); // Centered on WV Philippines

                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                // Ensure leaflet.heat is loaded
                if (typeof L.heatLayer !== 'function') {
                    console.error("Leaflet Heatmap plugin is missing.");
                    return;
                }

                // **Embed PHP Heatmap Data Directly**
                var heatmapData = <?php echo json_encode($locations); ?>;
                console.log("Heatmap Data:", heatmapData);

                if (!Array.isArray(heatmapData) || heatmapData.length === 0) {
                    console.warn("No valid heatmap data available.");
                    return;
                }

                // Convert data to Leaflet Heatmap format
                var heatArray = heatmapData.map(coords => [parseFloat(coords.lat), parseFloat(coords.lng), 1]); // Intensity = 1

                // Add heatmap layer
                L.heatLayer(heatArray, { radius: 25, blur: 15, maxZoom: 17 }).addTo(map);

                // Add red circles to locations
                heatmapData.forEach(coords => {
                    if (coords.lat && coords.lng) {
                        var marker = L.circleMarker([parseFloat(coords.lat), parseFloat(coords.lng)], {
                            color: 'red',
                            fillColor: 'red',
                            fillOpacity: 0.5,
                            radius: 8
                        }).addTo(map);

                        // Bind tooltip with project name and location
                        marker.bindTooltip(`<b>${coords.project_name}</b><br>${coords.location}`, { permanent: false, direction: "top" });
                    }
                });
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get the financial forecast chart canvas
            var forecastCtx = document.getElementById('financialForecastChart').getContext('2d');
            
            // Extract data from PHP
            var dates = <?php echo json_encode($dates); ?>;
            var roiValues = <?php echo json_encode($roi_values); ?>;
            var liquidityValues = <?php echo json_encode($liquidity_values); ?>;
            var solvencyValues = <?php echo json_encode($solvency_values); ?>;
            
            // Historical data length
            var historicalLength = roiValues.length;
            
            // Calculate forecasts if we have at least 2 data points
            if (historicalLength >= 2) {
                var forecastYears = 3;
                var roiForecast = calculateForecast(roiValues, forecastYears);
                var liquidityForecast = calculateForecast(liquidityValues, forecastYears);
                var solvencyForecast = calculateForecast(solvencyValues, forecastYears);
                
                // Create combined datasets with historical and forecast data
                var combinedRoi = [...roiValues, ...roiForecast];
                var combinedLiquidity = [...liquidityValues, ...liquidityForecast];
                var combinedSolvency = [...solvencyValues, ...solvencyForecast];
                
                // Extend the dates array for the forecast
                var forecastDates = [...dates];
                for (var i = 1; i <= forecastYears; i++) {
                    forecastDates.push((parseInt(dates[dates.length - 1]) + i).toString()); // Add forecast years
                }
            } else {
                var combinedRoi = roiValues;
                var combinedLiquidity = liquidityValues;
                var combinedSolvency = solvencyValues;
                var forecastDates = dates;
            }

            // Create segment styles for each dataset
            var financialChart = new Chart(forecastCtx, {
                type: 'line',
                data: {
                    labels: forecastDates,
                    datasets: [
                        // ROI dataset (single continuous line)
                        {
                            label: 'ROI (%)',
                            data: combinedRoi,
                            borderColor: '#4e73df',
                            backgroundColor: 'rgba(78, 115, 223, 0.1)',
                            pointBackgroundColor: '#4e73df',
                            pointBorderColor: '#4e73df',
                            pointHoverBackgroundColor: '#4e73df',
                            pointHoverBorderColor: '#4e73df',
                            borderWidth: 2,
                            tension: 0.1,
                            fill: false,
                            segment: {
                                borderDash: function(ctx) {
                                    // Use solid line for historical data and dashed for forecast
                                    return ctx.p1DataIndex >= historicalLength ? [5, 5] : [];
                                }
                            },
                            spanGaps: false
                        },
                        // Liquidity dataset (single continuous line)
                        {
                            label: 'Liquidity',
                            data: combinedLiquidity,
                            borderColor: '#36b9cc',
                            backgroundColor: 'rgba(54, 185, 204, 0.1)',
                            pointBackgroundColor: '#36b9cc',
                            pointBorderColor: '#36b9cc',
                            pointHoverBackgroundColor: '#36b9cc',
                            pointHoverBorderColor: '#36b9cc',
                            borderWidth: 2,
                            tension: 0.1,
                            fill: false,
                            segment: {
                                borderDash: function(ctx) {
                                    // Use solid line for historical data and dashed for forecast
                                    return ctx.p1DataIndex >= historicalLength ? [5, 5] : [];
                                }
                            },
                            spanGaps: false
                        },
                        // Solvency dataset (single continuous line)
                        {
                            label: 'Solvency (%)',
                            data: combinedSolvency,
                            borderColor: '#f6c23e',
                            backgroundColor: 'rgba(246, 194, 62, 0.1)',
                            pointBackgroundColor: '#f6c23e',
                            pointBorderColor: '#f6c23e',
                            pointHoverBackgroundColor: '#f6c23e',
                            pointHoverBorderColor: '#f6c23e',
                            borderWidth: 2,
                            tension: 0.1,
                            fill: false,
                            segment: {
                                borderDash: function(ctx) {
                                    // Use solid line for historical data and dashed for forecast
                                    return ctx.p1DataIndex >= historicalLength ? [5, 5] : [];
                                }
                            },
                            spanGaps: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: true  // Only show tooltip when hovering directly over a point
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        tooltip: {
                            enabled: true,
                            callbacks: {
                                title: function(tooltipItems) {
                                    return tooltipItems[0].label; // Year
                                },
                                label: function(tooltipItem) {
                                    const isForecasted = tooltipItem.dataIndex >= historicalLength;
                                    const status = isForecasted ? '(Forecast)' : '(Historical)';
                                    return `${tooltipItem.dataset.label} ${status}: ${tooltipItem.raw}`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Year'
                            },
                            ticks: {
                                autoSkip: false,
                                maxRotation: 45,
                                minRotation: 45
                            }
                        },
                        y: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Value'
                            },
                            beginAtZero: true
                        }
                    }
                }
            });

            // Helper function to calculate forecast using linear regression
            function calculateForecast(data, periodsAhead) {
                // Ensure data contains at least 2 points
                if (data.length < 2) {
                    console.error("Not enough data for forecasting");
                    return [];
                }

                // Convert all string values in the data array to numbers
                data = data.map(value => parseFloat(value));

                const n = data.length;
                const indices = Array.from({ length: n }, (_, i) => i);

                // Calculate means for linear regression
                const meanX = indices.reduce((sum, x) => sum + x, 0) / n;
                const meanY = data.reduce((sum, y) => sum + y, 0) / n;

                // Calculate slope and intercept for linear regression
                let numerator = 0;
                let denominator = 0;

                for (let i = 0; i < n; i++) {
                    const diffX = indices[i] - meanX;
                    const diffY = data[i] - meanY;

                    numerator += diffX * diffY;
                    denominator += Math.pow(diffX, 2);
                }

                const slope = numerator / denominator;
                const intercept = meanY - slope * meanX;

                // Generate forecast values
                const forecast = [];
                for (let i = 1; i <= periodsAhead; i++) {
                    const forecastValue = slope * (n + i - 1) + intercept;
                    forecast.push(parseFloat(forecastValue.toFixed(2)));  // Round to two decimal places
                }

                return forecast;
            }
        });
    </script>
<?php
mysqli_close($conn);
?>