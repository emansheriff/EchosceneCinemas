<?php

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Save the current URL to redirect back after login
    $_SESSION['redirect_after_login'] = "index.php?page=booking&" . http_build_query($_GET);
    
    // Redirect to login page
    echo '<div class="alert alert-warning text-center">
            <p>Please <a href="index.php?page=login" class="alert-link">login</a> to book tickets.</p>
          </div>';
    echo '<meta http-equiv="refresh" content="2;url=index.php?page=login">';
    exit;
}

// Get showtime ID from URL
$showtime_id = $_GET['showtime_id'] ?? ($_GET['showtime'] ?? 0);
$movie_id = $_GET['movie_id'] ?? 0;

// Variables to store showtime and movie info
$showtime = null;
$movie = null;
$theater = null;
$seats = [];
$booked_seats = [];

// Check if we have a valid showtime ID
if ($showtime_id) {
    // Get showtime details from database
    $query = "SELECT s.*, m.title as movie_title, m.image as movie_image, m.genre as movie_genre, 
              t.name as theater_name, t.id as theater_id, t.capacity as theater_capacity 
              FROM showtimes s 
              JOIN movies m ON s.movie_id = m.id 
              JOIN theaters t ON s.theater_id = t.id 
              WHERE s.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $showtime_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $showtime = $result->fetch_assoc();
        $movie_id = $showtime['movie_id'];
        $theater_id = $showtime['theater_id'];
        
        // Get seats for this theater
        $seats_query = "SELECT * FROM seats WHERE theater_id = ? ORDER BY seat_row, seat_number";
        $stmt = $conn->prepare($seats_query);
        $stmt->bind_param('i', $theater_id);
        $stmt->execute();
        $seats_result = $stmt->get_result();
        
        while ($seat = $seats_result->fetch_assoc()) {
            $seats[] = $seat;
        }
        
        // Get booked seats for this showtime
        $booked_query = "SELECT bd.seat_id FROM booking_details bd 
                         JOIN bookings b ON bd.booking_id = b.id 
                         WHERE b.showtime_id = ? AND b.status != 'Cancelled'";
        $stmt = $conn->prepare($booked_query);
        $stmt->bind_param('i', $showtime_id);
        $stmt->execute();
        $booked_result = $stmt->get_result();
        
        while ($booked = $booked_result->fetch_assoc()) {
            $booked_seats[] = $booked['seat_id'];
        }
    }
}

// If we don't have showtime data from database, use sample data
if (!$showtime) {
    // Sample data for demo
    $showtime = [
        'id' => $showtime_id,
        'movie_id' => $movie_id,
        'movie_title' => 'Sample Movie',
        'movie_image' => 'images/IMG_2743.JPG',
        'movie_genre' => 'Action',
        'theater_id' => 1,
        'theater_name' => 'Cinema 1',
        'show_date' => date('Y-m-d'),
        'show_time' => '19:00:00',
        'price' => 12.50,
        'theater_capacity' => 50
    ];
    
    // Get movie details if we have movie_id
    if ($movie_id) {
        $movie_query = "SELECT * FROM movies WHERE id = ?";
        $stmt = $conn->prepare($movie_query);
        $stmt->bind_param('i', $movie_id);
        $stmt->execute();
        $movie_result = $stmt->get_result();
        
        if ($movie_result->num_rows > 0) {
            $movie = $movie_result->fetch_assoc();
            $showtime['movie_title'] = $movie['title'];
            $showtime['movie_image'] = $movie['image'];
            $showtime['movie_genre'] = $movie['genre'];
        }
    }
    
    // Generate sample seats
    $rows = ['A', 'B', 'C', 'D', 'E'];
    $seats_per_row = 10;
    
    foreach ($rows as $row) {
        for ($i = 1; $i <= $seats_per_row; $i++) {
            $seats[] = [
                'id' => $row . $i,
                'theater_id' => 1,
                'seat_row' => $row,
                'seat_number' => $i,
                'status' => 'Active'
            ];
        }
    }
    
    // Generate some random booked seats
    $num_booked = 10;
    $total_seats = count($seats);
    
    for ($i = 0; $i < $num_booked; $i++) {
        $random_index = rand(0, $total_seats - 1);
        $booked_seats[] = $seats[$random_index]['id'];
    }
}

