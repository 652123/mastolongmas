<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?login=true");
    exit;
}
include 'includes/config.php';
include 'includes/gamification.php';

$user_id = $_SESSION['user_id'];

// Handle Profile Update
$successMsg = '';
$errorMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $newName = trim($_POST['full_name'] ?? '');
    $newWa = trim($_POST['wa_number'] ?? '');

    if (strlen($newName) < 3) {
        $errorMsg = 'Nama minimal 3 karakter ya!';
    } elseif (!preg_match('/^[0-9]{10,15}$/', $newWa)) {
        $errorMsg = 'Nomor WA harus 10-15 digit angka.';
    } else {
        $upd = $conn->prepare("UPDATE users SET full_name=?, wa_number=? WHERE id=?");
        $upd->bind_param("ssi", $newName, $newWa, $user_id);
        if ($upd->execute()) {
            $successMsg = 'Profil berhasil di-update! 🎉';
        } else {
            $errorMsg = 'Gagal update: ' . $conn->error;
        }
    }
}

// Fetch User Data
$stmt = $conn->prepare("SELECT full_name, username, wa_number, created_at, points, rank_tier FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Ensure points & rank exist
$userPoints = (int)($user['points'] ?? 0);
$userRank = $user['rank_tier'] ?? getRank($userPoints);

// Fetch Order Stats
$statsStmt = $conn->prepare("SELECT 
    COUNT(*) as total_orders,
    COALESCE(SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END), 0) as completed,
    COALESCE(SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END), 0) as pending,
    COALESCE(SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END), 0) as cancelled,
    COALESCE(SUM(CASE WHEN status='completed' THEN price ELSE 0 END), 0) as total_spend,
    COALESCE(SUM(distance_km), 0) as total_km
    FROM orders WHERE user_id = ?");
$statsStmt->bind_param("i", $user_id);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();

