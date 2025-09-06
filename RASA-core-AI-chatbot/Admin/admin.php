<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session to check authentication
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ./admin-login.php");
    exit();
}

// Database Configuration
$host = "localhost";
$username = "root";
$password = "";
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

// Widget Data
try {
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT session_id) as count FROM user");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_users = $row['count'] ?? 0;
} catch (Exception $e) {
    error_log("Query failed: " . $e->getMessage());
    $total_users = 0;
}

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM feedback WHERE DATE(timestamp) = CURDATE()");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();
$row = $result->fetch_assoc();
$feedback_today = $row['count'] ?? 0;
} catch (Exception $e) {
    error_log("Query failed: " . $e->getMessage());
    $feedback_today = 0;
}

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM pushed_query WHERE status = 'Not Yet'");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();
$row = $result->fetch_assoc();
$pushed_awaiting = $row['count'] ?? 0;
} catch (Exception $e) {
    error_log("Query failed: " . $e->getMessage());
    $pushed_awaiting = 0;
}

// Graph Data
try {
    $stmt = $conn->prepare("SELECT DATE(timestamp) as date, COUNT(*) as count FROM chatlogs GROUP BY DATE(timestamp) ORDER BY date");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $chatlogs_per_day = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Query failed: " . $e->getMessage());
    $chatlogs_per_day = [];
}

// Queries per week
try {
    $stmt = $conn->prepare("SELECT YEARWEEK(timestamp, 1) as week, COUNT(*) as count FROM chatlogs GROUP BY week ORDER BY week");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $chatlogs_per_week = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Query failed: " . $e->getMessage());
    $chatlogs_per_week = [];
}

// Queries per month
try {
    $stmt = $conn->prepare("SELECT DATE_FORMAT(timestamp, '%Y-%m') as month, COUNT(*) as count FROM chatlogs GROUP BY month ORDER BY month");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $chatlogs_per_month = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Query failed: " . $e->getMessage());
    $chatlogs_per_month = [];
}

// Queries per year
try {
    $stmt = $conn->prepare("SELECT YEAR(timestamp) as year, COUNT(*) as count FROM chatlogs GROUP BY year ORDER BY year");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $chatlogs_per_year = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Query failed: " . $e->getMessage());
    $chatlogs_per_year = [];
}

try {
    $stmt = $conn->prepare("SELECT HOUR(timestamp) as hour, COUNT(*) as count FROM chatlogs GROUP BY HOUR(timestamp) ORDER BY hour");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();
$chatlogs_by_hour = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Query failed: " . $e->getMessage());
    $chatlogs_by_hour = [];
}

try {
    $stmt = $conn->prepare("SELECT answer, COUNT(*) as count FROM faq_cache GROUP BY answer ORDER BY count DESC LIMIT 5");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();
$faq_cache_answers = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Query failed: " . $e->getMessage());
    $faq_cache_answers = [];
}

try {
    $stmt = $conn->prepare("SELECT query, frequency FROM faq_frequency ORDER BY frequency DESC LIMIT 5");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();
$faq_frequency = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Query failed: " . $e->getMessage());
    $faq_frequency = [];
}

try {
    $stmt = $conn->prepare("SELECT feedback_type, COUNT(*) as count FROM feedback GROUP BY feedback_type");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();
$feedback_counts = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Query failed: " . $e->getMessage());
    $feedback_counts = [];
}

try {
    $stmt = $conn->prepare("SELECT DATE(timestamp) as date, feedback_type, COUNT(*) as count FROM feedback GROUP BY DATE(timestamp), feedback_type ORDER BY date");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();
$feedback_over_time = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Query failed: " . $e->getMessage());
    $feedback_over_time = [];
}

try {
    $stmt = $conn->prepare("SELECT session_duration, number_of_queries FROM user");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();
$user_sessions = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Query failed: " . $e->getMessage());
    $user_sessions = [];
}

// Calculate peak activity range
$max_range_count = 0;
$start_hour = null;
$end_hour = null;

for ($i = 0; $i < count($chatlogs_by_hour); $i++) {
    $current_range_count = $chatlogs_by_hour[$i]['count'];
    $current_start = $chatlogs_by_hour[$i]['hour'];
    $current_end = $current_start;

    for ($j = $i + 1; $j < count($chatlogs_by_hour); $j++) {
        if ($chatlogs_by_hour[$j]['hour'] == $current_end + 1) {
            $current_range_count += $chatlogs_by_hour[$j]['count'];
            $current_end = $chatlogs_by_hour[$j]['hour'];
        } else {
            break;
        }
    }

    if ($current_range_count > $max_range_count) {
        $max_range_count = $current_range_count;
        $start_hour = $current_start;
        $end_hour = $current_end;
    }
}

$time_range = ($start_hour !== null && $end_hour !== null) 
    ? sprintf("%02d:00 - %02d:00", $start_hour, $end_hour)
    : "No significant range";
$max_range_count = $max_range_count;
$total_queries_hour = array_sum(array_column($chatlogs_by_hour, 'count'));

