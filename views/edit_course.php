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
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($course_id <= 0) {
    die("Invalid course ID.");
}

// Handle form submission for updating course, instructor, and modules
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn->begin_transaction();
    try {
        // 1. Update basic course details
        $course_name = $_POST['course_name'];
        $course_code = $_POST['course_code'];
        $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
        
        $stmt_course = $conn->prepare("UPDATE courses SET course_name = ?, course_code = ?, department_id = ? WHERE course_id = ?");
        $stmt_course->bind_param("ssii", $course_name, $course_code, $department_id, $course_id);
        $stmt_course->execute();
        $stmt_course->close();

        // 2. Update instructor assignment
        $instructor_id = !empty($_POST['instructor_id']) ? (int)$_POST['instructor_id'] : null;
        // First, remove any existing assignment for this course
        $stmt_del_inst = $conn->prepare("DELETE FROM course_instructor_assignments WHERE course_id = ?");
        $stmt_del_inst->bind_param("i", $course_id);
        $stmt_del_inst->execute();
        $stmt_del_inst->close();
        // Then, add the new assignment if one was selected
        if ($instructor_id) {
            $stmt_add_inst = $conn->prepare("INSERT INTO course_instructor_assignments (course_id, instructor_id) VALUES (?, ?)");
            $stmt_add_inst->bind_param("ii", $course_id, $instructor_id);
            $stmt_add_inst->execute();
            $stmt_add_inst->close();
        }

        // 3. Update module assignments
        $assigned_modules = isset($_POST['modules']) ? $_POST['modules'] : [];
        // First, un-assign all modules from this course
        $stmt_unassign = $conn->prepare("UPDATE modules SET course_id = NULL WHERE course_id = ?");
        $stmt_unassign->bind_param("i", $course_id);
        $stmt_unassign->execute();
        $stmt_unassign->close();
        // Then, assign the selected modules to this course
        if (!empty($assigned_modules)) {
            $in_clause = implode(',', array_fill(0, count($assigned_modules), '?'));
            $types = str_repeat('i', count($assigned_modules));
            $stmt_assign = $conn->prepare("UPDATE modules SET course_id = ? WHERE module_id IN ($in_clause)");
            $params = array_merge([$course_id], $assigned_modules);
            $types = 'i' . $types;
            $stmt_assign->bind_param($types, ...$params);
            $stmt_assign->execute();
            $stmt_assign->close();
        }

        $conn->commit();
        $message = "<div class='alert alert-success'>✅ Course updated successfully!</div>";

    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        $message = "<div class='alert alert-danger'>❌ Database error: " . $exception->getMessage() . "</div>";
    }
}

// --- Fetch Data for the Form ---

// Fetch course details
$stmt = $conn->prepare("SELECT * FROM courses WHERE course_id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$course) {
    die("Course not found.");
}

// Fetch all departments
$departments = $conn->query("SELECT * FROM departments ORDER BY department_name")->fetch_all(MYSQLI_ASSOC);

