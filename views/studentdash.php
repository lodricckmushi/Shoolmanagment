<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../config/connection.php';

// Ensure student is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student' || !isset($_SESSION['email'])) {
    header('Location: ?page=login');
    exit;
}

// Get student info based on session email
$student_id = null;
$student_name = '';
$course_id = null;
$email = $_SESSION['email'];

$stmt = $conn->prepare("SELECT s.student_id, u.name, sce.course_id 
                        FROM users u
                        LEFT JOIN students s ON u.email = s.email
                        LEFT JOIN student_course_enrollments sce ON s.student_id = sce.student_id
                        WHERE u.email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $row = $result->fetch_assoc()) {
    $student_id = $row['student_id'];
    $student_name = $row['name'];
    $course_id = $row['course_id'];
}
$stmt->close();

// Handle module registration/dropping from dashboard (before any output)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $student_id) {
    if (isset($_POST['register_module_id'])) {
        $module_id = intval($_POST['register_module_id']);
        $stmt = $conn->prepare("INSERT INTO student_module_enrollments (student_id, module_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $student_id, $module_id);
        if ($stmt->execute()) {
            header("Location: ?page=studentdash&action=registered");
            exit;
        }
    } elseif (isset($_POST['drop_module_id'])) {
        $module_id = intval($_POST['drop_module_id']);
        $stmt = $conn->prepare("DELETE FROM student_module_enrollments WHERE student_id = ? AND module_id = ?");
        $stmt->bind_param("ii", $student_id, $module_id);
        if ($stmt->execute()) {
            header("Location: ?page=studentdash&action=dropped");
            exit;
        }
    }
}

// Fetch all modules for the course, and identify which are registered
$registered_modules = [];
$available_modules = [];
if ($course_id && $student_id) {
    $sql = "SELECT m.module_id, m.module_name, m.module_code, (sm.student_id IS NOT NULL) as is_registered
            FROM modules m
            LEFT JOIN student_module_enrollments sm ON m.module_id = sm.module_id AND sm.student_id = ?
            WHERE m.course_id = ? ORDER BY m.module_name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $student_id, $course_id);
    $stmt->execute();
    $all_modules_result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($all_modules_result as $mod) {
        $mod['is_registered'] ? ($registered_modules[] = $mod) : ($available_modules[] = $mod);
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
    .action-btn-sm {
        padding: .2rem .5rem;
        font-size: .8rem;
    }
    .online-indicator {
        display: inline-block;
        width: 10px;
        height: 10px;
        background-color: #28a745;
        border-radius: 50%;
        margin-right: 8px;
        animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
        100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
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
        <span class="online-indicator"></span>Welcome, <span style="color:#007bff;"><?= htmlspecialchars($student_name) ?></span>
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
              <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-layer-group"></i> Available Modules to Register</h5>
              </div>
              <div class="card-body">
                <?php if (count($available_modules) > 0): ?>
                  <ul class="list-group">
                    <?php foreach ($available_modules as $mod): ?>
                      <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                          <?= htmlspecialchars($mod['module_name']) ?>
                          <span class="badge badge-secondary badge-pill ml-2"><?= htmlspecialchars($mod['module_code']) ?></span>
                        </div>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="register_module_id" value="<?= $mod['module_id'] ?>">
                            <button type="submit" class="btn btn-success action-btn-sm"><i class="fas fa-plus-circle"></i> Register</button>
                        </form>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php else: ?>
                  <div class="alert alert-info mb-0">No new modules available for registration, or you may need to enroll in a course first.</div>
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
                        <div>
                          <?= htmlspecialchars($mod['module_name']) ?>
                          <span class="badge badge-info badge-pill ml-2"><?= htmlspecialchars($mod['module_code']) ?></span>
                        </div>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to drop this module?');">
                            <input type="hidden" name="drop_module_id" value="<?= $mod['module_id'] ?>">
                            <button type="submit" class="btn btn-danger action-btn-sm"><i class="fas fa-minus-circle"></i> Drop</button>
                        </form>
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
