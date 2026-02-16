<?php
include 'includes/config.php';

if ($conn->query("TRUNCATE TABLE login_attempts")) {
    echo "<h1>✅ Login Reset Berhasil!</h1>";
    echo "<p>Semua batasan login telah dihapus. Silakan coba login kembali.</p>";
    echo "<a href='admin/login.php'>Ke Login Admin</a> | <a href='index.php'>Ke Beranda</a>";
} else {
    echo "<h1>❌ Gagal Reset</h1>";
    echo "Error: " . $conn->error;
}
?>