// Active Users Calculation
try {
    $time_threshold_5min = date('Y-m-d H:i:s', strtotime('-5 minutes'));
    $time_threshold_24hr = date('Y-m-d H:i:s', strtotime('-24 hours'));

    $active_users_query = "
        SELECT 
            COUNT(DISTINCT CASE WHEN c.timestamp >= ? THEN c.session_id END) as active_users_5min,
            COUNT(DISTINCT CASE WHEN c.timestamp >= ? THEN c.session_id END) as active_users_24hr
        FROM chatlogs c
        WHERE c.timestamp >= ?
    ";

    $stmt = $conn->prepare($active_users_query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param('sss', $time_threshold_5min, $time_threshold_24hr, $time_threshold_24hr);
    $stmt->execute();
    $result = $stmt->get_result();
$active_users_data = $result->fetch_all(MYSQLI_ASSOC);

    $active_users_5min = (int)($active_users_data[0]['active_users_5min'] ?? 0);
    $active_users_24hr = (int)($active_users_data[0]['active_users_24hr'] ?? 0);
} catch (Exception $e) {
    error_log("Active users query failed: " . $e->getMessage());
    $active_users_5min = 0;
    $active_users_24hr = 0;
}

// Total Queries in the last 24 hours
try {
    $time_threshold_24hr = date('Y-m-d H:i:s', strtotime('-24 hours'));
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM chatlogs WHERE timestamp >= ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param('s', $time_threshold_24hr);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_queries_24hr = $row['count'] ?? 0;
} catch (Exception $e) {
    error_log("Query failed: " . $e->getMessage());
    $total_queries_24hr = 0;
}
// Prepare data for JSON response
$data = ['not_yet_count' => $pushed_awaiting];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Chatbot Panel</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="images/mmu_logo_- no bg.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700%7CJosefin+Sans:600,700" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link href="css/materialize.css" rel="stylesheet">
    <link href="css/bootstrap.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet" />
    <link href="css/style-mob.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: #f4f7fa; font-family: Arial, sans-serif; }
        .chart-container { background: #fff; padding: 20px; margin-bottom: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); width: 100%; }
        #chatlogsPerDayChart, #userChart {
            max-height: 260px !important;
            height: 260px !important;
        }
        .text-insight { font-size: 14px; color: #666; margin-top: 10px; }
        h2 { color: #1a1a2e; margin-bottom: 15px; }
        .widget-card { background: #fff; padding: 15px; text-align: center; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); }
        .widget-card h5 { color: #1a1a2e; margin-bottom: 10px; }
        .widget-card p { font-size: 24px; font-weight: 600; color: rgb(20, 4, 112); margin: 0; }
        .pie-chart { max-width: 300px; margin: 0 auto; }

        /* Widgets */
        .widget-card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;

        }
        .widget-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }
        .widget-card i {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 2rem;
            opacity: 0.2;
            color: #3b82f6;
        }
        .widget-card h5 {
            font-size: 1.1rem;
            color: #1a1a2e;
            margin-bottom: 10px;
        }
        .widget-card p {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1e3a8a;
        }

    .status-widgets-row {
    display: flex;
    flex-wrap: wrap;
    gap: 32px;
    justify-content: center;
    margin-bottom: 32px;
}
.rasa-status-widget {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.07);
    padding: 24px 30px;
    margin: 0;
    width: 370px;
    min-height: 220px;
    display: flex;
    flex-direction: column;
    align-items: center;
    font-family: 'Poppins', Arial, sans-serif;
    justify-content: flex-start;
}
@media (max-width: 900px) {
    .status-widgets-row {
        flex-direction: column;
        gap: 18px;
        align-items: center;
    }
    .rasa-status-widget {
        width: 95vw;
        max-width: 420px;
    }
}
.spinner {
    border: 4px solid #eaeaea;
    border-top: 4px solid #3b82f6;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    animation: spin 1s linear infinite;
    display: inline-block;
    vertical-align: middle;
    margin-right: 9px;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.rasa-status-title {
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 10px;
    color: #1e3a8a;
    letter-spacing: 0.5px;
}
.rasa-status-indicator {
    display: inline-block;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    margin-right: 8px;
    vertical-align: middle;
}
.rasa-online {
    background: #0abf53;
    box-shadow: 0 0 6px #0abf53cc;
}
.rasa-offline {
    background: #e74c3c;
    box-shadow: 0 0 6px #e74c3ccc;
}
.rasa-status-details {
    margin-top: 10px;
    font-size: 1.05rem;
    color: #333;
    text-align: center;
}
.rasa-status-refresh {
    margin-top: 14px;
    padding: 6px 18px;
    background: #002147;
    color: #fff;
    border: none;
    border-radius: 5px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.18s;
}
.rasa-status-refresh:hover {
    background: #0abf53;
    color: #fff;
}
</style>
</head>

<body>
    <!--== MAIN CONTAINER ==-->
    <div class="container-fluid sb1">
        <div class="row">
            <h3 style="color: #fff; justify-content: center; align-items: center; display: flex; margin-top: 1rem;">
                Campus Query Chatbot Admin Panel
                <span class="admin-notification" id="notification">
                    <i class="fa fa-commenting-o" aria-hidden="true" style="color: #fff; margin-left:10rem;"></i>
                    <span class="admin-badge" id="not-yet-count" style="background-color: #c82333; padding: 5px; border-radius: 40%; color:#f9f9f9;"></span>
                </span>
            </h3>
        </div>
    </div>

    <!--== BODY CONTAINER ==-->
    <div class="container-fluid sb2">
        <div class="row">
            <div class="sb2-1" style="position: fixed;">
                <!--== USER INFO ==-->
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
                <!--== LEFT MENU ==-->
                <div class="sb2-13">
                    <ul class="collapsible" data-collapsible="accordion">
                        <li><a href="admin.php" class="menu-active"><i class="fa fa-bar-chart" aria-hidden="true"></i> Dashboard</a></li>
                        <li><a href="admin-setting.php"><i class="fa fa-cogs" aria-hidden="true"></i> Account Information</a></li>
                        <li><a href="javascript:void(0)" class="collapsible-header"><i class="fas fa-chart-line" aria-hidden="true"></i> Chatbot Usage Analytics</a>
                            <div class="collapsible-body left-sub-menu">
                                <ul>
                                    <li><a href="chatlogs.php">Chatlogs</a></li>
                                    <li><a href="user_interactions.php">User Interaction Data</a></li>
                                    <li><a href="FAQ.php">Frequently Asked Questions</a></li>
                                </ul>
                            </div>
                        </li>
                        <li><a href="javascript:void(0)" class="collapsible-header"><i class="fas fa-database" aria-hidden="true"></i> AI Chatbot Model </a>
                            <div class="collapsible-body left-sub-menu">
                                <ul>
                                    <li><a href="chatbot-data.php" target="_blank">AI Chatbot Model </a></li>
                                </ul>
                            </div>
                        </li>
                        <li><a href="javascript:void(0)" class="collapsible-header"><i class="fas fa-comment-alt" aria-hidden="true"></i> Feedback</a>
                            <div class="collapsible-body left-sub-menu">
                                <ul>
                                    <li><a href="feedback.php">feedback</a></li>
                                </ul>
                            </div>
                        </li>
                        <li><a href="javascript:void(0)" class="collapsible-header"><i class="fa fa-commenting-o" aria-hidden="true"></i> Pushed Queries</a>
                            <div class="collapsible-body left-sub-menu">
                                <ul>
                                    <li><a href="pushed_query.php">All Queries</a></li>
                                </ul>
                            </div>
                        </li>
                        <li><a href="javascript:void(0)" class="collapsible-header"><i class="fas fa-file-alt" aria-hidden="true"></i> Report Overview</a>
                            <div class="collapsible-body left-sub-menu">
                                <ul>
                                    <li><a href="report.php">Report</a></li>
                                </ul>
                            </div>
                        </li>

                        
                        <li><a href="http://127.0.0.1:5000" class="collapsible-header" target="_blank"><i class="fas fa-robot" aria-hidden="true"></i>Chatbot</a>
                            
                        </li>
                        <li><a href="./admin-logout.php" class="collapsible-header"><i class="fas fa-sign-out-alt" aria-hidden="true"></i>Logout</a>
                            
                        </li>
                    </ul>
                </div>
            </div>

            <!--== BODY INNER CONTAINER ==-->
            <div class="sb2-2">
                <!--== breadcrumbs ==-->
                <div class="sb2-2-2">
                    <ul>
                        <li><a href="#"><i class="fa fa-home" aria-hidden="true"></i> Home</a></li>
                        <li class="active-bre"><a href="#"> Dashboard</a></li>
                        <li class="page-back"><a href="admin.php"><i class="fa fa-backward" aria-hidden="true"></i> Back</a></li>
                    </ul>
                </div>

                <!--== DASHBOARD INFO ==-->
                <div class="container" style="width: 100%;">
                <h1 class="text-center mb-4"><i class="fas fa-chart-pie mr-2"></i> Chatbot Dashboard</h1>

                    <!-- Widgets -->
                    <div class="row mb-4" style="padding: 20px; display: flex; justify-content: center;">


                        <div class="col-md-3 col-sm-4 mb-3">
                            <div class="widget-card">
                                <i class="fas fa-users"></i>
                                <h5>Total Users</h5>
                                <p><?php echo $total_users; ?></p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="widget-card">
                                <i class="fas fa-user-check"></i>
                                <h5>Active Users</h5>
                                <p><?php echo $active_users_5min; ?></p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="widget-card">
                                <i class="fas fa-exchange-alt"></i>
                                <h5>Interactions <br>In past 24hrs</h5>
                                <p><?php echo $active_users_24hr; ?></p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-4 mb-3">
                            <div class="widget-card">
                                <i class="fas fa-thumbs-up"></i>
                                <h5>Feedback Today</h5>
                                <p><?php echo $feedback_today; ?></p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-4 mb-3">
                            <div class="widget-card">
                                <i class="fas fa-hourglass-half"></i>
                                <h5>Awaiting Queries</h5>
                                <p><?php echo $pushed_awaiting; ?></p>
                            </div>
                        </div>
                    </div>


                    <div class="status-widgets-row">
    <!-- Rasa Chatbot Status Widget -->
    <div id="rasa-status-widget" class="rasa-status-widget">
        <div class="rasa-status-title">
            <i class="fas fa-robot" style="color:#002147; margin-right:8px;"></i>
            Rasa Chatbot Status
        </div>
        <div id="rasa-status-content">
            <span class="rasa-status-indicator rasa-offline"></span>
            <span class="spinner" aria-label="Loading"></span>
            <span style="font-weight:600;color:#e74c3c;">Checking...</span>
        </div>
        <button class="rasa-status-refresh" onclick="fetchRasaStatus()">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
    </div>
    <!-- End Rasa Chatbot Status Widget -->

    <!-- Flask App Status Widget -->
    <div id="flask-status-widget" class="rasa-status-widget">
        <div class="rasa-status-title">
            <i class="fas fa-flask" style="color:#e67e22; margin-right:8px;"></i>
            Flask App Status
        </div>
        <div id="flask-status-content">
            <span class="rasa-status-indicator rasa-offline"></span>
            <span class="spinner" aria-label="Loading"></span>
            <span style="font-weight:600;color:#e74c3c;">Checking...</span>
        </div>
        <button class="rasa-status-refresh" onclick="fetchFlaskStatus()">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
    </div>
    <!-- End Flask App Status Widget -->
</div>

<!-- Chatlogs: Queries by Hour -->
<section class="chart-container">
                        <h2>Chatlogs: Queries by Hour</h2>
                        <canvas id="chatlogsByHourChart"></canvas>
                        <div style="background-color:#f9f9f9; padding: 20px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); font-family: Arial, sans-serif; color: #333;">
                            <p style="margin: 10px 0; font-size: 16px; line-height: 1.6;">
                                <strong>Overview:</strong> Displays query frequency by hour (0-23).
                            </p>
                            <p style="margin: 10px 0; font-size: 16px; line-height: 1.6;">
                                <strong style="color: #2c3e50;">Peak Activity Range: </strong> Users are most active between <?php echo $time_range; ?> (<?php echo $max_range_count; ?> queries).
                            </p>
                            <p style="margin: 10px 0; font-size: 16px; line-height: 1.6;">
                                <strong style="color: #2c3e50;">Total Queries:</strong> <?php echo $total_queries_24hr; ?> queries in the last 24 hours.
                            </p>
                            <p style="margin: 10px 0; font-size: 16px; line-height: 1.6;">
                                <strong style="color: #2c3e50;">Insight:</strong> 
                                <?php echo ($start_hour !== null && $end_hour !== null) 
                                    ? "User activity peaks during $time_range. Consider enhancing support during these hours."
                                    : "No significant peak range detected. Activity is evenly distributed."; ?>
                            </p>
                        </div>
                    </section>
                    <!-- Chatlogs: Queries Per Day -->
                    <section class="chart-container">
                        <h2 style="font-size: 2rem; font-weight: 600; color: #1a1a2e;"><i class="fa fa-chart-line"></i> Chatlogs: Queries Per Week</h2>
                        <canvas id="chatlogsPerDayChart" height="120"></canvas>
                        <div class="text-insight">This chart shows the number of user queries received each day.</div>
                    </section>

                    <!-- Comparative Analysis Chart -->
                    <section class="chart-container">
                        <h2><i class="fa fa-chart-bar"></i>Queries Per Month</h2>
                        <canvas id="chatlogsComparativeChart" height="120"></canvas>
                        <div class="text-insight"></div>
                    </section>

          

                    

                    
                    

                    <div style="display: flex; justify-content: center; padding: 10px;">
                        <!-- FAQ Frequency: Top 5 Queries -->
                        <section class="chart-container" style="margin: 10px;">
                            <h2>FAQ Frequency</h2>
                            <canvas id="faqFrequencyChart"></canvas>
                            <div class="text-insight" style="background-color:#f9f9f9; padding: 20px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); font-family: Arial, sans-serif; color: #333;">
                                <p style="margin: 10px 0; font-size: 16px; line-height: 1.6;">
                                    <strong style="color: #2c3e50;">Overview:</strong> Top 5 most frequent queries.
                                </p>
                                <p style="margin: 10px 0; font-size: 16px; line-height: 1.6;">
                                    <strong style="color: #2c3e50;">Insights:</strong> "<?php echo $faq_frequency[0]['query'] ?? 'N/A'; ?>" leads (<?php echo $faq_frequency[0]['frequency'] ?? 0; ?> times).
                                </p>
                                <p style="margin: 10px 0; font-size: 16px; line-height: 1.6;">
                                    <strong style="color: #2c3e50;">Stats:</strong> Total frequency: <?php echo array_sum(array_column($faq_frequency, 'frequency')); ?>.
                                </p>
                            </div>

                        </section>

                        <!-- FAQ Cache: Top 5 Cached Answers -->
                        <section class="chart-container" style="margin: 10px;">
                            <h2>FAQ Cache</h2>
                            <canvas id="faqCacheChart"></canvas>
                            <div class="text-insight" style="background-color:#f9f9f9; padding: 20px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); font-family: Arial, sans-serif; color: #333;">
                                <p style="margin: 10px 0; font-size: 16px; line-height: 1.6;">
                                    <strong style="color: #2c3e50;">Overview:</strong> Shows the most frequent cached answers.
                                </p>
                                <p style="margin: 10px 0; font-size: 16px; line-height: 1.6;">
                                    <strong style="color: #2c3e50;">Insights:</strong> "<?php echo substr($faq_cache_answers[0]['answer'] ?? 'N/A', 0, 20); ?>..." tops the list (<?php echo $faq_cache_answers[0]['count'] ?? 0; ?> times).
                                </p>
                                <p style="margin: 10px 0; font-size: 16px; line-height: 1.6;">
                                    <strong style="color: #2c3e50;">Stats:</strong> Total of top 5: <?php echo array_sum(array_column($faq_cache_answers, 'count')); ?>.
                                </p>
                            </div>

                        </section>
                    </div>

                    <div style="display: flex; justify-content: center; padding: 10px;">
                        <!-- Feedback: Like vs Dislike -->
                        <section class="chart-container" style="margin: 10px;">
                            <h2>Feedback: Like vs Dislike</h2>
                            <div class="pie-chart">
                                <canvas id="feedbackChart"></canvas>
                            </div> <br>
                            <div class="text-insight">
                                <p><strong>Overview:</strong> Distribution of feedback types.</p>
                                <p><strong>Insights:</strong> dislikes (<?php echo $feedback_counts[0]['count'] ?? 0; ?>) vs likes (<?php echo $feedback_counts[1]['count'] ?? 0; ?>).</p>
                                <p><strong>Stats:</strong> Total: <?php echo array_sum(array_column($feedback_counts, 'count')); ?>, Like %: <?php echo array_sum(array_column($feedback_counts, 'count')) ? round(($feedback_counts[0]['count'] ?? 0) / array_sum(array_column($feedback_counts, 'count')) * 100, 1) : 0; ?>%.</p>
                            </div>
                        </section>

                        <!-- Feedback: Over Time -->
                        <section class="chart-container" style="margin: 10px;">
                            <h2>Feedback: Over Time</h2>
                            <canvas id="feedbackOverTimeChart"></canvas>
                            <div class="text-insight"><br><br><br><br><br>
                                <p><strong>Overview:</strong> Tracks feedback sentiment over time.</p>
                                <p><strong>Trends:</strong> Consistent likes with occasional dislikes</p>
                                <p><strong>Stats:</strong> Total: <?php echo array_sum(array_column($feedback_over_time, 'count')); ?>.</p>
                            </div>
                        </section>
                    </div>

                    <!-- User: Session Duration vs Queries -->
                    <section class="chart-container">
                        <h2>User: Session Duration vs Queries</h2>
                        <canvas id="userChart"></canvas>
                        <div class="text-insight">
                            <p><strong>Overview:</strong> Compares session duration (seconds) to query count.</p>
                            <p><strong>Insights:</strong> Longest session: <?php echo max(array_column($user_sessions, 'session_duration') ?: [0]); ?>s with <?php echo max(array_column($user_sessions, 'number_of_queries') ?: [0]); ?> queries.</p>
                            <p><strong>Stats:</strong> Avg duration: <?php echo count($user_sessions) ? round(array_sum(array_column($user_sessions, 'session_duration')) / count($user_sessions), 1) : 0; ?>s, Avg queries: <?php echo count($user_sessions) ? round(array_sum(array_column($user_sessions, 'number_of_queries')) / count($user_sessions), 1) : 0; ?>.</p>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Notification update function (unchanged)
function updateNotificationCount() {
    fetch('fetch_queries.php')
        .then(response => response.json())
        .then(data => {
            const notYetCount = document.getElementById('not-yet-count');
            if (notYetCount) {
                if (data.not_yet_count > 0) {
                    notYetCount.textContent = data.not_yet_count;
                    notYetCount.style.display = 'inline';
                } else {
                    notYetCount.style.display = 'none';
                }
            }
        })
        .catch(error => console.error('Error fetching notification count:', error));
}

updateNotificationCount();
setInterval(updateNotificationCount, 60000);

// Modern color palette and gradient function
const modernColors = {
    primary: '#3b82f6',
    secondary: '#ec4899',
    tertiary: '#10b981',
    quaternary: '#f59e0b'
};

function createGradient(ctx, chartArea, colorStart, colorEnd) {
    const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
    gradient.addColorStop(0, colorStart);
    gradient.addColorStop(1, colorEnd);
    return gradient;
}

// Shared chart options for modern look
const modernChartOptions = {
    plugins: {
        legend: {
            labels: {
                font: { family: 'Poppins', size: 12 },
                color: '#1a1a2e'
            }
        }
    },
    animation: {
        duration: 1000,
        easing: 'easeOutQuart'
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { font: { family: 'Poppins', size: 12 }, color: '#1a1a2e' }
        },
        y: {
            grid: { color: 'rgba(0, 0, 0, 0.05)' },
            ticks: { font: { family: 'Poppins', size: 12 }, color: '#1a1a2e' }
        }
    }
};

// Chart: Queries Per Week
const chatlogsPerDayCtx = document.getElementById('chatlogsPerDayChart').getContext('2d');
new Chart(chatlogsPerDayCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_map(function($row) { return 'W' . $row['week']; }, $chatlogs_per_week)); ?>,
        datasets: [{
            label: 'Queries Per Week',
            data: <?php echo json_encode(array_column($chatlogs_per_week, 'count')); ?>,
            borderColor: modernColors.primary,
            backgroundColor: function(context) {
                const chart = context.chart;
                const {ctx, chartArea} = chart;
                if (!chartArea) return;
                return createGradient(ctx, chartArea, 'rgba(59, 130, 246, 0.2)', 'rgba(59, 130, 246, 0.8)');
            },
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    },
    options: {
        ...modernChartOptions,
        scales: {
            ...modernChartOptions.scales,
            y: { ...modernChartOptions.scales.y, beginAtZero: true }
        }
    }
});

// Chart: Comparative Queries Per Day, Week, Month, Year
// Chart: Comparative Queries Per Month
const comparativeCtx = document.getElementById('chatlogsComparativeChart').getContext('2d');

const monthLabels = <?php echo json_encode(array_column($chatlogs_per_month, 'month')); ?>;

new Chart(comparativeCtx, {
    type: 'line',
    data: {
        labels: monthLabels,
        datasets: [
            {
                label: 'Queries Per Month',
                data: <?php echo json_encode(array_column($chatlogs_per_month, 'count')); ?>,
                borderColor: modernColors.primary,
                backgroundColor: function(context) {
                    const chart = context.chart;
                    const {ctx, chartArea} = chart;
                    if (!chartArea) return;
                    return createGradient(ctx, chartArea, 'rgba(59, 130, 246, 0.2)', 'rgba(59, 130, 246, 0.8)');
                },
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6
            }
        ]
    },
    options: {
        ...modernChartOptions,
        plugins: {
            ...modernChartOptions.plugins,
            legend: {
                ...modernChartOptions.plugins.legend,
                labels: { usePointStyle: true, font: { family: 'Poppins', size: 12 } }
            }
        },
        scales: {
            ...modernChartOptions.scales,
            y: { ...modernChartOptions.scales.y, beginAtZero: true }
        }
    }
});

// Chart: Queries by Hour
const chatlogsByHourCtx = document.getElementById('chatlogsByHourChart').getContext('2d');
new Chart(chatlogsByHourCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_map(function($h) { return sprintf("%02d:00", $h['hour']); }, $chatlogs_by_hour)); ?>,
        datasets: [{
            label: 'Number of Queries',
            data: <?php echo json_encode(array_column($chatlogs_by_hour, 'count')); ?>,
            backgroundColor: function(context) {
                const chart = context.chart;
                const {ctx, chartArea} = chart;
                if (!chartArea) return;
                return createGradient(ctx, chartArea, 'rgba(59, 130, 246, 0.2)', 'rgba(59, 130, 246, 0.8)');
            },
            borderRadius: 8
        }]
    },
    options: {
        ...modernChartOptions,
        indexAxis: 'y',
        scales: {
            x: {
                ...modernChartOptions.scales.x,
                beginAtZero: true,
                title: { display: true, text: 'Number of Queries', font: { family: 'Poppins', size: 14 } }
            },
            y: {
                ...modernChartOptions.scales.y,
                title: { display: true, text: 'Hour of Day (24-hour)', font: { family: 'Poppins', size: 14 } }
            }
        }
    }
});

