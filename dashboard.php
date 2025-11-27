<?php
// Add error checking for database connection
if (!isset($conn) || !$conn) {
    die("Database connection not established. Please check your connection settings.");
}

// Get counts for dashboard with error handling
// Movies count
$movies_count = 0;
$movies_query = "SELECT COUNT(*) as count FROM movies";
$movies_result = mysqli_query($conn, $movies_query);
if ($movies_result) {
    $movies_data = mysqli_fetch_assoc($movies_result);
    $movies_count = $movies_data ? $movies_data['count'] : 0;
} else {
    error_log("Error in movies count query: " . mysqli_error($conn));
}

// Showtimes count
$showtimes_count = 0;
$showtimes_query = "SELECT COUNT(*) as count FROM showtimes";
$showtimes_result = mysqli_query($conn, $showtimes_query);
if ($showtimes_result) {
    $showtimes_data = mysqli_fetch_assoc($showtimes_result);
    $showtimes_count = $showtimes_data ? $showtimes_data['count'] : 0;
} else {
    error_log("Error in showtimes count query: " . mysqli_error($conn));
}

// Users count
$users_count = 0;
$users_query = "SELECT COUNT(*) as count FROM users";
$users_result = mysqli_query($conn, $users_query);
if ($users_result) {
    $users_data = mysqli_fetch_assoc($users_result);
    $users_count = $users_data ? $users_data['count'] : 0;
} else {
    error_log("Error in users count query: " . mysqli_error($conn));
}

// Bookings count
$bookings_count = 0;
$bookings_query = "SELECT COUNT(*) as count FROM bookings";
$bookings_result = mysqli_query($conn, $bookings_query);
if ($bookings_result) {
    $bookings_data = mysqli_fetch_assoc($bookings_result);
    $bookings_count = $bookings_data ? $bookings_data['count'] : 0;
} else {
    error_log("Error in bookings count query: " . mysqli_error($conn));
}

// Get recent bookings with error handling
$recent_bookings_result = false;
$recent_bookings_query = "SELECT b.*, u.username, m.title as movie_title, s.show_date, s.show_time 
                          FROM bookings b 
                          JOIN users u ON b.user_id = u.id 
                          JOIN showtimes s ON b.showtime_id = s.id 
                          JOIN movies m ON s.movie_id = m.id 
                          ORDER BY b.booking_date DESC LIMIT 5";
$recent_bookings_result = mysqli_query($conn, $recent_bookings_query);
if (!$recent_bookings_result) {
    error_log("Error in recent bookings query: " . mysqli_error($conn));
}
?>

<!-- Admin Dashboard -->
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title mb-0">Admin Dashboard</h1>
        <div>
            <a href="index.php?page=admin_movies" class="btn btn-outline-light mr-2">
                <i class="fas fa-film mr-1"></i> Manage Movies
            </a>
            <a href="index.php?page=admin_showtimes" class="btn btn-outline-light">
                <i class="fas fa-clock mr-1"></i> Manage Showtimes
            </a>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-film fa-3x mb-3" style="color: var(--primary-color);"></i>
                    <h5 class="card-title">Total Movies</h5>
                    <h2><?php echo $movies_count; ?></h2>
                    <a href="index.php?page=admin_movies" class="btn btn-sm btn-outline-light mt-3">View All</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-3x mb-3" style="color: var(--primary-color);"></i>
                    <h5 class="card-title">Total Showtimes</h5>
                    <h2><?php echo $showtimes_count; ?></h2>
                    <a href="index.php?page=admin_showtimes" class="btn btn-sm btn-outline-light mt-3">View All</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-3x mb-3" style="color: var(--primary-color);"></i>
                    <h5 class="card-title">Total Users</h5>
                    <h2><?php echo $users_count; ?></h2>
                    <a href="index.php?page=admin_users" class="btn btn-sm btn-outline-light mt-3">View All</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-ticket-alt fa-3x mb-3" style="color: var(--primary-color);"></i>
                    <h5 class="card-title">Total Bookings</h5>
                    <h2><?php echo $bookings_count; ?></h2>
                    <a href="index.php?page=admin_bookings" class="btn btn-sm btn-outline-light mt-3">View All</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Bookings -->
    <div class="card mt-4">
        <div class="card-header bg-dark">
            <h5 class="mb-0">Recent Bookings</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Movie</th>
                            <th>Date & Time</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Booked On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_bookings_result && mysqli_num_rows($recent_bookings_result) > 0): ?>
                            <?php while ($booking = mysqli_fetch_assoc($recent_bookings_result)): ?>
                                <tr>
                                    <td><?php echo $booking['id']; ?></td>
                                    <td><?php echo $booking['username']; ?></td>
                                    <td><?php echo $booking['movie_title']; ?></td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($booking['show_date'])); ?> at 
                                        <?php echo date('h:i A', strtotime($booking['show_time'])); ?>
                                    </td>
                                    <td><?php echo number_format($booking['total_amount'], 2); ?> EGP</td>
                                    <td>
                                        <?php if ($booking['status'] == 'Confirmed'): ?>
                                            <span class="badge badge-success">Confirmed</span>
                                        <?php elseif ($booking['status'] == 'Pending'): ?>
                                            <span class="badge badge-warning">Pending</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Cancelled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo ucfirst($booking['payment_method'] ?? 'Cash'); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($booking['booking_date'])); ?></td>
                                    <td>
                                        <a href="index.php?page=admin_booking_view&id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No bookings found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
    
    .card-header {
        border-bottom: 1px solid var(--card-border);
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
    
    .badge-success {
        background-color: #28a745;
    }
    
    .badge-warning {
        background-color: #ffc107;
        color: #212529;
    }
    
    .badge-danger {
        background-color: #dc3545;
    }
    
    .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }
    
    .btn-primary:hover {
        background-color: var(--primary-color-dark);
        border-color: var(--primary-color-dark);
    }
</style>