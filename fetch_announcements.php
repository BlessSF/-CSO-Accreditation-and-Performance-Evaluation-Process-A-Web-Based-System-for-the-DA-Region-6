<?php
include('include/db_connect.php');

// Retrieve search query, if provided
$search_query = isset($_GET['search_query']) ? $_GET['search_query'] : '';

// SQL query to fetch announcements and associated files
$sql = "
    SELECT announcements.id AS announcement_id, announcements.title, announcements.ann_content, 
           announcements.status,
           DATE(announcements.uploaded_at) AS uploaded_date, 
           TIME(announcements.uploaded_at) AS uploaded_time,
           DATE(announcements.updated_at) AS updated_date,
           TIME(announcements.updated_at) AS updated_time,
           files.document_name, files.file_path
    FROM announcements
    LEFT JOIN files ON announcements.id = files.announcement_id
";

// Add search conditions if there's a search query
if ($search_query !== '') {
    $sql .= " WHERE (announcements.title LIKE ? OR announcements.ann_content LIKE ? OR files.document_name LIKE ?)";
} else {
    // By default, only show Active announcements or where status is NULL (for backward compatibility)
    $sql .= " WHERE (announcements.status = 'Active' OR announcements.status IS NULL)";
}

// Order by latest upload date
$sql .= " ORDER BY announcements.uploaded_at DESC";

// Prepare and bind parameters if there's a search query
if ($search_query !== '') {
    $stmt = $conn->prepare($sql);
    $search_param = "%" . $search_query . "%";
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
} else {
    $stmt = $conn->prepare($sql);
}

// Execute and fetch results
$stmt->execute();
$result = $stmt->get_result();

// Initialize variables to track and structure announcements
$current_announcement = null;
$announcements = [];

while ($row = $result->fetch_assoc()) {
    $announcement_id = $row['announcement_id'];

    // Check if a new announcement needs to be created
    if ($current_announcement !== $announcement_id) {
        $current_announcement = $announcement_id;

        // Create a new entry in the announcements array
        $announcements[$announcement_id] = [
            'id' => $announcement_id,
            'title' => $row['title'],
            'ann_content' => $row['ann_content'],
            'status' => $row['status'] ?? 'Active', // Default to 'Active' if NULL
            'uploaded_date' => $row['uploaded_date'],
            'uploaded_time' => $row['uploaded_time'],
            'updated_date' => $row['updated_date'],
            'updated_time' => $row['updated_time'],
            'files' => []
        ];
    }

    // Add file information to the announcement if it exists
    if (!empty($row['document_name']) && !empty($row['file_path'])) {
        $announcements[$announcement_id]['files'][] = [
            'document_name' => $row['document_name'],
            'file_path' => $row['file_path']
        ];
    }
}

$stmt->close();
$conn->close();

// Output HTML structure
foreach ($announcements as $announcement) {
    ?>
    <div class="post-card mb-4" style="border-radius: 10px; border: 1px solid #ccc; padding: 15px;">
        <div class="post-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <img class="img-prof rounded-circle" src="img/logo.png" alt="Logo" style="margin-right: 10px;">
                <div style="display: flex; flex-direction: column; line-height: 1.25;">
                    <b style="margin: 0;">Department of Agriculture</b>
                    <i style="font-size: 11px; margin: 0;">
                        <?php echo htmlspecialchars($announcement['uploaded_date']); ?> 
                        <span><?php echo htmlspecialchars($announcement['uploaded_time']); ?></span>
                        <?php if (!empty($announcement['updated_time'])): ?>
                            | <span>Updated at: <?php echo htmlspecialchars($announcement['updated_time']); ?></span>
                        <?php endif; ?>
                    </i>
                </div>
            </div>
            <div class="action-buttons">
                <button class="btn btn-sm edit-announcement" data-id="<?php echo $announcement['id']; ?>" style="background-color: #FFC107; color: #000;">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn btn-sm btn-delete delete-announcement" data-id="<?php echo $announcement['id']; ?>" style="background-color: #DC3545; color: #fff;">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </div>
        <div class="post-content" style="margin-top: 10px;">
            <hr>
            <?php if (!empty($announcement['title'])): ?>
                <h5><strong style="color: #0A593A;"><?php echo htmlspecialchars($announcement['title']); ?></strong></h5>
            <?php endif; ?>

            <?php if (!empty($announcement['ann_content'])): ?>
                <p><?php echo nl2br(htmlspecialchars($announcement['ann_content'])); ?></p>
            <?php endif; ?>

            <?php if (!empty($announcement['files'])): ?>
                <div class="file-list">
                    <?php foreach ($announcement['files'] as $file): ?>
                        <a href="<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank">
                            <i class="fas fa-file-pdf" style="color: maroon; margin-right: 5px;"></i>
                            <?php echo htmlspecialchars($file['document_name']); ?>
                        </a><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

// If no announcements were found
if (empty($announcements)) {
    echo "<p>Cannot find announcement(s).</p>";
}
?>