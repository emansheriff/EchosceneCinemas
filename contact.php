<?php
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message_text = $_POST['message'] ?? '';
    
    if (empty($name) || empty($email) || empty($subject) || empty($message_text)) {
        $message = 'Please fill in all fields';
        $messageType = 'danger';
    } else {
        // In a real application, you would send an email here
        // For now, we'll just show a success message
        $message = 'Thank you for your message! We will get back to you soon.';
        $messageType = 'success';
    }
}
?>

<!-- Page Title -->
<h1 class="page-title">Contact Us</h1>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="mb-4">Get In Touch</h2>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="form-group">
                        <label for="name">Your Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-book">Send Message</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="mb-4">Contact Information</h2>
                <p><i class="fas fa-map-marker-alt mr-2"></i> Zamalek, Cairo, Egypt Located near El Sawy Culture Wheel</p>
                <p><i class="fas fa-phone mr-2"></i>  Phone: +20 2 2735 8884</p>
                <p><i class="fas fa-envelope mr-2"></i> Email: info@echoscene.eg </p>
                
                <h4 class="mt-4">Opening Hours</h4>
                <p>Monday - Thursday: 11:00 AM - 11:00 PM<br>
                Friday - Saturday: 10:00 AM - 1:00 AM<br>
                Sunday: 10:00 AM - 11:00 PM</p>
                
                <h4 class="mt-4">Follow Us</h4>
                <div class="social-links mt-3">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>