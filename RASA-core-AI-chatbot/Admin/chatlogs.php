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


// Handle POST requests (empty table, delete record)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['empty_table'])) {
        $conn->query("TRUNCATE TABLE chatlogs");
        echo json_encode(["status" => "success", "message" => "Table emptied"]);
        exit;
    }
    if (isset($_POST['delete_record'])) {
        $id = $conn->real_escape_string($_POST['record_id']);
        $conn->query("DELETE FROM chatlogs WHERE chatlog_id = '$id'");
        echo json_encode(["status" => "success", "message" => "Record deleted"]);
        exit;
    }
}

// Handle filtering via GET (for AJAX)
$where = "";
if (isset($_GET['filter'])) {
    $filter = json_decode($_GET['filter'], true);
    $question = isset($filter['question']) ? $conn->real_escape_string($filter['question']) : '';
    $response = isset($filter['response']) ? $conn->real_escape_string($filter['response']) : '';
    $timestamp = isset($filter['timestamp']) ? $conn->real_escape_string($filter['timestamp']) : '';
    $session_id = isset($filter['session_id']) ? $conn->real_escape_string($filter['session_id']) : '';

    $conditions = [];
    if ($question) $conditions[] = "question LIKE '%$question%'";
    if ($response) $conditions[] = "response_text LIKE '%$response%'";
    if ($timestamp) $conditions[] = "timestamp LIKE '%$timestamp%'";
    if ($session_id) $conditions[] = "session_id LIKE '%$session_id%'";
    if (!empty($conditions)) $where = "WHERE " . implode(" AND ", $conditions);
}

$result = $conn->query("SELECT chatlog_id, session_id, question, response_text, timestamp FROM chatlogs $where LIMIT 50");
$chatlogs = [];
while ($row = $result->fetch_assoc()) {
    $chatlogs[] = $row;
}

if (isset($_GET['ajax'])) {
    echo json_encode($chatlogs);
    exit;
}

