<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'db_connect.php';

// Check if user is admin and redirect to admin dashboard if no page is specified
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1 && !isset($_GET['page'])) {
    header('Location: index.php?page=admin_dashboard');
    exit;
}

// Include header
include 'header.php';

// Get the current page from URL or default to home
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Define allowed pages and their corresponding PHP files
$allowed_pages = [
    'home' => 'home.php',
    'movies' => 'movies.php',
    'movie_details' => 'movie_details.php',
    'showtimes' => 'showtimes.php',
    'booking' => 'booking.php',
    'login' => 'login.php',
    'register' => 'register.php',
    'profile' => 'profile.php',
    'contact' => 'contact.php',
    'about' => 'about.php',
    'forgot' => 'forgot.php',
    'logout' => 'logout.php',
    // Admin pages
    'admin_dashboard' => 'admin/dashboard.php',
    'admin_movies' => 'admin/movies.php',
    'admin_movie_add' => 'admin/movie_add.php',
    'admin_movie_edit' => 'admin/movie_edit.php',
    'admin_showtimes' => 'admin/showtimes.php',
    'admin_showtime_add' => 'admin/showtimes_add.php',
     // Add these new pages
    'admin_users' => 'admin/users.php',
    'admin_user_add' => 'admin/users_add.php',
    'admin_user_delete' => 'admin/users_delete.php',
    'admin_bookings' => 'admin/bookings.php',
    'admin_delete_bookings' => 'admin/delete_booking.php',
    'admin_booking_view' => 'admin/booking_view.php'
];

// Check if the requested page is an admin page
$admin_pages =
 ['admin_dashboard', 'admin_movies', 'admin_movie_add', 'admin_movie_edit', 'admin_showtimes', 'admin_showtime_add' ,'admin_view_booking',// Add these new pages
    'admin_users','admin_user_delete','admin_delete_bookings',
    'admin_bookings','admin_user_add',
    'admin_booking_view'];
if (in_array($page, $admin_pages)) {
    // Check if user is logged in and is admin
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
        // Not an admin, show access denied message
        echo '<div class="container mt-5">
                <div class="alert alert-danger text-center">
                    <h3><i class="fas fa-exclamation-triangle mr-2"></i> Access Denied</h3>
                    <p>You do not have permission to access this page.</p>
                    <a href="index.php" class="btn btn-outline-light mt-3">Go Home</a>
                </div>
              </div>';
        include 'footer.php';
        exit;
    }
}

// Check if the requested page exists, otherwise show 404
if (array_key_exists($page, $allowed_pages)) {
    include $allowed_pages[$page];
} else {
    // 404 page
    echo '<div class="container mt-5 text-center">
            <h1>404 - Page Not Found</h1>
            <p>The page you are looking for does not exist.</p>
            <a href="index.php" class="btn btn-primary">Go Home</a>
          </div>';
}

// Include footer
include 'footer.php';
?>
