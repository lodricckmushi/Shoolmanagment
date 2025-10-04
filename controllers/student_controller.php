<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['first_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // DB connection
    $conn = new mysqli('localhost', 'root', '', 'unicourse', 3307);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Insert query
    $sql = "INSERT INTO students (name, email,password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sss", $name, $email, $password);

    if (!$stmt->execute()) {
        echo "Execute failed: " . $stmt->error;
    } elseif ($stmt->affected_rows > 0) {
        echo "STUDENT registered successfully.";
    } else {
        echo "No rows inserted.";
    }

    $stmt->close();
    $conn->close();
}
?>
