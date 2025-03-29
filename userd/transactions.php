<?php
session_start();
include('../config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$userId = $_SESSION['user_id'];

$sql = "Select total_points, points_balance from users where user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
if (!$user) {
    echo "User not found.";
    exit();
}
$totalPoints = $user['total_points'];
$pointsBalance = $user['points_balance'];


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

// Aggregate points by day of the week
$daysOfWeek = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
$pointsByDay = array_fill_keys($daysOfWeek, 0);

// After fetching $pointsByDay, calculate the maximum points for scaling
$maxPoints = max($pointsByDay) ?: 1; // Avoid division by zero
$pointsJson = json_encode($pointsByDay);
$maxPointsJson = json_encode($maxPoints);

foreach ($transactions as $transaction) {
    $transactionDate = new DateTime($transaction['transaction_date']);
    $dayOfWeek = strtolower(date('D', $transactionDate->getTimestamp())); // e.g., 'sun', 'mon'
    $pointsByDay[$dayOfWeek] += $transaction['points_given'];
}


$stmt->close();
$conn->close();
// Define colors for each day
$dayColors = [
    'sun' => '#FF5733',  // Orange-red for Sunday
    'mon' => '#33A1FF',  // Blue for Monday
    'tue' => '#33FF57',  // Green for Tuesday
    'wed' => '#FF33A1',  // Pink for Wednesday
    'thu' => '#A133FF',  // Purple for Thursday
    'fri' => '#FFD733',  // Yellow for Friday
    'sat' => '#33FFD7'   // Teal for Saturday
];
$dayColorsJson = json_encode($dayColors);
$pointsJson = json_encode($pointsByDay);
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
    <link rel="stylesheet" href="css/profile.css">
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
    </header>
    <br>
    <div class="box">
        <div class="month-name" id="currentMonth">
            <?php
            $monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            echo $monthNames[date('n') - 1];
            ?> 2025
        </div>
        <div class="point-balance">
            <div class="point">Total Points: <?php echo htmlspecialchars($totalPoints); ?> pts</div>
            <div class="balance">Current Balance: <?php echo htmlspecialchars($pointsBalance); ?> pts</div>
        </div>
        <!-- <br> -->
        <div class="chart-container">
            <div class="bar-container">
                <?php
                $days = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
                foreach ($days as $day):
                ?>
                    <div class="bar-item">
                        <div class="bar" id="<?php echo $day; ?>-bar">
                            <div class="bar-fill" id="<?php echo $day; ?>-fill"></div>
                        </div>
                        <div class="bar-label"><?php echo ucfirst($day); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

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
            <a href="notification.php"><i class="fa-solid fa-bell"></i></a>
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

    <!-- <script>
        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        const currentDate = new Date();
        document.getElementById("currentMonth").textContent = monthNames[currentDate.getMonth()];
    </script> -->

    <script>
        // Parse PHP data into JavaScript
        const pointsByDay = <?php echo $pointsJson; ?>;
        const maxPoints = <?php echo $maxPointsJson; ?> || 1;
        const dayColors = <?php echo $dayColorsJson; ?>;

        // Bar configuration
        const maxBarHeight = 50; // Matches your CSS height
        const minBarHeight = 5; // Minimum height for visibility

        // Update all bars
        Object.entries(pointsByDay).forEach(([day, points]) => {
            const fillElement = document.getElementById(`${day}-fill`);

            // Calculate height proportionally
            let height = minBarHeight;
            if (points > 0) {
                height = Math.max(minBarHeight, (points / maxPoints) * maxBarHeight);
            }

            // Set the height
            fillElement.style.height = `${height}px`;

            // Set the day-specific color
            fillElement.style.backgroundColor = dayColors[day];

            // Add slight transparency to bars with no points
            if (points === 0) {
                fillElement.style.opacity = '0.3';
            } else {
                fillElement.style.opacity = '1';
            }
        });
    </script>
</body>

</html>