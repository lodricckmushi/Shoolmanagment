<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Role check: only instructors and superadmins can view this page
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['instructor', 'superadmin', 'admin'])) {
    header('Location: ?page=login');
    exit;
}

require_once __DIR__ . '/../config/connection.php';
$conn = getDBConnection();

// --- Pagination and Search Logic ---
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$results_per_page = 10;
$offset = ($page - 1) * $results_per_page;

// Build the WHERE clause for searching
$where_clause = '';
$params = [];
$types = '';
if (!empty($search_term)) {
    $where_clause = " WHERE (s.name LIKE ? OR s.email LIKE ?)";
    $like_term = "%" . $search_term . "%";
    $params[] = $like_term;
    $params[] = $like_term;
    $types .= 'ss';
}

// Get total number of students for pagination
$total_sql = "SELECT COUNT(s.student_id) as total FROM students s" . $where_clause;
$total_stmt = $conn->prepare($total_sql);
if (!empty($search_term)) {
    $total_stmt->bind_param($types, ...$params);
}
$total_stmt->execute();
$total_result = $total_stmt->get_result()->fetch_assoc();
$total_students = $total_result['total'];
$total_pages = ceil($total_students / $results_per_page);
$total_stmt->close();

// Fetch the paginated student data
$students = [];
$sql = "SELECT s.student_id, s.name, s.email, s.semester, s.enrollment_year, c.course_name, d.department_name
        FROM students s
        LEFT JOIN student_course_enrollments sce ON s.student_id = sce.student_id
        LEFT JOIN courses c ON sce.course_id = c.course_id
        LEFT JOIN departments d ON s.department_id = d.department_id"
        . $where_clause . " ORDER BY s.name ASC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $types .= 'ii';
    $params[] = $results_per_page;
    $params[] = $offset;
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $students = $result->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();
}

