<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Role check: only instructors and superadmins can edit
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['instructor', 'superadmin', 'admin'])) {
    header('Location: ?page=login');
    exit;
}

require_once __DIR__ . '/../config/connection.php';
$conn = getDBConnection();

$message = '';
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($student_id <= 0) {
    die("Invalid student ID.");
}

// Handle form submission for updating student data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $semester = $_POST['semester'];
    $enrollment_year = $_POST['enrollment_year'];
    $department_id = !empty($_POST['department_id']) ? $_POST['department_id'] : null;
    $course_id = !empty($_POST['course_id']) ? $_POST['course_id'] : null;
    $id = $_POST['student_id'];

    $conn->begin_transaction();
    try {
        // Update students table
        $sql = "UPDATE students SET name = ?, semester = ?, enrollment_year = ?, department_id = ? WHERE student_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception($conn->error);
        
        $stmt->bind_param("sisii", $name, $semester, $enrollment_year, $department_id, $id);
        if (!$stmt->execute()) throw new Exception($stmt->error);
        $stmt->close();

        // Update course enrollment (UPSERT logic)
        if ($course_id) {
            $check_sql = "SELECT enrollment_id FROM student_course_enrollments WHERE student_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $id);
            $check_stmt->execute();
            $enrollment_result = $check_stmt->get_result();

            if ($enrollment_result->num_rows > 0) {
                // Student is already enrolled, so UPDATE
                $enrollment = $enrollment_result->fetch_assoc();
                $enrollment_id = $enrollment['enrollment_id'];
                $upsert_sql = "UPDATE student_course_enrollments SET course_id = ? WHERE enrollment_id = ?";
                $upsert_stmt = $conn->prepare($upsert_sql);
                $upsert_stmt->bind_param("ii", $course_id, $enrollment_id);
            } else {
                // Student is not enrolled, so INSERT
                $upsert_sql = "INSERT INTO student_course_enrollments (student_id, course_id) VALUES (?, ?)";
                $upsert_stmt = $conn->prepare($upsert_sql);
                $upsert_stmt->bind_param("ii", $id, $course_id);
            }

            if (!$upsert_stmt->execute()) throw new Exception($upsert_stmt->error);
            
            $upsert_stmt->close();
            $check_stmt->close();
        }
        
        $conn->commit();
        $message = "<div class='alert alert-success'>✅ Student academic and course enrollment details have been updated successfully!</div>";
    } catch (Exception $e) {
        $conn->rollback();
        $message = "<div class='alert alert-danger'>❌ Error: " . $e->getMessage() . "</div>";
    }
}

// Fetch the student's current data to pre-fill the form
$student = null;
$sql = "SELECT s.student_id, s.name, s.email, s.semester, s.enrollment_year, s.department_id, sce.course_id
        FROM students s
        LEFT JOIN student_course_enrollments sce ON s.student_id = sce.student_id
        WHERE s.student_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
}

if (!$student) {
    die("Student not found.");
}

// Fetch all departments for the dropdown
$departments = [];
$dept_result = $conn->query("SELECT department_id, department_name FROM departments ORDER BY department_name");
if ($dept_result) {
    while ($row = $dept_result->fetch_assoc()) {
        $departments[] = $row;
    }
}

// Fetch all courses for the dropdown
$courses = [];
$course_result = $conn->query("SELECT course_id, course_name, course_code FROM courses ORDER BY course_name");
if ($course_result) {
    while ($row = $course_result->fetch_assoc()) {
        $courses[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Student</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .card-header { background-color: #3a4a5a; color: white; }
    .form-section-title { border-bottom: 2px solid #3a4a5a; padding-bottom: 0.5rem; margin-bottom: 1.5rem; font-weight: 600; color: #3a4a5a;}
  </style>
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-user-edit"></i> Edit Student</h2>
        <a href="?page=total_students" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Student List</a>
    </div>

    <?php if ($message) echo $message; ?>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">Editing Student: <?= htmlspecialchars($student['name']) ?></h5>
        </div>
        <div class="card-body">
            <form action="?page=edit_student&id=<?= htmlspecialchars($student['student_id']) ?>" method="POST">
                <input type="hidden" name="student_id" value="<?= htmlspecialchars($student['student_id']) ?>">
                
                <h5 class="form-section-title"><i class="fas fa-user-circle"></i> Personal Information</h5>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="name">Student Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($student['name']) ?>" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="email">Email (Read-only)</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($student['email']) ?>" readonly>
                    </div>
                </div>

                <h5 class="form-section-title mt-4"><i class="fas fa-university"></i> Academic Information</h5>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="semester">Current Semester</label>
                        <input type="number" class="form-control" id="semester" name="semester" value="<?= htmlspecialchars($student['semester']) ?>" min="1" max="12">
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="enrollment_year">Enrollment Year</label>
                        <input type="number" class="form-control" id="enrollment_year" name="enrollment_year" value="<?= htmlspecialchars($student['enrollment_year']) ?>" min="2000" max="<?= date('Y') + 1 ?>">
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="department_id">Department</label>
                        <select class="form-control" id="department_id" name="department_id">
                            <option value="">-- Select Department --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['department_id'] ?>" <?= ($student['department_id'] == $dept['department_id']) ? 'selected' : '' ?>><?= htmlspecialchars($dept['department_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="course_id">Enrolled Course</label>
                        <select class="form-control" id="course_id" name="course_id">
                            <option value="">-- Select Course --</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= $course['course_id'] ?>" <?= ($student['course_id'] == $course['course_id']) ? 'selected' : '' ?>><?= htmlspecialchars($course['course_name']) ?> (<?= htmlspecialchars($course['course_code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save Changes</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>