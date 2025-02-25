<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminLogin.php"); // Redirect to login if not logged in
    exit();
}

require '../config.php'; // Include database connection

// Fetch the admin's role using admin_id from session
$adminId = $_SESSION['admin_id'];
$roleQuery = "SELECT role FROM admin WHERE id = ?";
$stmt = $conn->prepare($roleQuery);
$stmt->bind_param("i", $adminId); // Assuming admin_id is an integer
$stmt->execute();
$roleResult = $stmt->get_result();
$admin = $roleResult->fetch_assoc();
$role = $admin['role'] ?? ''; // Default to empty string if no role found
$stmt->close();

// Fetch statistics
$statsQuery = "SELECT 
    (SELECT COUNT(*) FROM transactions) AS total_transactions,
    (SELECT SUM(points_given) FROM transactions) AS total_points_issued,
    (SELECT SUM(amount_paid) FROM transactions) AS total_earnings,
    (SELECT SUM(points_redeemed) FROM redeem) AS total_points_redeemed,
    (SELECT COUNT(*) FROM users WHERE created_at >= CURDATE()) AS new_users,
    (SELECT COUNT(*) FROM users) AS total_users";
$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();

// Fetch top customers
$topCustomersQuery = "SELECT users.name, transactions.user_id, SUM(transactions.points_given) AS points 
                      FROM transactions 
                      JOIN users ON transactions.user_id = users.user_id 
                      GROUP BY transactions.user_id 
                      ORDER BY points DESC 
                      LIMIT 3";
$topCustomersResult = $conn->query($topCustomersQuery);
$topCustomers = $topCustomersResult->fetch_all(MYSQLI_ASSOC);

// Fetch the 5 most recent transactions
$transactionsQuery = "SELECT users.name, users.user_id, transactions.amount_paid, transactions.points_given, transactions.transaction_date 
                      FROM transactions 
                      JOIN users ON transactions.user_id = users.user_id 
                      ORDER BY transactions.id DESC 
                      LIMIT 5";
$transactionsResult = $conn->query($transactionsQuery);
$transactions = $transactionsResult->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Overview</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="../css/admindash.css">
    <link rel="stylesheet" href="../css/adminoverview.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script>
        let transactions = <?php echo json_encode($transactions); ?>;
    </script>
</head>

<body>
    <header>
        <div class="logo"><img src="../image/logo1.png" alt="Logo"></div>
        <div class="nav">
            <a href="admindash.php"><i class="fa-solid fa-arrow-left"></i></a>
        </div>
    </header>

    <div class="dashboard">
        <div class="stats-container">
            <div class="stat-box" style="background: #DAE9FA;">
                <p>Total Transactions</p> <b><?php echo $stats['total_transactions']; ?></b>
            </div>
            <div class="stat-box" style="background: #EFBDBD;">
                <p>Total Points Issued</p> <b><?php echo $stats['total_points_issued']; ?></b>
            </div>
            <?php if (in_array($role, ['dev', 'admin'])): ?>
                <div class="stat-box" style="background: #BBB7E5;">
                    <p>Total Earnings</p> <b>₹<?php echo $stats['total_earnings']; ?></b>
                </div>
            <?php endif; ?>
            <div class="stat-box" style="background: #FFFBF2;">
                <p>Total Points Redeemed</p> <b><?php echo $stats['total_points_redeemed']; ?></b>
            </div>
            <!-- <div class="stat-box" style="background: #B6C687;">
                <p>New Users</p> <b><?php echo $stats['new_users']; ?></b>
            </div> -->
            <div class="stat-box" style="background: #E8E0D6;">
                <p>Total Users</p> <b><?php echo $stats['total_users']; ?></b>
            </div>
        </div>
    </div>

    <div class="dashboard dashboard2">
        <h2>Top Customers</h2>
        <?php foreach ($topCustomers as $customer): ?>
            <div class="profile">
                <img src="../image/user.png" alt="User Avatar">
                <div class="profile-info">
                    <h3><?php echo $customer['name']; ?></h3>
                    <h4>ID: <?php echo $customer['user_id']; ?></h4>
                </div>
                <p><?php echo $customer['points']; ?> points</p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="container dashboard2">
        <div class="title">Recent Transactions</div>
        <div id="transactionContainer"></div>
    </div>
    <br>
    <script>
        // Function to display all transactions (up to 5, as limited by the query)
        function displayTransactions() {
            const container = document.getElementById('transactionContainer');

            // Optional: Format date for better readability
            const formatDate = (dateStr) => {
                let date = new Date(dateStr);
                if (isNaN(date.getTime())) {
                    return dateStr; // Fallback to original string if invalid
                }
                return date.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            };

            // Render all transactions (limited to 5 by the SQL query)
            container.innerHTML = transactions.length > 0 ?
                transactions.map(t => `
                <div class='transaction'>
                    <div>Id: ${t.user_id}</div>
                    <div class='amount'>₹${t.amount_paid}</div>
                    <div class='points'>+${t.points_given} pts</div>
                    <div class='date'>${formatDate(t.transaction_date)}</div>
                </div>
            `).join('') :
                `<p class="no-data">No transactions found.</p>`;
        }

        // Display transactions on page load
        document.addEventListener("DOMContentLoaded", displayTransactions);
    </script>

    <script src="../js/profile.js"></script>
    <script src="../js/admindash.js"></script>
    <script src="../js/adminoverview.js"></script>
</body>

</html>