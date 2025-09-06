<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session with secure settings
session_start([
    'cookie_lifetime' => 604800, // 7 days
    'cookie_httponly' => true,
    'cookie_secure' => false, // Set to false for local testing without HTTPS
    'cookie_samesite' => 'Strict'
]);

// Database connection settings
$host = "localhost";
$dbname = "chatbot_db";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize reset attempts
if (!isset($_SESSION['reset_attempts'])) {
    $_SESSION['reset_attempts'] = ['count' => 0, 'timestamp' => time()];
}

// Include PHPMailer
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Handle forgot password request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_reset'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid CSRF token!";
    } elseif ($_SESSION['reset_attempts']['count'] >= 10 && (time() - $_SESSION['reset_attempts']['timestamp']) < 3600) {
        $error = "Too many reset attempts. Please try again later.";
    } else {
        $email = trim($_POST['email']);
        $_SESSION['reset_attempts']['count']++;
        if ((time() - $_SESSION['reset_attempts']['timestamp']) > 3600) {
            $_SESSION['reset_attempts'] = ['count' => 1, 'timestamp' => time()];
        }

        // Check if email exists
        $stmt = $pdo->prepare("SELECT admin_id, email FROM admins WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Store token in database
            $stmt = $pdo->prepare("INSERT INTO password_resets (admin_id, token, expires) VALUES (:admin_id, :token, :expires)");
            $stmt->bindParam(':admin_id', $admin['admin_id']);
            $stmt->bindParam(':token', $token);
            $stmt->bindParam(':expires', $expires);
            $stmt->execute();

            // Send reset email
            $reset_link = "http://localhost/Admin/forgot_password.php?token=$token"; // Update to your actual domain
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'rasabotcodz@gmail.com';
                $mail->Password = 'yecj jtzy whdp cdwc'; // Verify this is correct
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Recipients
                $mail->setFrom('no-reply@yourdomain.com', 'AI Chatbot Admin');
                $mail->addAddress($email);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body = "Hello,<br><br>Click the following link to reset your password: <a href='$reset_link'>$reset_link</a><br>This link will expire in 1 hour.<br><br>If you did not request a password reset, please ignore this email.<br><br>Best regards,<br>AI Chatbot Admin Team";
                $mail->AltBody = "Click this link to reset your password: $reset_link\nLink expires in 1 hour.\n\nIf you did not request a password reset, please ignore this email.";

                $mail->send();
                $success = "Password reset link has been sent to your email.";
            } catch (Exception $e) {
                $error = "Failed to send reset email. Please try again later. Error: {$mail->ErrorInfo}";
            }
        } else {
            $error = "No account found with that email address.";
        }
    }
}

