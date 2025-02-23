<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    echo "Session ID not set, redirecting...";
    header("Location: login.php");
    exit();
}

include('config.php'); // Your database connection file
require_once('phpqrcode/qrlib.php'); // Path to phpqrcode library

$userId = $_SESSION['user_id'];

// Check if QR code exists
$sql = "SELECT qrimage FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$qrImage = $row['qrimage'];

if (empty($qrImage) || !file_exists($qrImage)) {
    // Generate QR code if it doesn’t exist or the file is missing
    $qrData = "user:" . $userId; // Content of the QR code
    $qrDir = 'userd/qrcodes/'; // Directory to store QR codes
    $qrFile = $qrDir . $userId . '.png';
    QRcode::png($qrData, $qrFile); // Generate QR code as PNG

    // Update database with QR code path
    $updateSql = "UPDATE users SET qrimage = ? WHERE user_id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("si", $qrFile, $userId);
    $updateStmt->execute();
    $updateStmt->close();
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <link rel="stylesheet" href="style.css">
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
            width: 300px;
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
        <h2>Welcome!</h2>
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
                        window.location.href = 'userd/profile.php'; // Adjust if profile.php is needed
                    }, 500);
                } else {
                    width += 2;
                    progressBar.style.width = width + "%";
                }
            }, 40); // Fills in 2 seconds
        }

        fillProgressBar();
    </script>
</body>
</html>