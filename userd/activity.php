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

// Filter setup
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all'; // 'all', 'earned', 'redeemed'
$whereClause = "";
if ($filter === 'earned') {
    $whereClause = " AND type = 'points_update'";
} elseif ($filter === 'redeemed') {
    $whereClause = " AND type = 'redeem'";
}

// Fetch total count for pagination
$sql = "SELECT COUNT(*) AS total 
        FROM notifications 
        WHERE user_id = ? AND type IN ('points_update', 'redeem') $whereClause";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$totalActivities = $result->fetch_assoc()['total'];
$totalPages = ceil($totalActivities / $limit);

// Fetch activities
$sql = "SELECT message, type, created_at 
        FROM notifications 
        WHERE user_id = ? AND type IN ('points_update', 'redeem') $whereClause 
        ORDER BY created_at DESC 
        LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $userId, $offset, $limit);
$stmt->execute();
$result = $stmt->get_result();
$activities = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Activity</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="../css/admindash.css">
    <link rel="stylesheet" href="css/activity.css">
    <link rel="stylesheet" href="css/profile.css">
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
    <h2>All Activity</h2>
    <div class="filter-buttons">
        <button class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>" onclick="window.location.href='activity.php?filter=all<?php echo $page > 1 ? "&page=$page" : ""; ?>'">All</button>
        <button class="filter-btn <?php echo $filter === 'earned' ? 'active' : ''; ?>" onclick="window.location.href='activity.php?filter=earned<?php echo $page > 1 ? "&page=$page" : ""; ?>'">Earned</button>
        <button class="filter-btn <?php echo $filter === 'redeemed' ? 'active' : ''; ?>" onclick="window.location.href='activity.php?filter=redeemed<?php echo $page > 1 ? "&page=$page" : ""; ?>'">Redeemed</button>
    </div>

    <div class="cont">
        <div class="activity-list">
            <?php if (count($activities) > 0): ?>
                <?php foreach ($activities as $activity): ?>
                    <div class="activity-card <?php echo $activity['type'] === 'points_update' ? 'earned' : 'redeemed'; ?>">
                        <?php echo htmlspecialchars($activity['message']); ?>
                        <div class="date"><?php echo date('d-m-y H:i', strtotime($activity['created_at'])); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="activity-card">No activities found.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="activity.php?filter=<?php echo $filter; ?>&page=<?php echo $page - 1; ?>">&laquo; Prev</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="activity.php?filter=<?php echo $filter; ?>&page=<?php echo $i; ?>" class="<?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <a href="activity.php?filter=<?php echo $filter; ?>&page=<?php echo $page + 1; ?>">Next &raquo;</a>
        <?php endif; ?>
    </div>
    <br>
    <br>
    <br>
    <br>
    <br>
</body>

</html>