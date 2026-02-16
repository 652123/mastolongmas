<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../index.php?modal=login");
    exit;
}
include '../includes/config.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Prevent deleting self or other admins (security precaution)
    $check = $conn->query("SELECT role FROM users WHERE id = $id")->fetch_assoc();
    if ($check && $check['role'] === 'admin') {
        header("Location: users.php?msg=" . urlencode("Tidak dapat menghapus admin!"));
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: users.php?msg=deleted");
    } else {
        header("Location: users.php?msg=" . urlencode("Gagal menghapus user: " . $conn->error));
    }
} else {
    header("Location: users.php");
}
exit;
?>

