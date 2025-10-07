<?php
// superadmindash.php - Superadmin dashboard for user management
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: ?page=login');
    exit;
}
require_once __DIR__ . '/../config/connection.php';
$conn = getDBConnection();

// Fetch all users
$users = [];
$sql = "SELECT id, email, role, created_at, created_by FROM users ORDER BY id DESC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Superadmin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css">
    <style>
        body { background: #f8fafc; }
        .container { margin-top: 2.5rem; }
        .table thead th { background: #3a4a5a; color: #fff; }
        .btn-role { font-size: 0.95rem; }
        .superadmin-header { font-size: 2rem; font-weight: 700; color: #2a3a4a; margin-bottom: 1.5rem; }
    </style>
</head>
<body>
<!-- Superadmin Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand font-weight-bold" href="?page=superadmindash"><i class="fas fa-user-shield"></i> Superadmin</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#superadminNav" aria-controls="superadminNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="superadminNav">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item active"><a class="nav-link" href="?page=superadmindash">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="?page=add_user">Add User</a></li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item"><a class="nav-link text-danger font-weight-bold" href="?page=logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
</nav>
<div class="container">
    <div class="superadmin-header"><i class="fas fa-user-shield"></i> Superadmin Dashboard</div>
    <div class="mb-3 text-center">
        <img src="./assets/images/course.jpg" alt="Course Management" style="max-width:220px;max-height:120px;border-radius:1.2rem;box-shadow:0 2px 12px #3a4a5a22;" onerror="this.style.display='none'">
    </div>
    <a href="?page=add_user" class="btn btn-success mb-3"><i class="fas fa-user-plus"></i> Add User</a>
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Email</th>
                <th>Role</th>
                <th>Created At</th>
                <th>Created By</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['id']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= htmlspecialchars($user['role']) ?></td>
                <td><?= htmlspecialchars($user['created_at']) ?></td>
                <td><?= htmlspecialchars($user['created_by']) ?></td>
                <td>
                    <a href="?page=edit_user&id=<?= $user['id'] ?>" class="btn btn-primary btn-sm btn-role"><i class="fas fa-edit"></i> Edit</a>
                    <a href="?page=delete_user&id=<?= $user['id'] ?>" class="btn btn-danger btn-sm btn-role" onclick="return confirm('Delete this user?');"><i class="fas fa-trash"></i> Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
