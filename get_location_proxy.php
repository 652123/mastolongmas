<?php
// get_location_proxy.php
// Proxy to fetch address from Nominatim (OSM) to avoid CORS errors on client side

header('Content-Type: application/json');
error_reporting(0); // Suppress warnings

$lat = $_GET['lat'] ?? '';
$lon = $_GET['lon'] ?? '';

if (!$lat || !$lon) {
    echo json_encode(['error' => 'Missing lat/lon parameters']);
    exit;
}

// Nominatim requires a User-Agent identifying the application
$url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lon}";

// Create context with User-Agent
$options = [
    "http" => [
        "header" => "User-Agent: MasTolongMas-App/1.0 (ambaware@example.com)\r\n"
    ]
];
$context = stream_context_create($options);

// Fetch data
$response = file_get_contents($url, false, $context);

if ($response === FALSE) {
    echo json_encode(['error' => 'Failed to fetch external data']);
} else {
    echo $response;
}
?>
