<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // DB connection
    $conn = new mysqli('localhost', 'root', '', 'unicourse');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $success = true; // track both inserts

    // Insert into instructors table
    $sql = "INSERT INTO instructors (name, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sss", $name, $email, $password);
        if (!$stmt->execute()) {
            echo "Error inserting into instructors: " . $stmt->error . "<br>";
            $success = false;
        }
        $stmt->close();
    } else {
        echo "Error preparing instructors statement: " . $conn->error . "<br>";
        $success = false;
    }

    // Insert into users table
    $created_at = date('Y-m-d H:i:s');
    $role = "instructor";

    $sql = "INSERT INTO users (email, password, role, created_at) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ssss", $email, $password, $role, $created_at);
        if (!$stmt->execute()) {
            echo "Error inserting into users: " . $stmt->error . "<br>";
            $success = false;
        }
        $stmt->close();
    } else {
        echo "Error preparing users statement: " . $conn->error . "<br>";
        $success = false;
    }

    if ($success) {
        echo "✅ INSTRUCTOR registered successfully in both tables.";
    } else {
        echo "❌ Registration failed. Check errors above.";
    }

    $conn->close();
}
?>
