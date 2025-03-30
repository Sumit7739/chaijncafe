<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['email']) || !isset($_GET['email']) || $_SESSION['email'] !== $_GET['email']) {
    header('Location: forgot.php');
    exit();
}

$userEmail = $_SESSION['email'];
$error = '';
$success = '';

require('config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredOtp = trim($_POST['otp']);
    $newPassword = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    $sql = "SELECT otp FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $userEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $storedOtp = $row['otp'];

        if ((string)$enteredOtp === (string)$storedOtp) {
            if ($newPassword === $confirmPassword) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                $updateSql = "UPDATE users SET password = ?, otp = NULL WHERE email = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("ss", $hashedPassword, $userEmail);

                if ($updateStmt->execute()) {
                    $success = "Password reset successfully! Redirecting to login...";
                    unset($_SESSION['email']);
                    header('Refresh: 2; URL=login.php');
                } else {
                    $error = "Error updating password: " . $conn->error;
                }
                $updateStmt->close();
            } else {
                $error = "Passwords do not match.";
            }
        } else {
            $error = "Invalid OTP. Please try again.";
        }
    } else {
        $error = "User not found.";
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
    <title>Chai Junction - Verify OTP</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/login.css">
</head>

<body>
    <div class="container">
        <div class="logo">
            <img src="image/logo1.png" alt="LOGO">
        </div>
        <p class="title" style="font-size: 24px; margin-bottom: 15px; text-align: center;">Verify OTP</p>
        <p class="subtitle">Enter the 6-digit OTP sent to <?php echo htmlspecialchars($userEmail); ?> and set your new password.</p>

        <?php if (!empty($error)): ?>
            <p style="color: red; text-align: center;"><?php echo $error; ?></p>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <p style="color: green; text-align: center;"><?php echo $success; ?></p>
        <?php endif; ?>

        <form method="POST">
            <div class="input-box">
                <label>Enter OTP</label>
                <input type="text" name="otp" placeholder="6-digit OTP" required maxlength="6" pattern="\d{6}">
            </div>
            <div class="input-box">
                <label>New Password</label>
                <input type="password" name="password" placeholder="Enter new password" required>
            </div>
            <div class="input-box">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="Confirm new password" required>
            </div>
            <button type="submit" class="sign-in send-otp">Reset Password</button>
        </form>
        <p class="terms"><a href="resetpassword.php" class="terms">Resend OTP</a></p>
    </div>
    <p class="footer">T&C © 2025 Chai Junction. All rights reserved.<br>Created by Sumit Srivastava<br>version - 3.1.1</p>
</body>

</html>