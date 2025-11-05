<?php
// =======================================
// LAPORAN KEUANGAN - ADMIN PENGELOLA
// =======================================
require_once 'config/database.php';
$db = new Database();

// --- FILTER PERIODE ---
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

// --- NAMA BULAN INDONESIA ---
$nama_bulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

function rupiah($n) {
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}

// --- QUERY TRANSAKSI DISETUJUI ---
$transactions = [];
try {
    $db->query("SELECT 
                id, nama_ruang, pemohon, tipe_aset, harga_tarif,
                tanggal_mulai, tanggal_selesai,
                DATEDIFF(tanggal_selesai, tanggal_mulai) + 1 AS durasi_hari,
                (harga_tarif * (DATEDIFF(tanggal_selesai, tanggal_mulai) + 1)) AS total_biaya,
                created_at
             FROM pemesanan
             WHERE status = 'disetujui'
               AND MONTH(created_at) = :bulan
               AND YEAR(created_at) = :tahun
             ORDER BY created_at DESC");
    $db->bind(':bulan', $bulan);
    $db->bind(':tahun', $tahun);
    $result = $db->resultSet();
    $transactions = is_array($result) ? $result : [];
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
}

// --- RINGKASAN DATA ---
$total_pendapatan = 0;
foreach ($transactions as $t) {
    $total_pendapatan += isset($t['total_biaya']) ? (float)$t['total_biaya'] : 0;
}
$total_transaksi = count($transactions);
$rata_rata = $total_transaksi > 0 ? $total_pendapatan / $total_transaksi : 0;

// --- GRAFIK 6 BULAN ---
$monthly_data = [];
try {
    $db->query("SELECT 
                DATE_FORMAT(created_at, '%Y-%m') AS bulan,
                SUM(harga_tarif * (DATEDIFF(tanggal_selesai, tanggal_mulai) + 1)) AS pendapatan,
                COUNT(*) AS transaksi
             FROM pemesanan
             WHERE status = 'disetujui'
               AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')
             ORDER BY bulan DESC");
    $result = $db->resultSet();
    $monthly_data = is_array($result) ? $result : [];
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
}

// --- PENDAPATAN PER TIPE ASET ---
$grouped = [];
foreach ($transactions as $t) {
    $tipe = isset($t['tipe_aset']) ? ucfirst(str_replace('_', ' ', $t['tipe_aset'])) : 'Tidak Diketahui';
    if (!isset($grouped[$tipe])) $grouped[$tipe] = 0;
    $grouped[$tipe] += isset($t['total_biaya']) ? (float)$t['total_biaya'] : 0;
}
arsort($grouped);

// --- EXPORT EXCEL ---
if (isset($_GET['export']) && $_GET['export'] == '1') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="Laporan_Keuangan_'.$nama_bulan[$bulan].'_'.$tahun.'.xls"');
    header('Cache-Control: max-age=0');
    
    echo '<table border="1">';
    echo '<tr><th colspan="7">LAPORAN KEUANGAN - '.$nama_bulan[$bulan].' '.$tahun.'</th></tr>';
    echo '<tr><th>No</th><th>Tanggal</th><th>Nama Ruang</th><th>Pemohon</th><th>Tipe Aset</th><th>Durasi</th><th>Total Biaya</th></tr>';
    
    $no = 1;
    foreach ($transactions as $tr) {
        echo '<tr>';
        echo '<td>'.$no++.'</td>';
        echo '<td>'.date('d/m/Y', strtotime($tr['created_at'])).'</td>';
        echo '<td>'.htmlspecialchars($tr['nama_ruang']).'</td>';
        echo '<td>'.htmlspecialchars($tr['pemohon']).'</td>';
        echo '<td>'.ucfirst(str_replace('_', ' ', $tr['tipe_aset'] ?? '')).'</td>';
        echo '<td>'.$tr['durasi_hari'].' hari</td>';
        echo '<td>'.number_format($tr['total_biaya'], 0, ',', '.').'</td>';
        echo '</tr>';
    }
    
    echo '<tr><td colspan="6" align="right"><strong>TOTAL</strong></td><td><strong>'.number_format($total_pendapatan, 0, ',', '.').'</strong></td></tr>';
    echo '</table>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Keuangan</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body {
  font-family: 'Poppins', sans-serif;
}
.text-dark-accent {
    color: #202938;
}

@media (min-width: 1024px) {
    .main { margin-left: 4rem; }
    .main.lg\:ml-60 { margin-left: 15rem; }
}

@media (max-width: 1023px) {
    .main { margin-left: 0 !important; }
}

#toggleBtn {
    position: relative;
    z-index: 51 !important;
}

