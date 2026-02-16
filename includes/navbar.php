    <!-- Navbar -->
    <?php
    // Fetch fresh Gamification Stats
    $myPoints = 0; $myRank = 'Warga Biasa';
    if (isset($_SESSION['user_id']) && isset($conn)) {
        try {
            $stmt = $conn->prepare("SELECT points, rank_tier FROM users WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $res = $stmt->get_result();
                if($res) {
                    $uData = $res->fetch_assoc();
                    if ($uData) {
                        $myPoints = $uData['points'] ?? 0;
                        $myRank = $uData['rank_tier'] ?? 'Warga Biasa';
                    }
                }
            }
        } catch (Exception $e) {
            // Ignore error if column missing, just show default
        }
    }
    ?>
    <nav class="fixed w-full z-50 transition-all duration-300 bg-white/90 dark:bg-brand-darker/90 backdrop-blur-md shadow-sm dark:shadow-none border-b border-gray-100 dark:border-gray-800" id="navbar">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold text-brand-blue dark:text-white flex items-center gap-2 group">
                <i class="fa-solid fa-person-running text-brand-orange group-hover:animate-bounce"></i>
                MasTolongMas
            </a>
            
            <!-- Desktop Menu -->
            <div class="hidden md:flex space-x-6 font-semibold items-center">
                <a href="index.php" class="text-gray-600 dark:text-gray-300 hover:text-brand-orange dark:hover:text-brand-orange transition flex items-center gap-2">
                    <i class="fa-solid fa-house text-gray-400 dark:text-gray-500 text-sm"></i> Beranda
                </a>
                <a href="index.php#services" class="text-gray-600 dark:text-gray-300 hover:text-brand-orange dark:hover:text-brand-orange transition flex items-center gap-2">
                    <i class="fa-solid fa-layer-group text-gray-400 dark:text-gray-500 text-sm"></i> Layanan
                </a>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                        <a href="admin/dashboard.php" class="text-brand-blue dark:text-blue-400 hover:text-brand-orange transition font-bold flex items-center gap-2">
                            <i class="fa-solid fa-gauge-high"></i> Dashboard
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <a href="order.php" class="bg-brand-orange text-white px-5 py-2.5 rounded-full hover:bg-orange-600 transition shadow-lg hover:shadow-orange-500/30 flex items-center gap-2 transform hover:-translate-y-0.5">
                    <i class="fa-solid fa-paper-plane"></i> Order Sekarang!
                </a>

                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="relative group ml-4 pl-4 border-l border-gray-200 dark:border-gray-700">
                        <button class="flex items-center gap-3 hover:text-brand-orange transition focus:outline-none">
                            <div class="w-9 h-9 bg-brand-blue text-white rounded-full flex items-center justify-center font-bold shadow-md ring-2 ring-white dark:ring-gray-700 text-sm">
                                <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                            </div>
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-200 max-w-[100px] truncate">
                                <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-gray-400"></i>
                        </button>
                        
                        <!-- Dropdown -->
                        <div class="absolute right-0 top-full pt-2 w-64 hidden group-hover:block z-50">
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden transform origin-top-right transition-all">
                                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Halo, Kak!</p>
                                    <p class="text-sm font-bold text-brand-blue dark:text-white truncate mb-2"><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></p>
                                    
                                    <!-- Gamification Badge -->
                                    <div class="flex items-center justify-between bg-white dark:bg-gray-800 p-2 rounded-lg border border-gray-100 dark:border-gray-600 shadow-sm">
                                        <?php 
                                            $badgeColor = 'bg-gray-500';
                                            if($myPoints >= 1000) $badgeColor = 'bg-gradient-to-r from-purple-600 to-pink-600 animate-pulse border border-purple-400';
                                            elseif($myPoints >= 500) $badgeColor = 'bg-gradient-to-r from-yellow-500 to-amber-600 border border-yellow-300';
                                            elseif($myPoints >= 200) $badgeColor = 'bg-blue-600';
                                            elseif($myPoints >= 50) $badgeColor = 'bg-green-500';
                                        ?>
                                        <span class="text-[10px] font-bold text-white px-2 py-0.5 rounded-full <?php echo $badgeColor; ?> shadow-sm">
                                            <?php echo strtoupper($myRank); ?>
                                        </span>
                                        <span class="text-xs font-bold text-brand-orange">
                                            <i class="fa-solid fa-star text-yellow-500"></i> <?php echo $myPoints; ?> XP
                                        </span>
                                    </div>
                                </div>
                                <a href="profile.php" class="block px-4 py-3 hover:bg-blue-50 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-200 transition flex items-center gap-3">
                                    <div class="w-6 h-6 bg-blue-100 dark:bg-blue-900 text-brand-blue dark:text-blue-300 rounded flex items-center justify-center text-xs"><i class="fa-solid fa-user"></i></div>
                                    Profil Saya
                                </a>
                                <a href="history.php" class="block px-4 py-3 hover:bg-blue-50 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-200 transition flex items-center gap-3">
                                    <div class="w-6 h-6 bg-orange-100 dark:bg-orange-900 text-brand-orange dark:text-orange-300 rounded flex items-center justify-center text-xs"><i class="fa-solid fa-clock-rotate-left"></i></div>
                                    Riwayat Order
                                </a>
                                <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                                <button onclick="openModal('logout')" class="w-full text-left block px-4 py-3 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 text-sm font-bold transition flex items-center gap-3">
                                    <div class="w-6 h-6 bg-red-100 dark:bg-red-900 text-red-500 rounded flex items-center justify-center text-xs"><i class="fa-solid fa-right-from-bracket"></i></div>
                                    Keluar
                                </button>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <button onclick="openModal('login')" class="bg-brand-blue text-white px-5 py-2.5 rounded-full hover:bg-blue-800 transition shadow-md font-bold flex items-center gap-2">
                        <i class="fa-regular fa-user"></i> Masuk / Daftar
                    </button>
                <?php endif; ?>
            </div>

            <button id="mobile-menu-btn" class="md:hidden text-2xl text-gray-700 dark:text-white focus:outline-none">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-white dark:bg-gray-800 border-t dark:border-gray-700 p-4 space-y-3 shadow-lg">
            <a href="index.php" class="block font-semibold text-gray-700 dark:text-gray-200 hover:text-brand-orange">Beranda</a>
            <a href="index.php#services" class="block font-semibold text-gray-700 dark:text-gray-200 hover:text-brand-orange">Layanan</a>
            
            <a href="order.php" class="block font-bold text-brand-orange hover:text-orange-600">Order Sekarang!</a>
            
            <?php if(isset($_SESSION['user_id'])): ?>
                <div class="border-t dark:border-gray-700 pt-2 mt-2">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Login sebagai: <b><?php echo htmlspecialchars($_SESSION['username']); ?></b></p>
                    <button onclick="openModal('logout')" class="block text-red-600 font-semibold mt-2 w-full text-left">Logout</button>
                </div>
            <?php else: ?>
                <button onclick="openModal('login')" class="block w-full text-center bg-brand-blue text-white py-2 rounded font-bold mt-2">
                    <i class="fa-regular fa-user"></i> Masuk / Daftar
                </button>
            <?php endif; ?>
        </div>
    </nav>
