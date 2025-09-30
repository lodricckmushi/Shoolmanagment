<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// DB connection
$conn = new mysqli('localhost','root','','unicourse');
if($conn->connect_error) die("Connection failed: ".$conn->connect_error);

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $email = $_POST['email'];
    $password = $_POST['password'];

    // fetch user from DB
    $sql = "SELECT user_id, email, password, role FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    if(!$stmt) die("Prepare failed: ".$conn->error);
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 1){
        $user = $result->fetch_assoc();

        if(password_verify($password, $user['password'])){
            // store session data
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // redirect by role
            if($user['role'] === 'student'){
                header("Location: studentdash.html");
            } elseif($user['role'] === 'instructor'){
                header("Location: instructordash.php");
            } elseif($user['role'] === 'manager'){
                header("Location: hostel_manager47dash.php");
            } else {
                echo "Unknown role in DB: ".htmlspecialchars($user['role']);
            }
            exit;
        } else {
            echo "<p style='color:red;'>Invalid password!</p>";
        }
    } else {
        echo "<p style='color:red;'>No user found with this email!</p>";
    }
} else {
    echo "<p style='color:red;'>Invalid request method.</p>";
}
?>