// Handle password reset and admin details update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid CSRF token!";
    } else {
        $token = isset($_POST['token']) ? trim($_POST['token']) : '';
        $new_password = trim($_POST['new_password']);
        $confirm_password = trim($_POST['confirm_password']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);

        if (empty($token)) {
            $error = "No reset token provided!";
        } else {
            // Validate token
            $stmt = $pdo->prepare("SELECT admin_id, expires FROM password_resets WHERE token = :token");
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reset) {
                $error = "Invalid reset token!";
            } elseif (strtotime($reset['expires']) <= time()) {
                $error = "Reset token has expired!";
            } else {
                // Check if username or email is already taken
                $stmt = $pdo->prepare("SELECT admin_id FROM admins WHERE (username = :username OR email = :email) AND admin_id != :admin_id");
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':admin_id', $reset['admin_id']);
                $stmt->execute();
                if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                    $error = "Username or email is already in use!";
                } elseif ($new_password !== $confirm_password) {
                    $error = "Passwords do not match!";
                } elseif (strlen($new_password) < 8) {
                    $error = "Password must be at least 8 characters long!";
                } else {
                    // Update admin details
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE admins SET username = :username, email = :email, password = :password WHERE admin_id = :admin_id");
                    $stmt->bindParam(':username', $username);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':password', $hashed_password);
                    $stmt->bindParam(':admin_id', $reset['admin_id']);
                    $stmt->execute();

                    // Delete used token
                    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = :token");
                    $stmt->bindParam(':token', $token);
                    $stmt->execute();

                    $success = "Your account details have been updated successfully. Please <a href='admin-login.php'>log in</a>.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Forgot Password - AI Chatbot Admin Portal</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Reset password for AI Chatbot admin portal">
    <meta name="keywords" content="forgot password, admin, AI, chatbot">
    <link rel="shortcut icon" href="images/mmu_logo_- no bg.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --primary-light: rgba(59, 130, 246, 0.1);
            --secondary: #f0f9ff;
            --dark: #1e293b;
            --light: #f8fafc;
            --danger: #ef4444;
            --danger-light: rgba(239, 68, 68, 0.1);
            --success: #10b981;
            --text: #334155;
            --text-light: #94a3b8;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: rgb(238, 241, 241);
            color: var(--text);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow: auto;
        }

        .reset-container {
            display: flex;
            max-width: 1000px;
            width: 65%;
            margin: 1.5rem;
            background-color: white;
            border-radius: 12px;
            box-shadow: var(--shadow-xl);
            overflow: hidden;
        }

        .reset-image {
            flex: 1;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.5)), url('images/login/bg3.jpg');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            padding: 40px;
            position: relative;
        }

        .reset-quote {
            max-width: 500px;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .reset-quote h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .reset-quote p {
            font-size: 1.1rem;
            line-height: 1.6;
            opacity: 0.9;
        }

        .reset-form-container {
            flex: 1;
            max-width: 500px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 40px;
            background-color: white;
        }

        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            display: block;
            padding: 10px;
            background-color: white;
            border-radius: 50%;
            box-shadow: var(--shadow-md);
        }

        .reset-title {
            font-size: 26px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .reset-subtitle {
            font-size: 15px;
            color: var(--text-light);
        }

        .error, .success {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error {
            background-color: var(--danger-light);
            color: var(--danger);
        }

        .success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .error i, .success i {
            font-size: 16px;
        }

        .reset-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 18px;
        }

        .input-group input {
            width: 100%;
            padding: 16px 16px 16px 45px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            color: var(--dark);
            transition: all 0.3s ease;
        }

        .input-group input::placeholder {
            color: var(--text-light);
        }

        .input-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .input-group .toggle-password {
            position: absolute;
            right: 50px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-light);
            font-size: 18px;
        }

        .reset-btn {
            background: var(--primary);
            color: white;
            padding: 16px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .reset-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .reset-btn:active {
            transform: translateY(0);
        }

        .reset-btn:disabled {
            background: var(--text-light);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .form-footer {
            margin-top: 30px;
            text-align: center;
            font-size: 14px;
            color: var(--text-light);
        }

        .form-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        .animation-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            opacity: 0.2;
        }

        .floating-particle {
            position: absolute;
            background-color: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            animation: float 20s infinite linear;
        }

        @keyframes float {
            0% {
                transform: translateY(100vh) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) translateX(20vw);
                opacity: 0;
            }
        }

        @media (max-width: 992px) {
            .reset-image {
                display: none;
            }
            
            .reset-container {
                max-width: 500px;
            }
        }

        @media (max-width: 576px) {
            .reset-form-container {
                padding: 30px 20px;
            }
            
            .reset-title {
                font-size: 22px;
            }
            
            .reset-subtitle {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-image">
            <div class="animation-container" id="animationContainer"></div>
            <div class="reset-quote">
                <h2>Reset Your Password</h2>
                <p>Securely update your account details to continue managing the AI Chatbot platform.</p>
            </div>
        </div>
        <div class="reset-form-container">
            <div class="form-header">
                <img src="images/mmu_logo_- no bg.png" alt="Campus Logo" class="logo">
                <h1 class="reset-title">
                    <?php echo isset($_GET['token']) ? 'Reset Password & Edit Details' : 'Forgot Password'; ?>
                </h1>
                <p class="reset-subtitle">
                    <?php echo isset($_GET['token']) ? 'Update your account information' : 'Enter your email to receive a reset link'; ?>
                </p>
            </div>

            <?php if (isset($error)): ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php elseif (isset($success)): ?>
                <div class="success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['token'])): ?>
                <?php
                // Verify token and fetch admin details
                $token = $_GET['token'];
                $stmt = $pdo->prepare("SELECT a.admin_id, a.username, a.email, pr.expires 
                                      FROM admins a 
                                      JOIN password_resets pr ON a.admin_id = pr.admin_id 
                                      WHERE pr.token = :token");
                $stmt->bindParam(':token', $token);
                $stmt->execute();
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$admin || strtotime($admin['expires']) <= time()) {
                    $error = "Invalid or expired reset token!";
                }
                ?>
                <?php if (!isset($error)): ?>
                    <form class="reset-form" method="POST" action="" id="resetForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        <input type="hidden" name="reset_password" value="1">

                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" name="username" id="username" placeholder="Username" value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                        </div>

                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" id="email" placeholder="Email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                        </div>

                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="new_password" id="new_password" placeholder="New Password" required>
                            <span class="toggle-password" id="toggleNewPassword">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>

                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
                            <span class="toggle-password" id="toggleConfirmPassword">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>

                        <button type="submit" class="reset-btn" id="resetBtn">
                            Update Details
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <form class="reset-form" method="POST" action="" id="requestForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="request_reset" value="1">

                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" id="email" placeholder="Enter your email" required>
                    </div>

                    <button type="submit" class="reset-btn" id="requestBtn">
                        Send Reset Link
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
            <?php endif; ?>

            <div class="form-footer">
                <p>Remember your password? <a href="admin-login.php">Back to Login</a></p>
            </div>
        </div>
    </div>

    <script>
        // Create floating particles for animation
        const animationContainer = document.getElementById('animationContainer');
        const particleCount = 15;

        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.classList.add('floating-particle');
            const size = Math.floor(Math.random() * 20) + 5;
            particle.style.width = `${size}px`;
            particle.style.height = `${size}px`;
            particle.style.left = `${Math.random() * 100}%`;
            particle.style.bottom = `${Math.random() * 20}%`;
            const duration = Math.floor(Math.random() * 15) + 15;
            particle.style.animationDuration = `${duration}s`;
            const delay = Math.floor(Math.random() * 10);
            particle.style.animationDelay = `${delay}s`;
            animationContainer.appendChild(particle);
        }

        // Toggle password visibility
        const togglePasswordFields = ['toggleNewPassword', 'toggleConfirmPassword'];
        togglePasswordFields.forEach(id => {
            const toggle = document.getElementById(id);
            if (toggle) {
                toggle.addEventListener('click', function() {
                    const input = this.parentElement.querySelector('input');
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    const icon = this.querySelector('i');
                    icon.classList.toggle('fa-eye');
                    icon.classList.toggle('fa-eye-slash');
                });
            }
        });

        // Form validation
        const resetForm = document.getElementById('resetForm');
        const requestForm = document.getElementById('requestForm');

        if (resetForm) {
            const inputs = resetForm.querySelectorAll('input:not([type="hidden"])');
            const resetBtn = document.getElementById('resetBtn');
            const originalBtnText = resetBtn.innerHTML;

            resetForm.addEventListener('submit', (e) => {
                let valid = true;
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        valid = false;
                        input.style.borderColor = '#ef4444';
                        input.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1)';
                    }
                });

                const newPassword = document.getElementById('new_password');
                const confirmPassword = document.getElementById('confirm_password');
                if (newPassword.value !== confirmPassword.value) {
                    valid = false;
                    confirmPassword.style.borderColor = '#ef4444';
                    confirmPassword.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1)';
                }

                if (!valid) {
                    e.preventDefault();
                    resetBtn.disabled = true;
                    setTimeout(() => {
                        resetBtn.disabled = false;
                    }, 1000);
                } else {
                    resetBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Updating...';
                    resetBtn.disabled = true;
                    setTimeout(() => {
                        if (resetBtn.disabled) {
                            resetBtn.innerHTML = originalBtnText;
                            resetBtn.disabled = false;
                        }
                    }, 3000);
                }
            });
        }

        if (requestForm) {
            const emailInput = document.getElementById('email');
            const requestBtn = document.getElementById('requestBtn');
            const originalBtnText = requestBtn.innerHTML;

            requestForm.addEventListener('submit', (e) => {
                if (!emailInput.value.trim()) {
                    e.preventDefault();
                    emailInput.style.borderColor = '#ef4444';
                    emailInput.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1)';
                    requestBtn.disabled = true;
                    setTimeout(() => {
                        requestBtn.disabled = false;
                    }, 1000);
                } else {
                    requestBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Sending...';
                    requestBtn.disabled = true;
                    setTimeout(() => {
                        if (requestBtn.disabled) {
                            requestBtn.innerHTML = originalBtnText;
                            requestBtn.disabled = false;
                        }
                    }, 3000);
                }
            });
        }

        // Input field animations
        const inputFields = document.querySelectorAll('.input-group input');
        inputFields.forEach(input => {
            input.addEventListener('focus', () => {
                input.parentElement.querySelector('i').style.color = '#3b82f6';
            });
            input.addEventListener('blur', () => {
                input.parentElement.querySelector('i').style.color = '#94a3b8';
            });
        });
    </script>
</body>
</html>