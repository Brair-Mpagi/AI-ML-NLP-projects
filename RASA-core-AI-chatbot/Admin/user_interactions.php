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
require_once 'db.php';
if (!$conn || $conn->connect_error) {
    die("Connection failed: " . ($conn ? $conn->connect_error : 'No connection object.'));
}
if (!$conn->ping()) {
    die("Database connection is closed.");
}

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Fetch Admin Details
$admin_query = "SELECT admin_id, username, email, image FROM admins WHERE admin_id = ?";
$admin_stmt = $conn->prepare($admin_query);

if ($admin_stmt) {
    $admin_stmt->bind_param("i", $_SESSION['admin_id']);
    $admin_stmt->execute();
    $admin_result = $admin_stmt->get_result();
    $admin = $admin_result->fetch_assoc();
    $admin_stmt->close();

    // Now you can use the $admin array
    if ($admin) {
        // Access admin details like this:
        // echo "Welcome, " . $admin['username'];
    } else {
        // Handle the case where no admin is found with the given ID
        echo "Admin not found.";
    }
} else {
    // Handle the case where the prepare statement failed
    echo "Error preparing admin query: " . $conn->error;
}

// Handle table operations
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['empty_table'])) {
        $table = $_POST['table_name'];
        $conn->query("TRUNCATE TABLE $table");
        echo "<p>Table $table has been emptied.</p>";
    }
    if (isset($_POST['delete_record'])) {
        $table = $_POST['table_name'];
        $id = $_POST['record_id'];
        $id_field = ($table === 'chatlogs') ? 'chatlog_id' : 'id';
        $conn->query("DELETE FROM $table WHERE $id_field = $id");
        echo "<p>Record deleted from $table.</p>";
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
            margin: 40px 0;
            background-color: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        table {
            width: 80%;
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
                        <li><a href="admin.php"><i class="fa fa-bar-chart" aria-hidden="true"></i> Dashboard</a></li>
                        <li><a href="admin-setting.php"><i class="fa fa-cogs" aria-hidden="true"></i> Account Information</a></li>
                        <li><a href="javascript:void(0)" class="collapsible-header  menu-active"><i class="fas fa-chart-line" aria-hidden="true"></i> Chatbot Usage Analytics</a>
                            <div class="collapsible-body left-sub-menu">
                                <ul>
                                    <li><a href="chatlogs.php">Chatlogs</a></li>
                                    <li><a href="user_interactions.php" style=" background: #b4babd !important; color: #000 !important;">User Interaction Data</a></li>
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
                        <li><a href="http://127.0.0.1:5000" class="collapsible-header" target="_blank"><i class="fas fa-robot" aria-hidden="true"></i>Chatbot</a></li>
                        <li><a href="./admin-logout.php" class="collapsible-header"><i class="fas fa-sign-out-alt" aria-hidden="true"></i>Logout</a></li>
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
                        <li class="active-bre"><a href="#"> Dashboard</a>
                        </li>
                        <li class="page-back"><a href="admin.php"><i class="fa fa-backward" aria-hidden="true"></i> Back</a>
                        </li>
                    </ul>
                </div>
                <!--== DASHBOARD INFO ==-->

                <div class="sb2-2-1">
                    <div class="db-2">

                <!-- ========================================= -->
                    <!-- User Interaction Statistics -->
                    <div class="table-container">
                            <h2>User Interactions</h2>
                            
                            <table>
                                <tr>
                                    <th>Period</th>
                                    <th>Unique Users</th>
                                    <th>Total Interactions</th>
                                </tr>
                                <?php
                                $periods = [
                                    'Daily' => "WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 1 DAY)",
                                    'Weekly' => "WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 1 WEEK)",
                                    'Monthly' => "WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 1 MONTH)",
                                    'Yearly' => "WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 1 YEAR)"
                                ];

                                foreach ($periods as $period => $condition) {
                                    $unique_users = $conn->query("SELECT COUNT(DISTINCT question) as users FROM chatlogs $condition")->fetch_assoc()['users'];
                                    $total_interactions = $conn->query("SELECT COUNT(*) as total FROM chatlogs $condition")->fetch_assoc()['total'];
                                    echo "<tr>";
                                    echo "<td>$period</td>";
                                    echo "<td>$unique_users</td>";
                                    echo "<td>$total_interactions</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </table>
                        </div>

                        <!-- Unique Interaction Data Table -->
                        <div class="table-container">
                            <h2>Unique Interaction Data</h2>
                            <div class="buttons">
                                <button class="btn btn-export" onclick="exportTable('faq_cache')">Export</button>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="table_name" value="faq_cache">
                                    <button type="submit" name="empty_table" class="btn btn-empty">Empty Table</button>
                                </form>
                            </div>
                            <table>
                                <tr>
                                    <th>ID</th>
                                    <th>Query</th>
                                    <th>Answer</th>
                                    <th>Timestamp</th>
                                    <th>Action</th>
                                </tr>
                                <?php
                                $result = $conn->query("SELECT * FROM faq_cache LIMIT 50");
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $row['id'] . "</td>";
                                    echo "<td>" . $row['query'] . "</td>";
                                    echo "<td>" . $row['answer'] . "</td>";
                                    echo "<td>" . $row['timestamp'] . "</td>";
                                    echo "<td>";
                                    echo "<form method='post' style='display: inline;'>";
                                    echo "<input type='hidden' name='table_name' value='faq_cache'>";
                                    echo "<input type='hidden' name='record_id' value='" . $row['id'] . "'>";
                                    echo "<button type='submit' name='delete_record' class='btn btn-delete'>Delete</button>";
                                    echo "</form>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </table>
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
                        
                 <!-- ================================================= -->
            </div>

        </div>
    </div>

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
</body>


</html>

<?php
$conn->close();
?>