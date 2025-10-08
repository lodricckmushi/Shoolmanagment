<!DOCTYPE html>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../config/connection.php';

// Ensure instructor is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor' || !isset($_SESSION['email'])) {
    header('Location: ?page=login');
    exit;
}

$conn = getDBConnection();
$instructor_name = '';
$instructor_id = null;

$stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ? AND role = 'instructor' LIMIT 1");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $user = $result->fetch_assoc()) {
    $instructor_name = $user['name'];
    $instructor_id = $user['id'];
}
$stmt->close();

// Fetch assigned courses, their modules, and their students
$assigned_courses = [];
if ($instructor_id) {
    // 1. Get assigned courses
    $courses_stmt = $conn->prepare("SELECT c.course_id, c.course_name, c.course_code FROM courses c JOIN course_instructor_assignments cia ON c.course_id = cia.course_id WHERE cia.instructor_id = ?");
    $courses_stmt->bind_param("i", $instructor_id);
    $courses_stmt->execute();
    $courses_result = $courses_stmt->get_result();

    while ($course = $courses_result->fetch_assoc()) {
        $course_id = $course['course_id'];
        
        // 2. Get modules for this course
        $modules_stmt = $conn->prepare("SELECT module_name, module_code FROM modules WHERE course_id = ? ORDER BY module_name");
        $modules_stmt->bind_param("i", $course_id);
        $modules_stmt->execute();
        $course['modules'] = $modules_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $modules_stmt->close();

        // 3. Get students for this course
        $students_stmt = $conn->prepare("SELECT s.name, s.email, s.semester FROM students s JOIN student_course_enrollments sce ON s.student_id = sce.student_id WHERE sce.course_id = ? ORDER BY s.name");
        $students_stmt->bind_param("i", $course_id);
        $students_stmt->execute();
        $course['students'] = $students_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $students_stmt->close();

        $assigned_courses[] = $course;
    }
    $courses_stmt->close();
}
?>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Instructor Dashboard</title>

  <!-- AdminLTE & Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .online-indicator {
        display: inline-block;
        width: 10px;
        height: 10px;
        background-color: #28a745;
        border-radius: 50%;
        animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
        100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
    }
    .course-card {
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        border-radius: 0.75rem;
        border: none;
    }
    .nav-pills .nav-link.active { background-color: #007bff; }
  </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-light border-bottom">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="#" class="nav-link">Home</a>
        <li class="nav-item"><a href="?page=logout" class="nav-link text-danger">Logout</a></li>
      </li>
    </ul>
  </nav>

  <!-- Sidebar -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="#" class="brand-link text-center">
      <span class="brand-text font-weight-bold">Instructor Portal</span>
    </a>

    <div class="sidebar">
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="true">

          <li class="nav-item">
            <a href="#?page=instructordash" class="nav-link">
              <i class="nav-icon fas fa-home"></i>
              <p>Home</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="?page=post_announcement" class="nav-link">
              <i class="nav-icon fas fa-plus"></i>
              <p>Post Announcement</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="?page=edit_announcement" class="nav-link">
              <i class="nav-icon fas fa-edit"></i>
              <p>Edit Announcement</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="?page=delete_announcement" class="nav-link">
              <i class="nav-icon fas fa-trash-alt"></i>
              <p>Delete Announcement</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="?page=total_students" class="nav-link">
              <i class="nav-icon fas fa-users"></i>
              <p>Total Students</p>
            </a>
          </li>

        </ul>
      </nav>
    </div>
  </aside>

  <!-- Content Wrapper -->
  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
          <h1 class="m-0">Instructor Dashboard</h1>
          <span class="badge badge-light p-2" style="font-size:1.1em; font-weight: 600;">
            <span class="online-indicator"></span>
            Welcome, <?= htmlspecialchars($instructor_name ?: 'Instructor') ?>!
          </span>
        </div>
        <?php
        // Fetch announcements
        $ann_sql = "SELECT ia.title, ia.content, ia.created_at, u.name as author_name 
                    FROM instructor_announcements ia
                    JOIN users u ON ia.user_id = u.id
                    ORDER BY ia.created_at DESC LIMIT 10";
        $ann_res = $conn->query($ann_sql);
        $announcements = $ann_res ? $ann_res->fetch_all(MYSQLI_ASSOC) : [];
        ?>
      </div>
    </div>

    <div class="content">
      <div class="container-fluid">
        <?php if (empty($assigned_courses)): ?>
            <div class="alert alert-warning text-center">You are not currently assigned to any courses.</div>
        <?php else: ?>
            <div class="row">
                <div class="col-12">
                    <div class="card course-card card-primary card-tabs">
                        <div class="card-header p-0 pt-1">
                            <ul class="nav nav-tabs" id="course-tabs" role="tablist">
                                <?php foreach ($assigned_courses as $index => $course): ?>
                                    <li class="nav-item"><a class="nav-link <?= $index == 0 ? 'active' : '' ?>" id="tab-<?= $course['course_id'] ?>-tab" data-toggle="pill" href="#tab-<?= $course['course_id'] ?>" role="tab"><?= htmlspecialchars($course['course_name']) ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="course-tabs-content">
                                <?php foreach ($assigned_courses as $index => $course): ?>
                                    <div class="tab-pane fade <?= $index == 0 ? 'show active' : '' ?>" id="tab-<?= $course['course_id'] ?>" role="tabpanel">
                                        <h4>Course Code: <span class="text-muted"><?= htmlspecialchars($course['course_code']) ?></span></h4>
                                        <hr>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <h5><i class="fas fa-layer-group text-info"></i> Modules</h5>
                                                <ul class="list-group">
                                                    <?php foreach ($course['modules'] as $module): ?>
                                                        <li class="list-group-item"><?= htmlspecialchars($module['module_name']) ?> (<?= htmlspecialchars($module['module_code']) ?>)</li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                            <div class="col-md-8">
                                                <h5><i class="fas fa-user-graduate text-warning"></i> Enrolled Students (<?= count($course['students']) ?>)</h5>
                                                <div class="table-responsive" style="max-height: 400px;">
                                                    <table class="table table-sm table-striped">
                                                        <thead><tr><th>Name</th><th>Email</th><th>Semester</th></tr></thead>
                                                        <tbody>
                                                            <?php foreach ($course['students'] as $student): ?>
                                                                <tr>
                                                                    <td><?= htmlspecialchars($student['name']) ?></td>
                                                                    <td><?= htmlspecialchars($student['email']) ?></td>
                                                                    <td><?= htmlspecialchars($student['semester'] ?? 'N/A') ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header"><h3 class="card-title"><i class="fas fa-bullhorn"></i> Recent Staff Announcements</h3></div>
                    <div class="card-body">
                        <?php if (empty($announcements)): ?>
                            <p class="text-muted">No announcements have been posted yet.</p>
                        <?php else: ?>
                            <?php foreach($announcements as $ann): ?>
                                <div class="post mb-4 border-bottom pb-3">
                                    <div class="user-block">
                                        <span class="username ml-0"><a href="#"><?= htmlspecialchars($ann['title']) ?></a></span>
                                        <span class="description ml-0">Posted on <?= date('M d, Y', strtotime($ann['created_at'])) ?> by <strong><?= htmlspecialchars($ann['author_name'] ?? 'Admin') ?></strong></span>
                                    </div>
                                    <div><?= html_entity_decode($ann['content']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
      </div>
    </div>

  </div>

  <!-- Footer -->
  <footer class="main-footer text-center">
    <strong>&copy; 2025 Instructor Portal</strong>
  </footer>

</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>
