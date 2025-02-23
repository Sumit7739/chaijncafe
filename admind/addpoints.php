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
    header("Location: adminLogin.php"); // Redirect to login if not logged in
    exit();
}

// Fetch admin details
$admin_id = $_SESSION['admin_id'];

// Check if user ID is provided
if (isset($_GET['user_id'])) {
    $userId = intval($_GET['user_id']);

    // Fetch user details
    $stmt = $conn->prepare("SELECT name, user_id, points_balance, card_level_id, amount_spent, created_at FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $showSearch = false;
    } else {
        echo "<script>alert('User not found'); window.location.href='addpoints.php';</script>";
        exit();
    }

    // Fetch transactions for the user (latest first)
    $stmt = $conn->prepare("SELECT amount_paid, points_given, DATE_FORMAT(transaction_date, '%d-%m-%y') AS formatted_date 
     FROM transactions 
     WHERE user_id = ? 
     ORDER BY transaction_date DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
}

// Process Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['amount'])) {
    $amount = floatval($_POST['amount']);

    if ($amount > 0) {
        // Fetch points conversion
        $stmt = $conn->prepare("SELECT points_awarded FROM conversion_rules WHERE min_amount <= ? ORDER BY min_amount DESC LIMIT 1");
        $stmt->bind_param("d", $amount);
        $stmt->execute();
        $result = $stmt->get_result();
        $conversion = $result->fetch_assoc();

        if ($conversion) {
            $pointsEarned = $conversion['points_awarded'];

            // Apply perks multiplier based on card level
            $stmt = $conn->prepare("SELECT perks FROM card_levels WHERE card_id = ?");
            $stmt->bind_param("i", $user['card_level_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $cardLevel = $result->fetch_assoc();

            if ($cardLevel) {
                $pointsEarned *= $cardLevel['perks'];
            }

            // Update user points and amount spent
            $newPointsBalance = $user['points_balance'] + $pointsEarned;
            $newAmountSpent = $user['amount_spent'] + $amount;

            $stmt = $conn->prepare("UPDATE users SET points_balance = ?, amount_spent = ? WHERE user_id = ?");
            $stmt->bind_param("dii", $newPointsBalance, $newAmountSpent, $userId);
            $stmt->execute();

            // Insert transaction log
            $stmt = $conn->prepare("INSERT INTO transactions (user_id, admin_id, amount_paid, points_given, transaction_date) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("iidi", $userId, $_SESSION['admin_id'], $amount, $pointsEarned);
            $stmt->execute();

            // Ensure the user has a default card level if it's NULL
            if ($user['card_level_id'] === null) {
                $stmt = $conn->prepare("SELECT card_id FROM card_levels ORDER BY amount_spent ASC LIMIT 1");
                $stmt->execute();
                $result = $stmt->get_result();
                $defaultCardLevel = $result->fetch_assoc();

                if ($defaultCardLevel) {
                    $stmt = $conn->prepare("UPDATE users SET card_level_id = ? WHERE user_id = ?");
                    $stmt->bind_param("ii", $defaultCardLevel['card_id'], $userId);
                    $stmt->execute();
                    $user['card_level_id'] = $defaultCardLevel['card_id']; // Update local variable to avoid redundant queries
                }
            }

            // Check for card upgrade after updating amount spent
            $stmt = $conn->prepare("SELECT card_id FROM card_levels WHERE amount_spent <= ? ORDER BY amount_spent DESC LIMIT 1");
            $stmt->bind_param("d", $newAmountSpent);
            $stmt->execute();
            $result = $stmt->get_result();
            $updatedCardLevel = $result->fetch_assoc();

            if ($updatedCardLevel && $updatedCardLevel['card_id'] != $user['card_level_id']) {
                $stmt = $conn->prepare("UPDATE users SET card_level_id = ? WHERE user_id = ?");
                $stmt->bind_param("ii", $updatedCardLevel['card_id'], $userId);
                $stmt->execute();
            }



            // Insert admin log
            $adminId = 1; // Change to actual logged-in admin ID
            $action = "Added Points";
            $tableName = "users"; // The affected table
            $recordId = $userId;
            $message = "Added $points points to user ID $userId"; // Custom message

            $stmt = $conn->prepare("INSERT INTO audit_logs (admin_id, action, table_name, record_id, message, created_at) 
                        VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("issis", $adminId, $action, $tableName, $recordId, $message);
            $stmt->execute();

            // Refresh page to show updated values
            header("Location: addpoints.php?user_id=$userId");
            exit();
        }
    } else {
        echo "<script>alert('Invalid amount!');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Points</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="../css/admindash.css">
    <link rel="stylesheet" href="../css/adminoverview.css">
    <link rel="stylesheet" href="../css/addpoints.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        .add-points {
            min-width: 100px;
            max-width: 110px;
        }


        .profile-info p {
            font-size: 18px;
        }

        .profile-info small {
            font-size: 16px;
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

    <?php if ($showSearch): ?>
        <section>
            <div class="searchbox">
                <div class="container">
                    <input type="text" placeholder="Search by Name, User ID, Email, Phone Number..">
                    <button class="btn4"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
                </div>
            </div>
        </section>
    <?php endif; ?>
    <br>
    <?php if ($user): ?>
        <section>
            <div class="box">
                <form method="POST">
                    <div class="amount-container">
                        <input type="number" name="amount" id="amount-input" placeholder="Enter amount" required>
                        <button type="submit" class="add-points">Add</button>
                    </div>
                    <div class="amount-options">
                        <button type="button" class="amount-btn" data-amount="1">₹1</button>
                        <button type="button" class="amount-btn" data-amount="2">₹2</button>
                        <button type="button" class="amount-btn" data-amount="5">₹5</button>
                        <button type="button" class="amount-btn" data-amount="10">₹10</button>
                        <button type="button" class="amount-btn" data-amount="20">₹20</button>
                        <button type="button" class="amount-btn" data-amount="50">₹50</button>
                        <button type="button" class="amount-btn" data-amount="100">₹100</button>
                        <button type="button" id="clear-btn" class="amount-btn">Clear</button>
                    </div>
                </form>
            </div>

            <div class="user-card">
                <h2 style="font-weight: 500; margin-bottom: 10px;">User Details</h2>
                <div class="profile">
                    <img src="../image/user.png" alt="User Avatar">
                    <div class="profile-info">
                        <p><?php echo htmlspecialchars($user['name']); ?></p>
                        <small>ID: <?php echo htmlspecialchars($user['user_id']); ?></small>
                    </div>
                </div>

                <div class="stats">
                    <div>Total Points: <?php echo htmlspecialchars($user['points_balance']); ?></div>
                    <div>Card Tier: <span class="card-tier"><?php echo htmlspecialchars($user['card_level_id'] ?? "Null"); ?></span></div>
                </div>

                <div class="stats stats2">
                    <div>Total Amount Spent: ₹<?php echo htmlspecialchars($user['amount_spent']); ?></div>
                    <div>Amount Needed for Next Tier: ₹<?php echo max(0, 2000 - $user['amount_spent']); ?></div>
                    <div>Joined on: <?php echo date("d-m-Y", strtotime($user['created_at'])); ?></div>
                </div>
            </div>
        </section>

        <div class="container dashboard2">
            <div class="title">Recent Transactions</div>

            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="transaction">
                        <div class="amount">₹<?php echo htmlspecialchars($row['amount_paid']); ?></div>
                        <div class="points">+<?php echo htmlspecialchars($row['points_given']); ?> pts</div>
                        <div class="date"><?php echo htmlspecialchars($row['formatted_date']); ?></div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-transactions">No transactions found.</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <script src="../js/profile.js"></script>
    <script src="../js/addpoints.js"></script>
</body>

</html>