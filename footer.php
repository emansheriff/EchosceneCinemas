  </div>

  <!-- Footer -->
  <footer>
    <div class="container">
      <div class="footer-content">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
  <img src="echoscene-logo.png" alt="EchoScene Cinemas Logo" class="neon-logo" />
</a>
        <div class="footer-links">
          <a href="index.php">Home</a>
          <a href="index.php?page=movies">Movies</a>
          <a href="index.php?page=showtimes">Showtimes</a>
          <a href="index.php?page=about">About Us</a>
          <a href="index.php?page=contact">Contact</a>
        </div>
        <div class="social-links">
          <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
          <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
          <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
          <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
        </div>
        <p class="copyright">&copy; <?php echo date('Y'); ?> EchoScene Cinemas. All rights reserved.</p>
      </div>
    </div>
  </footer>

  <!-- JavaScript -->
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <script>
    // Wait for document to load
    document.addEventListener('DOMContentLoaded', function() {
      // Get elements
      const searchInput = document.getElementById('searchInput');
      const searchButton = document.getElementById('searchButton');
      const movieCards = document.querySelectorAll('.movie-card');
      const noResults = document.getElementById('noResults');
      const genreButtons = document.querySelectorAll('.genre-buttons .btn');
      
      if (movieCards.length > 0) {
        let currentGenre = 'all';
        let searchTerm = '';
        
        // Function to filter movies
        function filterMovies() {
          let visibleCount = 0;
          
          movieCards.forEach(card => {
            const title = card.getAttribute('data-title').toLowerCase();
            const genre = card.getAttribute('data-genre');
            
            // Check if card matches both genre and search filters
            const matchesGenre = (currentGenre === 'all' || genre === currentGenre);
            const matchesSearch = (searchTerm === '' || title.includes(searchTerm));
            
            if (matchesGenre && matchesSearch) {
              card.classList.remove('d-none');
              visibleCount++;
            } else {
              card.classList.add('d-none');
            }
          });
          
          // Show or hide no results message
          if (visibleCount === 0 && noResults) {
            noResults.style.display = 'block';
          } else if (noResults) {
            noResults.style.display = 'none';
          }
        }
        
        // Filter by genre
        window.filterGenre = function(genre) {
          currentGenre = genre;
          
          // Update active button
          genreButtons.forEach(btn => {
            if (btn.textContent.trim().toLowerCase().includes(genre.toLowerCase())) {
              btn.classList.add('active');
            } else {
              btn.classList.remove('active');
            }
          });
          
          filterMovies();
        };
        
        // Search functionality
        if (searchInput && searchButton) {
          searchInput.addEventListener('input', function() {
            searchTerm = this.value.toLowerCase();
            filterMovies();
          });
          
          // Search button click
          searchButton.addEventListener('click', function() {
            searchTerm = searchInput.value.toLowerCase();
            filterMovies();
          });
          
          // Prevent form submission
          const searchForm = document.querySelector('form');
          if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
              e.preventDefault();
              searchTerm = searchInput.value.toLowerCase();
              filterMovies();
            });
          }
        }
        
        // Initialize with all movies shown
        filterMovies();
      }
    });
  </script>
</body>
</html>