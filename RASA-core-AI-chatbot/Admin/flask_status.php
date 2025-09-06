<?php
// flask_status.php
// Checks if a Flask app is running on http://127.0.0.1:5000 and shows basic info
header('Content-Type: application/json');

function check_flask_status() {
    $host = '127.0.0.1';
    $port = 5000;
    $timeout = 2;
    $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if (!$fp) {
        return [
            'status' => 'offline',
            'info' => null,
            'details' => "Flask app not listening on $host:$port"
        ];
    }
    fclose($fp);
    // Try to get the status endpoint
    $ch = curl_init("http://$host:$port/status");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $banner = null;
    if ($http_code === 200 && $response) {
        $banner = 'Flask status endpoint responded';
    }
    // Try to get process info (ps aux)
    $ps_output = shell_exec("ps aux | grep 'python' | grep 'app.py' | grep -v grep");
    $lines = array_filter(explode("\n", $ps_output));
    $proc_info = count($lines) ? $lines[0] : null;
    return [
        'status' => 'online',
        'info' => [
            'url' => "http://$host:$port",
            'banner' => $banner,
            'process' => $proc_info
        ],
        'details' => 'Flask app detected and responding.'
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(check_flask_status());
    exit;
}
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>