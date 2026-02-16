<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?login=true");
    exit;
}
include_once 'includes/config.php';
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<!-- Leaflet Routing Machine CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />




<style>
    #map { height: 500px; width: 100%; border-radius: 1rem; z-index: 10; }
    /* Hide Default Routing Panel (We use our own UI) */
    .leaflet-routing-container { display: none !important; }
    
    .step-active { border-color: #F97316; background-color: #FFF7ED; }
    .step-inactive { opacity: 0.6; pointer-events: none; }
</style>

<section class="pt-32 pb-20 bg-gray-50 dark:bg-brand-darker min-h-screen transition-colors duration-300">
    <div class="container mx-auto px-4">
        
        <div class="max-w-5xl mx-auto bg-white dark:bg-gray-900 rounded-3xl shadow-xl overflow-hidden border border-transparent dark:border-gray-800">
            <div class="bg-brand-blue p-6 text-white text-center relative overflow-hidden">
                <div class="relative z-10">
                    <h1 class="text-3xl font-bold mb-2">Order Layanan Sat-Set</h1>
                    <p class="opacity-90">Hitung jarak & harga akurat lewat jalur jalan raya!</p>
                </div>
<!-- Decorative Circle -->
                <div class="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 bg-white opacity-10 rounded-full"></div>
            </div>

            <!-- QUEUE ALERT: Check if driver is busy -->
            <?php
            // Check for any order in 'accepted' state (Driver is busy)
            $busyCheck = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE status = 'accepted'");
            $isBusy = $busyCheck && $busyCheck->fetch_assoc()['cnt'] > 0;

            if ($isBusy):
            ?>
            <div class="bg-yellow-50 dark:bg-yellow-900/30 border-l-4 border-yellow-500 p-4 m-6 mb-0 rounded-r-lg flex items-start gap-3 animate-pulse">
                <i class="fa-solid fa-traffic-light text-yellow-600 dark:text-yellow-400 text-xl mt-1"></i>
                <div>
                    <h3 class="font-bold text-yellow-800 dark:text-yellow-200">Info Antrean Driver</h3>
                    <p class="text-sm text-yellow-700 dark:text-yellow-300">
                        Saat ini driver <strong>sedang dalam pengantaran</strong> pesanan lain. 
                        Order kamu tetap akan diterima, tapi masuk antrean dan diproses segera setelahnya ya! Mohon bersabar. 🙏
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <div class="p-4 md:p-8 grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- Left Column: Form (5 Cols) -->
                <div class="lg:col-span-5 space-y-6">
                    <form id="orderForm" class="space-y-4">
                        
                         <!-- Service Type -->
                         <div>
                            <label class="block text-gray-700 dark:text-gray-300 font-bold mb-2 text-sm uppercase tracking-wide">1. Pilih Layanan</label>
                            <select id="serviceType" onchange="handleServiceChange()" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white text-sm rounded-xl focus:ring-brand-orange focus:border-brand-orange block p-3 transition-colors">
                                <option value="" disabled selected>-- Pilih Layanan --</option>
                                <?php
                                $svc_sql = "SELECT * FROM services WHERE is_active = 1";
                                $svc_res = $conn->query($svc_sql);
                                
                                if (!$svc_res) {
                                    echo '<option disabled>Error Loading Services</option>';
                                    error_log("Service Query Error: " . $conn->error);
                                } elseif ($svc_res->num_rows == 0) {
                                    echo '<option disabled>Tidak ada layanan aktif</option>';
                                } else {
                                    while($svc = $svc_res->fetch_assoc()) {
                                        echo '<option value="'.htmlspecialchars($svc['name']).'" data-category="'.htmlspecialchars($svc['category']).'" data-description="'.htmlspecialchars($svc['description']).'">'.htmlspecialchars($svc['name']).'</option>';
                                    }
                                }
                                ?>
                            </select>
                            <p id="serviceDescription" class="mt-2 text-xs text-gray-500 dark:text-gray-400 italic">
                                <!-- Dynamic Description -->
                            </p>
                        </div>

                        <!-- Step 1: Pickup -->
                        <div id="step1-container" class="border-2 border-brand-orange bg-orange-50 dark:bg-orange-900/30 rounded-xl p-4 transition-all duration-300">
                            <label class="flex items-center gap-2 text-brand-blue dark:text-blue-300 font-bold mb-2">
                                <span class="bg-brand-blue text-white w-6 h-6 flex items-center justify-center rounded-full text-xs">A</span>
                                Lokasi Penjemputan
                            </label>
                            <div class="flex gap-2 mb-2">
                                <input type="text" id="pickupInput" placeholder="Geser peta ke titik jemput..." class="w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg p-2 text-sm dark:text-white" readonly>
                                <button type="button" onclick="locateUser()" class="bg-blue-100 dark:bg-blue-900 text-brand-blue dark:text-blue-100 px-3 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-800" title="Lokasi Saya">
                                    <i class="fa-solid fa-crosshairs"></i>
                                </button>
                            </div>
                            <button type="button" onclick="confirmPickup()" id="btnConfirmPickup" class="w-full bg-brand-blue text-white text-sm font-bold py-2 rounded-lg hover:bg-blue-700 transition">
                                Lanjut ke Tujuan <i class="fa-solid fa-arrow-right ml-1"></i>
                            </button>
                        </div>

                        <!-- Step 2: Dropoff -->
                        <div id="step2-container" class="border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 rounded-xl p-4 transition-all duration-300 step-inactive">
                            <label class="flex items-center gap-2 text-gray-600 dark:text-gray-300 font-bold mb-2">
                                <span class="bg-brand-orange text-white w-6 h-6 flex items-center justify-center rounded-full text-xs">B</span>
                                Lokasi Tujuan
                            </label>
                            <div class="mb-2">
                                <input type="text" id="dropoffInput" placeholder="Klik peta untuk tujuan..." class="w-full bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg p-2 text-sm dark:text-white" readonly>
                            </div>
                            <button type="button" onclick="resetLocations()" class="text-xs text-red-500 hover:text-red-700 underline">
                                Reset / Ulangi
                            </button>
                            <button type="button" onclick="clearAutoSave()" class="text-xs text-gray-500 hover:text-gray-700 underline ml-4" title="Hapus simpanan data">
                                <i class="fa-solid fa-eraser"></i> Bersihkan Formulir
                            </button>
                        </div>

                        <!-- Weight Input (Courier Only) -->
                        <div id="weightContainer" class="hidden border-2 border-orange-400 dark:border-orange-600 bg-orange-50 dark:bg-orange-900/20 rounded-xl p-4 transition-all duration-300">
                            <label class="flex items-center gap-2 text-orange-700 dark:text-orange-300 font-bold mb-2 text-sm">
                                <i class="fa-solid fa-weight-hanging"></i>
                                Berat Barang (Kg)
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="number" id="weightInput" min="1" max="20" value="1" onchange="updatePrice()" oninput="checkWeightLimit(this); updatePrice()" class="w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 text-sm dark:text-white font-bold placeholder-gray-400">
                                <span class="text-gray-500 dark:text-gray-400 font-bold">Kg</span>
                            </div>
                            <p class="text-[10px] text-orange-600 dark:text-orange-400 mt-2"><i class="fa-solid fa-circle-info"></i> Berat mempengaruhi harga ongkir.</p>
                            <p id="weightLimitMsg" class="text-[10px] text-red-500 mt-1 font-bold hidden"><i class="fa-solid fa-triangle-exclamation"></i> Maaf, motor tidak kuat bawa lebih dari <span id="maxWeightDisp">20</span>kg 🙏.</p>
                        </div>

                        <!-- Estimasi Biaya Belanja (Only for Jasa Belanja) -->
                        <div id="shoppingEstimateContainer" class="hidden border-2 border-green-400 dark:border-green-600 bg-green-50 dark:bg-green-900/20 rounded-xl p-4 transition-all duration-300">
                            <label class="flex items-center gap-2 text-green-700 dark:text-green-300 font-bold mb-2 text-sm">
                                <i class="fa-solid fa-cart-shopping"></i>
                                Estimasi Biaya Belanja
                            </label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Masukkan perkiraan total harga barang yang mau dibelikan (sayur, makanan, dll).</p>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500 dark:text-gray-400 font-bold">Rp</span>
                                <input type="number" id="shoppingEstimate" min="0" max="100000" step="1000" value="0" onchange="updatePrice()" oninput="checkLimit(this); updatePrice()" class="w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 text-sm dark:text-white font-bold" placeholder="50000">
                            </div>
                            <p class="text-[10px] text-green-600 dark:text-green-400 mt-2"><i class="fa-solid fa-circle-info"></i> Biaya belanja akan ditambahkan ke total. Selisih harga disesuaikan saat pengantaran.</p>
                            <p class="text-[10px] text-red-500 mt-1 font-bold"><i class="fa-solid fa-triangle-exclamation"></i> Maksimal belanja Rp 100.000 (Sesuai SOP Safety).</p>
                        </div>

                        <!-- Notes / Catatan -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Catatan Tambahan (Opsional)</label>
                            <textarea id="notes" rows="2" class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 focus:border-blue-500 focus:ring-blue-500 transition-all text-sm resize-none dark:text-white" placeholder="Contoh: Titip ke satpam, rumah pagar hitam..."></textarea>
                        </div>

                        <!-- Payment Method (COD ONLY) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Metode Pembayaran</label>
                            <input type="hidden" name="paymentMethod" value="cash">
                            <div class="p-4 rounded-xl border-2 border-green-500 bg-green-50 dark:bg-green-900/20 flex items-center gap-3">
                                <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white text-lg flex-shrink-0">
                                    <i class="fa-solid fa-money-bill-wave"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-green-700 dark:text-green-300">Bayar di Tempat (COD)</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Bayar tunai langsung ke Mas Aji saat jasa selesai.</p>
                                </div>
                                <i class="fa-solid fa-circle-check text-green-500 text-xl ml-auto"></i>
                            </div>
                        </div>        

                        <!-- Price Summary -->
                        <div class="bg-gradient-to-r from-orange-50 to-orange-100 dark:from-gray-800 dark:to-gray-800 p-5 rounded-2xl border border-orange-200 dark:border-gray-700 shadow-sm">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-gray-600 dark:text-gray-400 text-sm">Jarak via Jalan Raya</span>
                                <span id="distanceDisplay" class="font-bold text-gray-800 dark:text-white">0 km</span>
                            </div>
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-gray-600 dark:text-gray-400 text-sm">Waktu Tempuh</span>
                                <span id="durationDisplay" class="font-bold text-gray-800 dark:text-white">- menit</span>
                            </div>
                            <div class="border-t border-orange-200 dark:border-gray-700 my-2"></div>
                            <div class="flex justify-between items-center mb-1">
                                <span id="ongkirLabel" class="text-gray-600 dark:text-gray-400 text-sm">Ongkos Jasa</span>
                                <span id="ongkirDisplay" class="font-bold text-gray-800 dark:text-white">Rp 0</span>
                            </div>
                            <div id="shoppingRow" class="hidden flex justify-between items-center mb-1">
                                <span class="text-gray-600 dark:text-gray-400 text-sm"><i class="fa-solid fa-cart-shopping"></i> Estimasi Belanja</span>
                                <span id="shoppingDisplay" class="font-bold text-green-600 dark:text-green-400">Rp 0</span>
                            </div>
                            <div id="weightRow" class="hidden flex justify-between items-center mb-1">
                                <span id="weightLabel" class="text-gray-600 dark:text-gray-400 text-sm"><i class="fa-solid fa-weight-hanging text-orange-500"></i> Surcharge Berat</span>
                                <span id="weightDisplay" class="font-bold text-orange-600 dark:text-orange-400">Rp 0</span>
                            </div>
                            <div class="border-t border-orange-200 dark:border-gray-700 my-2"></div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-700 dark:text-gray-300 font-bold">TOTAL BAYAR</span>
                                <span id="priceDisplay" class="font-bold text-3xl text-brand-orange">Rp 0</span>
                            </div>
                        </div>

                        <!-- Action Button -->
                         <button type="button" onclick="submitOrder()" id="btnOrder" disabled class="w-full bg-gray-300 text-gray-500 font-bold py-4 rounded-xl transition-all duration-300 transform hover:scale-[1.02] flex items-center justify-center gap-2 cursor-not-allowed shadow-sm">
                            <i class="fa-solid fa-paper-plane text-xl"></i> BUAT PESANAN
                        </button>
                    </form>
                </div>

                <!-- Right Column: Map (7 Cols) -->
                <div class="lg:col-span-7 relative h-full min-h-[500px]">
                    <div id="map" class="h-full w-full rounded-2xl shadow-inner border-4 border-white"></div>
                    
                    <!-- Floating Instruction -->
                    <div id="mapInstruction" class="absolute top-4 left-1/2 transform -translate-x-1/2 bg-white/95 backdrop-blur px-6 py-3 rounded-full shadow-xl z-[400] text-sm font-bold text-brand-blue border border-blue-100 flex items-center gap-2 transition-all">
                        <i class="fa-solid fa-map-pin animate-bounce"></i>
                        <span>Geser Peta untuk Tentukan Titik Jemput</span>
                    </div>

                     <!-- Loading Overlay -->
                     <div id="loadingOverlay" class="absolute inset-0 bg-white/80 z-[500] flex flex-col items-center justify-center rounded-2xl hidden">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-orange"></div>
                        <p class="mt-2 text-sm font-semibold text-gray-600">Menghitung Rute...</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<!-- Leaflet Routing Machine JS -->
<script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>

<script>
    // --- CONFIGURATION ---
    console.log("Mastolongmas Order Script Loaded: v2026-02-15-FIXED");
    const CSRF_TOKEN = "<?php echo $_SESSION['csrf_token']; ?>";
    const MOJOKERTO_CENTER = [-7.463, 112.433];
    
    // Pricing Rules (Dynamic FROM DB)
    const PRICING_RULES = {
        'default': { base: 5000, perKm: 2500, min: 10000 },
        <?php 
        // Re-use fetched services to generate JS object
        $svc_res->data_seek(0); // Reset pointer
        while($s = $svc_res->fetch_assoc()) {
            echo "'".htmlspecialchars($s['name'])."': { base: ".(int)$s['base_price'].", perKm: ".(int)$s['price_per_km'].", min: ".(int)$s['min_price'].", maxWeight: ".(int)($s['max_weight'] ?? 20).", weightPrice: ".(int)($s['price_per_kg'] ?? 0)." },\n";
        }
        ?>
    };

    // --- STATE ---
    let map;
    let pickupMarker; // Only for initial selection
    let routingControl;
    let pickupLatLng = null;
    let dropoffLatLng = null;
    let currentStep = 1; // 1: Pickup, 2: Dropoff
    let calculatedDistanceKm = 0;
    let calculatedPrice = 0;

    // --- INIT MAP ---
    map = L.map('map').setView(MOJOKERTO_CENTER, 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    // Icons
    const greenIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png',
        iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34]
    });
    const redIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
        iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34]
    });

    // Initial Pickup Marker
    pickupMarker = L.marker(MOJOKERTO_CENTER, {draggable: true, icon: greenIcon}).addTo(map);
    pickupMarker.bindPopup("Geser saya ke lokasi jemput!").openPopup();

    // Events
    pickupMarker.on('dragend', function(e) {
        const latlng = e.target.getLatLng();
        updateAddressText(latlng, 'pickupInput');
    });

    map.on('click', function(e) {
        if (currentStep === 1) {
            pickupMarker.setLatLng(e.latlng);
            updateAddressText(e.latlng, 'pickupInput');
        } else if (currentStep === 2) {
            handleDropoffSelection(e.latlng);
        }
    });

    // Initial Address
    updateAddressText(L.latLng(MOJOKERTO_CENTER), 'pickupInput');

    // --- LOGIC ---
    function confirmPickup() {
        pickupLatLng = pickupMarker.getLatLng();
        if(!pickupLatLng) { 
            MasAlert.fire({
                icon: 'warning',
                title: 'Lokasi Belum Dipilih',
                text: 'Tentukan lokasi jemput dulu ya!',
                timer: 2000,
                showConfirmButton: false
            });
            return; 
        }

        // Switch to Step 2
        currentStep = 2;
        document.getElementById('step1-container').classList.add('step-inactive', 'opacity-50');
        document.getElementById('step1-container').classList.remove('border-brand-orange', 'bg-orange-50');
        
        document.getElementById('step2-container').classList.remove('step-inactive');
        document.getElementById('step2-container').classList.add('border-brand-orange', 'bg-orange-50', 'shadow-lg');
        
        document.getElementById('mapInstruction').innerHTML = '<i class="fa-solid fa-map-pin text-red-500"></i> KLIK Peta untuk Tujuan';
        
        pickupMarker.dragging.disable();
        map.closePopup();
    }

    function handleDropoffSelection(latlng) {
        dropoffLatLng = latlng;
        
        // Remove existing routing if any
        if (routingControl) {
            map.removeControl(routingControl);
        }

        // Show Loading (Safe)
        const overlay = document.getElementById('loadingOverlay');
        if(overlay) overlay.classList.remove('hidden');

        // Calculate Route using OSRM
        routingControl = L.Routing.control({
            waypoints: [
                L.latLng(pickupLatLng.lat, pickupLatLng.lng),
                L.latLng(dropoffLatLng.lat, dropoffLatLng.lng)
            ],
            router: L.Routing.osrmv1({
                serviceUrl: 'https://router.project-osrm.org/route/v1'
            }),
            lineOptions: {
                styles: [{color: '#F97316', opacity: 0.8, weight: 6}]
            },
            createMarker: function(i, wp, nWps) {
                if (i === 0) return L.marker(wp.latLng, {icon: greenIcon}).bindPopup('Jemput');
                if (i === nWps - 1) return L.marker(wp.latLng, {icon: redIcon}).bindPopup('Tujuan');
                return null;
            },
            addWaypoints: false,
            draggableWaypoints: false,
            fitSelectedRoutes: true,
            showAlternatives: false
        })
        .on('routesfound', function(e) {
            const routes = e.routes;
            const summary = routes[0].summary;
            
            // Update UI
            calculatedDistanceKm = summary.totalDistance / 1000;
            const durationMins = Math.round(summary.totalTime / 60);
            
            const distDisp = document.getElementById('distanceDisplay');
            if(distDisp) distDisp.innerText = calculatedDistanceKm.toFixed(1) + " km";
            
            const durDisp = document.getElementById('durationDisplay');
            if(durDisp) durDisp.innerText = durationMins + " menit";
            
            updateAddressText(dropoffLatLng, 'dropoffInput');
            updatePrice();
            
            // Hide Loading (Safe)
            if(overlay) overlay.classList.add('hidden');
        })
        .on('routingerror', function(err) {
            console.error('Routing Error:', err);
            // alert('Gagal menghitung rute. Coba titik lain.'); // Suppress alert for better UX on minor errors
            if(overlay) overlay.classList.add('hidden');
        })
        .addTo(map);
        
        // Save after route found
        saveFormData();
    }

    function updatePrice() {
        if (calculatedDistanceKm <= 0) return;

        const serviceEl = document.getElementById('serviceType');
        if(!serviceEl) return;
        
        const serviceType = serviceEl.value;
        const selectedOption = document.querySelector('#serviceType option:checked');
        const category = selectedOption ? selectedOption.getAttribute('data-category') : 'other';
        
        const rule = PRICING_RULES[serviceType] || PRICING_RULES['default'];

        let ongkir = rule.base + (Math.ceil(calculatedDistanceKm) * rule.perKm);
        if (ongkir < rule.min) ongkir = rule.min;

        // Shopping Estimate (Active for Jasa Belanja & Titip Makanan)
        let shopping = 0;
        const shoppingContainer = document.getElementById('shoppingEstimateContainer');
        const shoppingRow = document.getElementById('shoppingRow');
        const shoppingLabel = document.querySelector('#shoppingEstimateContainer label');
        
        if (shoppingContainer && shoppingRow) {
            if (category === 'shopping' || category === 'food') {
                shoppingContainer.classList.remove('hidden');
                shoppingRow.classList.remove('hidden');
                shopping = parseInt(document.getElementById('shoppingEstimate').value) || 0;
                // Update label based on service
                if (shoppingLabel) {
                    if (category === 'food') {
                        shoppingLabel.innerHTML = '<i class="fa-solid fa-utensils"></i> Estimasi Harga Makanan';
                    } else {
                        shoppingLabel.innerHTML = '<i class="fa-solid fa-cart-shopping"></i> Estimasi Biaya Belanja';
                    }
                }
            } else {
                shoppingContainer.classList.add('hidden');
                shoppingRow.classList.add('hidden');
                document.getElementById('shoppingEstimate').value = 0;
            }
        }

        // Weight Surcharge (Couriers Only)
        let weightSurcharge = 0;
        const weightContainer = document.getElementById('weightContainer');
        const weightRow = document.getElementById('weightRow');
        const weightInput = document.getElementById('weightInput');
        const weightLimitMsg = document.getElementById('weightLimitMsg');
        const maxWeightDisp = document.getElementById('maxWeightDisp');

        if (weightContainer && weightInput) {
            if (category === 'courier') {
                weightContainer.classList.remove('hidden');
                const weight = parseInt(weightInput.value) || 1;
                const maxWeight = rule.maxWeight || 20; // Default 20kg
                const weightPrice = rule.weightPrice || 0;

                if(maxWeightDisp) maxWeightDisp.innerText = maxWeight;
                weightInput.max = maxWeight;

                if (weight > maxWeight && weightLimitMsg) {
                    weightLimitMsg.classList.remove('hidden');
                } else if (weightLimitMsg) {
                    weightLimitMsg.classList.add('hidden');
                }

                if (weightPrice > 0 && weightRow) {
                     weightRow.classList.remove('hidden');
                     weightSurcharge = weight * weightPrice;
                } else if (weightRow) {
                     weightRow.classList.add('hidden');
                }

            } else {
                weightContainer.classList.add('hidden');
                if(weightRow) weightRow.classList.add('hidden');
                weightInput.value = 1;
            }
        }

        const total = ongkir + shopping + weightSurcharge;
        calculatedPrice = total;

        // Update UI Breakdown
        const ongkirDisp = document.getElementById('ongkirDisplay');
        if(ongkirDisp) ongkirDisp.innerText = "Rp " + ongkir.toLocaleString('id-ID');
        
        // Dynamic Label for Distance
        const ongkirLabel = document.getElementById('ongkirLabel');
        if(ongkirLabel) ongkirLabel.innerText = "Ongkos Kirim (" + calculatedDistanceKm.toFixed(1) + " km)";

        const shopDisp = document.getElementById('shoppingDisplay');
        if(shopDisp) shopDisp.innerText = "Rp " + shopping.toLocaleString('id-ID');
        
        const wDisp = document.getElementById('weightDisplay');
        if(wDisp) wDisp.innerText = "Rp " + weightSurcharge.toLocaleString('id-ID');
        
        // Dynamic Label for Weight
        const wLabel = document.getElementById('weightLabel');
        const wInput = document.getElementById('weightInput');
        if(wLabel && wInput) {
             const currentWeight = parseInt(wInput.value) || 1;
             wLabel.innerHTML = '<i class="fa-solid fa-weight-hanging text-orange-500"></i> Biaya Berat (' + currentWeight + ' kg)';
        }
        
        const pDisp = document.getElementById('priceDisplay');
        if(pDisp) pDisp.innerText = "Rp " + total.toLocaleString('id-ID');

        // Enable Button
        const btn = document.getElementById('btnOrder');
        if (btn) {
            btn.disabled = false;
            btn.classList.remove('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
            btn.classList.add('bg-green-500', 'text-white', 'hover:bg-green-600', 'shadow-lg');
        }
    }

    function resetLocations() {
        // Reload page logic or reset vars
        location.reload(); 
        // Or simpler: reset vars and UI manually
    }

    // --- UTILS ---

    async function updateAddressText(latlng, elementId) {
        const el = document.getElementById(elementId);
        el.value = `${latlng.lat.toFixed(5)}, ${latlng.lng.toFixed(5)} (Loading...)`;

        try {
            // Use local proxy to avoid CORS errors
            const response = await fetch(`get_location_proxy.php?lat=${latlng.lat}&lon=${latlng.lng}`);
            const data = await response.json();
            if (data && data.display_name) {
                // Formatting address to be shorter
                const parts = data.display_name.split(',');
                // Take first 3 parts (Street, District, City usually)
                const shortAddress = parts.slice(0, 3).join(', ');
                el.value = shortAddress;
            }
        } catch (e) {
            el.value = `${latlng.lat.toFixed(5)}, ${latlng.lng.toFixed(5)}`;
        }
    }

    function locateUser() {
        map.locate({setView: true, maxZoom: 16});
    }

    async function submitOrder() {
        if (!calculatedPrice) return;

        const btn = document.getElementById('btnOrder');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan Order...';

        const service = document.getElementById('serviceType').value;
        const pickup = document.getElementById('pickupInput').value;
        const dropoff = document.getElementById('dropoffInput').value;
        const notes = document.getElementById('notes').value;
        const paymentMethod = 'cash'; // COD ONLY
        const dist = calculatedDistanceKm.toFixed(1);
        const shoppingEstimate = parseInt(document.getElementById('shoppingEstimate').value) || 0;

        // Prepare Data
        const orderData = {
            csrf_token: CSRF_TOKEN,
            serviceType: service,
            pickup: pickup,
            pickupLat: pickupLatLng.lat,
            pickupLng: pickupLatLng.lng,
            dropoff: dropoff,
            dropoffLat: dropoffLatLng.lat,
            dropoffLng: dropoffLatLng.lng,
            distance: dist,
            price: calculatedPrice,
            shoppingEstimate: shoppingEstimate,
            notes: notes,
            paymentMethod: paymentMethod,
            weight: parseInt(document.getElementById('weightInput').value) || 0
        };

        try {
            // Send to Backend
            const response = await fetch('process_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(orderData)
            });
            const result = await response.json();

            if (result.status === 'success') {
                MasAlert.fire({
                    icon: 'success',
                    title: 'Order Berhasil! 🎉',
                    text: 'Mohon tunggu Mas Aji menghubungi kamu. Cek status di menu Riwayat.',
                    confirmButtonText: 'Siap, Ditunggu!'
                }).then(() => {
                    window.location.href = 'history.php';
                });
            } else {
                MasAlert.fire({
                    icon: 'error',
                    title: 'Gagal Menyimpan Order',
                    text: result.message
                });
                btn.disabled = false;
                btn.innerHTML = originalText;
            }

        } catch (error) {
            console.error(error);
            MasAlert.fire({
                icon: 'error',
                title: 'Terkendala Jaringan',
                text: 'Cek koneksi internet kamu ya.'
            });
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }

    // --- UI/UX LOGIC ---

    function handleServiceChange() {
        // 1. Update Description
        const select = document.getElementById('serviceType');
        const selectedOption = select.options[select.selectedIndex];
        const desc = selectedOption.getAttribute('data-description');
        const descEl = document.getElementById('serviceDescription');
        if(descEl) descEl.innerText = desc || '';

        // 2. Update Form Labels (Placeholders)
        updateFormLabels(select.value);

        // 3. Update Price Logic
        updatePrice();
    }

    function selectService(serviceName, cardElement) {
        // Update Hidden Input (NOT USED ANYMORE since we utilize standard select, but kept if needed)
        // Actually, this function seems to be for card selection which we removed.
        // Keeping it compatible or removing if unused. 
        // Based on current file, cards are gone. 
        // We solely rely on <select> change.
    }

    function updateFormLabels(serviceName) {
        const selectedOption = document.querySelector('#serviceType option[value="'+serviceName+'"]');
        // If triggered by onchange without arg, get value from select
        const currentSelect = document.getElementById('serviceType');
        const category = currentSelect.options[currentSelect.selectedIndex].getAttribute('data-category');

        const pickupLabel = document.querySelector('#step1-container label');
        const dropoffLabel = document.querySelector('#step2-container label');
        const pickupInput = document.getElementById('pickupInput');
        const dropoffInput = document.getElementById('dropoffInput');
        const notesField = document.getElementById('notes');

        switch(category) {
            case 'courier':
                pickupLabel.innerHTML = '<span class="bg-brand-blue text-white w-6 h-6 flex items-center justify-center rounded-full text-xs mr-2">A</span> Lokasi Pengambilan Paket';
                pickupInput.placeholder = "Geser peta ke lokasi ambil paket...";
                dropoffLabel.innerHTML = '<span class="bg-brand-orange text-white w-6 h-6 flex items-center justify-center rounded-full text-xs mr-2">B</span> Lokasi Tujuan Kirim';
                dropoffInput.placeholder = "Klik peta untuk tujuan kirim...";
                notesField.placeholder = "Contoh: Paket di meja satpam, tolong jangan dibanting ya...";
                break;

            case 'shopping':
                pickupLabel.innerHTML = '<span class="bg-brand-blue text-white w-6 h-6 flex items-center justify-center rounded-full text-xs mr-2">A</span> Lokasi Toko / Pasar';
                pickupInput.placeholder = "Geser peta ke Pasar/Toko...";
                dropoffLabel.innerHTML = '<span class="bg-brand-orange text-white w-6 h-6 flex items-center justify-center rounded-full text-xs mr-2">B</span> Lokasi Pengantaran (Rumah)';
                dropoffInput.placeholder = "Klik peta lokasi rumah...";
                notesField.placeholder = "Contoh: Beli sayur bayam 1 ikat, tahu 5rb, tempe 3rb...";
                break;

            case 'food':
                pickupLabel.innerHTML = '<span class="bg-brand-blue text-white w-6 h-6 flex items-center justify-center rounded-full text-xs mr-2">A</span> Lokasi Resto / Warung';
                pickupInput.placeholder = "Geser peta ke Resto/Warung...";
                dropoffLabel.innerHTML = '<span class="bg-brand-orange text-white w-6 h-6 flex items-center justify-center rounded-full text-xs mr-2">B</span> Lokasi Pengantaran';
                dropoffInput.placeholder = "Klik peta lokasi antar...";
                notesField.placeholder = "Contoh: Nasi goreng 2, es teh manis 2, level pedas...";
                break;

            case 'ride':
                pickupLabel.innerHTML = '<span class="bg-brand-blue text-white w-6 h-6 flex items-center justify-center rounded-full text-xs mr-2">A</span> Lokasi Penjemputan';
                pickupInput.placeholder = "Geser peta ke lokasi jemput...";
                dropoffLabel.innerHTML = '<span class="bg-brand-orange text-white w-6 h-6 flex items-center justify-center rounded-full text-xs mr-2">B</span> Lokasi Tujuan Antar';
                dropoffInput.placeholder = "Klik peta untuk tujuan antar...";
                notesField.placeholder = "Contoh: Jemput anak sekolah, pakai seragam merah, nama Budi...";
                break;

            default: // Other/Palugada
                pickupLabel.innerHTML = '<span class="bg-brand-blue text-white w-6 h-6 flex items-center justify-center rounded-full text-xs mr-2">A</span> Lokasi Awal';
                pickupInput.placeholder = "Geser peta ke titik awal...";
                dropoffLabel.innerHTML = '<span class="bg-brand-orange text-white w-6 h-6 flex items-center justify-center rounded-full text-xs mr-2">B</span> Lokasi Tujuan';
                dropoffInput.placeholder = "Klik peta untuk tujuan...";
                notesField.placeholder = "Jelaskan kebutuhan kamu secara detail di sini...";
                break;
        }
    }

    function checkLimit(el) {
        if (parseInt(el.value) > 100000) {
            MasAlert.fire({
                icon: 'info',
                title: 'Maksimal Rp 100.000',
                text: 'Maaf ya kak, maksimal belanja Rp 100.000 biar driver kuat nalangin 🙏'
            });
            el.value = 100000;
        }
    }

    function checkWeightLimit(el) {
        const serviceType = document.getElementById('serviceType').value;
        const rule = PRICING_RULES[serviceType] || PRICING_RULES['default'];
        const max = rule.maxWeight || 20;

        if (parseInt(el.value) > max) {
            document.getElementById('weightLimitMsg').classList.remove('hidden');
            // Allow input but warn, or cap it? Let's cap it for safety
            // el.value = max; // Optional: Force Cap
        } else {
             document.getElementById('weightLimitMsg').classList.add('hidden');
        }
    }

    // --- AUTO-SAVE FEATURE ---
    const STORAGE_KEY = 'mastolongmas_order_draft';

    function saveFormData() {
        // Only save if elements exist
        const serviceEl = document.getElementById('serviceType');
        const pickupEl = document.getElementById('pickupInput');
        
        if (!serviceEl) return;

        const data = {
            serviceType: serviceEl.value,
            pickup: pickupEl.value,
            pickupLat: pickupLatLng ? pickupLatLng.lat : null,
            pickupLng: pickupLatLng ? pickupLatLng.lng : null,
            dropoffLat: dropoffLatLng ? dropoffLatLng.lat : null,
            dropoffLng: dropoffLatLng ? dropoffLatLng.lng : null,
            dropoff: document.getElementById('dropoffInput').value,
            notes: document.getElementById('notes').value,
            shoppingEstimate: document.getElementById('shoppingEstimate').value,
            step: currentStep
        };
        localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
    }

    function loadFormData() {
        const kept = localStorage.getItem(STORAGE_KEY);
        if (!kept) return;

        try {
            const data = JSON.parse(kept);
            
            // Restore Service
            if (data.serviceType) {
                document.getElementById('serviceType').value = data.serviceType;
            }

            // Restore Inputs
            if (data.notes) document.getElementById('notes').value = data.notes;
            if (data.shoppingEstimate) document.getElementById('shoppingEstimate').value = data.shoppingEstimate;

            // Restore Locations
            if (data.pickupLat && data.pickupLng) {
                const latlng = { lat: data.pickupLat, lng: data.pickupLng };
                pickupLatLng = latlng;
                pickupMarker.setLatLng(latlng);
                map.setView(latlng, 15);
                updateAddressText(latlng, 'pickupInput'); 
            }

            if (data.dropoffLat && data.dropoffLng) {
                const latlng = { lat: data.dropoffLat, lng: data.dropoffLng };
                dropoffLatLng = latlng;
                // If we had a dropoff, we should be in step 2 (unless user reset)
                if (data.step === 2) {
                    confirmPickup(); 
                    handleDropoffSelection(latlng); 
                }
            }
            
            // Restore Service UI
            updateFormLabels(document.getElementById('serviceType').value);
            updatePrice();

        } catch (e) {
            console.error("Auto-load failed", e);
        }
    }

    function clearAutoSave() {
        if(confirm('Yakin ingin menghapus semua isian formulir?')) {
            localStorage.removeItem(STORAGE_KEY);
            location.reload();
        }
    }

    // Attach Listeners
    document.getElementById('notes').addEventListener('input', saveFormData);
    document.getElementById('shoppingEstimate').addEventListener('input', saveFormData);
    document.getElementById('serviceType').addEventListener('change', function() {
        updateFormLabels(this.value);
        saveFormData();
    });
    
    // Override Confirm Pickup to save
    const originalConfirm = confirmPickup;
    confirmPickup = function() {
        originalConfirm(); 
        saveFormData();
    };

    // Initialize
    window.addEventListener('load', loadFormData);
</script>

<?php include 'includes/footer.php'; ?>
