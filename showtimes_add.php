<?php
// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: index.php?page=login&error=admin_required');
    exit;
}

// Get movie ID from URL if provided
$movie_id = $_GET['movie_id'] ?? 0;

// Get movie details if movie_id is provided
$movie = null;
if ($movie_id) {
    $movie_query = "SELECT * FROM movies WHERE id = ?";
    $stmt = $conn->prepare($movie_query);
    $stmt->bind_param('i', $movie_id);
    $stmt->execute();
    $movie_result = $stmt->get_result();
    
    if ($movie_result->num_rows > 0) {
        $movie = $movie_result->fetch_assoc();
    }
}

// Get all theaters
$theaters_query = "SELECT * FROM theaters WHERE status = 'Active' ORDER BY name";
$theaters_result = mysqli_query($conn, $theaters_query);

// Get all movies if no specific movie is selected
$movies = [];
if (!$movie_id) {
    $movies_query = "SELECT id, title FROM movies WHERE status != 'Archived' ORDER BY title";
    $movies_result = mysqli_query($conn, $movies_query);
    while ($movie_row = mysqli_fetch_assoc($movies_result)) {
        $movies[] = $movie_row;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $movie_id = $_POST['movie_id'] ?? 0;
    $theater_id = $_POST['theater_id'] ?? 0;
    $show_date = $_POST['show_date'] ?? '';
    $show_time = $_POST['show_time'] ?? '';
    $price = $_POST['price'] ?? 0;
    $status = $_POST['status'] ?? 'Available';
    
    // Validate form data
    $errors = [];
    
    if (empty($movie_id) || !is_numeric($movie_id)) {
        $errors[] = 'Please select a movie';
    }
    
    if (empty($theater_id) || !is_numeric($theater_id)) {
        $errors[] = 'Please select a theater';
    }
    
    if (empty($show_date)) {
        $errors[] = 'Show date is required';
    }
    
    if (empty($show_time)) {
        $errors[] = 'Show time is required';
    }
    
    if (empty($price) || !is_numeric($price)) {
        $errors[] = 'Price must be a number';
    }
    
    // If no errors, insert showtime
    if (empty($errors)) {
        $query = "INSERT INTO showtimes (movie_id, theater_id, show_date, show_time, price, status, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('iissds', $movie_id, $theater_id, $show_date, $show_time, $price, $status);
        
        if ($stmt->execute()) {
            // Redirect to showtimes page
            header('Location: index.php?page=admin_showtimes&movie_id=' . $movie_id . '&success=added');
            exit;
        } else {
            $errors[] = 'Error adding showtime: ' . $conn->error;
        }
    }
}
?>

<!-- Add Showtime Page -->
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title mb-0">Add New Showtime</h1>
        <?php if ($movie_id): ?>
            <a href="index.php?page=admin_showtimes&movie_id=<?php echo $movie_id; ?>" class="btn btn-outline-light">
                <i class="fas fa-arrow-left mr-1"></i> Back to Showtimes
            </a>
        <?php else: ?>
            <a href="index.php?page=admin_showtimes" class="btn btn-outline-light">
                <i class="fas fa-arrow-left mr-1"></i> Back to Showtimes
            </a>
        <?php endif; ?>
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
    
    <div class="card">
        <div class="card-body">
            <form method="post" action="">
                <div class="row">
                    <div class="col-md-6">
                        <?php if ($movie): ?>
                            <div class="form-group">
                                <label>Movie</label>
                                <input type="hidden" name="movie_id" value="<?php echo $movie['id']; ?>">
                                <div class="card bg-dark">
                                    <div class="card-body d-flex align-items-center">
                                        <img src="<?php echo $movie['image']; ?>" alt="<?php echo $movie['title']; ?>" 
                                             class="img-thumbnail mr-3" style="width: 60px; height: 90px; object-fit: cover;">
                                        <div>
                                            <h5 class="mb-1"><?php echo $movie['title']; ?></h5>
                                            <p class="mb-0 text-muted"><?php echo $movie['genre']; ?> | <?php echo $movie['duration']; ?> min</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="form-group mb-3">
                                <label for="movie_id">Movie <span class="text-danger">*</span></label>
                                <select class="form-control" id="movie_id" name="movie_id" required>
                                    <option value="">Select Movie</option>
                                    <?php foreach ($movies as $movie_option): ?>
                                        <option value="<?php echo $movie_option['id']; ?>" <?php echo (isset($_POST['movie_id']) && $_POST['movie_id'] == $movie_option['id']) ? 'selected' : ''; ?>>
                                            <?php echo $movie_option['title']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-group mb-3">
                            <label for="theater_id">Theater <span class="text-danger">*</span></label>
                            <select class="form-control" id="theater_id" name="theater_id" required>
                                <option value="">Select Theater</option>
                                <?php while ($theater = mysqli_fetch_assoc($theaters_result)): ?>
                                    <option value="<?php echo $theater['id']; ?>" <?php echo (isset($_POST['theater_id']) && $_POST['theater_id'] == $theater['id']) ? 'selected' : ''; ?>>
                                        <?php echo $theater['name']; ?> (<?php echo $theater['location']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="show_date">Show Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="show_date" name="show_date" required 
                                           value="<?php echo $_POST['show_date'] ?? date('Y-m-d'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="show_time">Show Time <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="show_time" name="show_time" required 
                                           value="<?php echo $_POST['show_time'] ?? '19:00'; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="price">Ticket Price <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">EGP</span>
                                </div>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required 
                                       value="<?php echo $_POST['price'] ?? '12.50'; ?>">
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="status">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="Available" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Available') ? 'selected' : ''; ?>>Available</option>  
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-book">
                        <i class="fas fa-save mr-1"></i> Save Showtime
                    </button>
                </div>
            </form>
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
    
    .input-group-text {
        background: rgba(0, 0, 0, 0.3);
        border: 2px solid rgba(255, 255, 255, 0.3);
        color: var(--text-light);
    }
    
    .img-thumbnail {
        background-color: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .btn-book {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        color: white;
        padding: 8px 20px;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .btn-book:hover {
        background-color: #a71d1d;
        border-color: #a71d1d;
        color: white;
    }
</style>
