<?php
// delete_booking.php

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db_connect.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: index.php?page=login');
    exit;
}

// Check if booking ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: bookings.php?error=invalid_id');
    exit;
}

$booking_id = (int)$_GET['id'];

// Delete booking details (seats)
$stmt = $conn->prepare("DELETE FROM booking_details WHERE booking_id = ?");
$stmt->bind_param('i', $booking_id);
$stmt->execute();
$stmt->close();

// Delete the booking itself
$stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
$stmt->bind_param('i', $booking_id);
$stmt->execute();
$stmt->close();

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Booking deleted successfully.
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>
exit;
?>