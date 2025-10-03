<?php
// Test database connection
echo "<h2>Testing Database Connection...</h2>";

// Include the configuration
define('ROOT_PATH', __DIR__);
require_once 'config/connection.php';

// Test the connection
if ($conn) {
    echo "<h3 style='color: green;'>✅ Database connection successful!</h3>";
    echo "<p><strong>Server:</strong> " . $conn->host_info . "</p>";
    echo "<p><strong>Database:</strong> " . $conn->database . "</p>";
    echo "<p><strong>Character Set:</strong> " . $conn->character_set_name() . "</p>";
    
    // Test a simple query
    $result = $conn->query("SELECT 1 as test");
    if ($result) {
        echo "<p><strong>Test Query:</strong> ✅ Successful</p>";
        $result->free();
    } else {
        echo "<p><strong>Test Query:</strong> ❌ Failed</p>";
    }
    
    closeDBConnection();
} else {
    echo "<h3 style='color: red;'>❌ Database connection failed!</h3>";
    echo "<p><strong>Error:</strong> " . mysqli_connect_error() . "</p>";
    echo "<p><strong>Error Code:</strong> " . mysqli_connect_errno() . "</p>";
}
?>
