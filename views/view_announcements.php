<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../config/connection.php';

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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Announcements</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%) fixed;
      min-height: 100vh;
    }
    .announcements-header {
      background: linear-gradient(90deg, #8B5C2A 0%, #b8894a 100%);
      color: #fff;
      border-radius: 1rem 1rem 0 0;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      padding: 2rem 1rem 1rem 5rem;
      margin-bottom: 2rem;
      position: relative;
    }
    .announcement-card {
      border: none;
      border-radius: 1rem;
      box-shadow: 0 2px 12px rgba(139,92,42,0.08);
      margin-bottom: 2rem;
      background: #fff;
      transition: box-shadow 0.2s;
    }
    .announcement-card:hover {
      box-shadow: 0 4px 24px rgba(139,92,42,0.18);
    }
    .announcement-title {
      font-size: 1.3rem;
      font-weight: 600;
      color: #8B5C2A;
    }
    .announcement-meta {
      font-size: 0.95rem;
      color: #b8894a;
    }
    .announcement-content {
      font-size: 1.1rem;
      color: #333;
    }
    .home-btn-advanced {
      background: #8B5C2A !important;
      color: #fff !important;
      top: 20px;
      left: 20px;
      z-index: 1050;
      border-radius: 50%;
      width: 50px;
      height: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
      position: fixed;
      border: none;
      font-size: 1.3rem;
      transition: background 0.2s;
    }
    .home-btn-advanced:hover {
      background: #6d4317 !important;
    }
    @media (max-width: 600px) {
      .home-btn-advanced {
        width: 38px;
        height: 38px;
        font-size: 1.05rem;
        top: 10px;
        left: 10px;
      }
    }
    @media (max-width: 600px) {
      .announcements-header { padding: 1.5rem 1rem 1rem 1rem; text-align: center; }
      .announcements-header h2 { font-size: 1.5rem; }
      .home-btn-advanced { width: 40px; height: 40px; font-size: 1.1rem; }
      .announcement-title { font-size: 1.05rem; }
      .announcement-meta { font-size: 0.85rem; }
      .announcement-content { font-size: 0.98rem; padding-left: 0.75rem !important; }
      .announcement-card { margin-bottom: 1.1rem; }
    }
  </style>
</head>
<body class="hold-transition">
<!-- Home Button -->
<a href="?page=studentdash" class="btn home-btn-advanced">
  <i class="fas fa-home fa-lg"></i>
</a>
<div class="container mt-4 mb-5">
  <div class="announcements-header">
    <h2 class="mb-0"><i class="fas fa-bullhorn mr-2"></i>All Announcements</h2>
  </div>
  <?php
  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      echo "<div class='card announcement-card mb-4 shadow-sm border-left-warning'>";
      echo "  <div class='card-body'>";
      echo "    <div class='d-flex align-items-center mb-2'>";
      echo "      <span class='badge badge-warning mr-3' style='font-size:1.2em;'><i class='fas fa-bullhorn'></i></span>";
      echo "      <span class='announcement-title flex-grow-1'>" . htmlspecialchars($row['title']) . "</span>";
      echo "    </div>";
      echo "    <div class='announcement-meta mb-2'><i class='fas fa-user-tie mr-1'></i>By <span class='font-weight-bold text-primary'>" . htmlspecialchars($row['posted_by']) . "</span> <span class='mx-2 text-muted'>|</span> <i class='far fa-clock mr-1'></i>" . date('M d, Y H:i', strtotime($row['created_at'])) . "</div>";
      echo "    <div class='announcement-content pl-4' style='border-left:3px solid #ffe082;'>" . nl2br(htmlspecialchars($row['content'])) . "</div>";
      echo "  </div>";
      echo "</div>";
    }
  } else {
    echo "<div class='alert alert-warning'>No announcements found.</div>";
  }
  ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
