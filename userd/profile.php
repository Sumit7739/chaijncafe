<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include('../config.php');

$userId = $_SESSION['user_id'];

// Fetch user info
$sql = "SELECT name, user_id, profile_pic, qrimage, points_balance FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $name = $user['name'] ?? 'User Name';
    $userIdDisplay = $user['user_id'];
    $pointsBalance = $user['points_balance'] ?? 0;
    $profilePic = $user['profile_pic'] ?? '/profile/default.png';
    $qrImage = str_replace('userd/', '', $user['qrimage'] ?? 'qrcodes/default.png');
} else {
    $name = 'User Name';
    $userIdDisplay = $userId;
    $profilePic = '../image/user.png';
    $qrImage = 'qrcodes/default.png';
}

// Set your local timezone (adjust to your region)
date_default_timezone_set('Asia/Kolkata'); // Example—replace with your timezone, e.g., 'America/New_York'

// Verify current date
$today = new DateTime();
$monthEnd = new DateTime('last day of this month');
$interval = $today->diff($monthEnd);
$daysLeft = (int)$interval->days;
$expiryMessage = $daysLeft > 0 ? "Points expire in $daysLeft days" : "Points expire today!";


// Fetch total redeemed points
$sql = "SELECT SUM(points_redeemed) AS total_redeemed 
        FROM redeem 
        WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$totalRedeemed = $result->num_rows === 1 ? ($result->fetch_assoc()['total_redeemed'] ?? 0) : 0;


// Fetch recent activity (top 5 from notifications)
$sql = "SELECT message, created_at, type 
        FROM notifications 
        WHERE user_id = ? AND type IN ('points_update', 'redeem') 
        ORDER BY created_at DESC 
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$activityResult = $stmt->get_result();
$activities = $activityResult->fetch_all(MYSQLI_ASSOC);

// Fetch high-priority announcement
$sql = "SELECT title, message 
        FROM announcements 
        WHERE priority = 'high' AND status = 'active' 
        ORDER BY created_at DESC 
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->execute();
$announcementResult = $stmt->get_result();
$announcement = $announcementResult->num_rows === 1 ? $announcementResult->fetch_assoc() : null;

// Fetch system notifications for popup
$sql = "SELECT message 
        FROM notifications 
        WHERE user_id = ? AND type IN ('points_update', 'redeem', 'system') AND status = 'unread' ORDER BY id DESC
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$notificationResult = $stmt->get_result();
$notifications = $notificationResult->fetch_all(MYSQLI_ASSOC);

