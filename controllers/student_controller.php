<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? $_POST['first_name']; // Support both 'name' and 'first_name'
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // DB connection
    $conn = new mysqli('localhost', 'root', '', 'unicourse', 3307);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $conn->begin_transaction();
    $success = true;

    // 1. Insert into the main 'users' table to enable login
    $role = 'student';
    $created_at = date('Y-m-d H:i:s');
    $sql_user = "INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql_user);
    if ($stmt) {
        $stmt->bind_param("sssss", $name, $email, $password, $role, $created_at);
        if (!$stmt->execute()) {
            echo "Error inserting into users: " . $stmt->error . "<br>";
            $success = false;
        }
        $user_id = $stmt->insert_id; // Get the new user ID
        $stmt->close();
    } else {
        echo "Error preparing users statement: " . $conn->error . "<br>";
        $success = false;
    }

    // 2. Insert into the 'students' table with the corresponding user_id
    $sql_student = "INSERT INTO students (user_id, name, email, password) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql_student);
    if ($stmt && $success) {
        $stmt->bind_param("isss", $user_id, $name, $email, $password);
        if (!$stmt->execute()) {
            echo "Error inserting into students: " . $stmt->error . "<br>";
            $success = false;
        }
        $stmt->close();
    } else {
        $success = false;
    }

    // Commit or rollback transaction
    if ($success) {
        $conn->commit();
        echo "✅ STUDENT registered successfully and can now log in.";
    } else {
        $conn->rollback();
        echo "❌ Registration failed. Please check the errors above.";
    }

    $conn->close();
}
?>
