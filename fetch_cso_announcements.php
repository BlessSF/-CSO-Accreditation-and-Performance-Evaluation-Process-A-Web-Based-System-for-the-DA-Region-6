<?php
session_start();
include ('include/db_connect.php');

// Get search parameters
$search_query = isset($_GET['search_query']) ? $_GET['search_query'] : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'desc';
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$limit = 3; // Show 3 announcements per page
$offset = ($page - 1) * $limit;

// Base SQL query for filtering, sorting, and searching
$sql = "
    SELECT announcements.id, announcements.title, announcements.ann_content, 
           DATE(announcements.uploaded_at) AS uploaded_date, 
           TIME(announcements.uploaded_at) AS uploaded_time,
           DATE(announcements.updated_at) AS updated_date,
           TIME(announcements.updated_at) AS updated_time,
           files.document_name, files.file_path
    FROM announcements
    LEFT JOIN files ON announcements.id = files.announcement_id
    WHERE announcements.status = 'Active'";

// Add search condition if there's a search query
if ($search_query !== '') {
    $sql .= " AND (announcements.title LIKE ? OR announcements.ann_content LIKE ? OR files.document_name LIKE ?)";
}

// Apply filter
if ($filter == 'with_file') {
    $sql .= " AND files.file_path IS NOT NULL";
} elseif ($filter == 'without_file') {
    $sql .= " AND files.file_path IS NULL";
}

// Apply sorting
$sql .= " ORDER BY announcements.uploaded_at $sort";

// Add limit and offset for pagination
$sql .= " LIMIT $limit OFFSET $offset";

// Prepare and execute statement
if ($search_query !== '') {
    $stmt = $conn->prepare($sql);
    $search_param = "%" . $search_query . "%";
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $stmt->execute();
    $result_documents = $stmt->get_result();
} else {
    $result_documents = $conn->query($sql);
}

// Count total records for pagination - adjust to include search condition
$sql_count = "SELECT COUNT(DISTINCT announcements.id) AS total FROM announcements 
              LEFT JOIN files ON announcements.id = files.announcement_id
              WHERE announcements.status = 'Active'";

// Add search condition to count query if there's a search query
if ($search_query !== '') {
    $sql_count .= " AND (announcements.title LIKE ? OR announcements.ann_content LIKE ? OR files.document_name LIKE ?)";
    
    // Apply filter to count query as well
    if ($filter == 'with_file') {
        $sql_count .= " AND files.file_path IS NOT NULL";
    } elseif ($filter == 'without_file') {
        $sql_count .= " AND files.file_path IS NULL";
    }
    
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->bind_param("sss", $search_param, $search_param, $search_param);
    $stmt_count->execute();
    $total_result = $stmt_count->get_result();
} else {
    // Apply filter to count query
    if ($filter == 'with_file') {
        $sql_count .= " AND files.file_path IS NOT NULL";
    } elseif ($filter == 'without_file') {
        $sql_count .= " AND files.file_path IS NULL";
    }
    
    $total_result = $conn->query($sql_count);
}

$total_records = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Initialize arrays to track and structure announcements
$announcements = [];
$current_announcement = null;

while ($row = $result_documents->fetch_assoc()) {
    $announcement_id = $row['id'];

    // Check if a new announcement needs to be created
    if (!isset($announcements[$announcement_id])) {
        // Create a new entry in the announcements array
        $announcements[$announcement_id] = [
            'id' => $announcement_id,
            'title' => $row['title'],
            'ann_content' => $row['ann_content'],
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

// Display announcements
if (!empty($announcements)) {
    foreach ($announcements as $announcement) {
        ?>
        <div class="post-card mb-4" style="border-radius: 10px; border: 1px solid #ccc; padding: 15px;">
            <div class="post-header d-flex align-items-center">
                <img class="img-prof rounded-circle" src="img/logo.png" alt="Logo" style="margin-right: 10px;">
                <div style="display: flex; flex-direction: column; line-height: 1.25;">
                    <b style="margin: 0;">Department of Agriculture</b>
                    <i style="font-size: 11px; margin: 0;">
                        <?php echo htmlspecialchars($announcement['uploaded_date']); ?> 
                        <span><?php echo htmlspecialchars($announcement['uploaded_time']); ?></span>
                        <?php if (!empty($announcement['updated_date']) && !empty($announcement['updated_time'])): ?>
                            | <span>Updated: <?php echo htmlspecialchars($announcement['updated_date']); ?> at <?php echo htmlspecialchars($announcement['updated_time']); ?></span>
                        <?php endif; ?>
                    </i>
                </div>
            </div>
            <hr>
            <div class="post-content" style="margin-top: 10px;">
                <?php if (!empty($announcement['title'])): ?>
                    <h5><strong class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></strong></h5>
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
} else {
    echo '<p>No announcements found.</p>';
}

// Include pagination buttons
if ($total_pages > 1) {
    ?>
    <nav aria-label="Page navigation example">
        <ul class="pagination justify-content-center">
            <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                <a class="page-link" href="javascript:void(0)" onclick="loadPage(<?php echo $page - 1; ?>)" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php if ($page == $i) echo 'active'; ?>">
                    <a class="page-link" href="javascript:void(0)" onclick="loadPage(<?php echo $i; ?>)"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?php if ($page >= $total_pages) echo 'disabled'; ?>">
                <a class="page-link" href="javascript:void(0)" onclick="loadPage(<?php echo $page + 1; ?>)" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        </ul>
    </nav>
<?php
}
?>