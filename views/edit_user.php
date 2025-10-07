<?php
// edit_user.php - Superadmin edits a user
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['superadmin', 'admin'])) {
    header('Location: ?page=login');
    exit;
}
require_once __DIR__ . '/../config/connection.php';
$conn = getDBConnection();

// Define roles based on the current user's role
if ($_SESSION['role'] === 'superadmin') {
    $roles = ['student', 'instructor', 'admin', 'superadmin'];
} else { // 'admin' role
    $roles = ['student', 'instructor'];
}
$message = '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: ?page=superadmindash');
    exit;
}

// Fetch user
$sql = "SELECT id, name, email, role, status FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: ?page=superadmindash');
    exit;
}

// Security check: An 'admin' cannot edit a 'superadmin'
if ($_SESSION['role'] === 'admin' && $user['role'] === 'superadmin') {
    $_SESSION['flash_message'] = 'Access Denied: You do not have permission to edit a superadmin.';
    header('Location: ?page=superadmindash');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $status = $_POST['status'];
    $new_password = $_POST['new_password'];

    if ($name && $email && in_array($role, $roles) && in_array($status, ['active', 'suspended'])) {
        $conn->begin_transaction();
        // Base query
        $sql = "UPDATE users SET name=?, email=?, role=?, status=? WHERE id=?";
        $params = [$name, $email, $role, $status, $id];
        $types = 'ssssi';

        // If a new password is provided, hash it and add to query
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET name=?, email=?, role=?, status=?, password=? WHERE id=?";
            $params = [$name, $email, $role, $status, $hashed_password, $id];
            $types = 'sssssi';
        }

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        try {
            if (!$stmt->execute()) {
                throw new Exception($stmt->error, $stmt->errno);
            }
            $stmt->close();

            // Sync with students or instructors table
            if ($user['role'] === 'student') {
                // The students table is linked by email, not user_id. Use the old email to find the record.
                $sync_stmt = $conn->prepare("UPDATE students SET name = ?, email = ? WHERE email = ?");
                $sync_stmt->bind_param("sss", $name, $email, $user['email']);
                $sync_stmt->execute();
                $sync_stmt->close();
            } elseif ($user['role'] === 'instructor') {
                // Assuming instructors table has a user_id. If not, this needs adjustment.
                // For now, let's assume it's linked by email.
                $sync_stmt = $conn->prepare("UPDATE instructors SET name = ?, email = ? WHERE email = ?");
                $sync_stmt->bind_param("sss", $name, $email, $user['email']); // Use old email to find the record
                $sync_stmt->execute();
                $sync_stmt->close();
            }

            $conn->commit();
            $_SESSION['flash_message'] = 'User updated successfully!';
            header('Location: ?page=superadmindash');
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            if ($e->getCode() === 1062) {
                $message = '<div class="alert alert-danger">Error: This email address is already in use.</div>';
            } else {
                $message = '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }

        // Refresh user data to show in form
        $user['name'] = $name;
        $user['email'] = $email;
        $user['role'] = $role;
        $user['status'] = $status;
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8fafc; }
        .card { border: none; box-shadow: 0 0 20px rgba(0,0,0,.05); }
        .card-header { background-color: #343a40; color: white; }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0"><i class="fas fa-user-edit"></i> Edit User: <?= htmlspecialchars($user['name']) ?></h4>
        </div>
        <div class="card-body">
            <?php if ($message) echo $message; ?>
            <form method="POST" autocomplete="off">
                <div class="form-group"><label for="name">Full Name</label><input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required></div>
                <div class="form-group"><label for="email">Email</label><input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required></div>
                <div class="form-group"><label for="role">Role</label><select id="role" name="role" class="form-control" required>
                    <?php foreach ($roles as $r): ?><option value="<?= $r ?>" <?= $user['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option><?php endforeach; ?>
                </select></div>
                <div class="form-group"><label for="status">Account Status</label><select id="status" name="status" class="form-control" required>
                    <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="suspended" <?= $user['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                </select></div>
                <div class="form-group"><label for="new_password">Reset Password</label><input type="password" id="new_password" name="new_password" class="form-control" placeholder="Leave blank to keep current password" autocomplete="new-password"></div>
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save Changes</button>
                <a href="?page=superadmindash" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
