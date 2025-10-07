<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['superadmin', 'admin'])) {
    header('Location: ?page=login');
    exit;
}
require_once __DIR__ . '/../config/connection.php';
$conn = getDBConnection();

// Fetch all users with the 'instructor' role
$instructors = [];
$sql = "SELECT id, name, email, created_at FROM users WHERE role = 'instructor' ORDER BY name ASC";
$result = $conn->query($sql);
if ($result) {
    $instructors = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Instructors</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="?page=superadmindash"><i class="fas fa-user-shield"></i> Admin Panel</a>
    <ul class="navbar-nav ml-auto">
        <li class="nav-item"><a class="nav-link" href="?page=superadmindash"><i class="fas fa-arrow-left"></i> Back to Dashboard</a></li>
    </ul>
</nav>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="fas fa-chalkboard-teacher"></i> Manage Instructors</h2>
        <a href="?page=add_user&role=instructor" class="btn btn-success"><i class="fas fa-user-plus"></i> Add New Instructor</a>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Instructor List (<?= count($instructors) ?>)</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Date Joined</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($instructors)): ?>
                        <tr><td colspan="4" class="text-center">No instructors found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($instructors as $instructor): ?>
                            <tr>
                                <td><?= htmlspecialchars($instructor['name']) ?></td>
                                <td><?= htmlspecialchars($instructor['email']) ?></td>
                                <td><?= date('M d, Y', strtotime($instructor['created_at'])) ?></td>
                                <td class="text-right">
                                    <a href="?page=edit_user&id=<?= $instructor['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Edit</a>
                                    <form action="?page=delete_user" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete this instructor?');">
                                        <input type="hidden" name="delete_user_id" value="<?= $instructor['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>