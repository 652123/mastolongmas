<?php
session_start();
require_once '../includes/config.php';

// Auth Check
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../index.php?modal=login");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: services.php?msg=deleted");
    } else {
        $error = "Gagal menghapus jasa.";
    }
}

// Self-Healing: Check if table exists, if not create & seed
$tableCheck = $conn->query("SHOW TABLES LIKE 'services'");
if ($tableCheck->num_rows == 0) {
    // 1. Create Table
    $sql = "CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        icon VARCHAR(100) DEFAULT 'fa-solid fa-box',
        category VARCHAR(50) NOT NULL DEFAULT 'other',
        base_price DECIMAL(10,2) NOT NULL DEFAULT 5000,
        price_per_km DECIMAL(10,2) NOT NULL DEFAULT 2500,
        min_price DECIMAL(10,2) NOT NULL DEFAULT 10000,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($sql);

    // 2. Seed Data
    $defaults = [
        ['Antar Kilat', 'Kirim paket/dokumen super cepat dalam kota.', 'fa-solid fa-bolt', 'courier', 5000, 3000, 10000],
        ['Jasa Belanja', 'Titip belanja ke pasar, minimarket, atau apotek.', 'fa-solid fa-basket-shopping', 'shopping', 5000, 2500, 10000],
        ['Titip Makanan', 'Lapar tapi mager? Kami belikan makanan favoritmu.', 'fa-solid fa-utensils', 'food', 3000, 2500, 8000],
        ['Antar Jemput', 'Ojek aman & nyaman. Bisa langganan jemput anak sekolah.', 'fa-solid fa-person-biking', 'ride', 7000, 3000, 12000],
        ['Lainnya', 'Jasa apa aja (Palugada). Bantu angkat galon, buang sampah, dll.', 'fa-solid fa-hand-holding-heart', 'other', 5000, 2500, 10000]
    ];
    $stmt = $conn->prepare("INSERT INTO services (name, description, icon, category, base_price, price_per_km, min_price) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($defaults as $svc) {
        $stmt->bind_param("ssssddd", $svc[0], $svc[1], $svc[2], $svc[3], $svc[4], $svc[5], $svc[6]);
        $stmt->execute();
    }
}

