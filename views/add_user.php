<?php
// add_user.php - Superadmin adds a new user
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: ?page=login');
    exit;
}
require_once __DIR__ . '/../config/connection.php';
$conn = getDBConnection();

$roles = ['student', 'instructor', 'manager', 'superadmin'];
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $created_by = $_SESSION['user_id'];
    $created_at = date('Y-m-d H:i:s');
    if ($email && $password && in_array($role, $roles)) {
        $sql = "INSERT INTO users (email, password, role, created_at, created_by) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('ssssi', $email, $password, $role, $created_at, $created_by);
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">User added successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger">Error: ' . htmlspecialchars($stmt->error) . '</div>';
            }
            $stmt->close();
        } else {
            $message = '<div class="alert alert-danger">Prepare failed: ' . htmlspecialchars($conn->error) . '</div>';
        }
    } else {
        $message = '<div class="alert alert-warning">Please fill all fields and select a valid role.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add User - Superadmin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css">
    <style>
        body { background: #f8fafc; }
        .container { margin-top: 2.5rem; max-width: 500px; }
        .superadmin-header { font-size: 1.5rem; font-weight: 700; color: #2a3a4a; margin-bottom: 1.5rem; }
    </style>
</head>
<body>
<div class="container">
    <div class="superadmin-header"><i class="fas fa-user-plus"></i> Add New User</div>
    <?php if ($message) echo $message; ?>
    <form method="POST" autocomplete="off">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="text" name="password" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Role</label>
            <select name="role" class="form-control" required>
                <option value="">Select role</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= $r ?>"><?= ucfirst($r) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-success">Add User</button>
        <a href="?page=superadmindash" class="btn btn-secondary">Back</a>
    </form>
</div>
</body>
</html>
