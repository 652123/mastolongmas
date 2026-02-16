<?php
$host = 'localhost';
$user = 'root';
$pass = ''; // Default XAMPP password
$dbname = 'db_mastolongmas';


mysqli_report(MYSQLI_REPORT_OFF); // Matikan error fatal agar bisa di-handle manual
$conn = @new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("Maaf, terjadi gangguan koneksi ke database. Silakan coba lagi nanti.");
}

// Set Timezone to WIB (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');
$conn->query("SET time_zone = '+07:00'");

// Fix CSP "unsafe-eval" check
header("Content-Security-Policy: default-src * 'self' 'unsafe-inline' 'unsafe-eval' data: gap: content: blob:; script-src * 'self' 'unsafe-inline' 'unsafe-eval' blob:; style-src * 'self' 'unsafe-inline'; font-src * 'self' data:; img-src * 'self' data: blob:; connect-src * 'self' 'unsafe-inline' blob:;");

// --- Helper: Audit Logging ---
function logActivity($conn, $user_id, $username, $action, $details = null) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, username, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $username, $action, $details, $ip);
    $stmt->execute();
}

// --- Helper: CSRF Protection ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
