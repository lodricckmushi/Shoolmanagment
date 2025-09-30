<?php
// Database configuration for production environment
// Prevent direct access to this file
if (!defined('ROOT_PATH')) {
    die('Direct access to this file is not allowed');
}

// Database connection parameters
$servername = "localhost";  // or your host name
$username = "root";
$password = "";
$database = "unicourse";

// Create connection with improved error handling
$conn = mysqli_connect($servername, $username, $password, $database);

// Check connection with proper error handling
if (!$conn) {
    // Log error instead of displaying it in production
    error_log("Database connection failed: " . mysqli_connect_error());
    // Display user-friendly message
    die("Database connection error. Please try again later.");
}

// Set charset to UTF-8 for proper character encoding
mysqli_set_charset($conn, "utf8mb4");

// Function to get database connection
function getDBConnection() {
    global $conn;
    return $conn;
}

// Function to close database connection
function closeDBConnection() {
    global $conn;
    if ($conn) {
        mysqli_close($conn);
    }
}
?>
