<?php
include('include/db_connect.php');

$sql = "SELECT fr.cso_representative_id, 
               cr.first_name, 
               cr.last_name, 
               cr.suffix,
               cr.cso_name,  
               fr.upload_date,
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
            GROUP BY cso_representative_id
        ) AS latest
        INNER JOIN financial_report fr ON latest.cso_representative_id = fr.cso_representative_id
                                        AND latest.max_upload_date = fr.upload_date
        INNER JOIN cso_representative cr ON fr.cso_representative_id = cr.id
        LEFT JOIN financial_report previous ON fr.cso_representative_id = previous.cso_representative_id
                                             AND previous.upload_date < fr.upload_date
        LEFT JOIN thresholds t ON 1 = 1
        ORDER BY fr.cso_representative_id";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $difference_roi = $row['current_roi'] - $row['previous_roi'];
        $difference_liability = $row['current_liability'] - $row['previous_liability'];
        $difference_solvency = $row['current_solvency'] - $row['previous_solvency'];

        echo '<div class="container">';
        echo '<h4><strong>' . $row['cso_name'] . ' </strong></h4>';
        echo '<table class="table table-bordered">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Metrics</th>';
        echo '<th>Previous</th>';
        echo '<th>Current</th>';
        echo '<th>Difference</th>';
        echo '<th>Threshold</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        echo '<tr>';
        echo '<td>ROI (%)</td>';
        echo '<td>' . $row['previous_roi'] . '</td>';
        echo '<td>' . $row['current_roi'] . '</td>';
        echo '<td>' . number_format($difference_roi, 2) . '</td>';
        echo '<td align = center>' . '>=7%</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td>Liquidity</td>';
        echo '<td>' . $row['previous_liability'] . '</td>';
        echo '<td>' . $row['current_liability'] . '</td>';
        echo '<td>' . number_format($difference_liability, 2) . '</td>';
        echo '<td align = center>' . '>1.0</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td>Solvency (%)</td>';
        echo '<td>' . $row['previous_solvency'] . '</td>';
        echo '<td>' . $row['current_solvency'] . '</td>';
        echo '<td>' . number_format($difference_solvency, 2) . '</td>';
        echo '<td align = center>' . '<=60%</td>';
        echo '</tr>';

        if ($difference_roi >= $row['roi_threshold'] &&
            $difference_liability >= $row['liability_threshold'] &&
            $difference_solvency <= $row['solvency_threshold']) {
            $indication_result = '<span class="text-success">Excellent</span>: Significant improvements across all metrics compared to last year.';
        } elseif ($difference_roi >= $row['roi_threshold'] + 0.5 &&
                  $difference_liability >= $row['liability_threshold'] &&
                  $difference_solvency >= $row['solvency_threshold'] - 5) {
            $indication_result = '<span class="text-primary">Very Good</span>: Metrics have improved notably, meeting or exceeding key thresholds.';
        } elseif ($difference_roi >= $row['roi_threshold'] + 0.5 &&
                  $difference_liability >= $row['liability_threshold'] &&
                  $difference_solvency > $row['solvency_threshold']) {
            $indication_result = '<span class="text-info">Good</span>: Positive improvements in key metrics.';
        } elseif ($difference_roi >= $row['roi_threshold'] + 0.2 &&
                  $difference_liability >= $row['liability_threshold'] + 0.2 &&
                  $difference_solvency > $row['solvency_threshold']) {
            $indication_result = '<span class="text-warning">Fair</span>: Metrics have shown some improvement, though more progress is desired.';
        } else {
            $indication_result = '<span class="text-danger">Poor</span>: Metrics have declined or shown minimal improvement compared to last year.';
        }

        echo '<tr><td colspan="5">' . $indication_result . '</td></tr>';

        echo '</tbody></table>';
        echo '</div>';
    }
} else {
    echo '<p>No CSO representative data available.</p>';
}

$conn->close();
?>
