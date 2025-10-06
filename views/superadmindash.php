<?php
// superadmindash.php - Superadmin dashboard for user management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['superadmin', 'admin'])) {
    header('Location: ?page=login');
    exit;
}
require_once __DIR__ . '/../config/connection.php';
$conn = getDBConnection();

// Fetch current superadmin's name for the welcome message
$current_user_name = 'Admin';
$user_id = $_SESSION['user_id'] ?? 0;
$name_stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$name_stmt->bind_param("i", $user_id);
if ($name_stmt->execute()) {
    $name_result = $name_stmt->get_result();
    if ($user_row = $name_result->fetch_assoc()) {
        $current_user_name = $user_row['name'];
    }
}
$name_stmt->close();

// --- Fetch Dashboard Stats ---
$total_users = $conn->query("SELECT COUNT(id) as count FROM users")->fetch_assoc()['count'] ?? 0;
$total_instructors = $conn->query("SELECT COUNT(id) as count FROM users WHERE role = 'instructor'")->fetch_assoc()['count'] ?? 0;
$total_students = $conn->query("SELECT COUNT(student_id) as count FROM students")->fetch_assoc()['count'] ?? 0;
$total_courses = $conn->query("SELECT COUNT(course_id) as count FROM courses")->fetch_assoc()['count'] ?? 0;
$total_modules = $conn->query("SELECT COUNT(module_id) as count FROM modules")->fetch_assoc()['count'] ?? 0;
$active_users = $conn->query("SELECT COUNT(id) as count FROM users WHERE status = 'active'")->fetch_assoc()['count'] ?? 0;
$suspended_users = $conn->query("SELECT COUNT(id) as count FROM users WHERE status = 'suspended'")->fetch_assoc()['count'] ?? 0;

// --- User Management Logic (Pagination & Search) ---
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$results_per_page = 10;
$offset = ($page - 1) * $results_per_page;

$base_where = '';
if ($_SESSION['role'] === 'admin') {
    // Admins cannot see superadmins
    $base_where = " WHERE role != 'superadmin'";
}

$where_clause = $base_where;
$params = [];
$types = '';
if (!empty($search_term)) {
    $search_where = " (name LIKE ? OR email LIKE ? OR role LIKE ?)";
    $where_clause .= (empty($base_where) ? ' WHERE' : ' AND') . $search_where;

    $like_term = "%" . $search_term . "%";
    $params = [$like_term, $like_term, $like_term];
    $types = 'sss';
}

// Get total number of users for pagination
$total_sql = "SELECT COUNT(id) as total FROM users" . $where_clause;
$total_stmt = $conn->prepare($total_sql);
if (!empty($search_term)) $total_stmt->bind_param($types, ...$params);
$total_stmt->execute();
$total_users_filtered = $total_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_users_filtered / $results_per_page);
$total_stmt->close();

// Fetch paginated user data
$sql = "SELECT id, name, email, role, status, created_at FROM users" . $where_clause . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$types .= 'ii';
$params[] = $results_per_page;
$params[] = $offset;
$stmt->bind_param($types, ...$params);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch recent instructor announcements
$instructor_announcements = [];
try {
    $ann_sql = "SELECT title, content, created_at FROM instructor_announcements ORDER BY created_at DESC LIMIT 5";
    $ann_result = $conn->query($ann_sql);
    if ($ann_result) {
        $instructor_announcements = $ann_result->fetch_all(MYSQLI_ASSOC);
    }
} catch (mysqli_sql_exception $e) {
    // Table might not exist, so we'll just have an empty array. No need to crash.
}