// Process booking form
$booking_success = false;
$booking_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
    $selected_seats = $_POST['selected_seats'] ?? '';
    $total_price = $_POST['total_price'] ?? 0;
    $payment_method = $_POST['payment_method'] ?? '';
    
    if (empty($selected_seats)) {
        $booking_error = 'Please select at least one seat';
    } elseif (empty($payment_method)) {
        $booking_error = 'Please select a payment method';
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert booking
            $query = "INSERT INTO bookings (user_id, showtime_id, booking_date, total_amount, status, payment_method) 
                      VALUES (?, ?, NOW(), ?, 'Confirmed', ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('idds', $_SESSION['user_id'], $showtime_id, $total_price, $payment_method);
            $stmt->execute();
            
            $booking_id = $conn->insert_id;
            
            // Insert booking details - REMOVED the seat status update
            $seat_ids = explode(',', $selected_seats);
            $seat_price = $showtime['price'];
            
            foreach ($seat_ids as $seat_id) {
                // Insert booking detail
                $query = "INSERT INTO booking_details (booking_id, seat_id, price) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('isd', $booking_id, $seat_id, $seat_price);
                $stmt->execute();
                
                // REMOVED: Update seat status to inactive
                // This was causing seats to be marked as inactive for all showtimes
            }
            
            // Commit transaction
            $conn->commit();
            
            $booking_success = true;
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $booking_error = 'Booking failed: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Seats - <?php echo $showtime['title']; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .screen {
            background-color: #ccc;
            height: 50px;
            width: 80%;
            margin: 0 auto 30px;
            text-align: center;
            line-height: 50px;
            border-radius: 5px;
        }
        .seats-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 30px;
        }
        .seat-row {
            display: flex;
            margin-bottom: 10px;
        }
        .seat {
            width: 40px;
            height: 40px;
            margin: 0 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
            border-radius: 5px;
        }
        .seat.booked {
            background-color: #f44336;
            cursor: not-allowed;
        }
        .seat.selected {
            background-color: #2196F3;
        }
        .seat.user-booked {
            background-color: #9C27B0;
        }
        .seat-legend {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            margin: 0 10px;
        }
        .legend-color {
            width: 20px;
            height: 20px;
            margin-right: 5px;
            border-radius: 3px;
        }
        .booking-summary {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
   
    
    <div class="container">
        <h1 class="page-title">Book Tickets</h1>

<?php if ($booking_success): ?>
    <div class="alert alert-success text-center">
        <h4><i class="fas fa-check-circle mr-2"></i> Booking Successful!</h4>
        <p>Your tickets have been booked successfully. 
        <?php if ($_POST['payment_method'] == 'cash'): ?>
            Please pay at the counter when you arrive at the theater.
        <?php else: ?>
            Your credit card has been charged. You will receive a confirmation email shortly.
        <?php endif; ?>
        </p>
        <a href="index.php" class="btn btn-book mt-3">Return to Home</a>
    </div>
<?php else: ?>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <img src="<?php echo $showtime['movie_image']; ?>" class="card-img-top" alt="<?php echo $showtime['movie_title']; ?>">
            <div class="card-body">
                <h4 class="card-title"><?php echo $showtime['movie_title']; ?></h4>
                <span class="badge-genre"><?php echo $showtime['movie_genre']; ?></span>
                
                <div class="mt-4">
                    <p><strong>Date:</strong> <?php echo date('l, F d, Y', strtotime($showtime['show_date'])); ?></p>
                    <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($showtime['show_time'])); ?></p>
                    <p><strong>Theater:</strong> <?php echo $showtime['theater_name']; ?></p>
                    <p><strong>Ticket Price:</strong> <?php echo number_format($showtime['price'], 2); ?> EGP</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-4">Select Your Seats</h4>
                
                <?php if ($booking_error): ?>
                    <div class="alert alert-danger"><?php echo $booking_error; ?></div>
                <?php endif; ?>
                
                <div class="text-center mb-4">
                    <div class="d-inline-block mr-4">
                        <div class="seat-example available"></div> Available
                    </div>
                    <div class="d-inline-block mr-4">
                        <div class="seat-example selected"></div> Selected
                    </div>
                    <div class="d-inline-block">
                        <div class="seat-example booked"></div> Booked
                    </div>
                </div>
                
                <div class="screen-container text-center mb-5">
                    <div class="screen">Screen</div>
                </div>
                
                <div class="seat-map">
                    <?php
                    // Group seats by row
                    $seats_by_row = [];
                    foreach ($seats as $seat) {
                        $row = $seat['seat_row'];
                        if (!isset($seats_by_row[$row])) {
                            $seats_by_row[$row] = [];
                        }
                        $seats_by_row[$row][] = $seat;
                    }
                    
                    // Display seats by row
                    foreach ($seats_by_row as $row => $row_seats):
                    ?>
                        <div class="seat-row">
                            <div class="row-label"><?php echo $row; ?></div>
                            <div class="seats">
                                <?php foreach ($row_seats as $seat): 
                                    $seat_id = $seat['id'];
                                    // Only consider a seat booked if it's in the booked_seats array for this specific showtime
                                    // or if its status is 'Inactive' (for maintenance purposes)
                                    $is_booked = in_array($seat_id, $booked_seats) || $seat['status'] === 'Inactive';
                                    $seat_class = $is_booked ? 'booked' : 'available';
                                    $disabled = $is_booked ? 'disabled' : '';
                                ?>
                                    <div class="seat <?php echo $seat_class; ?>" 
                                         data-seat-id="<?php echo $seat_id; ?>" 
                                         data-price="<?php echo $showtime['price']; ?>"
                                         <?php echo $disabled; ?>>
                                        <?php echo $seat['seat_number']; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <form method="post" action="" id="bookingForm">
                    <input type="hidden" name="showtime_id" value="<?php echo $showtime_id; ?>">
                    <input type="hidden" name="selected_seats" id="selectedSeats" value="">
                    <input type="hidden" name="total_price" id="totalPrice" value="0">
                    
                    <div class="booking-summary mt-4">
                        <h5>Booking Summary</h5>
                        <div class="card" style="background: rgba(0,0,0,0.3);">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Movie:</span>
                                    <span><?php echo $showtime['movie_title']; ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Date & Time:</span>
                                    <span><?php echo date('M d, Y', strtotime($showtime['show_date'])); ?> at <?php echo date('h:i A', strtotime($showtime['show_time'])); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Theater:</span>
                                    <span><?php echo $showtime['theater_name']; ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Selected Seats:</span>
                                    <span id="seatsList">None</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Ticket Price:</span>
                                    <span><?php echo number_format($showtime['price'], 2); ?> EGP each</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <strong>Total:</strong>
                                    <strong id="totalAmount">0.00 EGP</strong>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payment Method Selection -->
                        <div class="mt-4">
                            <h5>Payment Method</h5>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="payment_method" id="paymentCash" value="cash" required>
                                <label class="form-check-label" for="paymentCash">
                                    <i class="fas fa-money-bill-wave mr-2"></i> Cash (Pay at Counter)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="paymentCard" value="card" required>
                                <label class="form-check-label" for="paymentCard">
                                    <i class="fas fa-credit-card mr-2"></i> Credit/Debit Card
                                </label>
                            </div>
                            
                            <!-- Credit Card Details (shown only when card payment is selected) -->
                            <div id="cardDetails" class="mt-3" style="display: none;">
                                <div class="form-group">
                                    <label for="cardNumber">Card Number</label>
                                    <input type="text" class="form-control" id="cardNumber" placeholder="1234 5678 9012 3456">
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="expiryDate">Expiry Date</label>
                                            <input type="text" class="form-control" id="expiryDate" placeholder="MM/YY">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="cvv">CVV</label>
                                            <input type="text" class="form-control" id="cvv" placeholder="123">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="cardName">Name on Card</label>
                                    <input type="text" class="form-control" id="cardName" placeholder="John Doe">
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" name="confirm_booking" class="btn btn-book btn-block mt-4" id="confirmButton" disabled>
                            Confirm Booking
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add custom CSS for seat selection -->
<style>
    .seat-example {
        width: 25px;
        height: 25px;
        margin-right: 5px;
        display: inline-block;
        border-radius: 5px;
        vertical-align: middle;
    }
    
    .seat-example.available {
        background-color: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.5);
    }
    
    .seat-example.selected {
        background-color: var(--primary-color);
        border: 1px solid var(--primary-color);
    }
    
    .seat-example.booked {
        background-color: rgba(0, 0, 0, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .screen-container {
        position: relative;
        margin-bottom: 30px;
    }
    
    .screen {
        width: 80%;
        height: 10px;
        background: linear-gradient(to right, rgba(255,255,255,0.1), rgba(255,255,255,0.8), rgba(255,255,255,0.1));
        margin: 0 auto 20px;
        position: relative;
    }
    
    .screen:after {
        content: 'SCREEN';
        position: absolute;
        top: 15px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 12px;
        color: rgba(255, 255, 255, 0.7);
    }
    
    .seat-map {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 30px;
    }
    
    .seat-row {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .row-label {
        width: 30px;
        text-align: center;
        font-weight: bold;
    }
    
    .seats {
        display: flex;
        gap: 8px;
    }
    
    .seat {
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 12px;
    }
    
    .seat.available {
        background-color: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.5);
    }
    
    .seat.available:hover {
        background-color: rgba(183, 28, 28, 0.5);
        transform: scale(1.05);
    }
    
    .seat.selected {
        background-color: var(--primary-color);
        border: 1px solid var(--primary-color);
        color: white;
        transform: scale(1.05);
    }
    
    .seat.booked {
        background-color: rgba(0, 0, 0, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.2);
        cursor: not-allowed;
        color: rgba(255, 255, 255, 0.5);
    }
    
    .booking-summary {
        background: rgba(0, 0, 0, 0.2);
        padding: 20px;
        border-radius: 10px;
    }
</style>

<!-- Add custom JavaScript for seat selection -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const availableSeats = document.querySelectorAll('.seat.available');
    const selectedSeatsInput = document.getElementById('selectedSeats');
    const totalPriceInput = document.getElementById('totalPrice');
    const seatsList = document.getElementById('seatsList');
    const totalAmount = document.getElementById('totalAmount');
    const confirmButton = document.getElementById('confirmButton');
    const paymentCard = document.getElementById('paymentCard');
    const paymentCash = document.getElementById('paymentCash');
    const cardDetails = document.getElementById('cardDetails');
    
    let selectedSeats = [];
    const ticketPrice = <?php echo $showtime['price']; ?>;
    
    // Show/hide card details based on payment method selection
    paymentCard.addEventListener('change', function() {
        if (this.checked) {
            cardDetails.style.display = 'block';
        }
    });
    
    paymentCash.addEventListener('change', function() {
        if (this.checked) {
            cardDetails.style.display = 'none';
        }
    });
    
    // Add click event to seats
    availableSeats.forEach(seat => {
        seat.addEventListener('click', function() {
            const seatId = this.getAttribute('data-seat-id');
            
            if (this.classList.contains('selected')) {
                // Deselect seat
                this.classList.remove('selected');
                this.classList.add('available');
                selectedSeats = selectedSeats.filter(id => id !== seatId);
            } else {
                // Select seat
                this.classList.remove('available');
                this.classList.add('selected');
                selectedSeats.push(seatId);
            }
            
            // Update form values
            selectedSeatsInput.value = selectedSeats.join(',');
            const totalPrice = selectedSeats.length * ticketPrice;
            totalPriceInput.value = totalPrice.toFixed(2);
            
            // Update summary
            if (selectedSeats.length > 0) {
                seatsList.textContent = selectedSeats.join(', ');
                totalAmount.textContent = '$' + totalPrice.toFixed(2);
                confirmButton.disabled = false;
            } else {
                seatsList.textContent = 'None';
                totalAmount.textContent = '0.00 EG';
                confirmButton.disabled = true;
            }
        });
    });
    
    // Form validation
    document.getElementById('bookingForm').addEventListener('submit', function(e) {
        if (selectedSeats.length === 0) {
            e.preventDefault();
            alert('Please select at least one seat');
            return false;
        }
        
        if (!paymentCash.checked && !paymentCard.checked) {
            e.preventDefault();
            alert('Please select a payment method');
            return false;
        }
        
        if (paymentCard.checked) {
            const cardNumber = document.getElementById('cardNumber').value;
            const expiryDate = document.getElementById('expiryDate').value;
            const cvv = document.getElementById('cvv').value;
            const cardName = document.getElementById('cardName').value;
            
            if (!cardNumber || !expiryDate || !cvv || !cardName) {
                e.preventDefault();
                alert('Please fill in all card details');
                return false;
            }
        }
        
        return true;
    });
});
</script>

<?php endif; ?>
    </div>
    
    <?php include 'footer.php'; ?>
    
    
</body>
</html>
