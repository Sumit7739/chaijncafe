<?php
session_start();
require '../config.php'; // DB Connection

header("Content-Type: application/json");

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

// Get transaction ID from request
$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['transaction_id'])) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit();
}

$transaction_id = intval($data['transaction_id']);
$admin_id = $_SESSION['admin_id'];

$conn->begin_transaction();
try {
    // Get transaction details before restoring
    $query = "SELECT user_id, points_given, amount_paid FROM transactions WHERE id = ?";
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

    // Get user details before restore
    $beforeQuery = "SELECT points_balance, total_points, amount_spent FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($beforeQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $beforeUser = $result->fetch_assoc();

    $beforePoints = $beforeUser['total_points'];
    $beforeAmount = $beforeUser['amount_spent'];

    // Deduct points and amount from user
    $updateUserQuery = "UPDATE users 
                        SET points_balance = points_balance - ?, 
                            total_points = total_points - ?, 
                            amount_spent = amount_spent - ? 
                        WHERE user_id = ?";
    $stmt = $conn->prepare($updateUserQuery);
    $stmt->bind_param("iiii", $points, $points, $amount_paid, $user_id);
    $stmt->execute();

    // Get user details after restore
    $afterQuery = "SELECT total_points, amount_spent FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($afterQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $afterUser = $result->fetch_assoc();

    $afterPoints = $afterUser['total_points'];
    $afterAmount = $afterUser['amount_spent'];

    // Delete transaction record
    $deleteTransactionQuery = "DELETE FROM transactions WHERE id = ?";
    $stmt = $conn->prepare($deleteTransactionQuery);
    $stmt->bind_param("i", $transaction_id);
    $stmt->execute();

    // Log action in audit log
    $logMessage = "Restored transaction #$transaction_id, deducted $points points and ₹$amount_paid from user #$user_id";
    $insertLogQuery = "INSERT INTO audit_logs (admin_id, action, table_name, record_id, message, created_at) VALUES (?, 'restore', 'transactions', ?, ?, NOW())";
    $stmt = $conn->prepare($insertLogQuery);
    $stmt->bind_param("iis", $admin_id, $transaction_id, $logMessage);
    $stmt->execute();

    // Commit changes
    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Transaction restored successfully!",
        "beforePoints" => $beforePoints,
        "afterPoints" => $afterPoints,
        "beforeAmount" => $beforeAmount,
        "afterAmount" => $afterAmount
    ]);

} catch (Exception $e) {
    $conn->rollback(); // Rollback on error
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>

