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

// Database connection
require_once 'db.php';
if (!$conn || $conn->connect_error) {
    die("Connection failed: " . ($conn ? $conn->connect_error : 'No connection object.'));
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
$admin_stmt->close();

// Fetch logged-in admin's role
$stmt = $conn->prepare("SELECT admin_id, role FROM admins WHERE admin_id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param('i', $_SESSION['admin_id']);
$stmt->execute();
$result = $stmt->get_result();
$current_admin = $result->fetch_assoc();
$current_admin_role = $current_admin['role'];
$current_admin_id = $current_admin['admin_id'];
$stmt->close();

// Handle form submissions for CRUD operations
$error = $success = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Create Admin
    if (isset($_POST['create']) && $current_admin_role === 'Senior_Admin') {
        error_log("Create form submitted. POST data: " . print_r($_POST, true));

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role = $_POST['role'] ?? 'Junior_Admin';

        error_log("Parsed inputs: username=$username, email=$email, role=$role, password=" . (empty($password) ? 'empty' : 'set'));

        // Validate inputs
        if (empty($username) || empty($email) || empty($password)) {
            $error = "All fields (username, email, password) are required. Please fill in all fields.";
            error_log("Validation failed: Missing required fields.");
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format. Please enter a valid email address.";
            error_log("Validation failed: Invalid email ($email).");
        } elseif (!in_array($role, ['Senior_Admin', 'Junior_Admin'])) {
            $error = "Invalid role selected. Please choose Senior Admin or Junior Admin.";
            error_log("Validation failed: Invalid role ($role).");
        } else {
            // Handle image upload
            $image = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 2 * 1024 * 1024; // 2MB
                if (!in_array($_FILES['image']['type'], $allowed_types)) {
                    $error = "Invalid image type. Please upload a JPEG, PNG, or GIF.";
                    error_log("Image validation failed: Invalid type for $email");
                } elseif ($_FILES['image']['size'] > $max_size) {
                    $error = "Image size exceeds 2MB. Please upload a smaller file.";
                    error_log("Image validation failed: Size exceeds 2MB for $email");
                } else {
                    $upload_dir = 'Uploads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    $image_name = 'admin_' . time() . '_' . basename($_FILES['image']['name']);
                    $image_path = $upload_dir . $image_name;
                    if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                        $error = "Failed to upload image. Please try again.";
                        error_log("Image upload failed for $email");
                    } else {
                        $image = $image_path;
                        error_log("Image uploaded: $image_path");
                    }
                }
            } elseif ($_FILES['image']['error'] != UPLOAD_ERR_NO_FILE) {
                $error = "Image upload error. Please try again.";
                error_log("Image upload error: " . $_FILES['image']['error']);
            }

            if (!$error) {
                try {
                    if (!$conn->ping()) {
                        throw new Exception("Database connection lost. Please try again later.");
                    }

                    // Check for existing email
                    $stmt = $conn->prepare("SELECT admin_id FROM admins WHERE email = ?");
                    $stmt->bind_param('s', $email);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows > 0) {
                        $error = "Email already exists. Please use a different email address.";
                        error_log("Validation failed: Email ($email) already exists.");
                        $stmt->close();
                        // Clean up uploaded image if it exists
                        if ($image && file_exists($image)) {
                            unlink($image);
                            error_log("Cleaned up image: $image");
                        }
                    } else {
                        $stmt->close();
                        $password_hashed = password_hash($password, PASSWORD_BCRYPT);
                        $stmt = $conn->prepare("INSERT INTO admins (username, email, password, role, image, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                        if (!$stmt) {
                            throw new Exception("Prepare failed: " . $conn->error);
                        }
                        // Handle NULL image
                        $stmt->bind_param('sssss', $username, $email, $password_hashed, $role, $image);
                        error_log("Executing query: username=$username, email=$email, role=$role, image=" . ($image ?: 'NULL'));
                        if ($stmt->execute()) {
                            $success = "Admin created successfully! (ID: " . $stmt->insert_id . ")";
                            error_log("Query executed successfully. New admin_id: " . $stmt->insert_id);
                            header("Location: admin-setting.php?success=" . urlencode($success));
                            exit();
                        } else {
                            $error = "Failed to create admin: " . $stmt->error . ". Please try again.";
                            error_log("Query execution failed: " . $stmt->error);
                            // Clean up uploaded image if it exists
                            if ($image && file_exists($image)) {
                                unlink($image);
                                error_log("Cleaned up image: $image");
                            }
                        }
                        $stmt->close();
                    }
                } catch (Exception $e) {
                    $error = "Error creating admin: " . $e->getMessage() . ". Please try again.";
                    error_log("Exception: " . $e->getMessage());
                    if ($e instanceof mysqli_sql_exception && $e->getCode() == 1062) {
                        $error = "Email already exists. Please use a different email address.";
                        error_log("Duplicate email detected.");
                    }
                    // Clean up uploaded image if it exists
                    if ($image && file_exists($image)) {
                        unlink($image);
                        error_log("Cleaned up image: $image");
                    }
                }
            }
        }
    } elseif (isset($_POST['update'])) {
        // Update Admin
        $admin_id = (int)$_POST['admin_id'];
        $can_update = ($current_admin_role === 'Senior_Admin' || $admin_id == $current_admin_id);

        if (!$can_update) {
            $error = "Access Denied: You can only update your own details.";
            error_log("Update failed: Access denied for admin_id=$admin_id");
        } else {
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = !empty($_POST['password']) ? password_hash(trim($_POST['password']), PASSWORD_BCRYPT) : null;

            if (empty($username) || empty($email)) {
                $error = "Username and email are required. Please fill in all fields.";
                error_log("Update failed: Missing required fields.");
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Invalid email format. Please enter a valid email address.";
                error_log("Update failed: Invalid email ($email).");
            } else {
                // Handle image upload
                $image = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $max_size = 2 * 1024 * 1024; // 2MB
                    if (!in_array($_FILES['image']['type'], $allowed_types)) {
                        $error = "Invalid image type. Please upload a JPEG, PNG, or GIF.";
                        error_log("Image validation failed: Invalid type for admin_id=$admin_id");
                    } elseif ($_FILES['image']['size'] > $max_size) {
                        $error = "Image size exceeds 2MB. Please upload a smaller file.";
                        error_log("Image validation failed: Size exceeds 2MB for admin_id=$admin_id");
                    } else {
                        $upload_dir = 'Uploads/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        $image_name = 'admin_' . time() . '_' . basename($_FILES['image']['name']);
                        $image_path = $upload_dir . $image_name;
                        if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                            $error = "Failed to upload image. Please try again.";
                            error_log("Image upload failed for admin_id=$admin_id");
                        } else {
                            $image = $image_path;
                            error_log("Image uploaded: $image_path");
                        }
                    }
                } elseif ($_FILES['image']['error'] != UPLOAD_ERR_NO_FILE) {
                    $error = "Image upload error. Please try again.";
                    error_log("Image upload error: " . $_FILES['image']['error']);
                }

                if (!$error) {
                    try {
                        // Fetch current image to delete if new image is uploaded
                        if ($image) {
                            $stmt = $conn->prepare("SELECT image FROM admins WHERE admin_id = ?");
                            $stmt->bind_param('i', $admin_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $current_image = $result->fetch_assoc()['image'];
                            $stmt->close();
                            if ($current_image && file_exists($current_image)) {
                                unlink($current_image);
                                error_log("Deleted old image: $current_image");
                            }
                        }

                        if ($password && $image) {
                            $stmt = $conn->prepare("UPDATE admins SET username = ?, email = ?, password = ?, image = ? WHERE admin_id = ?");
                            $stmt->bind_param('ssssi', $username, $email, $password, $image, $admin_id);
                        } elseif ($password) {
                            $stmt = $conn->prepare("UPDATE admins SET username = ?, email = ?, password = ? WHERE admin_id = ?");
                            $stmt->bind_param('sssi', $username, $email, $password, $admin_id);
                        } elseif ($image) {
                            $stmt = $conn->prepare("UPDATE admins SET username = ?, email = ?, image = ? WHERE admin_id = ?");
                            $stmt->bind_param('sssi', $username, $email, $image, $admin_id);
                        } else {
                            $stmt = $conn->prepare("UPDATE admins SET username = ?, email = ? WHERE admin_id = ?");
                            $stmt->bind_param('ssi', $username, $email, $admin_id);
                        }
                        if ($stmt->execute()) {
                            $success = "Admin updated successfully!";
                            error_log("Admin updated successfully: admin_id=$admin_id");
                            header("Location: admin-setting.php?success=" . urlencode($success));
                            exit();
                        } else {
                            $error = "Failed to update admin: " . $stmt->error . ". Please try again.";
                            error_log("Update failed: " . $stmt->error);
                            // Clean up uploaded image if it exists
                            if ($image && file_exists($image)) {
                                unlink($image);
                                error_log("Cleaned up image: $image");
                            }
                        }
                        $stmt->close();
                    } catch (Exception $e) {
                        $error = "Error updating admin: " . $e->getMessage() . ". Please try again.";
                        error_log("Update exception: " . $e->getMessage());
                        // Clean up uploaded image if it exists
                        if ($image && file_exists($image)) {
                            unlink($image);
                            error_log("Cleaned up image: $image");
                        }
                    }
                }
            }
        }
    } elseif (isset($_POST['delete']) && $current_admin_role === 'Senior_Admin') {
        // Delete Admin
        $admin_id = (int)$_POST['admin_id'];
        if ($admin_id == $current_admin_id) {
            $error = "You cannot delete your own account.";
            error_log("Delete failed: Cannot delete own account, admin_id=$admin_id");
        } else {
            try {
                $stmt = $conn->prepare("SELECT role, image FROM admins WHERE admin_id = ?");
                $stmt->bind_param('i', $admin_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $deleting_admin_role = $row['role'];
                $image = $row['image'];
                $stmt->close();

                $stmt = $conn->prepare("SELECT COUNT(*) FROM admins WHERE role = 'Senior_Admin'");
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_row();
                $senior_admin_count = $row[0];
                $stmt->close();

                if ($deleting_admin_role === 'Senior_Admin' && $senior_admin_count <= 1) {
                    $error = "Cannot delete the last Senior Admin.";
                    error_log("Delete failed: Cannot delete last Senior Admin, admin_id=$admin_id");
                } else {
                    $stmt = $conn->prepare("DELETE FROM admins WHERE admin_id = ?");
                    $stmt->bind_param('i', $admin_id);
                    if ($stmt->execute()) {
                        // Delete image if it exists
                        if ($image && file_exists($image)) {
                            unlink($image);
                            error_log("Deleted image: $image");
                        }
                        $success = "Admin deleted successfully!";
                        error_log("Admin deleted successfully: admin_id=$admin_id");
                        header("Location: admin-setting.php?success=" . urlencode($success));
                        exit();
                    } else {
                        $error = "Failed to delete admin: " . $stmt->error . ". Please try again.";
                        error_log("Delete failed: " . $stmt->error);
                    }
                    $stmt->close();
                }
            } catch (Exception $e) {
                $error = "Error deleting admin: " . $e->getMessage() . ". Please try again.";
                error_log("Delete exception: " . $e->getMessage());
            }
        }
    }
}

