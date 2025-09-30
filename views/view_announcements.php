<?php
session_start();
include 'connection.php';

$sql = "SELECT a.id, a.title, a.content, a.created_at, u.name AS posted_by 
        FROM announcements a 
        JOIN users u ON a.user_id = u.id 
        ORDER BY a.created_at DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Announcements</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
</head>
<body class="hold-transition">
<div class="container mt-5">
  <h2>All Announcements</h2>
  <?php
  if ($result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
          echo "<div class='card mb-3'>";
          echo "<div class='card-header'><strong>" . htmlspecialchars($row['title']) . "</strong> by " . htmlspecialchars($row['posted_by']) . " on " . $row['created_at'] . "</div>";
          echo "<div class='card-body'>" . nl2br(htmlspecialchars($row['content'])) . "</div>";
          echo "</div>";
      }
  } else {
      echo "<p>No announcements found.</p>";
  }
  ?>
</div>
</body>
</html>

<?php $conn->close(); ?>