// Fetch Services
$result = $conn->query("SELECT * FROM services ORDER BY created_at ASC");
?>
<?php
$pageTitle = 'Kelola Jasa';
include 'includes/header.php';
?>

    <!-- Toast Notification -->
    <?php if(isset($_GET['msg'])): ?>
    <div id="toast" class="fixed top-24 right-5 z-[500] flex items-center w-full max-w-xs p-4 space-x-4 text-gray-500 bg-white dark:bg-gray-800 divide-x divide-gray-200 dark:divide-gray-700 rounded-2xl shadow-2xl dark:text-gray-400 space-x transition-all duration-500 transform translate-x-full opacity-0 border border-gray-100 dark:border-gray-700 glass" role="alert">
        <?php if($_GET['msg'] == 'deleted' || $_GET['msg'] == 'saved'): ?>
            <div class="inline-flex items-center justify-center flex-shrink-0 w-10 h-10 text-green-500 bg-green-100 rounded-xl dark:bg-green-900/30 dark:text-green-400">
                <i class="fa-solid fa-check text-lg"></i>
            </div>
            <div class="ml-3 text-sm font-bold text-gray-800 dark:text-white">
                <?php echo $_GET['msg'] == 'deleted' ? 'Data berhasil dihapus!' : 'Data berhasil disimpan!'; ?>
            </div>
        <?php else: ?>
             <div class="inline-flex items-center justify-center flex-shrink-0 w-10 h-10 text-red-500 bg-red-100 rounded-xl dark:bg-red-900/30 dark:text-red-400">
                <i class="fa-solid fa-xmark text-lg"></i>
            </div>
            <div class="ml-3 text-sm font-bold text-gray-800 dark:text-white">Terjadi kesalahan.</div>
        <?php endif; ?>
        <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-transparent text-gray-400 hover:text-gray-900 rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-100 inline-flex h-8 w-8 dark:text-gray-500 dark:hover:text-white dark:hover:bg-gray-700 transition" aria-label="Close" onclick="document.getElementById('toast').classList.add('translate-x-full', 'opacity-0')">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toast = document.getElementById('toast');
            setTimeout(() => { toast.classList.remove('translate-x-full', 'opacity-0'); }, 100);
            setTimeout(() => { toast.classList.add('translate-x-full', 'opacity-0'); }, 5000);
        });
    </script>
    <?php endif; ?>

    <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 text-white">
        <div>
            <h2 class="text-3xl font-extrabold mb-1">📦 Kelola Jasa & Harga</h2>
            <p class="opacity-80">Atur layanan dan tarif yang tersedia di aplikasi</p>
        </div>
        <a href="manage_service.php" class="mt-4 md:mt-0 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-blue-500/30 transition transform hover:scale-105 active:scale-95 flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Tambah Jasa
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php while($row = $result->fetch_assoc()): ?>
        <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden group hover:-translate-y-1 transition duration-300">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-100 to-blue-50 dark:from-blue-900/40 dark:to-blue-800/20 rounded-2xl flex items-center justify-center text-blue-600 dark:text-blue-400 text-2xl shadow-inner">
                        <i class="<?php echo $row['icon']; ?>"></i>
                    </div>
                    <span class="px-3 py-1 text-xs font-bold rounded-full border <?php echo $row['is_active'] ? 'bg-green-100 text-green-700 border-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-800' : 'bg-red-100 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800'; ?>">
                        <?php echo $row['is_active'] ? 'Aktif' : 'Non-Aktif'; ?>
                    </span>
                </div>
                
                <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2 tracking-tight"><?php echo htmlspecialchars($row['name']); ?></h3>
                <p class="text-gray-500 dark:text-gray-400 text-sm mb-6 h-10 overflow-hidden leading-relaxed line-clamp-2"><?php echo htmlspecialchars($row['description']); ?></p>
                
                <div class="space-y-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 mb-6 border border-gray-100 dark:border-gray-700/50">
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-gray-500 dark:text-gray-400 font-medium">Harga Dasar</span>
                        <span class="font-bold text-gray-800 dark:text-white bg-white dark:bg-gray-800 px-2 py-0.5 rounded shadow-sm border border-gray-100 dark:border-gray-700">Rp <?php echo number_format($row['base_price'], 0, ',', '.'); ?></span>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-gray-500 dark:text-gray-400 font-medium">Per KM</span>
                        <span class="font-bold text-gray-800 dark:text-white bg-white dark:bg-gray-800 px-2 py-0.5 rounded shadow-sm border border-gray-100 dark:border-gray-700">Rp <?php echo number_format($row['price_per_km'], 0, ',', '.'); ?></span>
                    </div>
                    
                    <?php if($row['category'] == 'courier'): ?>
                         <div class="flex justify-between text-sm items-center">
                            <span class="text-gray-500 dark:text-gray-400 font-medium">Max Berat</span>
                            <span class="font-bold text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-900/30 px-2 py-0.5 rounded shadow-sm border border-orange-100 dark:border-orange-800"><?php echo isset($row['max_weight']) ? $row['max_weight'] . ' KG' : '-'; ?></span>
                        </div>
                        <div class="flex justify-between text-sm items-center">
                            <span class="text-gray-500 dark:text-gray-400 font-medium">Harga/KG</span>
                            <span class="font-bold text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-900/30 px-2 py-0.5 rounded shadow-sm border border-orange-100 dark:border-orange-800">Rp <?php echo isset($row['price_per_kg']) ? number_format($row['price_per_kg'], 0, ',', '.') : '-'; ?></span>
                        </div>
                    <?php else: ?>
                        <div class="flex justify-between text-sm items-center">
                            <span class="text-gray-500 dark:text-gray-400 font-medium">Minimal Order</span>
                            <span class="font-bold text-gray-800 dark:text-white bg-white dark:bg-gray-800 px-2 py-0.5 rounded shadow-sm border border-gray-100 dark:border-gray-700">Rp <?php echo number_format($row['min_price'], 0, ',', '.'); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="flex gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                    <a href="manage_service.php?id=<?php echo $row['id']; ?>" class="flex-1 bg-yellow-50 dark:bg-yellow-900/20 text-yellow-600 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-800 py-2.5 rounded-xl font-bold text-center hover:bg-yellow-100 dark:hover:bg-yellow-900/40 transition flex items-center justify-center gap-2 text-sm">
                        <i class="fa-solid fa-pen-to-square"></i> Edit
                    </a>
                    <button onclick="deleteService(event, 'services.php?delete=<?php echo $row['id']; ?>')" class="flex-1 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800 py-2.5 rounded-xl font-bold text-center hover:bg-red-100 dark:hover:bg-red-900/40 transition flex items-center justify-center gap-2 text-sm">
                        <i class="fa-solid fa-trash"></i> Hapus
                    </button>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

<script>
    function deleteService(e, url) {
        e.preventDefault();
        Swal.fire({
            title: 'Hapus Jasa?',
            text: "Layanan ini akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        })
    }
</script>

<?php include 'includes/footer.php'; ?>