// Fetch all admins for display
$stmt = $conn->prepare("SELECT admin_id, username, email, role, image FROM admins");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();
$admins = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
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
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="css/materialize.css" rel="stylesheet">
    <link href="css/bootstrap.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet" />
    <link href="css/style-mob.css" rel="stylesheet" />
    <style>
        .admin-form-container {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .admin-form-container h4 {
            color: #1a1a2e;
            margin-bottom: 20px;
        }
        .admin-form-container .input-field {
            margin-bottom: 20px;
        }
        .admin-form-container input[type="text"],
        .admin-form-container input[type="email"],
        .admin-form-container input[type="password"],
        .admin-form-container input[type="file"],
        .admin-form-container select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
        }
        .admin-form-container select {
            height: 40px;
        }
        .admin-form-container button {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            font-size: 1rem;
            background: #28a745;
            color: #fff;
        }
        .admin-table {
            width: 100%;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .admin-table th,
        .admin-table td {
            padding: 15px;
            text-align: left;
        }
        .admin-table th {
            background: #002147;
            color: #fff;
        }
        .admin-table td button {
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 0.9rem;
            margin: 5px;
        }
        .admin-table td button.green {
            background: #28a745;
            color: #fff;
           
        }
        .admin-table td button.red {
            background: #dc3545;
            color: #fff;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 500px;
        }
        .modal-content .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            cursor: pointer;
        }
        .modal-content .input-field {
            margin-bottom: 20px;
        }
        .modal-content input[type="text"],
        .modal-content input[type="email"],
        .modal-content input[type="password"],
        .modal-content input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .modal-content button {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            background: #28a745;
            color: #fff;
        }
        .admin-table td img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
        }
        .error {
            color: red;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .success {
            color: green;
            font-weight: bold;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container-fluid sb1">
        <div class="row">
            <h3 style="color: #fff; text-align: center; margin-top: 1rem;">
                Campus Query Chatbot Admin Panel
                <span class="admin-notification" id="notification">
                    <i class="fa fa-commenting-o" style="color: #fff; margin-left:10rem;"></i>
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
                                <img src="<?= htmlspecialchars($admin['image']) ?>" alt="Admin Image">
                            <?php else: ?>
                                <img src="images/default_admin_icon.png" alt="Default Icon">
                            <?php endif; ?>
                        </li> 
                        <h6 style="margin-left: 8rem;">Admin ID: <?php echo htmlspecialchars($admin['admin_id']); ?></h6>
                        <h6 style="margin-left: 8rem;">Name: <?php echo htmlspecialchars($admin['username']); ?></h6>                   
                    </ul>
                </div>
                <div class="sb2-13">
                    <ul class="collapsible" data-collapsible="accordion">
                        <li><a href="admin.php"><i class="fa fa-bar-chart"></i> Dashboard</a></li>
                        <li><a href="admin-setting.php" class="menu-active"><i class="fa fa-cogs"></i> Account Information</a></li>
                        <li><a href="javascript:void(0)" class="collapsible-header"><i class="fas fa-chart-line"></i> Chatbot Usage Analytics</a>
                            <div class="collapsible-body left-sub-menu">
                                <ul>
                                    <li><a href="chatlogs.php">Chatlogs</a></li>
                                    <li><a href="user_interactions.php">User Interaction Data</a></li>
                                    <li><a href="FAQ.php">Frequently Asked Questions</a></li>
                                </ul>
                            </div>
                        </li>
                        <li><a href="javascript:void(0)" class="collapsible-header"><i class="fas fa-database"></i> AI Chatbot Model </a>
                            <div class="collapsible-body left-sub-menu">
                                <ul>
                                    <li><a href="chatbot-data.php" target="_blank">AI Chatbot Model </a></li>
                                </ul>
                            </div>
                        </li>
                        <li><a href="javascript:void(0)" class="collapsible-header"><i class="fas fa-comment-alt"></i> Feedback</a>
                            <div class="collapsible-body left-sub-menu">
                                <ul>
                                    <li><a href="feedback.php">feedback</a></li>
                                </ul>
                            </div>
                        </li>
                        <li><a href="javascript:void(0)" class="collapsible-header"><i class="fa fa-commenting-o"></i> Pushed Queries</a>
                            <div class="collapsible-body left-sub-menu">
                                <ul>
                                    <li><a href="pushed_query.php">All Queries</a></li>
                                </ul>
                            </div>
                        </li>
                        <li><a href="javascript:void(0)" class="collapsible-header"><i class="fas fa-file-alt"></i> Report Overview</a>
                            <div class="collapsible-body left-sub-menu">
                                <ul>
                                    <li><a href="report.php">Report</a></li>
                                </ul>
                            </div>
                        </li>
                        <li><a href="http://127.0.0.1:5000" target="_blank"><i class="fas fa-robot"></i>Chatbot</a></li>
                        <li><a href="./admin-logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
                    </ul>
                </div>
            </div>

            <div class="sb2-2">
                <div class="sb2-2-2">
                    <ul>
                        <li><a href="#"><i class="fa fa-home"></i> Home</a></li>
                        <li class="active-bre"><a href="#"> Account Information</a></li>
                        <li class="page-back"><a href="admin.php"><i class="fa fa-backward"></i> Back</a></li>
                    </ul>
                </div>

                <div class="sb2-2-3">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="box-inn-sp admin-form">
                                <div class="inn-title">
                                    <h4>Admin Management</h4>
                                    <?php if (isset($_GET['success'])): ?>
                                        <p class="success"><?= htmlspecialchars($_GET['success']) ?></p>
                                        <script>alert('<?= htmlspecialchars($_GET['success']) ?>');</script>
                                    <?php endif; ?>
                                    <?php if (isset($success)): ?>
                                        <p class="success"><?= htmlspecialchars($success) ?></p>
                                        <script>alert('<?= htmlspecialchars($success) ?>');</script>
                                    <?php endif; ?>
                                    <?php if (isset($error)): ?>
                                        <p class="error"><?= htmlspecialchars($error) ?></p>
                                        <script>alert('<?= htmlspecialchars($error) ?>');</script>
                                    <?php endif; ?>
                                    <!-- Display session info -->
                                    <p>Logged-in Admin ID: <?= htmlspecialchars($current_admin_id) ?>, Role: <?= htmlspecialchars($current_admin_role) ?></p>
                                </div>
                                <div class="tab-inn">
                                    <!-- Create Admin Form -->
                                    <?php if ($current_admin_role === 'Senior_Admin'): ?>
                                        <div class="admin-form-container">
                                            <h4>Create New Admin Account</h4>
                                            <form method="POST" action="" id="createAdminForm" enctype="multipart/form-data">
                                                <div class="input-field">
                                                    <input type="text" name="username" placeholder="Username" required>
                                                    <input type="email" name="email" placeholder="Email" required>
                                                    <input type="password" name="password" placeholder="Password" required>
                                                    <input type="file" name="image" accept="image/jpeg,image/png,image/gif">
                                                </div>
                                                <div class="input-field">
                                                    <select name="role" required>
                                                        <option value="Senior_Admin">Senior Admin</option>
                                                        <option value="Junior_Admin" selected>Junior Admin</option>
                                                    </select>
                                                </div>
                                                <div class="input-field">
                                                    <button type="submit" name="create" class="waves-effect waves-light btn" style="width: 20rem;">Create Admin</button>
                                                </div>
                                            </form>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Display Admins Table -->
                                    <div class="inn-title">
                                        <h4>Admin Accounts</h4>
                                    </div>
                                    <table class="admin-table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Username</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>Image</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($admins as $admin): ?>
                                                <?php
                                                $can_edit = ($current_admin_role === 'Senior_Admin' || $admin['admin_id'] == $current_admin_id);
                                                $can_delete = ($current_admin_role === 'Senior_Admin' && $admin['admin_id'] != $current_admin_id);
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($admin['admin_id']) ?></td>
                                                    <td><?= htmlspecialchars($admin['username']) ?></td>
                                                    <td><?= htmlspecialchars($admin['email']) ?></td>
                                                    <td><?= htmlspecialchars($admin['role']) ?></td>
                                                    <td>
                                                        <?php if ($admin['image'] && file_exists($admin['image'])): ?>
                                                            <img src="<?= htmlspecialchars($admin['image']) ?>" alt="Admin Image">
                                                        <?php else: ?>
                                                            <img src="images/default_admin_icon.png" alt="Default Icon">
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($can_edit): ?>
                                                            <button style="width:15rem;" class="waves-effect waves-light btn green" onclick="openModal('editModal-<?= $admin['admin_id'] ?>')">Edit</button>
                                                        <?php endif; ?>
                                                        <?php if ($can_delete): ?>
                                                            <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this admin?');">
                                                                <input type="hidden" name="admin_id" value="<?= $admin['admin_id'] ?>">
                                                                <button type="submit" name="delete" style="width:15rem;"  class="waves-effect waves-light btn red">Delete</button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>

                                    <!-- Edit Modals -->
                                    <?php foreach ($admins as $admin): ?>
                                        <?php if ($current_admin_role === 'Senior_Admin' || $admin['admin_id'] == $current_admin_id): ?>
                                            <div id="editModal-<?= $admin['admin_id'] ?>" class="modal">
                                                <div class="modal-content">
                                                    <span class="close" onclick="closeModal('editModal-<?= $admin['admin_id'] ?>')">×</span>
                                                    <h4>Edit Admin</h4>
                                                    <form method="POST" action="" enctype="multipart/form-data">
                                                        <input type="hidden" name="admin_id" value="<?= $admin['admin_id'] ?>">
                                                        <div class="input-field">
                                                            <input type="text" name="username" value="<?= htmlspecialchars($admin['username']) ?>" placeholder="Username" required>
                                                        </div>
                                                        <div class="input-field">
                                                            <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" placeholder="Email" required>
                                                        </div>
                                                        <div class="input-field">
                                                            <input type="password" name="password" placeholder="New Password (optional)">
                                                        </div>
                                                        <div class="input-field">
                                                            <input type="file" name="image" accept="image/jpeg,image/png,image/gif">
                                                            <?php if ($admin['image'] && file_exists($admin['image'])): ?>
                                                                <p>Current Image: <img src="<?= htmlspecialchars($admin['image']) ?>" alt="Current Image" style="width: 50px; height: 50px; border-radius: 50%;"></p>
                                                            <?php endif; ?>
                                                        </div>
                                                        <button type="submit" name="update" class="waves-effect waves-light btn">Update</button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }

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

        // Client-side validation
        document.getElementById('createAdminForm').onsubmit = function(e) {
            const email = document.querySelector('input[name="email"]').value;
            const username = document.querySelector('input[name="username"]').value;
            const password = document.querySelector('input[name="password"]').value;
            if (!username || !email || !password) {
                alert('All fields (username, email, password) are required. Please fill in all fields.');
                e.preventDefault();
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                alert('Invalid email format. Please enter a valid email address.');
                e.preventDefault();
            }
        };
    </script>

    <script src="js/main.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/materialize.min.js"></script>
    <script src="js/custom.js"></script>
</body>
</html>