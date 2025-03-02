<?php
session_start();
require '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminLogin.php");
    exit();
}

// Fetch current admin details
$admin_id = $_SESSION['admin_id'];
$stmt = $conn->prepare("SELECT name, phone, email FROM admin WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;

    // Update query
    if ($password) {
        $update_stmt = $conn->prepare("UPDATE admin SET name = ?, phone = ?, email = ?, password = ? WHERE id = ?");
        $update_stmt->bind_param("ssssi", $name, $phone, $email, $password, $admin_id);
    } else {
        $update_stmt = $conn->prepare("UPDATE admin SET name = ?, phone = ?, email = ? WHERE id = ?");
        $update_stmt->bind_param("sssi", $name, $phone, $email, $admin_id);
    }
    $update_stmt->execute();
    header("Location: settings.php?success=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Admin Settings</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="../css/admindash.css">
    <style>
        body {
            background: linear-gradient(135deg, #f0e6f6 0%, #e6f0fa 100%);
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            /* display: flex; */
            justify-content: center;
            align-items: center;
        }

        .container {
            margin: 0;
            padding: 0;
            margin: 0 auto;
            margin-top: 40px;
            width: 96%;
        }

        .card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid #e0e0e0;
        }

        .card-header {
            background: linear-gradient(90deg, #d4a5e6 0%, #89c4f4 100%);
            color: #fff;
            padding: 15px 20px;
            font-size: 20px;
            font-weight: 500;
            text-align: center;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        }

        .card-body {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            font-size: 16px;
            color: #666;
            font-weight: 500;
            display: block;
            margin-bottom: 5px;
            text-align: left;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            color: #555;
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #89c4f4;
            box-shadow: 0 0 8px rgba(137, 196, 244, 0.3);
        }

        .form-group input::placeholder {
            color: #bbb;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(90deg, #a9dfbf 0%, #89c4f4 100%);
            color: #333;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: opacity 0.3s ease;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .success-message {
            text-align: center;
            color:rgb(0, 122, 49);
            font-size: 14px;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <header>
        <div class="logo">
            <img src="../image/logo1.png" alt="Logo">
        </div>
        <div class="nav">
            <a href="admindash.php"><i class="fa-solid fa-arrow-left"></i></a>
        </div>
    </header>
    <br>
    <div class="container">
        <div class="card">
            <div class="card-header">Personal Settings</div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($admin['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($admin['phone']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>New Password (optional)</label>
                        <input type="password" name="password" placeholder="Leave blank to keep current password">
                    </div>
                    <button type="submit" class="btn">Update Settings</button>
                </form>
                <?php if (isset($_GET['success'])) { ?>
                    <p class="success-message">Settings updated successfully! Redirecting in 3 seconds...</p>
                    <script>
                        setTimeout(function() {
                            window.location.href = 'settings.php';
                        }, 3000); // Redirect after 3 seconds
                    </script>
                <?php } ?>
            </div>
        </div>
    </div>
</body>

</html>