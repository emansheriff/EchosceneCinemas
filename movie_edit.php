<?php
// Get movie ID from URL
$movie_id = $_GET['id'] ?? 0;

// Check if movie exists
$query = "SELECT * FROM movies WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $movie_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Movie not found, redirect to movies page
    header('Location: index.php?page=admin_movies&error=not_found');
    exit;
}

$movie = $result->fetch_assoc();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $duration = $_POST['duration'] ?? 0;
    $release_date = $_POST['release_date'] ?? '';
    $genre = $_POST['genre'] ?? '';
    $language = $_POST['language'] ?? '';
    $director = $_POST['director'] ?? '';
    $cast = $_POST['cast'] ?? '';
    $rating = $_POST['rating'] ?? 0;
    $image = $_POST['image'] ?? '';
    $trailer_url = $_POST['trailer_url'] ?? '';
    $status = $_POST['status'] ?? 'Now Showing';
    
    // Validate form data
    $errors = [];
    
    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    
    if (empty($description)) {
        $errors[] = 'Description is required';
    }
    
    if (empty($duration) || !is_numeric($duration)) {
        $errors[] = 'Duration must be a number';
    }
    
    if (empty($release_date)) {
        $errors[] = 'Release date is required';
    }
    
    if (empty($genre)) {
        $errors[] = 'Genre is required';
    }
    
    if (empty($image)) {
        $errors[] = 'Image URL is required';
    }
    
    // If no errors, update movie
    if (empty($errors)) {
        $query = "UPDATE movies SET 
                  title = ?, 
                  description = ?, 
                  duration = ?, 
                  release_date = ?, 
                  genre = ?, 
                  language = ?, 
                  director = ?, 
                  cast = ?, 
                  rating = ?, 
                  image = ?, 
                  trailer_url = ?, 
                  status = ? 
                  WHERE id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssisssssdsssi', $title, $description, $duration, $release_date, $genre, $language, $director, $cast, $rating, $image, $trailer_url, $status, $movie_id);
        
        if ($stmt->execute()) {
            // Redirect to movies page
            header('Location: index.php?page=admin_movies&success=updated');
            exit;
        } else {
            $errors[] = 'Error updating movie: ' . $conn->error;
        }
    }
}
?>

<!-- Edit Movie Page -->
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title mb-0">Edit Movie: <?php echo $movie['title']; ?></h1>
        <a href="index.php?page=admin_movies" class="btn btn-outline-light">
            <i class="fas fa-arrow-left mr-1"></i> Back to Movies
        </a>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-3">
            <div class="card mb-4">
                <img src="<?php echo $movie['image']; ?>" class="card-img-top" alt="<?php echo $movie['title']; ?>">
                <div class="card-body text-center">
                    <h5 class="card-title"><?php echo $movie['title']; ?></h5>
                    <p class="card-text"><?php echo $movie['genre']; ?></p>
                    <a href="index.php?page=movie_details&id=<?php echo $movie['id']; ?>" class="btn btn-sm btn-outline-light" target="_blank">
                        <i class="fas fa-eye mr-1"></i> View Movie
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-body">
                    <form method="post" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="title">Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" required 
                                           value="<?php echo $_POST['title'] ?? $movie['title']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="description">Description <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="description" name="description" rows="5" required><?php echo $_POST['description'] ?? $movie['description']; ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="duration">Duration (minutes) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="duration" name="duration" required 
                                                   value="<?php echo $_POST['duration'] ?? $movie['duration']; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="release_date">Release Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="release_date" name="release_date" required 
                                                   value="<?php echo $_POST['release_date'] ?? $movie['release_date']; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="genre">Genre <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="genre" name="genre" required 
                                                   value="<?php echo $_POST['genre'] ?? $movie['genre']; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="language">Language</label>
                                            <input type="text" class="form-control" id="language" name="language" 
                                                   value="<?php echo $_POST['language'] ?? $movie['language']; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="director">Director</label>
                                    <input type="text" class="form-control" id="director" name="director" 
                                           value="<?php echo $_POST['director'] ?? $movie['director']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="cast">Cast</label>
                                    <input type="text" class="form-control" id="cast" name="cast" 
                                           value="<?php echo $_POST['cast'] ?? $movie['cast']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="rating">Rating (0-10)</label>
                                    <input type="number" class="form-control" id="rating" name="rating" min="0" max="10" step="0.1" 
                                           value="<?php echo $_POST['rating'] ?? $movie['rating']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="image">Image URL <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="image" name="image" required 
                                           value="<?php echo $_POST['image'] ?? $movie['image']; ?>">
                                    <small class="form-text text-muted">Enter the URL of the movie poster image</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="trailer_url">Trailer URL</label>
                                    <input type="text" class="form-control" id="trailer_url" name="trailer_url" 
                                           value="<?php echo $_POST['trailer_url'] ?? $movie['trailer_url']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="Now Showing" <?php echo (isset($_POST['status']) ? ($_POST['status'] == 'Now Showing' ? 'selected' : '') : ($movie['status'] == 'Now Showing' ? 'selected' : '')); ?>>Now Showing</option>
                                        <option value="Coming Soon" <?php echo (isset($_POST['status']) ? ($_POST['status'] == 'Coming Soon' ? 'selected' : '') : ($movie['status'] == 'Coming Soon' ? 'selected' : '')); ?>>Coming Soon</option>
                                        <option value="Archived" <?php echo (isset($_POST['status']) ? ($_POST['status'] == 'Archived' ? 'selected' : '') : ($movie['status'] == 'Archived' ? 'selected' : '')); ?>>Archived</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-book">
                                <i class="fas fa-save mr-1"></i> Update Movie
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }
    
    .form-control {
        background: rgba(255, 255, 255, 0.1);
        border: 2px solid rgba(255, 255, 255, 0.3);
        color: var(--text-light);
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .form-control:focus {
        background: rgba(255, 255, 255, 0.2);
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(183, 28, 28, 0.25);
        color: var(--text-light);
    }
    
    .form-control::placeholder {
        color: rgba(255, 255, 255, 0.7);
    }
</style>