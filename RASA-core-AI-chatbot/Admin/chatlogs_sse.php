<?php
require_once 'db.php';
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

$lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
$startTime = time();
$maxDuration = 30; // seconds, to prevent runaway

while (true) {
    $result = $conn->query("SELECT chatlog_id, session_id, question, response_text, timestamp FROM chatlogs WHERE chatlog_id > $lastId ORDER BY chatlog_id DESC LIMIT 1");
    if ($row = $result->fetch_assoc()) {
        $lastId = $row['chatlog_id'];
        echo "data: " . json_encode($row) . "\n\n";
        ob_flush();
        flush();
    }
    if ((time() - $startTime) > $maxDuration) break;
    sleep(1);
}
exit;
?>
