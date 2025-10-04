<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../config/connection.php';

// Fetch all students from the students table
$sql = "SELECT student_id, name, email, semester, enrollment_year, department_id FROM students ORDER BY name ASC";
$result = $conn->query($sql);
$students = [];
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $students[] = $row;
  }
}
$total = count($students);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Total Students</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
  <!-- Home Button -->
  <a href="?page=instructordash" class="btn btn-primary position-fixed" style="top: 20px; left: 20px; z-index: 1050; border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
    <i class="fas fa-home fa-lg"></i>
  </a>

  <div class="content-wrapper p-4">
    <h2>Total Students</h2>
    <div class="card">
      <div class="card-body">
        <h3>Total Registered Students: <span id="student-count"><?php echo $total; ?></span></h3>
        <div class="table-responsive mt-3">
          <table class="table table-striped table-bordered">
            <thead class="thead-dark">
              <tr>
                <th>#</th>
                <th>Student ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Semester</th>
                <th>Enrollment Year</th>
                <th>Department ID</th>
              </tr>
            </thead>
            <tbody>
            <?php if ($total > 0): $i = 1; foreach ($students as $student): ?>
              <tr>
                <td><?php echo $i++; ?></td>
                <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                <td><?php echo htmlspecialchars($student['name']); ?></td>
                <td><?php echo htmlspecialchars($student['email']); ?></td>
                <td><?php echo htmlspecialchars($student['semester']); ?></td>
                <td><?php echo htmlspecialchars($student['enrollment_year']); ?></td>
                <td><?php echo htmlspecialchars($student['department_id']); ?></td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="7" class="text-center">No students found.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
