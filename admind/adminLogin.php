<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $enteredPassword = $_POST['password'];

    require('../config.php');

    // Query to fetch admin details including name
    $sql = "SELECT id, name, password FROM admin WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Verify the password
        if (password_verify($enteredPassword, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $row['id'];

            // Insert login log with admin name
            $logSql = "INSERT INTO login_log (admin_id, message, login_time) VALUES (?, ?, NOW())";
            $logStmt = $conn->prepare($logSql);
            $logMessage = "Admin " . $row['name'] . " logged in successfully";
            $logStmt->bind_param("is", $row['id'], $logMessage);
            $logStmt->execute();
            $logStmt->close();

            header("Location: adminwelcome.php");
            exit();
        } else {
            $error_message = "Invalid password";
        }
    } else {
        $error_message = "Invalid email or phone number";
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
    <title>Chai Junction - Admin Sign In</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/login.css">
</head>

<body>
    <div class="logo">
        <a href="../index.html"><img src="../image/logo1.png" alt="LOGO"></a>
    </div>

    <?php if (!empty($error_message)): ?>
        <p style="color: red; text-align: center;"><?php echo $error_message; ?></p>
    <?php endif; ?>

    <div class="container">
        <p class="title">Admin Sign In</p>
        <br>
        <form method="POST" action="adminLogin.php">
            <div class="input-box">
                <label>Enter Your Email</label>
                <input type="text" name="email" placeholder="example@gmail.com" required>
            </div>
            <div class="input-box">
                <label>Enter Your Password</label>
                <input type="password" name="password" placeholder="************" required>
            </div>
            <button type="submit" class="sign-in-btn">Sign In</button>
        </form>
        <p class="links"><a href="adforgot.php">Forgot Your Password?</a></p>
    </div>
    <br>
    <p class="footer">T&C © 2025 Chai Junction. All rights reserved.</p>
</body>

</html>