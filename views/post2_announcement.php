<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    die("❌ Only logged-in instructors can post announcements.");
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];

    $sql = "INSERT INTO announcements (title, content,created_at, user_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $title, $content,$created_at, $user_id);
    if ($stmt->execute()) {
        echo "✅ Announcement posted successfully!";
    } else {
        echo "❌ Error: " . $stmt->error;
    }
    $stmt->close();
}
$conn->close();
?>
