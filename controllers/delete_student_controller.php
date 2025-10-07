<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Role check: only instructors and superadmins can delete
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['instructor', 'superadmin', 'admin'])) {
    header('Location: ?page=login');
    exit;
}

require_once __DIR__ . '/../config/connection.php';
$conn = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_student_id'])) {
    $student_id = (int)$_POST['delete_student_id'];

    if ($student_id > 0) {
        // Use a transaction to ensure both deletes succeed or neither do.
        $conn->begin_transaction();

        try {
            // First, get the user_id from the students table before deleting
            $user_id = null;
            $stmt_get = $conn->prepare("SELECT user_id FROM students WHERE student_id = ?");
            $stmt_get->bind_param("i", $student_id);
            $stmt_get->execute();
            $result = $stmt_get->get_result();
            if ($row = $result->fetch_assoc()) {
                $user_id = $row['user_id'];
            }
            $stmt_get->close();

            // Now, delete from students and then users table
            $stmt_student = $conn->prepare("DELETE FROM students WHERE student_id = ?");
            $stmt_student->bind_param("i", $student_id);
            $stmt_student->execute();
            $stmt_student->close();

            if ($user_id) {
                $stmt_user = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt_user->bind_param("i", $user_id);
                $stmt_user->execute();
                $stmt_user->close();
            }

            $conn->commit();
            $_SESSION['flash_message'] = "Student deleted successfully.";
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            $_SESSION['flash_message'] = "Error: Could not delete student.";
        }
    }
}
header('Location: ?page=total_students');
exit;