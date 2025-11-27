<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Use the correct path to include the database connection file
require_once __DIR__ . '/../db_connect.php';

// Check if user is admin based on is_admin field
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: index.php?page=login');
    exit;
}

// --- Handle Cancel Booking ---
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $booking_id = (int)$_GET['cancel'];
    $stmt = $conn->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ?");
    $stmt->bind_param('i', $booking_id);
    $stmt->execute();
    $stmt->close();
    header("Location: index.php?page=admin_bookings");
    exit;
}

// --- Handle Delete Booking ---
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $booking_id = (int)$_GET['delete'];
    // Delete all booking details (seats)
    $stmt = $conn->prepare("DELETE FROM booking_details WHERE booking_id = ?");
    $stmt->bind_param('i', $booking_id);
    $stmt->execute();
    $stmt->close();
    // Delete booking itself
    $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt->bind_param('i', $booking_id);
    $stmt->execute();
    $stmt->close();
    header("Location: index.php?page=admin_bookings");
    exit;
}

// Get all bookings with pagination
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Apply search and filters if provided
$where_clauses = [];
$params = [];
$param_types = '';

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $where_clauses[] = "(b.id LIKE ? OR u.username LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $param_types .= 'ss';
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $where_clauses[] = "b.status = ?";
    $params[] = $_GET['status'];
    $param_types .= 's';
}

if (isset($_GET['date']) && !empty($_GET['date'])) {
    $where_clauses[] = "DATE(s.show_date) = ?";
    $params[] = $_GET['date'];
    $param_types .= 's';
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = " WHERE " . implode(' AND ', $where_clauses);
}

// Count total bookings with filters
$total_query = "SELECT COUNT(*) as total FROM bookings b 
                JOIN users u ON b.user_id = u.id 
                JOIN showtimes s ON b.showtime_id = s.id" . $where_sql;

$total_params = $params; // copy for total count
$total_param_types = $param_types;

if (!empty($total_params)) {
    $stmt = $conn->prepare($total_query);
    $stmt->bind_param($total_param_types, ...$total_params);
    $stmt->execute();
    $total_result = $stmt->get_result();
} else {
    $total_result = mysqli_query($conn, $total_query);
}

// Handle potential errors in the query
if (!$total_result) {
    $total_bookings = 0;
    $total_pages = 1;
} else {
    $total_row = mysqli_fetch_assoc($total_result);
    $total_bookings = $total_row ? ($total_row['total'] ?? 0) : 0;
    $total_pages = max(1, ceil($total_bookings / $per_page));
}

// Get bookings with pagination and filters
$bookings_query = "SELECT b.*, u.username, m.title as movie_title, s.show_date, s.show_time, 
                   (SELECT COUNT(*) FROM booking_details WHERE booking_id = b.id) as seat_count
                   FROM bookings b 
                   JOIN users u ON b.user_id = u.id 
                   JOIN showtimes s ON b.showtime_id = s.id 
                   JOIN movies m ON s.movie_id = m.id" . 
                   $where_sql . " ORDER BY b.booking_date DESC LIMIT ?, ?";

// Prepare statement with error handling
$booking_params = $params; // copy filters
$booking_param_types = $param_types;
$booking_params[] = $offset;
$booking_params[] = $per_page;
$booking_param_types .= 'ii';

$stmt = $conn->prepare($bookings_query);
if (!$stmt) {
    echo "Error preparing statement: " . $conn->error;
    $bookings_result = false;
} else {
    if (!empty($params)) {
        $stmt->bind_param($booking_param_types, ...$booking_params);
    } else {
        $stmt->bind_param('ii', $offset, $per_page);
    }
    $stmt->execute();
    $bookings_result = $stmt->get_result();
}
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
                    <input type="text" class="form-control" id="search" name="search" placeholder="Search by ID or username" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
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
                    <input type="date" class="form-control" id="date" name="date" value="<?php echo isset($_GET['date']) ? htmlspecialchars($_GET['date']) : ''; ?>">
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
                            <th>Seats</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Booked On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($bookings_result && $bookings_result->num_rows > 0): ?>
                            <?php while ($booking = mysqli_fetch_assoc($bookings_result)): ?>
                                <tr>
                                    <td><?php echo $booking['id']; ?></td>
                                    <td><?php echo htmlspecialchars($booking['username']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['movie_title']); ?></td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($booking['show_date'])); ?> at 
                                        <?php echo date('h:i A', strtotime($booking['show_time'])); ?>
                                    </td>
                                    <td><?php echo $booking['seat_count']; ?></td>
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
                                    <td><?php echo ucfirst($booking['payment_method']); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($booking['booking_date'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <!-- Print/View Booking Button -->
                                            <a href="index.php?page=admin_booking_view&id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-primary" title="View & Print Booking" target="_blank">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <!-- Cancel Booking Button (only if not already cancelled) -->
                                            <?php if ($booking['status'] != 'Cancelled'): ?>
                                            <a href="index.php?page=admin_bookings&cancel=<?php echo $booking['id']; ?>" class="btn btn-sm btn-warning"
                                               onclick="return confirm('Are you sure you want to cancel this booking?');"
                                               title="Cancel Booking">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                            <?php endif; ?>
                                            <!-- Delete Booking Button -->
                                            <a href="index.php?page=admin_bookings&delete=<?php echo $booking['id']; ?>" class="btn btn-sm btn-danger"
                                               onclick="return confirm('Are you sure you want to delete this booking? This action cannot be undone.');"
                                               title="Delete Booking">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center">No bookings found</td>
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
                                <a class="page-link" href="index.php?page=admin_bookings&p=<?php echo $page - 1; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['date']) ? '&date=' . urlencode($_GET['date']) : ''; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="index.php?page=admin_bookings&p=<?php echo $i; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['date']) ? '&date=' . urlencode($_GET['date']) : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="index.php?page=admin_bookings&p=<?php echo $page + 1; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['date']) ? '&date=' . urlencode($_GET['date']) : ''; ?>" aria-label="Next">
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

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">

<style>
    .card {
        background: var(--card-bg, #23272b);
        border: 1px solid var(--card-border, #2c3748);
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }
    
    .card-header {
        border-bottom: 1px solid var(--card-border, #2c3748);
    }
    
    .table {
        color: var(--text-light, #fff);
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
        background-color: var(--primary-color, #b71c1c);
        border-color: var(--primary-color, #b71c1c);
    }
    
    .btn-primary:hover {
        background-color: var(--primary-color-dark, #8e1313);
        border-color: var(--primary-color-dark, #8e1313);
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
        background-color: var(--card-bg, #23272b);
        border-color: var(--card-border, #2c3748);
        color: var(--text-light, #fff);
    }
    
    .page-item.active .page-link {
        background-color: var(--primary-color, #b71c1c);
        border-color: var(--primary-color, #b71c1c);
    }
    
    .form-control {
        background-color: rgba(255, 255, 255, 0.1);
        border-color: var(--card-border, #2c3748);
        color: var(--text-light, #fff);
    }
    
    .form-control:focus {
        background-color: rgba(255, 255, 255, 0.15);
        color: var(--text-light, #fff);
        border-color: var(--primary-color, #b71c1c);
        box-shadow: 0 0 0 0.2rem rgba(183,28,28,0.25);
    }
</style>