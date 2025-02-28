<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../config.php'; // Database connection

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminLogin.php");
    exit();
}

// Pagination setup
$limit = 10; // Redemptions per page
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Fetch total redemptions count
$countQuery = "SELECT COUNT(*) AS total FROM redeem";
$countResult = $conn->query($countQuery);
$totalRedemptions = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRedemptions / $limit);

// Fetch total points redeemed for all users
$stmt = $conn->prepare("SELECT SUM(points_redeemed) AS total_redeemed FROM redeem");
$stmt->execute();
$redeemSumResult = $stmt->get_result();
$redeemSum = $redeemSumResult->fetch_assoc();
$totalPointsRedeemed = $redeemSum['total_redeemed'] ?? 0;

// Fetch all redemption history with pagination
$stmt = $conn->prepare("SELECT r.id, r.user_id, r.points_redeemed, r.date_redeemed, r.admin_id, u.name AS user_name 
                        FROM redeem r 
                        JOIN users u ON r.user_id = u.user_id 
                        ORDER BY r.date_redeemed DESC 
                        LIMIT ?, ?");
$stmt->bind_param("ii", $offset, $limit);
$stmt->execute();
$redeemResult = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redeemption History</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="../css/admindash.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        /* Total Redeemed Card */
        .total-redeemed-card {
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            /* margin: 20px 0; */
            margin-top: 40px;
            text-align: center;
            width: 90%;
        }

        .total-redeemed-card h3 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }

        .total-redeemed-card span {
            margin: 10px 0 0;
            margin-left: 20px;
            font-size: 18px;
            font-weight: bold;
            color: #ff4d4d;
        }

        /* Table Container */
        .redeem-table-container {
            padding: 10px;
            overflow-x: auto;
            position: relative;
        }

        /* Table Style */
        .redeem-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
            min-width: 600px;
        }

        .redeem-table th,
        .redeem-table td {
            padding: 12px;
            text-align: left;
            font-size: 16px;
            border-bottom: 1px solid #ddd;
        }

        .redeem-table th {
            background: #f4f4f4;
            color: #333;
            font-weight: bold;
        }

        .redeem-table tr:hover {
            background: #f9f9f9;
            transition: background 0.2s ease;
        }

        /* Scroll Buttons Container */
        .scroll-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            width: 30%;
            margin: 0 auto;
        }

        /* Scroll Buttons */
        .scroll-btn {
            background: #007bff;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 20px;
            transition: background 0.3s ease;
        }

        .scroll-btn:hover {
            background: #0056b3;
        }

        /* Pagination */
        .pagination {
            margin-top: 20px;
            margin-bottom: 20px;
            text-align: center;
        }

        .pagination a {
            margin: 0 5px;
            padding: 4px 8px;
            border: 1px solid #ddd;
            text-decoration: none;
            display: inline-block;
        }

        .pagination a.active {
            background-color: #007bff;
            color: white;
            border-radius: 10px;
        }

        /* Mobile Adjustments */
        @media (max-width: 768px) {

            .redeem-table th,
            .redeem-table td {
                font-size: 14px;
                padding: 8px;
            }

            .scroll-btn {
                width: 35px;
                height: 35px;
                font-size: 18px;
            }

            .total-redeemed-card p {
                font-size: 20px;
            }
        }

        @media (max-width: 480px) {

            .redeem-table th,
            .redeem-table td {
                font-size: 12px;
                padding: 6px;
            }

            .scroll-btn {
                width: 30px;
                height: 30px;
                font-size: 16px;
            }

            .total-redeemed-card p {
                font-size: 18px;
            }
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
    <!-- Total Redeemed Points Card -->
    <div class="total-redeemed-card">
        <h3>Total Points Redeemed (All Users) - <span><?php echo htmlspecialchars($totalPointsRedeemed); ?> pts</span></h3>

    </div>

    <!-- Redemption History Table -->
    <div class="redeem-table-container">
        <table class="redeem-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Points Redeemed</th>
                    <th>Date</th>
                    <th>Admin ID</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $redeemResult->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['user_name']) . " (ID: " . $row['user_id'] . ")"; ?></td>
                        <td><?php echo htmlspecialchars($row['points_redeemed']); ?> pts</td>
                        <td><?php echo date("d-m-Y", strtotime($row['date_redeemed'])); ?></td>
                        <td><?php echo htmlspecialchars($row['admin_id']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <div class="scroll-buttons">
        <button class="scroll-btn left" id="scrollLeft"><i class="fa-solid fa-arrow-left"></i></button>
        <button class="scroll-btn right" id="scrollRight"><i class="fa-solid fa-arrow-right"></i></button>
    </div>

    <!-- Pagination -->
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const tableContainer = document.querySelector(".redeem-table-container");
            const scrollLeftBtn = document.getElementById("scrollLeft");
            const scrollRightBtn = document.getElementById("scrollRight");

            scrollLeftBtn.addEventListener("click", function() {
                tableContainer.scrollBy({
                    left: -300,
                    behavior: "smooth"
                });
            });

            scrollRightBtn.addEventListener("click", function() {
                tableContainer.scrollBy({
                    left: 300,
                    behavior: "smooth"
                });
            });
        });
    </script>
</body>

</html>