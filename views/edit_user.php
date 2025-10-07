<?php
// edit_user.php - Superadmin edits a user
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: ?page=login');
    exit;
}
require_once __DIR__ . '/../config/connection.php';
$conn = getDBConnection();

$roles = ['student', 'instructor', 'manager', 'superadmin'];
$message = '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: ?page=superadmindash');
    exit;
}
// Fetch user
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$user) {
    header('Location: ?page=superadmindash');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    if ($email && in_array($role, $roles)) {
        $sql = "UPDATE users SET email=?, role=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssi', $email, $role, $id);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">User updated successfully!</div>';
            $user['email'] = $email;
            $user['role'] = $role;
        } else {
            $message = '<div class="alert alert-danger">Error: ' . htmlspecialchars($stmt->error) . '</div>';
        }
        $stmt->close();
    } else {
        $message = '<div class="alert alert-warning">Please fill all fields and select a valid role.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User - Superadmin</title>
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
    <div class="superadmin-header"><i class="fas fa-user-edit"></i> Edit User</div>
    <?php if ($message) echo $message; ?>
    <form method="POST" autocomplete="off">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>
        <div class="form-group">
            <label>Role</label>
            <select name="role" class="form-control" required>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= $r ?>" <?= $user['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Update User</button>
        <a href="?page=superadmindash" class="btn btn-secondary">Back</a>
    </form>
</div>
</body>
</html>
