<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: ../index.php?modal=login"); exit; }
include '../includes/config.php';

// Revenue: Last 7 days (NET INCOME)
$days = [];
$dayRevenue = [];
$dayOrders = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('d M', strtotime("-$i days"));
    $days[] = $label;
    
    $r = $conn->query("SELECT COALESCE(SUM(price - shopping_cost), 0) as rev, COUNT(*) as cnt FROM orders WHERE status='completed' AND DATE(created_at) = '$date'");
    $row = $r->fetch_assoc();
    $dayRevenue[] = (float)$row['rev'];
    $dayOrders[] = (int)$row['cnt'];
}



// Summary Stats (NET INCOME)
$todayRev = $conn->query("SELECT COALESCE(SUM(price - shopping_cost),0) as r FROM orders WHERE status='completed' AND DATE(created_at)=CURDATE()")->fetch_assoc()['r'];
$weekRev = $conn->query("SELECT COALESCE(SUM(price - shopping_cost),0) as r FROM orders WHERE status='completed' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetch_assoc()['r'];
$monthRev = $conn->query("SELECT COALESCE(SUM(price - shopping_cost),0) as r FROM orders WHERE status='completed' AND MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())")->fetch_assoc()['r'];
$allRev = $conn->query("SELECT COALESCE(SUM(price - shopping_cost),0) as r FROM orders WHERE status='completed'")->fetch_assoc()['r'];

$todayOrders = $conn->query("SELECT COUNT(*) as c FROM orders WHERE DATE(created_at)=CURDATE()")->fetch_assoc()['c'];
$completedOrders = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status='completed'")->fetch_assoc()['c'];
$allOrders = $conn->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'];
$completionRate = $allOrders > 0 ? round(($completedOrders / $allOrders) * 100) : 0;

// Top Service
$topSvc = $conn->query("SELECT service_type, COUNT(*) as cnt FROM orders WHERE status='completed' GROUP BY service_type ORDER BY cnt DESC LIMIT 1")->fetch_assoc();
$topServiceName = $topSvc ? $topSvc['service_type'] : '-';
$topServiceCount = $topSvc ? $topSvc['cnt'] : 0;

// Service Popularity (Pie Chart Data)
$svcLabels = [];
$svcData = [];
$svcRes = $conn->query("SELECT service_type, COUNT(*) as cnt FROM orders WHERE status='completed' GROUP BY service_type");
while($row = $svcRes->fetch_assoc()) {
    $svcLabels[] = $row['service_type'];
    $svcData[] = $row['cnt'];
}

// Monthly Revenue (last 6 months - NET INCOME)
$months = [];
$monthlyRev = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));
    $months[] = $label;
    $r = $conn->query("SELECT COALESCE(SUM(price - shopping_cost),0) as r FROM orders WHERE status='completed' AND DATE_FORMAT(created_at, '%Y-%m') = '$m'");
    $monthlyRev[] = (float)$r->fetch_assoc()['r'];
}

