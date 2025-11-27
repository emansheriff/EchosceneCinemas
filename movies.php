<?php
// Get all movies
$query = "SELECT * FROM movies ORDER BY release_date DESC";
$result = mysqli_query($conn, $query);

// Get all unique genres for filter buttons
$genres_query = "SELECT DISTINCT genre FROM movies WHERE genre IS NOT NULL AND genre != ''";
$genres_result = mysqli_query($conn, $genres_query);
$genres = [];
while ($genre = mysqli_fetch_assoc($genres_result)) {
    if (strpos($genre['genre'], ',') !== false) {
        $genre_parts = explode(',', $genre['genre']);
        foreach ($genre_parts as $part) {
            $part = trim($part);
            if (!empty($part) && !in_array($part, $genres)) {
                $genres[] = $part;
            }
        }
    } else {
        if (!empty($genre['genre']) && !in_array($genre['genre'], $genres)) {
            $genres[] = $genre['genre'];
        }
    }
}
?>

<!-- Page Title -->
<h1 class="page-title">All Movies</h1>

<!-- Genre Filter Buttons -->
<div class="genre-buttons">
  <button class="btn btn-outline-light active" onclick="filterGenre('all')">All Movies</button>
  <?php foreach ($genres as $genre): ?>
    <button class="btn btn-outline-light" onclick="filterGenre('<?php echo $genre; ?>')"><?php echo $genre; ?></button>
  <?php endforeach; ?>
</div>

<!-- No Results Message -->
<div class="no-results" id="noResults">
  <i class="fas fa-film"></i>
  <h3>No Movies Found</h3>
  <p>We couldn't find any movies matching your search. Please try different keywords or browse by genre.</p>
</div>

<!-- Movie Grid -->
<div class="row movie-container" id="movieGrid">
  <?php 
  if (mysqli_num_rows($result) > 0) {
    while ($movie = mysqli_fetch_assoc($result)): 
      // Extract the first genre if multiple are stored
      $movie_genre = $movie['genre'];
      if (strpos($movie_genre, ',') !== false) {
        $movie_genre = trim(explode(',', $movie_genre)[0]);
      }
  ?>
    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 movie-card" data-genre="<?php echo $movie_genre; ?>" data-title="<?php echo strtolower($movie['title']); ?>">
      <div class="card position-relative">
        <span class="badge-genre"><?php echo $movie_genre; ?></span>
        <img src="<?php echo $movie['image'] ?? 'https://via.placeholder.com/300x450?text=No+Image'; ?>" class="card-img-top" alt="<?php echo $movie['title']; ?>" />
        <div class="card-body">
          <h5 class="card-title"><?php echo $movie['title']; ?></h5>
          <div class="card-rating">
            <?php  
$rating = number_format($movie['rating'] ?? 0, 1);
echo "<p class='movie-rating'>$rating <i class='fas fa-star text-warning'></i></p>";
?>

          </div>
          <a href="index.php?page=movie_details&id=<?php echo $movie['id']; ?>" class="btn btn-book">Book Now</a>
        </div>
      </div>
    </div>
  <?php 
    endwhile; 
  } else {
    echo '<div class="col-12 text-center"><p>No movies available at this time.</p></div>';
  }
  ?>
</div>