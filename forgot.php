<?php
// Initialize variables
$email = '';
$error = '';
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Simple validation
    if (empty($email)) {
        $error = 'Please enter your email address';
    } elseif (empty($new_password) || empty($confirm_password)) {
        $error = 'Please enter and confirm your new password';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        // Check if email exists in database
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update the password in the database
            $update_query = "UPDATE users SET password = ? WHERE email = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param('ss', $hashed_password, $email);
            
            if ($update_stmt->execute()) {
                $success = 'Your password has been reset successfully. You can now login with your new password.';
                
                // Redirect to login page after a short delay
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'index.php?page=login';
                    }, 3000);
                </script>";
            } else {
                $error = 'Failed to update password. Please try again.';
            }
        } else {
            $error = 'Email address not found';
        }
    }
}
?>

<!-- Page Title -->
<div class="container mt-4">
    <h1 class="page-title">Reset Password</h1>

    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="card">
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                        <p class="text-center text-light">Redirecting to login page...</p>
                    <?php else: ?>
                        <p class="mb-3">Enter your email and a new password to reset your account.</p>
                        
                        <form method="post" action="index.php?page=forgot">
                            <div class="form-group mb-3">
                                <label for="email">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       placeholder="Enter your email" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="new_password">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password"
                                       placeholder="Enter new password" required>
                                <small class="form-text text-light">Password must be at least 6 characters long</small>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="confirm_password">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                       placeholder="Confirm new password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-book btn-block">Reset Password</button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <a href="index.php?page=login" class="text-light"><u>Back to Login</u></a>
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
    
    .form-control::placeholder {
        color: rgba(255, 255, 255, 0.7);
    }
    
    .btn-book {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        color: white;
        padding: 8px 20px;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .btn-book:hover {
        background-color: #a71d1d;
        border-color: #a71d1d;
        color: white;
    }
    
    .alert-success {
        background-color: rgba(40, 167, 69, 0.2);
        border-color: rgba(40, 167, 69, 0.3);
        color: #c3e6cb;
    }
    
    .alert-danger {
        background-color: rgba(220, 53, 69, 0.2);
        border-color: rgba(220, 53, 69, 0.3);
        color: #f8d7da;
    }
</style>
