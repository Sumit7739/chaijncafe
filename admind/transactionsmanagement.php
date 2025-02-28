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
        /* Container */
        .transaction-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            padding: 10px;
            margin-top: 30px;
        }

        /* Card Style */
        .transaction-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            gap: 10px;
            transition: transform 0.2s ease;
        }

        /* Hover Effect */
        .transaction-card:hover {
            transform: scale(1.02);
        }

        /* Header (ID & Date) */
        .transaction-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
            padding-bottom: 8px;
        }

        .transaction-header h3 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }

        .transaction-date {
            font-size: 14px;
            color: gray;
        }

        /* Transaction Details */
        .transaction-details p {
            margin: 5px 0;
            font-size: 16px;
        }

        /* Restore Button */
        .restore-btn {
            background: #ff4d4d;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s ease;
        }

        .restore-btn:hover {
            background: #d93636;
        }

        /* Pagination */
        .pagination {
            margin-top: 20px;
            margin-bottom: 20px;
            text-align: center;
        }

        .pagination a {
            margin: 0 5px;
            padding: 8px 12px;
            border: 1px solid #ddd;
            text-decoration: none;
            display: inline-block;
        }

        .pagination a.active {
            background-color: #007bff;
            color: white;
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

    <div class="transaction-container">
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="transaction-card">
                <div class="transaction-header">
                    <h3>#<?= $row['id'] ?></h3>
                    <span class="transaction-date"><?= date("Y-m-d", strtotime($row['transaction_date'])) ?></span>
                </div>
                <div class="transaction-details">
                    <p><strong>User:</strong> <?= htmlspecialchars($row['user_name']) ?> <strong> (ID: <?= $row['user_id'] ?>)</strong></p>
                    <p><strong>Amount Paid:</strong> ₹ <?= $row['amount_paid'] ?></p>
                    <p><strong>Points Given:</strong> <?= $row['points_given'] ?></p>
                </div>
                <button class="restore-btn" data-id="<?= $row['id'] ?>">Restore</button>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- Pagination -->
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++) { ?>
            <a href="?page=<?= $i ?>" class="<?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
        <?php } ?>
    </div>
    <br>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".restore-btn").forEach(button => {
                button.addEventListener("click", function() {
                    let transactionId = this.getAttribute("data-id");
                    showConfirmPopup(transactionId);
                });
            });
        });

        function showConfirmPopup(transactionId) {
            // Remove any existing popup
            let existingPopup = document.querySelector(".popup-overlay");
            if (existingPopup) existingPopup.remove();

            // Create confirmation popup
            const popup = document.createElement("div");
            popup.classList.add("popup-overlay");
            popup.innerHTML = `
                <div class="popup-content">
                    <p>Are you sure you want to restore this transaction?</p>
                    <button id="confirmRestore" class="popup-btn">Yes</button>
                    <br>
                    <button id="cancelRestore" class="popup-btn">No</button>
                </div>
            `;
            document.body.appendChild(popup);

            // Event listeners for buttons
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
            // Remove existing popup if any
            let existingPopup = document.querySelector(".popup-overlay");
            if (existingPopup) existingPopup.remove();

            // Create the success popup
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

            // Close button functionality
            popup.querySelector(".close-btn").addEventListener("click", function() {
                popup.remove();
                location.reload(); // Reload the page after closing
            });
        }
    </script>
</body>

</html>