// Fetch count of unread notifications for indicator
$sql = "SELECT COUNT(*) AS unread_count 
        FROM notifications 
        WHERE user_id = ? AND status = 'unread'";
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
    <title>Profile Page</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        .menu-list a{
            text-decoration: none;
        }
    </style>
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
    <div id="topNotificationPopup" class="top-notification">
        <span id="notificationMessage"></span>
    </div>
    <div class="overlay" id="overlay">
        <i class="fa-solid fa-xmark close-btn" onclick="toggleOverlay()"></i>
        <div class="overlay-content">
            <ul class="menu-list">
                <a href="about.php">
                    <li>About <i class="fa-solid fa-chevron-right"></i></li>
                </a>
                <li>Help & Support <i class="fa-solid fa-chevron-right"></i></li>
                <li>Leaderboard <i class="fa-solid fa-chevron-right"></i></li>
                <a href="term.php">
                    <li>Terms and Conditions <i class="fa-solid fa-chevron-right"></i></li>
                </a>
                <li>Referral Hub <i class="fa-solid fa-chevron-right"></i></li>
                <li>Rewards & Offers <i class="fa-solid fa-chevron-right"></i></li>
            </ul>
            <a href="logout.php" class="logout-btn">Log Out</a>
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
                    <p>Redeemed - <?php echo htmlspecialchars($totalRedeemed); ?> pts</p>
                </div>
            </div>
            <div class="inf">
                <p class="exp"><?php echo $expiryMessage; ?></p>
                <div class="btn3">
                    <div class="wrapper">
                        <button id="qrButton"><i class="fa-solid fa-qrcode"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div id="qrPopup" class="qr-overlay">
        <div class="qr-popup">
            <h2>Scan the <i>QR</i> on the Scanner</h2>
            <p>Coming Soon</p>
            <img src="<?php echo htmlspecialchars($qrImage); ?>" alt="QR Code">
            <button id="closeQrPopup">Close</button>
        </div>
    </div>
    <hr>
    <section class="sec2">
        <div class="section">
            <div class="activity-container">
                <div class="activity-header">
                    <span>Recent Activity</span>
                    <a href="activity.php" class="expand">See All</a>
                </div>
                <div class="activity-list">
                    <?php if (count($activities) > 0): ?>
                        <?php foreach ($activities as $activity): ?>
                            <div class="activity-item <?php echo $activity['type'] === 'points_update' ? 'earned' : 'redeemed'; ?>">
                                <?php echo htmlspecialchars($activity['message']); ?> - <?php echo date('d-m-y', strtotime($activity['created_at'])); ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="activity-item">No recent activity found.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <hr>
        <!-- <div class="section">
            <div class="referral-container">
                Referral Bonus
                <div class="text">
                    <p>Referral Bonus - Coming Soon</p>
                </div>
            </div>
        </div>
        <hr> -->
        <?php if ($announcement): ?>
            <div class="section">
                <div class="announcement-container">
                    <div class="announcement-header">Announcements</div>
                    <div class="announcement-content">
                        <h3><?php echo htmlspecialchars($announcement['title']); ?></h3>
                        <p><?php echo htmlspecialchars($announcement['message']); ?></p>
                    </div>
                </div>
            </div>
            <hr>
        <?php endif; ?>
    </section>

    <section class="sec3">
        <div class="navbar">
            <a href="transactions.php"><i class="fa-solid fa-money-bill-transfer"></i></a>
            <a href="redeem.php"><i class="fa-solid fa-hand-holding-dollar"></i></a>
            <a href="profile.php"><i class="fa-solid fa-house active"></i></a>
            <a href="#" id="bell-icon" class="bell-wrapper">
                <i class="fa-solid fa-bell"></i>
                <?php if ($unreadCount > 0): ?>
                    <span class="unread-indicator"><?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="settings.php"><i class="fa-solid fa-gear"></i></a>
        </div>
    </section>

    <div class="notification-popup" id="notificationPopup">
        <div class="notif-header">
            <h2>Notifications</h2>
            <a href="notification.php"><i class="fa-solid fa-up-right-and-down-left-from-center"></i></a>
        </div>
        <div class="notif">
            <?php if (count($notifications) > 0): ?>
                <?php foreach ($notifications as $notif): ?>
                    <div class="notification-item">🔔 <?php echo htmlspecialchars($notif['message']); ?></div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="notification-item">No new notifications.</div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <script>
        $(document).ready(function() {
            // Check for new notifications on load
            $.ajax({
                url: 'check_notifications.php',
                method: 'POST',
                data: {
                    user_id: <?php echo $userId; ?>
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.message && response.id) {
                        var $popup = $('#topNotificationPopup');
                        var $message = $('#notificationMessage');

                        // Get stored notification data from localStorage
                        var storedData = localStorage.getItem('lastNotification');
                        var lastShown = storedData ? JSON.parse(storedData) : {
                            id: null,
                            date: null
                        };
                        var currentDate = new Date().toDateString(); // Today's date as string
                        var lastShownDate = lastShown.date ? new Date(lastShown.date).toDateString() : null;
                        var lastShownId = lastShown.id;

                        // Show popup if it's a new notification or a new day
                        if (lastShownId !== response.id || lastShownDate !== currentDate) {
                            $message.text(response.message);
                            $popup.removeClass('earned redeemed');
                            $popup.addClass(response.type === 'points_update' ? 'earned' : 'redeemed');
                            $popup.fadeIn(300);

                            // Auto-hide after 3 seconds
                            setTimeout(function() {
                                $popup.fadeOut(300);
                            }, 3000);

                            // Click to redirect
                            $popup.on('click', function() {
                                window.location.href = 'notification.php';
                            });

                            // Store this notification as shown
                            localStorage.setItem('lastNotification', JSON.stringify({
                                id: response.id,
                                date: new Date().toISOString()
                            }));
                        }
                    }
                }
            });
        });
    </script>
    <script src="../js/profile.js"></script>
</body>

</html>