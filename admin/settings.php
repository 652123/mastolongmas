<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: ../index.php?modal=login"); exit; }
include '../includes/config.php';

$message = '';
$msgType = '';

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'change_password') {
        $oldPass = $_POST['old_password'];
        $newPass = $_POST['new_password'];
        $confirmPass = $_POST['confirm_password'];
        
        // Get current admin password
        $admin = $conn->query("SELECT * FROM users WHERE role='admin' LIMIT 1")->fetch_assoc();
        
        if (!password_verify($oldPass, $admin['password'])) {
            $message = 'Password lama salah!';
            $msgType = 'error';
        } elseif ($newPass !== $confirmPass) {
            $message = 'Konfirmasi password tidak cocok!';
            $msgType = 'error';
        } elseif (strlen($newPass) < 6) {
            $message = 'Password minimal 6 karakter!';
            $msgType = 'error';
        } else {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $conn->query("UPDATE users SET password = '$hash' WHERE role='admin' LIMIT 1");
            $message = 'Password berhasil diubah!';
            $msgType = 'success';
        }
    }
}

$pageTitle = 'Pengaturan';
include 'includes/header.php';
?>

    <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 text-white" data-aos="fade-down">
        <div>
            <h2 class="text-3xl font-extrabold mb-1">⚙️ Pengaturan Akun</h2>
            <p class="opacity-80">Ganti password administrator</p>
        </div>
    </div>

    <?php if($message): ?>
        <div class="mb-6 p-4 rounded-xl font-bold text-sm <?php echo $msgType === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200'; ?>">
            <i class="fa-solid <?php echo $msgType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-1"></i>
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="max-w-xl mx-auto">
        <!-- Change Password -->
        <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6" data-aos="fade-up">
            <h4 class="text-sm font-bold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-lock text-blue-500"></i> Ganti Password Admin
            </h4>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="change_password">
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">Password Lama</label>
                    <input type="password" name="old_password" required class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 focus:border-blue-500 text-sm dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">Password Baru</label>
                    <input type="password" name="new_password" required minlength="6" class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 focus:border-blue-500 text-sm dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">Konfirmasi Password</label>
                    <input type="password" name="confirm_password" required class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 focus:border-blue-500 text-sm dark:text-white">
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-bold shadow-lg shadow-blue-500/30 transition">
                    <i class="fa-solid fa-key mr-1"></i> Ubah Password
                </button>
            </form>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>

