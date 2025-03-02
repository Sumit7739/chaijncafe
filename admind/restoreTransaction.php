<?php
session_start();
require '../config.php';

header("Content-Type: application/json");

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['transaction_id'])) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit();
}

$transaction_id = intval($data['transaction_id']);
$admin_id = $_SESSION['admin_id'];

$conn->begin_transaction();
try {
    // Get transaction details
    $query = "SELECT user_id, points_given, amount_paid, transaction_date FROM transactions WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $transaction_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Transaction not found");
    }

    $transaction = $result->fetch_assoc();
    $user_id = $transaction['user_id'];
    $points = $transaction['points_given'];
    $amount_paid = $transaction['amount_paid'];
    $transaction_date = $transaction['transaction_date'];

    // Get user details before restore
    $beforeQuery = "SELECT points_balance, total_points, amount_spent FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($beforeQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $beforeUser = $result->fetch_assoc();

    $beforePointsBalance = $beforeUser['points_balance'];
    $beforeTotalPoints = $beforeUser['total_points'];
    $beforeAmount = $beforeUser['amount_spent'];

    // Check redemptions since transaction
    $redeemQuery = "SELECT SUM(points_redeemed) AS total_redeemed 
                    FROM redeem 
                    WHERE user_id = ? AND date_redeemed > ?";
    $stmt = $conn->prepare($redeemQuery);
    $stmt->bind_param("is", $user_id, $transaction_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $redeemedSince = $result->fetch_assoc()['total_redeemed'] ?? 0;

    // Calculate points to subtract from points_balance
    $pointsToSubtractFromBalance = min($points, max(0, $beforePointsBalance - $redeemedSince));
    $newPointsBalance = $beforePointsBalance - $pointsToSubtractFromBalance;

    // Always subtract from total_points since it’s a correction
    $newTotalPoints = $beforeTotalPoints - $points;
    $newAmountSpent = $beforeAmount - $amount_paid;

    // Prevent negative points_balance
    if ($newPointsBalance < 0) {
        $newPointsBalance = 0;
    }

    // Update user
    $updateUserQuery = "UPDATE users 
                        SET points_balance = ?, 
                            total_points = ?, 
                            amount_spent = ? 
                        WHERE user_id = ?";
    $stmt = $conn->prepare($updateUserQuery);
    $stmt->bind_param("iidi", $newPointsBalance, $newTotalPoints, $newAmountSpent, $user_id);
    $stmt->execute();

    // Delete transaction
    $deleteTransactionQuery = "DELETE FROM transactions WHERE id = ?";
    $stmt = $conn->prepare($deleteTransactionQuery);
    $stmt->bind_param("i", $transaction_id);
    $stmt->execute();

    // Get user details after restore
    $afterQuery = "SELECT points_balance, total_points, amount_spent FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($afterQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $afterUser = $result->fetch_assoc();

    $afterPointsBalance = $afterUser['points_balance'];
    $afterTotalPoints = $afterUser['total_points'];
    $afterAmount = $afterUser['amount_spent'];

    // Log action
    $logMessage = "Restored transaction #$transaction_id, deducted $pointsToSubtractFromBalance points from balance, $points from total, and ₹$amount_paid from user #$user_id";
    $insertLogQuery = "INSERT INTO audit_logs (admin_id, action, table_name, record_id, message, created_at) VALUES (?, 'Restore', 'transactions', ?, ?, NOW())";
    $stmt = $conn->prepare($insertLogQuery);
    $stmt->bind_param("iis", $admin_id, $transaction_id, $logMessage);
    $stmt->execute();

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Transaction restored successfully!",
        "beforePoints" => $beforePointsBalance, // Report points_balance
        "afterPoints" => $afterPointsBalance,
        "beforeAmount" => $beforeAmount,
        "afterAmount" => $afterAmount
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>