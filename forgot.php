<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require 'PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;

// Initialize error variable
$error = '';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userEmail = $_POST['email']; // Retrieve the entered email

    include('config.php'); // Assuming config.php has your DB connection details

    // Check if the email exists in the database (no verification_status check)
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $userEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // Generate a random 6-digit OTP
        $otp = mt_rand(100000, 999999);

        // Update the database with the new OTP
        $updateSql = "UPDATE users SET otp = ? WHERE email = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("ss", $otp, $userEmail);
        if ($updateStmt->execute()) {
            // Send OTP via email
            $mail = new PHPMailer();

            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->Port = 587;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->SMTPAuth = true;
            $mail->Username = 'srisinhasumit10@gmail.com'; // Replace with your email
            $mail->Password = 'ggtbuofjfdmqcohr'; // Replace with your app-specific password

            $mail->setFrom('no-reply@chaijunction.com', 'Chai Junction Cafe');
            $mail->addAddress($userEmail);

            $mail->isHTML(true);
            $mail->Subject = 'Reset Your Chai Junction Cafe Password';

            $body = "
<html>
<head>
  <title>Reset Your Chai Junction Cafe Password</title>
</head>
<body>
  <p>Hi,</p>
  <p>We received a request to reset your password for your Chai Junction Cafe account. Use the following One-Time Password (OTP) to reset your password:</p>
  <p style='font-weight: bold; font-size: 1.2em;'>" . $otp . "</p>
  <p>This OTP is valid for 5 minutes.</p>
  <p>If you did not request this, you can safely ignore this email. No changes will be made to your account.</p>
  <p>Thank you,<br />The Chai Junction Cafe Team</p>
</body>
</html>";

            $mail->Body = $body;

            if ($mail->send()) {
                $_SESSION['email'] = $userEmail;
                header('Location: passverification.php?email=' . urlencode($userEmail));
                exit();
            } else {
                $error = 'Error sending email: ' . $mail->ErrorInfo;
            }
        } else {
            $error = 'Error updating OTP: ' . $conn->error;
        }
        $updateStmt->close();
    } else {
        $error = "Email not found in our system.";
    }
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chai Junction - Reset Password</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="image/logo1.png" alt="LOGO">
        </div>
        <p class="title" style="font-size: 24px; margin-bottom: 15px; text-align: center;">Reset Your Password</p>
        <p class="subtitle">Enter your email address to receive a 6-digit OTP for password reset.</p>

        <?php if (!empty($error)): ?>
            <p style="color: red; text-align: center;"><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="POST">
            <div class="input-box">
                <label>Enter Your Email</label>
                <input type="email" name="email" placeholder="example@gmail.com" required>
            </div>
            <button type="submit" class="sign-in send-otp">Send OTP</button>
        </form>
        <p class="terms"><a href="login.php" class="terms">Back to Sign In</a></p>
    </div>
    <p class="footer">T&C © 2025 Chai Junction. All rights reserved.<br>Created by Sumit Srivastava<br>version - 3.1.1</p>
</body>
</html>