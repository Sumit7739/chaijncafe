<?php
session_start();
require '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminLogin.php");
    exit();
}

// Fetch role from DB
$admin_id = $_SESSION['admin_id'];
$role_query = $conn->prepare("SELECT role FROM admin WHERE id = ?");
$role_query->bind_param("i", $admin_id);
$role_query->execute();
$role_result = $role_query->get_result()->fetch_assoc();
$role = $role_result['role'];

// Get user_id from URL
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($user_id <= 0) {
    die("Invalid user ID.");
}

// Fetch user details
$user_stmt = $conn->prepare("SELECT user_id, name, phone, email, total_points, created_at, 
    (SELECT SUM(points_given) - IFNULL((SELECT SUM(points_redeemed) FROM redeem WHERE user_id = ?), 0) FROM transactions WHERE user_id = ?) as points_balance,
    (SELECT SUM(amount_paid) FROM transactions WHERE user_id = ?) as amount_spent 
    FROM users WHERE user_id = ?");
$user_stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
if (!$user) {
    die("User not found.");
}

// Fetch transactions
$trans_stmt = $conn->prepare("SELECT id, amount_paid, points_given, transaction_date, DATE_FORMAT(transaction_date, '%d-%m-%Y') as formatted_date 
    FROM transactions WHERE user_id = ? ORDER BY transaction_date DESC");
$trans_stmt->bind_param("i", $user_id);
$trans_stmt->execute();
$transactions = $trans_stmt->get_result();

// Fetch redemptions
$redeem_stmt = $conn->prepare("SELECT id, points_redeemed, date_redeemed, DATE_FORMAT(date_redeemed, '%d-%m-%Y') as formatted_date 
    FROM redeem WHERE user_id = ? ORDER BY date_redeemed DESC");
$redeem_stmt->bind_param("i", $user_id);
$redeem_stmt->execute();
$redeems = $redeem_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="../css/admindash.css">
    <link rel="stylesheet" href="../css/addpoints.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #e9ecef 0%, #f9e1e1 100%);
            font-family: 'Roboto', sans-serif;
            color: #555;
            margin: 0;
            padding: 15px;
            min-height: 100vh;
        }

        .container {
            max-width: 100%;
            padding: 0;
        }

        .title {
            font-size: 24px;
            text-align: center;
            color: #89c4f4;
            margin-bottom: 20px;
            font-weight: 500;
            letter-spacing: 1px;
        }

        .card {
            background: #fff;
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(90deg, #b8e1ff 0%, #f4d4fa 100%);
            color: #555;
            padding: 12px 15px;
            font-size: 16px;
            font-weight: 500;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-body {
            padding: 15px;
        }

        .toggle-btn {
            font-size: 12px;
            padding: 5px 10px;
            background: #d4a5e6;
            border: none;
            color: #fff;
            border-radius: 8px;
        }

        .user-card {
            background: #fff;
            padding: 0px;
            border-radius: 12px;
            box-shadow: none;
        }

        .user-card h2 {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }

        .profile {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .profile img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-right: 15px;
            background: #e3f2fd;
            padding: 5px;
        }

        .profile-info p {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #444;
        }

        .profile-info small {
            font-size: 14px;
            color: #666;
        }

        .stats-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .stats,
        .stats2 {
            flex: 1 1 45%;
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            font-size: 16px;
            font-weight: 500;
            color: #555;
            border: 1px solid #ddd;
        }

        .stats span,
        .stats2 span {
            font-weight: 700;
            color: #222;
        }

        .transaction {
            background: #f0f7fa;
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 60px;
            /* Fixed height */
            overflow: hidden;
        }

        .points {
            font-weight: 500;
            color: #555;
        }

        .date {
            font-size: 14px;
            color: #333;
        }

        .scroll-container {
            max-height: 300px;
            overflow-y: auto;
        }

        .toggle-btn {
            width: 100px;
            color: #333;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <header>
        <div class="logo">
            <img src="../image/logo1.png" alt="Logo">
        </div>
        <div class="nav">
            <a href="usermanagement.php"><i class="fa-solid fa-arrow-left"></i></a>
        </div>
    </header>
    <br>
    <div class="container">
        <!-- User Details -->
        <div class="card">
            <div class="card-header">User Details</div>
            <div class="card-body">
                <div class="user-card">
                    <!-- <h2>User Details</h2> -->
                    <div class="profile">
                        <img src="../image/user.png" alt="User Avatar">
                        <div class="profile-info">
                            <p><?php echo htmlspecialchars($user['name']); ?></p>
                            <small>ID: <?php echo htmlspecialchars($user['user_id']); ?></small>
                        </div>
                    </div>
                    <div class="stats">
                        <div>Phone: <?php echo htmlspecialchars($user['phone']); ?></div>
                        <div>Email: <?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    <div class="stats-container">
                        <div class="stats">Total Points: <span><?php echo htmlspecialchars($user['total_points'] ? : 0); ?></span></div>
                        <div class="stats">Current Pts: <span><?php echo htmlspecialchars($user['points_balance'] ? : 0 ) ; ?></span></div>
                        <?php if ($role !== 'manager') { ?>
                            <div class="stats2">Total Spent: ₹<span><?php echo htmlspecialchars($user['amount_spent']); ?></span></div>
                        <?php } ?>
                        <div class="stats2">Joined on: <span><?php echo date("d-m-Y", strtotime($user['created_at'])); ?></span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions -->
        <div class="card">
            <div class="card-header">
                Transactions
                <button class="toggle-btn" onclick="$('#transContainer').slideToggle();">Toggle</button>
            </div>
            <div class="card-body scroll-container" id="transContainer" style="display: none;">
                <?php if ($transactions->num_rows > 0) {
                    while ($row = $transactions->fetch_assoc()) { ?>
                        <div class="transaction">
                            <div class="amount">₹<?php echo htmlspecialchars($row['amount_paid']); ?></div>
                            <div class="points">+<?php echo htmlspecialchars($row['points_given']); ?> pts</div>
                            <div class="date"><?php echo htmlspecialchars($row['formatted_date']); ?></div>
                        </div>
                    <?php }
                } else { ?>
                    <p>No transactions found.</p>
                <?php } ?>
            </div>
        </div>

        <!-- Redemptions -->
        <div class="card">
            <div class="card-header">
                Redemptions
                <button class="toggle-btn" onclick="$('#redeemContainer').slideToggle();">Toggle</button>
            </div>
            <div class="card-body scroll-container" id="redeemContainer" style="display: none;">
                <?php if ($redeems->num_rows > 0) {
                    while ($row = $redeems->fetch_assoc()) { ?>
                        <div class="transaction">
                            <div class="points">-<?php echo htmlspecialchars($row['points_redeemed']); ?> pts</div>
                            <div class="date"><?php echo htmlspecialchars($row['formatted_date']); ?></div>
                        </div>
                    <?php }
                } else { ?>
                    <p>No redemptions found.</p>
                <?php } ?>
            </div>
        </div>
    </div>
    <br>
</body>

</html>