// Fetch all instructors
$instructors = $conn->query("SELECT id, name FROM users WHERE role = 'instructor' ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Fetch currently assigned instructor for this course
$assigned_instructor_id = null;
$stmt_inst = $conn->prepare("SELECT instructor_id FROM course_instructor_assignments WHERE course_id = ?");
$stmt_inst->bind_param("i", $course_id);
$stmt_inst->execute();
$res_inst = $stmt_inst->get_result();
if ($row_inst = $res_inst->fetch_assoc()) {
    $assigned_instructor_id = $row_inst['instructor_id'];
}
$stmt_inst->close();

// Separate modules into "assigned" to this course and "available" (unassigned or assigned to other courses)
$assigned_modules_list = [];
$available_modules_list = [];
$stmt_mod = $conn->prepare("
    SELECT m.module_id, m.module_name, m.module_code, m.course_id, c.course_name as assigned_course_name
    FROM modules m
    LEFT JOIN courses c ON m.course_id = c.course_id
    ORDER BY m.module_name
");
$stmt_mod->execute();
foreach ($stmt_mod->get_result()->fetch_all(MYSQLI_ASSOC) as $module) {
    if ($module['course_id'] == $course_id) {
        $assigned_modules_list[] = $module;
    } else {
        $available_modules_list[] = $module;
    }
}
$stmt_mod->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Course</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .form-section-title { border-bottom: 2px solid #343a40; padding-bottom: 0.5rem; margin-bottom: 1.5rem; font-weight: 600; color: #343a40;}
    .dual-list-box {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .dual-list-box .list-container {
        border: 1px solid #ddd;
        border-radius: 5px;
        height: 300px;
        overflow-y: auto;
        width: 45%;
    }
    .dual-list-box .list-group-item { cursor: pointer; }
    .dual-list-box .list-group-item.active { background-color: #007bff; color: white; border-color: #007bff; }
    .dual-list-box .controls { width: 10%; text-align: center; }
  </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="?page=superadmindash"><i class="fas fa-user-shield"></i> Admin Panel</a>
    <ul class="navbar-nav ml-auto">
        <li class="nav-item"><a class="nav-link" href="?page=manage_courses"><i class="fas fa-arrow-left"></i> Back to Courses</a></li>
    </ul>
</nav>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-edit"></i> Edit Course</h2>
    </div>

    <?php if ($message) echo $message; ?>

    <form action="?page=edit_course&id=<?= $course_id ?>" method="POST">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Editing Course: <?= htmlspecialchars($course['course_name']) ?></h5>
            </div>
            <div class="card-body">
                <!-- Section 1: Basic Details -->
                <h5 class="form-section-title"><i class="fas fa-book-open"></i> Course Details</h5>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="course_name">Course Name</label>
                        <input type="text" class="form-control" id="course_name" name="course_name" value="<?= htmlspecialchars($course['course_name']) ?>" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="course_code">Course Code</label>
                        <input type="text" class="form-control" id="course_code" name="course_code" value="<?= htmlspecialchars($course['course_code']) ?>" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="department_id">Department</label>
                        <select class="form-control" id="department_id" name="department_id">
                            <option value="">-- None --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['department_id'] ?>" <?= ($course['department_id'] == $dept['department_id']) ? 'selected' : '' ?>><?= htmlspecialchars($dept['department_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Section 2: Instructor Assignment -->
                <h5 class="form-section-title mt-4"><i class="fas fa-chalkboard-teacher"></i> Assign Instructor</h5>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="instructor_id">Course Instructor</label>
                        <select class="form-control" id="instructor_id" name="instructor_id">
                            <option value="">-- Unassigned --</option>
                            <?php foreach ($instructors as $instructor): ?>
                                <option value="<?= $instructor['id'] ?>" <?= ($assigned_instructor_id == $instructor['id']) ? 'selected' : '' ?>><?= htmlspecialchars($instructor['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Section 3: Module Assignment -->
                <h5 class="form-section-title mt-4"><i class="fas fa-layer-group"></i> Manage Modules</h5>
                <div class="dual-list-box">
                    <!-- Available Modules -->
                    <div class="list-wrapper" style="width: 45%;">
                        <h6>Available Modules</h6>
                        <input type="text" id="available-search" class="form-control mb-2" placeholder="Search available...">
                        <ul id="available-modules" class="list-group list-container">
                            <?php foreach ($available_modules_list as $module): ?>
                                <li class="list-group-item" data-id="<?= $module['module_id'] ?>">
                                    <?= htmlspecialchars($module['module_name']) ?> <span class="text-muted">(<?= htmlspecialchars($module['module_code']) ?>)</span>
                                    <?php if ($module['course_id']): ?>
                                        <span class="badge badge-warning float-right mt-1">Assigned to: <?= htmlspecialchars($module['assigned_course_name']) ?></span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Controls -->
                    <div class="controls">
                        <button type="button" id="add-module" class="btn btn-secondary mb-2 w-100">&gt;</button>
                        <button type="button" id="remove-module" class="btn btn-secondary w-100">&lt;</button>
                    </div>

                    <!-- Assigned Modules -->
                    <div class="list-wrapper" style="width: 45%;">
                        <h6>Assigned Modules</h6>
                        <div id="assigned-modules-container">
                            <ul id="assigned-modules" class="list-group list-container">
                                <?php foreach ($assigned_modules_list as $module): ?>
                                    <li class="list-group-item" data-id="<?= $module['module_id'] ?>">
                                        <?= htmlspecialchars($module['module_name']) ?> <span class="text-muted">(<?= htmlspecialchars($module['module_code']) ?>)</span>
                                        <input type="hidden" name="modules[]" value="<?= $module['module_id'] ?>">
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-right">
                <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-save"></i> Save All Changes</button>
            </div>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    function moveItems(fromList, toList) {
        fromList.find('.active').each(function() {
            $(this).removeClass('active').appendTo(toList);
            updateHiddenInputs();
        });
    }

    function updateHiddenInputs() {
        // Clear previous hidden inputs from the form that might be outside the list
        $('input[name="modules[]"]').remove();
        // Add a hidden input for each item in the assigned list
        $('#assigned-modules li').each(function() {
            const moduleId = $(this).data('id');
            $('<input>').attr({
                type: 'hidden',
                name: 'modules[]',
                value: moduleId
            }).appendTo('form');
        });
    }

    // Toggle active class on click
    $('.dual-list-box').on('click', '.list-group-item', function(e) {
        if (!e.ctrlKey) {
            $(this).siblings().removeClass('active');
        }
        $(this).toggleClass('active');
    });

    // Move from available to assigned
    $('#add-module').on('click', function() {
        moveItems($('#available-modules'), $('#assigned-modules'));
    });

    // Move from assigned to available
    $('#remove-module').on('click', function() {
        moveItems($('#assigned-modules'), $('#available-modules'));
    });

    // Live search for available modules
    $('#available-search').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('#available-modules li').each(function() {
            const text = $(this).text().toLowerCase();
            if (text.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
});
</script>
</body>
</html>
<?php $conn->close(); ?>