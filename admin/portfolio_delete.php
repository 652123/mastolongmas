<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../index.php?modal=login");
    exit;
}
include '../includes/config.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Get image path to delete file
    $stmt = $conn->prepare("SELECT image_path FROM portfolio WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $file_path = "../" . $row['image_path'];
        
        // Delete file if exists
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        // Delete record
        $del_stmt = $conn->prepare("DELETE FROM portfolio WHERE id = ?");
        $del_stmt->bind_param("i", $id);
        $del_stmt->execute();
    }
}

header("Location: portfolio.php?status=success");
exit;
?>

