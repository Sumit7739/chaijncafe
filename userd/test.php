<?php
session_start();
include('../config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch user details
$sql = "SELECT total_points, points_balance FROM users WHERE user_id = ?";
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

// Fetch transactions for the current month
$currentMonth = date('n'); // Numeric month (1-12)
$currentYear = date('Y');

// Calculate the start and end dates for the current month
$startDate = date('Y-m-d', strtotime("$currentYear-$currentMonth-01"));
$endDate = date('Y-m-t', strtotime("$currentYear-$currentMonth-01"));

// SQL query to fetch transactions for the current month
$sql = "SELECT points_given, transaction_date 
        FROM transactions 
        WHERE user_id = ? AND transaction_date BETWEEN ? AND ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $userId, $startDate, $endDate);
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

// Close statements and connection
$stmt->close();
$conn->close();

// Convert pointsByDay to JSON for JavaScript
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
    <style>
        /* Chart Container */
        .chart-container {
            background-color: #333;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        /* Bar Styles */
        .bar-container {
            display: flex;
            /* justify-content: space-between; */
            margin: 0;
            padding: 0;
            gap: 25px;
            width: 100%;
            max-width: 350px;
            margin: 0 auto;
        }

        .bar-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: calc(100% / 7 - 5px);
            /* Divide into 7 equal parts */
        }

        .bar {
            position: relative;
            width: 100%;
            height: 50px;
            /* Set a fixed height for the container */
            background-color: #ddd;
            border-radius: 5px;
            overflow: hidden;
            display: flex;
            align-items: flex-end;
            /* Align bars to the bottom */
        }

        .bar-fill {
            width: 100%;
            transition: height 0.3s ease;
        }

        /* Color Coding */
        .bar-fill.low {
            background-color: #ADD8E6;
        }

        /* Light blue */
        .bar-fill.medium {
            background-color: #FFC0CB;
        }

        /* Pink */
        .bar-fill.high {
            background-color: #4CAF50;
        }

        /* Green */

        .bar-label {
            font-size: 12px;
            color: white;
            margin-top: 5px;
            text-align: center;
        }
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

    <div class="box">
        <div class="month-name" id="currentMonth">
            <?php
            $monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            echo $monthNames[date('n') - 1] . ' ' . date('Y');
            ?>
        </div>
        <div class="point-balance">
            <div class="point">Total Points: <?php echo htmlspecialchars($totalPoints); ?> pts</div>
            <div class="balance">Current Balance: <?php echo htmlspecialchars($pointsBalance); ?> pts</div>
        </div>
    </div>

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

    <!-- Transaction Details Section -->
    <!-- <div class="transaction-details">
        <?php foreach ($pointsByDay as $dayOfWeek => $data): ?>
            <div class="transaction-day">
                <h3><?php echo strtoupper(substr($dayOfWeek, 0, 3)); ?></h3>
                <ul>
                    <?php foreach ($data['transactions'] as $transaction): ?>
                        <li>
                            <i class="fa-solid fa-arrow-down"></i> You have received.
                            <span style="float: right;"><?php echo $transaction['points_given']; ?> pts</span>
                            <small><?php echo $transaction['transaction_date']; ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div> -->

    <!-- Pagination Section -->
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

    <section class="sec3">
        <div class="navbar">
            <a href="transactions.php"><i class="fa-solid fa-money-bill-transfer active"></i></a>
            <a href="redeem.php"><i class="fa-solid fa-hand-holding-dollar"></i></a>
            <a href="profile.php"><i class="fa-solid fa-house"></i></a>
            <a href="notification.php"><i class="fa-solid fa-bell"></i></a>
            <a href="settings.php"><i class="fa-solid fa-gear"></i></a>
        </div>
    </section>

    <!-- JavaScript for Dynamic Bars -->
    <script>
        // Parse PHP data into JavaScript
        const pointsByDay = <?php echo $pointsJson; ?>;
        const maxPoints = <?php echo $maxPointsJson; ?> || 1;

        // Bar configuration
        const maxBarHeight = 100; // Maximum height in pixels
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

            // Clear all color classes
            fillElement.classList.remove('low', 'medium', 'high');

            // Set appropriate color class
            if (points === 0) {
                fillElement.style.backgroundColor = '#ddd'; // Gray for zero
            } else if (points < maxPoints * 0.33) {
                fillElement.classList.add('low');
            } else if (points < maxPoints * 0.66) {
                fillElement.classList.add('medium');
            } else {
                fillElement.classList.add('high');
            }
        });
    </script>
</body>

</html>