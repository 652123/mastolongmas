<?php
session_start();
include 'includes/config.php';

header('Content-Type: application/json');
error_reporting(0); // Disable error output to keep JSON clean
ini_set('display_errors', 0);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        // Try $_POST if JSON fails (though we expect JSON from JS)
        $data = $_POST;
    }

    // CSRF Check (supports JSON or POST)
    $token = $data['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!$token || $token !== $_SESSION['csrf_token']) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Security Token. Refresh page.']);
        exit;
    }
    
    // Validate required fields
    if (empty($data['serviceType']) || empty($data['pickup']) || empty($data['dropoff'])) {
         echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
         exit;
    }

    // --- 0. SINGLE COURIER LOGIC (QUEUE LIMITER) ---
    // Check if there is already an active order (accepted)
    // We allow 'pending' orders to pile up? Or do we block them?
    // User agreement: "Kalau Admin sedang mengerjakan 1 order (Accepted), sistem otomatis menolak order baru"
    // So we BLOCK creation of new orders if one is currently being delivered.
    
    $checkBusy = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE status = 'accepted'");
    $busyRow = $checkBusy->fetch_assoc();
    if ($busyRow['cnt'] > 0) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Maaf, kurir sedang sibuk mengantar pesanan lain. Mohon tunggu sebentar sampai pengantaran selesai ya! 🙏'
        ]);
        exit;
    }

    // --- 1. Fetch Service and Pricing Rules from DB ---
    $stmt = $conn->prepare("SELECT * FROM services WHERE name = ? AND is_active = 1");
    $stmt->bind_param("s", $data['serviceType']);
    $stmt->execute();
    $service = $stmt->get_result()->fetch_assoc();

    if (!$service) {
        echo json_encode(['status' => 'error', 'message' => 'Layanan tidak valid atau tidak aktif']);
        exit;
    }

    $service_type = $service['name'];
    $category = $service['category'];
    $rules = [
        'base' => $service['base_price'],
        'perKm' => $service['price_per_km'],
        'min' => $service['min_price'],
        'max_weight' => $service['max_weight'] ?? 20,
        'weight_price' => $service['price_per_kg'] ?? 0
    ];

    // --- 2. Sanitize & Validate Coordinates ---
    $pickup_lat = filter_var($data['pickupLat'], FILTER_VALIDATE_FLOAT);
    $pickup_lng = filter_var($data['pickupLng'], FILTER_VALIDATE_FLOAT);
    $dropoff_lat = filter_var($data['dropoffLat'], FILTER_VALIDATE_FLOAT);
    $dropoff_lng = filter_var($data['dropoffLng'], FILTER_VALIDATE_FLOAT);

    if ($pickup_lat === false || $pickup_lng === false || $dropoff_lat === false || $dropoff_lng === false) {
        echo json_encode(['status' => 'error', 'message' => 'Koordinat tidak valid']);
        exit;
    }

    // --- 3. Validate Distance (Anti-Spoofing) ---
    $input_distance_km = filter_var($data['distance'], FILTER_VALIDATE_FLOAT);
    if ($input_distance_km <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Jarak tidak valid']);
        exit;
    }

    function haversineDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    $min_possible_distance = haversineDistance($pickup_lat, $pickup_lng, $dropoff_lat, $dropoff_lng);
    
    // Allow a very small margin of error (0.001 km) for floating point diffs
    // Road distance MUST be >= Straight Line distance
    if ($input_distance_km < ($min_possible_distance - 0.1)) {
        echo json_encode(['status' => 'error', 'message' => 'Manipulasi jarak terdeteksi! Jarak jalan tidak mungkin lebih pendek dari garis lurus.']);
        exit;
    }

    // --- 4. Server-Side Price Calculation ---
    // Distance Price
    $ongkir = $rules['base'] + (ceil($input_distance_km) * $rules['perKm']);
    if ($ongkir < $rules['min']) {
        $ongkir = $rules['min'];
    }

    // Weight Logic (Courier Only)
    $weight = 0;
    $weight_surcharge = 0;
    if ($category === 'courier') {
        $weight = filter_var($data['weight'] ?? 1, FILTER_VALIDATE_INT);
        if ($weight === false || $weight < 1) $weight = 1;

        if ($weight > $rules['max_weight']) {
             echo json_encode(['status' => 'error', 'message' => "Berat melebihi batas maksimal {$rules['max_weight']}kg untuk layanan ini."]);
             exit;
        }

        if ($rules['weight_price'] > 0) {
            $weight_surcharge = $weight * $rules['weight_price'];
        }
    }

    // Shopping Estimate (for Jasa Belanja & Titip Makanan)
    $shopping_estimate = 0;
    if (($category === 'shopping' || $category === 'food') && isset($data['shoppingEstimate'])) {
        $shopping_estimate = filter_var($data['shoppingEstimate'], FILTER_VALIDATE_INT);
        if ($shopping_estimate === false || $shopping_estimate < 0) {
            $shopping_estimate = 0;
        }
        // Limit Max 100rb per rule
        if ($shopping_estimate > 100000) {
            echo json_encode(['status' => 'error', 'message' => 'Maksimal belanja Rp 100.000 ya kak!']);
            exit;
        }
    }

    $calculated_price = $ongkir + $shopping_estimate + $weight_surcharge;

    // Variable Assignments for DB
    $pickup_address = htmlspecialchars(strip_tags($data['pickup']));
    $dropoff_address = htmlspecialchars(strip_tags($data['dropoff']));
    $distance_km = $input_distance_km;
    $price = $calculated_price; // OVERWRITE client-sent price
    $notes = htmlspecialchars(strip_tags($data['notes']));
    if ($shopping_estimate > 0) {
        $notes .= " [Estimasi Belanja: Rp " . number_format($shopping_estimate, 0, ',', '.') . "]";
    }
    
    $payment_method = 'cash'; // COD ONLY SIMPLIFICATION
    
    // Insert into DB
    try {
        $stmt = $conn->prepare("INSERT INTO orders (user_id, service_type, pickup_location, pickup_lat, pickup_lng, dropoff_location, dropoff_lat, dropoff_lng, distance_km, price, shopping_cost, notes, payment_method, weight) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        // Fixed bind_param: 14 chars for 14 variables
        // i s s d d s d d d d d s s i
        $stmt->bind_param("issddsdddddssi", 
            $user_id, 
            $service_type, 
            $pickup_address, 
            $pickup_lat, 
            $pickup_lng, 
            $dropoff_address, 
            $dropoff_lat, 
            $dropoff_lng, 
            $distance_km, 
            $price, 
            $shopping_estimate,
            $notes,
            $payment_method,
            $weight
        );

        if ($stmt->execute()) {
            $order_id = $conn->insert_id;
            
            // Success response
            echo json_encode([
                'status' => 'success', 
                'order_id' => $order_id,
                'message' => 'Order berhasil disimpan (COD)'
            ]);
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
    } catch (Exception $e) {
         http_response_code(500);
         file_put_contents(__DIR__ . '/error_log.txt', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
         echo json_encode(['status' => 'error', 'message' => 'Internal Server Error: ' . $e->getMessage()]); // Show error for debugging
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request Method']);
}
?>
