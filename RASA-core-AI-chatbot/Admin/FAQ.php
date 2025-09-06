<?php
// Start session to check authentication
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ./admin-login.php");
    exit();
}

require_once 'db.php';
if (!$conn || $conn->connect_error) {
    die("Connection failed: " . ($conn ? $conn->connect_error : 'No connection object.'));
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
} else {
    echo "Error preparing admin query: " . $conn->error;
}

// Handle POST requests (empty table)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['empty_table'])) {
    $conn->query("TRUNCATE TABLE faq_frequency");
    echo json_encode(["status" => "success", "message" => "Table emptied"]);
    exit;
}

// Handle filtering and row limit
$where = "";
$limit = isset($_GET['limit']) && $_GET['limit'] !== 'all' ? (int)$_GET['limit'] : 20;
$params = [];
$types = "";

if (isset($_GET['filter']) && !empty($_GET['filter'])) {
    $filter = json_decode($_GET['filter'], true);
    $conditions = [];
    
    if (!empty($filter['query'])) {
        $conditions[] = "query LIKE ?";
        $params[] = "%" . $conn->real_escape_string($filter['query']) . "%";
        $types .= "s";
    }
    
    if (!empty($filter['frequency']) && is_numeric($filter['frequency'])) {
        $conditions[] = "frequency >= ?";
        $params[] = (int)$filter['frequency'];
        $types .= "i";
    }
    
    if (!empty($conditions)) {
        $where = "WHERE " . implode(" AND ", $conditions);
    }
}

