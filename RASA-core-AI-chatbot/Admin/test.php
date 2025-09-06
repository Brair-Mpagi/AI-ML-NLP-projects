<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session to check authentication
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page if not authenticated
    header("Location: ./admin-login.php");
    exit();
}

// Database connection
$host = "localhost";
$username = "root";
$password = ""; // Update with your MySQL password
$database = "chatbot_db";

require_once 'db.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch Admin Details
$admin_query = "SELECT admin_id, username, email, image FROM admins WHERE admin_id = ?";
if (!$conn->ping()) {
    die("Database connection is closed.");
}
$admin_stmt = $conn->prepare($admin_query);
if (!$admin_stmt) {
    die("Prepare failed: " . $conn->error);
}
$admin_stmt->bind_param('i', $_SESSION['admin_id']);
$admin_stmt->execute();
$admin_result = $admin_stmt->get_result();
$admin = $admin_result->fetch_assoc();

// Fetch data function
function fetchData($conn, $query, ...$params) {
    $stmt = $conn->prepare($query);
    if ($params) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result) {
        die("Query failed: " . $conn->error);
    }
    return $result->fetch_all(MYSQLI_ASSOC);
}

// --- FILTER LOGIC ---
$date_range = isset($_GET['date_range']) ? $_GET['date_range'] : '30';
$from = isset($_GET['from']) ? $_GET['from'] : '';
$to = isset($_GET['to']) ? $_GET['to'] : '';
$date_sql = '';
if ($date_range === 'custom' && $from && $to) {
    $date_sql = "timestamp BETWEEN '$from 00:00:00' AND '$to 23:59:59'";
} else {
    $days = in_array($date_range, ['7','30','60']) ? (int)$date_range : 30;
    $date_sql = "timestamp >= DATE_SUB(NOW(), INTERVAL $days DAY)";
}
// Default sections if none selected
$default_sections = ['overview', 'feedback', 'intents', 'faq', 'graphs', 'recent'];

// Ensure sections is always an array
if (isset($_GET['sections']) && is_array($_GET['sections'])) {
    $sections_selected = $_GET['sections'];
    // If no checkboxes were checked, use all sections (prevents blank report)
    if (empty($sections_selected)) {
        $sections_selected = $default_sections;
    }
} else {
    // Default to all sections
    $sections_selected = $default_sections;
}

// Data queries (current period)
$users_current = fetchData($conn, "SELECT * FROM user WHERE session_id IN (SELECT DISTINCT session_id FROM chatlogs WHERE $date_sql)");
$chatlogs_current = fetchData($conn, "SELECT * FROM chatlogs WHERE $date_sql ORDER BY timestamp DESC LIMIT 15");
$feedback_current = fetchData($conn, "SELECT * FROM feedback WHERE $date_sql ORDER BY timestamp DESC");
$faq_frequency_current = fetchData($conn, "SELECT * FROM faq_frequency ORDER BY frequency DESC LIMIT 10");
$pushed_query_current = fetchData($conn, "SELECT * FROM pushed_query WHERE $date_sql ORDER BY timestamp DESC");
$daily_queries_current = fetchData($conn, "SELECT DATE(timestamp) as date, COUNT(*) as queries FROM chatlogs WHERE $date_sql GROUP BY DATE(timestamp) ORDER BY date");
$hourly_queries_current = fetchData($conn, "SELECT HOUR(timestamp) as hour, COUNT(*) as queries FROM chatlogs WHERE $date_sql GROUP BY HOUR(timestamp) ORDER BY hour");
$intents_current = fetchData($conn, "SELECT i.intent_name, COUNT(*) as count FROM chatlogs cl JOIN intent_examples ie ON cl.question LIKE CONCAT('%', ie.example_text, '%') JOIN intents i ON ie.intent_id = i.id WHERE cl.timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY i.intent_name ORDER BY count DESC LIMIT 5");

$users_current = fetchData($conn, "SELECT * FROM user WHERE session_id IN (SELECT DISTINCT session_id FROM chatlogs WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY))");
$chatlogs_current = fetchData($conn, "SELECT * FROM chatlogs WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY) ORDER BY timestamp DESC LIMIT 15");
$feedback_current = fetchData($conn, "SELECT * FROM feedback WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY) ORDER BY timestamp DESC");
$faq_frequency_current = fetchData($conn, "SELECT * FROM faq_frequency ORDER BY frequency DESC LIMIT 10");
$pushed_query_current = fetchData($conn, "SELECT * FROM pushed_query WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY) ORDER BY timestamp DESC");
$daily_queries_current = fetchData($conn, "SELECT DATE(timestamp) as date, COUNT(*) as queries FROM chatlogs WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(timestamp) ORDER BY date");
$hourly_queries_current = fetchData($conn, "SELECT HOUR(timestamp) as hour, COUNT(*) as queries FROM chatlogs WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY HOUR(timestamp) ORDER BY hour");
$intents_current = fetchData($conn, "SELECT i.intent_name, COUNT(*) as count FROM chatlogs cl JOIN intent_examples ie ON cl.question LIKE CONCAT('%', ie.example_text, '%') JOIN intents i ON ie.intent_id = i.id WHERE cl.timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY i.intent_name ORDER BY count DESC LIMIT 5");

// Previous period (60-30 days ago)
$users_prev = fetchData($conn, "SELECT * FROM user WHERE session_id IN (SELECT DISTINCT session_id FROM chatlogs WHERE timestamp BETWEEN DATE_SUB(NOW(), INTERVAL 60 DAY) AND DATE_SUB(NOW(), INTERVAL 30 DAY))");
$total_queries_prev = count(fetchData($conn, "SELECT * FROM chatlogs WHERE timestamp BETWEEN DATE_SUB(NOW(), INTERVAL 60 DAY) AND DATE_SUB(NOW(), INTERVAL 30 DAY)"));
$feedback_prev = fetchData($conn, "SELECT * FROM feedback WHERE timestamp BETWEEN DATE_SUB(NOW(), INTERVAL 60 DAY) AND DATE_SUB(NOW(), INTERVAL 30 DAY)");
$positive_feedback_prev = count(array_filter($feedback_prev, function($fb) { return isset($fb['feedback_type']) && $fb['feedback_type'] === 'like'; }));

