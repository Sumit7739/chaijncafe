<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); // Adjusted to go up one level
    exit();
}

include('../config.php'); // Your database connection file

$userId = $_SESSION['user_id'];

// Fetch user info from the database
$sql = "SELECT name, user_id, profile_pic, qrimage, points_balance, card_level_id FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $name = $user['name'] ?? 'User Name';
    $userIdDisplay = $user['user_id'];
    $pointsBalance = $user['points_balance'] ?? 0; // Default to 0 if null
    $cardLevelId = $user['card_level_id'] ?? "Null"; // Default to 0 if null
    $profilePic = $user['profile_pic'] ?? '/profile/default.png';
    // Adjust qrimage path to be relative to userd/
    $qrImageRaw = $user['qrimage'] ?? 'qrcodes/default.png'; // Default if null
    $qrImage = str_replace('userd/', '', $qrImageRaw); // Remove 'userd/' prefix
} else {
    $name = 'User Name';
    $userIdDisplay = $userId;
    $profilePic = '../image/user.png';
    $qrImage = 'qrcodes/default.png'; // Adjusted default
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Page</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>
    <header>
        <div class="logo">
            <img src="../image/logo1.png" alt="Logo">
        </div>
        <div class="nav">
            <i class="fa-solid fa-ellipsis-vertical menu-icon" onclick="toggleOverlay()"></i>
        </div>
    </header>
    <!-- Overlay Popup -->
    <div class="overlay" id="overlay">
        <i class="fa-solid fa-xmark close-btn" onclick="toggleOverlay()"></i>
        <div class="overlay-content">
            <ul class="menu-list">
                <li>About <i class="fa-solid fa-chevron-right"></i></li>
                <li>Help & Support <i class="fa-solid fa-chevron-right"></i></li>
                <li>Leaderboard <i class="fa-solid fa-chevron-right"></i></li>
                <li>Terms and Conditions <i class="fa-solid fa-chevron-right"></i></li>
                <li>Referral Hub <i class="fa-solid fa-chevron-right"></i></li>
                <li>Rewards & Offers <i class="fa-solid fa-chevron-right"></i></li>
            </ul>
            <a href="../logout.php" class="logout-btn">Log Out</a>
        </div>
    </div>
    <section>
        <div class="user">
            <div class="container">
                <div class="profile">
                    <div class="profile-image">
                        <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="User">
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($name); ?></h2>
                        <h3>User Id: <?php echo htmlspecialchars($userIdDisplay); ?></h3>
                    </div>
                </div>
            </div>
            <div class="inffo">
                <div class="btn1">
                    <p>Total Points - <?php echo htmlspecialchars($pointsBalance); ?></p>
                </div>
                <div class="btn2">
                    <p>Card:- <span><?php echo htmlspecialchars($cardLevelId);?></span></p>
                </div>
                <div class="btn3">
                    <div class="wrapper">
                        <button id="qrButton"><i class="fa-solid fa-qrcode"></i></button>
                    </div>
                </div>
            </div>
            <p class="exp">Points expires in 8 days</p>
        </div>
    </section>
    <!-- Overlay & QR Code Popup -->
    <div id="qrPopup" class="qr-overlay">
        <div class="qr-popup">
            <h2>Scan the <i>QR</i> on the Scanner</h2>
            <img src="<?php echo htmlspecialchars($qrImage); ?>" alt="QR Code">
            <button id="closeQrPopup">Close</button>
        </div>
    </div>
    <br>
    <hr>
    <section class="sec2">
        <div class="section">
            Recent Activity
            <div class="text">
                <p>You earned 12 points from your last purchase on 19-02-25.</p>
            </div>
        </div>
        <hr>
        <div class="section">
            Referral Bonus Status
            <div class="text">
                <p>You referred 2 people! Earn 10 more points when they make their first purchase.</p>
            </div>
        </div>
        <hr>
        <div class="section">
            Progress Tracker for Next Tier
            <div class="text text2">
                <p>₹750 more to upgrade to Silver Card!</p>
                <div class="progress-bar">
                    <div class="progress"></div>
                </div>
            </div>
        </div>
        <hr>
        <div class="section">
            Daily / Weekly Challenges
            <div class="text text2">
                <p>Buy 3 teas this week & earn bonus points! (2/3 completed)</p>
                <div class="progress-bar">
                    <div class="progress" style="width: 66%;"></div>
                </div>
            </div>
        </div>
        <hr>
    </section>
    <section class="sec3">
        <div class="navbar">
            <a href="#"><i class="fa-solid fa-money-bill-transfer"></i></a>
            <a href="#"><i class="fa-solid fa-hand-holding-dollar"></i></a>
            <a href="profile.php"><i class="fa-solid fa-house active"></i></a>
            <a href="#" id="bell-icon"><i class="fa-solid fa-bell"></i></a>
            <a href="#"><i class="fa-solid fa-gear"></i></a>
        </div>
    </section>
    <div class="notification-popup" id="notificationPopup">
        <div class="notif-header">
            <h2>Notifications</h2>
            <a href="notification.html"><i class="fa-solid fa-up-right-and-down-left-from-center"></i></a>
        </div>
        <div class="notif">
            <div class="notification-item">🔔 New message received</div>
            <div class="notification-item">🔔 New message received</div>
            <div class="notification-item">🔔 New message received</div>
        </div>
    </div>

    <script src="../js/profile.js"></script>
</body>

</html>