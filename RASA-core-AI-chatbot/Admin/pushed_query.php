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

// Database Configuration
const DB_HOST = "localhost";
const DB_USER = "root";
const DB_PASSWORD = ""; // Update with your MySQL password
const DB_NAME = "chatbot_db";

// Database Connection
require_once 'db.php';
if (!$conn || $conn->connect_error) {
    die("Connection failed: " . ($conn ? $conn->connect_error : 'No connection object.'));
}
if (!$conn->ping()) {
    die("Database connection is closed.");
}
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all admins up front
$admins = [];
$admin_result = $conn->query("SELECT admin_id, image FROM admins");
while ($a = $admin_result->fetch_assoc()) {
    $admins[$a['admin_id']] = $a;
}

// Fetch Admin Details
$admin_query = "SELECT admin_id, username, email FROM admins WHERE admin_id = ?";
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

// Handle Status Update
function handleStatusUpdate($conn) {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
        $id = $_POST['id'];
        $new_status = $_POST['status'];

        if ($new_status == "Responded") {
            $sql_select = "SELECT * FROM pushed_query WHERE id = ?";
            $stmt = $conn->prepare($sql_select);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if ($row) {
                $responded_timestamp = date("Y-m-d H:i:s");
                $sql_insert = "INSERT INTO responded_query (username, email, query, timestamp, responded_timestamp) 
                               VALUES (?, ?, ?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->bind_param("sssss", $row['username'], $row['email'], $row['query'], $row['timestamp'], $responded_timestamp);
                $stmt_insert->execute();

                $sql_delete = "DELETE FROM pushed_query WHERE id = ?";
                $stmt_delete = $conn->prepare($sql_delete);
                $stmt_delete->bind_param("i", $id);
                $stmt_delete->execute();
            }
        } elseif ($new_status == "Not Yet" && isset($_POST['from_responded'])) {
            $sql_select = "SELECT * FROM responded_query WHERE id = ?";
            $stmt = $conn->prepare($sql_select);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if ($row) {
                $sql_insert = "INSERT INTO pushed_query (username, email, query, timestamp, status) 
                               VALUES (?, ?, ?, ?, 'Not Yet')";
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->bind_param("ssss", $row['username'], $row['email'], $row['query'], $row['timestamp']);
                $stmt_insert->execute();

                $sql_delete = "DELETE FROM responded_query WHERE id = ?";
                $stmt_delete = $conn->prepare($sql_delete);
                $stmt_delete->bind_param("i", $id);
                $stmt_delete->execute();
            }
        } else {
            $sql_update = "UPDATE pushed_query SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql_update);
            $stmt->bind_param("si", $new_status, $id);
            $stmt->execute();
        }
    }
}

// Handle Delete Actions
function handleDeleteActions($conn) {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_pushed'])) {
        $id = $_POST['id'];
        $sql_delete = "DELETE FROM pushed_query WHERE id = ?";
        $stmt = $conn->prepare($sql_delete);
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_responded'])) {
        $id = $_POST['id'];
        $sql_delete = "DELETE FROM responded_query WHERE id = ?";
        $stmt = $conn->prepare($sql_delete);
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
}

// Fetch Queries
function fetchQueries($conn) {
    $pushed_queries = $conn->query("SELECT * FROM pushed_query ORDER BY timestamp DESC");
    $responded_queries = $conn->query("SELECT * FROM responded_query ORDER BY responded_timestamp DESC");
    return [$pushed_queries, $responded_queries];
}

// Process Requests
handleStatusUpdate($conn);
handleDeleteActions($conn);
list($pushed_queries, $responded_queries) = fetchQueries($conn);

