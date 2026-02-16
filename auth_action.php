<?php
ob_start();
session_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
include 'includes/config.php';

// Safety check: if $conn failed, return JSON error
if (!$conn || $conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database belum siap. Silakan buka setup_db.php dulu.']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Security Token (CSRF). Refresh page.']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action == 'login') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $ip_address = $_SERVER['REMOTE_ADDR'];

        // --- Rate Limiting Check ---
        $stmt_check = $conn->prepare("SELECT COUNT(*) as count FROM login_attempts WHERE ip_address = ? AND attempt_time > (NOW() - INTERVAL 15 MINUTE)");
        $stmt_check->bind_param("s", $ip_address);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result()->fetch_assoc();

        if ($result_check['count'] > 5) {
            echo json_encode(['status' => 'error', 'message' => 'Terlalu banyak percobaan login. Tunggu 15 menit.']);
            exit;
        }

        $stmt = $conn->prepare("SELECT id, password, role, full_name FROM users WHERE username = ?");
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Tabel users belum ada.']);
            exit;
        }
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                session_regenerate_id(true); // Prevent Session Fixation
                
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $row['role'];
                $_SESSION['full_name'] = $row['full_name'];
                
                // Clear attempts on success (Optional: DELETE FROM login_attempts WHERE ip_address = ?)
                
                $redirect = 'index.php';
                if ($row['role'] == 'admin') {
                    $_SESSION['admin_logged_in'] = true;
                    $redirect = 'admin/dashboard.php';
                }

                echo json_encode(['status' => 'success', 'message' => 'Login berhasil!', 'redirect' => $redirect]);
            } else {
                // Log failed attempt
                $stmt_log = $conn->prepare("INSERT INTO login_attempts (ip_address, username) VALUES (?, ?)");
                $stmt_log->bind_param("ss", $ip_address, $username);
                $stmt_log->execute();
                
                echo json_encode(['status' => 'error', 'message' => 'Password salah!']);
            }
        } else {
            // Log failed attempt
            $stmt_log = $conn->prepare("INSERT INTO login_attempts (ip_address, username) VALUES (?, ?)");
            $stmt_log->bind_param("ss", $ip_address, $username);
            $stmt_log->execute();
            
            echo json_encode(['status' => 'error', 'message' => 'Username tidak ditemukan!']);
        }
    } 
    elseif ($action == 'register') {
        $full_name = $_POST['full_name'] ?? '';
        $wa_number = $_POST['wa_number'] ?? '';
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($password !== $confirm_password) {
            echo json_encode(['status' => 'error', 'message' => 'Konfirmasi password tidak cocok!']);
            exit;
        }

        // Check Username
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        if (!$check) {
            echo json_encode(['status' => 'error', 'message' => 'Tabel users belum ada. Silakan buka setup_db.php dulu.']);
            exit;
        }
        $check->bind_param("s", $username);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Username sudah dipakai!']);
            exit;
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'customer';
        
        $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, wa_number, role) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Tabel users belum ada. Silakan buka setup_db.php dulu.']);
            exit;
        }
        $stmt->bind_param("sssss", $username, $hashed_password, $full_name, $wa_number, $role);
        
        if ($stmt->execute()) {
            $new_user_id = $conn->insert_id;
            
            // Auto Login
            $_SESSION['user_id'] = $new_user_id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            $_SESSION['full_name'] = $full_name;

            echo json_encode(['status' => 'success', 'message' => 'Pendaftaran berhasil! Mengalihkan...', 'redirect' => 'order.php']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mendaftar: ' . $stmt->error]);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
}
?>
