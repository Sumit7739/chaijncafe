<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../config.php'; // Database connection
$user = null;
$showSearch = true;

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminLogin.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

// Check if user ID is provided
if (isset($_GET['user_id'])) {
    $userId = intval($_GET['user_id']);

    // Fetch user details
    $stmt = $conn->prepare("SELECT name, profile_pic, user_id, points_balance, total_points, amount_spent, created_at FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Construct the full URL for the profile picture
        $user['profile_pic'] = $user['profile_pic'] ?? 'profile/default.png';
        if ($user['profile_pic'] !== 'profile/default.png') {
            $user['profile_pic'] = '../userd/profile/uploads/' . $user['profile_pic'];
        } else {
            $user['profile_pic'] = '../userd/' . $user['profile_pic']; // Default image path
        }
        $showSearch = false;
    } else {
        echo "<script>alert('User not found'); window.location.href='redeempoints.php';</script>";
        exit();
    }

    // Fetch total points redeemed for the user
    $stmt = $conn->prepare("SELECT SUM(points_redeemed) AS total_redeemed FROM redeem WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $redeemSumResult = $stmt->get_result();
    $redeemSum = $redeemSumResult->fetch_assoc();
    $totalPointsRedeemed = $redeemSum['total_redeemed'] ?? 0; // Default to 0 if no redemptions

    // Fetch redemption history for the user (latest first)
    $stmt = $conn->prepare("SELECT points_redeemed, DATE_FORMAT(date_redeemed, '%d-%m-%y') AS formatted_date 
                            FROM redeem 
                            WHERE user_id = ? 
                            ORDER BY date_redeemed DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $redeemResult = $stmt->get_result();
}

// Process Redeem Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['redeem_points'])) {
    $redeemPoints = intval($_POST['redeem_points']);

    if ($redeemPoints > 0 && $redeemPoints <= $user['points_balance']) {
        // Deduct points directly from points_balance
        $newPointsBalance = $user['points_balance'] - $redeemPoints;

        $stmt = $conn->prepare("UPDATE users SET points_balance = ? WHERE user_id = ?");
        $stmt->bind_param("ii", $newPointsBalance, $userId);
        $stmt->execute();

        // Insert redemption transaction into redeem table
        $stmt = $conn->prepare("INSERT INTO redeem (user_id, points_redeemed, date_redeemed, admin_id) VALUES (?, ?, NOW(), ?)");
        $stmt->bind_param("iii", $userId, $redeemPoints, $admin_id);
        $stmt->execute();

        // Insert admin log
        $action = "Redeemed Points";
        $tableName = "users";
        $recordId = $userId;
        $message = "Redeemed $redeemPoints points from user ID $userId";

        $stmt = $conn->prepare("INSERT INTO audit_logs (admin_id, action, table_name, record_id, message, created_at) 
                        VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issis", $admin_id, $action, $tableName, $recordId, $message);
        $stmt->execute();

        // Insert notification for the user
        $notificationMessage = "You have redeemed points."; // Generic message without points value
        $notificationType = "redeem";
        $status = "unread"; // Ensure this matches the ENUM values in the database

        // Prepare the SQL statement with the new `points` column
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, points, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");

        // Bind parameters, including the `points` column
        $stmt->bind_param("issss", $userId, $notificationMessage, $notificationType, $redeemPoints, $status);

        // Execute the query
        if (!$stmt->execute()) {
            echo "Error: " . $stmt->error; // Output any errors for debugging
        }
        // Refresh page to show updated values
        header("Location: redeempoints.php?user_id=$userId");
        exit();
    } else {
        echo "<script>alert('Invalid points amount or insufficient balance!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redeem Points</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="../css/admindash.css">
    <link rel="stylesheet" href="../css/adminoverview.css">
    <link rel="stylesheet" href="../css/addpoints.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        .user-card {
            margin-top: 40px;
        }

        .box {
            padding: 0;
        }

        .profile-info p {
            font-size: 18px;
        }

        .profile-info small {
            font-size: 16px;
        }

        .amount-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        #redeem-input {
            width: 300px;
        }

        .amount-container button {
            padding: 5px 10px;
        }

        #profile-pic {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-right: 20px;
        }
    </style>
</head>

<body>
    <header>
        <div class="logo">
            <img src="../image/logo1.png" alt="Logo">
        </div>
        <div class="nav">
            <a href="admindash.php"><i class="fa-solid fa-arrow-left"></i></a>
        </div>
    </header>
    <br>
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
            <a href="logout.html" class="logout-btn">Log Out</a>
        </div>
    </div>

    <?php if ($user): ?>
        <section>
            <div class="user-card">
                <h2 style="font-weight: 500; margin-bottom: 10px;">User Details</h2>
                <div class="profile">
                    <img id="profile-pic" src="<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="User">
                    <div class="profile-info">
                        <p><?php echo htmlspecialchars($user['name']); ?></p>
                        <small>ID: <?php echo htmlspecialchars($user['user_id']); ?></small>
                    </div>
                </div>

                <div class="stats">
                    <div>Total Points: <?php echo htmlspecialchars($user['points_balance']); ?></div>
                </div>

                <div class="stats stats2">
                    <div>Total Points Redeemed: <?php echo htmlspecialchars($totalPointsRedeemed); ?> pts</div>
                    <div>Joined on: <?php echo date("d-m-Y", strtotime($user['created_at'])); ?></div>
                </div>
            </div>

            <div class="box">
                <h2 style="font-weight: 500; margin-top: 10px; margin-bottom: 10px;">Redeem Points</h2>
                <form method="POST">
                    <div class="amount-container">
                        <input type="number" name="redeem_points" id="redeem-input" placeholder="Enter points to redeem" required>
                        <button type="submit" class="redeem-points">Redeem</button>
                    </div>
                </form>
            </div>
        </section>

        <div class="container dashboard2">
            <div class="title">Recent Redemptions</div>

            <?php if ($redeemResult->num_rows > 0): ?>
                <?php while ($row = $redeemResult->fetch_assoc()): ?>
                    <div class="transaction">
                        <div class="points">-<?php echo htmlspecialchars($row['points_redeemed']); ?> pts</div>
                        <div class="date"><?php echo htmlspecialchars($row['formatted_date']); ?></div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-transactions">No redemptions found.</div>
            <?php endif; ?>
        </div>
        <br>
    <?php endif; ?>

    <script src="../js/profile.js"></script>
</body>

</html>