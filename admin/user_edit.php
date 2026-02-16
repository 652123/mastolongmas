<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../index.php?modal=login");
    exit;
}
include '../includes/config.php';
include '../includes/gamification.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;
$error = null;
$success = null;

if (!$id) {
    header("Location: users.php");
    exit;
}

// Fetch User Data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: users.php?msg=" . urlencode("User tidak ditemukan"));
    exit;
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = $_POST['full_name'];
    $username = $_POST['username'];
    $waNumber = $_POST['wa_number'];
    $points = (int)$_POST['points'];
    
    // Recalculate Rank based on points
    $rank = getRank($points);

    // Update Query
    $update = $conn->prepare("UPDATE users SET full_name=?, username=?, wa_number=?, points=?, rank_tier=? WHERE id=?");
    $update->bind_param("sssisi", $fullName, $username, $waNumber, $points, $rank, $id);
    
    if ($update->execute()) {
        $success = "Data pengguna berhasil diperbarui!";
        // Refresh data
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
    } else {
        $error = "Gagal mengupdate: " . $conn->error;
    }
}

$pageTitle = 'Edit Pengguna';
include 'includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 text-white" data-aos="fade-down">
    <div>
        <h2 class="text-3xl font-extrabold mb-1">✏️ Edit Pengguna</h2>
        <p class="opacity-80">Ubah data profil dan poin gamifikasi</p>
    </div>
    <a href="users.php" class="mt-4 md:mt-0 bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-lg font-bold backdrop-blur-md transition border border-white/20">
        <i class="fa-solid fa-arrow-left"></i> Kembali
    </a>
</div>

<div class="max-w-2xl mx-auto">
    <!-- Notifications -->
    <?php if($success): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-md" role="alert">
            <p class="font-bold">Sukses!</p>
            <p><?php echo $success; ?></p>
        </div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-md" role="alert">
            <p class="font-bold">Error!</p>
            <p><?php echo $error; ?></p>
        </div>
    <?php endif; ?>

    <div class="glass bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8" data-aos="fade-up">
        <form method="POST" class="space-y-6">
            
            <!-- Identity -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-2 uppercase">Nama Lengkap</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 focus:border-blue-500 font-bold dark:text-white" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-2 uppercase">Username</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 focus:border-blue-500 font-bold dark:text-white" required>
                </div>
            </div>

            <!-- Contact -->
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-2 uppercase">Nomor WhatsApp</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-green-500"><i class="fa-brands fa-whatsapp text-lg"></i></span>
                    <input type="text" name="wa_number" value="<?php echo htmlspecialchars($user['wa_number']); ?>" class="w-full pl-12 pr-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 focus:border-blue-500 font-bold dark:text-white" placeholder="08xxxxxxxx">
                </div>
            </div>

            <div class="border-t border-gray-100 dark:border-gray-700 my-6"></div>

            <!-- Gamification -->
            <div class="bg-yellow-50 dark:bg-yellow-900/20 p-6 rounded-2xl border border-yellow-200 dark:border-yellow-700/50">
                <h3 class="text-lg font-bold text-yellow-700 dark:text-yellow-400 mb-4"><i class="fa-solid fa-trophy mr-2"></i>Gamifikasi</h3>
                
                <div class="flex items-center gap-4 mb-4">
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-2 uppercase">XP Poin</label>
                        <input type="number" name="points" value="<?php echo $user['points']; ?>" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 focus:border-yellow-500 font-bold dark:text-white">
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-2 uppercase">Rank Saat Ini</label>
                        <div class="px-4 py-3 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 text-center">
                            <?php echo getRankBadge($user['rank_tier']); ?>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 italic">*Rank akan otomatis terupdate berdasarkan jumlah poin yang Anda masukkan.</p>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-xl font-bold shadow-lg shadow-blue-500/30 transition transform hover:scale-[1.02] active:scale-95 text-lg">
                <i class="fa-solid fa-save mr-2"></i> Simpan Perubahan
            </button>

        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

