<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../index.php?modal=login");
    exit;
}
include '../includes/config.php';

// --- FILTERS ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // Default to 1st of current month
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); // Default to today

// Build WHERE Clause
$where = "1=1";
$params = [];
$types = "";

// Date Filter
if ($startDate && $endDate) {
    $where .= " AND DATE(orders.created_at) BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
    $types .= "ss";
}

// Search Filter
if ($search !== '') {
    $where .= " AND (users.full_name LIKE ? OR orders.id LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}

// Status Filter
if ($filter !== 'all') {
    $where .= " AND orders.status = ?";
    $params[] = $filter;
    $types .= "s";
}

// --- PAGINATION ---
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// --- QUERIES ---

// 1. STATS (Filtered by Date Range)
// Note: Stats usually ignore 'status' filter unless explicitly desired. 
// For "Net Income", we only count 'completed'.
$statsWhere = "DATE(created_at) BETWEEN ? AND ?";
$statsParams = [$startDate, $endDate];

// Net Income (Gross - Shopping Cost)
$netSql = "SELECT 
    SUM(price) as gross, 
    SUM(shopping_cost) as cost,
    COUNT(*) as total_orders
    FROM orders 
    WHERE status='completed' AND $statsWhere";
$netStmt = $conn->prepare($netSql);
$netStmt->bind_param("ss", $startDate, $endDate);
$netStmt->execute();
$netRes = $netStmt->get_result()->fetch_assoc();
$netIncomeRange = ($netRes['gross'] ?? 0) - ($netRes['cost'] ?? 0);
$ordersCountRange = $netRes['total_orders'] ?? 0;

// Pending Count (Realtime, ignoring date usually, but let's stick to range if user wants history, 
// actually Pending is usually "Current", so maybe ignore date? 
// User said "view category images FROM DATE X TO Y". This implies stats too.
// But Pending orders from last year aren't useful. 
// Let's keep Pending as GLOBAL (Current active work).
$pendingRes = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE status='pending'")->fetch_assoc();
$pendingCount = $pendingRes['cnt'];

// 2. CHART: Service Distribution (Filtered by Date Range)
$chartSql = "SELECT service_type, COUNT(*) as cnt FROM orders WHERE $statsWhere GROUP BY service_type";
$chartStmt = $conn->prepare($chartSql);
$chartStmt->bind_param("ss", $startDate, $endDate);
$chartStmt->execute();
$chartResult = $chartStmt->get_result();
$chartLabels = [];
$chartData = [];
while($row = $chartResult->fetch_assoc()) {
    $chartLabels[] = $row['service_type'];
    $chartData[] = $row['cnt'];
}

// 3. ORDER LIST (Filtered by Date, Search, Status)
$countSql = "SELECT COUNT(*) as total FROM orders JOIN users ON orders.user_id = users.id WHERE $where";
$countStmt = $conn->prepare($countSql);
if($types) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalOrdersList = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalOrdersList / $limit);

$sql = "SELECT orders.*, users.full_name, users.wa_number 
        FROM orders 
        JOIN users ON orders.user_id = users.id 
        WHERE $where
        ORDER BY orders.created_at DESC 
        LIMIT $limit OFFSET $offset";
