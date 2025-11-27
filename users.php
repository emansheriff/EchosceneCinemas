<?php
// Check if user is logged in and is an admin - already done in index.php

// Handle user actions
$message = '';
$message_type = '';

// Delete user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = $_GET['delete'];
    
    // Don't allow deleting yourself
    if ($user_id == $_SESSION['user_id']) {
        $message = "You cannot delete your own account!";
        $message_type = "danger";
    } else {
        // Check if user exists
        $check_query = "SELECT id FROM users WHERE id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Delete user's bookings first (due to foreign key constraints)
            $conn->begin_transaction();
            
            try {
                // Delete booking details
                $delete_details_query = "DELETE bd FROM booking_details bd 
                                        JOIN bookings b ON bd.booking_id = b.id 
                                        WHERE b.user_id = ?";
                $stmt = $conn->prepare($delete_details_query);
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                
                // Delete bookings
                $delete_bookings_query = "DELETE FROM bookings WHERE user_id = ?";
                $stmt = $conn->prepare($delete_bookings_query);
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                
                // Delete user
                $delete_user_query = "DELETE FROM users WHERE id = ?";
                $stmt = $conn->prepare($delete_user_query);
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                
                $conn->commit();
                $message = "User deleted successfully!";
                $message_type = "success";
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Error deleting user: " . $e->getMessage();
                $message_type = "danger";
            }
        } else {
            $message = "User not found!";
            $message_type = "danger";
        }
    }
}

// Toggle admin status
if (isset($_GET['toggle_admin']) && is_numeric($_GET['toggle_admin'])) {
    $user_id = $_GET['toggle_admin'];
    
    // Don't allow changing your own admin status
    if ($user_id == $_SESSION['user_id']) {
        $message = "You cannot change your own admin status!";
        $message_type = "danger";
    } else {
        // Get current admin status
        $status_query = "SELECT is_admin FROM users WHERE id = ?";
        $stmt = $conn->prepare($status_query);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $new_status = $user['is_admin'] ? 0 : 1;
            
            // Update admin status
            $update_query = "UPDATE users SET is_admin = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param('ii', $new_status, $user_id);
            $stmt->execute();
            
            $message = "User admin status updated successfully!";
            $message_type = "success";
        } else {
            $message = "User not found!";
            $message_type = "danger";
        }
    }
}

// Get users with pagination
$page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
$params = [];
$types = '';

if (!empty($search)) {
    $search_condition = "WHERE username LIKE ? OR email LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param];
    $types = 'ss';
}

// Count total users for pagination
$count_query = "SELECT COUNT(*) as total FROM users $search_condition";
if (!empty($params)) {
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $count_result = $stmt->get_result();
} else {
    $count_result = $conn->query($count_query);
}
$count_data = $count_result->fetch_assoc();
$total_users = $count_data['total'];
$total_pages = ceil($total_users / $limit);

// Get users for current page
$users_query = "SELECT id, username, email, is_admin, created_at FROM users $search_condition ORDER BY id LIMIT ?, ?";
$stmt = $conn->prepare($users_query);
if (!empty($params)) {
    $params[] = $offset;
    $params[] = $limit;
    $types .= 'ii';
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param('ii', $offset, $limit);
}
$stmt->execute();
$users_result = $stmt->get_result();
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title mb-0">User Management</h1>
        <div>
            <a href="index.php?page=admin_dashboard" class="btn btn-outline-light mr-2">
                <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
            </a>
            <a href="index.php?page=admin_user_add" class="btn btn-outline-light">
                <i class="fas fa-plus mr-1"></i> Add New User
            </a>
        </div>
    </div>
    
    <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>
    
    <!-- Search Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="index.php" method="get" class="form-inline">
                <input type="hidden" name="page" value="admin_users">
                <div class="form-group mb-2 flex-grow-1">
                    <input type="text" class="form-control w-100" id="search" name="search" placeholder="Search by username or email" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <button type="submit" class="btn btn-primary mb-2 ml-2">Search</button>
                <?php if (!empty($search)): ?>
                <a href="index.php?page=admin_users" class="btn btn-secondary mb-2 ml-2">Clear</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <!-- Users Table -->
    <div class="card">
        <div class="card-header bg-dark">
            <h5 class="mb-0">Users</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users_result->num_rows > 0): ?>
                            <?php while ($user = $users_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php if ($user['is_admin']): ?>
                                        <span class="badge badge-primary">Admin</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">User</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <a href="index.php?page=admin_user_edit&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="index.php?page=admin_users&toggle_admin=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to change admin status for this user?')">
                                        <?php if ($user['is_admin']): ?>
                                            <i class="fas fa-user"></i>
                                        <?php else: ?>
                                            <i class="fas fa-user-shield"></i>
                                        <?php endif; ?>
                                    </a>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <a href="index.php?page=admin_users&delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user? This will also delete all their bookings.')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No users found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="index.php?page=admin_users&page_num=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="index.php?page=admin_users&page_num=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="index.php?page=admin_users&page_num=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
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
    
    .badge-primary {
        background-color: var(--primary-color);
    }
    
    .badge-secondary {
        background-color: #6c757d;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        line-height: 1.5;
        border-radius: 0.2rem;
    }
    
    .btn-info {
        background-color: #17a2b8;
        border-color: #17a2b8;
    }
    
    .btn-warning {
        background-color: #ffc107;
        border-color: #ffc107;
        color: #212529;
    }
    
    .btn-danger {
        background-color: #dc3545;
        border-color: #dc3545;
    }
    
    .pagination {
        margin-bottom: 0;
    }
    
    .page-link {
        background-color: var(--card-bg);
        border: 1px solid var(--card-border);
        color: var(--text-light);
    }
    
    .page-item.active .page-link {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }
    
    .page-item.disabled .page-link {
        background-color: var(--card-bg);
        border-color: var(--card-border);
        color: rgba(255, 255, 255, 0.5);
    }
</style>