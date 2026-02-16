<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
} 
include 'includes/config.php';
include 'includes/header.php'; 

// Fetch Stats
$stats = [
    'orders' => 0,
    'users' => 0,
    'drivers' => 0
];

if ($conn) {
    // Count Completed Orders (or all orders if no status column verified yet)
    $resOrder = $conn->query("SELECT COUNT(*) as total FROM orders");
    if ($resOrder) $stats['orders'] = $resOrder->fetch_assoc()['total'];

    // Count Users
    $resUser = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='user'");
    if ($resUser) $stats['users'] = $resUser->fetch_assoc()['total'];

    // Count Service Hours (Mock or logic)
    // For now we can just show '24' as it is a service promise, or calculate based on orders
}
?>
<?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section id="hero" class="relative pt-32 pb-20 px-4 min-h-screen flex items-center bg-brand-darker overflow-hidden">
        <!-- Background Decor -->
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-5"></div>
        <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-brand-orange/20 rounded-full blur-3xl animate-float"></div>
        <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-brand-blue/20 rounded-full blur-3xl animate-float" style="animation-delay: 2s;"></div>

        <div class="container mx-auto relative z-10">
            <div class="flex flex-col lg:flex-row items-center gap-16">
                <!-- Left Content -->
                <div class="w-full lg:w-1/2 text-center lg:text-left" data-aos="fade-right">
                    <div class="inline-flex items-center gap-2 px-4 py-2 mb-6 bg-brand-orange/10 border border-brand-orange/20 text-brand-orange rounded-full text-sm font-bold tracking-wide animate-pulse-glow">
                        <span class="relative flex h-3 w-3">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-orange opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-3 w-3 bg-brand-orange"></span>
                        </span>
                        VIRAL DI MOJOKERTO!
                    </div>
                    <h1 class="text-5xl lg:text-7xl font-extrabold mb-6 text-white leading-tight tracking-tight">
                        Mas Tolong Mas<br>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-gray-400 to-gray-600 block text-3xl lg:text-4xl mt-2 font-medium">by Mas Aji</span>
                    </h1>
                    <p class="text-lg lg:text-xl text-gray-400 mb-10 max-w-lg mx-auto lg:mx-0 leading-relaxed">
                        Jasa suruhan serabutan 'Palugada' andalan warga Mojokerto. Apapun masalahmu, asal halal, <strong class="text-white">Mas Aji</strong> siap sat-set das-des 24 Jam!
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                        <a href="order.php" class="group bg-gradient-to-r from-brand-orange to-red-500 text-white px-8 py-4 rounded-xl font-bold text-lg hover:shadow-lg hover:shadow-orange-500/30 transition-all transform hover:-translate-y-1 flex items-center justify-center gap-3">
                            <i class="fa-solid fa-arrow-pointer text-xl group-hover:animate-bounce"></i> Order Sekarang
                        </a>
                        <a href="#services" class="group bg-white/5 text-white border border-white/10 px-8 py-4 rounded-xl font-bold text-lg hover:bg-white/10 transition flex items-center justify-center gap-3 backdrop-blur-sm">
                            <i class="fa-solid fa-list-check text-gray-400 group-hover:text-white transition"></i> LIHAT LAYANAN
                        </a>
                    </div>
                </div>

                <!-- Right Content (Profile Card) -->
                <div class="w-full lg:w-1/2 flex justify-center" data-aos="fade-left" data-aos-delay="200">
                    <div class="relative w-full max-w-sm" data-tilt data-tilt-max="5" data-tilt-speed="400">
                        <!-- Glow Effect -->
                        <div class="absolute -inset-1 bg-gradient-to-r from-brand-orange to-brand-blue rounded-[2rem] blur opacity-30 animate-pulse-glow"></div>
                        
                        <div class="relative glass bg-gray-900/60 p-8 rounded-[2rem] border border-white/10 text-center backdrop-blur-xl">
                            <div class="w-32 h-32 mx-auto bg-gradient-to-br from-gray-800 to-black rounded-full p-1 shadow-2xl mb-6 relative group cursor-pointer">
                                <div class="w-full h-full rounded-full overflow-hidden border-2 border-brand-orange/50 flex items-center justify-center bg-gray-900">
                                   <!-- Placeholder User Icon if no image -->
                                   <i class="fa-solid fa-user-tie text-5xl text-gray-500 group-hover:text-brand-orange transition duration-300"></i>
                                </div>
                                <div class="absolute bottom-2 right-2 w-6 h-6 bg-green-500 border-4 border-gray-900 rounded-full animate-pulse" title="Online"></div>
                            </div>

                            <h3 class="text-3xl font-bold text-white mb-1">Mas Aji</h3>
                            <p class="text-brand-orange text-xs font-bold tracking-[0.2em] mb-6 uppercase">Founder & CEO</p>
                            
                            <div class="bg-white/5 p-4 rounded-xl border border-white/5 mb-6">
                                <p class="text-gray-300 italic text-sm leading-relaxed">"Gausah bingung, gausah pusing. Ada apa-apa, calling Mas Aji aja. Insyaallah amanah!"</p>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-green-500/10 border border-green-500/20 py-2.5 rounded-lg">
                                    <p class="text-green-400 text-xs font-bold"><i class="fa-solid fa-check mr-1"></i> 100% AMANAH</p>
                                </div>
                                <div class="bg-blue-500/10 border border-blue-500/20 py-2.5 rounded-lg">
                                     <p class="text-blue-400 text-xs font-bold"><i class="fa-solid fa-bolt mr-1"></i> FAST RESPON</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-24 bg-gray-50 dark:bg-[#0B1120] transition-colors duration-300">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="text-3xl md:text-4xl font-bold text-brand-blue dark:text-white mb-4">Layanan <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-orange to-yellow-500">"Palugada"</span></h2>
                <p class="text-gray-600 dark:text-gray-400 max-w-xl mx-auto text-lg">Apa lu mau, gue ada! Request aneh-aneh? Boleh, asal sopan & halal!</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php
                // Fetch active services
                $svc_sql = "SELECT * FROM services WHERE is_active = 1 LIMIT 4";
                $svc_res = $conn->query($svc_sql);
                
                if ($svc_res && $svc_res->num_rows > 0) {
                    while($svc = $svc_res->fetch_assoc()) {
                        // Standardize Icons Logic
                        $iconClass = $svc['icon'] ?: 'fa-solid fa-star';
                        
                        // Color Logic based on Category (Cleaner)
                        $theme = 'blue';
                        if ($svc['category'] == 'food') $theme = 'orange';
                        if ($svc['category'] == 'ride') $theme = 'green';
                        if ($svc['category'] == 'shopping') $theme = 'purple';
                        
                        // Dynamic Classes
                        $bgIcon = match($theme) {
                            'orange' => 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400',
                            'green' => 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400',
                            'purple' => 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400',
                            default => 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'
                        };
                ?>
                <div class="group bg-white dark:bg-gray-800 rounded-3xl p-8 shadow-xl border border-gray-100 dark:border-gray-700 hover:border-brand-orange/50 dark:hover:border-brand-orange/50 transition-all duration-300 hover:-translate-y-2" data-aos="fade-up">
                    <div class="w-16 h-16 <?php echo $bgIcon; ?> rounded-2xl flex items-center justify-center text-3xl mb-6 transition-transform group-hover:scale-110 group-hover:rotate-3 shadow-inner">
                        <i class="<?php echo htmlspecialchars($iconClass); ?>"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-gray-800 dark:text-white group-hover:text-brand-orange transition"><?php echo htmlspecialchars($svc['name']); ?></h3>
                    <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed mb-6 line-clamp-2"><?php echo htmlspecialchars($svc['description']); ?></p>
                    
                    <div class="flex items-center justify-between border-t border-gray-100 dark:border-gray-700 pt-4">
                        <span class="text-xs text-gray-400 font-bold uppercase tracking-wider">Mulai</span>
                        <span class="text-brand-blue dark:text-blue-300 font-bold">Rp <?php echo number_format($svc['base_price'], 0, ',', '.'); ?></span>
                    </div>
                </div>
                <?php 
                    }
                } else {
                    echo '<div class="col-span-4 text-center py-12 bg-gray-100 dark:bg-gray-800 rounded-3xl border border-dashed border-gray-300 dark:border-gray-700">';
                    echo '<i class="fa-solid fa-box-open text-4xl text-gray-400 mb-3"></i>';
                    echo '<p class="text-gray-500">Belum ada layanan aktif yang tersedia.</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Activities / Kegiatan Section (Dynamic from DB) -->
    <section id="kegiatan" class="py-20 bg-gray-50 dark:bg-brand-darker transition-colors duration-300">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16 reveal">
                <h2 class="text-3xl md:text-4xl font-bold text-brand-blue dark:text-white mb-4">Galeri Kegiatan</h2>
                <p class="text-gray-600 dark:text-gray-400 max-w-xl mx-auto">Bukti nyata kerja keras kami di lapangan. Bukan sekedar janji, tapi bukti!</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php
                // Fetch activities from DB
                $sql = "SELECT * FROM portfolio ORDER BY created_at DESC LIMIT 6";
                $result = $conn->query($sql);

                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                ?>
                <!-- Activity Item (Dynamic) -->
                <div class="group relative overflow-hidden rounded-2xl shadow-lg cursor-pointer reveal hover:shadow-2xl transition duration-300">
                    <img src="<?php echo htmlspecialchars($row['image_path']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>" class="w-full h-64 object-cover transform group-hover:scale-110 transition duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent opacity-0 group-hover:opacity-100 transition duration-300 flex flex-col justify-end p-6">
                        <span class="text-brand-orange font-bold text-sm mb-1">Kegiatan Terbaru</span>
                        <h3 class="text-white text-xl font-bold"><?php echo htmlspecialchars($row['title']); ?></h3>
                        <p class="text-gray-300 text-sm mt-2"><?php echo htmlspecialchars($row['description']); ?></p>
                    </div>
                </div>
                <?php 
                    }
                } else {
                ?>
                    <div class="col-span-1 md:col-span-3 text-center py-10">
                        <i class="fa-solid fa-person-digging text-6xl text-gray-300 mb-4 animate-bounce"></i>
                        <h3 class="text-xl font-bold text-gray-500">Wah, belum ada dokumentasi nih!</h3>
                        <p class="text-gray-400">Mimin lagi sibuk di lapangan, nanti difotoin deh kalau sempet. Stay tuned ya!</p>
                    </div>
                <?php
                }
                ?>
            </div>

           
        </div>
    </section>



<!-- Auto Open Modal -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('modal') === 'login') {
            // Wait a bit for other scripts to load
            setTimeout(() => {
                if(typeof openModal === 'function') {
                    openModal('login');
                }
            }, 500);
        }
    });
</script>

<?php include 'includes/footer.php'; ?>