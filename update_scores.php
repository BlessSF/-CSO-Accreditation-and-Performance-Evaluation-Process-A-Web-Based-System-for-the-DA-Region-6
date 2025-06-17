<?php
// update_scores.php

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
    $csoId = isset($_POST['csoId']) ? intval($_POST['csoId']) : null;
    $accuracyScore = isset($_POST['accuracyScore']) ? floatval($_POST['accuracyScore']) : null;
    $complianceScore = isset($_POST['complianceScore']) ? floatval($_POST['complianceScore']) : null;
    $communityEngagementScore = isset($_POST['communityEngagementScore']) ? floatval($_POST['communityEngagementScore']) : null;

    // Validate that all scores are provided and between 1 and 5
    if ($csoId === null || $csoId <= 0) {
        $response['message'] = "Invalid CSO ID.";
        echo json_encode($response);
        exit;
    }

    foreach (['accuracyScore' => $accuracyScore, 'complianceScore' => $complianceScore, 'communityEngagementScore' => $communityEngagementScore] as $key => $value) {
        if ($value === null || $value < 1 || $value > 5) {
            $response['message'] = "Invalid value for {$key}. It must be between 1 and 5.";
            echo json_encode($response);
            exit;
        }
    }

    // Check if the CSO exists in cso_evaluations
    $stmt = $conn->prepare("SELECT id FROM cso_evaluations WHERE cso_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $csoId);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            // Insert a new evaluation record if it doesn't exist
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO cso_evaluations (cso_id, accuracy_score, compliance_score, community_engagement_score) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("iddd", $csoId, $accuracyScore, $complianceScore, $communityEngagementScore);
                if ($stmt->execute()) {
                    $response['status'] = 'success';
                    $response['message'] = 'Scores added successfully.';
                } else {
                    $response['message'] = 'Failed to add scores: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $response['message'] = 'Failed to prepare SQL statement: ' . $conn->error;
            }
        } else {
            // Update existing evaluation record
            $stmt->close();
            $stmt = $conn->prepare("UPDATE cso_evaluations SET accuracy_score = ?, compliance_score = ?, community_engagement_score = ? WHERE cso_id = ?");
            if ($stmt) {
                $stmt->bind_param("dddi", $accuracyScore, $complianceScore, $communityEngagementScore, $csoId);
                if ($stmt->execute()) {
                    $response['status'] = 'success';
                    $response['message'] = 'Scores updated successfully.';
                } else {
                    $response['message'] = 'Failed to update scores: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $response['message'] = 'Failed to prepare SQL statement: ' . $conn->error;
            }
        }
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