// Current period metrics
$total_users_current = count($users_current);
$total_queries_current = count(fetchData($conn, "SELECT * FROM chatlogs WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)"));
$avg_queries_per_user_current = $total_users_current ? round($total_queries_current / $total_users_current, 1) : 0;
$positive_feedback_current = count(array_filter($feedback_current, function($fb) { return isset($fb['feedback_type']) && $fb['feedback_type'] === 'like'; }));
$negative_feedback_current = count(array_filter($feedback_current, function($fb) { return isset($fb['feedback_type']) && $fb['feedback_type'] === 'dislike'; }));
$neutral_feedback_current = count($feedback_current) - ($positive_feedback_current + $negative_feedback_current);
$feedback_rate_current = $total_queries_current ? round(($positive_feedback_current / $total_queries_current) * 100, 1) : 0;
$negative_feedback_rate_current = $total_queries_current ? round(($negative_feedback_current / $total_queries_current) * 100, 1) : 0;
$pending_queries_current = count(array_filter($pushed_query_current, function($pq) { return isset($pq['status']) && $pq['status'] === 'Not Yet'; }));
$avg_response_time_current = fetchData($conn, "SELECT AVG(TIMESTAMPDIFF(SECOND, timestamp, responded_timestamp)) as avg_time FROM responded_query WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)")[0]['avg_time'] ?? 0;
$peak_usage_current = $daily_queries_current ? max(array_column($daily_queries_current, 'queries')) : 0;
$avg_session_duration = fetchData($conn, "SELECT AVG(session_duration) as avg_duration FROM user WHERE session_id IN (SELECT DISTINCT session_id FROM chatlogs WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY))")[0]['avg_duration'] ?? 0;

// Intent recognition accuracy (proxy: positive feedback rate)
$intent_accuracy_current = $feedback_rate_current;


// Previous period metrics
$total_users_prev = count($users_prev);
$avg_queries_per_user_prev = $total_users_prev ? round($total_queries_prev / $total_users_prev, 1) : 0;
$feedback_rate_prev = $total_queries_prev ? round(($positive_feedback_prev / $total_queries_prev) * 100, 1) : 0;

// Comparisons
$users_change = $total_users_prev ? round((($total_users_current - $total_users_prev) / $total_users_prev) * 100, 1) : 0;
$queries_change = $total_queries_prev ? round((($total_queries_current - $total_queries_prev) / $total_queries_prev) * 100, 1) : 0;
$feedback_change = $feedback_rate_prev ? round(($feedback_rate_current - $feedback_rate_prev), 1) : 0;