// Chart: FAQ Frequency
const faqFrequencyCtx = document.getElementById('faqFrequencyChart').getContext('2d');
new Chart(faqFrequencyCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($faq_frequency, 'query')); ?>,
        datasets: [{
            label: 'Frequency',
            data: <?php echo json_encode(array_column($faq_frequency, 'frequency')); ?>,
            backgroundColor: function(context) {
                const chart = context.chart;
                const {ctx, chartArea} = chart;
                if (!chartArea) return;
                return createGradient(ctx, chartArea, 'rgba(59, 130, 246, 0.2)', 'rgba(59, 130, 246, 0.8)');
            },
            borderRadius: 8
        }]
    },
    options: {
        ...modernChartOptions,
        scales: {
            ...modernChartOptions.scales,
            y: { ...modernChartOptions.scales.y, beginAtZero: true }
        }
    }
});

// Chart: FAQ Cache
const faqCacheCtx = document.getElementById('faqCacheChart').getContext('2d');
new Chart(faqCacheCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_map(function($row) { return substr($row['answer'], 0, 20) . '...'; }, $faq_cache_answers)); ?>,
        datasets: [{
            label: 'Count',
            data: <?php echo json_encode(array_column($faq_cache_answers, 'count')); ?>,
            backgroundColor: function(context) {
                const chart = context.chart;
                const {ctx, chartArea} = chart;
                if (!chartArea) return;
                return createGradient(ctx, chartArea, 'rgba(59, 130, 246, 0.2)', 'rgba(59, 130, 246, 0.8)');
            },
            borderRadius: 8
        }]
    },
    options: {
        ...modernChartOptions,
        scales: {
            ...modernChartOptions.scales,
            y: { ...modernChartOptions.scales.y, beginAtZero: true }
        }
    }
});

