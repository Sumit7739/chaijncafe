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
$sql = "SELECT COUNT(*) AS total FROM transactions WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$totalTransactions = $result->fetch_assoc()['total'];
$totalPages = ceil($totalTransactions / $limit);

// Fetch transactions
$sql = "SELECT points_given, amount_paid, transaction_date 
        FROM transactions 
        WHERE user_id = ? 
        ORDER BY transaction_date DESC 
        LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $userId, $offset, $limit);
$stmt->execute();
$result = $stmt->get_result();
$transactions = $result->fetch_all(MYSQLI_ASSOC);

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
    <title>Transactions</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="../css/admindash.css">
    <link rel="stylesheet" href="css/transaction.css">
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
    <h2>Transactions</h2>

    <div class="transaction-list">
        <div class="transaction-header">
            <div class="points">Points</div>
            <div class="amount">Amount</div>
            <div class="date">Date</div>
        </div>
        <?php if (count($transactions) > 0): ?>
            <?php foreach ($transactions as $transaction): ?>
                <div class="transaction-row">
                    <div class="points"><?php echo htmlspecialchars($transaction['points_given']); ?> pts</div>
                    <div class="amount">₹<?php echo number_format($transaction['amount_paid'], 2); ?></div>
                    <div class="date"><?php echo date('d-m-y', strtotime($transaction['transaction_date'])); ?></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="transaction-row">
                <div class="points" style="flex: 3; text-align: center;">No transactions found.</div>
            </div>
        <?php endif; ?>
    </div>

    <section class="sec3">
        <div class="navbar">
            <a href="transactions.php"><i class="fa-solid fa-money-bill-transfer active"></i></a>
            <a href="redeem.php"><i class="fa-solid fa-hand-holding-dollar"></i></a>
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
            <a href="transactions.php?page=<?php echo $page - 1; ?>">« Prev</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="transactions.php?page=<?php echo $i; ?>" class="<?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <a href="transactions.php?page=<?php echo $page + 1; ?>">Next »</a>
        <?php endif; ?>
    </div>
    <br><br>
</body>

</html>