// Fetch Recent Orders (last 5)
$recentStmt = $conn->prepare("SELECT id, service_type, pickup_location, dropoff_location, price, status, distance_km, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$recentStmt->bind_param("i", $user_id);
$recentStmt->execute();
$recentOrders = $recentStmt->get_result();

// Next rank calculation
$nextRankPoints = 50;
$nextRankName = 'Warga Senior';
if ($userPoints >= 1000) { $nextRankPoints = $userPoints; $nextRankName = 'MAX LEVEL'; }
elseif ($userPoints >= 500) { $nextRankPoints = 1000; $nextRankName = 'Dewa'; }
elseif ($userPoints >= 200) { $nextRankPoints = 500; $nextRankName = 'Sultan'; }
elseif ($userPoints >= 50) { $nextRankPoints = 200; $nextRankName = 'Juragan'; }
$progressPercent = $nextRankPoints > 0 ? min(100, round(($userPoints / $nextRankPoints) * 100)) : 100;

include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="pt-28 pb-20 bg-gradient-to-br from-gray-50 via-blue-50/30 to-orange-50/20 dark:from-brand-darker dark:via-gray-900 dark:to-gray-900 min-h-screen transition-colors duration-300">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">

            <!-- Alert Messages are now handled by SweetAlert2 via JS below -->

            <!-- ============ PROFILE HERO CARD ============ -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl overflow-hidden mb-6 border border-gray-100 dark:border-gray-700 transition-colors duration-300">
                <!-- Cover Banner -->
                <div class="h-36 md:h-44 relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-brand-blue via-indigo-700 to-purple-900 dark:from-blue-900 dark:via-indigo-900 dark:to-purple-950"></div>
                    <div class="absolute inset-0 opacity-20" style="background-image:url('https://www.transparenttextures.com/patterns/cubes.png')"></div>
                    <!-- Floating Shapes -->
                    <div class="absolute top-4 right-8 w-24 h-24 bg-white/10 rounded-full blur-xl"></div>
                    <div class="absolute bottom-4 left-12 w-32 h-32 bg-brand-orange/15 rounded-full blur-xl"></div>
                </div>

                <div class="px-6 md:px-8 pb-8">
                    <div class="relative flex flex-col md:flex-row md:justify-between md:items-end -mt-14 md:-mt-16 mb-6">
                        <!-- Avatar + Name -->
                        <div class="flex items-end gap-5">
                            <div class="w-24 h-24 md:w-28 md:h-28 bg-white dark:bg-gray-800 rounded-2xl p-1.5 shadow-xl border-4 border-white dark:border-gray-800 relative group transition-colors duration-300">
                                <div class="w-full h-full bg-gradient-to-br from-brand-orange to-orange-600 rounded-xl flex items-center justify-center text-4xl md:text-5xl text-white font-extrabold shadow-inner">
                                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                </div>
                                <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-green-500 rounded-full border-2 border-white dark:border-gray-800 shadow-sm"></div>
                            </div>
                            <div class="mb-2">
                                <h1 class="text-2xl md:text-3xl font-extrabold text-gray-800 dark:text-white tracking-tight"><?php echo htmlspecialchars($user['full_name']); ?></h1>
                                <p class="text-gray-400 dark:text-gray-400 font-medium text-sm">@<?php echo htmlspecialchars($user['username']); ?></p>
                                <div class="mt-2">
                                    <?php echo getRankBadge($userRank); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="flex gap-3 mt-4 md:mt-0">
                            <a href="order.php" class="bg-brand-orange hover:bg-orange-600 text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-orange-500/30 transition transform hover:scale-105 active:scale-95 flex items-center gap-2 text-sm">
                                <i class="fa-solid fa-plus"></i> Order Baru
                            </a>
                            <button onclick="document.getElementById('editModal').classList.remove('hidden')" class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200 px-5 py-2.5 rounded-xl font-bold shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 transition flex items-center gap-2 text-sm">
                                <i class="fa-solid fa-pen-to-square"></i> Edit Profil
                            </button>
                        </div>
                    </div>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-gradient-to-br from-blue-50 to-white dark:from-blue-900/30 dark:to-gray-800 p-4 rounded-2xl text-center border border-blue-100/50 dark:border-blue-900/50 group hover:shadow-md transition">
                            <div class="w-10 h-10 mx-auto bg-blue-100 dark:bg-blue-900/50 rounded-xl flex items-center justify-center text-blue-600 dark:text-blue-400 mb-2 group-hover:scale-110 transition-transform">
                                <i class="fa-solid fa-clipboard-list"></i>
                            </div>
                            <p class="text-2xl font-extrabold text-gray-800 dark:text-white"><?php echo $stats['total_orders']; ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Total Order</p>
                        </div>
                        <div class="bg-gradient-to-br from-green-50 to-white dark:from-green-900/30 dark:to-gray-800 p-4 rounded-2xl text-center border border-green-100/50 dark:border-green-900/50 group hover:shadow-md transition">
                            <div class="w-10 h-10 mx-auto bg-green-100 dark:bg-green-900/50 rounded-xl flex items-center justify-center text-green-600 dark:text-green-400 mb-2 group-hover:scale-110 transition-transform">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                            <p class="text-2xl font-extrabold text-gray-800 dark:text-white"><?php echo $stats['completed']; ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Selesai</p>
                        </div>
                        <div class="bg-gradient-to-br from-orange-50 to-white dark:from-orange-900/30 dark:to-gray-800 p-4 rounded-2xl text-center border border-orange-100/50 dark:border-orange-900/50 group hover:shadow-md transition">
                            <div class="w-10 h-10 mx-auto bg-orange-100 dark:bg-orange-900/50 rounded-xl flex items-center justify-center text-orange-600 dark:text-orange-400 mb-2 group-hover:scale-110 transition-transform">
                                <i class="fa-solid fa-wallet"></i>
                            </div>
                            <p class="text-2xl font-extrabold text-gray-800 dark:text-white">Rp <?php echo number_format($stats['total_spend'], 0, ',', '.'); ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Total Belanja</p>
                        </div>
                        <div class="bg-gradient-to-br from-indigo-50 to-white dark:from-indigo-900/30 dark:to-gray-800 p-4 rounded-2xl text-center border border-indigo-100/50 dark:border-indigo-900/50 group hover:shadow-md transition">
                            <div class="w-10 h-10 mx-auto bg-indigo-100 dark:bg-indigo-900/50 rounded-xl flex items-center justify-center text-indigo-600 dark:text-indigo-400 mb-2 group-hover:scale-110 transition-transform">
                                <i class="fa-solid fa-road"></i>
                            </div>
                            <p class="text-2xl font-extrabold text-gray-800 dark:text-white"><?php echo number_format($stats['total_km'], 1); ?> km</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Total Jarak</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============ MAIN CONTENT GRID ============ -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <!-- LEFT COLUMN -->
                <div class="space-y-6">
                    
                    <!-- Gamification Progress Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-lg p-6 border border-gray-100 dark:border-gray-700 relative overflow-hidden group">
                        <!-- Background Glow -->
                        <div class="absolute top-0 right-0 w-32 h-32 bg-brand-orange/10 rounded-full blur-3xl -mr-16 -mt-16 transition-opacity group-hover:opacity-100 opacity-50"></div>
                        
                        <h3 class="font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2 relative z-10">
                            <i class="fa-solid fa-trophy text-yellow-500"></i> Level & Poin
                        </h3>
                        <div class="text-center mb-4 relative z-10">
                            <div class="inline-block transform transition group-hover:scale-110 duration-300"><?php echo getRankBadge($userRank); ?></div>
                            <p class="text-4xl font-extrabold text-gray-800 dark:text-white mt-3"><?php echo number_format($userPoints); ?></p>
                            <p class="text-xs text-gray-400 font-bold uppercase tracking-wide">POIN TERKUMPUL</p>
                        </div>
                        
                        <?php if($nextRankName !== 'MAX LEVEL'): ?>
                        <div class="mt-4 relative z-10">
                            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1.5 font-bold">
                                <span><?php echo $userRank; ?></span>
                                <span><?php echo $nextRankName; ?></span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-3 overflow-hidden border border-gray-200 dark:border-gray-600">
                                <div class="bg-gradient-to-r from-brand-orange to-yellow-400 h-3 rounded-full transition-all duration-1000 shadow-sm shadow-orange-300 relative" style="width: <?php echo $progressPercent; ?>%">
                                    <div class="absolute inset-0 bg-white/30 animate-pulse"></div>
                                </div>
                            </div>
                            <p class="text-center text-[10px] text-gray-400 mt-1.5 font-medium">
                                <?php echo ($nextRankPoints - $userPoints); ?> poin lagi ke <b><?php echo $nextRankName; ?></b>
                            </p>
                        </div>
                        <?php else: ?>
                        <div class="mt-4 text-center relative z-10">
                            <span class="text-xs text-purple-600 font-bold bg-purple-50 px-3 py-1 rounded-full border border-purple-100 shadow-sm">🏆 MAX LEVEL REACHED!</span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Info Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                        <h3 class="font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-id-card text-blue-500"></i> Informasi Akun
                        </h3>
                        <div class="space-y-4">
                            <div class="flex items-center gap-4 bg-green-50 dark:bg-green-900/10 p-3.5 rounded-2xl border border-green-100 dark:border-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/20 transition">
                                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center text-green-600 dark:text-green-400 flex-shrink-0">
                                    <i class="fa-brands fa-whatsapp text-xl"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">WhatsApp</p>
                                    <p class="text-sm font-bold text-gray-800 dark:text-white truncate"><?php echo htmlspecialchars($user['wa_number']); ?></p>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 bg-gray-50 dark:bg-gray-700/30 p-3.5 rounded-2xl border border-gray-100 dark:border-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition">
                                <div class="w-10 h-10 bg-gray-200 dark:bg-gray-600 rounded-xl flex items-center justify-center text-gray-600 dark:text-gray-300 flex-shrink-0">
                                    <i class="fa-solid fa-calendar-days"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Member Sejak</p>
                                    <p class="text-sm font-bold text-gray-800 dark:text-white"><?php echo date('d F Y', strtotime($user['created_at'])); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700">
                            <a href="logout.php" class="w-full block text-center bg-red-50 dark:bg-red-900/10 text-red-600 dark:text-red-400 py-3 rounded-xl font-bold hover:bg-red-100 dark:hover:bg-red-900/20 transition text-sm border border-red-100 dark:border-red-900/20 shadow-sm hover:shadow-md">
                                <i class="fa-solid fa-right-from-bracket mr-2"></i> Logout
                            </a>
                        </div>
                    </div>

                </div>

                <!-- RIGHT COLUMN (History) -->
                <div class="md:col-span-2">
                    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden h-full flex flex-col">
                        <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/50">
                            <h3 class="font-bold text-lg text-gray-800 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-clock-rotate-left text-brand-blue dark:text-blue-400"></i> Pesanan Terakhir
                            </h3>
                            <a href="history.php" class="text-xs font-bold text-brand-orange hover:text-orange-600 flex items-center gap-1 group">
                                Lihat Semua <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                            </a>
                        </div>
                        
                        <div class="p-0 flex-1 overflow-y-auto max-h-[600px] scrollbar-hide">
                            <?php if ($recentOrders->num_rows > 0): ?>
                                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                    <?php while($order = $recentOrders->fetch_assoc()): ?>
                                        <?php 
                                            // Status Style
                                            $statusBadge = match($order['status']) {
                                                'pending' => '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-yellow-50 text-yellow-600 border border-yellow-200"><i class="fa-solid fa-clock mr-1"></i> Menunggu</span>',
                                                'accepted' => '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-600 border border-blue-200"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Diproses</span>',
                                                'completed' => '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-green-50 text-green-600 border border-green-200"><i class="fa-solid fa-check mr-1"></i> Selesai</span>',
                                                'cancelled' => '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-50 text-red-600 border border-red-200"><i class="fa-solid fa-ban mr-1"></i> Batal</span>',
                                                default => '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-50 text-gray-600 border border-gray-200">Unknown</span>'
                                            };
                                            
                                            $icon = match(strtolower($order['service_type'])) {
                                                'antar kilat' => 'fa-bolt text-brand-orange',
                                                'jasa belanja' => 'fa-bag-shopping text-brand-blue',
                                                'titip makanan' => 'fa-utensils text-brand-orange',
                                                'antar jemput' => 'fa-car text-brand-blue',
                                                'kurir barang' => 'fa-box text-indigo-500',
                                                default => 'fa-concierge-bell text-gray-500'
                                            };
                                        ?>
                                        <div class="p-5 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors group">
                                            <div class="flex items-start gap-4">
                                                <div class="w-10 h-10 rounded-xl bg-gray-50 dark:bg-gray-700 flex items-center justify-center flex-shrink-0 border border-gray-100 dark:border-gray-600 group-hover:scale-110 transition-transform">
                                                    <i class="fa-solid <?php echo $icon; ?>"></i>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex justify-between items-start mb-1">
                                                        <h4 class="font-bold text-gray-800 dark:text-gray-200 text-sm truncate"><?php echo htmlspecialchars($order['service_type']); ?> <span class="text-gray-400 font-normal text-xs ml-1">#<?php echo $order['id']; ?></span></h4>
                                                        <p class="font-bold text-gray-800 dark:text-white text-sm whitespace-nowrap">Rp <?php echo number_format($order['price'], 0, ',', '.'); ?></p>
                                                    </div>
                                                    
                                                    <!-- Simplified location for cleaner look -->
                                                    <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-2 truncate">
                                                        <span class="truncate max-w-[120px]"><i class="fa-solid fa-location-dot text-green-500 mr-1"></i> <?php echo explode(',', $order['pickup_location'])[0]; ?></span>
                                                        <i class="fa-solid fa-arrow-right text-[10px] text-gray-300"></i>
                                                        <span class="truncate max-w-[120px]"><i class="fa-solid fa-flag-checkered text-red-500 mr-1"></i> <?php echo explode(',', $order['dropoff_location'])[0]; ?></span>
                                                    </div>

                                                    <div class="flex justify-between items-center text-[10px] text-gray-400 font-medium">
                                                        <span class="flex items-center gap-1"><i class="fa-regular fa-calendar"></i> <?php echo date('d M, H:i', strtotime($order['created_at'])); ?></span>
                                                        <span class="flex items-center gap-1"><i class="fa-solid fa-road"></i> <?php echo $order['distance_km']; ?> km</span>
                                                        <?php echo $statusBadge; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                                <div class="p-4 text-center border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                                    <a href="history.php" class="text-sm font-bold text-gray-500 hover:text-brand-orange transition">Lihat Semua Riwayat</a>
                                </div>
                            <?php else: ?>
                                <div class="flex flex-col items-center justify-center h-64 text-center p-6">
                                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-2xl flex items-center justify-center mb-4 text-gray-300 dark:text-gray-500 text-2xl animate-pulse">
                                        <i class="fa-solid fa-scroll"></i>
                                    </div>
                                    <p class="text-gray-500 dark:text-gray-400 font-bold mb-1">Belum ada pesanan</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">Yuk cobain order jasa kami sekarang!</p>
                                    <a href="order.php" class="bg-brand-orange text-white px-5 py-2 rounded-xl font-bold text-xs hover:bg-orange-600 transition shadow-lg shadow-orange-500/20">
                                        Order Sekarang
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ============ EDIT PROFILE MODAL ============ -->
<!-- ============ EDIT PROFILE MODAL ============ -->
<div id="editModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 transition-opacity duration-300" onclick="if(event.target===this) this.classList.add('hidden')">
    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all scale-100 border border-gray-100 dark:border-gray-700">
        <div class="bg-gradient-to-r from-brand-blue to-indigo-700 dark:from-blue-900 dark:to-indigo-900 px-6 py-5 text-white">
            <h2 class="text-xl font-extrabold flex items-center gap-2"><i class="fa-solid fa-user-pen"></i> Edit Profil</h2>
            <p class="text-sm opacity-80">Perbarui informasi akunmu</p>
        </div>
        <form method="POST" class="p-6 space-y-5">
            <input type="hidden" name="update_profile" value="1">
            
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Nama Lengkap</label>
                <div class="relative">
                    <i class="fa-solid fa-user absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500"></i>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required minlength="3"
                        class="w-full pl-11 pr-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-brand-orange focus:border-brand-orange transition text-sm font-medium bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Nomor WhatsApp</label>
                <div class="relative">
                    <i class="fa-brands fa-whatsapp absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500"></i>
                    <input type="text" name="wa_number" value="<?php echo htmlspecialchars($user['wa_number']); ?>" required pattern="[0-9]{10,15}"
                        class="w-full pl-11 pr-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-brand-orange focus:border-brand-orange transition text-sm font-medium bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400"
                        placeholder="08xxxxxxxxxx">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Username</label>
                <div class="relative">
                    <i class="fa-solid fa-at absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500"></i>
                    <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled
                        class="w-full pl-11 pr-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-100 dark:bg-gray-900/50 text-sm font-medium text-gray-500 dark:text-gray-500 cursor-not-allowed">
                </div>
                <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 ml-1">Username tidak bisa diubah.</p>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="flex-1 py-3 px-4 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-bold hover:bg-gray-200 dark:hover:bg-gray-600 transition text-sm border border-gray-200 dark:border-gray-600">
                    Batal
                </button>
                <button type="submit" class="flex-1 py-3 px-4 bg-brand-orange text-white rounded-xl font-bold shadow-lg shadow-orange-500/30 hover:bg-orange-600 transition transform hover:scale-105 active:scale-95 text-sm">
                    <i class="fa-solid fa-save mr-2"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<style>
@keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
.animate-fadeIn { animation: fadeIn 0.4s ease-out; }
</style>

<script>
    // --- HANDLE PROFILE UPDATE ALERTS ---
    <?php if ($successMsg): ?>
    document.addEventListener('DOMContentLoaded', () => {
        MasAlert.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: '<?php echo $successMsg; ?>',
            timer: 2000,
            showConfirmButton: false
        });
    });
    <?php endif; ?>

    <?php if ($errorMsg): ?>
    document.addEventListener('DOMContentLoaded', () => {
        MasAlert.fire({
            icon: 'error',
            title: 'Gagal!',
            text: '<?php echo $errorMsg; ?>'
        });
    });
    <?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>