.stat-card {
    transition: all 0.3s ease;
}
.stat-card:hover {
    transform: translateY(-5px);
}
</style>
</head>
<body class="bg-blue-100 min-h-screen">

<?php include 'sidebar_admin.php'; ?>

<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

<div id="mainContent" class="main flex-1 p-3 sm:p-5 transition-all duration-300 lg:ml-16 min-h-screen">
    
    <div class="flex justify-between items-center mb-4 sm:mb-6">
        <button id="toggleBtn" class="bg-amber-500 hover:bg-amber-600 text-gray-900 p-2 rounded-lg transition-colors shadow-md relative z-50" onclick="toggleSidebar()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
    </div>

    <div class="content bg-white p-4 sm:p-6 rounded-xl shadow-lg">
        <div class="mb-4 sm:mb-6 pb-4 border-b">
            <h1 class="text-xl sm:text-2xl font-bold text-dark-accent flex items-center gap-2">
                <span class="text-2xl">💰</span>
                <span>Laporan Keuangan</span>
            </h1>
            <p class="text-xs sm:text-sm text-gray-600 mt-1">Statistik dan analisis keuangan pemesanan</p>
        </div>

        <!-- Filter Form -->
        <form method="GET" class="bg-gray-50 border border-gray-200 rounded-xl p-3 sm:p-4 flex flex-wrap gap-3 sm:gap-4 items-end mb-6 sm:mb-8 shadow-sm">
            <div class="flex-1 min-w-[120px]">
                <label class="text-xs sm:text-sm font-semibold text-gray-600 block mb-1">Bulan</label>
                <select name="bulan" class="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg bg-white focus:ring-amber-500 focus:border-amber-500 text-sm">
                    <?php for($m=1; $m<=12; $m++): ?>
                        <option value="<?= $m ?>" <?= $bulan==$m ? 'selected' : '' ?>><?= $nama_bulan[$m] ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="flex-1 min-w-[120px]">
                <label class="text-xs sm:text-sm font-semibold text-gray-600 block mb-1">Tahun</label>
                <select name="tahun" class="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg bg-white focus:ring-amber-500 focus:border-amber-500 text-sm">
                    <?php for($y=date('Y'); $y>=date('Y')-3; $y--): ?>
                        <option value="<?= $y ?>" <?= $tahun==$y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-gray-900 px-3 sm:px-4 py-2 rounded-lg font-semibold transition-colors text-xs sm:text-sm whitespace-nowrap">
                🔍 <span class="hidden sm:inline">Tampilkan</span>
            </button>
            <a href="?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&export=1" class="bg-green-500 hover:bg-green-600 text-white px-3 sm:px-4 py-2 rounded-lg font-semibold transition-colors text-xs sm:text-sm whitespace-nowrap">
                📊 <span class="hidden sm:inline">Export</span>
            </a>
        </form>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 mb-6 sm:mb-8">
            
            <div class="stat-card relative rounded-xl p-4 sm:p-6 bg-gradient-to-br from-blue-50 to-blue-200 shadow-md">
                <div class="flex items-center justify-between">
                    <div class="stat-icon text-3xl sm:text-4xl text-blue-700">💸</div>
                    <div class="stat-info text-right">
                        <h3 class="text-xs sm:text-sm font-semibold text-gray-600 mb-1">Total Pendapatan</h3>
                        <div class="text-xl sm:text-2xl font-extrabold text-blue-800"><?= rupiah($total_pendapatan) ?></div>
                    </div>
                </div>
            </div>

            <div class="stat-card relative rounded-xl p-4 sm:p-6 bg-gradient-to-br from-green-50 to-green-200 shadow-md">
                <div class="flex items-center justify-between">
                    <div class="stat-icon text-3xl sm:text-4xl text-green-700">📈</div>
                    <div class="stat-info text-right">
                        <h3 class="text-xs sm:text-sm font-semibold text-gray-600 mb-1">Rata-rata Transaksi</h3>
                        <div class="text-xl sm:text-2xl font-extrabold text-green-800"><?= rupiah($rata_rata) ?></div>
                    </div>
                </div>
            </div>

            <div class="stat-card relative rounded-xl p-4 sm:p-6 bg-gradient-to-br from-yellow-50 to-yellow-200 shadow-md">
                <div class="flex items-center justify-between">
                    <div class="stat-icon text-3xl sm:text-4xl text-amber-700">📅</div>
                    <div class="stat-info text-right">
                        <h3 class="text-xs sm:text-sm font-semibold text-gray-600 mb-1">Total Transaksi</h3>
                        <div class="text-2xl sm:text-3xl font-extrabold text-amber-800"><?= $total_transaksi ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table & Chart -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
            
            <!-- Table - Desktop: 2 cols, Mobile: Full width on top -->
            <div class="table-container lg:col-span-2 bg-white p-4 sm:p-6 rounded-xl shadow-lg border border-gray-100 order-2 lg:order-1">
                <h2 class="text-base sm:text-xl font-bold mb-3 sm:mb-4 text-dark-accent flex items-center gap-2">
                    <span>📄</span>
                    <span>Detail Transaksi</span>
                </h2>
                
                <!-- Desktop Table -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-700 text-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">No</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Nama Ruang</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Pemohon</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Durasi</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if(!empty($transactions)): $i=1; foreach($transactions as $tr): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-700"><?= $i++ ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700"><?= date('d/m/Y', strtotime($tr['created_at'])) ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-800"><?= htmlspecialchars($tr['nama_ruang']) ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-800"><?= htmlspecialchars($tr['pemohon']) ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm"><?= $tr['durasi_hari'] ?> hari</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-green-600"><?= rupiah($tr['total_biaya']) ?></td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-gray-500 italic text-sm">Tidak ada transaksi</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="lg:hidden space-y-3">
                    <?php if(!empty($transactions)): $i=1; foreach($transactions as $tr): ?>
                    <div class="bg-gray-50 border-2 border-gray-200 rounded-xl p-3 hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex-1">
                                <div class="text-xs text-gray-500 mb-1">No. <?= $i++ ?> • <?= date('d/m/Y', strtotime($tr['created_at'])) ?></div>
                                <h3 class="font-bold text-sm text-gray-900"><?= htmlspecialchars($tr['nama_ruang']) ?></h3>
                            </div>
                            <span class="text-xs font-bold text-green-600 ml-2"><?= rupiah($tr['total_biaya']) ?></span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="bg-white p-2 rounded-lg">
                                <div class="text-gray-600 mb-1">Pemohon</div>
                                <div class="font-bold text-gray-800 truncate"><?= htmlspecialchars($tr['pemohon']) ?></div>
                            </div>
                            <div class="bg-white p-2 rounded-lg">
                                <div class="text-gray-600 mb-1">Durasi</div>
                                <div class="font-bold text-amber-600"><?= $tr['durasi_hari'] ?> hari</div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; else: ?>
                    <div class="text-center p-6 text-gray-500 italic text-sm">Tidak ada transaksi</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chart -->
            <div class="chart-container lg:col-span-1 bg-white p-4 sm:p-6 rounded-xl shadow-lg border border-gray-100 order-1 lg:order-2">
                <h2 class="text-base sm:text-xl font-bold mb-3 sm:mb-4 text-dark-accent flex items-center gap-2">
                    <span>🏆</span>
                    <span>Top Tipe Aset</span>
                </h2>
                
                <div class="relative h-64 sm:h-80">
                    <canvas id="revenueChart"></canvas>
                </div>

                <!-- Legend Mobile -->
                <div class="mt-4 lg:hidden">
                    <div class="text-xs font-semibold text-gray-600 mb-2">Keterangan:</div>
                    <div class="grid grid-cols-2 gap-2">
                        <?php 
                        $colors = ['#f59e0b','#10b981','#6366f1','#06b6d4','#f43f5e','#a855f7','#facc15','#94a3b8'];
                        $idx = 0;
                        foreach($grouped as $tipe=>$val): 
                            $color = $colors[$idx % count($colors)];
                        ?>
                        <div class="flex items-center gap-1.5 text-xs">
                            <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: <?= $color ?>"></span>
                            <span class="truncate"><?= htmlspecialchars($tipe) ?></span>
                        </div>
                        <?php $idx++; endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    const overlay = document.getElementById('sidebarOverlay');
    const is_desktop = window.innerWidth >= 1024;

    if (is_desktop) {
        sidebar.classList.toggle('lg:w-60');
        sidebar.classList.toggle('lg:w-16');
        main.classList.toggle('lg:ml-60');
        main.classList.toggle('lg:ml-16');
    } else {
        sidebar.classList.toggle('translate-x-0');
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    }

    const is_expanded = sidebar.classList.contains('lg:w-60') || sidebar.classList.contains('translate-x-0');
    
    if (typeof updateSidebarVisibility === 'function') {
        updateSidebarVisibility(is_expanded);
    }
    
    localStorage.setItem('sidebarStatus', is_expanded ? 'open' : 'collapsed');
}

