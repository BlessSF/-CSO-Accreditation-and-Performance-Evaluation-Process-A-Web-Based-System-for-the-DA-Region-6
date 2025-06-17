<?php
include('admin_include/header.php');
include('admin_include/navbar.php');
include('include/db_connect.php');

$message = "";

// Handle announcement creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create') {
    $title = $_POST['title'] ?? null;
    $ann_content = $_POST['ann_content'] ?? null;
    $uploaded_at = date('Y-m-d H:i:s');
    $status = 'Active'; // Set status to Active by default

    // Validate title
    if (empty($title)) {
        $message = "Title is required.";
    } else {
        // Insert into announcements table
        $stmt = $conn->prepare("INSERT INTO announcements (title, ann_content, status, uploaded_at) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssss", $title, $ann_content, $status, $uploaded_at);
            if ($stmt->execute()) {
                $announcement_id = $stmt->insert_id;
                $stmt->close();
                
                // Process each uploaded file
                if (!empty($_FILES['document_file']['name'][0])) {
                    foreach ($_FILES['document_file']['name'] as $key => $fileName) {
                        if (!empty($fileName)) {
                            $fileTmpPath = $_FILES['document_file']['tmp_name'][$key];
                            $filePath = "uploads/" . basename($fileName);

                            // Move file to the 'uploads' directory
                            if (move_uploaded_file($fileTmpPath, $filePath)) {
                                // Insert file data into the files table
                                $stmt_file = $conn->prepare("INSERT INTO files (announcement_id, document_name, file_path, uploaded_at) VALUES (?, ?, ?, ?)");
                                if ($stmt_file) {
                                    $stmt_file->bind_param("isss", $announcement_id, $fileName, $filePath, $uploaded_at);
                                    if (!$stmt_file->execute()) {
                                        $message .= "Error: Could not insert file data into the database. ";
                                    }
                                    $stmt_file->close();
                                } else {
                                    $message .= "Error preparing file insertion statement: " . $conn->error;
                                }
                            } else {
                                $message .= "Error: Could not move the uploaded file ($fileName). ";
                            }
                        }
                    }
                }
                $message .= "Announcement posted successfully!";
            } else {
                $message = "Error executing announcement insertion: " . $conn->error;
            }
        } else {
            $message = "Error preparing announcement insertion statement: " . $conn->error;
        }
    }
}

// Handle announcement update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $announcement_id = $_POST['announcement_id'] ?? null;
    $title = $_POST['edit_title'] ?? null;
    $ann_content = $_POST['edit_ann_content'] ?? null;
    $status = $_POST['edit_status'] ?? 'Active'; // Get status from form or default to 'Active'
    $updated_at = date('Y-m-d H:i:s');

    // Validate data
    if (empty($title) || empty($announcement_id)) {
        $message = "Title and announcement ID are required.";
    } else {
        // Update announcement in the database
        $stmt = $conn->prepare("UPDATE announcements SET title = ?, ann_content = ?, status = ?, updated_at = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("ssssi", $title, $ann_content, $status, $updated_at, $announcement_id);
            if ($stmt->execute()) {
                // Process files to be removed (if any)
                if (isset($_POST['files_to_remove']) && !empty($_POST['files_to_remove'])) {
                    $files_to_remove = explode(',', $_POST['files_to_remove']);
                    foreach ($files_to_remove as $file_id) {
                        // Get file path first
                        $file_query = $conn->prepare("SELECT file_path FROM files WHERE id = ?");
                        if ($file_query) {
                            $file_query->bind_param("i", $file_id);
                            $file_query->execute();
                            $result = $file_query->get_result();
                            if ($row = $result->fetch_assoc()) {
                                // Remove the physical file if it exists
                                $file_path = $row['file_path'];
                                if (file_exists($file_path)) {
                                    unlink($file_path);
                                }
                            }
                            $file_query->close();
                        }
                        
                        // Delete the file record from database
                        $delete_file = $conn->prepare("DELETE FROM files WHERE id = ?");
                        if ($delete_file) {
                            $delete_file->bind_param("i", $file_id);
                            $delete_file->execute();
                            $delete_file->close();
                        }
                    }
                }
                
                // Process each uploaded file (if any new files are being added)
                if (!empty($_FILES['edit_document_file']['name'][0])) {
                    foreach ($_FILES['edit_document_file']['name'] as $key => $fileName) {
                        if (!empty($fileName)) {
                            $fileTmpPath = $_FILES['edit_document_file']['tmp_name'][$key];
                            $filePath = "uploads/" . basename($fileName);

                            // Move file to the 'uploads' directory
                            if (move_uploaded_file($fileTmpPath, $filePath)) {
                                // Insert file data into the files table
                                $stmt_file = $conn->prepare("INSERT INTO files (announcement_id, document_name, file_path, uploaded_at) VALUES (?, ?, ?, ?)");
                                if ($stmt_file) {
                                    $stmt_file->bind_param("isss", $announcement_id, $fileName, $filePath, $updated_at);
                                    if (!$stmt_file->execute()) {
                                        $message .= "Error: Could not insert file data into the database. ";
                                    }
                                    $stmt_file->close();
                                } else {
                                    $message .= "Error preparing file insertion statement: " . $conn->error;
                                }
                            } else {
                                $message .= "Error: Could not move the uploaded file ($fileName). ";
                            }
                        }
                    }
                }
                $message = "Announcement updated successfully!";
            } else {
                $message = "Error executing announcement update: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $message = "Error preparing announcement update statement: " . $conn->error;
        }
    }
}

