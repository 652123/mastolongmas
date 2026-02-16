<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?login=true");
    exit;
}
include 'includes/config.php';

$user_id = $_SESSION['user_id'];

// Filters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build Query
$where = "user_id = ?";
$params = [$user_id];
$types = "i";

if ($statusFilter !== 'all') {
    $where .= " AND status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

// Fetch Orders
$orderStmt = $conn->prepare("SELECT * FROM orders WHERE $where ORDER BY created_at DESC");
$orderStmt->bind_param($types, ...$params);
$orderStmt->execute();
$resultOrders = $orderStmt->get_result();

// Count per status
$cntStmt = $conn->prepare("SELECT status, COUNT(*) as cnt FROM orders WHERE user_id = ? GROUP BY status");
$cntStmt->bind_param("i", $user_id);
$cntStmt->execute();
$cntResult = $cntStmt->get_result();
$statusCounts = ['all' => 0, 'pending' => 0, 'accepted' => 0, 'completed' => 0, 'cancelled' => 0];
while($c = $cntResult->fetch_assoc()) {
    $statusCounts[$c['status']] = (int)$c['cnt'];
    $statusCounts['all'] += (int)$c['cnt'];
}

include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="pt-28 pb-20 bg-gradient-to-br from-gray-50 via-blue-50/30 to-orange-50/20 dark:from-brand-darker dark:via-gray-900 dark:to-gray-900 min-h-screen transition-colors duration-300">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">

            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <a href="profile.php" class="w-8 h-8 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl flex items-center justify-center text-gray-400 hover:text-brand-orange hover:border-orange-200 transition shadow-sm">
                            <i class="fa-solid fa-arrow-left text-xs"></i>
                        </a>
                        <h1 class="text-2xl md:text-3xl font-extrabold text-gray-800 dark:text-white tracking-tight">Riwayat Pesanan</h1>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm ml-11">Pantau semua pesananmu di sini.</p>
                </div>
                <a href="order.php" class="bg-brand-orange hover:bg-orange-600 text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-orange-500/30 transition transform hover:scale-105 active:scale-95 flex items-center gap-2 text-sm whitespace-nowrap">
                    <i class="fa-solid fa-plus"></i> Order Baru
                </a>
            </div>

            <!-- Status Filter Tabs -->
            <div class="flex gap-2 mb-6 overflow-x-auto pb-2 scrollbar-hide">
                <?php
                $tabs = [
                    'all' => ['Semua', 'fa-border-all', 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700'],
                    'pending' => ['Menunggu', 'fa-clock', 'bg-yellow-50 text-yellow-700 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400 dark:border-yellow-700'],
                    'accepted' => ['Diproses', 'fa-spinner', 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-700'],
                    'completed' => ['Selesai', 'fa-check-circle', 'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-700'],
                    'cancelled' => ['Dibatalkan', 'fa-ban', 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-700'],
                ];
                foreach($tabs as $key => $tab):
                    $isActive = ($statusFilter === $key);
                    $activeClass = $isActive ? 'ring-2 ring-brand-orange shadow-md scale-105' : 'hover:shadow-sm';
                ?>
                <a href="?status=<?php echo $key; ?>" class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold border whitespace-nowrap transition-all transform <?php echo $tab[2]; ?> <?php echo $activeClass; ?>">
                    <i class="fa-solid <?php echo $tab[1]; ?>"></i>
                    <?php echo $tab[0]; ?>
                    <span class="bg-white/60 dark:bg-black/30 backdrop-blur-sm px-1.5 py-0.5 rounded-md text-[10px] font-extrabold"><?php echo $statusCounts[$key]; ?></span>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Order Cards -->
            <div class="space-y-4">
                <?php if ($resultOrders->num_rows > 0): ?>
                    <?php while($order = $resultOrders->fetch_assoc()): ?>
                    <?php 
                        $statusInfo = match($order['status']) {
                            'pending' => ['bg-yellow-50 text-yellow-700 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400 dark:border-yellow-700', 'Menunggu', 'fa-clock', 'border-l-yellow-400'],
                            'accepted' => ['bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-700', 'Diproses', 'fa-spinner', 'border-l-blue-400'],
                            'completed' => ['bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-700', 'Selesai', 'fa-check-circle', 'border-l-green-500'],
                            'cancelled' => ['bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-700', 'Dibatalkan', 'fa-ban', 'border-l-red-400'],
                            default => ['bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700', $order['status'], 'fa-circle', 'border-l-gray-400']
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
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 dark:border-gray-700 border-l-4 <?php echo $statusInfo[3]; ?> overflow-hidden group">
                        <div class="p-5 md:p-6">
                            <div class="flex flex-col md:flex-row justify-between md:items-start gap-4">
                                <!-- Left: Order Details -->
                                <div class="flex items-start gap-4 flex-1 min-w-0">
                                    <div class="w-12 h-12 rounded-2xl bg-gray-50 dark:bg-gray-700 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                                        <i class="fa-solid <?php echo $icon; ?> text-xl"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                                            <h3 class="font-extrabold text-gray-800 dark:text-white text-sm"><?php echo htmlspecialchars($order['service_type']); ?></h3>
                                            <span class="text-[10px] text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded font-bold">#<?php echo $order['id']; ?></span>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border <?php echo $statusInfo[0]; ?>">
                                                <i class="fa-solid <?php echo $statusInfo[2]; ?>"></i>
                                                <?php echo $statusInfo[1]; ?>
                                            </span>
                                        </div>

                                        <!-- Locations -->
                                        <div class="space-y-1.5 text-sm">
                                            <div class="flex items-start gap-2 text-gray-600 dark:text-gray-300">
                                                <span class="w-5 h-5 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                                    <i class="fa-solid fa-location-dot text-green-500 text-[10px]"></i>
                                                </span>
                                                <span class="truncate"><?php echo htmlspecialchars($order['pickup_location']); ?></span>
                                            </div>
                                            <div class="flex items-start gap-2 text-gray-600 dark:text-gray-300">
                                                <span class="w-5 h-5 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                                    <i class="fa-solid fa-flag-checkered text-red-500 text-[10px]"></i>
                                                </span>
                                                <span class="truncate"><?php echo htmlspecialchars($order['dropoff_location']); ?></span>
                                            </div>
                                        </div>

                                        <!-- Meta -->
                                        <div class="flex flex-wrap items-center gap-3 mt-3 text-[10px] text-gray-400 font-medium">
                                            <span class="flex items-center gap-1"><i class="fa-regular fa-calendar"></i> <?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?></span>
                                            <span class="flex items-center gap-1"><i class="fa-solid fa-road"></i> <?php echo $order['distance_km']; ?> km</span>
                                            <?php if(isset($order['payment_method'])): ?>
                                            <span class="flex items-center gap-1">
                                                <i class="fa-solid <?php echo $order['payment_method'] == 'transfer' ? 'fa-building-columns' : 'fa-money-bill-wave'; ?>"></i>
                                                <?php echo $order['payment_method'] == 'transfer' ? 'Transfer' : 'Tunai'; ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right: Price -->
                                <div class="text-right flex-shrink-0 md:ml-4">
                                    <p class="text-xl font-extrabold text-gray-800 dark:text-brand-orange">Rp <?php echo number_format($order['price'], 0, ',', '.'); ?></p>
                                    <?php if($order['shopping_cost'] > 0): ?>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        <span class="text-red-500 font-bold">+ Belanja Rp <?php echo number_format($order['shopping_cost'], 0, ',', '.'); ?></span>
                                    </p>
                                    <?php endif; ?>
                                    <?php if($order['status'] == 'pending'): ?>
                                    <div class="mt-2 flex flex-col items-end gap-2">
                                        <div class="flex items-center gap-1 justify-end text-brand-orange text-xs font-bold animate-pulse">
                                            <i class="fa-solid fa-satellite-dish"></i> Mencari mitra...
                                        </div>
                                        <button onclick="cancelOrder(<?php echo $order['id']; ?>)" class="bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800 hover:bg-red-100 dark:hover:bg-red-900/40 px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-sm flex items-center gap-1">
                                            <i class="fa-solid fa-ban"></i> Batalkan
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="w-24 h-24 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-5">
                            <i class="fa-solid fa-basket-shopping text-4xl text-gray-300 dark:text-gray-500"></i>
                        </div>
                        <h3 class="text-xl font-extrabold text-gray-500 dark:text-gray-400 mb-2">Tidak ada pesanan</h3>
                        <p class="text-gray-400 dark:text-gray-500 mb-6 text-sm max-w-xs mx-auto">
                            <?php echo $statusFilter !== 'all' ? 'Tidak ada pesanan dengan status "' . $tabs[$statusFilter][0] . '".' : 'Kamu belum pernah melakukan pemesanan.'; ?>
                        </p>
                        <a href="order.php" class="inline-block bg-brand-orange text-white px-6 py-3 rounded-xl font-bold hover:bg-orange-600 transition shadow-lg shadow-orange-500/30 text-sm transform hover:scale-105 active:scale-95">
                            <i class="fa-solid fa-plus mr-2"></i> Mulai Order
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<style>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<script>
function cancelOrder(id) {
    MasAlert.fire({
        title: 'Batalkan Pesanan?',
        text: "Yakin mau batalin pesanan ini?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Batalkan',
        cancelButtonText: 'Gak Jadi',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            MasAlert.fire({
                title: 'Memproses...',
                text: 'Mohon tunggu sebentar',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => { Swal.showLoading() }
            });

            // Use Fetch API (jQuery might not be available)
            const formData = new FormData();
            formData.append('id', id);

            fetch('user_cancel_order.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(res => {
                if(res.status === 'success') {
                    MasAlert.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    MasAlert.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: res.message
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                MasAlert.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Terjadi kesalahan pada server.'
                });
            });
        }
    })
}
</script>

<?php include 'includes/footer.php'; ?>
