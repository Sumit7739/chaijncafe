<?php
session_start();
include('../config.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$userId = $_POST['user_id'] ?? $_SESSION['user_id'];

// Fetch the latest unread notification with ID
$sql = "SELECT id, message, type, created_at 
        FROM notifications 
        WHERE user_id = ? AND status = 'unread' 
        ORDER BY created_at DESC 
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $notif = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'id' => $notif['id'], // Include ID for tracking
        'message' => $notif['message'],
        'type' => $notif['type'],
        'created_at' => $notif['created_at']
    ]);
} else {
    echo json_encode(['success' => false]);
}

$stmt->close();
$conn->close();