// Chart: Feedback Pie
const feedbackCtx = document.getElementById('feedbackChart').getContext('2d');
new Chart(feedbackCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode(array_column($feedback_counts, 'feedback_type')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($feedback_counts, 'count')); ?>,
            backgroundColor: ['rgba(59, 130, 246, 0.2)', 'rgba(59, 130, 246, 0.8)'],
            borderColor: '#fff',
            borderWidth: 2
        }]
    },
    options: {
        ...modernChartOptions,
        maintainAspectRatio: false,
        responsive: true,
        plugins: {
            ...modernChartOptions.plugins,
            legend: { position: 'bottom' }
        }
    }
});

// Chart: Feedback Over Time
const feedbackOverTimeCtx = document.getElementById('feedbackOverTimeChart').getContext('2d');
const dates = [...new Set(<?php echo json_encode(array_column($feedback_over_time, 'date')); ?>)];
const likes = dates.map(date => {
    const row = <?php echo json_encode($feedback_over_time); ?>.find(r => r.date === date && r.feedback_type === 'like');
    return row ? row.count : 0;
});
const dislikes = dates.map(date => {
    const row = <?php echo json_encode($feedback_over_time); ?>.find(r => r.date === date && r.feedback_type === 'dislike');
    return row ? row.count : 0;
});
new Chart(feedbackOverTimeCtx, {
    type: 'line',
    data: {
        labels: dates,
        datasets: [
            {
                label: 'Likes',
                data: likes,
                borderColor: modernColors.secondary,
                backgroundColor: function(context) {
                    const chart = context.chart;
                    const {ctx, chartArea} = chart;
                    if (!chartArea) return;
                    return createGradient(ctx, chartArea, 'rgba(59, 130, 246, 0.2)', 'rgba(59, 130, 246, 0.8)');
                },
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6
            },
            {
                label: 'Dislikes',
                data: dislikes,
                borderColor: modernColors.primary,
                backgroundColor: function(context) {
                    const chart = context.chart;
                    const {ctx, chartArea} = chart;
                    if (!chartArea) return;
                    return createGradient(ctx, chartArea, 'rgba(59, 130, 246, 0.2)', 'rgba(59, 130, 246, 0.8)');
                },
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6
            }
        ]
    },
    options: {
        ...modernChartOptions,
        scales: {
            ...modernChartOptions.scales,
            y: { ...modernChartOptions.scales.y, beginAtZero: true }
        }
    }
});

