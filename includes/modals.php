<!-- Login Modal -->
<div id="loginModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4 sm:p-6 transition-all duration-300 opacity-0 scale-95" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Overlay -->
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="closeModal('login')"></div>

    <!-- Modal Panel -->
    <div class="relative bg-white dark:bg-gray-800 rounded-3xl w-full max-w-md shadow-2xl overflow-hidden transform transition-all border border-gray-100 dark:border-gray-700">
        
        <!-- Header Pattern -->
        <div class="absolute top-0 left-0 right-0 h-32 bg-gradient-to-br from-brand-orange to-red-500 opacity-10"></div>
        <div class="absolute -top-10 -right-10 w-40 h-40 bg-brand-orange rounded-full opacity-20 blur-3xl"></div>
        
        <div class="relative px-8 pt-8 pb-6">
            <button onclick="closeModal('login')" class="absolute top-4 right-4 bg-white/50 dark:bg-gray-700/50 hover:bg-red-50 dark:hover:bg-red-900/20 text-gray-400 hover:text-red-500 p-2 rounded-full transition-all backdrop-blur-sm">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
            
            <div class="text-center mb-8">
                <div class="bg-gradient-to-br from-orange-100 to-red-50 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-inner text-brand-orange text-2xl">
                    <i class="fa-solid fa-right-to-bracket"></i>
                </div>
                <h3 class="text-2xl font-extrabold text-gray-900 dark:text-white mb-1" id="modal-title">Selamat Datang</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Masuk untuk mulai memesan layanan</p>
            </div>

            <div id="loginAlert" class="hidden mb-4 p-3 rounded-xl text-sm text-center font-medium shadow-sm transition-all"></div>

            <form id="loginForm" class="space-y-4">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div>
                    <label class="block text-gray-700 dark:text-gray-300 text-xs font-bold mb-1.5 uppercase tracking-wide">Username</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-brand-orange transition-colors">
                            <i class="fa-solid fa-user"></i>
                        </span>
                        <input type="text" name="username" class="w-full pl-11 pr-4 py-3.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-orange/50 focus:border-brand-orange text-sm font-medium transition-all" placeholder="Ketik username kamu..." required>
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-700 dark:text-gray-300 text-xs font-bold mb-1.5 uppercase tracking-wide">Password</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-brand-orange transition-colors">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" name="password" class="w-full pl-11 pr-4 py-3.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-orange/50 focus:border-brand-orange text-sm font-medium transition-all" placeholder="••••••••" required>
                    </div>
                </div>
                
                <button type="submit" class="w-full bg-gradient-to-r from-brand-orange to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-orange-500/30 transform transition-all active:scale-95 flex items-center justify-center gap-2">
                    <span>Masuk Sekarang</span> <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <div class="mt-8 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Belum punya akun? 
                    <a href="#" onclick="switchModal('login', 'register')" class="text-brand-blue font-bold hover:text-blue-700 transition relative inline-block group">
                        Daftar disini
                        <span class="absolute bottom-0 left-0 w-full h-0.5 bg-brand-blue transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left"></span>
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Register Modal -->
<div id="registerModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4 sm:p-6 transition-all duration-300 opacity-0 scale-95" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="closeModal('register')"></div>

    <div class="relative bg-white dark:bg-gray-800 rounded-3xl w-full max-w-xl shadow-2xl overflow-hidden transform transition-all border border-gray-100 dark:border-gray-700">
        
        <div class="absolute top-0 left-0 right-0 h-32 bg-gradient-to-br from-brand-blue to-purple-600 opacity-10"></div>
        <div class="absolute -top-10 -right-10 w-40 h-40 bg-brand-blue rounded-full opacity-20 blur-3xl"></div>

        <div class="relative px-8 pt-8 pb-6">
            <button onclick="closeModal('register')" class="absolute top-4 right-4 bg-white/50 dark:bg-gray-700/50 hover:bg-red-50 dark:hover:bg-red-900/20 text-gray-400 hover:text-red-500 p-2 rounded-full transition-all backdrop-blur-sm">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
            
            <div class="text-center mb-8">
                <div class="bg-gradient-to-br from-blue-100 to-purple-50 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-inner text-brand-blue text-2xl">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <h3 class="text-2xl font-extrabold text-gray-900 dark:text-white mb-1">Buat Akun Baru</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Gabung gratis dan nikmati kemudahannya</p>
            </div>

            <div id="registerAlert" class="hidden mb-4 p-3 rounded-xl text-sm text-center font-medium shadow-sm transition-all"></div>

            <form id="registerForm" class="space-y-4">
                <input type="hidden" name="action" value="register">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 text-xs font-bold mb-1.5 uppercase tracking-wide">Nama Lengkap</label>
                        <input type="text" name="full_name" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-blue/50 focus:border-brand-blue text-sm font-medium transition-all" placeholder="Nama kamu..." required>
                    </div>
                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 text-xs font-bold mb-1.5 uppercase tracking-wide">WhatsApp</label>
                        <input type="text" name="wa_number" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-blue/50 focus:border-brand-blue text-sm font-medium transition-all" placeholder="08xxxxx" required>
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 dark:text-gray-300 text-xs font-bold mb-1.5 uppercase tracking-wide">Username</label>
                    <input type="text" name="username" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-blue/50 focus:border-brand-blue text-sm font-medium transition-all" placeholder="Username unik..." required>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 text-xs font-bold mb-1.5 uppercase tracking-wide">Password</label>
                        <input type="password" name="password" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-blue/50 focus:border-brand-blue text-sm font-medium transition-all" placeholder="••••••••" required>
                    <!-- Logout Modal -->
<div id="logoutModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4 sm:p-6 transition-all duration-300 opacity-0 scale-95" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="closeModal('logout')"></div>

    <div class="relative bg-white dark:bg-gray-800 rounded-3xl w-full max-w-sm shadow-2xl overflow-hidden transform transition-all border border-gray-100 dark:border-gray-700">
        <div class="p-6 text-center">
            <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 text-red-500 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-sm animate-pulse">
                <i class="fa-solid fa-right-from-bracket text-2xl"></i>
            </div>
            <h3 class="text-xl font-extrabold text-gray-900 dark:text-white mb-2">Yakin Ingin Keluar?</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Kamu harus login lagi nanti untuk memesan layanan.</p>
            
            <div class="flex gap-3">
                <button onclick="closeModal('logout')" class="flex-1 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 font-bold hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    Batal
                </button>
                <a href="logout.php" class="flex-1 py-2.5 rounded-xl bg-red-500 hover:bg-red-600 text-white font-bold shadow-lg shadow-red-500/30 transition flex items-center justify-center gap-2">
                    Ya, Keluar
                </a>
            </div>
        </div>
    </div>
</div>
</div>
                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 text-xs font-bold mb-1.5 uppercase tracking-wide">Konfirmasi</label>
                        <input type="password" name="confirm_password" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-blue/50 focus:border-brand-blue text-sm font-medium transition-all" placeholder="••••••••" required>
                    </div>
                </div>
                
                <button type="submit" class="w-full bg-gradient-to-r from-brand-blue to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-blue-500/30 transform transition-all active:scale-95 flex items-center justify-center gap-2 mt-2">
                    <span>Daftar Sekarang</span> <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>

            <div class="mt-8 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Sudah punya akun? 
                    <a href="#" onclick="switchModal('register', 'login')" class="text-brand-orange font-bold hover:text-orange-700 transition relative inline-block group">
                        Login disini
                        <span class="absolute bottom-0 left-0 w-full h-0.5 bg-brand-orange transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left"></span>
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>
