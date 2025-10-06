<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../config/connection.php';
// Handle registration from dashboard (before any output)
$student_id = $_SESSION['student_id'] ?? null;
$registration_success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_module_id']) && $student_id) {
    $module_id = intval($_POST['register_module_id']);
    $check = $conn->query("SELECT * FROM student_module_enrollments WHERE student_id=$student_id AND module_id=$module_id");
    if ($check && $check->num_rows === 0) {
        $conn->query("INSERT INTO student_module_enrollments (student_id, module_id) VALUES ($student_id, $module_id)");
        header("Location: ?page=studentdash&registered=1");
        exit;
    }
}
// Get student info
$student_name = '';
$course_id = null;
if ($student_id) {
  $res = $conn->query("SELECT name, course_id FROM students WHERE student_id = $student_id");
  if ($res && $row = $res->fetch_assoc()) {
    $student_name = $row['name'];
    $course_id = $row['course_id'];
  }
}
// Fetch all modules for this student's course
$all_modules = [];
if ($course_id) {
  $res = $conn->query("SELECT module_id, module_name, module_code FROM modules WHERE course_id = $course_id");
  if ($res) {
    while ($row = $res->fetch_assoc()) {
      $all_modules[] = $row;
    }
  }
}
// Fetch registered modules for this student
$registered_modules = [];
if ($student_id) {
  $res = $conn->query("SELECT m.module_id, m.module_name, m.module_code FROM modules m INNER JOIN student_module_enrollments sm ON m.module_id = sm.module_id WHERE sm.student_id = $student_id");
  if ($res) {
    while ($row = $res->fetch_assoc()) {
      $registered_modules[$row['module_id']] = $row;
    }
  }
}
// Calculate available modules (not yet registered)
$available_modules = [];
foreach ($all_modules as $mod) {
  if (!isset($registered_modules[$mod['module_id']])) {
    $available_modules[] = $mod;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Student Dashboard</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .student-welcome {
      position: absolute;
      right: 2.5rem;
      top: 1.2rem;
      font-size: 1.1rem;
      font-weight: 600;
      color: #2a3a4a;
      z-index: 10;
    }
    .home-btn-advanced, .btn-home-mobile {
      background: #007bff !important;
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
    .home-btn-advanced:hover, .btn-home-mobile:hover {
      background: #0056b3 !important;
    }
    @media (max-width: 600px) {
      .home-btn-advanced, .btn-home-mobile {
        width: 38px;
        height: 38px;
        font-size: 1.05rem;
        top: 10px;
        left: 10px;
      }
    }
    @media (max-width: 600px) {
      .student-welcome { right: 0.5rem; top: 0.5rem; font-size: 0.98em; }
      .content-wrapper, .container-fluid { padding: 0.5rem !important; }
      .row.mb-4 { margin-bottom: 0.7rem !important; }
      .card, .small-box { border-radius: 0.7rem !important; }
      .card-header, .card-body { padding: 0.7rem 0.7rem !important; }
      .list-group-item { font-size: 0.98em; padding: 0.5rem 0.7rem; }
      .small-box .inner h4 { font-size: 1.1rem; }
      .small-box .inner p { font-size: 0.98rem; }
    }
  </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-light border-bottom position-relative">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="#" class="nav-link">Home</a>
         <li class="nav-item"><a href="?page=logout" class="nav-link text-danger">Logout</a></li>
      </li>
    </ul>
    <div class="student-welcome">
      <?php if ($student_name): ?>
        Welcome, <span style="color:#007bff;"><i class="fas fa-user-graduate"></i> <?= htmlspecialchars($student_name) ?></span>
      <?php else: ?>
        Welcome, Student
      <?php endif; ?>
    </div>
  </nav>
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="#" class="brand-link text-center">
      <span class="brand-text font-weight-bold">Student Portal</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="true">
          
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-home"></i>
              <p>Home</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="?page=register_module" class="nav-link">
              <i class="nav-icon fas fa-plus-circle"></i>
              <p>Register Module</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="?page=drop_module" class="nav-link">
              <i class="nav-icon fas fa-minus-circle"></i>
              <p>Drop Module</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="?page=view_announcements" class="nav-link bg-warning text-dark font-weight-bold rounded shadow-sm" style="margin: 0.2rem 0;">
              <i class="nav-icon fas fa-bullhorn"></i>
              <p style="display:inline; margin-left: 0.5rem;">View Announcements</p>
              <span class="badge badge-danger ml-2" style="font-size:0.8em;">New</span>
            </a>
          </li>

        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Student Dashboard</h1>
          </div>
        </div>
      </div>
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
      <div class="container-fluid">
        <div class="row mb-4">
          <div class="col-md-6 mb-3">
            <div class="card h-100">
              <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-layer-group"></i> All Modules in Your Course</h5>
              </div>
              <div class="card-body">
                <?php if (count($all_modules) > 0): ?>
                  <ul class="list-group">
                    <?php foreach ($all_modules as $mod): ?>
                      <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?= htmlspecialchars($mod['module_name']) ?>
                        <span class="badge badge-dark badge-pill"> <?= htmlspecialchars($mod['module_code']) ?> </span>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php else: ?>
                  <div class="alert alert-secondary mb-0">No modules found for your course.</div>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <div class="col-md-6 mb-3">
            <div class="card h-100">
              <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-book"></i> Registered Modules</h5>
              </div>
              <div class="card-body">
                <?php if (count($registered_modules) > 0): ?>
                  <ul class="list-group">
                    <?php foreach ($registered_modules as $mod): ?>
                      <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?= htmlspecialchars($mod['module_name']) ?>
                        <span class="badge badge-info badge-pill"> <?= htmlspecialchars($mod['module_code']) ?> </span>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php else: ?>
                  <div class="alert alert-warning mb-0">You have not registered for any modules yet.</div>
                <?php endif; ?>
              </div>
            </div>
          </div>

        </div>
        <!-- Dashboard cards -->
        <div class="row">
          <div class="col-lg-4 col-6">
            <div class="small-box bg-info">
              <div class="inner">
                <h4>Register Module</h4>
                <p>Add new modules to your course</p>
              </div>
              <div class="icon">
                <i class="fas fa-plus-circle"></i>
              </div>
              <a href="?page=register_module" class="small-box-footer">Go <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <div class="col-lg-4 col-6">
            <div class="small-box bg-danger">
              <div class="inner">
                <h4>Drop Module</h4>
                <p>Remove modules you no longer want</p>
              </div>
              <div class="icon">
                <i class="fas fa-minus-circle"></i>
              </div>
              <a href="?page=drop_module" class="small-box-footer">Go <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <div class="col-lg-4 col-6">
            <div class="small-box" style="background: linear-gradient(135deg, #43e97b 60%, #38f9d7 100%); box-shadow: 0 2px 12px #43e97b99; border-radius: 1rem;">
              <div class="inner">
                <h4 style="color:#006d3c;"><i class="fas fa-bullhorn"></i> Announcements</h4>
                <p style="color:#006d3c;">View latest news from instructors</p>
              </div>
              <div class="icon">
                <i class="fas fa-bullhorn" style="color:#00c853;"></i>
              </div>
              <a href="?page=view_announcements" class="small-box-footer font-weight-bold text-success" style="font-size:1.1em;">Go <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
        <!-- Debug Section: Show raw registered modules data -->
        <div class="row mt-4">
          <div class="col-12">
            <div class="card border-danger">
              <div class="card-header bg-danger text-white">
                <strong>Debug: Raw Registered Modules Data</strong>
              </div>
              <div class="card-body">
                <pre style="font-size:0.95em; color:#b71c1c; background:#fff3e0; border-radius:0.5em; padding:1em;">
<?php print_r($registered_modules); ?>
                </pre>
              </div>
            </div>
          </div>
        </div>
        </div>
      </div>
    </div>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

  <!-- Footer -->
  <footer class="main-footer text-center">
    <strong>&copy; 2025 Student Portal</strong>
  </footer>

</div>
<!-- ./wrapper -->

<!-- Required JS -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>
