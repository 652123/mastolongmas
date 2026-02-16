<?php
session_start();
include '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Count Pending Orders
$result = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status='pending'");
$row = $result->fetch_assoc();

echo json_encode([
    'status' => 'success',
    'pending_count' => (int)$row['total']
]);
?>
