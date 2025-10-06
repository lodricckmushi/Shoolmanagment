<?php
// delete_user.php - Superadmin deletes a user
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: ?page=login');
    exit;
}
require_once __DIR__ . '/../config/connection.php';
$conn = getDBConnection();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: ?page=superadmindash');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        header('Location: ?page=superadmindash&msg=deleted');
        exit;
    } else {
        echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($stmt->error) . '</div>';
    }
    $stmt->close();
}
?>