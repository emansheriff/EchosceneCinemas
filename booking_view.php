<?php

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../index.php?page=login');
    exit;
}

// Check if booking ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: bookings.php');
    exit;
}

$booking_id = $_GET['id'];

// Get booking details
$booking_query = "SELECT b.*, u.username, u.email, 
                 s.show_date, s.show_time, s.price as ticket_price,
                 m.title as movie_title, m.image as movie_image, m.genre as movie_genre,
                 t.name as theater_name
                 FROM bookings b
                 JOIN users u ON b.user_id = u.id
                 JOIN showtimes s ON b.showtime_id = s.id
                 JOIN movies m ON s.movie_id = m.id
                 JOIN theaters t ON s.theater_id = t.id
                 WHERE b.id = ?";
$stmt = $conn->prepare($booking_query);
$stmt->bind_param('i', $booking_id);
$stmt->execute();
$booking_result = $stmt->get_result();

if ($booking_result->num_rows == 0) {
    header('Location: bookings.php');
    exit;
}

$booking = $booking_result->fetch_assoc();

// Get booked seats
$seats_query = "SELECT bd.*, s.seat_row, s.seat_number 
               FROM booking_details bd
               JOIN seats s ON bd.seat_id = s.id
               WHERE bd.booking_id = ?";
$stmt = $conn->prepare($seats_query);
$stmt->bind_param('i', $booking_id);
$stmt->execute();
$seats_result = $stmt->get_result();