// SSE endpoint has been moved to chatlogs_sse.php. Use that endpoint for real-time updates via EventSource or AJAX.

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
    /* Improve search form placeholder visibility */
    input::placeholder,
    textarea::placeholder {
        color: #555 !important;
        opacity: 1 !important;
    }

        body { font-family: Arial, sans-serif; background-color: #f5f5f5; }
        .table-container { margin: 40px 0; background-color: white; padding: 15px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        table { width: 80%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #002147; color: white; }
        tr:hover { background-color: #f5f5f5; }
        .buttons { margin: 10px 0; }
        .btn { padding: 8px 15px; margin-right: 5px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-export { background-color: #002147; color: white; }
        .btn-delete { background-color: #f44336; color: white; }
        .btn-empty { background-color: #002147; color: white; }
        .btn-search { background-color: #4CAF50; color: white; }
        .filter-container { margin-bottom: 10px; }
        .filter-container input { padding: 5px; margin-right: 10px; width: 200px; }
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
                        <li><a href="admin.php"><i class="fa fa-bar-chart" aria-hidden="true"></i> Dashboard</a>
                        </li>
						<li><a href="admin-setting.php"><i class="fa fa-cogs" aria-hidden="true"></i> Account Information</a>
                        </li>

                        <li><a href="javascript:void(0)" class="collapsible-header menu-active"><i class="fas fa-chart-line" aria-hidden="true"></i> Chatbot Usage Analytics</a>
                            <div class="collapsible-body left-sub-menu">
                                <ul>
                                    <li><a href="chatlogs.php" style=" background: #b4babd !important; color: #000 !important;">Chatlogs</a>
                                    </li>
                                    <li><a href="user_interactions.php">User Interaction Data</a>
                                    </li>
                                    <li><a href="FAQ.php">Frequently Asked Questions</a>
                                    </li>
                                    
                                    
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
                  <!-- Chatlogs Table -->
    <div class="table-container">
        <h2>Chatlogs</h2>
        <div class="filter-container">
            <input type="text" id="filter-session-id" placeholder="Filter by Session ID">
            <input type="text" id="filter-question" placeholder="Filter by Question">
            <input type="text" id="filter-response" placeholder="Filter by Response">
            <input type="text" id="filter-timestamp" placeholder="Filter by Timestamp">
            <button class="btn btn-search" onclick="applyFilters()">Search</button>
        </div>
        <div class="buttons">
            <button class="btn btn-export" onclick="exportTable('chatlogs')">Export</button>
            <button class="btn btn-empty" onclick="emptyTable()">Empty Table</button>
        </div>
        <table id="chatlogs-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Session ID</th>
                    <th>Question</th>
                    <th>Response</th>
                    <th>Timestamp</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($chatlogs as $row): ?>
                    <tr data-id="<?php echo $row['chatlog_id']; ?>">
                        <td><?php echo $row['chatlog_id']; ?></td>
                        <td><?php echo $row['session_id']; ?></td>
                        <td><?php echo $row['question']; ?></td>
                        <td><?php echo $row['response_text']; ?></td>
                        <td><?php echo $row['timestamp']; ?></td>
                        <td>
                            <button class="btn btn-delete" onclick="deleteRecord(<?php echo $row['chatlog_id']; ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
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


        // Filtering with Search Button
        function applyFilters() {
            const filter = {
                session_id: document.getElementById('filter-session-id').value,
                question: document.getElementById('filter-question').value,
                response: document.getElementById('filter-response').value,
                timestamp: document.getElementById('filter-timestamp').value
            };
            fetchChatlogs(filter);
        }

        function fetchChatlogs(filter = {}) {
            fetch(`chatlogs.php?ajax=1&filter=${encodeURIComponent(JSON.stringify(filter))}`)
                .then(response => response.json())
                .then(data => updateTable(data));
        }

        function updateTable(chatlogs) {
            const tbody = document.querySelector('#chatlogs-table tbody');
            tbody.innerHTML = '';
            chatlogs.forEach(row => {
                const tr = document.createElement('tr');
                tr.dataset.id = row.chatlog_id;
                tr.innerHTML = `
                    <td>${row.chatlog_id}</td>
                    <td>${row.session_id}</td>
                    <td>${row.question}</td>
                    <td>${row.response_text}</td>
                    <td>${row.timestamp}</td>
                    <td><button class="btn btn-delete" onclick="deleteRecord(${row.chatlog_id})">Delete</button></td>
                `;
                tbody.appendChild(tr);
            });
        }

        // Actions
        function deleteRecord(id) {
            fetch('chatlogs.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `delete_record=1&record_id=${id}&table_name=chatlogs`
            }).then(() => applyFilters()); // Re-apply filters after deletion
        }

        function emptyTable() {
            fetch('chatlogs.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'empty_table=1&table_name=chatlogs'
            }).then(() => fetchChatlogs());
        }

        function exportTable() {
            window.location.href = 'chatlogs.php?export=1'; // Add export logic if needed
        }

        // Real-time updates with Server-Sent Events
        const source = new EventSource('chatlogs.php?sse=1');
        source.onmessage = function(event) {
            const newLog = JSON.parse(event.data);
            const tbody = document.querySelector('#chatlogs-table tbody');
            const tr = document.createElement('tr');
            tr.dataset.id = newLog.chatlog_id;
            tr.innerHTML = `
                <td>${newLog.chatlog_id}</td>
                <td>${newLog.session_id}</td>
                <td>${newLog.question}</td>
                <td>${newLog.response_text}</td>
                <td>${newLog.timestamp}</td>
                <td><button class="btn btn-delete" onclick="deleteRecord(${newLog.chatlog_id})">Delete</button></td>
            `;
            tbody.insertBefore(tr, tbody.firstChild); // Add new log at the top
        };
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