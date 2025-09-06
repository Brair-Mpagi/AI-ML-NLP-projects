<?php
// fetch_queries.php
header('Content-Type: application/json');

$host = "localhost";
$user = "root";
$password = ""; // Set your MySQL password
$database = "chatbot_db";

require_once 'db.php';
if (!$conn || $conn->connect_error) {
    die("Connection failed: " . ($conn ? $conn->connect_error : 'No connection object.'));
}
if (!$conn->ping()) {
    die("Database connection is closed.");
}
// $conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// Fetch pushed queries
$sql_pushed = "SELECT * FROM pushed_query ORDER BY timestamp DESC";
$result_pushed = $conn->query($sql_pushed);
$pushed_queries = [];
while ($row = $result_pushed->fetch_assoc()) {
    $pushed_queries[] = $row;
}

// Fetch responded queries
$sql_responded = "SELECT * FROM responded_query ORDER BY responded_timestamp DESC";
$result_responded = $conn->query($sql_responded);
$responded_queries = [];
while ($row = $result_responded->fetch_assoc()) {
    $responded_queries[] = $row;
}

// Count "Not Yet" queries
$sql_count_not_yet = "SELECT COUNT(*) as not_yet FROM pushed_query WHERE status = 'Not Yet'";
$result_count_not_yet = $conn->query($sql_count_not_yet);
$not_yet_count = $result_count_not_yet->fetch_assoc()['not_yet'];

$conn->close();

echo json_encode([
    'pushed_queries' => $pushed_queries,
    'responded_queries' => $responded_queries,
    'not_yet_count' => $not_yet_count
]);
?>