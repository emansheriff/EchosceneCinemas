<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only admins, only POST
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
   // header('Location: index.php?page=login');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $user_id = (int)$_POST['id'];
    // Prevent self-delete
    if ($user_id !== (int)$_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->close();
    }
}
//header('Location: index.php?page=admin_users');
exit;
?>