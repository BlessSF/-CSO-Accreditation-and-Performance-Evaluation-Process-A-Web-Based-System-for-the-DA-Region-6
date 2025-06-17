<?php
// update_weights.php

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set header for JSON response
header('Content-Type: application/json');

// Include database connection
include('include/db_connect.php'); // Adjust the path as necessary

$response = ['status' => 'error', 'message' => 'Unknown error occurred.'];

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize POST data
    $weightSolvency = isset($_POST['weightSolvency']) ? floatval($_POST['weightSolvency']) : null;
    $weightLiquidity = isset($_POST['weightLiquidity']) ? floatval($_POST['weightLiquidity']) : null;
    $weightROI = isset($_POST['weightROI']) ? floatval($_POST['weightROI']) : null;
    $weightCompletionRate = isset($_POST['weightCompletionRate']) ? floatval($_POST['weightCompletionRate']) : null;
    $weightApprovalRate = isset($_POST['weightApprovalRate']) ? floatval($_POST['weightApprovalRate']) : null;
    $weightAccuracy = isset($_POST['weightAccuracy']) ? floatval($_POST['weightAccuracy']) : null;
    $weightCompliance = isset($_POST['weightCompliance']) ? floatval($_POST['weightCompliance']) : null;
    $weightCommunityEngagement = isset($_POST['weightCommunityEngagement']) ? floatval($_POST['weightCommunityEngagement']) : null;
    $weightProjectDelayImpact = isset($_POST['weightProjectDelayImpact']) ? floatval($_POST['weightProjectDelayImpact']) : 0;
    $weightBudgetDeviationImpact = isset($_POST['weightBudgetDeviationImpact']) ? floatval($_POST['weightBudgetDeviationImpact']) : 0;


    // Validate that all weights are provided and between 0 and 1
    $weights = [
        'weightSolvency' => $weightSolvency,
        'weightLiquidity' => $weightLiquidity,
        'weightROI' => $weightROI,
        'weightCompletionRate' => $weightCompletionRate,
        'weightApprovalRate' => $weightApprovalRate,
        'weightAccuracy' => $weightAccuracy,
        'weightCompliance' => $weightCompliance,
        'weightCommunityEngagement' => $weightCommunityEngagement
    ];

    foreach ($weights as $key => $value) {
        if ($value === null || $value < 0 || $value > 1) {
            $response['message'] = "Invalid value for {$key}. It must be between 0 and 1.";
            echo json_encode($response);
            exit;
        }
    }

    // Validate that the total weight equals 1
    $totalWeight = $weightSolvency + $weightLiquidity + $weightROI + $weightCompletionRate + $weightApprovalRate + $weightAccuracy + $weightCompliance + $weightCommunityEngagement + $weightProjectDelayImpact + $weightBudgetDeviationImpact;
    if (abs($totalWeight - 1) > 0.0001) { // Allowing a small margin for floating point precision
        $response['message'] = "The total weight must equal 1. Currently, it is {$totalWeight}.";
        echo json_encode($response);
        exit;
    }

    // Prepare the SQL statement to update weights
    $stmt = $conn->prepare("UPDATE ranking_weights SET 
        weightSolvency = ?, 
        weightLiquidity = ?, 
        weightROI = ?, 
        weightCompletionRate = ?, 
        weightApprovalRate = ?, 
        weightAccuracy = ?, 
        weightCompliance = ?, 
        weightCommunityEngagement = ?, 
        weightProjectDelayImpact = ?, 
        weightBudgetDeviationImpact = ?
        WHERE id = 1"); // Assuming there's only one row with id=1

    if ($stmt) {
        $stmt->bind_param(
            "dddddddddd", 
            $weightSolvency, 
            $weightLiquidity, 
            $weightROI, 
            $weightCompletionRate, 
            $weightApprovalRate, 
            $weightAccuracy, 
            $weightCompliance, 
            $weightCommunityEngagement, 
            $weightProjectDelayImpact, 
            $weightBudgetDeviationImpact
        );

        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Ranking weights updated successfully.';
        } else {
            $response['message'] = 'Failed to update ranking weights: ' . $stmt->error;
        }

        $stmt->close();
    } else {
        $response['message'] = 'Failed to prepare SQL statement: ' . $conn->error;
    }
} else {
    $response['message'] = 'Invalid request method.';
}

// Close the database connection
$conn->close();

// Return the response as JSON
echo json_encode($response);
?>