// Chart: User Sessions
const userCtx = document.getElementById('userChart').getContext('2d');

// Create a copy of the data for trend line calculation
const sessionData = <?php echo json_encode(array_map(function($row) { return ['x' => $row['session_duration'], 'y' => $row['number_of_queries']]; }, $user_sessions)); ?>;

// Calculate trend line using linear regression
function calculateTrendLine(data) {
    let sumX = 0, sumY = 0, sumXY = 0, sumX2 = 0;
    const n = data.length;
    
    for (let i = 0; i < n; i++) {
        sumX += data[i].x;
        sumY += data[i].y;
        sumXY += data[i].x * data[i].y;
        sumX2 += data[i].x * data[i].x;
    }
    
    const slope = (n * sumXY - sumX * sumY) / (n * sumX2 - sumX * sumX);
    const intercept = (sumY - slope * sumX) / n;
    
    return { slope, intercept };
}

const trend = calculateTrendLine(sessionData);

// Get min and max x values for the trend line
let minX = Math.min(...sessionData.map(point => point.x));
let maxX = Math.max(...sessionData.map(point => point.x));

// Create trend line data points
const trendLineData = [
    { x: minX, y: trend.slope * minX + trend.intercept },
    { x: maxX, y: trend.slope * maxX + trend.intercept }
];

