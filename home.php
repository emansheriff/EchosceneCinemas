
<?php
// Fetch movies from database
$query = "SELECT * FROM movies ORDER BY release_date DESC LIMIT 8";
$result = mysqli_query($conn, $query);

// Get all unique genres for filter buttons
$genres_query = "SELECT DISTINCT genre FROM movies WHERE genre IS NOT NULL AND genre != ''";
$genres_result = mysqli_query($conn, $genres_query);
$genres = [];
while ($genre_row = mysqli_fetch_assoc($genres_result)) {
    $genres[] = $genre_row['genre'];
}
?>
<!-- Background Video -->
<video autoplay muted loop id="bg-video">
  <source src="intro.mp4" type="video/mp4" />
  Your browser does not support this video.
</video>
<!-- Page Title -->
<h1 class="page-title">Welcome to EchoScene <?php if (isset($_SESSION['username'])): ?>
    <div class="welcome-msg text-light text-center mt-3">
        <?php echo htmlspecialchars($_SESSION['username']); ?>!
    </div>
<?php endif; ?></h1>
<!-- Hero Welcome Section -->
<div class="hero-section">
  <p>
    EchoScene isn't about the latest releases , it's about the ones that echo in your memory. <br />
    From timeless masterpieces to cult favorites, we bring back the most unforgettable stories to the big screen.
</p>
</div>

<!-- Featured Movies -->
<section class="featured-memories">
    <h2>Featured Memories</h2>
    <div class="row">
      <div class="col-md-4 mb-4">
        <div class="memory-card">
          <img src="https://m.media-amazon.com/images/I/514zBLkyJcL.jpg" alt="Movie">
          <div class="memory-card-body">
            <h5>Interstellar (2014)</h5>
            <p>One of the greatest films ever made, returning to EchoScene in restored glory.</p>
          </div>
        </div> 
      </div>
      <div class="col-md-4 mb-4">
        <div class="memory-card">
          <img src="https://m.media-amazon.com/images/M/MV5BYTFmNTFlOTAtNzEyNi00MWU2LTg3MGEtYjA2NWY3MDliNjlkXkEyXkFqcGc@._V1_.jpg" alt="Movie">
          <div class="memory-card-body">
            <h5>Drive (2011)</h5>
            <p>Slick, stylish, and neon-drenched. Ryan Gosling's mysterious getaway driver returns to the big screen.</p>
          </div>
        </div> 
      </div>
      <div class="col-md-4 mb-4">
        <div class="memory-card">
          <img src="https://m.media-amazon.com/images/I/81D+KJkO4SL._AC_UF894,1000_QL80_.jpg" alt="Movie">
          <div class="memory-card-body">
            <h5>Fight Club (1999)</h5>
            <p>The cult classic that rewrote the rules ,Fight Club is back louder and sharper than ever.</p>
          </div>
        </div> 
      </div>
    </div>
  </section>
  <br>
  
<!-- Why EchoScene -->
<section class="text-center text-white container mb-5">
    <h2>Why Choose EchoScene?</h2>
    <p class="mb-4">More than just a cinema , it's a time machine.</p>
    <div class="row">
      <div class="col-md-4">
        <h5>📽️ Nostalgic Classics</h5>
        <p>We revive movies that shaped generations.</p>
      </div>
      <div class="col-md-4">
        <h5>🍿 Cinematic Comfort</h5>
        <p>Cozy seats, immersive sound, and perfect vibes.</p>
      </div>
      <div class="col-md-4">
        <h5>🖥️ Smart Booking</h5>
        <p>Simple, sleek, and fast — reserve your spot in seconds.</p>
      </div>
    </div>
  </section>

</div>