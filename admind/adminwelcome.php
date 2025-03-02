<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['admin_id'])) {
    header("Location: adminLogin.php");
    exit();
}

include('../config.php');

$adminId = $_SESSION['admin_id']; // Correct variable usage

// Optional: Fetch admin details
$sql = "SELECT name FROM admin WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body {
            text-align: center;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #eee;
        }

        .welcome-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 350px;
        }

        .welcome-container h2{
            font-size: 28px;
            margin-bottom: 10px;
        }
        .welcome-container p{
            font-size: 20px;
        }

        .progress-bar-container {
            width: 100%;
            background-color: #ddd;
            border-radius: 5px;
            margin-top: 15px;
            height: 10px;
            overflow: hidden;
        }

        .progress-bar {
            width: 0%;
            height: 100%;
            background-color: rgb(255, 123, 0);
            transition: width 0.1s linear;
        }

        #loading-text {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <h2>Welcome, <?= htmlspecialchars($admin['name'] ?? 'Admin') ?>!</h2>
        <p>We are setting up your profile...</p>
        <div class="progress-bar-container">
            <div class="progress-bar" id="progress-bar"></div>
        </div>
        <p id="loading-text">Loading...</p>
    </div>

    <script>
        let progressBar = document.getElementById("progress-bar");
        let loadingText = document.getElementById("loading-text");
        let width = 0;

        function fillProgressBar() {
            let interval = setInterval(() => {
                if (width >= 100) {
                    clearInterval(interval);
                    loadingText.innerText = "Setup Complete!";
                    setTimeout(() => {
                        window.location.href = 'admindash.php';
                    }, 500);
                } else {
                    width += 2;
                    progressBar.style.width = width + "%";
                }
            }, 40);
        }

        fillProgressBar();
    </script>
</body>
</html>
