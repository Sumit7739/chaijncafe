<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require('config.php');

    $emailOrPhone = trim($_POST['email_or_phone']);
    $enteredPassword = trim($_POST['password']);

    $sql = "SELECT user_id, password FROM users WHERE email = ? OR phone = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL error: " . $conn->error);
    }

    $stmt->bind_param("ss", $emailOrPhone, $emailOrPhone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $storedHash = $row['password'];

        // Verify Password
        if (password_verify($enteredPassword, $storedHash)) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $row['user_id'];

            header("Location: welcome.php");
            exit();
        } else {
            $error_message = "Incorrect password.";
        }
    } else {
        $error_message = "Invalid email or phone number.";
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
    <title>Chai Junction - Sign In</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .password-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-container input {
            width: 100%;
            padding-right: 30px;
            /* Space for the eye icon */
        }

        .password-container i {
            position: absolute;
            right: 10px;
            cursor: pointer;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="logo">
        <a href="index.html"><img src="image/logo1.png" alt="LOGO"></a>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="error-message">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <div class="container">
        <p class="title">Sign In</p>
        <br>
        <form method="POST" action="login.php">
            <div class="input-box">
                <label>Enter Your Email or Phone Number</label>
                <input type="text" name="email_or_phone" placeholder="example@gmail.com" required>
            </div>

            <div class="input-box">
                <label>Enter Your Password</label>
                <div class="password-container">
                    <input type="password" name="password" id="password" placeholder="************" required>
                    <i class="fas fa-eye" id="togglePassword"></i>
                </div>
            </div>

            <button type="submit" class="sign-in-btn">Sign In</button>
        </form>

        <p class="links"><a href="forgot.php">Forgot Your Password?</a></p>
        <p class="links">Don’t have an account? <a href="signup.php" class="bld" style="color: black; font-size: 18px;">Sign Up</a></p>
    </div>
    <br>
    <p class="footer">T&C © 2025 Chai Junction. All rights reserved.</p>
    <script>
        // Password Visibility Toggle
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = password.type === 'password' ? 'text' : 'password';
            password.type = type;
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>

</html>