// Handle announcement deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $announcement_id = $_POST['delete_announcement_id'] ?? null;

    if (empty($announcement_id)) {
        $message = "Announcement ID is required for deletion.";
    } else {
        // First, delete associated files from the database
        $stmt_file = $conn->prepare("DELETE FROM files WHERE announcement_id = ?");
        if ($stmt_file) {
            $stmt_file->bind_param("i", $announcement_id);
            $stmt_file->execute();
            $stmt_file->close();
        }

        // Then delete the announcement
        $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $announcement_id);
            if ($stmt->execute()) {
                $message = "Announcement deleted successfully!";
            } else {
                $message = "Error deleting announcement: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $message = "Error preparing deletion statement: " . $conn->error;
        }
    }
}
?>

<style>
    .container-fluid { 
        padding: 20px; 
    }
    .nav-tabs .nav-link {
        background-color: lightgray; 
        color: #0A593A; 
        padding: 12px 16px;
        font-size: 14px; 
        transition: all 0.3s; 
    }
    .nav-tabs .nav-link.active {
        background-color: #0A593A;
        color: white;
        padding: 14px 18px; 
        font-size: 16px; 
        margin-bottom: -1px; 
        border-bottom: 2px solid white;
    }
    .success {
        color: blue;
        font-weight: bold;
        margin-bottom: 20px;
    }
    th, .btn-customs, .card-title, .modal-header {
        background-color: #0A593A;
        color: white;
    }
    .card-header {
        position: sticky;
        top: 0;
        z-index: 1000; 
        background-color: #0A593A; 
        color: white;
    }
    .yellow-line {
        background-color: rgb(253, 199, 5);
        height: 7px;
        width: 100%;
        z-index: 10;
    }

    h2, h3, label { 
        color: #0A593A; 
        font-weight: bold; 
    }

    .custom-card {
        max-width: 100%;
        height: 400px; 
        overflow-y: auto; 
        display: flex;
        flex-direction: column;
    }
    .img-prof {
        height: 30px;
        width: 30px;
    }
    .full-height-container {
        height: 90vh;
        display: flex;
        flex-direction: column;
    }
    .btn-edit {
        background-color: #FFC107;
        color: #000;
    }
    .btn-delete {
        background-color: #DC3545;
        color: #fff;
    }
    .action-buttons {
        display: flex;
        gap: 5px;
    }
    .alert {
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 4px;
    }
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .btn-customs {
        transition: all 0.3s ease;
    }
    .btn-customs:hover {
        background-color: #074028;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .search-create-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .search-box {
        flex-grow: 1;
        margin-right: 15px;
        max-width: 1100px;
    }
    .modal-footer {
        justify-content: flex-end;
        gap: 10px;
    }
    .btn-custom {
        background-color: #0A593A;
        color: white;
        transition: all 0.3s ease;
    }
    .btn-custom:hover {
        background-color: #074028;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    /* Add this to your existing style section */
    .modal-footer .btn {
        background-color: #0A593A;
        color: white;
        border: none;
        transition: none; /* Remove transition effects */
    }

    .modal-footer .btn:hover {
        background-color: #0A593A; /* Keep the same color on hover */
        transform: none; /* Remove transform effect */
        box-shadow: none; /* Remove shadow effect */
    }

    /* Fix the footer alignment */
    .modal-footer {
        justify-content: flex-end;
        gap: 10px;
    }
    
    /* Style for file removal indicators */
    .file-marked-removal {
        background-color: #ffebee; /* Light red background */
        text-decoration: line-through;
        opacity: 0.7;
    }
</style>

<div class="container-fluid full-height-container">
    <div class="row">
        <div class="col-md-12">
            <h2>Announcements List</h2>
            <div class="yellow-line"></div>
            <br>
            
            <?php if(!empty($message)): ?>
                <div class="alert <?php echo strpos($message, 'successfully') !== false ? 'alert-success' : 'alert-danger'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Search and Create Button in the same row -->
            <div class="search-create-row">
                <div class="search-box">
                    <div class="input-group">
                        <input type="text" name="search_query" placeholder="Search announcements" class="form-control" id="search_query" />
                    </div>
                </div>
                <button type="button" class="btn btn-customs" data-bs-toggle="modal" data-bs-target="#createAnnouncementModal">
                    <i class="fas fa-plus"></i> Create New Announcement
                </button>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow mb-12" style="height: 650px; overflow-y: auto;">
                        <div class="card-body">
                            <div id="announcements-result">
                                <!-- Dynamic announcements will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Announcement Modal -->
<div class="modal fade" id="createAnnouncementModal" tabindex="-1" aria-labelledby="createAnnouncementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createAnnouncementModalLabel">Create New Announcement</h5>
                <button type="button" class="custom-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 2.75%; right: 0; font-size: 24px; color: white; background: transparent; border: none; padding: 0 15px; line-height: 1;">×</button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label for="title" class="form-label font-weight-bold">Title <span style="color:red; font-weight:bold;">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="ann_content" class="form-label font-weight-bold">Content</label>
                        <textarea class="form-control" id="ann_content" name="ann_content" rows="12"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="document_file" class="form-label font-weight-bold">File</label>
                        <input type="file" class="form-control" id="document_file" name="document_file[]" multiple>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="submit" class="btn btn btn-customs mr-2">Post Announcement</button>
                        <button type="button" class="btn" data-bs-dismiss="modal">Cancel</button>   
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Announcement Modal -->
<div class="modal fade" id="editAnnouncementModal" tabindex="-1" aria-labelledby="editAnnouncementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editAnnouncementModalLabel">Edit Announcement</h5>
                <button type="button" class="custom-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 3%; right: 0; font-size: 24px; color: white; background: transparent; border: none; padding: 0 15px; line-height: 1;">×</button>
            </div>
            <div class="modal-body">
                <form id="editAnnouncementForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="announcement_id" id="edit_announcement_id">
                    <input type="hidden" name="files_to_remove" id="files_to_remove">
                    
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">Title <span style="color:red; font-weight:bold;">*</span></label>
                        <input type="text" class="form-control" id="edit_title" name="edit_title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_ann_content" class="form-label">Content</label>
                        <textarea class="form-control" id="edit_ann_content" name="edit_ann_content" rows="5"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Existing Files</label>
                        <div id="existing_files_list">
                            <!-- Files will be displayed here -->
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_document_file" class="form-label">Add File</label>
                        <input type="file" class="form-control" id="edit_document_file" name="edit_document_file[]" multiple>
                    </div>
                                        
                    <div class="modal-footer">
                         <button type="submit" class="btn btn-primary">Save Changes</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Announcement Modal -->
<div class="modal fade" id="deleteAnnouncementModal" tabindex="-1" aria-labelledby="deleteAnnouncementModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteAnnouncementModalLabel">Delete Announcement</h5>
                <button type="button" class="custom-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 7%; right: 0; font-size: 24px; color: white; background: transparent; border: none; padding: 0 15px; line-height: 1;">×</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this announcement? This action cannot be undone.</p>
                <form id="deleteAnnouncementForm" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="delete_announcement_id" id="delete_announcement_id">
                    
                    <div class="modal-footer">
                        <button type="submit" class="btn">Confirm Delete</button>
                        <button type="button" class="btn" data-bs-dismiss="modal">Cancel</button> 
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // An array to track files marked for removal
    let filesToRemove = [];
    
    // Search functionality
    $('#search_query').on('input', function() {
        const query = $(this).val().trim();
        loadAnnouncements(query);
    });

    // Initial load to show all announcements when the page loads
    loadAnnouncements("");
    
    // Function to load announcements
    function loadAnnouncements(query) {
        $.ajax({
            url: 'fetch_announcements.php',
            method: 'GET',
            data: { search_query: query },
            success: function(data) {
                if (data.trim() === '') {
                    $('#announcements-result').html('<p>Cannot find announcement(s).</p>');
                } else {
                    $('#announcements-result').html(data);
                    
                    // Attach event handlers to the edit and delete buttons
                    attachEventHandlers();
                }
            }
        });
    }
    
    // Reset the files to remove when the modal is closed or hidden
    $('#editAnnouncementModal').on('hidden.bs.modal', function() {
        filesToRemove = [];
        $('#files_to_remove').val('');
    });
    
    // Function to attach event handlers
    function attachEventHandlers() {
        // Edit button click event
        $('.edit-announcement').on('click', function() {
            const announcementId = $(this).data('id');
            
            // Reset files to remove array
            filesToRemove = [];
            
            // Fetch announcement data
            $.ajax({
                url: 'get_announcement_data.php',
                method: 'GET',
                data: { id: announcementId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Fill the form with the announcement data
                        $('#edit_announcement_id').val(response.data.id);
                        $('#edit_title').val(response.data.title);
                        $('#edit_ann_content').val(response.data.ann_content);
                        
                        // Populate existing files list
                        if (response.files && response.files.length > 0) {
                            let filesList = '<ul class="list-group">';
                            response.files.forEach(function(file) {
                                filesList += `<li class="list-group-item d-flex justify-content-between align-items-center" id="file-item-${file.id}">
                                                ${file.document_name}
                                                <div>
                                                    <a href="${file.file_path}" target="_blank" class="btn btn-sm btn-info">View</a>
                                                    <button type="button" class="btn btn-sm btn-danger mark-file-removal" data-file-id="${file.id}">
                                                        <i class="fas fa-trash"></i> Remove
                                                    </button>
                                                </div>
                                            </li>`;
                            });
                            filesList += '</ul>';
                            $('#existing_files_list').html(filesList);
                        } else {
                            $('#existing_files_list').html('<p>No files attached to this announcement.</p>');
                        }
                        
                        // Show the edit modal
                        $('#editAnnouncementModal').modal('show');
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error: Could not fetch announcement data.');
                }
            });
        });
        
        // Handle file removal marking (not immediate deletion)
        $(document).on('click', '.mark-file-removal', function() {
            const fileId = $(this).data('file-id');
            const fileItem = $(`#file-item-${fileId}`);
            
            // Check if this file is already marked for removal
            const fileIndex = filesToRemove.indexOf(fileId);
            
            if (fileIndex === -1) {
                // Mark for removal
                filesToRemove.push(fileId);
                fileItem.addClass('file-marked-removal');
                $(this).text('Undo');
                $(this).removeClass('btn-danger').addClass('btn-warning');
            } else {
                // Unmark from removal
                filesToRemove.splice(fileIndex, 1);
                fileItem.removeClass('file-marked-removal');
                $(this).html('<i class="fas fa-trash"></i> Remove');
                $(this).removeClass('btn-warning').addClass('btn-danger');
            }
            
            // Update the hidden input with files to remove
            $('#files_to_remove').val(filesToRemove.join(','));
        });
        
        // Delete button click event
        $('.delete-announcement').on('click', function() {
            const announcementId = $(this).data('id');
            $('#delete_announcement_id').val(announcementId);
            $('#deleteAnnouncementModal').modal('show');
        });
    }
});
</script>

<?php include ('admin_include/script.php'); ?>