<?php
session_start();
require_once '../includes/config.php';

// Auth Check
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../index.php?modal=login");
    exit;
}

$id = isset($_GET['id']) ? $_GET['id'] : null;
$error = null;
$name = ''; $desc = ''; $icon = 'fa-solid fa-box'; 
$base = 0; $per_km = 0; $min = 0; $is_active = 1;
$max_weight = 20; $price_per_kg = 0;

// Load Data if Edit
if ($id) {
    $stmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $name = $row['name'];
        $desc = $row['description'];
        $icon = $row['icon'];
        $category = $row['category'];
        $base = $row['base_price'];
        $per_km = $row['price_per_km'];
        $min = $row['min_price'];
        $max_weight = $row['max_weight'] ?? 20;
        $price_per_kg = $row['price_per_kg'] ?? 0;
        $is_active = $row['is_active'];
    }
} else {
    $category = 'other'; // Default for new
}

// Handle Submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    // $icon handled below
    $category = $_POST['category'];
    
    // Auto-Assign Icon based on Category
    switch($category) {
        case 'courier': $icon = 'fa-solid fa-box'; break;
        case 'shopping': $icon = 'fa-solid fa-basket-shopping'; break;
        case 'food': $icon = 'fa-solid fa-utensils'; break;
        case 'ride': $icon = 'fa-solid fa-motorcycle'; break;
        default: $icon = 'fa-solid fa-star'; break;
    }

    $base = $_POST['base_price'];
    $per_km = $_POST['price_per_km'];
    $min = $_POST['min_price'];
    $max_weight = $_POST['max_weight'] ?? 20;
    $price_per_kg = $_POST['weight_price'] ?? 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($id) {
        // Update
        $stmt = $conn->prepare("UPDATE services SET name=?, description=?, icon=?, category=?, base_price=?, price_per_km=?, min_price=?, max_weight=?, price_per_kg=?, is_active=? WHERE id=?");
        if ($stmt === false) {
             die("<div style='color:red; paadding:20px; font-family:sans-serif;'><h2>Error Database!</h2><p>Gagal update database. Kemungkinan tabel belum di-upgrade.</p><p>👉 <a href='upgrade_services_table.php'>Klik Disini untuk Perbaiki Database</a></p><p>Error Detail: " . $conn->error . "</p></div>");
        }
        $stmt->bind_param("ssssdddidii", $name, $desc, $icon, $category, $base, $per_km, $min, $max_weight, $price_per_kg, $is_active, $id);
    } else {
        // Insert
        $stmt = $conn->prepare("INSERT INTO services (name, description, icon, category, base_price, price_per_km, min_price, max_weight, price_per_kg, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt === false) {
             die("<div style='color:red; paadding:20px; font-family:sans-serif;'><h2>Error Database!</h2><p>Gagal insert database. Kemungkinan tabel belum di-upgrade.</p><p>👉 <a href='upgrade_services_table.php'>Klik Disini untuk Perbaiki Database</a></p><p>Error Detail: " . $conn->error . "</p></div>");
        }
        $stmt->bind_param("ssssdddidi", $name, $desc, $icon, $category, $base, $per_km, $min, $max_weight, $price_per_kg, $is_active);
    }

    if ($stmt->execute()) {
        header("Location: services.php?msg=saved");
        exit;
    } else {
        $error = "Database Error: " . $stmt->error;
    }
}
?>
<?php
$pageTitle = $id ? 'Edit Jasa' : 'Tambah Jasa';
include 'includes/header.php';
?>

    <div class="flex justify-center items-start min-h-[calc(100vh-200px)] pt-10 px-4">
        <div class="glass bg-white dark:bg-gray-800/90 rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden border border-gray-100 dark:border-gray-700 relative">
            
            <!-- Decor -->
            <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500"></div>

            <div class="p-8">
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h2 class="text-2xl font-extrabold text-gray-800 dark:text-white"><?php echo $id ? '✏️ Edit Layanan' : '✨ Tambah Layanan Baru'; ?></h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Lengkapi informasi layanan dengan detail.</p>
                    </div>
                    <a href="services.php" class="text-gray-400 hover:text-red-500 transition p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </a>
                </div>

                <?php if($error): ?>
                    <div class="bg-red-100 dark:bg-red-900/30 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4 mb-6 rounded-r-lg shadow-sm font-medium text-sm animate-pulse">
                        <i class="fa-solid fa-triangle-exclamation mr-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <!-- Name & Description -->
                    <div class="space-y-4 bg-gray-50 dark:bg-gray-900/50 p-5 rounded-2xl border border-gray-100 dark:border-gray-700/50">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Nama Layanan</label>
                            <div class="relative group">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-blue-500 transition-colors">
                                    <i class="fa-solid fa-tag"></i>
                                </span>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required class="w-full pl-11 pr-4 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all font-semibold dark:text-white placeholder-gray-400" placeholder="Contoh: Antar Kilat">
                            </div>
                        </div>

                         <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Deskripsi Singkat</label>
                            <div class="relative group">
                                <span class="absolute top-3.5 left-0 flex items-start pl-4 text-gray-400 group-focus-within:text-blue-500 transition-colors">
                                    <i class="fa-solid fa-align-left"></i>
                                </span>
                                <textarea name="description" required rows="3" class="w-full pl-11 pr-4 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all text-sm dark:text-white placeholder-gray-400 resize-none" placeholder="Jelaskan layanan ini agar customer paham..."><?php echo htmlspecialchars($desc); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Category & Icon Preview -->
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Kategori & Ikon</label>
                            <div class="relative group">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-blue-500 transition-colors">
                                    <i class="fa-solid fa-layer-group"></i>
                                </span>
                                <select name="category" id="categorySelect" onchange="updateIconPreview()" class="w-full pl-11 pr-10 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all font-medium dark:text-white appearance-none cursor-pointer">
                                    <option value="courier" data-icon="fa-box" <?php echo $category == 'courier' ? 'selected' : ''; ?>>Courier (Antar Barang)</option>
                                    <option value="shopping" data-icon="fa-basket-shopping" <?php echo $category == 'shopping' ? 'selected' : ''; ?>>Shopping (Jasa Belanja)</option>
                                    <option value="food" data-icon="fa-utensils" <?php echo $category == 'food' ? 'selected' : ''; ?>>Food (Titip Makan)</option>
                                    <option value="ride" data-icon="fa-motorcycle" <?php echo $category == 'ride' ? 'selected' : ''; ?>>Ride (Ojek)</option>
                                    <option value="other" data-icon="fa-star" <?php echo $category == 'other' ? 'selected' : ''; ?>>Other (Lainnya)</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-gray-400">
                                    <i class="fa-solid fa-chevron-down text-xs"></i>
                                </div>
                            </div>
                        </div>
                        <div class="w-16 flex flex-col items-center justify-end pb-1">
                             <div id="iconPreview" class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-xl flex items-center justify-center text-xl shadow-inner transition-all duration-300 transform hover:scale-110">
                                <i class="fa-solid fa-box"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Base Price</label>
                            <div class="relative group">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 text-xs font-bold">Rp</span>
                                <input type="number" name="base_price" value="<?php echo $base; ?>" required class="w-full pl-8 pr-3 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all font-bold text-gray-700 dark:text-white">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Per KM</label>
                            <div class="relative group">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 text-xs font-bold">Rp</span>
                                <input type="number" name="price_per_km" value="<?php echo $per_km; ?>" required class="w-full pl-8 pr-3 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all font-bold text-gray-700 dark:text-white">
                            </div>
                        </div>
                         <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Min Order</label>
                            <div class="relative group">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 text-xs font-bold">Rp</span>
                                <input type="number" name="min_price" value="<?php echo $min; ?>" required class="w-full pl-8 pr-3 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all font-bold text-gray-700 dark:text-white">
                            </div>
                        </div>
                    </div>

                    <!-- Weight Config (Courier Only) -->
                    <div id="weightConfig" class="bg-orange-50 dark:bg-orange-900/20 p-5 rounded-2xl border border-orange-100 dark:border-orange-800/50 hidden">
                        <h4 class="text-xs font-bold text-orange-600 dark:text-orange-400 mb-4 uppercase flex items-center gap-2">
                            <i class="fa-solid fa-scale-balanced"></i> Konfigurasi Berat (Khusus Kurir)
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Batas Berat Max (KG)</label>
                                <div class="relative group">
                                    <input type="number" name="max_weight" value="<?php echo $max_weight; ?>" class="w-full pl-4 pr-3 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500/50 focus:border-orange-500 transition-all font-bold text-gray-700 dark:text-white">
                                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 text-xs font-bold">KG</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Harga Per KG</label>
                                <div class="relative group">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 text-xs font-bold">Rp</span>
                                    <input type="number" name="weight_price" value="<?php echo $price_per_kg; ?>" class="w-full pl-8 pr-3 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500/50 focus:border-orange-500 transition-all font-bold text-gray-700 dark:text-white">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Toggle -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-xl border border-blue-100 dark:border-blue-800/50 flex items-center gap-3">
                        <div class="relative inline-block w-12 h-6 align-middle select-none transition duration-200 ease-in">
                            <input type="checkbox" name="is_active" id="is_active" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer transition-transform duration-200 ease-in-out <?php echo $is_active ? 'translate-x-full border-blue-600' : 'translate-x-0 border-gray-300'; ?>" <?php echo $is_active ? 'checked' : ''; ?> onclick="this.classList.toggle('translate-x-full'); this.classList.toggle('border-blue-600'); this.parentElement.classList.toggle('bg-blue-600'); this.parentElement.classList.toggle('bg-gray-300');">
                            <label for="is_active" class="toggle-label block overflow-hidden h-6 rounded-full cursor-pointer transition-colors duration-200 <?php echo $is_active ? 'bg-blue-600' : 'bg-gray-300'; ?>"></label>
                        </div>
                        <label for="is_active" class="font-bold text-gray-700 dark:text-gray-200 text-sm cursor-pointer select-none">Tampilkan Layanan Ini di Aplikasi</label>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-4 pt-4 border-t border-gray-100 dark:border-gray-700 mt-6">
                        <a href="services.php" class="flex-1 px-6 py-3.5 rounded-xl border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 font-bold hover:bg-gray-50 dark:hover:bg-gray-700 text-center transition">
                            Batal
                        </a>
                        <button type="submit" class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white py-3.5 rounded-xl font-bold shadow-lg shadow-blue-500/30 transform transition-all active:scale-95 flex items-center justify-center gap-2">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function updateIconPreview() {
            const select = document.getElementById('categorySelect');
            const selectedOption = select.options[select.selectedIndex];
            const iconClass = selectedOption.getAttribute('data-icon');
            const category = selectedOption.value;
            const preview = document.getElementById('iconPreview');
            preview.innerHTML = `<i class="fa-solid ${iconClass}"></i>`;

            // Toggle Weight Config
            const weightConfig = document.getElementById('weightConfig');
            if (category === 'courier') {
                weightConfig.classList.remove('hidden');
            } else {
                weightConfig.classList.add('hidden');
            }
        }
        // Init logic
        document.addEventListener('DOMContentLoaded', updateIconPreview);
    </script>

<?php include 'includes/footer.php'; ?>