$pageTitle = 'Laporan Keuangan';
include 'includes/header.php';
?>

    <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 text-white" data-aos="fade-down">
        <div>
            <h2 class="text-3xl font-extrabold mb-1">📊 Laporan Pendapatan Bersih</h2>
            <p class="opacity-80">Analisis PROFIT MURNI (Jasa Driver) - Tanpa Uang Belanja</p>
        </div>
    </div>

    <!-- Revenue Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Daily -->
        <div class="glass bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-xl border-t-4 border-blue-500 relative overflow-hidden group hover:-translate-y-1 transition duration-300">
            <div class="absolute -right-6 -top-6 opacity-5 dark:opacity-10 group-hover:opacity-10 transition"><i class="fa-solid fa-calendar-day text-9xl text-blue-500"></i></div>
            <p class="text-gray-400 dark:text-gray-500 text-xs font-bold uppercase tracking-widest">Hari Ini</p>
            <h3 class="text-2xl font-extrabold text-blue-600 dark:text-blue-400 mt-2">Rp <?php echo number_format($todayRev, 0, ',', '.'); ?></h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 font-medium mt-1"><span class="text-blue-500 font-bold"><?php echo $todayOrders; ?></span> order baru</p>
        </div>

        <!-- Weekly -->
        <div class="glass bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-xl border-t-4 border-green-500 relative overflow-hidden group hover:-translate-y-1 transition duration-300">
             <div class="absolute -right-6 -top-6 opacity-5 dark:opacity-10 group-hover:opacity-10 transition"><i class="fa-solid fa-calendar-week text-9xl text-green-500"></i></div>
            <p class="text-gray-400 dark:text-gray-500 text-xs font-bold uppercase tracking-widest">Minggu Ini</p>
            <h3 class="text-2xl font-extrabold text-green-600 dark:text-green-400 mt-2">Rp <?php echo number_format($weekRev, 0, ',', '.'); ?></h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 font-medium mt-1">Stabilitas Terjaga</p>
        </div>

        <!-- Monthly -->
        <div class="glass bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-xl border-t-4 border-purple-500 relative overflow-hidden group hover:-translate-y-1 transition duration-300">
             <div class="absolute -right-6 -top-6 opacity-5 dark:opacity-10 group-hover:opacity-10 transition"><i class="fa-solid fa-chart-pie text-9xl text-purple-500"></i></div>
            <p class="text-gray-400 dark:text-gray-500 text-xs font-bold uppercase tracking-widest">Bulan Ini</p>
            <h3 class="text-2xl font-extrabold text-purple-600 dark:text-purple-400 mt-2">Rp <?php echo number_format($monthRev, 0, ',', '.'); ?></h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 font-medium mt-1">Layanan Terlaris: <span class="font-bold text-purple-500"><?php echo $topServiceName; ?></span></p>
        </div>

        <!-- Total -->
        <div class="bg-gradient-to-br from-gray-800 to-gray-900 dark:from-blue-900 dark:to-indigo-900 p-6 rounded-3xl shadow-xl text-white relative overflow-hidden group hover:-translate-y-1 transition duration-300">
            <div class="absolute -right-6 -top-6 opacity-20"><i class="fa-solid fa-trophy text-9xl text-yellow-500"></i></div>
            <p class="text-gray-400 text-xs font-bold uppercase tracking-widest">Total Revenue</p>
            <h3 class="text-3xl font-extrabold text-white mt-1">Rp <?php echo number_format($allRev, 0, ',', '.'); ?></h3>
            <div class="flex items-center gap-2 mt-2">
                <span class="px-2 py-0.5 bg-white/20 rounded text-[10px] font-bold"><?php echo $completedOrders; ?> Order Sukses</span>
                <span class="text-xs text-green-400 font-bold"><?php echo $completionRate; ?>% Rate</span>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Daily Revenue Chart -->
        <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
            <h4 class="text-sm font-bold text-gray-800 dark:text-white mb-4"><i class="fa-solid fa-chart-bar text-blue-500 mr-2"></i>Pendapatan 7 Hari Terakhir</h4>
            <canvas id="dailyRevenueChart" height="200"></canvas>
        </div>

        <!-- Monthly Revenue Chart -->
        <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
            <h4 class="text-sm font-bold text-gray-800 dark:text-white mb-4"><i class="fa-solid fa-chart-line text-green-500 mr-2"></i>Pendapatan 6 Bulan Terakhir</h4>
            <canvas id="monthlyRevenueChart" height="200"></canvas>
        </div>
    </div>

    <!-- Service Popularity -->
    <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-8">
        <h4 class="text-sm font-bold text-gray-800 dark:text-white mb-4"><i class="fa-solid fa-chart-pie text-purple-500 mr-2"></i>Layanan Terpopuler</h4>
        <div class="h-64">
            <canvas id="servicePopChart"></canvas>
        </div>
    </div>

    <!-- Recent Transactions Table -->
    <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden mb-8">
        <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
            <h4 class="text-lg font-bold text-gray-800 dark:text-white"><i class="fa-solid fa-receipt text-blue-500 mr-2"></i>Transaksi Terakhir</h4>
            <a href="dashboard.php" class="text-sm text-blue-500 hover:text-blue-600 font-bold">Lihat Semua →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-100 dark:border-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-400 uppercase">Order ID</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-400 uppercase">Pelanggan</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-400 uppercase">Layanan</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-400 uppercase">Total</th>
                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-400 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php 
                    $recent = $conn->query("SELECT o.*, u.full_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5");
                    while($r = $recent->fetch_assoc()):
                    ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="px-6 py-4 text-sm font-bold text-gray-800 dark:text-gray-200">#<?php echo $r['id']; ?></td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300"><?php echo htmlspecialchars($r['full_name']); ?></td>
                        <td class="px-6 py-4 text-sm"><span class="px-2 py-1 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded text-xs font-bold"><?php echo $r['service_type']; ?></span></td>
                        <td class="px-6 py-4 text-sm text-right font-bold text-gray-800 dark:text-white">Rp <?php echo number_format($r['price'], 0, ',', '.'); ?></td>
                        <td class="px-6 py-4 text-center">
                            <?php 
                                $color = match($r['status']) {
                                    'completed' => 'text-green-500', 
                                    'pending' => 'text-yellow-500', 
                                    'accepted' => 'text-blue-500', 
                                    'cancelled' => 'text-red-500',
                                    default => 'text-gray-500'
                                };
                            ?>
                            <span class="text-xs font-bold <?php echo $color; ?> uppercase"><?php echo $r['status']; ?></span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>



<script>
    // Daily Revenue
    new Chart(document.getElementById('dailyRevenueChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($days); ?>,
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: <?php echo json_encode($dayRevenue); ?>,
                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                borderColor: '#3B82F6',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: v => 'Rp ' + v.toLocaleString('id-ID') } },
                x: { grid: { display: false } }
            }
        }
    });

    // Monthly Revenue
    new Chart(document.getElementById('monthlyRevenueChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: <?php echo json_encode($monthlyRev); ?>,
                borderColor: '#10B981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#10B981',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: v => 'Rp ' + v.toLocaleString('id-ID') } },
                x: { grid: { display: false } }
            }
        }
    });



    // Service Popularity
    new Chart(document.getElementById('servicePopChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($svcLabels); ?>,
            datasets: [{
                data: <?php echo json_encode($svcData); ?>,
                backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { position: 'right', labels: { boxWidth: 10, usePointStyle: true, font: { size: 10 } } } 
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>

