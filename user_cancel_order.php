<?php
session_start();
include 'includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $order_id = $_POST['id'];
    $user_id = $_SESSION['user_id'];

    if (!$order_id) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Order ID']);
        exit;
    }

    // Security Check: Order must belong to user AND be 'pending'
    $stmt = $conn->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Pesanan tidak ditemukan atau bukan milik Anda.']);
        exit;
    }

    $order = $result->fetch_assoc();

    if ($order['status'] !== 'pending') {
        echo json_encode(['status' => 'error', 'message' => 'Pesanan sudah diproses, tidak bisa dibatalkan. Hubungi admin.']);
        exit;
    }

    // Proceed to Cancel
    $updateStmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
    $updateStmt->bind_param("i", $order_id);

    if ($updateStmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Pesanan berhasil dibatalkan.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal membatalkan pesanan.']);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
}
?>
