<?php
// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Simple validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        // Check if email already exists
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Email already exists';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $query = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('sss', $username, $email, $hashed_password);
            
            if ($stmt->execute()) {
                $success = 'Registration successful! You can now login.';
                
                // Redirect to login page after 2 seconds
                echo '<meta http-equiv="refresh" content="2;url=index.php?page=login">';
            } else {
                $error = 'Registration failed: ' . $conn->error;
            }
        }
    }
}
?>

<!-- Page Title -->
<h1 class="page-title">Create an Account</h1>

<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php else: ?>
                
                <form method="post" action="">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Choose a username" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Choose a password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                    </div>
                    <button type="submit" class="btn btn-book btn-block">Register</button>
                </form>
                
                <div class="text-center mt-4">
                    <p>Already have an account? <a href="index.php?page=login" class="text-light"><u>Login here</u></a></p>
                </div>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>