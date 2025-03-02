<?php
session_start();
include('../config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$message = '';

// Fetch user info
$sql = "SELECT user_id, name, email, phone, password FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$currentName = $user['name'] ?? 'User Name';
$currentEmail = $user['email'] ?? '';
$currentPhone = $user['phone'] ?? '';
$currentPasswordHash = $user['password'] ?? ''; // Hashed password from DB

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newName = trim($_POST['name'] ?? '');
    $newEmail = trim($_POST['email'] ?? '');
    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    // Validate inputs
    if (empty($newName)) {
        $message = "Name cannot be empty.";
    } elseif ($currentEmail === '' && !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email.";
    } elseif (!empty($currentPass) && !password_verify($currentPass, $currentPasswordHash)) {
        $message = "Current password is incorrect.";
    } elseif (!empty($newPass) && $newPass !== $confirmPass) {
        $message = "New password and confirmation do not match.";
    } elseif (!empty($newPass) && strlen($newPass) < 6) {
        $message = "New password must be at least 6 characters.";
    } else {
        // Update user data
        $updateFields = [];
        $updateParams = [];
        $types = '';

        if ($newName !== $currentName) {
            $updateFields[] = "name = ?";
            $updateParams[] = $newName;
            $types .= 's';
        }
        if ($currentEmail === '' && $newEmail !== '') {
            $updateFields[] = "email = ?";
            $updateParams[] = $newEmail;
            $types .= 's';
        }
        if (!empty($newPass)) {
            $newPassHash = password_hash($newPass, PASSWORD_DEFAULT);
            $updateFields[] = "password = ?";
            $updateParams[] = $newPassHash;
            $types .= 's';
        }

        if (!empty($updateFields)) {
            $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $updateParams[] = $userId;
            $types .= 'i';
            $stmt->bind_param($types, ...$updateParams);
            if ($stmt->execute()) {
                $message = "Settings updated successfully!";
                // Update current values
                $currentName = $newName;
                if ($currentEmail === '' && $newEmail !== '') {
                    $currentEmail = $newEmail;
                }
            } else {
                $message = "Error updating settings.";
            }
        } else {
            $message = "No changes to save.";
        }
    }
}

// Fetch unread count for navbar
$sql = "SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND status = 'unread'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$unreadCount = $result->fetch_assoc()['unread_count'];

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="../css/admindash.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        /* Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f3ebfc;
            /* Pastel purple */
            min-height: 100vh;
            color: #333;
            padding: 10px;
            padding-bottom: 80px;
            /* Space for navbar */
        }

        .logo img {
            /* height: 40px; */
            transition: transform 0.3s ease;
        }

        .logo img:hover {
            transform: scale(1.1);
        }

        .nav a {
            font-size: 24px;
            color: #8a4af3;
            /* Pastel purple accent */
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .nav a:hover {
            color: #6a38c2;
            /* Darker purple */
        }

        /* Settings Form */
        .settings-container {
            max-width: 600px;
            margin: 20px auto;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            animation: fadeIn 0.5s ease;
        }

        h2 {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            color: #333;
            background: #fafafa;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            border-color: #8a4af3;
            outline: none;
        }

        .form-group input[readonly] {
            background: #f0f0f0;
            color: #888;
        }

        .message {
            text-align: center;
            margin: 10px 0;
            color: #28a745;
            /* Green for success */
        }

        .message.error {
            color: #dc3545;
            /* Red for error */
        }

        .submit-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background: #8a4af3;
            /* Pastel purple */
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        .submit-btn:hover {
            background: #6a38c2;
            /* Darker purple */
            transform: translateY(-2px);
        }

        /* Navbar */
        .sec3 {
            position: fixed;
            bottom: 0;
            width: 100%;
            background: #fff;
            padding: 10px 0;
            box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .navbar {
            display: flex;
            justify-content: space-around;
            align-items: center;
        }

        .navbar a {
            color: #666;
            font-size: 22px;
            position: relative;
            transition: color 0.3s ease, transform 0.3s ease;
        }

        .navbar a.active {
            color: #8a4af3;
            /* Pastel purple */
            transform: scale(1.2);
        }

        .navbar a:hover {
            color: #8a4af3;
            transform: scale(1.1);
        }

        .bell-wrapper {
            position: relative;
        }

        .unread-indicator {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: #fff;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 5px;
            border-radius: 10px;
            min-width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            animation: pulse 1.5s infinite;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
        }

        /* Responsive */
        @media (max-width: 480px) {
            body {
                padding-bottom: 70px;
            }

            h2 {
                font-size: 20px;
            }

            .settings-container {
                padding: 15px;
            }

            .form-group input {
                font-size: 12px;
                padding: 8px;
            }

            .submit-btn {
                font-size: 14px;
                padding: 10px;
            }

            .navbar a {
                font-size: 18px;
            }

            .unread-indicator {
                top: -3px;
                right: -3px;
                min-width: 14px;
                height: 14px;
                font-size: 8px;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="logo">
            <img src="../image/logo1.png" alt="Logo">
        </div>
        <div class="nav">
            <a href="profile.php"><i class="fa-solid fa-arrow-left"></i></a>
        </div>
    </header>
    <br>

    <div class="settings-container">
        <h2>Settings</h2>
        <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : ''; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>User ID</label>
                <input type="text" value="<?php echo htmlspecialchars($userId); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($currentName); ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($currentEmail); ?>" <?php echo $currentEmail ? 'readonly' : ''; ?>>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" value="<?php echo htmlspecialchars($currentPhone); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" placeholder="Enter current password">
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" placeholder="Enter new password">
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" placeholder="Confirm new password">
            </div>
            <button type="submit" class="submit-btn">Save Changes</button>
        </form>
    </div>

    <section class="sec3">
        <div class="navbar">
            <a href="transactions.php"><i class="fa-solid fa-money-bill-transfer"></i></a>
            <a href="redeem.php"><i class="fa-solid fa-hand-holding-dollar"></i></a>
            <a href="profile.php"><i class="fa-solid fa-house"></i></a>
            <a href="notification.php" id="bell-icon" class="bell-wrapper">
                <i class="fa-solid fa-bell"></i>
                <?php if ($unreadCount > 0): ?>
                    <span class="unread-indicator"><?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="settings.php"><i class="fa-solid fa-gear active"></i></a>
        </div>
    </section>
</body>

</html>