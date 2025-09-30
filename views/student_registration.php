<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Registration</title>

  <!-- AdminLTE & Bootstrap CSS via CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body class="hold-transition login-page">

  <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="card card-primary w-100" style="max-width: 500px;">
      <div class="card-header text-center">
        <h3 class="card-title w-100">Student Registration</h3>
      </div>
      <div class="card-body">
        <form action="userstudent_controller.php" method="POST">

          <div class="form-group">
            <label for="name">Name</label>
            <!-- Fixed name attribute to match PHP -->
            <input type="text" class="form-control" id="name" name="name" autocomplete="name" required>
          </div>

          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" class="form-control" id="email" name="email" autocomplete="email" required>
          </div>

          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" class="form-control" id="password" name="password" minlength="6" required>
          </div>

          <div class="form-group">
            <button type="submit" class="btn btn-primary btn-block">Register</button>
          </div>

        </form>
        <p class="text-center mb-0">
          Already have an account? <a href="login.php">Login here</a>
        </p>
      </div>
    </div>
  </div>

  <!-- Required JS -->
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>
