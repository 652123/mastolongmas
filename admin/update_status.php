<?php
session_start();
include '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $order_id = $_POST['id'];
    $new_status = $_POST['status'];

    // Validate Status (Optional but recommended)
    $allowed_statuses = ['pending', 'accepted', 'completed', 'cancelled'];
    if (!in_array($new_status, $allowed_statuses)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid status']);
        exit;
    }

    // Get Order Details (for Gamification)
    $queryOrder = $conn->prepare("SELECT user_id, status FROM orders WHERE id = ?");
    $queryOrder->bind_param("i", $order_id);
    $queryOrder->execute();
    $orderData = $queryOrder->get_result()->fetch_assoc();

    if (!$orderData) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);

    if ($stmt->execute()) {
        // Gamification: Add Points if status becomes 'completed'
        if ($new_status == 'completed' && $orderData['status'] != 'completed') {
            include_once '../includes/gamification.php';
            addPoints($conn, $orderData['user_id'], 10); // Award 10 points
        }
        echo json_encode(['status' => 'success', 'message' => 'Status updated']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
}
?>
