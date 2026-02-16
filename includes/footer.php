    <!-- Footer -->
    <footer class="bg-brand-blue dark:bg-brand-darker text-white pt-16 pb-8 border-t border-blue-800 dark:border-gray-800 relative overflow-hidden transition-colors duration-300">
        <!-- Background Pattern -->
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/black-scales.png')] opacity-10"></div>

        <div class="container mx-auto px-4 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                <!-- Brand Content -->
                <div class="col-span-1 md:col-span-2">
                     <div class="flex items-center gap-2 mb-4">
                        <div class="bg-white p-2 rounded-full">
                            <i class="fa-solid fa-person-running text-brand-orange text-xl"></i>
                        </div>
                        <span class="text-2xl font-bold tracking-tight">MasTolongMas</span>
                    </div>
                    <p class="text-gray-300 mb-6 leading-relaxed max-w-sm">
                        "Apapun masalahmu di Mojokerto, asal sopan dan halal, Mas Aji siap bantu sat-set das-des! Gausah sungkan, kita semua saudara."
                    </p>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gray-700 rounded-full flex items-center justify-center border-2 border-brand-orange">
                            <i class="fa-solid fa-user-tie text-white text-xl"></i>
                        </div>
                        <div>
                            <div class="text-white font-bold">Mas Aji</div>
                            <div class="text-brand-orange text-xs font-bold uppercase">Owner & Founder</div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div>
                    <h4 class="text-lg font-bold mb-6 text-brand-orange">Menu Sat-Set</h4>
                    <ul class="space-y-3">
                        <li><a href="index.php" class="text-gray-300 hover:text-white footer-link transition"><i class="fa-solid fa-chevron-right text-xs text-brand-orange mr-2"></i> Beranda</a></li>
                        <li><a href="order.php" class="text-gray-300 hover:text-white footer-link transition"><i class="fa-solid fa-chevron-right text-xs text-brand-orange mr-2"></i> Order Jasa</a></li>
                        <li><a href="index.php#services" class="text-gray-300 hover:text-white footer-link transition"><i class="fa-solid fa-chevron-right text-xs text-brand-orange mr-2"></i> Cek Layanan</a></li>
                        <li><a href="index.php#kegiatan" class="text-gray-300 hover:text-white footer-link transition"><i class="fa-solid fa-chevron-right text-xs text-brand-orange mr-2"></i> Galeri Kegiatan</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                     <h4 class="text-lg font-bold mb-6 text-brand-orange">Hubungi Pusat</h4>
                     <ul class="space-y-4">
                        <li class="flex items-start gap-3">
                            <i class="fa-brands fa-whatsapp text-green-500 text-xl mt-1"></i>
                            <div>
                                <div class="text-xs text-gray-400">WhatsApp Admin</div>
                                <a href="https://wa.me/6289513768868" target="_blank" class="text-white font-bold hover:text-brand-orange transition">+62 895-1376-8868</a>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fa-brands fa-instagram text-pink-500 text-xl mt-1"></i>
                            <div>
                                <div class="text-xs text-gray-400">Instagram</div>
                                <a href="https://instagram.com/mastolongmas" target="_blank" class="text-white font-bold hover:text-brand-orange transition">@mastolongmas</a>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-location-dot text-red-500 text-xl mt-1"></i>
                            <div>
                                <div class="text-xs text-gray-400">Markas Besar</div>
                                <a href="https://www.google.com/maps/search/?api=1&query=Mojokerto,+Jawa+Timur" target="_blank" class="text-gray-300 hover:text-red-500 transition">Mojokerto, Jawa Timur</a>
                            </div>
                        </li>
                     </ul>
                </div>
            </div>

            <div class="border-t border-blue-800 dark:border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-sm text-gray-400">
                <p>&copy; <?php echo date("Y"); ?> Mas Tolong Mas by <b>Mas Aji</b>. All Rights Reserved.</p>
                <div class="flex gap-6">
                    <span class="text-gray-500">Dibuat dengan <i class="fa-solid fa-heart text-red-500 animate-pulse"></i> untuk Warga Mojokerto</span>
                </div>
            </div>
        </div>
    </footer>


    <?php include 'includes/modals.php'; ?>

    <!-- Scripts -->
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/script.js?v=<?php echo time(); ?>"></script>
    <!-- AOS Animation JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <!-- Vanilla Tilt JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.7.0/vanilla-tilt.min.js"></script>
    <script>
        // --- FAILSAFE PRELOADER KILLER ---
        // Ensuring preloader disappears no matter what!
        setTimeout(function() {
            var preloader = document.getElementById('preloader');
            if(preloader) {
                preloader.style.opacity = '0';
                preloader.style.pointerEvents = 'none';
                setTimeout(function() { preloader.remove(); }, 500);
            }
        }, 1500); // Wait 1.5s max then kill

        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });
        
        // Initialize Vanilla Tilt (Auto-detected by data-tilt attribute, but safe to have)
    </script>
</body>
</html>