$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// --- AJAX Request Handler ---
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    ob_start();
    // Generate table rows
    if (count($students) > 0) {
        foreach ($students as $student) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($student['student_id']) . "</td>";
            echo "<td>" . htmlspecialchars($student['name']) . "</td>";
            echo "<td>" . htmlspecialchars($student['email']) . "</td>";
            echo "<td>" . htmlspecialchars($student['course_name'] ?? 'Not Enrolled') . "</td>";
            echo "<td>" . htmlspecialchars($student['department_name'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($student['semester'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($student['enrollment_year'] ?? 'N/A') . "</td>";
            echo "<td class='text-right'>";
            echo "<a href='?page=edit_student&id=" . $student['student_id'] . "' class='btn btn-primary btn-sm'><i class='fas fa-edit'></i> Edit</a> ";
            echo "<button class='btn btn-danger btn-sm delete-btn' data-id='" . $student['student_id'] . "' data-name='" . htmlspecialchars($student['name']) . "'><i class='fas fa-trash'></i> Delete</button>";
            echo "</td>";
            echo "</tr>";
        }
    } else {
        echo '<tr><td colspan="8" class="text-center py-4">No students found.</td></tr>';
    }
    $table_html = ob_get_clean();

    ob_start();
    // Generate pagination links
    for ($i = 1; $i <= $total_pages; $i++) {
        echo "<li class='page-item " . (($i == $page) ? 'active' : '') . "'>";
        echo "<a class='page-link' href='?page=total_students&p=" . $i . "&search=" . urlencode($search_term) . "'>" . $i . "</a>";
        echo "</li>";
    }
    $pagination_html = ob_get_clean();

    header('Content-Type: application/json');
    echo json_encode([
        'table' => $table_html,
        'pagination' => $pagination_html,
        'total' => $total_students
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Total Students</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css">
    <style>
        body { background: #f8fafc; }
        .container { margin-top: 2.5rem; }
        .table thead th { background: #3a4a5a; color: #fff; }
        .page-header { font-size: 2rem; font-weight: 700; color: #2a3a4a; }
    </style>
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand font-weight-bold" href="?page=instructordash"><i class="fas fa-chalkboard-teacher"></i> Instructor Portal</a>
    <div class="collapse navbar-collapse">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item"><a class="nav-link text-danger font-weight-bold" href="?page=logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
</nav>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <div class="page-header"><i class="fas fa-users"></i> Student Management</div>
            <span class="badge badge-pill badge-primary" style="font-size: 1rem; padding: 0.5em 1em;">
                Total: <?= $total_students ?>
            </span>
        </div>
        <a href="?page=add_user&role=student" class="btn btn-success"><i class="fas fa-user-plus"></i> Add New Student</a>
    </div>

    <?php if ($flash_message): ?><div class="alert alert-success"><?= $flash_message ?></div><?php endif; ?>

    <!-- Search Form -->
    <form method="GET" class="mb-4">
        <input type="hidden" name="page" value="total_students" id="page-name">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="<?= htmlspecialchars($search_term) ?>">
            <div class="input-group-append">
                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Search</button>
            </div>
        </div>
    </form>

    <div id="student-list-container">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Course</th>
                            <th>Department</th>
                            <th>Semester</th>
                            <th>Year</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="student-table-body">
                    <?php if (count($students) > 0): ?>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?= htmlspecialchars($student['student_id']) ?></td>
                                <td><?= htmlspecialchars($student['name']) ?></td>
                                <td><?= htmlspecialchars($student['email']) ?></td>
                                <td><?= htmlspecialchars($student['course_name'] ?? 'Not Enrolled') ?></td>
                                <td><?= htmlspecialchars($student['department_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($student['semester'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($student['enrollment_year'] ?? 'N/A') ?></td>
                                <td class="text-right">
                                    <a href="?page=edit_student&id=<?= $student['student_id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                    <button class="btn btn-danger btn-sm delete-btn" data-id="<?= $student['student_id'] ?>" data-name="<?= htmlspecialchars($student['name']) ?>"><i class="fas fa-trash"></i> Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center py-4">No students found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination Controls -->
        <nav class="mt-4">
            <ul class="pagination justify-content-center" id="pagination-links">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=total_students&p=<?= $i ?>&search=<?= urlencode($search_term) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete the student: <strong id="studentName"></strong>? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <form action="?page=delete_student_controller" method="POST">
                    <input type="hidden" name="delete_student_id" id="deleteStudentId">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Student</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

function fetchStudents(page = 1, search = '') {
    const pageName = $('#page-name').val();
    const url = `?page=${pageName}&p=${page}&search=${encodeURIComponent(search)}`;

    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            $('#student-table-body').html('<tr><td colspan="8" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>');
        },
        success: function(response) {
            $('#student-table-body').html(response.table);
            $('#pagination-links').html(response.pagination);
            $('.badge-primary').text('Total: ' + response.total);
            // Update URL without reloading
            window.history.pushState({path: url}, '', url);
        },
        error: function() {
            $('#student-table-body').html('<tr><td colspan="8" class="text-center py-4">An error occurred.</td></tr>');
        }
    });
}

$(document).ready(function() {
    // Use event delegation for dynamically added elements
    $('body').on('click', '.delete-btn', function() {
        var studentId = $(this).data('id');
        var studentName = $(this).data('name');
        $('#deleteStudentId').val(studentId);
        $('#studentName').text(studentName);
        $('#deleteStudentModal').modal('show');
    });

    // AJAX search on keyup with debounce
    $('input[name="search"]').on('keyup', debounce(function() {
        var searchTerm = $(this).val();
        fetchStudents(1, searchTerm);
    }, 300));

    // AJAX pagination
    $('body').on('click', '#pagination-links a', function(e) {
        e.preventDefault();
        var url = new URL($(this).attr('href'), window.location.origin);
        var page = url.searchParams.get('p');
        var search = url.searchParams.get('search');
        fetchStudents(page, search);
    });
});
</script>
</body>
</html>