<?php
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log incoming POST data
    file_put_contents('post_data.log', print_r($_POST, true));

    $admin_email = 'rasabotcodz@gmail.com'; // Hardcoded admin email
    $recipient_name = isset($_POST['recipient_name']) ? $_POST['recipient_name'] : '';
    $recipient_email = isset($_POST['recipient_email']) ? $_POST['recipient_email'] : '';
    $subject = isset($_POST['subject']) ? $_POST['subject'] : '';
    $message = isset($_POST['message']) ? $_POST['message'] : '';

    if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL) || !filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email address';
        echo json_encode($response);
        exit;
    }
    if (empty($recipient_name) || empty($recipient_email) || empty($subject) || empty($message)) {
        $response['message'] = 'All fields are required';
        echo json_encode($response);
        exit;
    }

    require_once 'db.php';
    if (!$conn || $conn->connect_error) {
        die("Connection failed: " . ($conn ? $conn->connect_error : 'No connection object.'));
    }
    if (!$conn->ping()) {
        die("Database connection is closed.");
    }

    $mail = new PHPMailer(true);
    try {
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function($str, $level) {
            file_put_contents('smtp_debug.log', "[$level] $str\n", FILE_APPEND);
        };
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'rasabotcodz@gmail.com';
        $mail->Password = 'yecj jtzy whdp cdwc'; // Verify this is correct
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('rasabotcodz@gmail.com', 'MMU Chatbot Admin Team');
        $mail->addAddress($recipient_email, $recipient_name);
        $mail->addReplyTo($admin_email, 'MMU Chatbot Admin Team');
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = "
            <html>
            <head><title>" . htmlspecialchars($subject) . "</title></head>
            <body>
                <p>Hello " . htmlspecialchars($recipient_name) . ",</p>
                <div>" . nl2br(htmlspecialchars($message)) . "</div>
                <p>Best regards,<br>MMU Chatbot Admin Team</p>
            </body>
            </html>
        ";

        $mail->send();
        $response['success'] = true;
        $response['message'] = 'Email sent successfully';
    } catch (Exception $e) {
        $response['message'] = 'Failed to send email: ' . $mail->ErrorInfo;
    }
} else {
    $response['message'] = 'Invalid request method';
}

header('Content-Type: application/json');
echo json_encode($response);
?>