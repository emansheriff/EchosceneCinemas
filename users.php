<?php
// Don't start session if already active
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

// Get all users with pagination
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Apply search and filters if provided
$where_clauses = [];
$params = [];
$param_types = '';

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $where_clauses[] = "(username LIKE ? OR email LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $param_types .= 'ss';
}

if (isset($_GET['role']) && !empty($_GET['role'])) {
    if ($_GET['role'] == 'admin') {
        $where_clauses[] = "is_admin = 1";
    } else {
        $where_clauses[] = "is_admin = 0";
    }
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = " WHERE " . implode(' AND ', $where_clauses);
}

// Count total users with filters
$total_query = "SELECT COUNT(*) as total FROM users" . $where_sql;
if (!empty($params)) {
    $stmt = $conn->prepare($total_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $total_result = $stmt->get_result();
} else {
    $total_result = mysqli_query($conn, $total_query);
}
$total_users = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_users / $per_page);

// Get users with pagination and filters
$users_query = "SELECT * FROM users" . $where_sql . " ORDER BY id DESC LIMIT ?, ?";
$stmt = $conn->prepare($users_query);

if (!empty($params)) {
    $params[] = $offset;
    $params[] = $per_page;
    $param_types .= 'ii';
    $stmt->bind_param($param_types, ...$params);
} else {
    $stmt->bind_param('ii', $offset, $per_page);
}

$stmt->execute();
$users_result = $stmt->get_result();
?>

<!-- Admin Users Page -->
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title mb-0">Manage Users</h1>
        <a href="index.php?page=admin_dashboard" class="btn btn-outline-light">
            <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
        </a>
    </div>
    
    <!-- Search and Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="" method="GET" class="row align-items-end">
                <input type="hidden" name="page" value="admin_users">
                <div class="col-md-4 mb-3 mb-md-0">
                    <label for="search">Search Users</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="Search by name or email" value="<?php echo $_GET['search'] ?? ''; ?>">
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <label for="role">Filter by Role</label>
                    <select class="form-control" id="role" name="role">
                        <option value="">All Roles</option>
                        <option value="admin" <?php echo (isset($_GET['role']) && $_GET['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="user" <?php echo (isset($_GET['role']) && $_GET['role'] == 'user') ? 'selected' : ''; ?>>User</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-block">Filter</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Users Table -->
    <div class="card">
        <div class="card-header bg-dark d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Users</h5>
            <a href="index.php?page=admin_user_add" class="btn btn-sm btn-primary">
                <i class="fas fa-plus mr-1"></i> Add New User
            </a>
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
                            <th>Registered On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users_result && mysqli_num_rows($users_result) > 0): ?>
                            <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <?php if ($user['is_admin'] == 1): ?>
                                            <span class="badge badge-primary">Admin</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                 <?php if ($user['id'] != $_SESSION['user_id']): ?>
    <form action="index.php?page=admin_user_delete" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
            <i class="fas fa-trash"></i>
        </button>
    </form>
<?php endif; ?>
                                        </div>
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
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="index.php?page=admin_users&p=<?php echo $page - 1; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['role']) ? '&role=' . urlencode($_GET['role']) : ''; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="index.php?page=admin_users&p=<?php echo $i; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['role']) ? '&role=' . urlencode($_GET['role']) : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="index.php?page=admin_users&p=<?php echo $page + 1; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['role']) ? '&role=' . urlencode($_GET['role']) : ''; ?>" aria-label="Next">
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
    
    .badge-danger {
        background-color: #dc3545;
    }
    
    .badge-primary {
        background-color: var(--primary-color);
    }
    
    .badge-secondary {
        background-color: #6c757d;
    }
    
    .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }
    
    .btn-primary:hover {
        background-color: var(--primary-color-dark);
        border-color: var(--primary-color-dark);
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