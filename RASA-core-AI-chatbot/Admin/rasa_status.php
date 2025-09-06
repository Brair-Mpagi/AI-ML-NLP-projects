<?php
// rasa_status.php
// Endpoint to check Rasa server status securely

function is_valid_rasa_url($url) {
    // Only allow localhost or 127.0.0.1 with port 5005
    $parsed = parse_url($url);
    if (!$parsed || !isset($parsed['scheme'], $parsed['host'], $parsed['port'])) {
        return false;
    }
    $allowed_hosts = ['localhost', '127.0.0.1'];
    $allowed_port = 5005;
    return in_array($parsed['host'], $allowed_hosts) && $parsed['port'] == $allowed_port && $parsed['scheme'] === 'http';
}

function check_rasa_status($rasa_url = 'http://localhost:5005/status') {
    if (!is_valid_rasa_url($rasa_url)) {
        error_log("[RASA STATUS] Invalid Rasa URL: $rasa_url", 3, __DIR__ . '/rasa_status_error.log');
        return [
            'status' => 'offline',
            'error' => 'Invalid Rasa URL.'
        ];
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $rasa_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    $response = curl_exec($ch);
    $curl_errno = curl_errno($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curl_errno || $http_code !== 200) {
        error_log("[RASA STATUS] CURL error: $curl_error HTTP code: $http_code", 3, __DIR__ . '/rasa_status_error.log');
        return [
            'status' => 'offline',
            'error' => $curl_error ?: 'HTTP error',
            'http_code' => $http_code
        ];
    }
    $data = json_decode($response, true);
    if (!$data || !isset($data['model_file'])) {
        error_log("[RASA STATUS] Invalid JSON or missing model_file.", 3, __DIR__ . '/rasa_status_error.log');
        return [
            'status' => 'offline',
            'error' => 'Invalid JSON or missing model_file.'
        ];
    }
    $model_name = basename($data['model_file']);
    $active_jobs = isset($data['active_training_jobs']) ? $data['active_training_jobs'] : [];
    return [
        'status' => 'online',
        'model' => $model_name,
        'active_jobs' => $active_jobs,
        'rasa_version' => $data['rasa_version'] ?? null,
        'url' => $rasa_url
    ];
}

// AJAX endpoint logic
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = check_rasa_status();
    echo json_encode($result);
    exit;
}
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