// Group by session duration (x), average y for duplicates
const grouped = {};
sessionData.forEach(pt => {
    if (!grouped[pt.x]) grouped[pt.x] = [];
    grouped[pt.x].push(pt.y);
});
const averagedData = Object.entries(grouped).map(([x, yArr]) => ({
    x: Number(x),
    y: yArr.reduce((a, b) => a + b, 0) / yArr.length
}));
// Sort by x
averagedData.sort((a, b) => a.x - b.x);

const sessionLabels = averagedData.map(pt => pt.x + 's');
const sessionCounts = averagedData.map(pt => pt.y);

new Chart(userCtx, {
    type: 'line',
    data: {
        labels: sessionLabels,
        datasets: [
            {
                label: 'Sessions',
                data: sessionCounts,
                borderColor: modernColors.primary,
                backgroundColor: function(context) {
                    const chart = context.chart;
                    const {ctx, chartArea} = chart;
                    if (!chartArea) return;
                    return createGradient(ctx, chartArea, 'rgba(59, 130, 246, 0.2)', 'rgba(59, 130, 246, 0.8)');
                },
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6
            }
        ]
    },
    options: {
        ...modernChartOptions,
        plugins: {
            ...modernChartOptions.plugins,
            legend: {
                ...modernChartOptions.plugins.legend,
                labels: {
                    usePointStyle: true,
                    padding: 15,
                    font: {
                        family: 'Poppins',
                        size: 12
                    }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(44, 62, 80, 0.9)',
                titleColor: '#ecf0f1',
                bodyColor: '#ecf0f1',
                borderColor: 'rgba(255, 255, 255, 0.1)',
                borderWidth: 1,
                cornerRadius: 8,
                padding: 10,
                displayColors: true,
                callbacks: {
                    title: function(tooltipItems) {
                        return tooltipItems[0].dataset.label;
                    },
                    label: function(context) {
                        if (context.dataset.label === 'Trend Line') {
                            return '';
                        }
                        return `Duration: ${context.parsed.x} s, Queries: ${context.parsed.y}`;
                    }
                }
            }
        },
        scales: {
            x: {
                ...modernChartOptions.scales.x,
                title: { 
                    display: true, 
                    text: 'Duration (s)', 
                    font: { family: 'Poppins', size: 14 },
                    padding: { top: 10 }
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)',
                    drawBorder: false
                }
            },
            y: {
                ...modernChartOptions.scales.y,
                title: { 
                    display: true, 
                    text: 'Queries', 
                    font: { family: 'Poppins', size: 14 },
                    padding: { bottom: 10 }
                },
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)',
                    drawBorder: false
                }
            }
        },
        animation: {
            duration: 1500,
            easing: 'easeOutQuart'
        }
    }
});
</script>
    <script>