$seats = [];
$total_seats = 0;
while ($seat = $seats_result->fetch_assoc()) {
    $seats[] = $seat;
    $total_seats++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Booking - Movie Booking System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
body {
  background: #1a1a1a;
  color: #fff;
  font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;
  min-height: 100vh;
  padding: 0;
}

.booking-header {
  background: rgba(255,255,255,0.07);
  color: #fff;
  border-radius: 14px;
  border: 1px solid rgba(255,255,255,0.12);
  margin-bottom: 22px;
  box-shadow: 0 2px 14px rgba(183,28,28,0.06);
  padding: 22px 35px;
}

.booking-header h4 {
  color: #b71c1c;
  font-weight: 700;
}

.badge-success {
  background: #43a047 !important;
  color: #fff !important;
}
.badge-warning {
  background:rgb(46, 30, 5) !important;
  color: #fff !important;
}
.badge-danger {
  background: #e53935 !important;
  color: #fff !important;
}
.badge-secondary {
  background: #b71c1c !important;
  color: #fff !important;
  font-weight: 700;
}

.card {
  background: rgba(255,255,255,0.07) !important;
  color: #fff;
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: 14px !important;
  box-shadow: 0 4px 24px rgba(0,0,0,0.14);
}

.card-header {
  background: transparent !important;
  border-bottom: 1px solid rgba(255,255,255,0.12);
  color: #b71c1c;
}

.text-primary, .font-weight-bold.text-primary {
  color: #b71c1c !important;
}

.seat-badge {
  display: inline-block;
  background: #b71c1c;
  color: #fff;
  border-radius: 6px;
  font-weight: 600;
  letter-spacing: 1px;
  padding: 6px 14px;
  margin: 3px 2px;
  box-shadow: 0 1px 5px rgba(183, 28, 28, 0.08);
  font-size: 1rem;
}

.btn-success {
  background: #43a047;
  border-color: #43a047;
}
.btn-success:hover {
  background: #2e7031;
  border-color: #2e7031;
}
.btn-warning {
  background: #b67100;
  border-color: #b67100;
  color: #fff !important;
}
.btn-warning:hover {
  background: #6e4700;
  border-color: #6e4700;
  color: #fff !important;
}
.btn-danger {
  background: #e53935;
  border-color: #e53935;
}
.btn-danger:hover {
  background: #a01b1b;
  border-color: #a01b1b;
}
.btn-primary {
  background: #b71c1c;
  border-color: #b71c1c;
}
.btn-primary:hover {
  background: #8e1313;
  border-color: #8e1313;
}

a, a:visited {
  color: #ffd54f;
  transition: color 0.2s;
}
a:hover, a:focus {
  color: #b71c1c;
  text-decoration: none;
}

hr {
  border-color: rgba(255,255,255,0.12);
}

@media (max-width: 991.98px) {
  .booking-header {
    padding: 20px 12px;
  }
  .card {
    margin-bottom: 16px;
  }
}
    </style>
</head>
<body>
            
            <!-- Main Content -->
            <div class="col-md-10 py-4 px-4"> 
                <div class="booking-header">
                    <div class="row">
                        <div class="col-md-6">
                            <h4>Booking #<?php echo $booking['id']; ?></h4>
                            <p class="mb-0">
                                <strong>Status:</strong> 
                                <?php if ($booking['status'] == 'Confirmed'): ?>
                                    <span class="badge badge-success">Confirmed</span>
                                <?php elseif ($booking['status'] == 'Pending'): ?>
                                    <span class="badge badge-warning">Pending</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Cancelled</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-6 text-md-right">
                            <p class="mb-0"><strong>Booking Date:</strong> <?php echo date('F d, Y H:i', strtotime($booking['booking_date'])); ?></p>
                            <p class="mb-0"><strong>Total Amount:</strong> <?php echo number_format($booking['total_amount'], 2); ?> EGP</p>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Movie Information -->
                    <div class="col-md-4 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Movie Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <img src="<?php echo $booking['movie_image']; ?>" alt="<?php echo $booking['movie_title']; ?>" class="img-fluid" style="max-height: 200px;">
                                </div>
                                <h5 class="text-center"><?php echo $booking['movie_title']; ?></h5>
                                <p class="text-center"><span class="badge badge-secondary"><?php echo $booking['movie_genre']; ?></span></p>
                                <hr>
                                <p><strong>Theater:</strong> <?php echo $booking['theater_name']; ?></p>
                                <p><strong>Show Date:</strong> <?php echo date('F d, Y', strtotime($booking['show_date'])); ?></p>
                                <p><strong>Show Time:</strong> <?php echo date('h:i A', strtotime($booking['show_time'])); ?></p>
                                <p><strong>Ticket Price:</strong><?php echo number_format($booking['ticket_price'], 2); ?> EGP each</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Customer Information -->
                    <div class="col-md-4 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Customer Information</h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Username:</strong> <?php echo $booking['username']; ?></p>
                                <p><strong>Email:</strong> <?php echo $booking['email']; ?></p>
                                <p><strong>User ID:</strong> <?php echo $booking['user_id']; ?></p>
                                <hr>
                                <p><strong>Payment Method:</strong> <?php echo ucfirst($booking['payment_method']); ?></p>
                                <p><strong>Payment Status:</strong> 
                                    <?php if ($booking['status'] == 'Confirmed'): ?>
                                        <span class="badge badge-success">Paid</span>
                                    <?php elseif ($booking['status'] == 'Pending' && $booking['payment_method'] == 'cash'): ?>
                                        <span class="badge badge-warning">Pay at Counter</span>
                                    <?php elseif ($booking['status'] == 'Pending'): ?>
                                        <span class="badge badge-warning">Pending</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Cancelled</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Booking Details -->
                    <div class="col-md-4 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Booking Details</h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Total Seats:</strong> <?php echo $total_seats; ?></p>
                                <p><strong>Seats:</strong></p>
                                <div>
                                    <?php foreach ($seats as $seat): ?>
                                        <span class="seat-badge"><?php echo $seat['seat_row'] . $seat['seat_number']; ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <span><strong>Subtotal:</strong></span>
                                    <span><?php echo number_format($booking['total_amount'], 2); ?> EGP</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span><strong>Tax:</strong></span>
                                    <span>Included</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <span><strong>Total:</strong></span>
                                    <span><strong><?php echo number_format($booking['total_amount'], 2); ?> EGP</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <?php if ($booking['status'] == 'Pending'): ?>
                                <a href="bookings.php?confirm=<?php echo $booking['id']; ?>" class="btn btn-success mr-2" onclick="return confirm('Are you sure you want to confirm this booking?')">
                                    <i class="fas fa-check mr-1"></i> Confirm Booking
                                </a>
                                <?php endif; ?>
                                <?php if ($booking['status'] != 'Cancelled'): ?>
                                <a href="bookings.php?cancel=<?php echo $booking['id']; ?>" class="btn btn-warning mr-2" onclick="return confirm('Are you sure you want to cancel this booking?')">
                                    <i class="fas fa-ban mr-1"></i> Cancel Booking
                                </a>
                                <?php endif; ?>
                                <a href="admin/delete_booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this booking? This action cannot be undone.')">
                                <i class="fas fa-trash"></i> Delete Booking
                                </a>
                            </div>
                            <div>
                                <button class="btn btn-primary" onclick="window.print()">
                                    <i class="fas fa-print mr-1"></i> Print Booking
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
