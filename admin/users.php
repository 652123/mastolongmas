<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: ../index.php?modal=login"); exit; }
include '../includes/config.php';
include '../includes/gamification.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build Query
$where = "1=1";
$params = [];
$types = "";
if ($search !== '') {
    $where .= " AND (u.full_name LIKE ? OR u.username LIKE ? OR u.wa_number LIKE ?)";
    $s = "%$search%";
    $params = [$s, $s, $s];
    $types = "sss";
}

$sql = "SELECT u.*, 
        COUNT(o.id) as total_orders, 
        COALESCE(SUM(CASE WHEN o.status='completed' THEN o.price ELSE 0 END), 0) as total_spend
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id
        WHERE u.role = 'user' AND $where
        GROUP BY u.id
        ORDER BY total_orders DESC";

$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Count users
$totalUsers = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='user'")->fetch_assoc()['cnt'];

$pageTitle = 'Pelanggan';
include 'includes/header.php';
?>

    <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 text-white" data-aos="fade-down">
        <div>
            <h2 class="text-3xl font-extrabold mb-1">👥 Manajemen Pelanggan</h2>
            <p class="opacity-80">Total <?php echo $totalUsers; ?> pelanggan terdaftar</p>
        </div>
    </div>

    <!-- Search -->
    <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-4 mb-6" data-aos="fade-up">
        <form method="GET" class="flex gap-3">
            <div class="flex-1 relative">
                <i class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari nama, username, atau nomor WA..." class="w-full pl-11 pr-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 focus:border-blue-500 text-sm font-medium dark:text-white transition">
            </div>
            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-blue-500/30 transition hover:bg-blue-700">
                <i class="fa-solid fa-search"></i> Cari
            </button>
        </form>
    </div>

    <!-- Users Table -->
    <div class="glass bg-white dark:bg-gray-800 rounded-3xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700" data-aos="fade-up" data-aos-delay="200">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-100 dark:border-gray-700">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Pelanggan</th>
                        <th class="px-5 py-3 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Level</th>
                        <th class="px-5 py-3 text-left text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Username</th>
                        <th class="px-5 py-3 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Total Order</th>
                        <th class="px-5 py-3 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Total Belanja</th>
                        <th class="px-5 py-3 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Bergabung</th>
                        <th class="px-5 py-3 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr class="hover:bg-blue-50/50 dark:hover:bg-gray-700/50 transition">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 text-white flex items-center justify-center font-bold shadow-lg text-sm">
                                        <?php echo strtoupper(substr($row['full_name'] ?? $row['username'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-800 dark:text-white text-sm"><?php echo htmlspecialchars($row['full_name'] ?? '-'); ?></p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500"><i class="fa-brands fa-whatsapp text-green-500"></i> <?php echo htmlspecialchars($row['wa_number'] ?? '-'); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <?php echo getRankBadge($row['rank_tier'] ?? 'Warga Biasa'); ?>
                                <div class="text-xs font-bold text-brand-orange mt-1">
                                    <i class="fa-solid fa-star text-yellow-500"></i> <?php echo number_format($row['points'] ?? 0); ?> XP
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-sm text-gray-600 dark:text-gray-300 font-mono bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">@<?php echo htmlspecialchars($row['username']); ?></span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 px-3 py-1 rounded-full text-xs font-bold"><?php echo $row['total_orders']; ?> order</span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <p class="font-bold text-gray-800 dark:text-white text-sm">Rp <?php echo number_format($row['total_spend'], 0, ',', '.'); ?></p>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo date('d M Y', strtotime($row['created_at'])); ?></p>
                            </td>
                                <td class="px-5 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <?php 
                                        if($row['wa_number']): 
                                            $waNumber = preg_replace('/^0/', '62', $row['wa_number']);
                                            $custName = explode(' ', trim($row['full_name']))[0];
                                            $waMsg = "Halo kak *$custName*, ini admin MasTolongMas. Ada yang bisa dibantu? 😁";
                                            $waLink = "https://wa.me/$waNumber?text=" . urlencode($waMsg);
                                    ?>
                                        <a href="<?php echo $waLink; ?>" target="_blank" class="bg-green-500 hover:bg-green-600 text-white p-2 rounded-lg inline-flex shadow-lg shadow-green-500/30 transition" title="Chat WA">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <!-- EDIT & DELETE Aksi -->
                                    <a href="user_edit.php?id=<?php echo $row['id']; ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white p-2 rounded-lg inline-flex shadow-lg shadow-yellow-500/30 transition" title="Edit User">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    
                                    <button onclick="deleteUser(<?php echo $row['id']; ?>)" class="bg-red-500 hover:bg-red-600 text-white p-2 rounded-lg inline-flex shadow-lg shadow-red-500/30 transition" title="Hapus User">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="py-12 text-center text-gray-500">
                                <i class="fa-solid fa-users-slash text-4xl mb-2 text-gray-300"></i>
                                <p>Belum ada pelanggan terdaftar.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>

<script>
    function deleteUser(id) {
        Swal.fire({
            title: 'Hapus Pengguna?',
            text: "Data yang dihapus tidak bisa dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'user_delete.php?id=' + id;
            }
        })
    }
</script>

