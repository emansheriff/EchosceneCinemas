<?php
// Handle movie deletion
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $movie_id = $_GET['delete_id'];
    
    // Check if movie exists
    $check_query = "SELECT * FROM movies WHERE id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param('i', $movie_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Delete movie
        $delete_query = "DELETE FROM movies WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param('i', $movie_id);
        
        if ($stmt->execute()) {
            $success_message = "Movie deleted successfully!";
        } else {
            $error_message = "Error deleting movie: " . $conn->error;
        }
    } else {
        $error_message = "Movie not found!";
    }
}

// Get all movies
$query = "SELECT * FROM movies ORDER BY release_date DESC";
$result = mysqli_query($conn, $query);
?>

<!-- Admin Movies Page -->
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title mb-0">Manage Movies</h1>
        <div>
            <a href="index.php?page=admin_dashboard" class="btn btn-outline-light mr-2">
                <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
            </a>
            <a href="index.php?page=admin_movie_add" class="btn btn-outline-light">
                <i class="fas fa-plus mr-1"></i> Add New Movie
            </a>
        </div>
    </div>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>
    
    <!-- Movies Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Genre</th>
                            <th>Duration</th>
                            <th>Release Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($movie = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo $movie['id']; ?></td>
                                    <td>
                                        <img src="<?php echo $movie['image']; ?>" alt="<?php echo $movie['title']; ?>" 
                                             class="img-thumbnail" style="width: 50px; height: 70px; object-fit: cover;">
                                    </td>
                                    <td><?php echo $movie['title']; ?></td>
                                    <td><?php echo $movie['genre']; ?></td>
                                    <td><?php echo $movie['duration']; ?> min</td>
                                    <td><?php echo date('M d, Y', strtotime($movie['release_date'])); ?></td>
                                    <td>
                                        <?php if ($movie['status'] == 'Now Showing'): ?>
                                            <span class="badge badge-success">Now Showing</span>
                                        <?php elseif ($movie['status'] == 'Coming Soon'): ?>
                                            <span class="badge badge-warning">Coming Soon</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Archived</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="index.php?page=admin_movie_edit&id=<?php echo $movie['id']; ?>" 
                                               class="btn btn-sm btn-outline-light" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="index.php?page=admin_showtimes&movie_id=<?php echo $movie['id']; ?>" 
                                               class="btn btn-sm btn-outline-light" title="Showtimes">
                                                <i class="fas fa-clock"></i>
                                            </a>
                                            <a href="#" class="btn btn-sm btn-outline-danger" title="Delete" 
                                               onclick="confirmDelete(<?php echo $movie['id']; ?>, '<?php echo addslashes($movie['title']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No movies found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content bg-dark text-light">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="close text-light" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete the movie "<span id="movieTitle"></span>"?
                <p class="text-danger mt-2">This action cannot be undone!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-light" data-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }
    
    .table {
        color: var(--text-light);
    }
    
    .table thead th {
        border-top: none;
        border-bottom: 2px solid rgba(255, 255, 255, 0.1);
    }
    
    .table td, .table th {
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        vertical-align: middle;
    }
    
    .badge {
        padding: 0.4em 0.6em;
        font-size: 85%;
    }
    
    .img-thumbnail {
        background-color: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .btn-outline-danger {
        color: #dc3545;
        border-color: #dc3545;
    }
    
    .btn-outline-danger:hover {
        color: #fff;
        background-color: #dc3545;
        border-color: #dc3545;
    }
    
    .modal-content {
        background: rgba(0, 0, 0, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }
    
    .modal-header, .modal-footer {
        border-color: rgba(255, 255, 255, 0.1);
    }
</style>

<script>
    function confirmDelete(id, title) {
        document.getElementById('movieTitle').textContent = title;
        document.getElementById('confirmDeleteBtn').href = 'index.php?page=admin_movies&delete_id=' + id;
        $('#deleteModal').modal('show');
    }
</script>