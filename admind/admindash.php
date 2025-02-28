<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require '../config.php'; // Include DB connection file

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminLogin.php"); // Redirect to login if not logged in
    exit();
}

// Fetch admin details
$admin_id = $_SESSION['admin_id'];
$sql = "SELECT name FROM admin WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

$name = $admin ? $admin['name'] : 'Admin';

// Get today's transactions summary using CURDATE()
$sql = "SELECT COUNT(*) AS total_transactions, COALESCE(SUM(points_given), 0) AS total_points 
        FROM transactions 
        WHERE transaction_date >= CURDATE() AND transaction_date < CURDATE() + INTERVAL 1 DAY";

$stmt = $conn->prepare($sql);
$stmt->execute();
$stmt->bind_result($totalTransactions, $totalPoints);
$stmt->fetch();
$stmt->close();

// Fetch all users
$query = "SELECT user_id, name, email, phone, points_balance, card_level_id, created_at FROM users";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();
$conn->close();

// Encode user data for JavaScript use
$usersJson = json_encode($users);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="../css/admindash.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        .stats {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
            padding: 5px;
        }

        .stats div {
            background: black;
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            /* font-weight: bold; */
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
                <!-- <li>Help & Support <i class="fa-solid fa-chevron-right"></i></li> -->
                <!-- <li>Leaderboard <i class="fa-solid fa-chevron-right"></i></li> -->
                <a href="terms.html">
                    <li>Terms and Conditions <i class="fa-solid fa-chevron-right"></i></li>
                </a>
                <!-- <li>Referral Hub <i class="fa-solid fa-chevron-right"></i></li> -->
                <!-- <li>Rewards & Offers <i class="fa-solid fa-chevron-right"></i></li> -->
            </ul>
            <a href="logout.php" class="logout-btn">Log Out</a>
        </div>
    </div>

    <section>
        <div class="user">
            <div class="container">
                <div class="profile-info">
                    <h2>Welcome, <?php echo htmlspecialchars($name); ?></h2>
                </div>
            </div>
            <div class="searchbox">
                <div class="container2 container">
                    <input type="text" id="searchInput" placeholder="Search..." oninput="searchUsers()">
                    <select id="searchFilter">
                        <option value="user_id">User ID</option>
                        <option value="name">Name</option>
                        <option value="email">Email</option>
                        <option value="phone">Phone Number</option>
                        <option value="all">ALL</option>
                    </select>
                </div>
            </div>
        </div>
    </section>
    <section id="usersList" style="display: none;">
        <!-- Users will be dynamically inserted here -->
    </section>

    <section>
        <div class="inffo">
            <div class="btn1">
                <p>Transactions - <?php echo htmlspecialchars($totalTransactions ?? 0); ?></p>
            </div>
            <div class="btn2">
                <p>Points Issued - <?php echo htmlspecialchars($totalPoints ?? 0); ?></p>
            </div>
        </div>
    </section>

    <section class="dashboard">
        <h2>Dashboard</h2>
        <div class="dashboard-menu">
            <a href="overview.php">
                <div class="menu-item">
                    <div class="menu-text">
                        <p><i class="fa-solid fa-chart-line"></i> Overview</p>
                        <span>A quick glance at Transactions.</span>
                    </div>
                    <i class="fa-solid fa-chevron-right"></i>
                </div>
            </a>
            <div class="menu-item">
                <a href="usermanagement.php">
                    <div class="menu-text">
                        <p><i class="fa-solid fa-user"></i> User Management</p>
                        <span>Search Users, View/Edit User Details</span>
                    </div>
                </a>
                <i class="fa-solid fa-chevron-right"></i>
            </div>
            <div class="menu-item">
                <a href="transactionsmanagement.php">
                    <div class="menu-text">
                        <p><i class="fa-solid fa-envelope"></i> Transactions Management</p>
                        <span>View All Transactions.</span>
                    </div>
                </a>
                <i class="fa-solid fa-chevron-right"></i>
            </div>
            <div class="menu-item">
                <a href="redeemhistory.php">
                    <div class="menu-text">
                        <p><i class="fa-solid fa-coins"></i> Redeem History</p>
                        <span>View Redeem History.</span>
                    </div>
                </a>
                <i class="fa-solid fa-chevron-right"></i>
            </div>
            <div class="menu-item">
                <a href="referraltracking.php">
                    <div class="menu-text">
                        <p><i class="fa-solid fa-users"></i> Referral & Rewards Control</p>
                        <span>Set Referral Bonus.</span>
                    </div>
                </a>
                <i class="fa-solid fa-chevron-right"></i>
            </div>
            <div class="menu-item">
                <a href="reports.php">
                    <div class="menu-text">
                        <p><i class="fa-solid fa-chart-pie"></i> Analytics & Reports</p>
                        <span>Daily/Weekly/Monthly Reports.</span>
                    </div>
                </a>
                <i class="fa-solid fa-chevron-right"></i>
            </div>
            <div class="menu-item">
                <a href="adminsettings.php">
                    <div class="menu-text">
                        <p><i class="fa-solid fa-gear"></i> System Settings & Roles</p>
                        <span>Modify System Settings</span>
                    </div>
                </a>
                <i class="fa-solid fa-chevron-right"></i>
            </div>
            <div class="menu-item">
                <a href="settings.php">
                    <div class="menu-text">
                        <p><i class="fa-solid fa-gear"></i> Personal Settings</p>
                        <span>Change Password, Profile Settings</span>
                    </div>
                </a>
            </div>
            <div class="menu-item">
                <a href="announcement.php">
                    <div class="menu-text">
                        <p><i class="fa-solid fa-bell"></i> Announcements & Updates</p>
                        <span>Post Important Notices.</span>
                    </div>
                </a>
                <i class="fa-solid fa-chevron-right"></i>
            </div>
            <div class="menu-item">
                <a href="activitylog.php">
                    <div class="menu-text">
                        <p><i class="fa-solid fa-clipboard-list"></i> Activity & Login Logs</p>
                        <span>View Admin Activity Logs.</span>
                    </div>
                </a>
                <i class="fa-solid fa-chevron-right"></i>
            </div>
            <div class="menu-item">
                <a href="reset.php">
                    <div class="menu-text">
                        <p><i class="fa-solid fa-rotate-right"></i> Points & Transactions Reset</p>
                        <span>Monthly Auto-Reset.</span>
                    </div>
                </a>
                <i class="fa-solid fa-chevron-right"></i>
            </div>
        </div>
    </section>
    <br>
    <script>
        let usersData = <?php echo $usersJson; ?>;

        function searchUsers() {
            let query = document.getElementById("searchInput").value.toLowerCase();
            let filter = document.getElementById("searchFilter").value;
            let userList = document.getElementById("usersList");

            if (query.trim() === "") {
                userList.style.display = "none";
                return;
            }

            let filteredUsers = usersData.filter(user => {
                if (filter === "all") {
                    return user.name.toLowerCase().includes(query) ||
                        user.email.toLowerCase().includes(query) ||
                        user.phone.includes(query) ||
                        user.user_id.toString().includes(query);
                }
                return user[filter].toString().toLowerCase().includes(query);
            });

            displayUsers(filteredUsers);
            userList.style.display = filteredUsers.length > 0 ? "block" : "none";
        }

        function displayUsers(users) {
            let userList = document.getElementById('usersList');
            userList.innerHTML = "";

            users.forEach(user => {
                let userCard = document.createElement('div');
                userCard.classList.add('user-card');
                userCard.innerHTML = `
        <div class="profile">
            <img src="../image/user.png" alt="User Avatar">
            <div class="profile-info">
                <p>${user.name}</p>
                <small>ID: ${user.user_id}</small>
            </div>
        </div>
        <div class="stats">
            <div>Total Points: ${user.points_balance}</div>
            <div>Joined on ${new Date(user.created_at).toLocaleDateString()}</div>
        </div>
        <hr>
        <div class="buttons">
            <button class="add-points" onclick="redirectToPage('addpoints.php', '${user.user_id}')"><i class="fa-solid fa-plus"></i> Add Points</button>
            <button class="redeem-points" onclick="redirectToPage('redeempoints.php', '${user.user_id}')"><i class="fa-solid fa-coins"></i> Redeem Points</button>
        </div>
    `;
                userList.appendChild(userCard);
            });
        }

        // Function to redirect with user_id as a parameter
        function redirectToPage(page, userId) {
            window.location.href = `${page}?user_id=${userId}`;
        }
    </script>


    <script src="../js/profile.js"></script>
    <!-- <script src="../js/admindash.js"></script> -->
</body>

</html>