document.addEventListener("DOMContentLoaded", function() {
    const sidebar = document.getElementById("sidebar");
    const main = document.getElementById("mainContent");
    const overlay = document.getElementById("sidebarOverlay");
    const status = localStorage.getItem('sidebarStatus');
    const is_desktop = window.innerWidth >= 1024;

    if (status === 'open') {
        if (is_desktop) {
            main.classList.add('lg:ml-60');
            main.classList.remove('lg:ml-16');
            sidebar.classList.add('lg:w-60');
            sidebar.classList.remove('lg:w-16');
        } else {
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
            overlay.classList.remove('hidden');
        }
    } else {
        if (is_desktop) {
            main.classList.remove('lg:ml-60');
            main.classList.add('lg:ml-16');
            sidebar.classList.remove('lg:w-60');
            sidebar.classList.add('lg:w-16');
        } else {
            sidebar.classList.remove('translate-x-0');
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }
    }

    if (overlay) {
        overlay.addEventListener('click', toggleSidebar);
    }

    // Chart.js Initialization
    const ctx = document.getElementById('revenueChart').getContext('2d');
    const chartLabels = <?= json_encode(array_keys($grouped)) ?>;
    const chartData = <?= json_encode(array_values($grouped)) ?>;

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: chartLabels,
            datasets: [{
                data: chartData,
                backgroundColor: [
                    '#f59e0b','#10b981','#6366f1','#06b6d4',
                    '#f43f5e','#a855f7','#facc15','#94a3b8'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { 
                    position: 'bottom',
                    display: window.innerWidth >= 1024,
                    labels: { 
                        padding: 10,
                        font: { size: window.innerWidth < 640 ? 10 : 12 }
                    }
                },
                tooltip: { 
                    callbacks: { 
                        label: ctx => ctx.label + ': Rp ' + ctx.parsed.toLocaleString('id-ID')
                    }
                }
            },
            animation: { animateScale: true }
        }
    });
});
</script>
</body>
</html>