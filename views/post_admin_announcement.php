<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Role check: only superadmins can post to other admins
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: ?page=login');
    exit;
}

require_once __DIR__ . '/../config/connection.php';
$conn = getDBConnection();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['content'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $user_id = $_SESSION['user_id'];

    if (!empty($title) && !empty($content)) {
        $stmt = $conn->prepare("INSERT INTO admin_announcements (user_id, title, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $title, $content);

        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Announcement posted successfully for all admins!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='alert alert-warning'>Title and content cannot be empty.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Post Admin Announcement</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark"><a class="navbar-brand" href="?page=superadmindash"><i class="fas fa-user-shield"></i> Admin Panel</a><ul class="navbar-nav ml-auto"><li class="nav-item"><a class="nav-link" href="?page=superadmindash"><i class="fas fa-arrow-left"></i> Back to Dashboard</a></li></ul></nav>
<div class="container mt-4">
    <div class="card card-info shadow-lg">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-bullhorn mr-2"></i>Create Announcement for Admins</h3></div>
        <div class="card-body">
            <?php if ($message) echo $message; ?>
            <form method="POST">
                <div class="form-group"><label for="title">Announcement Title</label><input type="text" class="form-control" id="title" name="title" placeholder="Enter a clear title for admins" required></div>
                <div class="form-group"><label for="content">Announcement Content</label><textarea class="form-control" id="content" name="content" required></textarea></div>
                <button type="submit" class="btn btn-info btn-lg"><i class="fas fa-paper-plane"></i> Post to Admins</button>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
<script>$(document).ready(function(){$('#content').summernote({placeholder:'Enter the announcement details for admins here...',height:250});});</script>
</body>
</html>
<?php $conn->close(); ?>