    <?php
    include('admin_include/header.php');
    include('admin_include/navbar.php');
    include('include/db_connect.php');

    // Function to execute queries with error handling
    function executeQuery($conn, $query, $errorMessage) {
        $result = mysqli_query($conn, $query);
        if (!$result) {
            die("$errorMessage: " . mysqli_error($conn));
        }
        return $result;
    }

    // Function to get Solvency Data
    function getSolvencyData($conn) {
        $query = "SELECT c.cso_name, fr.solvency FROM cso_chairperson c
                JOIN cso_representative cr ON c.cso_name = cr.cso_name
                JOIN financial_report fr ON cr.id = fr.cso_representative_id";
        $result = mysqli_query($conn, $query);
        $labels = [];
        $values = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $labels[] = $row['cso_name'];
            $values[] = $row['solvency'];
        }
        return ['labels' => $labels, 'values' => $values];
    }

    // Function to get Liquidity Data
    function getLiquidityData($conn) {
        $query = "SELECT c.cso_name, fr.liquidity FROM cso_chairperson c
                JOIN cso_representative cr ON c.cso_name = cr.cso_name
                JOIN financial_report fr ON cr.id = fr.cso_representative_id";
        $result = mysqli_query($conn, $query);
        $labels = [];
        $values = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $labels[] = $row['cso_name'];
            $values[] = $row['liquidity'];
        }
        return ['labels' => $labels, 'values' => $values];
    }

    // Function to get ROI Data
    function getROIData($conn) {
        $query = "SELECT 
                    CONCAT('Q', QUARTER(upload_date), ' ', YEAR(upload_date)) AS quarter, 
                    AVG(roi) AS avg_roi 
                FROM financial_report 
                GROUP BY quarter 
                ORDER BY YEAR(upload_date), QUARTER(upload_date)";
        $result = mysqli_query($conn, $query);
        if (!$result) {
            die("ROI Data Query Failed: " . mysqli_error($conn));
        }
        $labels = [];
        $values = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $labels[] = $row['quarter'];
            $values[] = $row['avg_roi'];
        }
        return ['labels' => $labels, 'values' => $values];
    }

    // Function to get Performance Score Data
    function getPerformanceScoreData($conn) {
        // Aggregate average scores across criteria
        $query = "SELECT 'Solvency' AS criterion, AVG(fr.solvency) AS score FROM financial_report fr
                UNION
                SELECT 'Liquidity', AVG(fr.liquidity) FROM financial_report fr
                UNION
                SELECT 'ROI', AVG(fr.roi) FROM financial_report fr
                UNION
                SELECT 'Total Projects', AVG(c.total_projects) FROM (
                    SELECT c.id, COUNT(DISTINCT all_pr.id) AS total_projects
                    FROM cso_chairperson c
                    LEFT JOIN project_cso pc ON c.id = pc.cso_id
                    LEFT JOIN projects all_pr ON pc.project_id = all_pr.id
                    GROUP BY c.id
                ) c
                UNION
                SELECT 'Completed Projects', AVG(c.completed_projects) FROM (
                    SELECT c.id, COUNT(DISTINCT pr.id) AS completed_projects
                    FROM cso_chairperson c
                    LEFT JOIN project_cso pc ON c.id = pc.cso_id
                    LEFT JOIN projects pr ON pc.project_id = pr.id AND pr.status = 'Completed'
                    GROUP BY c.id
                ) c
                UNION
                SELECT 'Total Tasks', AVG(t.total_tasks) FROM (
                    SELECT c.id, COUNT(t.id) AS total_tasks
                    FROM cso_chairperson c
                    LEFT JOIN project_cso pc ON c.id = pc.cso_id
                    LEFT JOIN projects p ON pc.project_id = p.id
                    LEFT JOIN tasks t ON p.id = t.project_id
                    GROUP BY c.id
                ) t
                UNION
                SELECT 'Completed Tasks', AVG(t.completed_tasks) FROM (
                    SELECT c.id, COUNT(ct.id) AS completed_tasks
                    FROM cso_chairperson c
                    LEFT JOIN project_cso pc ON c.id = pc.cso_id
                    LEFT JOIN projects p ON pc.project_id = p.id
                    LEFT JOIN tasks t ON p.id = t.project_id
                    LEFT JOIN tasks ct ON t.id = ct.id AND ct.status = 'Done'
                    GROUP BY c.id
                ) t";
        $result = mysqli_query($conn, $query);
        $labels = [];
        $values = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $labels[] = $row['criterion'];
            $values[] = $row['score'];
        }
        return ['labels' => $labels, 'values' => $values];
    }

    // Fetch Total Civil Service Organizations
    $query_total_cso = "SELECT COUNT(*) AS total_cso FROM cso_chairperson";
    $result_total_cso = executeQuery($conn, $query_total_cso, "Total CSO Query Failed");
    $row_total_cso = mysqli_fetch_assoc($result_total_cso);
    $total_cso = $row_total_cso['total_cso'];

    // Fetch Total Accreditation Applications
    $query_accreditation = "SELECT COUNT(*) AS total_accreditation FROM accreditation_application";
    $result_accreditation = executeQuery($conn, $query_accreditation, "Accreditation Applications Query Failed");
    $row_accreditation = mysqli_fetch_assoc($result_accreditation);
    $total_accreditation = $row_accreditation['total_accreditation'];

    // Fetch Pending Proposals
    $query_pending_proposals = "SELECT COUNT(*) AS pending_proposals FROM proposal WHERE status = 'Pending'";
    $result_pending_proposals = executeQuery($conn, $query_pending_proposals, "Pending Proposals Query Failed");
    $row_pending_proposals = mysqli_fetch_assoc($result_pending_proposals);
    $pending_proposals = $row_pending_proposals['pending_proposals'];

    // Fetch Completed Projects
    // Note: This might be redundant now with the new rankings query
    $query_completed_projects = "SELECT COUNT(*) AS completed_projects FROM projects WHERE status = 'Completed'";
    $result_completed_projects = executeQuery($conn, $query_completed_projects, "Completed Projects Query Failed");
    $row_completed_projects = mysqli_fetch_assoc($result_completed_projects);
    $completed_projects = $row_completed_projects['completed_projects'];

    // Fetch Ranking Weights
    $query_weights = "SELECT * FROM ranking_weights LIMIT 1";
    $result_weights = executeQuery($conn, $query_weights, "Fetching Ranking Weights Failed");
    $weights = mysqli_fetch_assoc($result_weights);

    // Assign weights to variables for easier use
    $weightSolvency = isset($weights['weightSolvency']) ? floatval($weights['weightSolvency']) : 0.1000;
    $weightLiquidity = isset($weights['weightLiquidity']) ? floatval($weights['weightLiquidity']) : 0.1000;
    $weightROI = isset($weights['weightROI']) ? floatval($weights['weightROI']) : 0.1000;

    // New grouped criteria weights
    $weightCompletionRate = isset($weights['weightCompletionRate']) ? floatval($weights['weightCompletionRate']) : 0.10;
    $weightApprovalRate = isset($weights['weightApprovalRate']) ? floatval($weights['weightApprovalRate']) : 0.10;

    // Non-Numerical Criteria Weights (Point System)
    $weightAccuracy = isset($weights['weightAccuracy']) ? floatval($weights['weightAccuracy']) : 0.05;
    $weightCompliance = isset($weights['weightCompliance']) ? floatval($weights['weightCompliance']) : 0.05;
    $weightCommunityEngagement = isset($weights['weightCommunityEngagement']) ? floatval($weights['weightCommunityEngagement']) : 0.05;

    // **New Negative Criteria Weights**
    $weightProjectDelayImpact = isset($weights['weightProjectDelayImpact']) ? floatval($weights['weightProjectDelayImpact']) : 0.10;
    $weightBudgetDeviationImpact = isset($weights['weightBudgetDeviationImpact']) ? floatval($weights['weightBudgetDeviationImpact']) : 0.10;

    // Fetch data for charts
    $solvencyData = getSolvencyData($conn);
    $liquidityData = getLiquidityData($conn);
    $roiData = getROIData($conn);
    $performanceScoreData = getPerformanceScoreData($conn);

    // Encode data to JSON for JavaScript
    $solvencyDataJSON = json_encode($solvencyData);
    $liquidityDataJSON = json_encode($liquidityData);
    $roiDataJSON = json_encode($roiData);
    $performanceScoreDataJSON = json_encode($performanceScoreData);

    $query_top_csos = "
    SELECT 
        c.id, 
        c.cso_name, 
        CONCAT(c.first_name, ' ', c.last_name) AS chairperson_name,
        c.profile_image,
        CONCAT(cr.first_name, ' ', cr.last_name) AS representative_name,
        
        -- Latest financial data from the current year
        latest_fr.solvency,
        latest_fr.liquidity,
        latest_fr.roi,

        -- Grouped Criteria: Use aggregated project/proposal counts
        (CASE WHEN c.total_projects > 0 THEN (c.completed_projects / c.total_projects) * 100 ELSE 0 END) AS completion_rate,
        (CASE WHEN p.total_proposals > 0 THEN (p.approved_proposals / p.total_proposals) * 100 ELSE 0 END) AS approval_rate,

        -- Negative Criteria aggregated over the year (if none found, use zero)
        COALESCE(neg.total_days_over, 0) AS days_over,
        COALESCE(neg.total_budget_over, 0) AS budget_over,

        -- Non-Numerical Criteria (Point System)
        e.accuracy_score,
        e.compliance_score,
        e.community_engagement_score,

        -- Aggregated values (for reference)
        c.total_projects,
        c.completed_projects,
        p.total_proposals,
        p.approved_proposals,

        -- Calculated Performance Score using the specified weights
        (
            latest_fr.solvency * $weightSolvency +
            latest_fr.liquidity * $weightLiquidity +
            latest_fr.roi * $weightROI +
            (CASE WHEN c.total_projects > 0 THEN (c.completed_projects / c.total_projects) * 100 ELSE 0 END) * $weightCompletionRate +
            (CASE WHEN p.total_proposals > 0 THEN (p.approved_proposals / p.total_proposals) * 100 ELSE 0 END) * $weightApprovalRate -
            COALESCE(neg.total_days_over, 0) * $weightProjectDelayImpact -
            COALESCE(neg.total_budget_over, 0) * $weightBudgetDeviationImpact +
            e.accuracy_score * $weightAccuracy +
            e.compliance_score * $weightCompliance +
            e.community_engagement_score * $weightCommunityEngagement
        ) AS performance_score

    FROM 
        (
            -- Subquery for each CSO's project counts (only for projects in the current year)
            SELECT 
                c.id,
                c.cso_name,
                c.first_name,
                c.last_name,
                c.profile_image,
                COUNT(DISTINCT all_pr.id) AS total_projects,
                COUNT(DISTINCT pr.id) AS completed_projects
            FROM cso_chairperson c
            LEFT JOIN project_cso pc ON c.id = pc.cso_id
            LEFT JOIN projects pr ON pc.project_id = pr.id 
                AND pr.status = 'Completed' 
                AND YEAR(pr.created_at) = YEAR(CURDATE())
            LEFT JOIN projects all_pr ON pc.project_id = all_pr.id 
                AND YEAR(all_pr.created_at) = YEAR(CURDATE())
            GROUP BY c.id, c.cso_name
        ) c
    LEFT JOIN cso_representative cr ON c.cso_name = cr.cso_name
    LEFT JOIN (
        -- Join only the latest financial report for each representative in the current year
        SELECT fr1.*
        FROM financial_report fr1
        INNER JOIN (
            SELECT cso_representative_id, MAX(upload_date) AS latest_upload
            FROM financial_report
            WHERE YEAR(upload_date) = YEAR(CURDATE())
            GROUP BY cso_representative_id
        ) fr2 
        ON fr1.cso_representative_id = fr2.cso_representative_id 
        AND fr1.upload_date = fr2.latest_upload
    ) latest_fr ON cr.id = latest_fr.cso_representative_id
    LEFT JOIN cso_evaluations e ON c.id = e.cso_id
    LEFT JOIN (
        -- Aggregate negative criteria (project delay and budget deviation) for projects in the current year
        SELECT 
            pc.cso_id,
            SUM(pr.days_over) AS total_days_over,
            SUM(pr.budget_over) AS total_budget_over
        FROM project_cso pc
        JOIN projects pr ON pc.project_id = pr.id
        WHERE YEAR(pr.created_at) = YEAR(CURDATE())
        GROUP BY pc.cso_id
    ) neg ON c.id = neg.cso_id
    LEFT JOIN (
        -- Aggregate proposal data for the current year
        SELECT 
            cr.cso_name, 
            COUNT(p.id) AS total_proposals, 
            SUM(CASE WHEN p.status = 'Approved' THEN 1 ELSE 0 END) AS approved_proposals
        FROM proposal p
        JOIN cso_representative cr ON p.cso_representative_id = cr.id
        WHERE YEAR(p.status_updated_at) = YEAR(CURDATE())
        GROUP BY cr.cso_name
    ) p ON c.cso_name = p.cso_name

    ORDER BY performance_score DESC
    LIMIT 5
    ";

    $result_top_csos = executeQuery($conn, $query_top_csos, "Top CSOs Query Failed");
    $top_csos = mysqli_fetch_all($result_top_csos, MYSQLI_ASSOC);

    $query_representatives = "SELECT COUNT(*) AS total_representatives FROM cso_representative";
    $result_representatives = mysqli_query($conn, $query_representatives);
    $row_representatives = mysqli_fetch_assoc($result_representatives);
    $totalRepresentatives = $row_representatives['total_representatives'];

    $query_chairpersons = "SELECT COUNT(*) AS total_chairpersons FROM cso_chairperson";
    $result_chairpersons = mysqli_query($conn, $query_chairpersons);
    $row_chairpersons = mysqli_fetch_assoc($result_chairpersons);
    $totalChairpersons = $row_chairpersons['total_chairpersons'];

    $query_accreditations = "
        SELECT COUNT(*) AS count
        FROM accreditation_application";

    $result_accreditations = mysqli_query($conn, $query_accreditations);

    $query_renewals = "
        SELECT YEAR(status_updated_at) AS year, COUNT(*) AS count
        FROM renewal_application
        GROUP BY YEAR(status_updated_at)
        ORDER BY YEAR(status_updated_at) DESC";
    $result_renewals = mysqli_query($conn, $query_renewals);

    $query_projects = "
        SELECT 
            c.id AS cso_id, 
            c.cso_name, 
            COUNT(p.id) AS project_count
        FROM project_cso pc
        INNER JOIN projects p ON pc.project_id = p.id
        INNER JOIN cso_chairperson c ON pc.cso_id = c.id
        GROUP BY c.id, c.cso_name";
    $result_projects = mysqli_query($conn, $query_projects);
    $chart_data_projects = [];
    while ($row = mysqli_fetch_assoc($result_projects)) {
        $chart_data_projects[] = [
            'label' => $row['cso_name'],
            'value' => $row['project_count']
        ];
    }

    $query_proposals = "
        SELECT r.cso_name, COUNT(*) AS proposal_count
        FROM proposal pr
        INNER JOIN cso_representative r ON pr.cso_representative_id = r.id
        GROUP BY r.cso_name";
    $result_proposals = mysqli_query($conn, $query_proposals);
    $chart_data_proposals = [];
    while ($row = mysqli_fetch_assoc($result_proposals)) {
        $chart_data_proposals[] = [
            'label' => $row['cso_name'],
            'value' => $row['proposal_count']
        ];
    }

    $projectsChartLabels = [];
    $projectsChartValues = [];
    $projectsChartColors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e'];
    foreach ($chart_data_projects as $data) {
        $projectsChartLabels[] = $data['label'];
        $projectsChartValues[] = $data['value'];
    }

    $proposalsChartLabels = [];
    $proposalsChartValues = [];
    $proposalsChartColors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e'];
    foreach ($chart_data_proposals as $data) {
        $proposalsChartLabels[] = $data['label'];
        $proposalsChartValues[] = $data['value'];
    }

    $query_projects_by_status = "
        SELECT status, COUNT(*) AS status_count
        FROM projects
        GROUP BY status";
    $result_projects_by_status = mysqli_query($conn, $query_projects_by_status);

    $projectStatusLabels = [];
    $projectStatusCounts = [];
    while ($row = mysqli_fetch_assoc($result_projects_by_status)) {
        $projectStatusLabels[] = $row['status'];  // e.g. "Completed", "Pending"
        $projectStatusCounts[] = $row['status_count'];  // Count for each status
    }



    $query_proposals_by_status = "
        SELECT status, COUNT(*) AS status_count
        FROM proposal
        GROUP BY status";
    $result_proposals_by_status = mysqli_query($conn, $query_proposals_by_status);

    $proposalStatusLabels = [];
    $proposalStatusCounts = [];
    while ($row = mysqli_fetch_assoc($result_proposals_by_status)) {
        $proposalStatusLabels[] = $row['status'];  // e.g. "Approved", "Pending"
        $proposalStatusCounts[] = $row['status_count'];  // Count for each status
    }

    $cso_locations = [];
    $query_cso = "
        SELECT cso_name, latitude, longitude, cso_address, CONCAT(first_name, ' ', last_name) AS chairperson_name
        FROM cso_chairperson 
        WHERE latitude IS NOT NULL 
        AND longitude IS NOT NULL 
        AND status = 'Verified'
    ";
    $result_cso = mysqli_query($conn, $query_cso);
    if (!$result_cso) {
        die("CSO Query Failed: " . mysqli_error($conn));
    }
    while ($row = mysqli_fetch_assoc($result_cso)) {
        $cso_locations[] = $row;
    }

    $projects_locations = [];
    $query_projects = "
        SELECT 
            p.location,
            p.latitude,
            p.longitude,
            p.title,
            p.status,
            c.cso_name
        FROM projects p
        JOIN cso_chairperson c ON p.cso_id = c.id
        WHERE p.proposal_status = 'Approved'
        AND p.latitude IS NOT NULL
        AND p.longitude IS NOT NULL
    ";
    $result_projects = mysqli_query($conn, $query_projects);
    if (!$result_projects) {
        die("Projects Query Failed: " . mysqli_error($conn));
    }
    while ($row = mysqli_fetch_assoc($result_projects)) {
        $projects_locations[] = $row;
    }
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
                gap: 10px; /* Optional: Adds spacing between the icon and text */
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
                width: 75%;
                height: 75%;
                margin: 0 auto;                 
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
                left: 10px; /* Position the left button to the left of the container */
            }

            .slider-arrow.right {
                right: 10px; /* Position the right button to the right of the container */
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
            #csoDetailsWrapper {
                overflow-x: auto;
            }

            #csoDetails table {
                width: 100%;
                table-layout: fixed;
                word-wrap: break-word;
            }
        </style>
    </head>
    <body>
        <div class="container-fluid full-height-container">
            <div class="row">
                <!-- Total CSOs -->
                <div class="col-md-4 col-lg-3 mb-3">
                    <a href="admin_cso_users.php" style="text-decoration: none;">
                        <div class="card value-card">
                            <div class="card-body">
                                <div>
                                    <div class="metric"><?php echo htmlspecialchars($total_cso); ?></div>
                                    <div class="metric-title">Total CSOs</div>
                                </div>
                                <div>
                                    <i class="fas fa-users fa-3x"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4 col-lg-3 mb-3">
                    <a href="admin_accreditation.php" style="text-decoration: none;">
                        <div class="card value-card">
                            <div class="card-body">
                                <div>
                                    <div class="metric"><?php echo htmlspecialchars($total_accreditation); ?></div>
                                    <div class="metric-title">Accreditation Applications</div>
                                </div>
                                <div>
                                    <i class="fas fa-file-alt fa-3x"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4 col-lg-3 mb-3">
                    <a href="admin_proposal.php" style="text-decoration: none;">
                        <div class="card value-card">
                            <div class="card-body">
                                <div>
                                    <div class="metric"><?php echo htmlspecialchars($pending_proposals); ?></div>
                                    <div class="metric-title">Pending Proposals</div>
                                </div>
                                <div>
                                    <i class="fas fa-hourglass-half fa-3x"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4 col-lg-3 mb-3">
                    <a href="admin_projects_list.php" style="text-decoration: none;">
                        <div class="card value-card">
                            <div class="card-body">
                                <div>
                                    <div class="metric"><?php echo htmlspecialchars($completed_projects); ?></div>
                                    <div class="metric-title">Completed Projects</div>
                                </div>
                                <div>
                                    <i class="fas fa-check-circle fa-3x"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            <div class="row">
                <!-- Best Performing CSOs -->
                <div class="col-lg-6">
                    <div class="card chart-card mb-3 chart-custom-height">
                    <?php
                        $result_top_csos = executeQuery($conn, $query_top_csos, "Top CSOs Query Failed");
                        $top3 = [];
                        $allCSOs = [];
                        $counter = 0;
                        while ($row = mysqli_fetch_assoc($result_top_csos)) {
                            $allCSOs[] = $row;
                            if ($row['performance_score'] > 0 && $counter < 3) {
                                $top3[] = $row;
                                $counter++;
                            }
                        }
                    ?>
                        <div class="card-header">
                            Best Performing CSOs
                        </div>
                        <br>
                        <div class="container-chart">
                            <canvas id="rankingsChart" style="height: 20%;"></canvas>
                        </div>
                    </div>
                    
                    <div class="card chart-card chart-custom-height">
                        <div class="card-header">
                            CSOs and Project Locations
                        </div>
                        <div class="card-body">
                            <div id="csoMap" style="height: 100%;"></div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section with Tabs -->
                <div class="col-lg-6">
                    <div class="card chart-card mb-3 chart-custom-height">
                        <div class="card-header">
                            CSO Financial Insights
                        </div>
                        <div class="card-body position-relative">
                            <!-- Left Button -->
                            <button 
                                class="btn btn-outline-primary btn-sm slider-arrow left" 
                                id="prevCso" 
                                onclick="moveSlider(-1)">
                                &#10094;
                            </button>

                            <div id="csoDetailsWrapper" class="mx-auto" style="width: 100%;">
                                <div id="csoDetails"></div>
                            </div>

                            <!-- Right Button -->
                            <button 
                                class="btn btn-outline-primary btn-sm slider-arrow right" 
                                id="nextCso" 
                                onclick="moveSlider(1)">
                                &#10095;
                            </button>
                            <div id="comparisonTable" class="mt-5"></div>
                        </div>
                    </div>
                    
                    <div class="card chart-card chart-custom-height">
                        <div class="card-header">
                            Proposal & Project Insights
                        </div>
                        <div class="card-body">
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item">
                                    <button class="nav-link active" id="projects-tab" data-bs-toggle="tab" data-bs-target="#projects-chart" role="tab" aria-controls="projects-chart" aria-selected="true">Project by CSO</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" id="proposals-tab" data-bs-toggle="tab" data-bs-target="#proposals-chart" role="tab" aria-controls="proposals-chart" aria-selected="false">Proposal by CSO</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" id="projects-status-tab" data-bs-toggle="tab" data-bs-target="#projects-status-chart" role="tab" aria-controls="projects-status-chart" aria-selected="false">Project by Status</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" id="proposals-status-tab" data-bs-toggle="tab" data-bs-target="#proposals-status-chart" role="tab" aria-controls="proposals-status-chart" aria-selected="false">Proposal by Status</button>
                                </li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="projects-chart" role="tabpanel" aria-labelledby="projects-tab">
                                    <div class="chart-container">
                                        <canvas id="projectsChart"></canvas>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="proposals-chart" role="tabpanel" aria-labelledby="proposals-tab">
                                    <div class="chart-container">
                                        <canvas id="proposalsChart"></canvas>
                                    </div>
                                </div>
                                <!-- Projects Status Bar Chart -->
                                <div class="tab-pane fade" id="projects-status-chart" role="tabpanel" aria-labelledby="projects-status-tab">
                                    <div class="chart-container">
                                        <canvas id="projectsBarChart"></canvas>
                                    </div>
                                </div>

                                <!-- Proposals Status Bar Chart -->
                                <div class="tab-pane fade" id="proposals-status-chart" role="tabpanel" aria-labelledby="proposals-status-tab">
                                    <div class="chart-container">
                                        <canvas id="proposalsBarChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal fade" id="allCSOsModal" tabindex="-1" aria-labelledby="allCSOsLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="allCSOsLabel" style="color: white !important;">All CSOs Ranked by Performance Score</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>CSO Name</th>
                                <th>Chairperson/ President</th>
                                <th>Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rank = 1; ?>
                            <?php foreach ($allCSOs as $cso): ?>
                                <tr>
                                    <td><?= $rank++ ?></td>
                                    <td><?= htmlspecialchars($cso['cso_name']) ?></td>
                                    <td><?= htmlspecialchars($cso['chairperson_name']) ?></td>
                                    <td><?= number_format($cso['performance_score'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                </div>
            </div>
        </div>
        
        <!-- Leaflet CSS -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

        <!-- Leaflet JS -->
        <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

        <!-- Include jQuery, Bootstrap JS, DataTables JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
        <script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap5.min.css">

        <!-- Custom JavaScript -->
        <script>
            $(document).ready(function () {
                // Toggle Sidebar
                $('#sidebarCollapse').on('click', function () {
                    $('#sidebar').toggleClass('active');
                    $('#content').toggleClass('active');
                });

                // Initialize DataTable
                $('#rankingsTable').DataTable({
                    responsive: false,
                    autoWidth: false,
                    scrollX: true,
                    scrollY: true,
                    pageLength: 2,
                    lengthMenu: [1,2],
                    stripeClasses: [],
                    language: {
                        emptyTable: "No CSOs found.",
                        zeroRecords: "No matching records found."
                    }
                });

                // Handle Ranking Weights Form Submission
                $('#rankingWeightsForm').on('submit', function (event) {
                event.preventDefault(); // Prevent form submission

                // Collect form data
                const formData = {
                    weightSolvency: parseFloat($('#weightSolvency').val()),
                    weightLiquidity: parseFloat($('#weightLiquidity').val()),
                    weightROI: parseFloat($('#weightROI').val()),
                    weightCompletionRate: parseFloat($('#weightCompletionRate').val()),
                    weightApprovalRate: parseFloat($('#weightApprovalRate').val()),
                    weightAccuracy: parseFloat($('#weightAccuracy').val()),
                    weightCompliance: parseFloat($('#weightCompliance').val()),
                    weightCommunityEngagement: parseFloat($('#weightCommunityEngagement').val()),
                    weightProjectDelayImpact: parseFloat($('#weightProjectDelayImpact').val()),
                    weightBudgetDeviationImpact: parseFloat($('#weightBudgetDeviationImpact').val())
                };

                // Validate that the total weight equals 1
                const totalWeight = formData.weightSolvency + formData.weightLiquidity + formData.weightROI + formData.weightCompletionRate + formData.weightApprovalRate + formData.weightAccuracy + formData.weightCompliance + formData.weightCommunityEngagement + formData.weightProjectDelayImpact + formData.weightBudgetDeviationImpact;
                if (Math.abs(totalWeight - 1) > 0.0001) {
                    $('#weightsError').text("The total weight must equal 1.");
                    return;
                } else {
                    $('#weightsError').text("");
                }

                // Send data to the PHP backend via AJAX
                $.ajax({
                    url: 'update_weights.php',  // Ensure this path is correct
                    method: 'POST',
                    data: formData,
                    dataType: 'json', // Expect JSON response
                    success: function (response) {
                        if (response.status === 'success') {
                            $('#successAlert').removeClass('d-none').text(response.message);
                            $('#errorAlert').addClass('d-none');
                            // Optionally, reload the page or update the rankings table to reflect new weights
                            setTimeout(() => {
                                $('#weightUpdateModal').modal('hide');
                                location.reload(); // Reload to fetch updated data
                            }, 1500);
                        } else {
                            $('#errorAlert').removeClass('d-none').text(response.message);
                            $('#successAlert').addClass('d-none');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error("AJAX Error:", status, error);
                        console.error("Response Text:", xhr.responseText);
                        $('#errorAlert').removeClass('d-none').text("Error: " + xhr.status + " " + xhr.statusText);
                        $('#successAlert').addClass('d-none');
                    }
                });
            });

                let currentSlide = 0; // Tracks the current slide
                let csoItems = []; // Stores the CSO data fetched via AJAX

                // Fetch initial CSO data
                $.ajax({
                    url: 'fetch_cso_data.php', // Adjust to your backend file
                    method: 'GET',
                    success: function (response) {
                        csoItems = $(response); // Load CSO data
                        showCsoDetails(currentSlide); // Display the first CSO
                        updateComparisonTable($(csoItems[currentSlide]).data('cso-id')); // Update comparison table
                    },
                    error: function () {
                        $('#csoDetails').html('<p>Error fetching CSO data.</p>');
                    },
                });

                // Handle previous slide
                $('#prevCso').click(function () {
                    if (currentSlide > 0) {
                        currentSlide--;
                        showCsoDetails(currentSlide);
                        updateComparisonTable($(csoItems[currentSlide]).data('cso-id'));
                    }
                });

                // Handle next slide
                $('#nextCso').click(function () {
                    if (currentSlide < csoItems.length - 1) {
                        currentSlide++;
                        showCsoDetails(currentSlide);
                        updateComparisonTable($(csoItems[currentSlide]).data('cso-id'));
                    }
                });

                // Function to display the current CSO details
                function showCsoDetails(index) {
                    $('#csoDetails').html($(csoItems[index]));
                }

                // Function to update the comparison table for the current CSO
                function updateComparisonTable(csoId) {
                    $.ajax({
                        url: 'fetch_comparison_data.php', // Adjust to your backend file
                        method: 'POST',
                        data: { csoId: csoId },
                        success: function (response) {
                            $('#comparisonTable').html(response);
                        },
                        error: function () {
                            $('#comparisonTable').html('<p>Error fetching comparison data.</p>');
                        },
                    });
                }
                // Initialize Bootstrap tooltips if used
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
                })
                
                // Handle Edit Scores Button Click
                $('.edit-scores-btn').on('click', function () {
                    // Retrieve CSO data from data attributes
                    var csoId = $(this).data('cso-id');
                    var csoName = $(this).data('cso-name');
                    var accuracy = $(this).data('accuracy');
                    var compliance = $(this).data('compliance');
                    var communityEngagement = $(this).data('community-engagement');
                    
                    // Populate the modal with CSO data
                    $('#csoName').text(csoName);
                    $('#accuracyScore').val(accuracy);
                    $('#complianceScore').val(compliance);
                    $('#communityEngagementScore').val(communityEngagement);
                    $('#csoId').val(csoId);
                    
                    // Reset alerts
                    $('#editSuccessAlert').addClass('d-none');
                    $('#editErrorAlert').addClass('d-none');
                    
                    // Show the modal
                    $('#editScoresModal').modal('show');
                });
                
                // Handle Edit Scores Form Submission
                $('#editScoresForm').on('submit', function (event) {
                    event.preventDefault(); // Prevent default form submission
                    
                    // Collect form data
                    var formData = {
                        csoId: $('#csoId').val(),
                        accuracyScore: $('#accuracyScore').val(),
                        complianceScore: $('#complianceScore').val(),
                        communityEngagementScore: $('#communityEngagementScore').val()
                    };
                    
                    // Validate scores are between 1 and 5
                    if (
                        formData.accuracyScore < 1 || formData.accuracyScore > 5 ||
                        formData.complianceScore < 1 || formData.complianceScore > 5 ||
                        formData.communityEngagementScore < 1 || formData.communityEngagementScore > 5
                    ) {
                        $('#editErrorAlert').removeClass('d-none').text('Scores must be between 1 and 5.');
                        $('#editSuccessAlert').addClass('d-none');
                        return;
                    } else {
                        $('#editErrorAlert').addClass('d-none').text('');
                    }
                    
                    // Send AJAX request to update_scores.php
                    $.ajax({
                        url: 'update_scores.php', // Ensure this path is correct
                        method: 'POST',
                        data: formData,
                        dataType: 'json',
                        success: function (response) {
                            if (response.status === 'success') {
                                $('#editSuccessAlert').removeClass('d-none').text(response.message);
                                $('#editErrorAlert').addClass('d-none');
                                
                                // Update the table row with new scores without reloading
                                var button = $('.edit-scores-btn[data-cso-id="' + formData.csoId + '"]');
                                button.data('accuracy', formData.accuracyScore);
                                button.data('compliance', formData.complianceScore);
                                button.data('community-engagement', formData.communityEngagementScore);
                                
                                // Find the table row and update the score cells
                                var row = button.closest('tr');
                                row.find('td').eq(8).text(formData.accuracyScore); // Accuracy
                                row.find('td').eq(9).text(formData.complianceScore); // Compliance
                                row.find('td').eq(10).text(formData.communityEngagementScore); // Community Engagement
                                
                                // Optionally, recalculate the performance score if necessary
                                
                                // Hide the modal after a short delay
                                setTimeout(function () {
                                    $('#editScoresModal').modal('hide');
                                }, 1000);
                            } else {
                                $('#editErrorAlert').removeClass('d-none').text(response.message);
                                $('#editSuccessAlert').addClass('d-none');
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error("AJAX Error:", status, error);
                            $('#editErrorAlert').removeClass('d-none').text("An error occurred while updating scores.");
                            $('#editSuccessAlert').addClass('d-none');
                        }
                    });
                });
            });
        </script>
        <script>
            var projectsCtx = document.getElementById('projectsChart').getContext('2d');
            var projectsChart = new Chart(projectsCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($projectsChartLabels); ?>,
                    datasets: [{
                        label: ' Projects Handled',
                        data: <?php echo json_encode($projectsChartValues); ?>,
                        backgroundColor: <?php echo json_encode($projectsChartColors); ?>,
                        hoverBackgroundColor: <?php echo json_encode($projectsChartColors); ?>,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        },
                        tooltip: {
                            callbacks: {
                                label: function (tooltipItem, data) {
                                    var dataset = data.datasets[tooltipItem.datasetIndex];
                                    var total = dataset.data.reduce(function (previousValue, currentValue) {
                                        return previousValue + currentValue;
                                    });
                                    var currentValue = dataset.data[tooltipItem.index];
                                    var percentage = Math.floor(((currentValue / total) * 100) + 0.5);
                                    return percentage + '%';
                                }
                            }
                        }
                    }
                }
            });

            var proposalsCtx = document.getElementById('proposalsChart').getContext('2d');
            var proposalsChart = new Chart(proposalsCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode(array_column($chart_data_proposals, 'label')); ?>,
                    datasets: [{
                        label: ' Proposals Endorsed',
                        data: <?php echo json_encode(array_column($chart_data_proposals, 'value')); ?>,
                        backgroundColor: <?php echo json_encode($proposalsChartColors); ?>,
                        hoverBackgroundColor: <?php echo json_encode($proposalsChartColors); ?>,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        },
                        tooltip: {
                            callbacks: {
                                label: function (tooltipItem, data) {
                                    var dataset = data.datasets[tooltipItem.datasetIndex];
                                    var total = dataset.data.reduce(function (previousValue, currentValue) {
                                        return previousValue + currentValue;
                                    });
                                    var currentValue = dataset.data[tooltipItem.index];
                                    var percentage = Math.floor(((currentValue / total) * 100) + 0.5);
                                    return percentage + '%';
                                }
                            }
                        }
                    },
                }
            });

            // Projects Status Bar Chart
            var projectsCtx = document.getElementById('projectsBarChart').getContext('2d');
            var projectsChart = new Chart(projectsCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($projectStatusLabels); ?>, // X-axis values like "Completed", "Pending", etc.
                    datasets: [{
                        data: <?php echo json_encode($projectStatusCounts); ?>, // Counts for each status
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
                    maintainAspectRatio: false,
                    scales: {
                        x: {
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
                            position: 'top', // Position the legend at the top
                            labels: {
                                // The legend now uses the x-axis labels and colors
                                generateLabels: function(chart) {
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

            // Proposals Status Bar Chart
            var proposalsCtx = document.getElementById('proposalsBarChart').getContext('2d');
            var proposalsChart = new Chart(proposalsCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($proposalStatusLabels); ?>, // X-axis values like "Approved", "Pending", etc.
                    datasets: [{
                        data: <?php echo json_encode($proposalStatusCounts); ?>, // Counts for each status
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
                    maintainAspectRatio: false,
                    scales: {
                        x: {
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
                            position: 'top', // Position the legend at the top
                            labels: {
                                // The legend now uses the x-axis labels and colors
                                generateLabels: function(chart) {
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
    var csoNames = <?php echo json_encode(array_column($top_csos, 'cso_name')); ?>;
    var performanceScores = <?php echo json_encode(array_column($top_csos, 'performance_score')); ?>;
    var solvencyScores = <?php echo json_encode(array_column($top_csos, 'solvency')); ?>;
    var liquidityScores = <?php echo json_encode(array_column($top_csos, 'liquidity')); ?>;
    var roiScores = <?php echo json_encode(array_column($top_csos, 'roi')); ?>;
    var completionRates = <?php echo json_encode(array_column($top_csos, 'completion_rate')); ?>;
    var approvalRates = <?php echo json_encode(array_column($top_csos, 'approval_rate')); ?>;
    var accuracyScores = <?php echo json_encode(array_column($top_csos, 'accuracy_score')); ?>;
    var complianceScores = <?php echo json_encode(array_column($top_csos, 'compliance_score')); ?>;
    var communityEngagementScores = <?php echo json_encode(array_column($top_csos, 'community_engagement_score')); ?>;
    var projectDelayImpact = <?php echo json_encode(array_column($top_csos, 'days_over')); ?>;
    var budgetDeviationImpact = <?php echo json_encode(array_column($top_csos, 'budget_over')); ?>;

    var ctx = document.getElementById('rankingsChart').getContext('2d');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: csoNames,
            datasets: [
                {
                    label: 'Solvency',
                    data: solvencyScores,
                    backgroundColor: 'rgba(128, 177, 211, 0.7)',
                    borderColor: 'rgba(128, 177, 211, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Liquidity',
                    data: liquidityScores,
                    backgroundColor: 'rgba(53, 142, 212, 0.7)',
                    borderColor: 'rgba(53, 142, 212, 1)',
                    borderWidth: 1
                },
                {
                    label: 'ROI',
                    data: roiScores,
                    backgroundColor: 'rgba(46, 56, 91, 0.7)',
                    borderColor: 'rgba(46, 56, 91, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Completion Rate',
                    data: completionRates,
                    backgroundColor: 'rgba(155, 188, 109, 0.7)',
                    borderColor: 'rgba(155, 188, 109, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Approval Rate',
                    data: approvalRates,
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Accuracy',
                    data: accuracyScores,
                    backgroundColor: 'rgba(153, 102, 255, 0.7)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Compliance',
                    data: complianceScores,
                    backgroundColor: 'rgba(255, 159, 64, 0.7)',
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Community Engagement',
                    data: communityEngagementScores,
                    backgroundColor: 'rgba(255, 205, 86, 0.7)',
                    borderColor: 'rgba(255, 205, 86, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Project Delay Impact',
                    data: projectDelayImpact,
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Budget Deviation Impact',
                    data: budgetDeviationImpact,
                    backgroundColor: 'rgba(201, 203, 207, 0.7)',
                    borderColor: 'rgba(201, 203, 207, 1)',
                    borderWidth: 1
                },
                {
                    type: 'line',
                    label: 'Performance Score',
                    data: performanceScores,
                    borderColor: 'rgba(0, 0, 0, 1)',
                    backgroundColor: 'rgba(0, 0, 0, 0.1)',
                    borderWidth: 3,
                    tension: 0.3,
                    fill: false,
                    yAxisID: 'y1',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'CSO Name',
                        font: { size: 12, weight: 'bold' }
                    },
                    ticks: {
                        maxRotation: 60,
                        minRotation: 40,
                        autoSkip: false,
                        font: { size: 10 }
                    },
                    stacked: false
                },
                y: {
                    type: 'linear',
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Bar Criteria Values',
                        font: { size: 12, weight: 'bold' }
                    },
                    beginAtZero: true
                },
                y1: {
                    type: 'linear',
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Performance Score',
                        font: { size: 12, weight: 'bold' }
                    },
                    grid: {
                        drawOnChartArea: false
                    },
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 15,
                        font: { size: 9 }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.parsed.y}`;
                        }
                    }
                }
            },
            layout: {
                padding: {
                    right: 40
                }
            }
        }
    });
</script>

        <script>
            const csoLocations = <?php echo json_encode($cso_locations); ?>;
            const projectLocations = <?php echo json_encode($projects_locations); ?>;

            const map = L.map('csoMap').setView([10.792999, 122.442999], 8); // Center on WV Philippines

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            // CSO Red icon
            const redIcon = L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
                iconSize: [20, 30],
                iconAnchor: [10, 30],
                popupAnchor: [0, -30]
            });

            // Project icons by status (you can change colors or icons URLs)
            const projectIcons = {
                'Assigned': L.icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-yellow.png',
                    iconSize: [20, 30],
                    iconAnchor: [10, 30],
                    popupAnchor: [0, -30]
                }),
                'In Progress': L.icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',
                    iconSize: [20, 30],
                    iconAnchor: [10, 30],
                    popupAnchor: [0, -30]
                }),
                'Completed': L.icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png',
                    iconSize: [20, 30],
                    iconAnchor: [10, 30],
                    popupAnchor: [0, -30]
                }),
                // fallback icon if status is unexpected
                'default': L.icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-grey.png',
                    iconSize: [20, 30],
                    iconAnchor: [10, 30],
                    popupAnchor: [0, -30]
                }),
            };

            // Add CSO markers
            csoLocations.forEach(cso => {
                if (cso.latitude && cso.longitude) {
                    L.marker([cso.latitude, cso.longitude], { icon: redIcon })
                        .addTo(map)
                        .bindPopup(`<strong>${cso.cso_name}</strong><br>${cso.cso_address}<br>Chairperson/President: ${cso.chairperson_name}`)
                        .bindTooltip(cso.cso_name, { direction: "top", offset: [0, -10] });
                }
            });

            // Add Project markers with color based on status
            projectLocations.forEach(project => {
                if (project.latitude && project.longitude) {
                    // Use icon matching status or default if unknown
                    const icon = projectIcons[project.status] || projectIcons['default'];
                    L.marker([project.latitude, project.longitude], { icon: icon })
                        .addTo(map)
                        .bindPopup(`<strong>${project.title}</strong><br>Status: ${project.status}<br>CSO: ${project.cso_name}`)
                        .bindTooltip(project.title, { direction: "top", offset: [0, -10] });
                }
            });

            // Add legend control inside the map
    const legend = L.control({ position: 'bottomleft' });

    legend.onAdd = function (map) {
        const div = L.DomUtil.create('div', 'info legend');
        div.style.background = 'white';
        div.style.padding = '8px';
        div.style.borderRadius = '5px';
        div.style.boxShadow = '0 0 15px rgba(0,0,0,0.2)';
        div.style.lineHeight = '20px';
        div.style.fontSize = '10px';

        div.innerHTML += '<strong>Legend</strong><br>';

        // Red pin icon for CSO locations
        div.innerHTML +=
            '<img src="https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png" style="vertical-align:middle; width:10px; height:15px; margin-right:8px;"> CSO Location<br>';

        div.innerHTML += '<i>Project Locations</i><br>';

        // Yellow marker for Assigned
        div.innerHTML +=
            '<img src="https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-yellow.png" style="vertical-align:middle; width:10px; height:15px; margin-right:8px;"> Assigned<br>';

        // Blue marker for In Progress
        div.innerHTML +=
            '<img src="https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png" style="vertical-align:middle; width:10px; height:15px; margin-right:8px;"> In Progress<br>';

        // Green marker for Completed
        div.innerHTML +=
            '<img src="https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png" style="vertical-align:middle; width:10px; height:15px; margin-right:8px;"> Completed<br>';

        // Grey marker for Default/Unknown
        div.innerHTML +=
            '<img src="https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-grey.png" style="vertical-align:middle; width:10px; height:15px; margin-right:8px;"> Other<br>';

        return div;
    };

    legend.addTo(map);
        </script>
        <?php 
        // Close the database connection
        mysqli_close($conn);
        ?>
    </body>
    </html>