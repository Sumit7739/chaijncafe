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
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            color: #333;
        }

        /* Logo styling */
        .logo {
            margin-bottom: 30px;
            text-align: center;
            animation: fadeInDown 0.8s ease-out;
        }

        .logo img {
            max-height: 80px;
            transition: transform 0.3s ease;
        }

        .logo img:hover {
            transform: scale(1.05);
        }

        /* Error message styling */
        .error-message {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
            font-weight: 500;
            text-align: center;
            animation: shake 0.5s ease-in-out;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* Container styling */
        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            animation: fadeInUp 0.8s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Title styling */
        .title {
            font-size: 28px;
            font-weight: bold;
            text-align: center;
            color: #333;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Input box styling */
        .input-box {
            margin-bottom: 25px;
        }

        .input-box label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-box input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .input-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: white;
        }

        /* Password container styling */
        .password-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-container input {
            width: 100%;
            padding-right: 45px;
        }

        .password-container i {
            position: absolute;
            right: 15px;
            cursor: pointer;
            color: #666;
            font-size: 16px;
            transition: color 0.3s ease;
        }

        .password-container i:hover {
            color: #667eea;
        }

        /* Button styling */
        .sign-in-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }

        .sign-in-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .sign-in-btn:active {
            transform: translateY(0);
        }

        /* Links styling */
        .links {
            text-align: center;
            margin: 10px 0;
            font-size: 14px;
        }

        .links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .links a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .links .bld {
            font-weight: 700 !important;
            color: #333 !important;
            font-size: 16px !important;
        }

        .links .bld:hover {
            color: #667eea !important;
        }

        /* Footer styling */
        .footer {
            margin-top: 30px;
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
            font-size: 12px;
            font-weight: 300;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive design */
        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
                margin: 10px;
            }
            
            .title {
                font-size: 24px;
            }
            
            body {
                padding: 10px;
            }
        }

        /* Loading state for form submission */
        .sign-in-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        /* Focus styles for accessibility */
        .sign-in-btn:focus,
        .links a:focus {
            outline: 2px solid #667eea;
            outline-offset: 2px;
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
