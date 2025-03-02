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
// Pagination setup
$limit = 10; // Transactions per page
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Fetch total transactions count
$countQuery = "SELECT COUNT(*) AS total FROM transactions";
$countResult = $conn->query($countQuery);
$totalTransactions = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalTransactions / $limit);

// Fetch transactions with pagination, ordered by ID in descending order
$query = "SELECT t.id, t.user_id, t.amount_paid, t.points_given, t.transaction_date, u.name AS user_name
          FROM transactions t
          JOIN users u ON t.user_id = u.user_id
          ORDER BY t.id DESC
          LIMIT ?, ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $offset, $limit);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Management</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="../css/admindash.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        /* Table Container */
        .transaction-table-container {
            padding: 10px;
            margin-top: 30px;
            overflow-x: auto;
            position: relative; /* For positioning scroll buttons */
        }

        /* Table Style */
        .transaction-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
            min-width: 600px; /* Ensures table remains wide enough for content */
        }

        .transaction-table th,
        .transaction-table td {
            padding: 15px;
            text-align: left;
            font-size: 16px;
            border-bottom: 1px solid #ddd;
        }

        .transaction-table th {
            background: #f4f4f4;
            color: #333;
            font-weight: bold;
        }

        .transaction-table tr:hover {
            background: #f9f9f9;
            transition: background 0.2s ease;
        }

        /* Restore Button */
        .restore-btn {
            background: #ff4d4d;
            color: #000;
            padding: 10px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s ease;
        }

        .restore-btn:hover {
            background: #d93636;
        }

        /* Scroll Buttons Container */
        .scroll-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            /* border: 1px solid; */
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

        .scroll-btn.left {
            margin-left: 0;
        }

        .scroll-btn.right {
            margin-right: 0;
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

        /* Popup Overlay */
        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        /* Popup Content */
        .popup-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.2);
            text-align: center;
            width: 400px;
            max-width: 90%;
        }

        .popup-content p {
            margin: 15px 0;
            font-size: 16px;
        }

        /* Popup Buttons */
        .popup-btn {
            padding: 8px 20px;
            margin: 0 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        #confirmRestore {
            background: #007bff;
            color: white;
            margin-bottom: 10px;
        }

        #cancelRestore {
            background: #ff4d4d;
            color: white;
        }

        /* Close Button */
        .close-btn {
            font-size: 48px;
            color: #000;
            cursor: pointer;
            position: absolute;
            top: 10px;
            right: 20px;
        }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .transaction-table th,
            .transaction-table td {
                font-size: 14px;
                padding: 8px;
            }

            .restore-btn {
                font-size: 12px;
                padding: 6px 10px;
            }

            .scroll-btn {
                width: 35px;
                height: 35px;
                font-size: 18px;
            }
        }

        @media (max-width: 480px) {
            .transaction-table th,
            .transaction-table td {
                font-size: 12px;
                padding: 6px;
            }

            .restore-btn {
                font-size: 10px;
                padding: 4px 8px;
            }

            .scroll-btn {
                width: 30px;
                height: 30px;
                font-size: 16px;
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
    <div class="transaction-table-container">
        <table class="transaction-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Amount Paid</th>
                    <th>Points Given</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>#<?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['user_name']) ?> (ID: <?= $row['user_id'] ?>)</td>
                        <td>₹ <?= $row['amount_paid'] ?></td>
                        <td><?= $row['points_given'] ?></td>
                        <td><?= date("Y-m-d", strtotime($row['transaction_date'])) ?></td>
                        <td><button class="restore-btn" data-id="<?= $row['id'] ?>">Restore</button></td>
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
        <?php for ($i = 1; $i <= $totalPages; $i++) { ?>
            <a href="?page=<?= $i ?>" class="<?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
        <?php } ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Restore button functionality
            document.querySelectorAll(".restore-btn").forEach(button => {
                button.addEventListener("click", function() {
                    let transactionId = this.getAttribute("data-id");
                    showConfirmPopup(transactionId);
                });
            });

            // Scroll button functionality
            const tableContainer = document.querySelector(".transaction-table-container");
            const scrollLeftBtn = document.getElementById("scrollLeft");
            const scrollRightBtn = document.getElementById("scrollRight");

            scrollLeftBtn.addEventListener("click", function() {
                tableContainer.scrollBy({
                    left: -300, // Scroll left by 300px
                    behavior: "smooth"
                });
            });

            scrollRightBtn.addEventListener("click", function() {
                tableContainer.scrollBy({
                    left: 300, // Scroll right by 300px
                    behavior: "smooth"
                });
            });
        });

        function showConfirmPopup(transactionId) {
            let existingPopup = document.querySelector(".popup-overlay");
            if (existingPopup) existingPopup.remove();

            const popup = document.createElement("div");
            popup.classList.add("popup-overlay");
            popup.innerHTML = `
                <div class="popup-content">
                    <p>Are you sure you want to restore this transaction?</p>
                    <button id="confirmRestore" class="popup-btn">Yes</button>
                    <button id="cancelRestore" class="popup-btn">No</button>
                </div>
            `;
            document.body.appendChild(popup);

            document.getElementById("confirmRestore").addEventListener("click", function() {
                restoreTransaction(transactionId);
                popup.remove();
            });

            document.getElementById("cancelRestore").addEventListener("click", function() {
                popup.remove();
            });
        }

        function restoreTransaction(transactionId) {
            fetch("restoreTransaction.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    transaction_id: transactionId
                })
            })
            .then(response => {
                if (!response.ok) throw new Error("Network response was not ok");
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showPopup(data.message, data.beforePoints, data.afterPoints, data.beforeAmount, data.afterAmount);
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("An error occurred while restoring the transaction.");
            });
        }

        function showPopup(message, beforePoints, afterPoints, beforeAmount, afterAmount) {
            let existingPopup = document.querySelector(".popup-overlay");
            if (existingPopup) existingPopup.remove();

            const popup = document.createElement("div");
            popup.classList.add("popup-overlay");
            popup.innerHTML = `
                <div class="popup-content">
                    <span class="close-btn">×</span>
                    <h2>${message}</h2>
                    <p><strong>Total Points (Before):</strong> ${beforePoints} pts</p>
                    <p><strong>Current Points (After):</strong> ${afterPoints} pts</p>
                    <p><strong>Total Amount Spent (Before):</strong> ₹${beforeAmount}</p>
                    <p><strong>Current Amount Spent (After):</strong> ₹${afterAmount}</p>
                </div>
            `;
            document.body.appendChild(popup);

            popup.querySelector(".close-btn").addEventListener("click", function() {
                popup.remove();
                location.reload();
            });
        }
    </script>
</body>

</html>