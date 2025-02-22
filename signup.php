<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm-password'];

    if (!isset($_POST['terms'])) {
        $error_message = "You must agree to the Terms and Conditions.";
    } else {
        include('config.php');

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check if the user exists
        $sql = "SELECT * FROM users WHERE email = ? OR phone = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $email, $phone);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error_message = "User already exists with this email or phone number";
        } else {
            // Insert new user
            $sql = "INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $name, $email, $phone, $hashed_password);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $_SESSION['id'] = $stmt->insert_id;
                header('Location: welcome.php');
                exit();
            } else {
                $error_message = "Failed to create user";
            }
        }

        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chai Junction - Sign Up</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/signup.css">
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


    <div class="container">
        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        <br>
        <p class="title">Sign Up</p>
        <br>
        <form id="signup-form" action="signup.php" method="POST">
            <div class="input-box">
                <label>Enter Your Name</label>
                <input type="text" name="name" required>
            </div>

            <div class="input-box">
                <label>Enter Your Phone Number</label>
                <input type="text" name="phone" required maxlength="12">
            </div>

            <div class="input-box">
                <label>Enter Your Email</label>
                <input type="email" name="email" required>
            </div>

            <div class="input-box">
                <label>Enter Your Password</label>
                <div class="password-container">
                    <input type="password" name="password" id="password" required>
                    <i class="fas fa-eye" id="togglePassword"></i>
                </div>
            </div>

            <div class="input-box">
                <label>Confirm Password</label>
                <input type="password" name="confirm-password" id="confirm-password" required>
                <p id="password-error" style="color: red; display: none;">Passwords do not match!</p>
            </div>

            <div class="terms">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms"> I agree to the Terms and Conditions</label>
            </div>

            <button type="submit" class="sign-up-btn">Sign Up</button>
        </form>
        <p class="links">Already have an account? <a href="login.php" class="bld"
                style="color: black; font-size: 18px;">Sign In</a></p>
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