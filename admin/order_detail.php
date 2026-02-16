<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: ../index.php?modal=login"); exit; }
include '../includes/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: dashboard.php"); exit; }

$stmt = $conn->prepare("SELECT orders.*, users.full_name, users.wa_number, users.username 
                         FROM orders JOIN users ON orders.user_id = users.id 
                         WHERE orders.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) { header("Location: dashboard.php"); exit; }

$pageTitle = 'Order #' . $id;
$extraHead = '
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />
    <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>
    <style>.leaflet-routing-container { display: none !important; }</style>
';
include 'includes/header.php';
?>

    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-white/80 text-sm mb-6" data-aos="fade-down">
        <a href="dashboard.php" class="hover:text-white transition">Dashboard</a>
        <i class="fa-solid fa-chevron-right text-xs"></i>
        <span class="text-white font-bold">Order #<?php echo $id; ?></span>
    </div>

    <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 text-white" data-aos="fade-down" data-aos-delay="100">
        <div>
            <h2 class="text-3xl font-extrabold mb-1">📋 Detail Order #<?php echo $id; ?></h2>
            <p class="opacity-80"><?php echo date('d F Y, H:i', strtotime($order['created_at'])); ?> WIB</p>
        </div>
        <div class="mt-4 md:mt-0 flex gap-3">
            <?php if($order['status'] == 'pending'): ?>
                <button onclick="updateStatus(<?php echo $id; ?>, 'accepted')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-bold shadow-lg transition">
                    <i class="fa-solid fa-check"></i> Terima
                </button>
                <button onclick="updateStatus(<?php echo $id; ?>, 'cancelled')" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg font-bold shadow-lg transition">
                    <i class="fa-solid fa-xmark"></i> Tolak
                </button>
            <?php elseif($order['status'] == 'accepted'): ?>
                <button onclick="updateStatus(<?php echo $id; ?>, 'completed')" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg font-bold shadow-lg transition">
                    <i class="fa-solid fa-flag-checkered"></i> Selesaikan
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Left Column: Info -->
        <div class="md:col-span-1 space-y-6" data-aos="fade-right" data-aos-delay="200">
            <!-- Customer Card -->
            <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h4 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-4">Info Pelanggan</h4>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 text-white flex items-center justify-center font-bold text-lg shadow-lg">
                        <?php echo strtoupper(substr($order['full_name'], 0, 1)); ?>
                    </div>
                    <div>
                        <p class="font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($order['full_name']); ?></p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">@<?php echo htmlspecialchars($order['username']); ?></p>
                    </div>
                </div>
                <?php 
                    if($order['wa_number']): 
                        // Format Number
                        $waNumber = preg_replace('/^0/', '62', $order['wa_number']);
                        
                        // Gen Z Template
                        $custName = explode(' ', trim($order['full_name']))[0];
                        $orderId = $order['id'];
                        $waMsg = "";
                        
                        switch($order['status']) {
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
                    <a href="<?php echo $waLink; ?>" target="_blank" class="w-full flex items-center justify-center gap-2 bg-green-500 hover:bg-green-600 text-white py-2.5 rounded-xl font-bold shadow-lg shadow-green-500/30 transition">
                        <i class="fa-brands fa-whatsapp text-lg"></i> Chat WhatsApp
                    </a>
                <?php endif; ?>
            </div>

            <!-- Status Card -->
            <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h4 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-4">Status & Pembayaran</h4>
                <?php 
                    $sc = 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400'; $si = 'fa-clock';
                    if($order['status'] == 'accepted') { $sc = 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400'; $si = 'fa-spinner'; }
                    if($order['status'] == 'completed') { $sc = 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400'; $si = 'fa-check-circle'; }
                    if($order['status'] == 'cancelled') { $sc = 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'; $si = 'fa-times-circle'; }
                ?>
                <span class="px-4 py-2 rounded-xl text-sm font-bold <?php echo $sc; ?> flex items-center gap-2 w-fit mb-4">
                    <i class="fa-solid <?php echo $si; ?>"></i> <?php echo strtoupper($order['status']); ?>
                </span>
                
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Layanan</span>
                        <span class="text-sm font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($order['service_type']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Jarak</span>
                        <span class="text-sm font-bold text-gray-800 dark:text-white"><?php echo $order['distance_km']; ?> km</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Harga</span>
                        <span class="text-lg font-extrabold text-blue-600 dark:text-blue-400">Rp <?php echo number_format($order['price'], 0, ',', '.'); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Pembayaran</span>
                        <?php if(isset($order['payment_method']) && $order['payment_method'] == 'transfer'): ?>
                            <span class="text-xs font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 px-2 py-0.5 rounded border border-blue-100 dark:border-blue-800"><i class="fa-solid fa-building-columns"></i> Transfer</span>
                        <?php else: ?>
                            <span class="text-xs font-bold text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/30 px-2 py-0.5 rounded border border-green-100 dark:border-green-800"><i class="fa-solid fa-money-bill-wave"></i> COD</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Notes Card -->
            <?php if($order['notes']): ?>
            <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h4 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-3">Catatan</h4>
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700/50 rounded-xl p-3 text-sm text-gray-700 dark:text-yellow-100/80">
                    <i class="fa-solid fa-sticky-note text-yellow-500 mr-1"></i>
                    <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Column: Map -->
        <div class="md:col-span-2 space-y-6" data-aos="fade-left" data-aos-delay="300">
            <!-- Locations -->
            <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h4 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-4">Rute Pengiriman</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800/50 rounded-xl p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fa-solid fa-circle-dot text-green-500"></i>
                            <span class="text-xs font-bold text-green-700 dark:text-green-400 uppercase">Pickup</span>
                        </div>
                        <p class="text-sm text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($order['pickup_location']); ?></p>
                        <a href="https://www.google.com/maps?q=<?php echo $order['pickup_lat'].','.$order['pickup_lng']; ?>" target="_blank" class="text-[11px] text-green-600 dark:text-green-400 hover:underline mt-1 inline-block"><i class="fa-solid fa-external-link"></i> Buka Google Maps</a>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/50 rounded-xl p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fa-solid fa-location-dot text-red-500"></i>
                            <span class="text-xs font-bold text-red-700 dark:text-red-400 uppercase">Dropoff</span>
                        </div>
                        <p class="text-sm text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($order['dropoff_location']); ?></p>
                        <a href="https://www.google.com/maps?q=<?php echo $order['dropoff_lat'].','.$order['dropoff_lng']; ?>" target="_blank" class="text-[11px] text-red-600 dark:text-red-400 hover:underline mt-1 inline-block"><i class="fa-solid fa-external-link"></i> Buka Google Maps</a>
                    </div>
                </div>
            </div>

            <!-- Map -->
            <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden" style="height: 400px;">
                <div id="detailMap" class="z-0" style="height: 100%; width: 100%;"></div>
            </div>
        </div>
    </div>

<script>
    // Init Map
    const map = L.map('detailMap').setView([<?php echo $order['pickup_lat']; ?>, <?php echo $order['pickup_lng']; ?>], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);

    const greenIcon = L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png', iconSize: [25, 41], iconAnchor: [12, 41] });
    const redIcon = L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png', iconSize: [25, 41], iconAnchor: [12, 41] });

    L.marker([<?php echo $order['pickup_lat']; ?>, <?php echo $order['pickup_lng']; ?>], { icon: greenIcon }).addTo(map).bindPopup('Pickup');
    L.marker([<?php echo $order['dropoff_lat']; ?>, <?php echo $order['dropoff_lng']; ?>], { icon: redIcon }).addTo(map).bindPopup('Dropoff');

    L.Routing.control({
        waypoints: [L.latLng(<?php echo $order['pickup_lat']; ?>, <?php echo $order['pickup_lng']; ?>), L.latLng(<?php echo $order['dropoff_lat']; ?>, <?php echo $order['dropoff_lng']; ?>)],
        router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1' }),
        lineOptions: { styles: [{ color: '#3B82F6', opacity: 0.8, weight: 6 }] },
        addWaypoints: false, draggableWaypoints: false, fitSelectedRoutes: true, showAlternatives: false
    }).addTo(map);

    function updateStatus(id, stat) {
        Swal.fire({
            title: 'Konfirmasi Aksi',
            text: "Ubah status order #" + id + " jadi " + stat.toUpperCase() + "?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Lanjutkan!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('update_status.php', {id: id, status: stat}, (res) => {
                    if(res.status == 'success') {
                        Swal.fire('Berhasil!', 'Status berhasil diperbarui.', 'success')
                        .then(() => location.reload());
                    } else {
                        Swal.fire('Gagal!', res.message, 'error');
                    }
                }, 'json');
            }
        })
    }
</script>

<?php include 'includes/footer.php'; ?>

