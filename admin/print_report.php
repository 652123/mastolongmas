<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../index.php?modal=login");
    exit;
}
include '../includes/config.php';

// Filter Logic
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

$where = "DATE(orders.created_at) BETWEEN ? AND ?";
$params = [$startDate, $endDate];
$types = "ss";

if ($search !== '') {
    $where .= " AND (users.full_name LIKE ? OR orders.id LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}
if ($filter !== 'all') {
    $where .= " AND orders.status = ?";
    $params[] = $filter;
    $types .= "s";
}

// Main Query
$sql = "SELECT orders.*, users.full_name, users.wa_number 
        FROM orders 
        JOIN users ON orders.user_id = users.id 
        WHERE $where
        ORDER BY orders.created_at DESC";
$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Summary Metrics Calculation
$totalRevenue = 0;
$totalOrders = 0;
$serviceCounts = [];
$rows = []; // Cache rows to iterate twice

while($row = $result->fetch_assoc()) {
    $rows[] = $row;
    $totalOrders++;
    if($row['status'] == 'completed') {
        $totalRevenue += $row['price'];
    }
    // Count services
    $svc = $row['service_type'];
    if(!isset($serviceCounts[$svc])) $serviceCounts[$svc] = 0;
    $serviceCounts[$svc]++;
}

// Find Top Service
$topService = '-';
$maxCount = 0;
foreach($serviceCounts as $svc => $count) {
    if($count > $maxCount) {
        $maxCount = $count;
        $topService = $svc;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan_MasTolongMas_<?php echo date('Ymd'); ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; padding: 40px; background: #fff; color: #333; line-height: 1.5; }
        
        /* Header */
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; border-bottom: 3px solid #1E3A8A; padding-bottom: 20px; }
        .company-info h1 { margin: 0; font-size: 28px; color: #1E3A8A; text-transform: uppercase; letter-spacing: 1px; }
        .company-info p { margin: 5px 0 0; font-size: 13px; color: #666; }
        .report-meta { text-align: right; }
        .report-meta h2 { margin: 0; font-size: 18px; color: #555; }
        .report-meta p { margin: 5px 0 0; font-size: 13px; color: #888; }
        
        /* Summary Box */
        .summary-box { display: flex; gap: 20px; margin-bottom: 30px; background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; }
        .metric { flex: 1; text-align: center; border-right: 1px solid #cbd5e1; }
        .metric:last-child { border-right: none; }
        .metric-label { font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: bold; margin-bottom: 5px; }
        .metric-value { font-size: 18px; font-weight: bold; color: #1e293b; }
        .metric-value.money { color: #15803d; }

        /* Table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; font-size: 12px; }
        th { background: #1E3A8A; color: white; text-transform: uppercase; padding: 12px 10px; text-align: left; font-size: 11px; letter-spacing: 0.5px; }
        td { padding: 12px 10px; border-bottom: 1px solid #e2e8f0; }
        tr:nth-child(even) { background: #f8fafc; }
        
        /* Status Badges */
        .status { padding: 4px 8px; border-radius: 12px; font-size: 9px; font-weight: bold; text-transform: uppercase; display: inline-block; min-width: 60px; text-align: center; }
        .pending { background: #fff7ed; color: #c2410c; }
        .accepted { background: #eff6ff; color: #1d4ed8; }
        .completed { background: #f0fdf4; color: #15803d; }
        .cancelled { background: #fef2f2; color: #b91c1c; }
        
        /* Footer / Signature */
        .footer { display: flex; justify-content: space-between; margin-top: 50px; page-break-inside: avoid; }
        .signature-box { text-align: center; width: 200px; }
        .signature-line { margin-top: 60px; border-top: 1px solid #333; padding-top: 10px; font-weight: bold; font-size: 13px; }
        
        .no-print { position: fixed; top: 20px; right: 20px; background: #dc2626; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: 0.2s; z-index: 1000; }
        .no-print:hover { background: #b91c1c; }
    </style>
</head>
<body>

    <a href="javascript:window.close()" class="no-print">Tutup Laporan</a>

    <div id="reportContent">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <h1>MasTolongMas</h1>
                <p>Jasa Titip & Antar Terpercaya Mojokerto</p>
                <p>Jl. Majapahit No. 123, Mojokerto, Jawa Timur</p>
                <p>Email: admin@mastolongmas.com | WA: +62 895-1376-8868</p>
            </div>
            <div class="report-meta">
                <h2>LAPORAN TRANSAKSI</h2>
                <p>Periode: <?php echo date('d M Y', strtotime($startDate)); ?> - <?php echo date('d M Y', strtotime($endDate)); ?></p>
                <p>Dicetak: <?php echo date('d F Y, H:i'); ?></p>
                <p>Oleh: Administrator</p>
            </div>
        </div>

        <!-- Management Summary -->
        <div class="summary-box">
            <div class="metric">
                <div class="metric-label">Total Pendapatan</div>
                <div class="metric-value money">Rp <?php echo number_format($totalRevenue, 0, ',', '.'); ?></div>
            </div>
            <div class="metric">
                <div class="metric-label">Total Transaksi</div>
                <div class="metric-value"><?php echo $totalOrders; ?> Order</div>
            </div>
            <div class="metric">
                <div class="metric-label">Rata-rata Order</div>
                <div class="metric-value">
                    Rp <?php echo $totalOrders > 0 ? number_format($totalRevenue / $totalOrders, 0, ',', '.') : 0; ?>
                </div>
            </div>
            <div class="metric">
                <div class="metric-label">Layanan Terlaris</div>
                <div class="metric-value"><?php echo $topService; ?></div>
            </div>
        </div>

        <!-- Table -->
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="15%">Waktu</th>
                    <th width="20%">Pelanggan</th>
                    <th width="15%">Layanan</th>
                    <th width="10%">Jarak</th>
                    <th width="15%">Nominal</th>
                    <th width="10%">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1; 
                if (count($rows) > 0): 
                    foreach($rows as $row): 
                ?>
                <tr>
                    <td style="text-align: center; color: #666;"><?php echo $no++; ?></td>
                    <td>
                        <strong>#<?php echo $row['id']; ?></strong><br>
                        <span style="color: #666; font-size: 11px;"><?php echo date('d/m/y H:i', strtotime($row['created_at'])); ?></span>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($row['full_name']); ?><br>
                        <span style="color: #666; font-size: 11px;"><?php echo $row['wa_number']; ?></span>
                    </td>
                    <td><?php echo htmlspecialchars($row['service_type']); ?></td>
                    <td><?php echo $row['distance_km']; ?> km</td>
                    <td style="font-weight: bold;">Rp <?php echo number_format($row['price'], 0, ',', '.'); ?></td>
                    <td><span class="status <?php echo $row['status']; ?>"><?php echo strtoupper($row['status']); ?></span></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="7" style="text-align: center; padding: 30px; color: #888;">Tidak ada data transaksi untuk periode ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Signature -->
        <div class="footer">
            <div style="font-size: 11px; color: #888; width: 40%;">
                <p><strong>Catatan:</strong></p>
                <p>- Laporan ini dibuat secara otomatis oleh sistem.</p>
                <p>- Data pendapatan yang tertera adalah nilai transaksi final.</p>
            </div>
            <div class="signature-box">
                <p style="margin-bottom: 50px;">Mojokerto, <?php echo date('d F Y'); ?></p>
                <div class="signature-line">Administrator</div>
            </div>
        </div>
    </div>

<script>
    window.onload = function() {
        const element = document.getElementById('reportContent');
        const opt = {
            margin:       10,
            filename:     'Laporan_MasTolongMas_<?php echo date('Ymd_His'); ?>.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2 },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        // Auto convert to PDF logic or just Print
        // window.print(); // Often better for CSS based prints
    };
</script>

</body>
</html>

