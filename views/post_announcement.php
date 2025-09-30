<?php
session_start();
include 'connection.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("❌ You must be logged in to post an announcement.");
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];

    // Insert with user_id and created_at
    $sql = "INSERT INTO announcements (title, content, user_id, created_at) 
            VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ssi", $title, $content, $user_id);

    if ($stmt->execute()) {
        echo "✅ Announcement posted successfully!";
    } else {
        echo "❌ Error: " . $stmt->error;
    }

    $stmt->close();
}
$conn->close();
?>