// Rasa Status AJAX fetch logic
async function fetchRasaStatus() {
    const content = document.getElementById('rasa-status-content');
    content.innerHTML = '<span class="rasa-status-indicator rasa-offline"></span> <span class="spinner" aria-label="Loading"></span><span style="font-weight:600;color:#e74c3c;">Checking...</span>';
    try {
        const resp = await fetch('rasa_status.php');
        const data = await resp.json();
        if (data.status === 'online') {
            content.innerHTML = `
                <span class="rasa-status-indicator rasa-online"></span>
                <span style="font-weight:600;color:#0abf53;">Online</span>
                <div class="rasa-status-details">
                    <strong>Model:</strong> ${data.model ? data.model : 'Unknown'}<br>
                    <strong>Rasa Version:</strong> ${data.rasa_version ? data.rasa_version : 'N/A'}<br>
                    <strong>URL:</strong> <a href="${data.url}" target="_blank">${data.url}</a>
                </div>
            `;
        } else {
            content.innerHTML = `
                <span class="rasa-status-indicator rasa-offline"></span>
                <span style="font-weight:600;color:#e74c3c;">Offline</span>
                <div class="rasa-status-details">
                    <strong>Error:</strong> ${data.error ? data.error : 'Unable to connect.'}
                    ${data.http_code ? `<br><strong>HTTP:</strong> ${data.http_code}` : ''}
                </div>
            `;
        }
    } catch (e) {
        content.innerHTML = `
            <span class="rasa-status-indicator rasa-offline"></span>
            <span class="spinner" aria-label="Loading"></span><span style="font-weight:600;color:#e74c3c;">Offline</span>
            <div class="rasa-status-details">
                <strong>Error:</strong> Could not fetch status.
            </div>
        `;
    }
}
window.addEventListener('DOMContentLoaded', fetchRasaStatus);
</script>
<script>
// Rasa Status AJAX fetch logic
async function fetchRasaStatus() {
    const content = document.getElementById('rasa-status-content');
    content.innerHTML = '<span class="rasa-status-indicator rasa-offline"></span> <span class="spinner" aria-label="Loading"></span><span style="font-weight:600;color:#e74c3c;">Checking...</span>';
    try {
        const resp = await fetch('rasa_status.php');
        const data = await resp.json();
        if (data.status === 'online') {
            content.innerHTML = `
                <span class="rasa-status-indicator rasa-online"></span>
                <span style="font-weight:600;color:#0abf53;">Online</span>
                <div class="rasa-status-details">
                    <strong>Model:</strong> ${data.model ? data.model : 'Unknown'}<br>
                    <strong>Rasa Version:</strong> ${data.rasa_version ? data.rasa_version : 'N/A'}<br>
                    <strong>URL:</strong> <a href="${data.url}" target="_blank">${data.url}</a>
                </div>
            `;
        } else {
            content.innerHTML = `
                <span class="rasa-status-indicator rasa-offline"></span>
                <span class="spinner" aria-label="Loading"></span><span style="font-weight:600;color:#e74c3c;">Offline</span>
                <div class="rasa-status-details">
                    <strong>Error:</strong> ${data.error ? data.error : 'Unable to connect.'}
                    ${data.http_code ? `<br><strong>HTTP:</strong> ${data.http_code}` : ''}
                </div>
            `;
        }
    } catch (e) {
        content.innerHTML = `
            <span class="rasa-status-indicator rasa-offline"></span>
            <span class="spinner" aria-label="Loading"></span><span style="font-weight:600;color:#e74c3c;">Offline</span>
            <div class="rasa-status-details">
                <strong>Error:</strong> Could not fetch status.
            </div>
        `;
    }
}

