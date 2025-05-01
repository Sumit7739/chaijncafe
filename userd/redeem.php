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


// Fetch total redeemed points
$sql = "SELECT SUM(points_redeemed) AS total_redeemed 
        FROM redeem 
        WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$totalRedeemed = $result->num_rows === 1 ? ($result->fetch_assoc()['total_redeemed'] ?? 0) : 0;


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

// Aggregate points by day of the week
$daysOfWeek = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
$pointsByDay = array_fill_keys($daysOfWeek, 0);

// After fetching $pointsByDay, calculate the maximum points for scaling
$maxPoints = max($pointsByDay) ?: 1; // Avoid division by zero
$pointsJson = json_encode($pointsByDay);
$maxPointsJson = json_encode($maxPoints);

foreach ($redeems as $redeem) {
    $redeemDate = new DateTime($redeem['date_redeemed']);
    $dayOfWeek = strtolower(date('D', $redeemDate->getTimestamp())); // e.g., 'sun', 'mon'
    $pointsByDay[$dayOfWeek] += $redeem['points_redeemed'];
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
        <div class="redeem-balance">Reddemed Points: <?php echo htmlspecialchars($totalRedeemed); ?> pts</div>
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