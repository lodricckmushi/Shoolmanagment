<?php
// add_user.php - Superadmin adds a new user
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['superadmin', 'admin'])) {
    header('Location: ?page=login');
    exit;
}
require_once __DIR__ . '/../config/connection.php';
$conn = getDBConnection();

// Define roles based on the current user's role
if ($_SESSION['role'] === 'superadmin') {
    $roles = ['student', 'instructor', 'admin', 'superadmin'];
} else { // 'admin' role
    $roles = ['student', 'instructor'];
}
$preselected_role = $_GET['role'] ?? '';
$message = '';

// Fetch courses for the dropdown
$courses = $conn->query("SELECT course_id, course_name, course_code FROM courses ORDER BY course_name")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $created_by = $_SESSION['user_id'];
    $created_at = date('Y-m-d H:i:s');

    if ($name && $email && $password && in_array($role, $roles)) {
        $conn->begin_transaction();
        try {
            // 1. Insert into 'users' table
            $sql_user = "INSERT INTO users (name, email, password, role, created_at, created_by) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_user = $conn->prepare($sql_user);
            $stmt_user->bind_param('sssssi', $name, $email, $password, $role, $created_at, $created_by);
            $stmt_user->execute();
            $user_id = $stmt_user->insert_id;
            $stmt_user->close();

            // 2. If student, insert into 'students' table
            if ($role === 'student') {
                // Note: The 'students' table also has a password column, which is redundant.
                // Storing the hashed password here for consistency with student_controller.
                $sql_student = "INSERT INTO students (name, email, password) VALUES (?, ?, ?)";
                $stmt_student = $conn->prepare($sql_student);
                $stmt_student->bind_param("sss", $name, $email, $password);
                $stmt_student->execute();
                $student_id = $stmt_student->insert_id;
                $stmt_student->close();

                // Also enroll the student in the selected course
                $course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
                if ($course_id > 0) {
                    $stmt_enroll = $conn->prepare("INSERT INTO student_course_enrollments (student_id, course_id) VALUES (?, ?)");
                    $stmt_enroll->bind_param("ii", $student_id, $course_id);
                    $stmt_enroll->execute();
                    $stmt_enroll->close();
                }
            }

            // 3. If instructor, insert into 'instructors' table
            if ($role === 'instructor') {
                // Note: The 'instructors' table also has a password column, which is redundant.
                $sql_instructor = "INSERT INTO instructors (name, email, password) VALUES (?, ?, ?)";
                $stmt_instructor = $conn->prepare($sql_instructor);
                $stmt_instructor->bind_param("sss", $name, $email, $password);
                $stmt_instructor->execute();
                $stmt_instructor->close();
            }

            $conn->commit();
            $message = '<div class="alert alert-success">User (' . htmlspecialchars($role) . ') added successfully!</div>';

        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            // Check for duplicate email error
            if ($conn->errno === 1062) {
                $message = '<div class="alert alert-danger">Error: This email address is already registered.</div>';
            } else {
                $message = '<div class="alert alert-danger">Database error: ' . htmlspecialchars($exception->getMessage()) . '</div>';
            }
        }
    } else {
        $message = '<div class="alert alert-warning">Please fill all fields and select a valid role.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add User - Superadmin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8fafc; }
        .card { border: none; box-shadow: 0 0 20px rgba(0,0,0,.05); }
        .card-header { background-color: #343a40; color: white; }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0"><i class="fas fa-user-plus"></i> Add New User</h4>
        </div>
        <div class="card-body">
            <?php if ($message) echo $message; ?>
            <form method="POST" autocomplete="off">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" class="form-control" required>
                        <option value="">Select role</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= $r ?>" <?= ($preselected_role === $r) ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" id="course-assignment-group" style="display: none;">
                    <label for="course_id">Assign Course</label>
                    <select id="course_id" name="course_id" class="form-control">
                        <option value="">-- Select a course for the student --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['course_id'] ?>"><?= htmlspecialchars($course['course_name']) ?> (<?= htmlspecialchars($course['course_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Add User</button>
                <a href="?page=superadmindash" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('role');
    const courseGroup = document.getElementById('course-assignment-group');

    function toggleCourseVisibility() {
        courseGroup.style.display = (roleSelect.value === 'student') ? 'block' : 'none';
    }

    roleSelect.addEventListener('change', toggleCourseVisibility);
    toggleCourseVisibility(); // Run on page load
});
</script>
</body>
</html>