// Flask Status AJAX fetch logic
async function fetchFlaskStatus() {
    const content = document.getElementById('flask-status-content');
    content.innerHTML = '<span class="rasa-status-indicator rasa-offline"></span> <span class="spinner" aria-label="Loading"></span><span style="font-weight:600;color:#e74c3c;">Checking...</span>';
    try {
        const resp = await fetch('flask_status.php');
        const data = await resp.json();
        if (data.status === 'online') {
            let banner = data.info && data.info.banner ? `<div style=\"margin-top:6px;font-size:0.98rem;color:#555;\"><strong>Banner:</strong> ${data.info.banner}</div>` : '';
            let proc = data.info && data.info.process ? `<div style=\"margin-top:6px;font-size:0.98rem;color:#555;\"><strong>Process:</strong> <code style=\"font-size:0.95em;\">${data.info.process}</code></div>` : '';
            content.innerHTML = `
                <span class="rasa-status-indicator rasa-online"></span>
                <span style="font-weight:600;color:#0abf53;">Online</span>
                <div class="rasa-status-details">
                    <strong>URL:</strong> <a href="${data.info.url}" target="_blank">${data.info.url}</a>
                    ${banner}
                    ${proc}
                </div>
            `;
        } else {
            content.innerHTML = `
                <span class="rasa-status-indicator rasa-offline"></span>
                <span class="spinner" aria-label="Loading"></span><span style="font-weight:600;color:#e74c3c;">Offline</span>
                <div class="rasa-status-details">
                    <strong>Details:</strong> ${data.details ? data.details : 'Unable to connect.'}
                </div>
            `;
        }
    } catch (e) {
        content.innerHTML = `
            <span class="rasa-status-indicator rasa-offline"></span>
            <span class="spinner" aria-label="Loading"></span><span style="font-weight:600;color:#e74c3c;">Offline</span>
            <div class="rasa-status-details">
                <strong>Error:</strong> Could not fetch status.
            </div>
        `;
    }
}
window.addEventListener('DOMContentLoaded', () => {
    fetchRasaStatus();
    fetchFlaskStatus();
});
</script>
<script src="js/main.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/materialize.min.js"></script>
    <script src="js/custom.js"></script>
</body>
</html>