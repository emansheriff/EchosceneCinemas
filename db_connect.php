<?php
// Database connection parameters
$servername = "localhost";  // Default XAMPP MySQL server
$username = "root";         // Default XAMPP MySQL username
$password = "";             // Default XAMPP MySQL password (empty by default)
$dbname = "movie_booking";  // Your database name

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set character set to UTF-8
mysqli_set_charset($conn, "utf8");

// Uncomment the line below during development to see database errors
// mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Note: Don't close the connection here as it will be used in other files
?>