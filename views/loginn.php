<?php
$error_message = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'suspended':
            $error_message = 'Your account has been suspended. Please contact an administrator.';
            break;
        case 'invalid':
            $error_message = 'Invalid email or password. Please try again.';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
<div class="login-box">
  <div class="card card-outline card-primary">
    <div class="card-header text-center"><b>Login</b></div>
    <div class="card-body">
      <?php if ($error_message): ?><div class="alert alert-danger text-center"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>
      <form action="?page=login2_controller" method="POST">
        <div class="input-group mb-3">
          <input type="email" name="email" class="form-control" placeholder="Email" required>
          <div class="input-group-append">
            <div class="input-group-text"><span class="fas fa-envelope"></span></div>
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="password" name="password" class="form-control" placeholder="Password" required>
          <div class="input-group-append">
            <div class="input-group-text"><span class="fas fa-lock"></span></div>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Login</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
