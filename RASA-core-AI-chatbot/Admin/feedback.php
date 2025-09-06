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
$conn = new mysqli("localhost", "root", "", "chatbot_db");

// Check connection
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

// Handle table operations
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['empty_table'])) {
        $conn->query("TRUNCATE TABLE feedback");
        echo "<p>Feedback table has been emptied.</p>";
    }
    if (isset($_POST['delete_record'])) {
        $id = $_POST['record_id'];
        $conn->query("DELETE FROM feedback WHERE id = $id");
        echo "<p>Feedback record deleted.</p>";
    }
}

// =============


?>


<!DOCTYPE html>
<html lang="en">

<head>
    <title>AdminChatbotpannel</title>
    <!-- META TAGS -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- FAV ICON(BROWSER TAB ICON) -->
    <link rel="shortcut icon" href="images/mmu_logo_- no bg.png" type="image/x-icon">
    <!-- GOOGLE FONT -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700%7CJosefin+Sans:600,700" rel="stylesheet">
    <!-- FONTAWESOME ICONS -->
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- ALL CSS FILES -->
    <link href="css/materialize.css" rel="stylesheet">
    <link href="css/bootstrap.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet" />
    <!-- RESPONSIVE.CSS ONLY FOR MOBILE AND TABLET VIEWS -->
    <link href="css/style-mob.css" rel="stylesheet" />

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }
        .table-container {
            margin: 20px 0;
            background-color: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #002147;
            color: white;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .buttons {
            margin: 10px 0;
        }
        .btn {
            padding: 8px 15px;
            margin-right: 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-export { background-color: #002147; color: white; }
        .btn-delete { background-color: #f44336; color: white; }
        .btn-empty { background-color: #002147; color: white; }
    </style>
</head>

<body>
    <!--== MAIN CONTRAINER ==-->
    <div class="container-fluid sb1">
        <div class="row">
            <!--== LOGO ==-->
            
            
            
            <!--== MY ACCCOUNT ==-->
           <h3 style="color: #fff; justify-content: center; align-items: center; display: flex; margin-top: 1rem; justify-content: center; align-items: center;">
                Campus Query Chatbot Admin Panel
                <span class="admin-notification" id="notification">
             <i class="fa fa-commenting-o" aria-hidden="true" style="color: #fff; margin-left:10rem;"></i>
                <span class="admin-badge" id="not-yet-count" style="background-color: #c82333; padding: 5px; border-radius: 40%; color:#f9f9f9;"></span>
            </span>
            </h3>
           
        </div>
    </div>

    <!--== BODY CONTNAINER ==-->
    <div class="container-fluid sb2">
        <div class="row">
            <div class="sb2-1" style="position: fixed;">
                <!--== USER INFO ==-->
                <div class="sb2-12">
                    <ul>
                        <li>
                            <?php if ($admin['image'] && file_exists($admin['image'])): ?>
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
                        <li><a href="admin.php"><i class="fa fa-bar-chart" aria-hidden="true"></i> Dashboard</a>
                        </li>
						<li><a href="admin-setting.php"><i class="fa fa-cogs" aria-hidden="true"></i> Account Information</a>
                        </li>

                        <li><a href="javascript:void(0)" class="collapsible-header"><i class="fas fa-chart-line" aria-hidden="true"></i> Chatbot Usage Analytics</a>
                            <div class="collapsible-body left-sub-menu">
                                <ul>
                                <li><a href="chatlogs.php">Chatlogs</a>
                                    </li>
                                    <li><a href="user_interactions.php">User Interaction Data</a>
                                    </li>
                                    <li><a href="FAQ.php">Frequently Asked Questions</a>
                                    </li>
                                    
                                </ul>
                            </div>
                        </li>
                        



                                                <li><a href="javascript:void(0)" class="collapsible-header"><i class="fas fa-comment-alt" aria-hidden="true"></i> AI Chatbot Model </a>
                            <div class="collapsible-body left-sub-menu">
                                <ul>
                                    <li><a href="chatbot-data.php" target="_blank">AI Chatbot Model </a></li>
                                    
                                </ul>
                            </div>
                        </li>


                        <li><a href="javascript:void(0)" class="collapsible-header menu-active"><i class="fa fa-bullhorn " aria-hidden="true"></i> Feedback</a>
                            <div class="collapsible-body left-sub-menu">
                                <ul>
                                    <li><a href="feedback.php">feedback</a>
                                    </li>
                                    
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
                                    <li><a href="report.php">Report</a>
                                    </li>
                                    
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
                        <li><a href="#"><i class="fa fa-home" aria-hidden="true"></i> Home</a>
                        </li>
                        <li class="active-bre"><a href="#"> Seminars</a>
                        </li>
                        <li class="page-back"><a href="admin.php"><i class="fa fa-backward" aria-hidden="true"></i> Back</a>
                        </li>
                    </ul>
                </div>

                <!--== User Details ==-->
                <div class="sb2-2-3">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="box-inn-sp">
                                <div class="inn-title">
									<h4>feedbacks</h4>
                                    <p>All about students like name, student id, phone, email, country, city and more</p>
                                </div>
                                <div class="tab-inn">
                                    <div class="table-responsive table-desi">
                                        <!-- Summary Table -->
                                    <div class="table-container">
                                        <h2>Feedback Summary</h2>
                                        <table>
                                            <tr>
                                                <th>Metric</th>
                                                <th>Value</th>
                                            </tr>
                                            <?php
                                            $total_feedback = $conn->query("SELECT COUNT(*) as total FROM feedback")->fetch_assoc()['total'];
                                            $feedback_types = $conn->query("SELECT feedback_type, COUNT(*) as count FROM feedback GROUP BY feedback_type");
                                            $daily_feedback = $conn->query("SELECT COUNT(*) as daily FROM feedback WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 1 DAY)")->fetch_assoc()['daily'];
                                            $weekly_feedback = $conn->query("SELECT COUNT(*) as weekly FROM feedback WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 1 WEEK)")->fetch_assoc()['weekly'];
                                            
                                            echo "<tr><td>Total Feedback Entries</td><td>$total_feedback</td></tr>";
                                            echo "<tr><td>Daily Feedback</td><td>$daily_feedback</td></tr>";
                                            echo "<tr><td>Weekly Feedback</td><td>$weekly_feedback</td></tr>";
                                            
                                            while ($row = $feedback_types->fetch_assoc()) {
                                                echo "<tr><td>" . $row['feedback_type'] . " Feedback</td><td>" . $row['count'] . "</td></tr>";
                                            }
                                            ?>
                                        </table>
                                    </div>

                                    <!-- Detailed Feedback Table -->
                                    <div class="table-container">
                                        <h2>Feedback Details</h2>
<!-- Filter Form -->
<style>
input::placeholder,
textarea::placeholder {
    color: #555 !important;
    opacity: 1 !important;
}
</style>
<form method="get" style="margin-bottom: 15px;">
    <input type="text" name="id" placeholder="ID" value="<?php echo htmlspecialchars($_GET['id'] ?? ''); ?>"  style="margin-right:5px;" />
    <input type="text" name="timestamp" placeholder="Timestamp" value="<?php echo htmlspecialchars($_GET['timestamp'] ?? ''); ?>"  style="margin-right:5px;" />
    <input type="text" name="query" placeholder="Query" value="<?php echo htmlspecialchars($_GET['query'] ?? ''); ?>"  style="margin-right:5px;" />
    <select name="feedback_type" data-no-materialize style="margin-right:5px; padding:6px 12px; border-radius:4px; border:1px solid #ccc; background:#fff; width:100px !important; min-width:100px !important; max-width:100px !important; display:inline-block;">
        <option value="">All Types</option>
        <option value="like" <?php if(($_GET['feedback_type'] ?? '')==='like') echo 'selected'; ?>>Like</option>
        <option value="dislike" <?php if(($_GET['feedback_type'] ?? '')==='dislike') echo 'selected'; ?>>Dislike</option>
    </select>
    <input type="text" name="rasa_response" placeholder="Rasa Response" value="<?php echo htmlspecialchars($_GET['rasa_response'] ?? ''); ?>"  style="margin-right:5px;" />
    <button type="submit" class="btn btn-export">Filter</button>
    <a href="feedback.php" class="btn btn-empty">Reset</a>
</form>
                                        <div class="buttons">
                                            <button class="btn btn-export" onclick="exportTable('feedback')">Export</button>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="table_name" value="feedback">
                                                <button type="submit" name="empty_table" class="btn btn-empty">Empty Table</button>
                                            </form>
                                        </div>
                                        <table>
                                            <tr>
                                                <th>ID</th>
                                                <th>Timestamp</th>
                                                <th>Query</th>
                                                <th>Feedback Type</th>
                                                <th>Rasa Response</th>
                                                <th>Action</th>
                                            </tr>
                                            <?php
                                            // Build filter conditions
$conditions = [];
$params = [];
$types = '';

if (!empty($_GET['id'])) {
    $conditions[] = 'id = ?';
    $params[] = $_GET['id'];
    $types .= 'i';
}
if (!empty($_GET['timestamp'])) {
    $conditions[] = 'timestamp LIKE ?';
    $params[] = '%' . $_GET['timestamp'] . '%';
    $types .= 's';
}
if (!empty($_GET['query'])) {
    $conditions[] = 'query LIKE ?';
    $params[] = '%' . $_GET['query'] . '%';
    $types .= 's';
}
if (!empty($_GET['feedback_type'])) {
    $conditions[] = 'feedback_type = ?';
    $params[] = $_GET['feedback_type'];
    $types .= 's';
}
if (!empty($_GET['rasa_response'])) {
    $conditions[] = 'rasa_response LIKE ?';
    $params[] = '%' . $_GET['rasa_response'] . '%';
    $types .= 's';
}

$where = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';
$sql = "SELECT * FROM feedback $where ORDER BY timestamp DESC LIMIT 50";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
                                            while ($row = $result->fetch_assoc()) {
                                                echo "<tr>";
                                                echo "<td>" . $row['id'] . "</td>";
                                                echo "<td>" . $row['timestamp'] . "</td>";
                                                echo "<td>" . $row['query'] . "</td>";
                                                echo "<td>" . $row['feedback_type'] . "</td>";
                                                echo "<td>" . $row['rasa_response'] . "</td>";
                                                echo "<td>";
                                                echo "<form method='post' style='display: inline;'>";
                                                echo "<input type='hidden' name='table_name' value='feedback'>";
                                                echo "<input type='hidden' name='record_id' value='" . $row['id'] . "'>";
                                                echo "<button type='submit' name='delete_record' class='btn btn-delete'>Delete</button>";
                                                echo "</form>";
                                                echo "</td>";
                                                echo "</tr>";
                                            }
                                            ?>
                                        </table>
                                    </div>
                                                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
       function updateNotificationCount() {
    fetch('fetch_queries.php')
        .then(response => response.json())
        .then(data => {
            const notYetCount = document.getElementById('not-yet-count');
            if (notYetCount) { // Check if the element exists
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

updateNotificationCount(); // Call it on page load
setInterval(updateNotificationCount, 60000); // Update every minute
    </script>
    <!--Import jQuery before materialize.js-->
    <script src="js/main.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/materialize.min.js"></script>
    <script src="js/custom.js"></script>
    <script>
        function exportTable(tableName) {
            window.location.href = 'export.php?table=' + tableName;
        }
    </script>
<style>
form[method="get"] { display:block !important; visibility:visible !important; }
</style>
<script>
// Remove Materialize wrapper for feedback_type select if it exists
window.addEventListener('DOMContentLoaded', function() {
    try {
        var sel = document.querySelector('select[name="feedback_type"]');
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
</body>


</html>

<?php
$conn->close();
?>