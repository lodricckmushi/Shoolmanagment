<?php 


$servername = "localhost";  // or your host name
$username = "root";
$password = "";
$database = "unicourse";

// Create connection
// $conn = new mysqli($servername, $username, $password, $database);

// // Check connection
// if ($conn->connect_error) {
//     die("Connection failed: " . $conn->connect_error);
// }
// echo "Connected successfully";

$conn =mysqli_connect($servername, $username, $password, $database);

//testing connection
if(!$conn){
    echo"connection failed";
}


