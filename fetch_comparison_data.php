<?php
include('include/db_connect.php');

if (isset($_POST['cso_id'])) {
    $cso_id = $_POST['cso_id'];

    echo "CSO ID received: " . $cso_id;

    $sql = "SELECT previous.roi AS prev_roi,
                   current.roi AS curr_roi,
                   previous.liability AS prev_liability,
                   current.liability AS curr_liability,
                   previous.solvency AS prev_solvency,
                   current.solvency AS curr_solvency,
                   thresholds.roi_threshold,
                   thresholds.liability_threshold,
                   thresholds.solvency_threshold
            FROM financial_report previous
            JOIN financial_report current ON previous.cso_representative_id = current.cso_representative_id
            JOIN thresholds ON previous.cso_representative_id = thresholds.id
            WHERE previous.cso_representative_id = ?
            AND current.id = (SELECT MAX(id) FROM financial_report WHERE cso_representative_id = ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $cso_id, $cso_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        $roi_diff = $row['curr_roi'] - $row['prev_roi'];
        $liability_diff = $row['curr_liability'] - $row['prev_liability'];
        $solvency_diff = $row['curr_solvency'] - $row['prev_solvency'];

        $roi_status = ($roi_diff >= $row['roi_threshold']) ? 'Above Threshold' : 'Below Threshold';
        $liability_status = ($liability_diff >= $row['liability_threshold']) ? 'Above Threshold' : 'Below Threshold';
        $solvency_status = ($solvency_diff >= $row['solvency_threshold']) ? 'Above Threshold' : 'Below Threshold';

        $output = '<h5>Comparison with Thresholds</h5>';
        $output .= '<table class="table table-bordered">';
        $output .= '<thead>';
        $output .= '<tr>';
        $output .= '<th>Metrics</th>';
        $output .= '<th>Previous</th>';
        $output .= '<th>Current</th>';
        $output .= '<th>Difference</th>';
        $output .= '</tr>';
        $output .= '</thead>';
        $output .= '<tbody>';
        $output .= '<tr>';
        $output .= '<td>ROI</td>';
        $output .= '<td>' . $row['prev_roi'] . '</td>';
        $output .= '<td>' . $row['curr_roi'] . '</td>';
        $output .= '<td>' . $roi_diff . '</td>';
        $output .= '</tr>';
        $output .= '<tr>';
        $output .= '<td>Liability</td>';
        $output .= '<td>' . $row['prev_liability'] . '</td>';
        $output .= '<td>' . $row['curr_liability'] . '</td>';
        $output .= '<td>' . $liability_diff . '</td>';
        $output .= '</tr>';
        $output .= '<tr>';
        $output .= '<td>Solvency</td>';
        $output .= '<td>' . $row['prev_solvency'] . '</td>';
        $output .= '<td>' . $row['curr_solvency'] . '</td>';
        $output .= '<td>' . $solvency_diff . '</td>';
        $output .= '</tr>';
        $output .= '</tbody>';
        $output .= '</table>';
        $output .= '<p><strong>Indication based on the report:</strong><br>';
        $output .= 'ROI: ' . $roi_status . '<br>';
        $output .= 'Liability: ' . $liability_status . '<br>';
        $output .= 'Solvency: ' . $solvency_status . '</p>';

        echo $output;
    } else {
        echo '<p>No comparison data available.</p>';
    }

    $stmt->close();
    $conn->close();
} else {
}
?>
