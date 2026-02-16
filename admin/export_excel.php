<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: ../login.php"); exit; }
include '../includes/config.php';

// Date Range Filter
$from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-01'); // Default: first day of month
$to = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d'); // Default: today
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Build where
$where = "DATE(orders.created_at) BETWEEN '$from' AND '$to'";
if ($filter !== 'all') { $where .= " AND orders.status = '" . $conn->real_escape_string($filter) . "'"; }
if ($search) { $where .= " AND users.full_name LIKE '%" . $conn->real_escape_string($search) . "%'"; }

// Fetch
$sql = "SELECT orders.*, users.full_name, users.wa_number 
        FROM orders JOIN users ON orders.user_id = users.id 
        WHERE $where
        ORDER BY orders.created_at DESC";
$result = $conn->query($sql);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=laporan_' . $from . '_sd_' . $to . '.csv');

$output = fopen('php://output', 'w');

// BOM for Excel UTF-8 support
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Headers
fputcsv($output, ['ID', 'Tanggal', 'Pelanggan', 'WhatsApp', 'Layanan', 'Jemput', 'Tujuan', 'Jarak (km)', 'Harga (Rp)', 'Metode Bayar', 'Status', 'Catatan']);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['id'],
        $row['created_at'],
        $row['full_name'],
        $row['wa_number'],
        $row['service_type'],
        $row['pickup_location'],
        $row['dropoff_location'],
        $row['distance_km'],
        $row['price'],
        $row['payment_method'] ?? 'cash',
        $row['status'],
        $row['notes']
    ]);
}

fclose($output);
exit;
?>
