<?php
// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: index.php?page=login&error=admin_required');
    exit;
}

// Get movie ID from URL if provided
$movie_id = $_GET['movie_id'] ?? 0;

// Handle showtime deletion
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $showtime_id = $_GET['delete_id'];
    
    // Check if showtime exists
    $check_query = "SELECT * FROM showtimes WHERE id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param('i', $showtime_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Delete showtime
        $delete_query = "DELETE FROM showtimes WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param('i', $showtime_id);
        
        if ($stmt->execute()) {
            $success_message = "Showtime deleted successfully!";
        } else {
            $error_message = "Error deleting showtime: " . $conn->error;
        }
    } else {
        $error_message = "Showtime not found!";
    }
}

// Get movie details if movie_id is provided
$movie = null;
if ($movie_id) {
    $movie_query = "SELECT * FROM movies WHERE id = ?";
    $stmt = $conn->prepare($movie_query);
    $stmt->bind_param('i', $movie_id);
    $stmt->execute();
    $movie_result = $stmt->get_result();
    
    if ($movie_result->num_rows > 0) {
        $movie = $movie_result->fetch_assoc();
    }
}

// Get showtimes
if ($movie_id) {
    $query = "SELECT s.*, m.title as movie_title, t.name as theater_name 
              FROM showtimes s 
              JOIN movies m ON s.movie_id = m.id 
              JOIN theaters t ON s.theater_id = t.id 
              WHERE s.movie_id = ? 
              ORDER BY s.show_date DESC, s.show_time ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $movie_id);
} else {
    $query = "SELECT s.*, m.title as movie_title, t.name as theater_name 
              FROM showtimes s 
              JOIN movies m ON s.movie_id = m.id 
              JOIN theaters t ON s.theater_id = t.id 
              ORDER BY s.show_date DESC, s.show_time ASC";
    $stmt = $conn->prepare($query);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!-- Admin Showtimes Page -->
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <?php if ($movie): ?>
            <h1 class="page-title mb-0">Showtimes for "<?php echo $movie['title']; ?>"</h1>
        <?php else: ?>
            <h1 class="page-title mb-0">Manage Showtimes</h1>
        <?php endif; ?>
        
        <div>
            <a href="index.php?page=admin_dashboard" class="btn btn-outline-light mr-2">
                <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
            </a>
            <?php if ($movie): ?>
                <a href="index.php?page=admin_showtime_add&movie_id=<?php echo $movie_id; ?>" class="btn btn-outline-light">
                    <i class="fas fa-plus mr-1"></i> Add Showtime
                </a>
            <?php else: ?>
                <a href="index.php?page=admin_showtime_add" class="btn btn-outline-light">
                    <i class="fas fa-plus mr-1"></i> Add Showtime
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!$movie_id): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Filter Showtimes</h5>
                <form method="get" action="" class="row align-items-end">
                    <input type="hidden" name="page" value="admin_showtimes">
                    <div class="col-md-4 mb-3">
                        <label for="movie_filter" class="form-label">Movie:</label>
                        <select class="form-control" id="movie_filter" name="movie_id" onchange="this.form.submit()">
                            <option value="">All Movies</option>
                            <?php
                            $movies_query = "SELECT id, title FROM movies ORDER BY title";
                            $movies_result = mysqli_query($conn, $movies_query);
                            while ($movie_option = mysqli_fetch_assoc($movies_result)) {
                                echo '<option value="' . $movie_option['id'] . '">' . $movie_option['title'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Showtimes Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <?php if (!$movie_id): ?>
                                <th>Movie</th>
                            <?php endif; ?>
                            <th>Theater</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($showtime = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo $showtime['id']; ?></td>
                                    <?php if (!$movie_id): ?>
                                        <td><?php echo $showtime['movie_title']; ?></td>
                                    <?php endif; ?>
                                    <td><?php echo $showtime['theater_name']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($showtime['show_date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($showtime['show_time'])); ?></td>
                                    <td><?php echo number_format($showtime['price'], 2); ?> EGP</td>
                                    <td>
                                        <?php if ($showtime['status'] == 'Available'): ?>
                                            <span class="badge bg-success">Available</span>
                                        <?php elseif ($showtime['status'] == 'Full'): ?>
                                            <span class="badge bg-danger">Full</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Cancelled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="#" class="btn btn-sm btn-outline-danger" title="Delete" 
                                               onclick="confirmDelete(<?php echo $showtime['id']; ?>, '<?php echo date('M d, Y', strtotime($showtime['show_date'])); ?> at <?php echo date('h:i A', strtotime($showtime['show_time'])); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo $movie_id ? '7' : '8'; ?>" class="text-center">No showtimes found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-light">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete the showtime on "<span id="showtimeDate"></span>"?
                <p class="text-danger mt-2">This action cannot be undone!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
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
    
    .form-control {
        background: rgba(255, 255, 255, 0.1);
        border: 2px solid rgba(255, 255, 255, 0.3);
        color: var(--text-light);
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .form-control:focus {
        background: rgba(255, 255, 255, 0.2);
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(183, 28, 28, 0.25);
        color: var(--text-light);
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
    function confirmDelete(id, date) {
        document.getElementById('showtimeDate').textContent = date;
        document.getElementById('confirmDeleteBtn').href = 'index.php?page=admin_showtimes<?php echo $movie_id ? "&movie_id=$movie_id" : ""; ?>&delete_id=' + id;
        var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    }
</script>
