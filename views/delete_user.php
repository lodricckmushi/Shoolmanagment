 <?php
// delete_user.php - Superadmin deletes a user
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['superadmin', 'admin'])) {
    header('Location: ?page=login');
    exit;
}
require_once __DIR__ . '/../config/connection.php';
$conn = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $user_id_to_delete = intval($_POST['delete_user_id']);

    if ($user_id_to_delete > 0 && $user_id_to_delete != $_SESSION['user_id']) {
        $conn->begin_transaction();
        try {
            // Get user role before deleting
            $stmt_get = $conn->prepare("SELECT role, email FROM users WHERE id = ?");
            $stmt_get->bind_param("i", $user_id_to_delete);
            $stmt_get->execute();
            $user_to_delete = $stmt_get->get_result()->fetch_assoc();
            $stmt_get->close();

            if ($user_to_delete) {
                // Security: Admin cannot delete a superadmin
                if ($_SESSION['role'] === 'admin' && $user_to_delete['role'] === 'superadmin') {
                    throw new Exception("Access Denied. Admins cannot delete Superadmins.");
                }

                // Delete from role-specific tables first
                if ($user_to_delete['role'] === 'student') {
                    $stmt_student = $conn->prepare("DELETE FROM students WHERE user_id = ?");
                    $stmt_student->bind_param("i", $user_id_to_delete);
                    $stmt_student->execute();
                    $stmt_student->close();
                } elseif ($user_to_delete['role'] === 'instructor') {
                    $stmt_instructor = $conn->prepare("DELETE FROM instructors WHERE email = ?");
                    $stmt_instructor->bind_param("s", $user_to_delete['email']);
                    $stmt_instructor->execute();
                    $stmt_instructor->close();
                }

                // Finally, delete from the main users table
                $stmt_user = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt_user->bind_param("i", $user_id_to_delete);
                $stmt_user->execute();
                $stmt_user->close();

                $conn->commit();
                $_SESSION['flash_message'] = "User deleted successfully.";
            }
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['flash_message'] = "Error: " . $e->getMessage();
        }
    }
}
header('Location: ?page=superadmindash');
exit;
?>