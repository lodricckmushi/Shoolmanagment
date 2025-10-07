<?php
session_start();
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check user exists
    $sql = "SELECT id, name, password, role, status FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($user['status'] === 'suspended') {
            header("Location: ?page=loginn&error=suspended");
            exit;
        }

        // Verify the password against the stored hash
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email']; // Store email in session for consistency
            
            // Redirect based on role
            if ($user['role'] === 'student') {
                header("Location: ?page=studentdash");
            } elseif ($user['role'] === 'instructor') {
                header("Location: ?page=instructordash");
            } else {
                // Redirect both 'admin' and 'superadmin' to the main admin dashboard
                header("Location: ?page=superadmindash");
            }
            exit();
        } else {
            header("Location: ?page=loginn&error=invalid");
            exit;
        }
    } else {
        header("Location: ?page=loginn&error=invalid");
        exit;
    }
}
$conn->close();
?>
