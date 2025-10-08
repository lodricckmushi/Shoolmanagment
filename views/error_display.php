<?php
// Default error messages
$error_title = isset($error_title) ? $error_title : "404 - Not Found";
$error_message = isset($error_message) ? $error_message : "Sorry, the resource you are looking for does not exist or has been moved.";
$home_page = isset($_SESSION['role']) && $_SESSION['role'] === 'instructor' ? '?page=instructordash' : '?page=studentdash';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($error_title) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      background: linear-gradient(135deg, rgba(24,28,32,0.95) 60%, rgba(255,111,60,0.15) 100%);
      color: #fff;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      font-family: 'Segoe UI', sans-serif;
    }
    .error-container {
      text-align: center;
      background: rgba(30, 30, 30, 0.55);
      border-radius: 2rem;
      box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
      padding: 2.5rem 2rem 2rem 2rem;
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      border: 1.5px solid rgba(255,255,255,0.12);
      max-width: 450px;
      margin: 2rem auto;
    }
    .error-img {
      max-width: 260px;
      width: 100%;
      margin-bottom: 2rem;
      filter: drop-shadow(0 4px 24px #ff6f3c88);
      border-radius: 1.5rem;
      background: rgba(255,255,255,0.08);
      padding: 0.5rem;
    }
    .error-title {
      font-size: 2.2rem;
      font-weight: 700;
      color: #ff6f3c;
      letter-spacing: 1px;
      margin-bottom: 0.5rem;
    }
    .error-desc {
      font-size: 1.15rem;
      color: #e0e0e0;
      margin-bottom: 2rem;
    }
    .btn-home {
      background: linear-gradient(90deg, #ff6f3c 60%, #ff3c3c 100%);
      color: #fff;
      border: none;
      border-radius: 2rem;
      padding: 0.85rem 2.8rem;
      font-size: 1.15rem;
      font-weight: 600;
      box-shadow: 0 4px 16px #ff6f3c33;
      transition: background 0.2s, transform 0.2s;
      text-shadow: 0 1px 4px #0008;
      letter-spacing: 0.5px;
    }
    .btn-home:hover {
      background: linear-gradient(90deg, #ff3c3c 60%, #ff6f3c 100%);
      color: #fff;
      transform: scale(1.05);
    }
  </style>
</head>
<body>
  <div class="error-container">
  <img src="/assets/images/404.png" alt="Error Illustration" class="error-img" loading="lazy" onerror="this.style.display='none'">
    <div class="error-title"><?= htmlspecialchars($error_title) ?></div>
    <div class="error-desc"><?= $error_message /* No htmlspecialchars, allows for <br> */ ?></div>
  <a href="<?= $home_page ?>" class="btn btn-home"><i class="fas fa-home mr-2"></i>Go Home</a>
  </div>
</body>
</html>