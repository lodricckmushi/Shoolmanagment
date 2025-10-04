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
$port = 3307;  // MySQL port, configured port 3307 instead of 3306 which is default 

// Create connection with improved error handling and port specification
$conn = mysqli_connect($servername, $username, $password, $database, $port);

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
if (!function_exists('getDBConnection')) {
    function getDBConnection() {
        global $conn;
        return $conn;
    }
}

// Function to close database connection
if (!function_exists('closeDBConnection')) {
    function closeDBConnection() {
        global $conn;
        if ($conn) {
            mysqli_close($conn);
        }
    }
}
?>
