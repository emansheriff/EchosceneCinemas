<?php
// Get current date
$current_date = date('Y-m-d');

// Get date filter from URL or default to today
$date_filter = isset($_GET['date']) ? $_GET['date'] : $current_date;

// Get showtimes from database
$query = "SELECT s.*, m.title as movie_title, m.image as movie_image, m.genre as movie_genre, 
          t.name as theater_name, t.location as theater_location 
          FROM showtimes s 
          JOIN movies m ON s.movie_id = m.id 
          JOIN theaters t ON s.theater_id = t.id 
          WHERE s.show_date = ? 
          ORDER BY s.show_time";

$stmt = $conn->prepare($query);
$stmt->bind_param('s', $date_filter);
$stmt->execute();
$result = $stmt->get_result();

// Generate date options for the next 7 days
$date_options = [];
for ($i = 0; $i < 7; $i++) {
    $date = date('Y-m-d', strtotime("+$i days"));
    $date_options[$date] = date('l, F d', strtotime("+$i days"));
}
?>

<!-- Page Title -->
<h1 class="page-title">Movie Showtimes</h1>
<!-- Date Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="" class="form-inline justify-content-center">
            <input type="hidden" name="page" value="showtimes">
            <div class="form-group mr-3">
                <label for="date" class="mr-2">Select Date:</label>
                <select name="date" id="date" class="form-control" onchange="this.form.submit()">
                    <?php foreach ($date_options as $date => $label): ?>
                        <option style="background-color: black;" value="<?php echo $date; ?>" <?php echo ($date == $date_filter) ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<!-- Showtimes -->
<div class="row">
    <?php 
    // Check if we have showtimes in the database
    if ($result && $result->num_rows > 0):
        // Group showtimes by movie
        $showtimes_by_movie = [];
        while ($showtime = $result->fetch_assoc()) {
            if (!isset($showtimes_by_movie[$showtime['movie_id']])) {
                $showtimes_by_movie[$showtime['movie_id']] = [
                    'movie_id' => $showtime['movie_id'],
                    'movie_title' => $showtime['movie_title'],
                    'movie_image' => $showtime['movie_image'],
                    'movie_genre' => $showtime['movie_genre'],
                    'showtimes' => []
                ];
            }
            $showtimes_by_movie[$showtime['movie_id']]['showtimes'][] = [
                'id' => $showtime['id'],
                'theater_name' => $showtime['theater_name'],
                'theater_location' => $showtime['theater_location'],
                'show_time' => $showtime['show_time'],
                'price' => $showtime['price']
            ];
        }
        
        // Display each movie with its showtimes
        foreach ($showtimes_by_movie as $movie):
    ?>
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <img src="<?php echo $movie['movie_image']; ?>" alt="<?php echo $movie['movie_title']; ?>" class="img-fluid rounded" style="max-height: 300px;">
                        </div>
                        <div class="col-md-9">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3><?php echo $movie['movie_title']; ?></h3>
                               
                            </div>
                            
                            <h4 class="mt-4">Available Showtimes</h4>
                            <div class="row">
                                <?php foreach ($movie['showtimes'] as $showtime): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card" style="background: rgba(0,0,0,0.3);">
                                            <div class="card-body text-center">
                                                <h5 class="card-title"><?php echo date('h:i A', strtotime($showtime['show_time'])); ?></h5>
                                                <p class="card-text"><?php echo $showtime['theater_name']; ?><br>
                                                <?php echo $showtime['theater_location']; ?></p>
                                                <p class="card-text"><?php echo number_format($showtime['price'], 2); ?> EGP</p>
                                                <a href="index.php?page=booking&showtime_id=<?php echo $showtime['id']; ?>" class="btn btn-book">Book Now</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="mt-3">
                                <a href="index.php?page=movie_details&id=<?php echo $movie['movie_id']; ?>" class="text-light">
                                    <i class="fas fa-info-circle mr-1"></i> Movie Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php 
        endforeach;
    else:
        // If no showtimes in database, show sample data
    ?>
        
    <?php endif; ?>
</div>