// Close Database Connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Chatbot Panel</title>
    <!-- Meta Tags -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="images/mmu_logo_- no bg.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700|Josefin+Sans:600,700" rel="stylesheet">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link href="css/bootstrap.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet" />
    <link href="css/style-mob.css" rel="stylesheet" />

    <!-- Inline Styles -->
    <style>
        .admin-h2 {
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }
        .admin-table-container {
            max-width: 1500px;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .admin-th, .admin-td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            vertical-align: middle;
        }
        .admin-th {
            background-color: #002147;
            color: white;
        }
        .admin-tr:hover {
            background-color: #f9f9f9;
        }
        .admin-select {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
            cursor: pointer;
            width: 120px;
            font-size: 14px;
            background-color: #fff;
            appearance: none; /* Remove default dropdown arrow */
            -webkit-appearance: none;
            -moz-appearance: none;
            position: relative;
        }
        .admin-select-wrapper {
            position: relative;
            display: inline-block;
            width: 120px;
        }
        .admin-select-wrapper::after {
            content: '▼';
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: #333;
        }
        .admin-select option[value="Not Yet"] {
            color: #dc3545;
            font-weight: bold;
        }
        .admin-select option[value="Responded"] {
            color: #28a745;
            font-weight: bold;
        }
        .admin-delete-btn {
            padding: 8px 12px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            z-index: 9000;
        }
        .admin-delete-btn:hover {
            background-color: #c82333;
        }
        .admin-select:hover, .admin-select:focus {
            border-color: #007BFF;
            outline: none;
        }
        .admin-header-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        .email-btn {
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .email-btn:hover {
            background-color: #2980b9;
        }
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 60%;
            max-width: 600px;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .submit-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .submit-btn:hover {
            background-color: #45a049;
        }
    </style>
</head>

<body>
    <!-- Main Container -->
    <div class="container-fluid sb1">
        <div class="row" style="padding: 1rem;">
            <h3 style="color: #fff; justify-content: center; align-items: center; display: flex; margin-top: 1rem;">
                Campus Query Chatbot Admin Panel
                <span class="admin-notification" id="notification">
                    <i class="fa fa-commenting-o" aria-hidden="true" style="color: #fff; margin-left:10rem;"></i>
                    <span class="admin-badge" id="not-yet-count" style="background-color: #c82333; padding: 5px; border-radius: 40%; color:#f9f9f9;"></span>
                </span>
            </h3>
        </div>
    </div>

    <!-- Body Container -->
    <div class="container-fluid sb2">
        <div class="row">
            <!-- Sidebar -->
            <div class="sb2-1" style="position: fixed;">
                <div class="sb2-12">
                    <ul>
                        <li>
                            <?php
$img_path = isset($admins[$admin['admin_id']]['image']) ? $admins[$admin['admin_id']]['image'] : '';
if (!empty($img_path) && file_exists($img_path)) {
    echo '<img src="' . htmlspecialchars($img_path) . '" alt="Admin Image" style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%;">';
} else {
    echo '<img src="images/default_admin_icon.png" alt="Default Icon" style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%;">';
}
?>
                        </li> 
                        <h6 style="margin-left: 8rem;">Admin ID: <?php echo htmlspecialchars($admin['admin_id']); ?></h6>
                        <h6 style="margin-left: 8rem;">Name: <?php echo htmlspecialchars($admin['username']); ?></h6>                   
                    </ul>
                </div>
                <div class="sb2-13">
                    <ul class="collapsible" data-collapsible="accordion">
                        <li><a href="admin.php"><i class="fa fa-bar-chart" aria-hidden="true"></i> Dashboard</a></li>
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
                                    <li><a href="feedback.php">Feedback</a></li>
                                </ul>
                            </div>
                        </li>
                        <li><a href="javascript:void(0)" class="collapsible-header menu-active"><i class="fa fa-commenting-o" aria-hidden="true"></i> Pushed Queries</a>
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

            <!-- Main Content -->
            <div class="sb2-2">
                <!-- Breadcrumbs -->
                <div class="sb2-2-2">
                    <ul>
                        <li><a href="#"><i class="fa fa-home" aria-hidden="true"></i> Home</a></li>
                        <li class="active-bre"><a href="#"> All Queries</a></li>
                        <li class="page-back"><a href="admin.php"><i class="fa fa-backward" aria-hidden="true"></i> Back</a></li>
                    </ul>
                </div>

                <h4>Enquiry</h4>

                <!-- Tables Container -->
                <div class="admin-table-container">
                    <!-- Pushed Queries -->
                    <div class="admin-header-container">
                        <h2 class="admin-h2">Pushed Queries</h2>
                    </div>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th class="admin-th">ID</th>
                                <th class="admin-th">Username</th>
                                <th class="admin-th">Email</th>
                                <th class="admin-th">Query</th>
                                <th class="admin-th">Timestamp</th>
                                <th class="admin-th">Status</th>
                                <th class="admin-th">Action</th>
                                <th class="admin-th">Contact</th>
                            </tr>
                        </thead>
                        <tbody id="pushed-queries-body">
                            <?php while ($row = $pushed_queries->fetch_assoc()): ?>
                                <tr class="admin-tr">
                                    <td class="admin-td"><?php echo $row['id']; ?></td>
                                    <td class="admin-td"><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td class="admin-td"><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td class="admin-td"><?php echo htmlspecialchars($row['query']); ?></td>
                                    <td class="admin-td"><?php echo $row['timestamp']; ?></td>
                                    <td class="admin-td">
                                        <div class="admin-select-wrapper">
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                <select name="status" class="admin-select" onchange="this.form.submit()">
                                                    <option value="Not Yet" <?php echo $row['status'] == 'Not Yet' ? 'selected' : ''; ?>>Not Yet</option>
                                                    <option value="Responded" <?php echo $row['status'] == 'Responded' ? 'selected' : ''; ?>>Responded</option>
                                                </select>
                                                <input type="hidden" name="update_status" value="1">
                                            </form>
                                        </div>
                                    </td>
                                    <td class="admin-td">
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this query?');">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="delete_pushed" value="1">
                                            <button type="submit" class="admin-delete-btn">Delete</button>
                                        </form>
                                    </td>
                                    <td class="admin-td">
                                        <button class="email-btn" onclick="openEmailModal('<?php echo htmlspecialchars($row['username']); ?>', '<?php echo htmlspecialchars($row['email']); ?>')">
                                            <i class="fa fa-envelope"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <!-- Responded Queries -->
                    <h2 class="admin-h2">Responded Queries</h2>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th class="admin-th">ID</th>
                                <th class="admin-th">Username</th>
                                <th class="admin-th">Email</th>
                                <th class="admin-th">Query</th>
                                <th class="admin-th">Timestamp</th>
                                <th class="admin-th">Responded Timestamp</th>
                                <th class="admin-th">Action</th>
                               
                            </tr>
                        </thead>
                        <tbody id="responded-queries-body">
                            <?php while ($row = $responded_queries->fetch_assoc()): ?>
                                <tr class="admin-tr">
                                    <td class="admin-td"><?php echo $row['id']; ?></td>
                                    <td class="admin-td"><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td class="admin-td"><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td class="admin-td"><?php echo htmlspecialchars($row['query']); ?></td>
                                    <td class="admin-td"><?php echo $row['timestamp']; ?></td>
                                    <td class="admin-td"><?php echo $row['responded_timestamp']; ?></td>
                                    <td class="admin-td">
                                        <div class="admin-select-wrapper">
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                <select name="status" class="admin-select" onchange="this.form.submit()">
                                                    <option value="Responded" <?php echo $row['status'] == 'Responded' ? 'selected' : ''; ?>>Responded</option>
                                                    <option value="Not Yet" <?php echo $row['status'] == 'Not Yet' ? 'selected' : ''; ?>>Not Yet</option>
                                                </select>
                                                <input type="hidden" name="from_responded" value="1">
                                                <input type="hidden" name="update_status" value="1">
                                            </form>
                                        </div>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this query?');">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="delete_responded" value="1">
                                            <button type="submit" class="admin-delete-btn">Delete</button>
                                        </form>
                                    </td>
                                    
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Email Modal -->
                <div id="emailModal" class="modal">
                    <div class="modal-content">
                        <span class="close">×</span>
                        <h2>Send Email</h2>
                        <form id="emailForm" method="POST" action="send_email.php">
                            <div class="form-group">
                                <label for="recipient_name">Recipient Name:</label>
                                <input type="text" id="recipient_name" name="recipient_name" readonly>
                            </div>
                            <div class="form-group">
                                <label for="recipient_email">Recipient Email:</label>
                                <input type="email" id="recipient_email" name="recipient_email" readonly>
                            </div>
                            <div class="form-group">
                                <label for="subject">Subject:</label>
                                <input type="text" id="subject" name="subject" required>
                            </div>
                            <div class="form-group">
                                <label for="message">Message:</label>
                                <textarea id="message" name="message" rows="6" required></textarea>
                            </div>
                            <button type="submit" class="submit-btn">Send Email</button>
                        </form>
                    </div>
    </div>

            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="js/main.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/materialize.min.js"></script>
    <script src="js/custom.js"></script>

    <!-- Table Update Script -->
    <script>
        function escapeHtml(text) {
            const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        function updateTables() {
            fetch('fetch_queries.php')
                .then(response => response.json())
                .then(data => {
                    // Update Pushed Queries
                    const pushedBody = document.getElementById('pushed-queries-body');
                    pushedBody.innerHTML = '';
                    if (data.pushed_queries.length > 0) {
                        data.pushed_queries.forEach(row => {
                            const tr = document.createElement('tr');
                            tr.className = 'admin-tr';
                            tr.innerHTML = `
                                <td class="admin-td">${row.id}</td>
                                <td class="admin-td">${escapeHtml(row.username)}</td>
                                <td class="admin-td">${escapeHtml(row.email)}</td>
                                <td class="admin-td">${escapeHtml(row.query)}</td>
                                <td class="admin-td">${row.timestamp}</td>
                                <td class="admin-td">
                                    <div class="admin-select-wrapper">
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="id" value="${row.id}">
                                            <select name="status" onchange="this.form.submit()" class="admin-select">
                                                <option value="Not Yet" ${row.status === 'Not Yet' ? 'selected' : ''}>Not Yet</option>
                                                <option value="Responded" ${row.status === 'Responded' ? 'selected' : ''}>Responded</option>
                                            </select>
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                    </div>
                                </td>
                                <td class="admin-td">
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this query?');">
                                        <input type="hidden" name="id" value="${row.id}">
                                        <input type="hidden" name="delete_pushed" value="1">
                                        <button type="submit" class="admin-delete-btn">Delete</button>
                                    </form>
                                </td>
                                <td class="admin-td">
                                    <button class="email-btn" onclick="openEmailModal('${escapeHtml(row.username)}', '${escapeHtml(row.email)}')">
                                        <i class="fa fa-envelope"></i>
                                    </button>
                                </td>
                            `;
                            pushedBody.appendChild(tr);
                        });
                    } else {
                        pushedBody.innerHTML = '<tr class="admin-tr"><td class="admin-td" colspan="8">No pushed queries found.</td></tr>';
                    }

                    // Update Responded Queries
                    const respondedBody = document.getElementById('responded-queries-body');
                    respondedBody.innerHTML = '';
                    if (data.responded_queries.length > 0) {
                        data.responded_queries.forEach(row => {
                            const tr = document.createElement('tr');
                            tr.className = 'admin-tr';
                            tr.innerHTML = `
                                <td class="admin-td">${row.id}</td>
                                <td class="admin-td">${escapeHtml(row.username)}</td>
                                <td class="admin-td">${escapeHtml(row.email)}</td>
                                <td class="admin-td">${escapeHtml(row.query)}</td>
                                <td class="admin-td">${row.timestamp}</td>
                                <td class="admin-td">${row.responded_timestamp}</td>
                                <td class="admin-td">
                                    <div class="admin-select-wrapper">
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="id" value="${row.id}">
                                            <select name="status" onchange="this.form.submit()" class="admin-select">
                                                <option value="Responded" ${row.status === 'Responded' ? 'selected' : ''}>Responded</option>
                                                <option value="Not Yet" ${row.status === 'Not Yet' ? 'selected' : ''}>Not Yet</option>
                                            </select>
                                            <input type="hidden" name="from_responded" value="1">
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                    </div>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this query?');">
                                        <input type="hidden" name="id" value="${row.id}">
                                        <input type="hidden" name="delete_responded" value="1">
                                        <button type="submit" class="admin-delete-btn">Delete</button>
                                    </form>
                                </td>
                               
                            `;
                            respondedBody.appendChild(tr);
                        });
                    } else {
                        respondedBody.innerHTML = '<tr class="admin-tr"><td class="admin-td" colspan="8">No responded queries found.</td></tr>';
                    }

                    // Update Notification Count
                    const notYetCount = document.getElementById('not-yet-count');
                    if (data.not_yet_count > 0) {
                        notYetCount.textContent = data.not_yet_count;
                        notYetCount.style.display = 'inline';
                    } else {
                        notYetCount.style.display = 'none';
                    }
                })
                .catch(error => console.error('Error fetching queries:', error));
        }

        // Initial load and polling
        updateTables();
        setInterval(updateTables, 5000);
    </script>

    <!-- Email Modal Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('emailModal');
            const span = document.getElementsByClassName('close')[0];

            span.onclick = () => modal.style.display = 'none';
            window.onclick = event => { if (event.target == modal) modal.style.display = 'none'; };

            document.getElementById('emailForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                fetch('send_email.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Email sent successfully!');
                        modal.style.display = 'none';
                        updateTables();
                    } else {
                        alert('Failed to send email: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while sending the email.');
                });
            });
        });

        function openEmailModal(username, email) {
            const modal = document.getElementById('emailModal');
            document.getElementById('recipient_name').value = username;
            document.getElementById('recipient_email').value = email;
            document.getElementById('subject').value = '';
            document.getElementById('message').value = '';
            modal.style.display = 'block';
        }
    </script>
</body>
</html>