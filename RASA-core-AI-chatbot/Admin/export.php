<?php
// export.php
require_once 'db.php';
if (!$conn || $conn->connect_error) {
    die("Connection failed: " . ($conn ? $conn->connect_error : 'No connection object.'));
}
if (!$conn->ping()) {
    die("Database connection is closed.");
}
// $conn = new mysqli("localhost", "root", "", "chatbot_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['table'])) {
    $table = $_GET['table'];
    $valid_tables = ['chatlogs', 'faq_cache', 'faq_frequency'];
    
    if (in_array($table, $valid_tables)) {
        $result = $conn->query("SELECT * FROM $table");
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $table . '_export.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Get column names
        $fields = $result->fetch_fields();
        $headers = array();
        foreach ($fields as $field) {
            $headers[] = $field->name;
        }
        fputcsv($output, $headers);
        
        // Output rows
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit();
    }
}

$conn->close();
?>