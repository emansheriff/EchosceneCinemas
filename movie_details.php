<?php
// Get movie ID from URL
$movie_id = $_GET['id'] ?? 0;

// Get movie details
$query = "SELECT * FROM movies WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $movie_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="text-center mt-5">
            <i class="fas fa-exclamation-circle fa-3x text-danger mb-3"></i>
            <h2 class="text-light">Movie Not Found</h2>
            <p class="text-light">The movie you are looking for does not exist or has been removed.</p>
            <a href="index.php?page=movies" class="btn btn-outline-light mt-3">Browse Movies</a>
          </div>';
    exit;
}

$movie = $result->fetch_assoc();

// Get showtimes for this movie
$showtimes_query = "SELECT s.*, t.name as theater_name FROM showtimes s 
                    JOIN theaters t ON s.theater_id = t.id 
                    WHERE s.movie_id = ? AND s.show_date >= CURDATE() 
                    ORDER BY s.show_date, s.show_time";
$stmt = $conn->prepare($showtimes_query);
$stmt->bind_param('i', $movie_id);
$stmt->execute();
$showtimes_result = $stmt->get_result();

// Extract the first genre if multiple are stored
$movie_genre = $movie['genre'];
if (strpos($movie_genre, ',') !== false) {
    $movie_genre = trim(explode(',', $movie_genre)[0]);
}
?>

<div class="row mt-5">
    <div class="col-md-4">
        <div class="card">
            <span class="badge-genre"><?php echo $movie_genre; ?></span>
            <img src="<?php echo $movie['image'] ?? 'https://via.placeholder.com/300x450?text=No+Image'; ?>" class="card-img-top" alt="<?php echo $movie['title']; ?>" style="height: auto;">
        </div>
    </div>
    <div class="col-md-8">
        <h1 class="mb-3"><?php echo $movie['title']; ?></h1>
        
        <div class="card-rating mb-3">
           <?php  
$rating = number_format($movie['rating'] ?? 0, 1);
echo "<p class='movie-rating'>$rating <i class='fas fa-star text-warning'></i></p>";
?>

        </div>
        
        <div class="movie-details p-4" style="background: rgba(0,0,0,0.5); border-radius: 16px;">
            <p><?php echo $movie['description']; ?></p>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <p><strong>Genre:</strong> <?php echo $movie['genre']; ?></p>
                    <p><strong>Duration:</strong> <?php echo $movie['duration']; ?> minutes</p>
                    <p><strong>Language:</strong> <?php echo $movie['language'] ?? 'English'; ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Director:</strong> <?php echo $movie['director'] ?? 'Not specified'; ?></p>
                    <p><strong>Cast:</strong> <?php echo $movie['cast'] ?? 'Not specified'; ?></p>
                    <p><strong>Release Date:</strong> <?php echo date('F d, Y', strtotime($movie['release_date'])); ?></p>
                </div>
            </div>
            
            <?php if (!empty($movie['trailer_url'])): ?>
                <div class="mt-4">
                    <a href="<?php echo $movie['trailer_url']; ?>" class="btn btn-danger" target="_blank">
                        <i class="fab fa-youtube mr-2"></i> Watch Trailer
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <h2 class="mt-5 mb-4">Showtimes</h2>
        
        <?php if ($showtimes_result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table" style="background: rgba(0,0,0,0.5); border-radius: 16px;">
                    <thead>
                        <tr class="text-light">
                            <th>Date</th>
                            <th>Time</th>
                            <th>Theater</th>
                            <th>Price</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($showtime = $showtimes_result->fetch_assoc()): ?>
                            <tr class="text-light">
                                <td><?php echo date('F d, Y', strtotime($showtime['show_date'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($showtime['show_time'])); ?></td>
                                <td><?php echo $showtime['theater_name']; ?></td>
                                <td><?php echo number_format($showtime['price'], 2); ?> EGP </td>
                                <td>
                                    <a href="index.php?page=booking&showtime_id=<?php echo $showtime['id']; ?>" class="btn btn-sm btn-book">Book Tickets</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert" style="background: rgba(0,0,0,0.5); color: white; border-radius: 16px;">
                No showtimes available for this movie. Please check back later.
            </div>
        <?php endif; ?>
    </div>
</div>