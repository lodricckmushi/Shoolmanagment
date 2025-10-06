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
    // --- Add or Update Module ---
    if (isset($_POST['module_name'])) {
        $module_name = trim($_POST['module_name']);
        $module_code = trim($_POST['module_code']);
        $course_id = (int)$_POST['course_id'];
        $module_id = isset($_POST['module_id']) ? (int)$_POST['module_id'] : 0;

        if ($module_id > 0) { // Update
            $stmt = $conn->prepare("UPDATE modules SET module_name = ?, module_code = ?, course_id = ? WHERE module_id = ?");
            $stmt->bind_param("ssii", $module_name, $module_code, $course_id, $module_id);
            $message = "Module updated successfully!";
        } else { // Insert
            $stmt = $conn->prepare("INSERT INTO modules (module_name, module_code, course_id) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $module_name, $module_code, $course_id);
            $message = "Module added successfully!";
        }
        if (!$stmt->execute()) {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    // --- Delete Module ---
    if (isset($_POST['delete_module_id'])) {
        $module_id = (int)$_POST['delete_module_id'];
        $stmt = $conn->prepare("DELETE FROM modules WHERE module_id = ?");
        $stmt->bind_param("i", $module_id);
        if ($stmt->execute()) {
            $message = "Module deleted successfully!";
        } else {
            $message = "Error deleting module: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch all modules and courses
$modules = $conn->query("SELECT m.*, c.course_name FROM modules m LEFT JOIN courses c ON m.course_id = c.course_id ORDER BY m.module_name")->fetch_all(MYSQLI_ASSOC);
$courses = $conn->query("SELECT * FROM courses ORDER BY course_name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Modules</title>
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
    <h2><i class="fas fa-layer-group"></i> Manage Modules</h2>
    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Add/Edit Module Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Add New Module</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="form-row">
                    <div class="col-md-4 form-group">
                        <label>Module Name</label>
                        <input type="text" name="module_name" class="form-control" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Module Code</label>
                        <input type="text" name="module_code" class="form-control" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Assign to Course</label>
                        <select name="course_id" class="form-control" required>
                            <option value="">-- Select Course --</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= $course['course_id'] ?>"><?= htmlspecialchars($course['course_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 form-group d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-plus"></i> Add Module</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Module List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Existing Modules</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th>Module Name</th>
                        <th>Code</th>
                        <th>Assigned Course</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($modules)): ?>
                        <tr><td colspan="4" class="text-center">No modules found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($modules as $module): ?>
                            <tr>
                                <td><?= htmlspecialchars($module['module_name']) ?></td>
                                <td><?= htmlspecialchars($module['module_code']) ?></td>
                                <td><?= htmlspecialchars($module['course_name'] ?? 'N/A') ?></td>
                                <td class="text-right">
                                    <button class="btn btn-sm btn-primary edit-btn" data-id="<?= $module['module_id'] ?>" data-name="<?= htmlspecialchars($module['module_name']) ?>" data-code="<?= htmlspecialchars($module['module_code']) ?>" data-course-id="<?= $module['course_id'] ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this module?');">
                                        <input type="hidden" name="delete_module_id" value="<?= $module['module_id'] ?>">
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

<!-- Edit Module Modal -->
<div class="modal fade" id="editModuleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Edit Module</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="module_id" id="edit_module_id">
                    <div class="form-group">
                        <label>Module Name</label>
                        <input type="text" name="module_name" id="edit_module_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Module Code</label>
                        <input type="text" name="module_code" id="edit_module_code" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Assign to Course</label>
                        <select name="course_id" id="edit_course_id" class="form-control" required>
                            <option value="">-- Select Course --</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= $course['course_id'] ?>"><?= htmlspecialchars($course['course_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    $('.edit-btn').on('click', function() {
        $('#edit_module_id').val($(this).data('id'));
        $('#edit_module_name').val($(this).data('name'));
        $('#edit_module_code').val($(this).data('code'));
        $('#edit_course_id').val($(this).data('course-id'));
        $('#editModuleModal').modal('show');
    });
});
</script>
</body>
</html>
<?php $conn->close(); ?>