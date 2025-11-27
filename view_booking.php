<?php
// Don't start session if already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Use the correct path to include the database connection file
require_once __DIR__ . '/../db_connect.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

// Get all bookings with pagination
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$total_query = "SELECT COUNT(*) as total FROM bookings";
$total_result = mysqli_query($conn, $total_query);
$total_bookings = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_bookings / $per_page);

$bookings_query = "SELECT b.*, u.username, m.title as movie_title, s.show_date, s.show_time 
                   FROM bookings b 
                   JOIN users u ON b.user_id = u.id 
                   JOIN showtimes s ON b.showtime_id = s.id 
                   JOIN movies m ON s.movie_id = m.id 
                   ORDER BY b.booking_date DESC 
                   LIMIT ?, ?";
$stmt = $conn->prepare($bookings_query);
$stmt->bind_param('ii', $offset, $per_page);
$stmt->execute();
$bookings_result = $stmt->get_result();
?>

<!-- Admin Bookings Page -->
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title mb-0">Manage Bookings</h1>
        <a href="index.php?page=admin_dashboard" class="btn btn-outline-light">
            <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
        </a>
    </div>
    
    <!-- Search and Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="" method="GET" class="row align-items-end">
                <input type="hidden" name="page" value="admin_bookings">
                <div class="col-md-3 mb-3 mb-md-0">
                    <label for="search">Search Bookings</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="Search by ID or username" value="<?php echo $_GET['search'] ?? ''; ?>">
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <label for="status">Filter by Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="Confirmed" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="Pending" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="Cancelled" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <label for="date">Filter by Date</label>
                    <input type="date" class="form-control" id="date" name="date" value="<?php echo $_GET['date'] ?? ''; ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-block">Filter</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Bookings Table -->
    <div class="card">
        <div class="card-header bg-dark">
            <h5 class="mb-0">All Bookings</h5>
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
                        <?php if ($bookings_result && mysqli_num_rows($bookings_result) > 0): ?>
                            <?php while ($booking = mysqli_fetch_assoc($bookings_result)): ?>
                                <tr>
                                    <td><?php echo $booking['id']; ?></td>
                                    <td><?php echo htmlspecialchars($booking['username']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['movie_title']); ?></td>
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
                                        <div class="btn-group">
                                            <a href="index.php?page=admin_booking_view&id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($booking['status'] != 'Cancelled'): ?>
                                                <a href="index.php?page=admin_booking_update&id=<?php echo $booking['id']; ?>&status=Confirmed" class="btn btn-sm btn-success">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <a href="index.php?page=admin_booking_update&id=<?php echo $booking['id']; ?>&status=Cancelled" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to cancel this booking?');">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
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
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="index.php?page=admin_bookings&p=<?php echo $page - 1; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="index.php?page=admin_bookings&p=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="index.php?page=admin_bookings&p=<?php echo $page + 1; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
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
    
    .btn-success {
        background-color: #28a745;
        border-color: #28a745;
    }
    
    .btn-success:hover {
        background-color: #218838;
        border-color: #1e7e34;
    }
    
    .btn-danger {
        background-color: #dc3545;
        border-color: #dc3545;
    }
    
    .btn-danger:hover {
        background-color: #c82333;
        border-color: #bd2130;
    }
    
    .page-link {
        background-color: var(--card-bg);
        border-color: var(--card-border);
        color: var(--text-light);
    }
    
    .page-item.active .page-link {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }
    
    .form-control {
        background-color: rgba(255, 255, 255, 0.1);
        border-color: var(--card-border);
        color: var(--text-light);
    }
    
    .form-control:focus {
        background-color: rgba(255, 255, 255, 0.15);
        color: var(--text-light);
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(var(--primary-color-rgb), 0.25);
    }
</style>