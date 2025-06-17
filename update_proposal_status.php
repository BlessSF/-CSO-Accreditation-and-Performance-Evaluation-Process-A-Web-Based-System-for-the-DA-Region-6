<?php
// update_proposal_status.php

// Include database connection
include('include/db_connect.php');

// Set response headers to JSON
header('Content-Type: application/json');

// Function to log messages
function log_message($message) {
    error_log($message);
}

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize POST data
    $proposalId       = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $newStatus        = isset($_POST['status']) ? trim($_POST['status']) : '';
    $newFundingStatus = isset($_POST['funding_status']) ? trim($_POST['funding_status']) : '';
    $remarks          = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';

    // Validate proposal ID
    if ($proposalId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid proposal ID.']);
        log_message("Invalid proposal ID: " . $proposalId);
        exit;
    }

    // Validate status
    $validStatuses = ['Pending', 'Approved', 'Denied'];
    if (!in_array($newStatus, $validStatuses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid proposal status.']);
        log_message("Invalid status: " . $newStatus);
        exit;
    }

    // Validate funding status
    $validFundingStatuses = ['Pending', 'Approved', 'Denied'];
    if (!in_array($newFundingStatus, $validFundingStatuses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid funding status.']);
        log_message("Invalid funding status: " . $newFundingStatus);
        exit;
    }

    // Begin transaction to ensure atomicity
    $conn->begin_transaction();

    try {
        // 0. Fetch current status of the proposal (for logging only)
        $fetchCurrentStatusSql = "SELECT `status` FROM `proposal` WHERE `id` = ?";
        $stmtFetchStatus = $conn->prepare($fetchCurrentStatusSql);
        if (!$stmtFetchStatus) {
            throw new Exception("Error preparing fetch status statement: " . $conn->error);
        }
        $stmtFetchStatus->bind_param("i", $proposalId);
        if (!$stmtFetchStatus->execute()) {
            throw new Exception("Error executing fetch status statement: " . $stmtFetchStatus->error);
        }
        $resultStatus = $stmtFetchStatus->get_result();
        $currentStatusRow = $resultStatus->fetch_assoc();
        $stmtFetchStatus->close();

        if (!$currentStatusRow) {
            throw new Exception("Proposal not found with ID: " . $proposalId);
        }

        $currentStatus = $currentStatusRow['status'];
        log_message("Current status of proposal ID {$proposalId}: {$currentStatus}");

        // 1. Determine if remarks can be updated
        //    We allow remarks to be updated if:
        //    - The new status is "Pending", OR
        //    - The new status is "Approved" but the new funding status is still "Pending"
        $canUpdateRemarks = ($newStatus === 'Pending' || ($newStatus === 'Approved' && $newFundingStatus === 'Pending'));
        log_message("canUpdateRemarks for proposal ID {$proposalId}: " . ($canUpdateRemarks ? "true" : "false"));

        // 2. Update the proposal record
        if ($canUpdateRemarks) {
            // Update status, funding_status, and remarks
            $updateProposalSql = "
                UPDATE `proposal`
                SET `status` = ?, `funding_status` = ?, `remarks` = ?, `status_updated_at` = NOW()
                WHERE `id` = ?
            ";
            $stmtUpdate = $conn->prepare($updateProposalSql);
            if (!$stmtUpdate) {
                throw new Exception("Error preparing update proposal statement: " . $conn->error);
            }
            $stmtUpdate->bind_param("sssi", $newStatus, $newFundingStatus, $remarks, $proposalId);
        } else {
            // Update only status/funding_status, no remarks
            $updateProposalSql = "
                UPDATE `proposal`
                SET `status` = ?, `funding_status` = ?, `status_updated_at` = NOW()
                WHERE `id` = ?
            ";
            $stmtUpdate = $conn->prepare($updateProposalSql);
            if (!$stmtUpdate) {
                throw new Exception("Error preparing update proposal statement: " . $conn->error);
            }
            $stmtUpdate->bind_param("ssi", $newStatus, $newFundingStatus, $proposalId);
        }

        if (!$stmtUpdate->execute()) {
            throw new Exception("Error executing update proposal statement: " . $stmtUpdate->error);
        }
        $stmtUpdate->close();

        log_message("Updated proposal ID: {$proposalId} with status: {$newStatus}, funding_status: {$newFundingStatus}"
                    . ($canUpdateRemarks ? " and remarks: {$remarks}" : ""));

        // 3. If both proposal and funding status are Approved, copy data into projects
        if ($newStatus === 'Approved' && $newFundingStatus === 'Approved') {
            // 3a. Fetch the proposal details
            $fetchProposalSql = "SELECT * FROM `proposal` WHERE `id` = ?";
            $stmtFetch = $conn->prepare($fetchProposalSql);
            if (!$stmtFetch) {
                throw new Exception("Error preparing fetch proposal statement: " . $conn->error);
            }
            $stmtFetch->bind_param("i", $proposalId);
            if (!$stmtFetch->execute()) {
                throw new Exception("Error executing fetch proposal statement: " . $stmtFetch->error);
            }
            $resultFetch = $stmtFetch->get_result();
            $proposal = $resultFetch->fetch_assoc();
            $stmtFetch->close();

            if (!$proposal) {
                throw new Exception("Proposal not found with ID: " . $proposalId);
            }

            // 3b. Fetch cso_chairperson.id (cso_id) from the cso_representative's cso_name
            $csoRepresentativeId = $proposal['cso_representative_id'];
            $fetchCsoNameSql = "SELECT `cso_name` FROM `cso_representative` WHERE `id` = ?";
            $stmtCsoName = $conn->prepare($fetchCsoNameSql);
            if (!$stmtCsoName) {
                throw new Exception("Error preparing fetch cso_name statement: " . $conn->error);
            }
            $stmtCsoName->bind_param("i", $csoRepresentativeId);
            if (!$stmtCsoName->execute()) {
                throw new Exception("Error executing fetch cso_name statement: " . $stmtCsoName->error);
            }
            $resultCsoName = $stmtCsoName->get_result();
            $csoRep = $resultCsoName->fetch_assoc();
            $stmtCsoName->close();

            if (!$csoRep) {
                throw new Exception("CSO Representative not found with ID: " . $csoRepresentativeId);
            }

            $csoName = $csoRep['cso_name'];
            log_message("cso_name: " . $csoName);

            // 3c. Get the actual cso_id from cso_chairperson
            $fetchChairpersonSql = "SELECT `id` FROM `cso_chairperson` WHERE `cso_name` = ?";
            $stmtChair = $conn->prepare($fetchChairpersonSql);
            if (!$stmtChair) {
                throw new Exception("Error preparing fetch chairperson statement: " . $conn->error);
            }
            $stmtChair->bind_param("s", $csoName);
            if (!$stmtChair->execute()) {
                throw new Exception("Error executing fetch chairperson statement: " . $stmtChair->error);
            }
            $resultChair = $stmtChair->get_result();
            $chairperson = $resultChair->fetch_assoc();
            $stmtChair->close();

            if (!$chairperson) {
                throw new Exception("Chairperson not found for CSO Name: " . $csoName);
            }

            $chairpersonId = $chairperson['id'];
            log_message("Chairperson ID: " . $chairpersonId);

            // 3d. Check if a project with the same title & cso_id already exists
            $checkProjectSql = "SELECT COUNT(*) AS count FROM `projects` WHERE `title` = ? AND `cso_id` = ?";
            $stmtCheck = $conn->prepare($checkProjectSql);
            if (!$stmtCheck) {
                throw new Exception("Error preparing check project statement: " . $conn->error);
            }
            $stmtCheck->bind_param("si", $proposal['title'], $chairpersonId);
            if (!$stmtCheck->execute()) {
                throw new Exception("Error executing check project statement: " . $stmtCheck->error);
            }
            $resultCheck = $stmtCheck->get_result();
            $rowCheck = $resultCheck->fetch_assoc();
            $stmtCheck->close();

            log_message("Existing projects with title '{$proposal['title']}' and cso_id '{$chairpersonId}': " . $rowCheck['count']);

            if ($rowCheck['count'] > 0) {
                // Already exists, skip insertion
                log_message("Project already exists for proposal ID: " . $proposalId);
            } else {
                // 3e. Calculate duration (end_date - start_date)
                $startDate = $proposal['start_date'];
                $endDate   = $proposal['end_date'];
                $duration  = 0;
                if (!empty($startDate) && !empty($endDate)) {
                    $start = new DateTime($startDate);
                    $end   = new DateTime($endDate);
                    $duration = $start->diff($end)->days;
                }
                log_message("Calculated duration: " . $duration);

                // 3f. Insert into projects with all your additional fields
                // Make sure these columns exist in your `projects` table!
                // e.g. outcomes, milestones, risks, team, date_submitted, proposal_status, funding_status,
                //      objectives, location, latitude, longitude
                $insertProjectSql = "
                    INSERT INTO `projects` (
                        `title`, 
                        `project_desc`, 
                        `budget`, 
                        `file_path`,
                        `created_at`,
                        `cso_id`,
                        `start_date`,
                        `end_date`,
                        `duration`,
                        `objectives`,
                        `location`,
                        `latitude`,
                        `longitude`,
                        `outcomes`,
                        `milestones`,
                        `risks`,
                        `team`,
                        `date_submitted`,
                        `proposal_status`,
                        `funding_status`,
                        `status`
                    ) VALUES (
                        ?, ?, ?, ?,
                        NOW(),
                        ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?,
                        'Assigned'
                    )
                ";

                $stmtInsert = $conn->prepare($insertProjectSql);
                if (!$stmtInsert) {
                    throw new Exception("Error preparing insert project statement: " . $conn->error);
                }

                // Convert lat/long to float (in case they're null or empty)
                $latitudeVal  = floatval($proposal['latitude'] ?? 0);
                $longitudeVal = floatval($proposal['longitude'] ?? 0);

                // Bind parameters:
                // 1  s: title (p.title)
                // 2  s: project_desc (p.content)
                // 3  d: budget (p.budget)
                // 4  s: file_path (p.file_path)
                // 5  i: cso_id (chairpersonId)
                // 6  s: start_date (p.start_date)
                // 7  s: end_date (p.end_date)
                // 8  i: duration
                // 9  s: objectives (p.objectives)
                // 10 s: location (p.location)
                // 11 d: latitude
                // 12 d: longitude
                // 13 s: outcomes (p.outcomes)
                // 14 s: milestones (p.milestones)
                // 15 s: risks (p.risks)
                // 16 s: team (p.team)
                // 17 s: date_submitted (p.date_submitted)
                // 18 s: proposal_status (p.status)
                // 19 s: funding_status (p.funding_status)
                $paramString = "ssdsississddsssssss";

                $stmtInsert->bind_param(
                    $paramString,
                    $proposal['title'],
                    $proposal['content'],      // or $proposal['project_desc'] if you use that column
                    $proposal['budget'],
                    $proposal['file_path'],
                    $chairpersonId,
                    $proposal['start_date'],
                    $proposal['end_date'],
                    $duration,
                    $proposal['objectives'],
                    $proposal['location'],
                    $latitudeVal,
                    $longitudeVal,
                    $proposal['outcomes'],
                    $proposal['milestones'],
                    $proposal['risks'],
                    $proposal['team'],
                    $proposal['date_submitted'],
                    $proposal['status'],       // or $newStatus
                    $proposal['funding_status'] // or $newFundingStatus
                );

                if (!$stmtInsert->execute()) {
                    throw new Exception("Error executing insert project statement: " . $stmtInsert->error);
                }
                $newProjectId = $stmtInsert->insert_id;
                $stmtInsert->close();

                log_message("Inserted new project ID: " . $newProjectId);

                // 3g. Insert into project_cso table
                $insertProjectCsoSql = "INSERT INTO `project_cso` (`project_id`, `cso_id`) VALUES (?, ?)";
                $stmtInsertCso = $conn->prepare($insertProjectCsoSql);
                if (!$stmtInsertCso) {
                    throw new Exception("Error preparing insert project_cso statement: " . $conn->error);
                }
                $stmtInsertCso->bind_param("ii", $newProjectId, $chairpersonId);
                if (!$stmtInsertCso->execute()) {
                    throw new Exception("Error executing insert project_cso statement: " . $stmtInsertCso->error);
                }
                $stmtInsertCso->close();

                log_message("Associated project ID {$newProjectId} with CSO ID {$chairpersonId}");
            }
        }

        // Commit transaction
        $conn->commit();

        // Respond with success
        echo json_encode(['success' => 'Proposal status updated successfully.']);
        log_message("Proposal ID {$proposalId} updated successfully.");
    } catch (Exception $e) {
        // Rollback transaction in case of error
        $conn->rollback();

        log_message("Exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred while updating the proposal: ' . $e->getMessage()]);
    }
} else {
    // Invalid request method
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Invalid request method.']);
}
?>
