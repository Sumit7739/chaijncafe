<?php
session_start();
include('../config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Update unread notifications to read
$sql = "UPDATE notifications SET status = 'read' WHERE user_id = ? AND status = 'unread'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Filter setup
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$whereClause = "";
if ($filter === 'earned') {
    $whereClause = " AND type = 'points_update'";
} elseif ($filter === 'redeemed') {
    $whereClause = " AND type = 'redeem'";
}

// Fetch total count for pagination (notifications)
$sql = "SELECT COUNT(*) AS total 
        FROM notifications 
        WHERE user_id = ? AND type IN ('points_update', 'redeem') $whereClause";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$totalNotifications = $result->fetch_assoc()['total'];
$totalPages = ceil($totalNotifications / $limit);

// Fetch notifications
$sql = "SELECT message, type, created_at 
        FROM notifications 
        WHERE user_id = ? AND type IN ('points_update', 'redeem') $whereClause 
        ORDER BY created_at DESC 
        LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $userId, $offset, $limit);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);

// Fetch announcements (normal/low priority)
$sql = "SELECT title, message, priority, created_at 
        FROM announcements 
        WHERE status = 'active' AND priority IN ('high', 'normal', 'low') 
        ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$announcements = $result->fetch_all(MYSQLI_ASSOC);

// Fetch unread count for navbar
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
    <title>Notifications</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="../css/admindash.css">
    <link rel="stylesheet" href="css/notification.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
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

    <section class="sec1">
        <h2>Announcements</h2>
        <div class="announcement-list">
            <?php if (count($announcements) > 0): ?>
                <?php foreach ($announcements as $announcement): ?>
                    <div class="announcement-card">
                        <div class="announcement-content">
                            <h3><?php echo htmlspecialchars($announcement['title']); ?></h3>
                            <p><?php echo htmlspecialchars($announcement['message']); ?></p>
                        </div>
                        <div class="announcement-date"><?php echo date('d-m-y H:i', strtotime($announcement['created_at'])); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="announcement-card">No announcements found.</div>
            <?php endif; ?>
        </div>

        <h2>Notifications</h2>

        <div class="filter-buttons">
            <button class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>" onclick="window.location.href='notification.php?filter=all<?php echo $page > 1 ? "&page=$page" : ""; ?>'">All</button>
            <button class="filter-btn <?php echo $filter === 'earned' ? 'active' : ''; ?>" onclick="window.location.href='notification.php?filter=earned<?php echo $page > 1 ? "&page=$page" : ""; ?>'">Earned</button>
            <button class="filter-btn <?php echo $filter === 'redeemed' ? 'active' : ''; ?>" onclick="window.location.href='notification.php?filter=redeemed<?php echo $page > 1 ? "&page=$page" : ""; ?>'">Redeemed</button>
        </div>

        <div class="notification-list">
            <?php if (count($notifications) > 0): ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-card <?php echo $notification['type'] === 'points_update' ? 'earned' : 'redeemed'; ?>">
                        <div class="notification-content">
                            <p><?php echo htmlspecialchars($notification['message']); ?></p>
                        </div>
                        <div class="notification-date"><?php echo date('d-m-y H:i', strtotime($notification['created_at'])); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="notification-card">No notifications found.</div>
            <?php endif; ?>
        </div>

        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="notification.php?filter=<?php echo $filter; ?>&page=<?php echo $page - 1; ?>">« Prev</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="notification.php?filter=<?php echo $filter; ?>&page=<?php echo $i; ?>" class="<?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="notification.php?filter=<?php echo $filter; ?>&page=<?php echo $page + 1; ?>">Next »</a>
            <?php endif; ?>
        </div>
    </section>
    <br><br>
    <section class="sec3">
        <div class="navbar">
            <a href="transactions.php"><i class="fa-solid fa-money-bill-transfer"></i></a>
            <a href="redeem.php"><i class="fa-solid fa-hand-holding-dollar"></i></a>
            <a href="profile.php"><i class="fa-solid fa-house"></i></a>
            <a href="notification.php" id="bell-icon" class="bell-wrapper">
                <i class="fa-solid fa-bell active"></i>
                <?php if ($unreadCount > 0): ?>
                    <span class="unread-indicator"><?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="settings.php"><i class="fa-solid fa-gear"></i></a>
        </div>
    </section>
</body>

</html>