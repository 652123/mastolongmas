<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: ../index.php?modal=login"); exit; }
include '../includes/config.php';

// Self-Healing: Create table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS portfolio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Fetch portfolios
$result = $conn->query("SELECT * FROM portfolio ORDER BY created_at DESC");

$pageTitle = 'Portofolio';
include 'includes/header.php';
?>

    <!-- Toast Notification -->
    <?php if(isset($_GET['status'])): ?>
    <div id="toast" class="fixed top-24 right-5 z-[500] flex items-center w-full max-w-xs p-4 space-x-4 text-gray-500 bg-white dark:bg-gray-800 divide-x divide-gray-200 dark:divide-gray-700 rounded-2xl shadow-2xl dark:text-gray-400 space-x transition-all duration-500 transform translate-x-full opacity-0 border border-gray-100 dark:border-gray-700 glass" role="alert">
        <?php if($_GET['status'] == 'success'): ?>
            <div class="inline-flex items-center justify-center flex-shrink-0 w-10 h-10 text-green-500 bg-green-100 rounded-xl dark:bg-green-900/30 dark:text-green-400">
                <i class="fa-solid fa-check text-lg"></i>
            </div>
            <div class="ml-3 text-sm font-bold text-gray-800 dark:text-white">Berhasil menyimpan data!</div>
        <?php else: ?>
             <div class="inline-flex items-center justify-center flex-shrink-0 w-10 h-10 text-red-500 bg-red-100 rounded-xl dark:bg-red-900/30 dark:text-red-400">
                <i class="fa-solid fa-xmark text-lg"></i>
            </div>
            <div class="ml-3 text-sm font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($_GET['msg'] ?? 'Terjadi kesalahan.'); ?></div>
        <?php endif; ?>
        <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-transparent text-gray-400 hover:text-gray-900 rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-100 inline-flex h-8 w-8 dark:text-gray-500 dark:hover:text-white dark:hover:bg-gray-700 transition" aria-label="Close" onclick="document.getElementById('toast').classList.add('translate-x-full', 'opacity-0')">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toast = document.getElementById('toast');
            // Animate In
            setTimeout(() => {
                toast.classList.remove('translate-x-full', 'opacity-0');
            }, 100);
            // Auto Hide
            setTimeout(() => {
                toast.classList.add('translate-x-full', 'opacity-0');
            }, 5000);
        });
    </script>
    <?php endif; ?>

    <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 text-white" data-aos="fade-down">
        <div>
            <h2 class="text-3xl font-extrabold mb-1">🖼️ Kelola Portofolio</h2>
            <p class="opacity-80">Atur foto aktivitas yang tampil di Homepage</p>
        </div>
        <button onclick="openAddModal()" class="mt-4 md:mt-0 bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-green-500/30 transition transform hover:scale-105 active:scale-95">
            <i class="fa-solid fa-plus mr-1"></i> Tambah Foto
        </button>
    </div>

    <!-- Gallery Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <?php if ($result->num_rows > 0): ?>
            <?php while($item = $result->fetch_assoc()): ?>
                <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden group hover:-translate-y-1 transition duration-300" data-aos="fade-up">
                    <div class="relative h-48 overflow-hidden">
                        <img src="../<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition flex items-end justify-between p-4">
                            <a href="portfolio_edit.php?id=<?php echo $item['id']; ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white p-2 rounded-lg shadow-lg transition" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <button onclick="deletePortfolio(<?php echo $item['id']; ?>)" class="bg-red-500 hover:bg-red-600 text-white p-2 rounded-lg shadow-lg transition" title="Hapus">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="p-4">
                        <h4 class="font-bold text-gray-800 dark:text-white text-sm truncate"><?php echo htmlspecialchars($item['title']); ?></h4>
                        <p class="text-xs text-gray-400 dark:text-gray-500 line-clamp-2 mt-1"><?php echo htmlspecialchars($item['description'] ?? ''); ?></p>
                        <p class="text-[10px] text-gray-300 dark:text-gray-600 mt-2"><?php echo date('d M Y', strtotime($item['created_at'])); ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-span-full text-center py-16">
                <i class="fa-solid fa-image text-5xl text-gray-300 dark:text-gray-600 mb-3"></i>
                <p class="text-gray-500 dark:text-gray-400">Belum ada foto portofolio.</p>
                <p class="text-xs text-gray-400 mt-1">Klik "Tambah Foto" untuk menambahkan.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 transition-all duration-300 opacity-0 scale-95" role="dialog" aria-modal="true">
        <div class="bg-white dark:bg-gray-800 w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden relative transition-all duration-300 transform scale-100 border border-gray-100 dark:border-gray-700">
            
            <!-- Header Pattern -->
            <div class="absolute top-0 left-0 right-0 h-24 bg-gradient-to-r from-blue-600 to-purple-600 opacity-10"></div>
            
            <div class="p-6 relative">
                <div class="flex justify-between items-center mb-6">
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-100 dark:bg-blue-900/30 p-2.5 rounded-xl text-blue-600 dark:text-blue-400">
                            <i class="fa-solid fa-cloud-arrow-up text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-xl text-gray-800 dark:text-white tracking-tight">Tambah Portofolio</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Upload foto kegiatan terbaru</p>
                        </div>
                    </div>
                    <button onclick="closeAddModal()" class="text-gray-400 dark:text-gray-500 hover:text-red-500 dark:hover:text-red-400 bg-gray-50 dark:bg-gray-700/50 p-2 rounded-lg transition-colors">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <form action="portfolio_add.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Judul Kegiatan</label>
                        <div class="relative group">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 group-focus-within:text-blue-500 transition-colors">
                                <i class="fa-solid fa-heading"></i>
                            </span>
                            <input type="text" name="title" required class="w-full pl-10 pr-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 text-sm font-medium dark:text-white transition-all placeholder-gray-400" placeholder="Contoh: Pengiriman ke Desa X">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Deskripsi Singkat</label>
                        <textarea name="description" rows="3" class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 text-sm font-medium resize-none dark:text-white transition-all placeholder-gray-400" placeholder="Ceritakan sedikit tentang kegiatan ini..."></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Pilih Foto</label>
                        <div class="relative group">
                            <input type="file" name="image" accept="image/*" required class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 dark:file:bg-blue-900/30 file:text-blue-700 dark:file:text-blue-400 hover:file:bg-blue-100 dark:hover:file:bg-blue-800 transition-all cursor-pointer border border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-1 bg-gray-50 dark:bg-gray-900/20">
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white py-3.5 rounded-xl font-bold shadow-lg shadow-blue-500/30 transform transition-all active:scale-95 flex items-center justify-center gap-2">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Upload Foto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
    function openAddModal() {
        const modal = document.getElementById('addModal');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0', 'scale-95');
            modal.classList.add('opacity-100', 'scale-100');
        }, 10);
    }

    function closeAddModal() {
        const modal = document.getElementById('addModal');
        modal.classList.remove('opacity-100', 'scale-100');
        modal.classList.add('opacity-0', 'scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    function deletePortfolio(id) {
        Swal.fire({
            title: 'Hapus Foto?',
            text: "Data yang dihapus tidak bisa dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'portfolio_delete.php?id=' + id;
            }
        })
    }
</script>

<?php include 'includes/footer.php'; ?>

