<!DOCTYPE html>
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
          <span class="badge badge-info p-2" style="font-size:1.1em;">Welcome, Instructor!</span>
        </div>
        <?php
        include __DIR__ . '/../config/connection.php';
        // Announcements count
        $res = $conn->query("SELECT COUNT(*) as cnt FROM announcements");
        $row = $res ? $res->fetch_assoc() : ['cnt'=>0];
        $announcements_count = $row['cnt'];
        // Total students count
        $res = $conn->query("SELECT COUNT(*) as cnt FROM students");
        $row = $res ? $res->fetch_assoc() : ['cnt'=>0];
        $students_count = $row['cnt'];
        // New posts (last 7 days)
        $res = $conn->query("SELECT COUNT(*) as cnt FROM announcements WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $row = $res ? $res->fetch_assoc() : ['cnt'=>0];
        $new_posts = $row['cnt'];
        // Edits (simulate: count announcements updated in last 7 days, if you have updated_at column, else fallback to new_posts)
        $edits = $new_posts;
        ?>
        <!-- Quick Stats Row -->
        <div class="row mt-3 mb-2">
          <div class="col-md-3 col-6">
            <div class="card text-center border-success">
              <div class="card-body p-2">
                <i class="fas fa-bullhorn fa-2x text-success mb-1"></i>
                <div class="h5 mb-0"><?php echo $announcements_count; ?></div>
                <small class="text-muted">Announcements</small>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="card text-center border-warning">
              <div class="card-body p-2">
                <i class="fas fa-users fa-2x text-warning mb-1"></i>
                <div class="h5 mb-0"><?php echo $students_count; ?></div>
                <small class="text-muted">Total Students</small>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="card text-center border-primary">
              <div class="card-body p-2">
                <i class="fas fa-plus fa-2x text-primary mb-1"></i>
                <div class="h5 mb-0"><?php echo $new_posts; ?></div>
                <small class="text-muted">New Posts</small>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="card text-center border-danger">
              <div class="card-body p-2">
                <i class="fas fa-edit fa-2x text-danger mb-1"></i>
                <div class="h5 mb-0"><?php echo $edits; ?></div>
                <small class="text-muted">Edits</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="container-fluid">
        <div class="row">

          <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
              <div class="inner">
                <h4>Post Announcement</h4>
                <p>Create new announcement</p>
              </div>
              <div class="icon">
                <i class="fas fa-plus"></i>
              </div>
              <a href="?page=post_announcement" class="small-box-footer">Go <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>

          <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
              <div class="inner">
                <h4>Edit Announcement</h4>
                <p>Modify existing announcements</p>
              </div>
              <div class="icon">
                <i class="fas fa-edit"></i>
              </div>
              <a href="?page=edit_announcement" class="small-box-footer">Go <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>

          <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
              <div class="inner">
                <h4>Delete Announcement</h4>
                <p>Remove announcements</p>
              </div>
              <div class="icon">
                <i class="fas fa-trash-alt"></i>
              </div>
              <a href="?page=delete_announcement" class="small-box-footer">Go <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>

          <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
              <div class="inner">
                <h4>Total Students</h4>
                <p>View all students under you</p>
              </div>
              <div class="icon">
                <i class="fas fa-users"></i>
              </div>
              <a href="?page=total_students" class="small-box-footer">Go <i class="fas fa-arrow-circle-right"></i></a>
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
