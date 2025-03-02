<?php
session_start();
include('../config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Fetch total count for pagination
$sql = "SELECT COUNT(*) AS total FROM redeem WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$totalRedeems = $result->fetch_assoc()['total'];
$totalPages = ceil($totalRedeems / $limit);

// Fetch redemptions
$sql = "SELECT points_redeemed, date_redeemed 
        FROM redeem 
        WHERE user_id = ? 
        ORDER BY date_redeemed DESC 
        LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $userId, $offset, $limit);
$stmt->execute();
$result = $stmt->get_result();
$redeems = $result->fetch_all(MYSQLI_ASSOC);

// Fetch unread count for navbar (assuming from profile.php context)
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
    <title>Redeem History</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="../css/admindash.css">
    <link rel="stylesheet" href="css/redeem.css">
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
    <h2>Redeem History</h2>

    <div class="redeem-list">
        <div class="redeem-header">
            <div class="points">Points Redeemed</div>
            <div class="date">Date</div>
        </div>
        <?php if (count($redeems) > 0): ?>
            <?php foreach ($redeems as $redeem): ?>
                <div class="redeem-row">
                    <div class="points"><?php echo htmlspecialchars($redeem['points_redeemed']); ?> pts</div>
                    <div class="date"><?php echo date('d-m-y', strtotime($redeem['date_redeemed'])); ?></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="redeem-row">
                <div class="points" style="flex: 2; text-align: center;">No redemptions found.</div>
            </div>
        <?php endif; ?>
    </div>
    <section class="sec3">
        <div class="navbar">
            <a href="transactions.php"><i class="fa-solid fa-money-bill-transfer"></i></a>
            <a href="redeem.php"><i class="fa-solid fa-hand-holding-dollar active"></i></a>
            <a href="profile.php"><i class="fa-solid fa-house"></i></a>
            <a href="notification.php" id="bell-icon" class="bell-wrapper">
                <i class="fa-solid fa-bell"></i>
                <?php if ($unreadCount > 0): ?>
                    <span class="unread-indicator"><?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="settings.php"><i class="fa-solid fa-gear"></i></a>
        </div>
    </section>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="redeem.php?page=<?php echo $page - 1; ?>">« Prev</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="redeem.php?page=<?php echo $i; ?>" class="<?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <a href="redeem.php?page=<?php echo $page + 1; ?>">Next »</a>
        <?php endif; ?>
    </div>
    <br><br>
</body>

</html>