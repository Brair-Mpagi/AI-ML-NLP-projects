<?php
// db.php - Centralized MySQLi connection for the project
$host = "localhost";
$dbname = "chatbot_db";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
