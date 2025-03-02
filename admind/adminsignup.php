<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require('../config.php');

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (!isset($_POST['terms'])) {
        $error_message = "You must agree to the Terms and Conditions.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } else {
        // Check if admin exists
        $sql = "SELECT * FROM admin WHERE email = ? OR phone = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $email, $phone);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error_message = "User already exists with this email or phone number.";
        } else {
            // Hash password correctly
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'manager'; // Default role

            // Insert admin
            $sql = "INSERT INTO admin (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $name, $email, $phone, $hashed_password, $role);
            if ($stmt->execute()) {
                $_SESSION['admin_id'] = $stmt->insert_id;
                header('Location: success.html');
                exit();
            } else {
                $error_message = "Failed to create admin.";
            }
        }

        $stmt->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chai Junction - Admin Sign Up</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/signup.css">
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
        <a href="../index.html"><img src="../image/logo1.png" alt="LOGO"></a>
    </div>

    <div class="container">
        <p class="title">Admin Sign Up</p>
        <br>
        <?php if (!empty($error_message)): ?>
            <p style="color: red; text-align: center;"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form method="POST" action="adminsignup.php">
            <div class="input-box">
                <label>Enter Your Name</label>
                <input type="text" name="name" placeholder="Your Name" required>
            </div>
            <div class="input-box">
                <label>Enter Your Phone Number</label>
                <input type="text" name="phone" placeholder="1234567890" required>
            </div>
            <div class="input-box">
                <label>Enter Your Email</label>
                <input type="email" name="email" placeholder="example@gmail.com" required>
            </div>
            <div class="input-box">
                <label>Enter Your Password</label>
                <div class="password-container">
                    <input type="password" name="password" id="password" placeholder="*********" required>
                    <i class="fas fa-eye" id="togglePassword"></i>
                </div>
            </div>

            <div class="input-box">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm-password" placeholder="*********" required>
                <p id="password-error" style="color: red; display: none;">Passwords do not match!</p>
            </div>

            <div class="terms">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">I agree to the Terms and Conditions</label>
            </div>
            <br>
            <button type="submit" class="sign-up-btn">Sign Up</button>
        </form>
        <p class="links">Already have an account? <a href="adminLogin.php" class="bld" style="color: black; font-size: 18px;">Sign In</a></p>
    </div>

    <script>
        // Password Visibility Toggle
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = password.type === 'password' ? 'text' : 'password';
            password.type = type;
            this.classList.toggle('fa-eye-slash');
        });

        // Password Match Validation
        document.getElementById('signup-form').addEventListener('submit', function(event) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            const errorText = document.getElementById('password-error');

            if (password !== confirmPassword) {
                event.preventDefault();
                errorText.style.display = 'block';
            } else {
                errorText.style.display = 'none';
            }
        });
    </script>
</body>

</html>