// PDF Export
// Only run export logic and exit if:
//   - POST with export=pdf (from the export form), or
//   - GET with export=pdf (for legacy direct links)
// Never run export logic for normal page loads or filter form submissions.
// PDF Export
if ((
    $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export']) && $_POST['export'] === 'pdf'
) || (
    $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['export']) && $_GET['export'] === 'pdf'
)) {
    if (!file_exists('tcpdf/tcpdf.php')) {
        die("TCPDF library not found. Please download and place in 'tcpdf/' directory.");
    }
    require_once 'tcpdf/tcpdf.php';

    // --- Dynamic PDF Title/SubTitle Logic ---
    $date_label = '';
    switch ($date_range) {
        case '7':
            $date_label = 'Last 7 Days';
            break;
        case '30':
            $date_label = 'Last 30 Days';
            break;
        case '60':
            $date_label = 'Last 60 Days';
            break;
        case 'custom':
            $from = isset($_GET['from']) ? $_GET['from'] : '';
            $to = isset($_GET['to']) ? $_GET['to'] : '';
            $date_label = "Custom: $from to $to";
            break;
        default:
            $date_label = 'Last 30 Days';
    }

    // Initialize TCPDF
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($admin['username'] ?? 'Unknown Admin');
    $pdf->SetTitle('Mountains of the Moon University Campus Query Chatbot System Report');
    $pdf->SetMargins(15, 20, 15); // Adjusted top margin for better spacing
    $pdf->SetAutoPageBreak(TRUE, 15);
    $pdf->SetFont('helvetica', '', 12); // Default font size for content

    // --- Cover Page ---
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 24); // Larger font for cover page title
    $pdf->SetTextColor(23, 70, 162); // Dark blue
    $pdf->Image('images/MMU-Logo-long-bgwhite.png', '', '', 160, 0, 'PNG', '', 'T', true, 300, 'C', false, false, 0, false, false, false);
    $pdf->Ln(50); // Space after logo
    $pdf->Cell(0, 15, 'Mountains of the Moon University', 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->Cell(0, 12, 'Campus Query Chatbot System', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 14);
    $pdf->Cell(0, 10, 'Project Report', 0, 1, 'C');
    $pdf->Ln(20);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetTextColor(34, 34, 34); // Reset to black
    $pdf->Cell(0, 8, 'Generated by: ' . ($admin['username'] ?? 'Unknown Admin'), 0, 1, 'C');
    $pdf->Cell(0, 8, 'Admin ID: ' . ($admin['admin_id'] ?? 'Unknown'), 0, 1, 'C');
    $pdf->Cell(0, 8, 'Date: ' . date('F j, Y'), 0, 1, 'C');
    $pdf->SetTextColor(23, 70, 162);
    $pdf->Cell(0, 8, $date_label, 0, 1, 'C');
    $pdf->SetTextColor(34, 34, 34);
    $pdf->setPageMark(); // Mark page for page break

    // --- Content Page ---
    // (Removed unnecessary AddPage here to prevent blank page)
    $pdf->SetFont('helvetica', '', 12); // Ensure 12pt font for all content

// --- Chart Section ---
$chart_titles = [
    'chart1_img' => 'Daily Query Volume',
    'chart2_img' => 'Hourly Query Distribution',
    'chart3_img' => 'Feedback Summary',
];
$charts_present = false;
foreach ($chart_titles as $key => $title) {
    if (!empty($_POST[$key])) {
        $charts_present = true;
        break;
    }
}

if ($charts_present && in_array('graphs', $sections_selected)) {
    $pdf->AddPage(); // Start charts on a new page
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetTextColor(23, 70, 162);
    $pdf->Cell(0, 10, 'Chatbot Query Graphs', 0, 1, 'C');
    $pdf->Ln(15); // Increased spacing after section title
    $pdf->SetTextColor(34, 34, 34);
    $pdf->SetFont('helvetica', '', 12);

    foreach ($chart_titles as $key => $title) {
        $img = isset($_POST[$key]) ? $_POST[$key] : '';
        if ($img && strpos($img, 'base64,') !== false) {
            $base64 = explode('base64,', $img)[1];
            $img_data = base64_decode($base64);
            $img_info = @getimagesizefromstring($img_data);
            $img_width_mm = 160;
            $img_height_mm = ($img_info && $img_info[0] > 0) ? ($img_width_mm * $img_info[1] / $img_info[0]) : 80;

            // Add title with spacing
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->SetTextColor(23, 70, 162);
            $pdf->Cell(0, 10, $title, 0, 1, 'C');
            $pdf->Ln(10); // Increased space between title and graph
            $pdf->SetTextColor(34, 34, 34);
            $pdf->SetFont('helvetica', '', 12);

            // Check for page break with buffer
            if ($pdf->GetY() + $img_height_mm + 30 > $pdf->getPageHeight() - 15) {
                $pdf->AddPage();
                $pdf->Ln(10); // Add spacing at top of new page
            }

            // Render graph
            $pdf->Image('@' . $img_data, '', '', $img_width_mm, $img_height_mm, 'PNG', '', 'T', false, 300, 'C', false, false, 1, false, false, false);
            $pdf->Ln(100); // Increased space after each graph to prevent overlap
        } elseif (!empty($_POST[$key])) {
            $pdf->SetFont('helvetica', 'I', 12);
            $pdf->SetTextColor(200, 35, 51);
            $pdf->Cell(0, 10, "[$title Graph Not Captured]", 0, 1, 'C');
            $pdf->SetTextColor(34, 34, 34);
            $pdf->Ln(15);
        }
    }
    $pdf->Ln(20); // Additional spacing after chart section
}

    // --- HTML Content for Report ---
    $admin_name = isset($admin['username']) && trim($admin['username']) !== '' ? htmlspecialchars($admin['username']) : 'Unknown Admin';
    $admin_id = isset($admin['admin_id']) ? htmlspecialchars($admin['admin_id']) : 'ADM12345';
    $html = '
    <style>
        .report-content, .report-content table, .report-content th, .report-content td, .report-content div, .report-content p, .report-content span {
            font-family: helvetica, sans-serif;
            font-size: 12pt !important;
            color: #222222;
        }
        h2.section-title {
            color: #1746a2;
            font-size: 14pt;
            margin-top: 25px;
            margin-bottom: 12px;
            border-bottom: 1px solid #eeeeee;
            padding-bottom: 6px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #dddddd;
            font-size: 12pt !important;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .change-up {
            color: #28a745;
        }
        .change-down {
            color: #c82333;
        }
    </style>
    <div class="report-content">';

    // Key Performance Indicators
    if (in_array('overview', $sections_selected)) {
        $html .= '<h2 class="section-title">Key Performance Indicators</h2>';
        if ($intents_current) {
            $html .= '<table>
                <tr><th width="50%">Metric</th><th width="50%">Value (Change vs. Prev. 30 Days)</th></tr>
                <tr><td>Total Users</td><td>' . $total_users_current . ' (' . ($users_change >= 0 ? '<span class="change-up">+' : '<span class="change-down">') . $users_change . '%</span>)</td></tr>
                <tr><td>Total Queries</td><td>' . $total_queries_current . ' (' . ($queries_change >= 0 ? '<span class="change-up">+' : '<span class="change-down">') . $queries_change . '%</span>)</td></tr>
                <tr><td>Avg. Queries/User</td><td>' . $avg_queries_per_user_current . ' (' . ($avg_queries_per_user_prev ? round($avg_queries_per_user_current - $avg_queries_per_user_prev, 1) : 'N/A') . ')</td></tr>
                <tr><td>Positive Feedback Rate</td><td>' . $feedback_rate_current . '% (' . ($feedback_change >= 0 ? '<span class="change-up">+' : '<span class="change-down">') . $feedback_change . '%</span>)</td></tr>
                <tr><td>Negative Feedback Rate</td><td>' . $negative_feedback_rate_current . '%</td></tr>
                <tr><td>Pending Queries</td><td>' . $pending_queries_current . '</td></tr>
                <tr><td>Avg. Response Time (seconds)</td><td>' . round($avg_response_time_current) . '</td></tr>
                <tr><td>Peak Daily Queries</td><td>' . $peak_usage_current . '</td></tr>
                <tr><td>Avg. Session Duration (seconds)</td><td>' . round($avg_session_duration) . '</td></tr>
                <tr><td>Intent Recognition Accuracy</td><td>' . $intent_accuracy_current . '%</td></tr>
            </table>';
        } else {
            $html .= '<p>No intent data available.</p>';
        }
    }

    // Top 5 Intents
    if (in_array('intents', $sections_selected)) {
        $html .= '<h2 class="section-title">Top 5 Intents Triggered</h2>';
        if ($intents_current) {
            $html .= '<table><tr><th width="70%">Intent</th><th width="30%">Count</th></tr>';
            foreach ($intents_current as $intent) {
                $html .= '<tr><td>' . htmlspecialchars($intent['intent_name']) . '</td><td>' . $intent['count'] . '</td></tr>';
            }
            $html .= '</table>';
        } else {
            $html .= '<p>No intent data available.</p>';
        }
    }

    // Top 10 FAQs
    if (in_array('faq', $sections_selected)) {
        $html .= '<h2 class="section-title">Top 10 Frequently Asked Questions</h2>';
        if ($faq_frequency_current) {
            $html .= '<table><tr><th width="60%">Query</th><th width="20%">Frequency</th><th width="20%">Rank</th></tr>';
            foreach ($faq_frequency_current as $faq) {
                $html .= '<tr><td>' . htmlspecialchars($faq['query'] ?? 'N/A') . '</td><td>' . ($faq['frequency'] ?? 0) . '</td><td>' . ($faq['rank'] ?? 'N/A') . '</td></tr>';
            }
            $html .= '</table>';
        } else {
            $html .= '<p>No frequently asked questions recorded.</p>';
        }
    }

    // Daily Query Volume
    if (in_array('graphs', $sections_selected)) {
        $html .= '<h2 class="section-title">Daily Query Volume</h2>';
        if ($daily_queries_current) {
            $html .= '<table><tr><th width="50%">Date</th><th width="50%">Queries</th></tr>';
            foreach ($daily_queries_current as $day) {
                $html .= '<tr><td>' . ($day['date'] ?? 'N/A') . '</td><td>' . ($day['queries'] ?? 0) . '</td></tr>';
            }
            $html .= '</table>';
        } else {
            $html .= '<p>No query volume data available.</p>';
        }
    }

    // Queries by Hour
    if (in_array('graphs', $sections_selected)) {
        $html .= '<h2 class="section-title">Queries by Hour</h2>';
        if ($hourly_queries_current) {
            $html .= '<table><tr><th width="50%">Hour (24h)</th><th width="50%">Queries</th></tr>';
            for ($hour = 0; $hour < 24; $hour++) {
                $queries = array_filter($hourly_queries_current, function($hq) use ($hour) { return $hq['hour'] == $hour; });
                $query_count = $queries ? reset($queries)['queries'] : 0;
                $html .= '<tr><td>' . sprintf("%02d:00 - %02d:59", $hour, $hour) . '</td><td>' . $query_count . '</td></tr>';
            }
            $html .= '</table>';
        } else {
            $html .= '<p>No hourly query data available.</p>';
        }
    }

    // Recent Chat Activity
    if (in_array('recent', $sections_selected)) {
        $html .= '<h2 class="section-title">Recent Chat Activity (Last 15)</h2>';
        if ($chatlogs_current) {
            $html .= '<table><tr><th width="35%">Question</th><th width="35%">Response</th><th width="30%">Timestamp</th></tr>';
            foreach ($chatlogs_current as $log) {
                $html .= '<tr><td>' . htmlspecialchars($log['question'] ?? 'N/A') . '</td><td>' . htmlspecialchars($log['response_text'] ?? 'N/A') . '</td><td>' . ($log['timestamp'] ?? 'N/A') . '</td></tr>';
            }
            $html .= '</table>';
        } else {
            $html .= '<p>No recent chat activity.</p>';
        }
    }

    // Approval Section
    $html .= '<div style="page-break-before:always;"></div>';
    $html .= '<div style="margin-top:50px;">
        <h2 style="color:#1746a2; font-size:14pt; border-bottom:1px solid #dddddd; padding-bottom:6px; margin-bottom:15px;">Approval</h2>
        <div style="font-size:12pt; margin-bottom:12px;"><b>Approved by:</b></div>
        <div style="font-size:12pt; margin-bottom:10px;">Supervisor\'s Name</div>
        <div style="font-size:12pt; margin-bottom:10px;">Designation</div>
        <div style="font-size:12pt; margin-bottom:20px;">Signature: <span style="display:inline-block; min-width:200px; border-bottom:1px solid #222222;"> </span></div>
        <div style="font-size:12pt;">Date: <span style="display:inline-block; min-width:160px; border-bottom:1px solid #222222;"> </span></div>
    </div>';

    $html .= '</div>';

    // Write HTML to PDF
    $pdf->writeHTML($html, true, false, true, false, '');

    // Output PDF
    if (ob_get_length()) ob_clean();
    $pdf->Output('Chatbot_Performance_Report_' . date('Ymd') . '.pdf', 'D');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title></title>
    <link rel="shortcut icon" href="images/mmu_logo_- no bg.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link href="css/bootstrap.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .report-header { text-align: center; padding: 20px; background: #fff; border-bottom: 2px solid #eee; margin-bottom: 20px; }
        .report-header h1 { color: #2c3e50; font-weight: 600; margin-bottom: 10px; }
        .report-header h3 { color: #666; font-weight: 400; }
        .metric-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; margin-bottom: 20px; transition: transform 0.2s ease; }
        .metric-card:hover { transform: translateY(-2px); }
        .metric-card h5 { color: #666; margin-bottom: 10px; font-size: 16px; text-transform: uppercase; }
        .metric-card p { color: #2c3e50; font-size: 24px; font-weight: 600; margin: 0; }
        .change-up { color: #28a745; font-size: 14px; }
        .change-down { color: #dc3545; font-size: 14px; }
        .section { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .section h2 { color: #2c3e50; font-weight: 600; margin-bottom: 15px; }
        .table { width: 100%; margin-top: 15px; border-collapse: collapse; }
        .table th { background: #f8f9fa; padding: 12px; font-weight: 600; text-align: left; border-bottom: 2px solid #eee; }
        .table td { padding: 12px; border-bottom: 1px solid #eee; }
        .table tr:hover { background: #fafafa; }
        .btn-primary { background: #2c3e50; border: none; padding: 10px 20px; border-radius: 4px; color: #fff; transition: background 0.3s ease; }
        .btn-primary:hover { background: #34495e; color: #fff; }
        .progress { height: 20px; margin-top: 10px; }
        .progress-bar-positive { background-color: #28a745; }
        .progress-bar-negative { background-color: #dc3545; }
        .progress-bar-neutral { background-color: #6c757d; }
        @media (max-width: 768px) { .metric-card { margin-bottom: 15px; } }
    </style>
</head>

<body>
    <div class="container-fluid sb1">
        <div class="row">
            <h3 style="color: #fff; display: flex; justify-content: center; align-items: center; margin: 1rem 0;">
                Campus Query Chatbot Admin Panel
                <span class="admin-notification" id="notification" style="margin-left: 10rem;">
                    <i class="fa fa-commenting-o" style="color: #fff;"></i>
                    <span class="admin-badge" id="not-yet-count" style="background: #c82333; padding: 5px; border-radius: 50%; color: #fff;"></span>
                </span>
            </h3>
        </div>
    </div>

    <div class="container-fluid sb2">
        <div class="row">
            <div class="sb2-1 col-md-3" style="padding: 0; position: fixed;">
                <div class="sb2-12">
                    <ul>
                        <li>
                            <?php if (!empty($admin['image']) && file_exists($admin['image'])): ?>
                                <img src="<?= htmlspecialchars($admin['image']) ?>" alt="Admin Image" style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%;">
                            <?php else: ?>
                                <img src="images/default_admin_icon.png" alt="Default Icon" style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%;">
                            <?php endif; ?>
                        </li> 
                        <h6 style="margin-left: 8rem;">Admin ID: <?php echo htmlspecialchars($admin['admin_id']); ?></h6>
                        <h6 style="margin-left: 8rem;">Name: <?php echo htmlspecialchars($admin['username']); ?></h6>                   
                    </ul>
                </div>
                <div class="sb2-13">
                    <ul class="collapsible" data-collapsible="accordion">
                        <li><a href="admin.php"><i class="fa fa-bar-chart"></i> Dashboard</a></li>
                        <li><a href="admin-setting.php"><i class="fa fa-cogs"></i> Account Information</a></li>
                        <li><a href="javascript:void(0)" class="collapsible-header"><i class="fas fa-chart-line"></i> Chatbot Usage Analytics</a>
                            <div class="collapsible-body left-sub-menu">
                                <ul><li><a href="chatlogs.php">Chatlogs</a></li><li><a href="user_interactions.php">User Interaction Data</a></li><li><a href="FAQ.php">Frequently Asked Questions</a></li></ul>
                            </div>
                        </li>
                        <li><a href="javascript:void(0)" class="collapsible-header"><i class="fas fa-database"></i> AI Chatbot Model </a>
                            <div class="collapsible-body left-sub-menu">
                                <ul><li><a href="chatbot-data.php" target="_blank">AI Chatbot Model </a></li></ul>
                            </div>
                        </li>
                        <li><a href="javascript:void(0)" class="collapsible-header"><i class="fas fa-comment-alt"></i> Feedback</a>
                            <div class="collapsible-body left-sub-menu">
                                <ul><li><a href="feedback.php">Feedback</a></li></ul>
                            </div>
                        </li>
                        <li><a href="javascript:void(0)" class="collapsible-header"><i class="fa fa-commenting-o"></i> Pushed Queries</a>
                            <div class="collapsible-body left-sub-menu">
                                <ul><li><a href="pushed_query.php">All Queries</a></li></ul>
                            </div>
                        </li>
                        <li><a href="javascript:void(0)" class="collapsible-header menu-active"><i class="fas fa-file-alt"></i> Report Overview</a>
                            <div class="collapsible-body left-sub-menu">
                                <ul><li><a href="report.php">Report</a></li></ul>
                            </div>
                        </li>
                        <li><a href="http://127.0.0.1:5000" class="collapsible-header" target="_blank"><i class="fas fa-robot" aria-hidden="true"></i>Chatbot</a>
                            
                        </li>
                        <li><a href="./admin-logout.php" class="collapsible-header"><i class="fas fa-sign-out-alt" aria-hidden="true"></i>Logout</a>
                            
                        </li>
                    </ul>
                </div>
            </div>

            <div class="sb2-2 col-md-9">
                <div class="sb2-2-2">
                    <ul>
                        <li><a href="#"><i class="fa fa-home"></i> Home</a></li>
                        <li class="active-bre"><a href="#">Performance Report</a></li>
                        <li class="page-back"><a href="admin.php"><i class="fa fa-backward"></i> Back</a></li>
                    </ul>
                </div>

                <div class="sb2-2-3">
                    <style>
                        .report-header {
                            margin-bottom: 30px;
                            font-family: 'Inter', 'Open Sans', Arial, sans-serif;
                        }
                        .report-header .logo {
                            max-width: 120px;
                            width: 100%;
                            height: auto;
                            display: inline-block;
                            margin-bottom: 10px;
                        }
                        .report-header .uni {
                            text-align: center;
                            margin-bottom: 0;
                            font-weight: 800;
                            font-size: 2.1rem;
                            color: #234;
                            letter-spacing: 0.5px;
                        }
                        .report-header .system {
                            text-align: center;
                            margin: 0;
                            font-weight: 600;
                            font-size: 1.35rem;
                            color: #345;
                        }
                        .report-header .proj {
                            text-align: center;
                            margin: 10px 0 0 0;
                            font-weight: 500;
                            font-size: 1.1rem;
                            color: #567;
                        }
                        .report-header .meta {
                            display: flex;
                            justify-content: center;
                            gap: 30px;
                            margin-top: 15px;
                            margin-bottom: 8px;
                            font-size: 1.08em;
                            color: #222;
                            flex-wrap: wrap;
                        }
                        .report-header .meta span {
                            background: #f3f6fa;
                            border-radius: 6px;
                            padding: 5px 16px;
                            margin: 2px 0;
                            font-weight: 500;
                            box-shadow: 0 1px 2px rgba(60,80,100,0.04);
                        }
                        .report-header .date-range-badge {
                            display: inline-block;
                            margin: 0 auto;
                            margin-top: 4px;
                            padding: 3px 18px;
                            font-size: 1em;
                            color: #2c3e50;
                            background: #e7f1fa;
                            border-radius: 14px;
                            font-weight: 500;
                            letter-spacing: 0.2px;
                        }
                    </style>
                    <div class="report-header">
                        <div style="text-align:center; margin-bottom: 10px;">
                            <img src="images/mmu_logo_- no bg.png" alt="MMU Logo" class="logo">
                        </div>
                        <div class="uni">MOUNTAINS OF THE MOON UNIVERSITY</div>
                        <div class="system">Campus Query Chatbot System</div>
                        <div class="proj">Project Report</div>
                        <div class="meta">
                            <span>Generated by: <b><?php echo isset($admin['username']) && trim($admin['username']) !== '' ? htmlspecialchars($admin['username']) : 'Unknown Admin'; ?></b></span>
                            <span>Admin id: <b><?php echo isset($admin['admin_id']) ? htmlspecialchars($admin['admin_id']) : 'Unknown'; ?></b></span>
                            <span>Date: <b><?php echo date('F j, Y'); ?></b></span>
                        </div>
                        <div style="text-align:center;">
                            <span class="date-range-badge">
                            <?php
                                $date_label = 'Last 30 Days';
                                if (isset($_GET['date_range'])) {
                                    switch ($_GET['date_range']) {
                                        case '7': $date_label = 'Last 7 Days'; break;
                                        case '30': $date_label = 'Last 30 Days'; break;
                                        case '60': $date_label = 'Last 60 Days'; break;
                                        case 'custom':
                                            $from = isset($_GET['from']) ? $_GET['from'] : '';
                                            $to = isset($_GET['to']) ? $_GET['to'] : '';
                                            $date_label = "Custom: $from to $to";
                                            break;
                                    }
                                }
                                echo $date_label . ' - ' . date('F j, Y');
                            ?>
                            </span>
                        </div>
                    </div>

                    <div style="text-align:center; margin-top: 16px; margin-bottom: 10px;">
                        <?php
                            // Build export URL with current filters
                            $export_params = $_GET;
                            $export_params['export'] = 'pdf';
                            $export_url = '?' . http_build_query($export_params);
                        ?>
                        <form id="pdfExportForm" method="POST" action="report.php" target="_blank" style="display:inline;">
  <?php foreach ($_GET as $key => $value): ?>
    <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
  <?php endforeach; ?>
  <input type="hidden" name="export" value="pdf">
  <input type="hidden" name="chart1_img" id="chart1_img">
  <input type="hidden" name="chart2_img" id="chart2_img">
  <input type="hidden" name="chart3_img" id="chart3_img"><!-- Add more as needed for additional charts -->
  <button type="submit" class="btn-export-pdf">Export to PDF</button>
</form>
<script>
// Ensure charts are rendered before capturing images for PDF export
// If you see missing graphs, check that the charts are visible on screen before exporting.
document.getElementById('pdfExportForm').addEventListener('submit', function(e) {
    var chart1 = document.getElementById('dailyQueryVolumeChart');
    var chart2 = document.getElementById('hourlyQueryChart');
    var chart3 = document.getElementById('feedbackChart'); // Example third chart (add more as needed)

    document.getElementById('chart1_img').value = (chart1 && chart1.toDataURL) ? chart1.toDataURL('image/png') : '';
    document.getElementById('chart2_img').value = (chart2 && chart2.toDataURL) ? chart2.toDataURL('image/png') : '';
    document.getElementById('chart3_img').value = (chart3 && chart3.toDataURL) ? chart3.toDataURL('image/png') : '';
    // Add more lines as needed for additional charts
});
</script>
                    </div>

                    <!-- Filter Form -->
<style>
.btn-export-pdf {
    background: #2563eb;
    color: #fff !important;
    font-weight: 600;
    border: none;
    border-radius: 7px;
    padding: 10px 28px;
    font-size: 1.09em;
    text-decoration: none;
    box-shadow: 0 2px 8px rgba(60,80,180,0.07);
    transition: background 0.18s, box-shadow 0.18s;
    display: inline-block;
    margin-top: 0;
    margin-bottom: 0;
}
.btn-export-pdf:hover {
    background: #1746a2;
    color: #fff;
    box-shadow: 0 4px 16px rgba(60,80,180,0.13);
    text-decoration: none;
}
</style>

                    <div class="report-filter-section" style="margin-top: 70px;">
                        <?php
// Validation for custom date range
$filter_error = '';
if (isset($_GET['date_range']) && $_GET['date_range'] === 'custom') {
    if (empty($_GET['from']) || empty($_GET['to'])) {
        $filter_error = 'Please select both From and To dates for custom range.';
    }
}
?>
<form method="GET" style="margin-bottom: 0;">
                            <div class="report-filter-row">
                                <div class="report-filter-col">
                                    <label>Date Range:</label>
                                    <select name="date_range" id="date_range" data-no-materialize onchange="toggleCustomDates(this.value); this.form.submit();" style="width:130px; padding:6px 12px; border-radius:4px; border:1px solid #ccc; background:#fff; display:inline-block;">
                                        <option value="7" <?php if(isset($_GET['date_range']) && $_GET['date_range']==='7') echo 'selected'; ?>>Last 7 Days</option>
                                        <option value="30" <?php if(!isset($_GET['date_range']) || $_GET['date_range']==='30') echo 'selected'; ?>>Last 30 Days</option>
                                        <option value="60" <?php if(isset($_GET['date_range']) && $_GET['date_range']==='60') echo 'selected'; ?>>Last 60 Days</option>
                                        <option value="custom" <?php if(isset($_GET['date_range']) && $_GET['date_range']==='custom') echo 'selected'; ?>>Custom</option>
                                    </select>
                                </div>
                                <div class="report-filter-col" id="custom_dates" style="display: <?php echo (isset($_GET['date_range']) && $_GET['date_range']==='custom') ? 'block' : 'none'; ?>;">
    <?php if (!empty($filter_error)): ?>
        <div style="color: red; font-weight: bold; margin-bottom: 8px;"> <?php echo $filter_error; ?> </div>
    <?php endif; ?>
                                    <label>From:</label>
                                    <input type="date" name="from" class="report-filter-input" value="<?php echo isset($_GET['from']) ? htmlspecialchars($_GET['from']) : ''; ?>">
                                    <label>To:</label>
                                    <input type="date" name="to" class="report-filter-input" value="<?php echo isset($_GET['to']) ? htmlspecialchars($_GET['to']) : ''; ?>">
                                </div>
                                <div class="report-filter-col">
                                    <label>Sections to include:</label><br>
                                    <div class="report-filter-checkboxes">
                                        <?php
                                            $sections = [
                                                'overview' => 'Overview',
                                                'feedback' => 'Feedback Sentiment',
                                                'intents' => 'Top Intents',
                                                'faq' => 'FAQ',
                                                'graphs' => 'Graphs',
                                                'recent' => 'Recent Chat Activity'
                                            ];
                                            foreach ($sections as $key => $label) {
                                                // Ensure the checkbox is checked if the section is selected
                                                $checked = in_array($key, $sections_selected) ? 'checked' : '';
                                                echo "<label class='report-filter-checkbox'><input type='checkbox' name='sections[]' value='$key' $checked> $label</label>";
                                            }
                                        ?>
                                    </div>
                                </div>
                                <div class="report-filter-col report-filter-btn-col">
                                    <button type="submit" class="report-filter-btn">Apply Filters</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <script>
                        function toggleCustomDates(val) {
                            document.getElementById('custom_dates').style.display = (val === 'custom') ? 'block' : 'none';
                        }
                    </script>
                    <style>
                        .report-filter-section {
                            margin-bottom: 24px;
                            background: #f8f9fa;
                            border-radius: 10px;
                            box-shadow: 0 2px 8px rgba(44,62,80,0.06);
                            padding: 18px 18px 8px 18px;
                            max-width: 100%;
                        }
                        .report-filter-row {
                            display: flex;
                            flex-wrap: wrap;
                            gap: 18px;
                            align-items: flex-end;
                        }
                        .report-filter-col {
                            flex: 1 1 200px;
                            min-width: 180px;
                            margin-bottom: 10px;
                        }
                        .report-filter-checkboxes {
                            display: flex;
                            flex-wrap: wrap;
                            gap: 10px;
                            margin-top: 4px;
                        }
                        .report-filter-checkbox {
                            font-weight: 400;
                            margin-right: 12px;
                            display: flex;
                            align-items: center;
                            gap: 4px;
                        }
                        .report-filter-input {
                            border-radius: 6px;
                            border: 1px solid #ced4da;
                            padding: 6px 10px;
                            width: 100%;
                            font-size: 1em;
                            margin-bottom: 4px;
                            background: #fff;
                            appearance: none;
                            box-shadow: none;
                        }
                        .report-filter-select {
                            background: #fff;
                            border: 1px solid #ced4da;
                            color: #222;
                            font-size: 1em;
                            height: 38px;
                            padding: 6px 10px;
                            width: 100%;
                            box-shadow: none;
                            appearance: menulist;
                        }
                        .report-filter-select:focus {
                            outline: 2px solid #2c3e50;
                            box-shadow: none;
                        }
                        .report-filter-select::-ms-expand {
                            display: none;
                        }
                        .report-filter-select::-webkit-inner-spin-button,
                        .report-filter-select::-webkit-outer-spin-button {
                            -webkit-appearance: none;
                            margin: 0;
                        }
                        .report-filter-col label {
                            font-weight: 600;
                            margin-bottom: 2px;
                            display: block;
                        }
                        .report-filter-btn-col {
                            min-width: 120px;
                            text-align: right;
                        }
                        .report-filter-btn {
                            background: #2c3e50;
                            color: #fff;
                            border: none;
                            border-radius: 6px;
                            padding: 8px 18px;
                            font-weight: 600;
                            font-size: 1em;
                            transition: background 0.2s;
                            cursor: pointer;
                        }
                        .report-filter-btn:hover {
                            background: #1a232b;
                        }
                        @media (max-width: 900px) {
                            .report-filter-row { flex-direction: column; gap: 0; }
                            .report-filter-col, .report-filter-btn-col { min-width: 100%; }
                        }
                    </style>
                    <div class="container" style="width: 100%;">

                        <!-- Key Metrics (Overview) -->
                        <?php if (in_array('overview', $sections_selected)): ?>
                        <div class="row">
                            <div class="col-md-3 col-sm-6">
                                <div class="metric-card">
                                    <h5>Total Users</h5>
                                    <p><?php echo $total_users_current; ?> <span class="<?php echo $users_change >= 0 ? 'change-up' : 'change-down'; ?>"><?php echo $users_change >= 0 ? '+' : ''; ?><?php echo $users_change; ?>%)</span></p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="metric-card">
                                    <h5>Total Queries</h5>
                                    <p><?php echo $total_queries_current; ?> <span class="<?php echo $queries_change >= 0 ? 'change-up' : 'change-down'; ?>"><?php echo $queries_change >= 0 ? '+' : ''; ?><?php echo $queries_change; ?>%)</span></p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="metric-card">
                                    <h5>Avg. Queries/User</h5>
                                    <p><?php echo $avg_queries_per_user_current; ?></p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="metric-card">
                                    <h5>Positive Feedback</h5>
                                    <p><?php echo $positive_feedback_current; ?> (<?php echo $feedback_rate_current; ?>%)</p>
                                </div>
                            </div>
                            <!-- Add more summary cards as needed -->
                        </div>
                        <?php endif; ?>

                        <!-- Feedback Sentiment -->
                        <?php if (in_array('feedback', $sections_selected)): ?>
                        <div class="section">
                            <h2>Feedback Sentiment</h2>
                            <div class="progress">
                                <div class="progress-bar progress-bar-positive" style="width: <?php echo $feedback_rate_current; ?>%;"><?php echo $positive_feedback_current; ?></div>
                                <div class="progress-bar progress-bar-negative" style="width: <?php echo $negative_feedback_rate_current; ?>%;"><?php echo $negative_feedback_current; ?></div>
                                <div class="progress-bar progress-bar-neutral" style="width: <?php echo $total_queries_current ? round(($neutral_feedback_current / $total_queries_current) * 100, 1) : 0; ?>%;"><?php echo $neutral_feedback_current; ?></div>
                            </div>
                            <small>Positive | Negative | Neutral</small>
                        </div>
                        <?php endif; ?>


                        <!-- Top Intents -->
                        <?php if (in_array('intents', $sections_selected)): ?>
                        <div class="section">
                            <h2>Top 5 Intents Triggered</h2>
                            <?php if ($intents_current): ?>
                                <table class="table">
                                    <thead><tr><th>Intent</th><th>Count</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($intents_current as $intent): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($intent['intent_name']); ?></td>
                                                <td><?php echo $intent['count']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p>No intent data available.</p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>


                        <!-- Top FAQs -->
                        <?php if (in_array('faq', $sections_selected)): ?>
                        <div class="section">
                            <h2>Top 10 Frequently Asked Questions</h2>
                            <?php if ($faq_frequency_current): ?>
                                <table class="table">
                                    <thead><tr><th>Query</th><th>Frequency</th><th>Rank</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($faq_frequency_current as $faq): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($faq['query'] ?? 'N/A'); ?></td>
                                                <td><?php echo $faq['frequency'] ?? 0; ?></td>
                                                <td><?php echo $faq['rank'] ?? 'N/A'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p>No frequently asked questions recorded.</p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Daily Query Volume -->
                        <?php if (in_array('graphs', $sections_selected)): ?>
                        <div class="section">
                            <h2>Daily Query Volume</h2>
                            <canvas id="dailyQueryVolumeChart" height="100"></canvas>
                            <?php if ($daily_queries_current): ?>
                                <table class="table">
                                    <thead><tr><th>Date</th><th>Number of Queries</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($daily_queries_current as $day): ?>
                                            <tr>
                                                <td><?php echo $day['date'] ?? 'N/A'; ?></td>
                                                <td><?php echo $day['queries'] ?? 0; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p>No query volume data available.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Queries by Hour -->
                        <div class="section">
                            <h2>Chatlogs: Queries by Hour</h2>
                            <canvas id="hourlyQueryChart" height="100"></canvas>
                            <?php if ($hourly_queries_current): ?>
                                <table class="table">
                                    <thead><tr><th>Hour (24h)</th><th>Number of Queries</th></tr></thead>
                                    <tbody>
                                        <?php for ($hour = 0; $hour < 24; $hour++): ?>
                                            <?php $queries = array_filter($hourly_queries_current, function($hq) use ($hour) { return $hq['hour'] == $hour; }); ?>
                                            <tr>
                                                <td><?php echo sprintf("%02d:00 - %02d:59", $hour, $hour); ?></td>
                                                <td><?php echo $queries ? reset($queries)['queries'] : 0; ?></td>
                                            </tr>
                                        <?php endfor; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p>No hourly query data available.</p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Recent Chat Activity -->
<?php if (in_array('recent', $sections_selected)): ?>
<div class="section">
    <h2>Recent Chat Activity (Last 15)</h2>
    <?php if ($chatlogs_current): ?>
        <table class="table">
            <thead><tr><th>Question</th><th>Response</th><th>Timestamp</th></tr></thead>
            <tbody>
                <?php foreach ($chatlogs_current as $log): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($log['question'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($log['response_text'] ?? 'N/A'); ?></td>
                        <td><?php echo $log['timestamp'] ?? 'N/A'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No recent chat activity.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/main.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/materialize.min.js"></script>
<script>
// Remove Materialize wrapper for date_range select if it exists
window.addEventListener('DOMContentLoaded', function() {
    try {
        var sel = document.querySelector('select[name="date_range"]');
        if (sel && sel.parentElement && sel.parentElement.classList.contains('select-wrapper')) {
            var wrapper = sel.parentElement;
            var parent = wrapper.parentElement;
            parent.insertBefore(sel, wrapper);
            wrapper.remove();
            sel.style.display = 'inline-block';
        }
    } catch(e) { console.warn('Materialize select cleanup error:', e); }
});
</script>
    <script src="js/custom.js"></script>
    <script>
        function updateNotificationCount() {
            fetch('fetch_queries.php')
                .then(response => response.json())
                .then(data => {
                    const notYetCount = document.getElementById('not-yet-count');
                    if (notYetCount) {
                        notYetCount.textContent = data.not_yet_count || 0;
                        notYetCount.style.display = data.not_yet_count > 0 ? 'inline' : 'none';
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        updateNotificationCount();
        setInterval(updateNotificationCount, 60000);

        // Daily Query Volume Chart
const dailyCtx = document.getElementById('dailyQueryVolumeChart').getContext('2d');
const dailyQueries = <?php echo json_encode($daily_queries_current); ?>;
new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: dailyQueries.map(d => d.date),
        datasets: [{
            label: 'Queries',
            data: dailyQueries.map(d => d.queries),
            borderColor: '#2c3e50',
            fill: false,
            tension: 0.4 // Make the line smooth
        }]
    },
    options: {
        scales: { y: { beginAtZero: true } }
    }
});

        // Queries by Hour Chart
        const hourlyCtx = document.getElementById('hourlyQueryChart').getContext('2d');
        const hourlyQueries = <?php echo json_encode($hourly_queries_current); ?>;
        const hourlyData = Array(24).fill(0);
        hourlyQueries.forEach(hq => hourlyData[parseInt(hq.hour)] = parseInt(hq.queries));
        new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: Array.from({length: 24}, (_, i) => `${i}:00`),
                datasets: [{
                    label: 'Queries by Hour',
                    data: hourlyData,
                    backgroundColor: '#2c3e50',
                    borderColor: '#2c3e50',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true },
                    x: { title: { display: true, text: 'Hour of Day (24h)' } }
                }
            }
        });
    </script>
</body>
</html>