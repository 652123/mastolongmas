<?php
// Determine current page for active highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
function isActive($page) {
    global $currentPage;
    return $currentPage === $page ? 'sidebar-active' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700';
}
?>
<!-- Sidebar (Desktop) -->
<aside class="hidden md:flex w-64 flex-col bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 z-20 flex-shrink-0">
    <div class="h-20 flex items-center px-8 border-b border-gray-100 dark:border-gray-800">
        <i class="fa-solid fa-person-running text-orange-500 text-3xl mr-2"></i>
        <h1 class="text-xl font-extrabold text-blue-900 dark:text-white tracking-tight">Mas<span class="text-orange-500">Tolong</span>Mas</h1>
    </div>

    <div class="flex-1 overflow-y-auto py-6 px-4 space-y-2">
        <p class="px-4 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Menu Utama</p>
        
        <a href="dashboard.php" class="<?php echo isActive('dashboard.php'); ?> flex items-center gap-3 px-4 py-3 rounded-xl font-bold transition-all dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white">
            <i class="fa-solid fa-gauge-high w-5 text-center"></i> Dashboard
        </a>
        <a href="reports.php" class="<?php echo isActive('reports.php'); ?> flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white">
            <i class="fa-solid fa-chart-line w-5 text-center"></i> Laporan
        </a>
        <a href="portfolio.php" class="<?php echo isActive('portfolio.php'); ?> flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white">
            <i class="fa-solid fa-images w-5 text-center"></i> Portofolio
        </a>
        <a href="services.php" class="<?php echo isActive('services.php'); ?> flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white">
            <i class="fa-solid fa-tags w-5 text-center"></i> Kelola Jasa
        </a>

        <p class="px-4 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mt-8 mb-2">Pengaturan</p>
        <a href="settings.php" class="<?php echo isActive('settings.php'); ?> flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white">
            <i class="fa-solid fa-gear w-5 text-center"></i> Settings
        </a>
        <a href="../logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all">
            <i class="fa-solid fa-right-from-bracket w-5 text-center"></i> Logout
        </a>
    </div>

    <div class="p-4 border-t border-gray-100 dark:border-gray-800">
        <div class="glass bg-blue-50 dark:bg-gray-800/50 rounded-xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-blue-600 dark:text-blue-300 font-bold">
                A
            </div>
            <div>
                <p class="text-sm font-bold text-gray-800 dark:text-white">Admin</p>
                <div id="liveIndicator" class="flex items-center gap-1 text-[10px] font-bold text-green-600 dark:text-green-400">
                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div> ONLINE
                </div>
            </div>
        </div>
    </div>
</aside>
