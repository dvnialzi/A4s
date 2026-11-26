<?php
$servername = "sql302.infinityfree.com";
$username = "if0_43537212";
$password = "2PSGFScO0Lc5t"; // InfinityFree > MySQL Databases > MySQL Password
$dbname = "if0_43537212_dbshoes";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