$stmt = $conn->prepare($sql);
if($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$pageTitle = 'Dashboard';
$extraHead = '
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />
    <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>
    <style>.leaflet-routing-container { display: none !important; }</style>
';
include 'includes/header.php';
?>

    <!-- Audio & Map Modal -->
    <audio id="notifSound" src="https://actions.google.com/sounds/v1/alarms/beep_short.ogg"></audio>
    <div id="mapModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 transition-all duration-300 opacity-0 scale-95" role="dialog" aria-modal="true">
        <div class="bg-white dark:bg-gray-800 w-full max-w-5xl h-[85vh] rounded-3xl shadow-2xl overflow-hidden flex flex-col relative transition-all duration-300 transform scale-100">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-white dark:bg-gray-900 sticky top-0 z-20 shadow-sm">
                <h3 class="font-extrabold text-xl text-gray-800 dark:text-white">Visualisasi Rute</h3>
                <button onclick="closeMap()" class="text-gray-400 hover:text-red-500"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>
            <div class="flex-1 w-full h-full relative" id="mapContainer">
                <div id="adminMap" class="w-full h-full z-10"></div>
                <div class="absolute bottom-6 left-6 z-[400] bg-white/90 dark:bg-gray-800/90 px-4 py-2 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700">
                    <p class="font-bold text-gray-800 dark:text-white">Jarak: <span id="modalDistance">--</span> | Waktu: <span id="modalDuration">--</span></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 text-white">
        <div>
            <h2 class="text-3xl font-extrabold mb-1">Dashboard</h2>
            <p class="opacity-80">Pantau order dari <?php echo date('d M', strtotime($startDate)); ?> sampai <?php echo date('d M Y', strtotime($endDate)); ?></p>
        </div>
        <div class="mt-4 md:mt-0 flex gap-3">
             <button onclick="checkNewOrders(true)" class="glass bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-bold backdrop-blur-md transition border border-white/20">
                <i class="fa-solid fa-sync"></i> Refresh
            </button>
            <a href="print_report.php?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" target="_blank" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-bold shadow-lg shadow-red-500/30 transition flex items-center gap-2">
                <i class="fa-solid fa-file-pdf"></i> Download PDF
            </a>
        </div>
    </div>

    <!-- Stats & Chart Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        
        <!-- 1. Pending (Action Required) -->
        <div class="relative overflow-hidden group hover:-translate-y-1 transition-all duration-300 rounded-3xl p-6 shadow-xl 
            <?php echo $pendingCount > 0 ? 'bg-gradient-to-br from-red-600 to-orange-600 text-white' : 'bg-gradient-to-br from-emerald-500 to-teal-600 text-white'; ?>">
            <div class="absolute -right-6 -top-6 opacity-20 transform rotate-12 group-hover:scale-110 transition-transform duration-500">
                <i class="fa-solid fa-bell text-9xl"></i>
            </div>
            
            <div class="relative z-10 flex flex-col justify-between h-full">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 backdrop-blur-sm text-xs font-bold uppercase tracking-wider mb-2 border border-white/10">
                        <i class="fa-solid fa-circle-exclamation"></i> Status Order
                    </div>
                    <h3 class="text-5xl font-extrabold mb-1 tracking-tight"><?php echo $pendingCount; ?></h3>
                    <p class="font-medium opacity-90 text-sm"><?php echo $pendingCount > 0 ? 'Menunggu Konfirmasi' : 'Semua Beres!'; ?></p>
                </div>
                <?php if($pendingCount > 0): ?>
                <div class="mt-4">
                    <span class="inline-block px-3 py-1 bg-white text-red-600 text-xs font-bold rounded-lg shadow-sm">Segera Proses!</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2. Net Income (Filtered) -->
        <div class="glass bg-white/80 dark:bg-gray-800/80 p-6 rounded-3xl shadow-xl border border-white/20 dark:border-gray-700/50 backdrop-blur-xl flex flex-col justify-between group hover:-translate-y-1 transition-all duration-300">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center text-2xl shadow-inner group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <span class="text-xs font-bold text-gray-400 bg-gray-100 dark:bg-gray-700/50 px-2 py-1 rounded-lg">
                        <?php echo date('d M', strtotime($startDate)); ?> - <?php echo date('d M', strtotime($endDate)); ?>
                    </span>
                </div>
                <h3 class="text-3xl font-extrabold text-gray-800 dark:text-white mb-1">Rp <?php echo number_format($netIncomeRange, 0, ',', '.'); ?></h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Pendapatan Bersih</p>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700/50 flex items-center gap-2 text-green-500 text-sm font-bold">
                <i class="fa-solid fa-arrow-trend-up"></i>
                <span><?php echo $ordersCountRange; ?> Order Selesai</span>
            </div>
        </div>

        <!-- 3. Chart: Service Type -->
        <div class="md:col-span-2 glass bg-white/80 dark:bg-gray-800/80 p-6 rounded-3xl shadow-xl border border-white/20 dark:border-gray-700/50 backdrop-blur-xl flex items-center justify-between group hover:-translate-y-1 transition-all duration-300">
            <div class="w-1/2 pr-4 border-r border-gray-100 dark:border-gray-700/50 mr-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-xl bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 flex items-center justify-center shadow-sm">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <h4 class="font-bold text-lg text-gray-800 dark:text-white">Tren Layanan</h4>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed mb-4">Analisis distribusi pesanan berdasarkan kategori layanan untuk periode terpilih.</p>
                <div class="flex flex-wrap gap-2 text-xs font-bold text-gray-400">
                     <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded-md">Total: <?php echo array_sum($chartData); ?> Order</span>
                </div>
            </div>
            <div class="h-40 w-1/2 relative">
                <canvas id="serviceChart"></canvas>
            </div>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="glass bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-lg p-5 mb-8 border border-white/20 dark:border-gray-700/50 backdrop-blur-md">
        <form method="GET" class="flex flex-col xl:flex-row gap-4 items-center">
            
            <!-- Date Picker -->
            <div class="flex items-center gap-2 bg-gray-50 dark:bg-gray-900/50 p-2.5 rounded-xl border border-gray-200 dark:border-gray-700 w-full xl:w-auto transition focus-within:ring-2 focus-within:ring-blue-500/20">
                <span class="text-gray-400 text-xs font-bold px-2 uppercase tracking-wide">Periode</span>
                <input type="date" name="start_date" value="<?php echo $startDate; ?>" class="bg-transparent text-sm font-bold text-gray-700 dark:text-gray-200 focus:outline-none cursor-pointer">
                <span class="text-gray-400">-</span>
                <input type="date" name="end_date" value="<?php echo $endDate; ?>" class="bg-transparent text-sm font-bold text-gray-700 dark:text-gray-200 focus:outline-none cursor-pointer">
            </div>

            <!-- Search -->
            <div class="flex-1 relative w-full group">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 w-8 h-8 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded-lg text-gray-400 transition-colors group-focus-within:bg-blue-100 group-focus-within:text-blue-500">
                    <i class="fa-solid fa-search text-xs"></i>
                </div>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari nama pelanggan, ID order..." class="w-full pl-14 pr-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition text-sm font-medium">
            </div>

            <!-- Status -->
            <div class="relative w-full xl:w-auto">
                <select name="filter" class="w-full xl:w-48 appearance-none px-4 py-3 pl-10 rounded-xl bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 text-sm font-bold focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition cursor-pointer">
                    <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>Semua Status</option>
                    <option value="pending" <?php echo $filter == 'pending' ? 'selected' : ''; ?>>Menunggu</option>
                    <option value="accepted" <?php echo $filter == 'accepted' ? 'selected' : ''; ?>>Diproses</option>
                    <option value="completed" <?php echo $filter == 'completed' ? 'selected' : ''; ?>>Selesai</option>
                </select>
                <i class="fa-solid fa-filter absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <i class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-xs text-gray-400 pointer-events-none"></i>
            </div>

            <button type="submit" class="w-full xl:w-auto bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-xl font-bold shadow-lg shadow-blue-500/30 transition transform hover:scale-105 active:scale-95 flex items-center justify-center gap-2">
                <i class="fa-solid fa-rotate"></i> Terapkan
            </button>
        </form>
    </div>

    <!-- ORDER TABLE -->
    <div class="glass bg-white/80 dark:bg-gray-800/80 rounded-3xl shadow-xl overflow-hidden border border-white/20 dark:border-gray-700/50 backdrop-blur-xl">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                        <th class="px-6 py-4 text-xs font-extrabold text-gray-400 text-left uppercase tracking-wider">Pelanggan & Order</th>
                        <th class="px-6 py-4 text-xs font-extrabold text-gray-400 text-left uppercase tracking-wider">Detail Layanan</th>
                        <th class="px-6 py-4 text-xs font-extrabold text-gray-400 text-left uppercase tracking-wider">Jarak & Rute</th>
                        <th class="px-6 py-4 text-xs font-extrabold text-gray-400 text-center uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-xs font-extrabold text-gray-400 text-right uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    <?php if ($result->num_rows > 0): ?>
                        <?php 
                        $i = 0;
                        while($row = $result->fetch_assoc()): 
                            $i++;
                            $waNumber = preg_replace('/^0/', '62', $row['wa_number']);
                            $waLink = "https://wa.me/$waNumber";
                        ?>
                        <tr class="hover:bg-blue-50/30 dark:hover:bg-gray-700/30 transition duration-200 group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white flex items-center justify-center font-bold shadow-lg shadow-blue-500/20 group-hover:scale-110 transition-transform">
                                        <?php echo strtoupper(substr($row['full_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <p class="font-extrabold text-gray-800 dark:text-gray-100 text-sm mb-0.5"><?php echo htmlspecialchars($row['full_name']); ?></p>
                                        <div class="flex items-center gap-2 text-xs text-gray-400 font-medium">
                                            <span class="bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded text-[10px]">#<?php echo $row['id']; ?></span>
                                            <span><?php echo date('d M, H:i', strtotime($row['created_at'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-block px-2.5 py-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg text-[10px] font-extrabold uppercase tracking-wide mb-1.5 border border-indigo-100 dark:border-indigo-800">
                                    <?php echo $row['service_type']; ?>
                                </span>
                                <div class="flex flex-col">
                                    <span class="text-sm font-extrabold text-gray-800 dark:text-white">
                                        Rp <?php echo number_format($row['price'], 0, ',', '.'); ?>
                                    </span>
                                    <?php if($row['shopping_cost'] > 0): ?>
                                        <span class="text-[10px] text-red-500 font-bold bg-red-50 dark:bg-red-900/20 px-1.5 py-0.5 rounded w-fit mt-0.5 border border-red-100 dark:border-red-800/50">
                                            + Belanja Rp <?php echo number_format($row['shopping_cost'], 0, ',', '.'); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <button onclick="showRoute(<?php echo $row['pickup_lat']; ?>, <?php echo $row['pickup_lng']; ?>, <?php echo $row['dropoff_lat']; ?>, <?php echo $row['dropoff_lng']; ?>)" class="text-xs font-bold bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 px-3 py-1.5 rounded-lg border border-blue-100 dark:border-blue-800 hover:bg-blue-100 dark:hover:bg-blue-900/40 transition flex items-center w-fit gap-2 group-hover:shadow-sm">
                                    <i class="fa-solid fa-map-location-dot"></i> 
                                    <span><?php echo $row['distance_km']; ?> km</span>
                                </button>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php 
                                    $statusStyle = match($row['status']) {
                                        'pending' => 'bg-yellow-100/50 text-yellow-700 border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-400 dark:border-yellow-700',
                                        'accepted' => 'bg-blue-100/50 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-700',
                                        'completed' => 'bg-green-100/50 text-green-700 border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-700',
                                        'cancelled' => 'bg-red-100/50 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-700',
                                        default => 'bg-gray-100 text-gray-700 border-gray-200'
                                    };
                                    $statusIcon = match($row['status']) {
                                        'pending' => 'fa-clock',
                                        'accepted' => 'fa-spinner fa-spin',
                                        'completed' => 'fa-check-circle',
                                        'cancelled' => 'fa-ban',
                                        default => 'fa-circle'
                                    };
                                ?>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wide border <?php echo $statusStyle; ?>">
                                    <i class="fa-solid <?php echo $statusIcon; ?>"></i>
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <?php 
                                // --- GEN Z WA TEMPLATES ---
                                $custName = explode(' ', trim($row['full_name']))[0]; // First name only
                                $orderId = $row['id'];
                                $waMsg = "";

                                switch($row['status']) {
                                    case 'pending':
                                        $waMsg = "Halo kak *$custName*! 👋\nPesanan *#$orderId* udah masuk nih di MasTolongMas.\n\nMau kita proses sekarang? Konfirmasi yaa! 🚀✨";
                                        break;
                                    case 'accepted':
                                        $waMsg = "Gas kak *$custName*! 🛵💨\nPesanan *#$orderId* lagi jalan nih sama driver kita.\n\nDitunggu ya, bentar lagi sampe! 😎📦";
                                        break;
                                    case 'completed':
                                        $waMsg = "Yuhuu kak *$custName*! 🎉\nPesanan *#$orderId* udah beres ya.\n\nMakasih banyak udah percaya sama MasTolongMas! Ditunggu next ordernya! ⭐🔥";
                                        break;
                                    case 'cancelled':
                                        $waMsg = "Yah... pesanan *#$orderId* batal ya kak *$custName*? 😢\n\nKalo ada kendala atau mau order ulang, kabarin admin ya! Kita siap bantu! 💪";
                                        break;
                                    default:
                                        $waMsg = "Halo kak *$custName*, ini admin MasTolongMas. Ada yg bisa dibantu utk pesanan *#$orderId*? 🤔";
                                }
                                $waLink = "https://wa.me/$waNumber?text=" . urlencode($waMsg);
                            ?>

                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2 opacity-80 group-hover:opacity-100 transition-opacity">
                                    <?php if($row['status'] == 'pending'): ?>
                                        <button onclick="updateStatus(<?php echo $row['id']; ?>, 'accepted')" class="w-8 h-8 flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-lg shadow-blue-500/30 transition transform hover:scale-110 active:scale-95" title="Terima Order"><i class="fa-solid fa-check text-xs"></i></button>
                                    <?php elseif($row['status'] == 'accepted'): ?>
                                        <button onclick="updateStatus(<?php echo $row['id']; ?>, 'completed')" class="w-8 h-8 flex items-center justify-center bg-green-500 hover:bg-green-600 text-white rounded-lg shadow-lg shadow-green-500/30 transition transform hover:scale-110 active:scale-95" title="Selesaikan"><i class="fa-solid fa-flag-checkered text-xs"></i></button>
                                    <?php endif; ?>

                                    <a href="order_detail.php?id=<?php echo $row['id']; ?>" class="w-8 h-8 flex items-center justify-center bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 rounded-lg border border-gray-200 dark:border-gray-600 transition transform hover:scale-110 active:scale-95" title="Lihat Detail">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>

                                    <a href="<?php echo $waLink; ?>" target="_blank" class="w-8 h-8 flex items-center justify-center bg-green-100 text-green-600 hover:bg-green-200 rounded-lg border border-green-200 transition transform hover:scale-110 active:scale-95" title="Chat WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
                                    
                                    <button onclick="deleteOrder(<?php echo $row['id']; ?>)" class="w-8 h-8 flex items-center justify-center bg-red-50 text-red-500 hover:bg-red-100 rounded-lg border border-red-200 transition transform hover:scale-110 active:scale-95" title="Hapus"><i class="fa-solid fa-trash text-xs"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                                        <i class="fa-solid fa-folder-open text-2xl opacity-50"></i>
                                    </div>
                                    <p class="font-bold">Tidak ada data ditemukan</p>
                                    <p class="text-sm opacity-70">Coba ubah filter atau periode tanggal.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if($totalPages > 1): ?>
        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700/50 flex justify-center gap-2 bg-gray-50/30 dark:bg-gray-900/30 backdrop-blur-sm">
             <?php 
                // Previous
                $prevClass = "px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm font-bold shadow-sm transition hover:bg-gray-50 dark:hover:bg-gray-700";
                $activeClass = "px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-blue-500/30 border border-blue-500";
                $disabledClass = "px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-400 rounded-xl text-sm font-bold cursor-not-allowed border border-transparent";
                
                $prevQ = http_build_query(array_merge($_GET, ['page' => $page-1]));
                echo ($page > 1) ? "<a href='?$prevQ' class='$prevClass'><i class='fa-solid fa-chevron-left mr-1'></i> Prev</a>" : "<span class='$disabledClass'><i class='fa-solid fa-chevron-left mr-1'></i> Prev</span>";
                
                // Page Numbers logic could go here, simplified just Prev/Next for now
                
                $nextQ = http_build_query(array_merge($_GET, ['page' => $page+1]));
                echo ($page < $totalPages) ? "<a href='?$nextQ' class='$prevClass'>Next <i class='fa-solid fa-chevron-right ml-1'></i></a>" : "<span class='$disabledClass'>Next <i class='fa-solid fa-chevron-right ml-1'></i></span>";
             ?>
        </div>
        <?php endif; ?>
    </div>

<script>
    // --- MAP LOGIC ---
    let map, routingControl;
    function showRoute(pLat, pLng, dLat, dLng) {
        $('#mapModal').removeClass('hidden').animate({opacity:1}, 200);
        setTimeout(() => {
            if(!map) {
                map = L.map('adminMap').setView([pLat, pLng], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
            }
            if(routingControl) map.removeControl(routingControl);
            
            routingControl = L.Routing.control({
                waypoints: [L.latLng(pLat, pLng), L.latLng(dLat, dLng)],
                router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1' }),
                lineOptions: { styles: [{color: '#3B82F6', opacity: 0.8, weight: 6}] },
                createMarker: function(i, wp) {
                    return L.marker(wp.latLng).bindPopup(i === 0 ? 'Pickup' : 'Dropoff');
                }
            }).on('routesfound', function(e) {
                const s = e.routes[0].summary;
                $('#modalDistance').text((s.totalDistance/1000).toFixed(1) + ' km');
                $('#modalDuration').text(Math.round(s.totalTime/60) + ' mnt');
            }).addTo(map);
        }, 100);
    }
    function closeMap() { $('#mapModal').addClass('hidden'); }

    // --- CHART ---
    new Chart(document.getElementById('serviceChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($chartLabels); ?>,
            datasets: [{ 
                data: <?php echo json_encode($chartData); ?>, 
                backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444'],
                borderWidth:0 
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    // --- ACTIONS ---
    function updateStatus(id, stat) {
        // ... (Keep existing ajax)
        $.post('update_status.php', {id: id, status: stat}, (res) => {
            location.reload();
        }, 'json');
    }

    function checkNewOrders() {
        // ... (Keep existing logic)
        $.get('check_new_orders.php', (res) => {
             if(res.pending_count > <?php echo $pendingCount; ?>) location.reload();
        }, 'json');
    }
    setInterval(checkNewOrders, 10000);

    // --- DELETE FUNCTION ---
    function deleteOrder(id) {
        Swal.fire({
            title: 'Hapus Order?',
            text: "Data yang dihapus tidak bisa dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('delete_order.php', {id: id}, (res) => {
                    if(res.status === 'success') {
                        Swal.fire('Terhapus!', 'Order berhasil dihapus.', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Gagal!', res.message, 'error');
                    }
                }, 'json');
            }
        })
    }
</script>
<?php include 'includes/footer.php'; ?>

