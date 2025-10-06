<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['superadmin', 'admin'])) {
    header('Location: ?page=login');
    exit;
}
require_once __DIR__ . '/../config/connection.php';
$conn = getDBConnection();

$message = '';

// Handle POST requests (Add/Edit/Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Add or Update Course ---
    if (isset($_POST['course_name'])) {
        $course_name = trim($_POST['course_name']);
        $course_code = trim($_POST['course_code']);
        $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
        $course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;

        if ($course_id > 0) { // Update
            $stmt = $conn->prepare("UPDATE courses SET course_name = ?, course_code = ?, department_id = ? WHERE course_id = ?");
            $stmt->bind_param("ssii", $course_name, $course_code, $department_id, $course_id);
            $message = "Course updated successfully!";
        } else { // Insert
            $stmt = $conn->prepare("INSERT INTO courses (course_name, course_code, department_id) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $course_name, $course_code, $department_id);
            $message = "Course added successfully!";
        }
        if (!$stmt->execute()) {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    // --- Delete Course ---
    if (isset($_POST['delete_course_id'])) {
        $course_id = (int)$_POST['delete_course_id'];
        $stmt = $conn->prepare("DELETE FROM courses WHERE course_id = ?");
        $stmt->bind_param("i", $course_id);
        if ($stmt->execute()) {
            $message = "Course deleted successfully!";
        } else {
            $message = "Error deleting course: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch all courses and departments
$courses_sql = "
    SELECT c.*, d.department_name, COUNT(m.module_id) as module_count
    FROM courses c 
    LEFT JOIN departments d ON c.department_id = d.department_id 
    LEFT JOIN modules m ON c.course_id = m.course_id
    GROUP BY c.course_id ORDER BY c.course_name";
$courses = $conn->query($courses_sql)->fetch_all(MYSQLI_ASSOC);
$departments = $conn->query("SELECT * FROM departments ORDER BY department_name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Courses</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="?page=superadmindash"><i class="fas fa-user-shield"></i> Admin Panel</a>
    <ul class="navbar-nav ml-auto">
        <li class="nav-item"><a class="nav-link" href="?page=superadmindash"><i class="fas fa-arrow-left"></i> Back to Dashboard</a></li>
    </ul>
</nav>

<div class="container mt-4">
    <h2><i class="fas fa-book"></i> Manage Courses</h2>
    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Add/Edit Course Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Add New Course</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="form-row">
                    <div class="col-md-4 form-group">
                        <label>Course Name</label>
                        <input type="text" name="course_name" class="form-control" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Course Code</label>
                        <input type="text" name="course_code" class="form-control" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Department</label>
                        <select name="department_id" class="form-control">
                            <option value="">-- None --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['department_id'] ?>"><?= htmlspecialchars($dept['department_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 form-group d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-plus"></i> Add Course</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Course List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Existing Courses</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th>Course Name</th>
                        <th>Code</th>
                        <th>Department</th>
                        <th>Modules</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($courses)): ?>
                        <tr><td colspan="5" class="text-center">No courses found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($courses as $course): ?>
                            <tr>
                                <td><?= htmlspecialchars($course['course_name']) ?></td>
                                <td><?= htmlspecialchars($course['course_code']) ?></td>
                                <td><?= htmlspecialchars($course['department_name'] ?? 'N/A') ?></td>
                                <td><span class="badge badge-info"><?= $course['module_count'] ?></span></td>
                                <td class="text-right">
                                    <a href="?page=edit_course&id=<?= $course['course_id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Edit</a>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this course?');">
                                        <input type="hidden" name="delete_course_id" value="<?= $course['course_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>