$sql = "SELECT rank, query, frequency FROM faq_frequency $where ORDER BY frequency DESC";
if ($limit !== 'all') {
    $sql .= " LIMIT ?";
    $params[] = $limit;
    $types .= "i";
}

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $faqs = [];
    while ($row = $result->fetch_assoc()) {
        $faqs[] = $row;
    }
    $stmt->close();
} else {
    die("Error preparing query: " . $conn->error);
}

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode($faqs);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Chatbot Panel</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="images/mmu_logo_-no-bg.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700%7CJosefin+Sans:600,700" rel="stylesheet">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="css/materialize.css" rel="stylesheet">
    <link href="css/bootstrap.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-mob.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f5f5f5; }
        .table-container { margin: 40px 0; background-color: white; padding: 15px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #002147; color: white; }
        tr:hover { background-color: #f5f5f5; }
        .buttons { margin: 10px 0; }
        .btn { padding: 8px 15px; margin-right: 5px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-export { background-color: #002147; color: white; }
        .btn-empty { background-color: #002147; color: white; }
        .btn-search { background-color: #4CAF50; color: white; }
        .filter-container { margin-bottom: 10px; display: flex; gap: 10px; }
        .filter-container input { padding: 8px; margin-right: 10px; width: 200px; border: 1px solid #ddd; border-radius: 4px; }
        .limit-container { margin-bottom: 10px; display: flex; gap: 10px; align-items: center; }
        .limit-container input { padding: 8px; width: 80px; border: 1px solid #ddd; border-radius: 4px; }
        input::placeholder, textarea::placeholder { color: #555 !important; opacity: 1 !important; }
    </style>
</head>

<body>
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

    <div class="container-fluid sb2">
        <div class="row">
            <div class="sb2-1" style="position: fixed;">
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
                <div class="sb2-13">
                    <ul class="collapsible" data-collapsible="accordion">
                        <li><a href="admin.php"><i class="fa fa-bar-chart" aria-hidden="true"></i> Dashboard</a></li>
                        <li><a href="admin-setting.php"><i class="fa fa-cogs" aria-hidden="true"></i> Account Information</a></li>
                        <li><a href="javascript:void(0)" class="collapsible-header menu-active"><i class="fas fa-chart-line" aria-hidden="true"></i> Chatbot Usage Analytics</a>
                            <div class="collapsible-body left-sub-menu">
                                <ul>
                                    <li><a href="chatlogs.php">Chatlogs</a></li>
                                    <li><a href="user_interactions.php">User Interaction Data</a></li>
                                    <li><a href="FAQ.php" style="background: #b4babd !important; color: #000 !important;">Frequently Asked Questions</a></li>
                                </ul>
                            </div>
                        </li>
                        <li><a href="javascript:void(0)" class="collapsible-header"><i class="fas fa-database" aria-hidden="true"></i> AI Chatbot Model</a>
                            <div class="collapsible-body left-sub-menu">
                                <ul>
                                    <li><a href="chatbot-data.php" target="_blank">AI Chatbot Model</a></li>
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

            <div class="sb2-2">
                <div class="sb2-2-2">
                    <ul>
                        <li><a href="#"><i class="fa fa-home" aria-hidden="true"></i> Home</a></li>
                        <li class="active-bre"><a href="#"> Dashboard</a></li>
                        <li class="page-back"><a href="admin.php"><i class="fa fa-backward" aria-hidden="true"></i> Back</a></li>
                    </ul>
                </div>

                <div class="sb2-2-1">
                    <div class="db-2">
                        <div class="table-container">
                            <h2>Top Frequently Asked Questions</h2>
                            <div class="filter-container">
                                <input type="text" id="filter-query" placeholder="Filter by Query">
                                <input type="number" id="filter-frequency" placeholder="Min Frequency" min="0">
                                <button class="btn btn-search" onclick="applyFilters()">Search</button>
                            </div>
                            <div class="limit-container">
                                <label for="row-limit">Rows to display: </label>
                                <input type="number" id="row-limit" min="1" value="20">
                                <button class="btn btn-search" onclick="applyFilters('all')">View All</button>
                                <button class="btn btn-search" onclick="applyFilters()">Apply Limit</button>
                            </div>
                            <div class="buttons">
                                <button class="btn btn-export" onclick="exportTable('faq_frequency')">Export</button>
                                <button class="btn btn-empty" onclick="emptyTable()">Empty Table</button>
                            </div>
                            <table id="faq-table">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Query</th>
                                        <th>Frequency</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($faqs as $row): ?>
                                        <tr data-id="<?php echo htmlspecialchars($row['rank']); ?>">
                                            <td><?php echo htmlspecialchars($row['rank']); ?></td>
                                            <td><?php echo htmlspecialchars($row['query']); ?></td>
                                            <td><?php echo htmlspecialchars($row['frequency']); ?></td>
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
            if (notYetCount) {
                notYetCount.textContent = data.not_yet_count;
                notYetCount.style.display = data.not_yet_count > 0 ? 'inline' : 'none';
            }
        })
        .catch(error => console.error('Error fetching notification count:', error));
}

updateNotificationCount();
setInterval(updateNotificationCount, 60000);

function applyFilters(limitOverride = null) {
    const filter = {
        query: document.getElementById('filter-query').value.trim(),
        frequency: document.getElementById('filter-frequency').value.trim()
    };
    const limit = limitOverride === 'all' ? 'all' : (parseInt(document.getElementById('row-limit').value) || 20);

    if (filter.frequency && isNaN(filter.frequency)) {
        alert('Please enter a valid number for frequency.');
        return;
    }
    if (limit !== 'all' && (isNaN(limit) || limit < 1)) {
        alert('Please enter a valid number for row limit.');
        return;
    }

    fetchFaqs(filter, limit);
}

function fetchFaqs(filter = {}, limit = 20) {
    const url = `FAQ.php?ajax=1&filter=${encodeURIComponent(JSON.stringify(filter))}&limit=${encodeURIComponent(limit)}`;
    fetch(url)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => updateTable(data))
        .catch(error => {
            console.error('Error fetching FAQs:', error);
            alert('Failed to fetch FAQs. Please try again.');
        });
}

function updateTable(faqs) {
    const tbody = document.querySelector('#faq-table tbody');
    tbody.innerHTML = '';
    if (faqs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3">No results found.</td></tr>';
        return;
    }
    faqs.forEach(row => {
        const tr = document.createElement('tr');
        tr.dataset.id = row.rank;
        tr.innerHTML = `
            <td>${row.rank}</td>
            <td>${row.query}</td>
            <td>${row.frequency}</td>
        `;
        tbody.appendChild(tr);
    });
}

function emptyTable() {
    if (confirm('Are you sure you want to empty the table?')) {
        fetch('FAQ.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'empty_table=1'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                applyFilters();
                alert('Table emptied successfully.');
            } else {
                alert('Failed to empty table.');
            }
        })
        .catch(error => {
            console.error('Error emptying table:', error);
            alert('Failed to empty table.');
        });
    }
}

function exportTable(tableName) {
    window.location.href = `export.php?table=${encodeURIComponent(tableName)}`;
}

const source = new EventSource('faq_sse.php');
source.onmessage = function(event) {
    const newFaq = JSON.parse(event.data);
    applyFilters(); // Refresh table to maintain sorting
};
source.onerror = function() {
    console.error('SSE connection error');
    source.close();
};
                        </script>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/main.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/materialize.min.js"></script>
    <script src="js/custom.js"></script>
</body>
</html>

<?php
$conn->close();
?>