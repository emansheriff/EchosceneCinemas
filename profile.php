<?php
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

// Get user information
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get user's bookings
$bookings_query = "SELECT b.*, m.title as movie_title, s.show_date, s.show_time, t.name as theater_name 
                  FROM bookings b 
                  JOIN showtimes s ON b.showtime_id = s.id 
                  JOIN movies m ON s.movie_id = m.id 
                  JOIN theaters t ON s.theater_id = t.id 
                  WHERE b.user_id = ? 
                  ORDER BY b.booking_date DESC";
$stmt = $conn->prepare($bookings_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$bookings_result = $stmt->get_result();

// Process profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Validate input
    if (empty($username)) {
        $errors[] = 'Username is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    // Check if email already exists (for another user)
    if ($email !== $user['email']) {
        $check_query = "SELECT * FROM users WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param('si', $email, $user_id);
        $stmt->execute();
        $check_result = $stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $errors[] = 'Email already exists';
        }
    }
    
    // If changing password
    if (!empty($current_password)) {
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = 'Current password is incorrect';
        }
        
        // Validate new password
        if (empty($new_password)) {
            $errors[] = 'New password is required';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'New password must be at least 6 characters';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match';
        }
    }
    
    // If no errors, update profile
    if (empty($errors)) {
        // If changing password
        if (!empty($current_password) && !empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param('sssi', $username, $email, $hashed_password, $user_id);
        } else {
            $update_query = "UPDATE users SET username = ?, email = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param('ssi', $username, $email, $user_id);
        }
        
        if ($stmt->execute()) {
            // Update session username
            $_SESSION['username'] = $username;
            
            // Check if user is admin based on email domain
            if (strpos($email, '@echoscene.com') !== false) {
                $_SESSION['is_admin'] = 1;
            } else {
                $_SESSION['is_admin'] = $user['is_admin'] ?? 0;
            }
            
            $success = 'Profile updated successfully';
            
            // Refresh user data
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        } else {
            $errors[] = 'Error updating profile: ' . $conn->error;
        }
    }
}
?>

<div class="container mt-4">
    <h1 class="page-title">My Profile</h1>
    
    <div class="row">
        <!-- Profile Information -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-dark">
                    <h5 class="mb-0">Profile Information</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <hr>
                        <h6>Change Password (optional)</h6>
                        
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-book btn-block">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Booking History -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-dark">
                    <h5 class="mb-0">My Bookings</h5>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($bookings_result) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Booking ID</th>
                                        <th>Movie</th>
                                        <th>Theater</th>
                                        <th>Date & Time</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Booked On</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($booking = mysqli_fetch_assoc($bookings_result)): ?>
                                        <tr>
                                            <td>#<?php echo $booking['id']; ?></td>
                                            <td><?php echo $booking['movie_title']; ?></td>
                                            <td><?php echo $booking['theater_name']; ?></td>
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
                                            <td><?php echo date('M d, Y H:i', strtotime($booking['booking_date'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-ticket-alt fa-3x mb-3" style="color: var(--primary-color);"></i>
                            <h5>No Bookings Yet</h5>
                            <p>You haven't made any bookings yet.</p>
                            <a href="index.php?page=movies" class="btn btn-outline-light mt-2">Browse Movies</a>
                        </div>
                    <?php endif; ?>
                </div>
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
        margin-bottom: 20px;
    }
    
    .card-header {
        border-bottom: 1px solid var(--card-border);
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
</style>
