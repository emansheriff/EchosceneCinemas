<?php
// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // If user is admin, redirect to admin dashboard
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
        header('Location:index.php?page=admin_dashboard');
    } else {
        header('Location:index.php');
    }
    exit;
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Simple validation
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        // Check user credentials
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                
                // Check if user is admin based on email domain
                if (strpos($email, '@echoscene.com') !== false) {
                    $_SESSION['is_admin'] = 1;
                } else {
                    $_SESSION['is_admin'] = $user['is_admin'] ?? 0;
                }
                
                // Redirect to appropriate page
                if ($_SESSION['is_admin']) {

                     //header('Location:index.php?page=admin_dashboard');
 echo '<div class="container mt-5">
                <div class="alert text-center">
                    <h3 class="page-title">Welcome Back,Admin ! </h3>
                    <a href="index.php" class="btn btn-outline-light mt-3">Go to Dashboard</a>
                </div>
              </div>';
                } else {
                   
                    
 echo '<div class="container mt-5">
                <div class="alert text-center">
                    <h3 class="page-title"> Welcome Back ! </h3>
                    <a href="index.php" class="btn btn-outline-light mt-3">Go to Home Page</a>
                   
                </div>
              </div>';
                }
                exit;
            } else {
                $error = 'Invalid password';
            }
        } else {
            $error = 'User not found';
        }
    }
}
?>

<!-- Page Title -->
<div class="container mt-4">
    <h1 class="page-title">Sign In</h1>

    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="card">
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <div class="form-group mb-3">
                            <label for="email">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                        </div>
                        <div class="form-group mb-3">
                            <a href="index.php?page=forgot" class="text-light">Forgot Password?</a>
                        </div>
                        <button type="submit" class="btn btn-book btn-block">Sign In</button>
                    </form>
                    
                    <div class="text-center mt-4">
                        <p>Don't have an account? <a href="index.php?page=register" class="text-light"><u>Register here</u></a></p>
                    </div>
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
</style>
