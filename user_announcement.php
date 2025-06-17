<?php
session_start();
include ('user_include/header.php');
include ('user_include/navbar.php');
include ('include/db_connect.php'); 

// Initialize filter and sort parameters (they'll be used by JavaScript)
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'desc';
?>

<style>
    .container-fluid { 
        padding: 20px; 
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
    }
    h2, h3, label { 
        color: #0A593A; 
        font-weight: bold; 
    }
    .img-prof {
        height: 30px;
        width: 30px;
    }
    /* Styling for filter and sort labels */
    label {
        color: #0A593A;
        font-weight: bold;
        margin-right: 5px;
    }
    /* Pagination Styling */
    .pagination .page-item.active .page-link {
        background-color: #0A593A;
        border-color: #0A593A;
        color: white;
    }
    .pagination .page-link {
        color: #0A593A;
    }
    .full-height-container {
        height: 90vh; 
        display: flex;
        flex-direction: column;
    }
    /* Search box styling */
    .search-filter-container {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
    }
    .search-box {
        flex-grow: 1;
        margin-right: 15px;
        max-width: 1100px;
    }
    .filter-sort-container {
        display: flex;
        gap: 10px;
    }
    .announcement-title {
        color: #0A593A;
        font-weight: bold;
    }
</style>

<div id="wrapper">
    <div class="container-fluid full-height-container">
        <div class="row">
            <div class="col-md-12">
                <h2>Announcements</h2>
            </div>
        </div>
        <div class="yellow-line"></div>
        <br>

        <!-- Search, Filter and Sort Options -->
        <div class="search-filter-container">
            <div class="search-box">
                <div class="input-group">
                    <input type="text" name="search_query" id="search_query" placeholder="Search announcements" class="form-control" />
                </div>
            </div>
            <div class="filter-sort-container">
                <div class="me-2">
                    <label for="filter">Show:</label>
                    <select name="filter" id="filter" class="form-select">
                        <option value="all" <?php if ($filter == 'all') echo 'selected'; ?>>All Announcements</option>
                        <option value="with_file" <?php if ($filter == 'with_file') echo 'selected'; ?>>With Attachments</option>
                        <option value="without_file" <?php if ($filter == 'without_file') echo 'selected'; ?>>Without Attachments</option>
                    </select>
                </div>
                <div>
                    <label for="sort">Sort by:</label>
                    <select name="sort" id="sort" class="form-select">
                        <option value="desc" <?php if ($sort == 'desc') echo 'selected'; ?>>Newest</option>
                        <option value="asc" <?php if ($sort == 'asc') echo 'selected'; ?>>Oldest</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Announcements Display with Pagination -->
        <div class="card shadow mb-12" style="height: 570px; overflow-y: auto;">
            <div class="card-body">
                <div id="announcements-container">
                    <!-- Announcements will be loaded here via AJAX -->
                </div>
            </div>
        </div>
        <br>
        
        <!-- Pagination will be loaded with announcements via AJAX -->
        <div id="pagination-container"></div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script>
$(document).ready(function() {
    // Variables to track current state
    let currentPage = 1;
    let currentQuery = '';
    let currentFilter = $('#filter').val();
    let currentSort = $('#sort').val();
    
    // Initial load of announcements
    loadAnnouncements();
    
    // Function to load announcements via AJAX
    function loadAnnouncements() {
        $.ajax({
            url: 'fetch_user_announcements.php',
            method: 'GET',
            data: { 
                search_query: currentQuery,
                filter: currentFilter,
                sort: currentSort,
                page: currentPage
            },
            success: function(data) {
                $('#announcements-container').html(data);
            }
        });
    }
    
    // Search input event handler
    $('#search_query').on('input', function() {
        currentQuery = $(this).val().trim();
        currentPage = 1; // Reset to first page on new search
        loadAnnouncements();
    });
    
    // Filter change event handler
    $('#filter').on('change', function() {
        currentFilter = $(this).val();
        currentPage = 1; // Reset to first page on filter change
        loadAnnouncements();
    });
    
    // Sort change event handler
    $('#sort').on('change', function() {
        currentSort = $(this).val();
        currentPage = 1; // Reset to first page on sort change
        loadAnnouncements();
    });
    
    // Function to handle pagination
    window.loadPage = function(page) {
        currentPage = page;
        loadAnnouncements();
    };
});
</script>

<?php
include ('user_include/script.php');
?>