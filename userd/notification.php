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

// Handle clear all notifications request
if (isset($_POST['clear_all']) && $_POST['clear_all'] == 'true') {
    $sql = "DELETE FROM notifications WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    if ($stmt->execute()) {
        // Success: All notifications deleted
        echo json_encode(['success' => true, 'message' => 'All notifications cleared.']);
        exit();
    } else {
        // Error: Failed to delete notifications
        echo json_encode(['success' => false, 'message' => 'Failed to clear notifications.']);
        exit();
    }
}
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
$sql = "SELECT message, points, type, created_at 
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
        ORDER BY id DESC";
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
    <!-- <link rel="stylesheet" href="css/profile.css"> -->
    <link rel="stylesheet" href="css/notification.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
       
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
    <br>
    <section class="sec1">

        <div class="notif">
            <div class="head">
                <h2>Notifications</h2>
                <button class="btn" id="clearAllBtn">Clear All</button>
            </div>
            <div class="filter-buttons">
                <button class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>" onclick="window.location.href='notification.php?filter=all<?php echo $page > 1 ? "&page=$page" : ""; ?>'">All</button>
                <button class="filter-btn <?php echo $filter === 'earned' ? 'active' : ''; ?>" onclick="window.location.href='notification.php?filter=earned<?php echo $page > 1 ? "&page=$page" : ""; ?>'">Earned</button>
                <button class="filter-btn <?php echo $filter === 'redeemed' ? 'active' : ''; ?>" onclick="window.location.href='notification.php?filter=redeemed<?php echo $page > 1 ? "&page=$page" : ""; ?>'">Redeemed</button>
            </div>
        </div>

        <div class="notification-list" id="notificationList">
            <?php if (count($notifications) > 0): ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-card <?php echo $notification['type'] === 'points_update' ? 'earned' : 'redeemed'; ?>">
                        <div class="notification-content">
                            <p><?php echo htmlspecialchars($notification['message']); ?></p>
                            <div class="notification-date"><?php echo date('d-m-y', strtotime($notification['created_at'])); ?></div>
                        </div>
                        <div class="points-earned">
                            <p><?php echo htmlspecialchars($notification['points']); ?> pts</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="notification-car" style="color: red;">No notifications found.</div>
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
    <div class="announcement-list">
        <h2>Announcements</h2>
        <?php if (count($announcements) > 0): ?>
            <?php foreach ($announcements as $announcement): ?>
                <?php
                $priorityClass = strtolower($announcement['priority']);
                ?>
                <div class="announcement-card <?php echo $priorityClass; ?>">
                    <div class="announcement-content">
                        <h3><?php echo htmlspecialchars($announcement['title']); ?></h3>
                        <p><?php echo htmlspecialchars($announcement['message']); ?></p>
                        <p><?php echo date('d-m-y', strtotime($announcement['created_at'])); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="announcement-card" style="color: red;">No announcements found.</div>
        <?php endif; ?>
    </div>
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
    <div id="toast-container"></div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#clearAllBtn").click(function() {
                $.ajax({
                    url: 'notification.php', // Current file
                    type: 'POST',
                    data: {
                        clear_all: 'true'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Clear the notification list
                            $("#notificationList").empty();
                            $("#notificationList").append('<div class="notification-card">No notifications found.</div>');
                            // Optionally, update the unread count in the navbar
                            $(".unread-indicator").remove();
                            // Show success toast
                            showToast('success', response.message);
                        } else {
                            // Show error toast
                            showToast('error', response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", error);
                        // Show error toast
                        showToast('error', "An error occurred while clearing notifications.");
                    }
                });
            });
        });

        function showToast(type, message) {
            // Create toast element
            var toast = $('<div></div>')
                .addClass('toast')
                .addClass(type)
                .text(message);

            // Append to container
            $('#toast-container').append(toast);

            // Fade in
            toast.hide().fadeIn(300);

            // Auto-hide after 3 seconds
            setTimeout(function() {
                toast.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
    </script>
</body>

</html>