$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Superadmin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <style>
        body { background: #f8fafc; }
        .content-wrapper { background-color: #f4f6f9; }
        .small-box { border-radius: .75rem; box-shadow: 0 0 1px rgba(0,0,0,.125),0 1px 3px rgba(0,0,0,.2); }
        .table thead th { background-color: #343a40; color: #fff; }
        .btn-role { font-size: 0.95rem; }
        .superadmin-header { font-size: 2rem; font-weight: 700; color: #2a3a4a; margin-bottom: 1.5rem; }
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
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a></li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <span class="navbar-text">
                    <span class="online-indicator"></span> Welcome, <strong><?= htmlspecialchars($current_user_name) ?>!</strong>
                </span>
            </li>
            <li class="nav-item ml-3"><a class="btn btn-danger btn-sm" href="?page=logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="?page=superadmindash" class="brand-link text-center">
            <span class="brand-text font-weight-bold"><i class="fas fa-user-shield"></i> Admin Panel</span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item"><a href="?page=superadmindash" class="nav-link active"><i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p></a></li>
                    <li class="nav-header">MANAGEMENT</li>
                    <li class="nav-item"><a href="?page=total_students" class="nav-link"><i class="nav-icon fas fa-user-graduate"></i><p>Students</p></a></li>
                    <li class="nav-item"><a href="?page=manage_instructors" class="nav-link"><i class="nav-icon fas fa-chalkboard-teacher"></i><p>Instructors</p></a></li>
                    <li class="nav-item"><a href="?page=manage_courses" class="nav-link"><i class="nav-icon fas fa-book"></i><p>Courses</p></a></li>
                    <li class="nav-item"><a href="?page=manage_modules" class="nav-link"><i class="nav-icon fas fa-layer-group"></i><p>Modules</p></a></li>
                    <li class="nav-header">COMMUNICATION</li>
                    <li class="nav-item"><a href="?page=post_instructor_announcement" class="nav-link"><i class="nav-icon fas fa-bullhorn"></i><p>Instructor Announcements</p></a></li>
                    <li class="nav-item"><a href="?page=post_announcement" class="nav-link"><i class="nav-icon fas fa-bullhorn"></i><p>Student Announcements</p></a></li>
                    <li class="nav-header">USERS</li>
                    <li class="nav-item"><a href="?page=add_user" class="nav-link"><i class="nav-icon fas fa-user-plus"></i><p>Add New User</p></a></li>
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <h1 class="m-0">Admin Dashboard</h1>
            </div>
        </div>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Small boxes (Stat cards) -->
                <div class="card card-primary card-tabs">
                    <div class="card-header p-0 pt-1">
                        <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                            <li class="nav-item"><a class="nav-link active" id="tab-users-tab" data-toggle="pill" href="#tab-users" role="tab"><i class="fas fa-users-cog"></i> User Management</a></li>
                            <li class="nav-item"><a class="nav-link" id="tab-stats-tab" data-toggle="pill" href="#tab-stats" role="tab"><i class="fas fa-chart-bar"></i> System Statistics</a></li>
                            <li class="nav-item"><a class="nav-link" id="tab-announcements-tab" data-toggle="pill" href="#tab-announcements" role="tab"><i class="fas fa-bullhorn"></i> Announcements</a></li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="custom-tabs-one-tabContent">
                            <!-- User Management Tab -->
                            <div class="tab-pane fade show active" id="tab-users" role="tabpanel">
                                <?php if ($flash_message): ?><div class="alert alert-success mb-3"><?= $flash_message ?></div><?php endif; ?>
                                <div class="d-flex justify-content-between mb-3">
                                    <h4>All System Users (<?= $total_users_filtered ?>)</h4>
                                    <a href="?page=add_user" class="btn btn-success"><i class="fas fa-user-plus"></i> Add New User</a>
                                </div>
                                <form method="GET" class="mb-3">
                                    <input type="hidden" name="page" value="superadmindash">
                                    <div class="input-group">
                                        <input type="text" name="search" class="form-control" placeholder="Search by name, email, or role..." value="<?= htmlspecialchars($search_term) ?>">
                                        <div class="input-group-append"><button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button></div>
                                    </div>
                                </form>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr>
                                        </thead>
                                        <tbody>
                                        <?php if (count($users) > 0): ?>
                                            <?php foreach ($users as $user): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($user['name']) ?></td>
                                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                                    <td><span class="badge badge-info"><?= ucfirst(htmlspecialchars($user['role'])) ?></span></td>
                                                    <td>
                                                        <?php if ($user['status'] == 'active'): ?>
                                                            <span class="badge badge-success">Active</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-danger">Suspended</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="?page=edit_user&id=<?= $user['id'] ?>" class="btn btn-xs btn-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                                        <?php if ($user['id'] != $_SESSION['user_id']): // Can't delete self ?>
                                                        <form action="?page=delete_user" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete this user? This cannot be undone.');">
                                                            <input type="hidden" name="delete_user_id" value="<?= $user['id'] ?>">
                                                            <button type="submit" class="btn btn-xs btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                                        </form>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="5" class="text-center">No users found.</td></tr>
                                        <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- Pagination -->
                                <nav class="mt-3">
                                    <ul class="pagination justify-content-center">
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=superadmindash&p=<?= $i ?>&search=<?= urlencode($search_term) ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>
                                    </ul>
                                </nav>
                            </div>
                            <!-- System Statistics Tab -->
                            <div class="tab-pane fade" id="tab-stats" role="tabpanel">
                                <div class="row">
                                    <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3><?= $total_users ?></h3><p>Total Users</p></div><div class="icon"><i class="fas fa-users"></i></div></div></div>
                                    <div class="col-lg-3 col-6"><div class="small-box bg-success"><div class="inner"><h3><?= $active_users ?></h3><p>Active Users</p></div><div class="icon"><i class="fas fa-user-check"></i></div></div></div>
                                    <div class="col-lg-3 col-6"><div class="small-box bg-danger"><div class="inner"><h3><?= $suspended_users ?></h3><p>Suspended Users</p></div><div class="icon"><i class="fas fa-user-slash"></i></div></div></div>
                                    <div class="col-lg-3 col-6"><div class="small-box bg-primary"><div class="inner"><h3><?= $total_instructors ?></h3><p>Instructors</p></div><div class="icon"><i class="fas fa-chalkboard-teacher"></i></div></div></div>
                                    <div class="col-lg-3 col-6"><div class="small-box bg-warning"><div class="inner"><h3><?= $total_students ?></h3><p>Students</p></div><div class="icon"><i class="fas fa-user-graduate"></i></div></div></div>
                                    <div class="col-lg-3 col-6"><div class="small-box bg-secondary"><div class="inner"><h3><?= $total_courses ?></h3><p>Courses</p></div><div class="icon"><i class="fas fa-book"></i></div></div></div>
                                    <div class="col-lg-3 col-6"><div class="small-box bg-dark"><div class="inner"><h3><?= $total_modules ?></h3><p>Modules</p></div><div class="icon"><i class="fas fa-layer-group"></i></div></div></div>
                                </div>
                            </div>
                            <!-- Announcements Tab -->
                            <div class="tab-pane fade" id="tab-announcements" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card card-outline card-info">
                                            <div class="card-header"><h3 class="card-title"><i class="fas fa-bullhorn mr-1"></i> Instructor Announcements</h3></div>
                                            <div class="card-body">
                                                <?php if (empty($instructor_announcements)): ?>
                                                    <p class="text-muted">No announcements for instructors yet.</p>
                                                <?php else: ?>
                                                    <?php foreach($instructor_announcements as $ann): ?>
                                                        <div class="post">
                                                            <div class="user-block">
                                                                <span class="username ml-0"><a href="#"><?= htmlspecialchars($ann['title']) ?></a></span>
                                                                <span class="description ml-0">Posted on <?= date('M d, Y', strtotime($ann['created_at'])) ?></span>
                                                            </div>
                                                            <p><?= htmlspecialchars(substr(strip_tags(html_entity_decode($ann['content'])), 0, 100)) ?>...</p>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="card-footer text-center">
                                                <a href="?page=post_instructor_announcement" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Post to Instructors</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card card-outline card-warning">
                                            <div class="card-header"><h3 class="card-title"><i class="fas fa-bullhorn mr-1"></i> Student Announcements</h3></div>
                                            <div class="card-body">
                                                <p class="text-muted">Feature to view student announcements can be added here.</p>
                                            </div>
                                            <div class="card-footer text-center">
                                                <a href="?page=post_announcement" class="btn btn-warning"><i class="fas fa-plus-circle"></i> Post to Students</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <footer class="main-footer">
        <strong>&copy; 2025 UniCourse Management System.</strong> All rights reserved.
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
