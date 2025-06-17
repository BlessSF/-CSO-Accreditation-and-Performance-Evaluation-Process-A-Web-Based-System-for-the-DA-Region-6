<?php
session_start();
include('include/db_connect.php');
header('Content-Type: application/json');

// Get raw input and decode JSON
$input = json_decode(file_get_contents('php://input'), true);

// Extract values safely
$application_id = isset($input['id']) ? intval($input['id']) : 0;
$status = isset($input['status']) ? trim($input['status']) : '';
$remarks = isset($input['remarks']) ? trim($input['remarks']) : '';
$schedule = isset($input['schedule']) ? trim($input['schedule']) : '';
$documents = isset($input['documents']) ? $input['documents'] : [];
$cso_review = isset($input['cso_review']) ? (bool)$input['cso_review'] : false;
$cso_status = isset($input['cso_status']) ? trim($input['cso_status']) : '';

$valid_statuses = ['Pending', 'Accredited', 'Denied'];
$valid_cso_statuses = ['Pending', 'Approved', 'Denied'];
$valid_doc_statuses = ['Pending', 'Approved', 'Denied'];

// Validate ID
if ($application_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid application ID provided.']);
    exit;
}

$conn->begin_transaction();

try {
    $all_documents_approved = true;

    if (!$cso_review) {
        if (empty($status) || !in_array($status, $valid_statuses)) {
            throw new Exception("Invalid or missing status for admin review. Provided: '$status'");
        }

        $sql = "UPDATE renewal_application SET status = ?, remarks = ?, schedule = ? WHERE application_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception('Database error preparing statement: ' . $conn->error);

        $stmt->bind_param("sssi", $status, $remarks, $schedule, $application_id);
        if (!$stmt->execute()) throw new Exception('Error executing main query: ' . $stmt->error);
    } else {
        if (!in_array($cso_status, $valid_cso_statuses)) {
            throw new Exception("Invalid CSO status value: $cso_status");
        }

        if ($cso_status === 'Approved') {
            $status = 'Pending';
            $sql = "UPDATE renewal_application SET cso_status = ?, status = ?, remarks = ?, schedule = ? WHERE application_id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) throw new Exception('Database error preparing statement: ' . $conn->error);

            $stmt->bind_param("ssssi", $cso_status, $status, $remarks, $schedule, $application_id);
        } else {
            $sql = "UPDATE renewal_application SET cso_status = ?, remarks = ?, schedule = ? WHERE application_id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) throw new Exception('Database error preparing statement: ' . $conn->error);

            $stmt->bind_param("sssi", $cso_status, $remarks, $schedule, $application_id);
        }

        if (!$stmt->execute()) throw new Exception('Error executing main query: ' . $stmt->error);
    }

    foreach ($documents as $field => $data) {
        $doc_status = isset($data['status']) ? trim($data['status']) : 'Pending';
        $doc_remarks = isset($data['remarks']) ? trim($data['remarks']) : 'No remarks';
        $doc_admin_remarks = isset($data['admin_remarks']) ? trim($data['admin_remarks']) : 'No admin remarks';

        if (!in_array($doc_status, $valid_doc_statuses)) {
            throw new Exception("Invalid document status '$doc_status' for field '$field'");
        }

        if ($doc_status !== 'Approved') {
            $all_documents_approved = false;
        }

        $status_col = $field . "_status";
        $remarks_col = $field . "_remarks";
        $admin_remarks_col = $field . "_admin_remarks";

        $sql_doc = "UPDATE renewal_application SET {$status_col} = ?, {$remarks_col} = ?, {$admin_remarks_col} = ? WHERE application_id = ?";
        $stmt_doc = $conn->prepare($sql_doc);
        if (!$stmt_doc) throw new Exception('Error preparing document query: ' . $conn->error);

        $stmt_doc->bind_param("sssi", $doc_status, $doc_remarks, $doc_admin_remarks, $application_id);
        if (!$stmt_doc->execute()) {
            throw new Exception("Error updating document field '$field': " . $stmt_doc->error);
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Data saved successfully.']);
} catch (Exception $e) {
    $conn->rollback();
    error_log("Update for Application Renewal of Accreditation Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>