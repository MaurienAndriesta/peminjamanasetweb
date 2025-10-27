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
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; }
.text-dark-accent { color: #202938; }

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

/* Bar Chart */
.monthly-chart {
    display: flex;
    align-items: flex-end;
    gap: 0.5rem;
    height: 100%;
    padding-bottom: 2rem;
}
.bar {
    flex: 1;
    border-radius: 0.3rem 0.3rem 0 0;
    position: relative;
    transition: all .3s ease;
    min-height: 20px;
}
.bar:hover {
    transform: translateY(-4px);
}
.bar-label {
    position: absolute;
    bottom: -1.8rem;
    left: 50%;
    transform: translateX(-50%);
    font-size: 0.65rem;
    white-space: nowrap;
}
.bar-value {
    position: absolute;
    top: -1.5rem;
    left: 50%;
    transform: translateX(-50%);
    font-size: 0.7rem;
    font-weight: 600;
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
    <div class="content-header border-b pb-4 mb-4 sm:mb-6">
      <h2 class="text-xl sm:text-2xl font-bold text-dark-accent">💰 Laporan Keuangan</h2>
      <p class="text-gray-500 text-xs sm:text-sm">Periode: <strong><?= $nama_bulan[$bulan] ?> <?= $tahun ?></strong></p>
    </div>

    <!-- Filter Form -->
    <form method="GET" class="filter-box bg-gray-50 border border-gray-200 rounded-xl p-3 sm:p-4 flex flex-wrap gap-3 sm:gap-4 items-end mb-6 sm:mb-8 shadow-sm">
      <div class="filter-group flex-1 min-w-[120px]">
        <label class="text-xs sm:text-sm font-semibold text-gray-600 block mb-1">Bulan</label>
        <select name="bulan" class="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg bg-white focus:ring-amber-500 focus:border-amber-500 text-sm">
          <?php for($m=1; $m<=12; $m++): ?>
            <option value="<?= $m ?>" <?= $bulan==$m ? 'selected' : '' ?>><?= $nama_bulan[$m] ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="filter-group flex-1 min-w-[120px]">
        <label class="text-xs sm:text-sm font-semibold text-gray-600 block mb-1">Tahun</label>
        <select name="tahun" class="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg bg-white focus:ring-amber-500 focus:border-amber-500 text-sm">
          <?php for($y=date('Y'); $y>=date('Y')-3; $y--): ?>
            <option value="<?= $y ?>" <?= $tahun==$y ? 'selected' : '' ?>><?= $y ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <button type="submit" class="btn-filter bg-amber-500 hover:bg-amber-600 text-gray-900 px-3 sm:px-4 py-2 rounded-lg font-semibold transition-colors text-xs sm:text-sm whitespace-nowrap">
        🔍 <span class="hidden sm:inline">Tampilkan</span>
      </button>
      <a href="?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&export=1" class="btn-export bg-green-500 hover:bg-green-600 text-white px-3 sm:px-4 py-2 rounded-lg font-semibold transition-colors text-xs sm:text-sm whitespace-nowrap">
        📊 <span class="hidden sm:inline">Export</span>
      </a>
    </form>

    <!-- Summary Cards -->
    <div class="summary-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5 mb-6 sm:mb-8">
      <div class="relative rounded-xl p-4 sm:p-6 text-white shadow-lg bg-gradient-to-br from-blue-500 to-blue-400">
        <div class="flex items-center justify-between">
          <div class="flex-1">
            <h3 class="text-xs sm:text-sm font-semibold opacity-90">Total Pendapatan</h3>
            <div class="text-lg sm:text-2xl font-extrabold mt-1"><?= rupiah($total_pendapatan) ?></div>
            <div class="text-xs mt-1 opacity-80"><?= $total_transaksi ?> transaksi</div>
          </div>
          <div class="text-2xl sm:text-3xl opacity-80">💸</div>
        </div>
      </div>
      
      <div class="relative rounded-xl p-4 sm:p-6 text-white shadow-lg bg-gradient-to-br from-green-500 to-green-400">
        <div class="flex items-center justify-between">
          <div class="flex-1">
            <h3 class="text-xs sm:text-sm font-semibold opacity-90">Rata-rata Transaksi</h3>
            <div class="text-lg sm:text-2xl font-extrabold mt-1"><?= rupiah($rata_rata) ?></div>
            <div class="text-xs mt-1 opacity-80">Per pemesanan</div>
          </div>
          <div class="text-2xl sm:text-3xl opacity-80">📈</div>
        </div>
      </div>
      
      <div class="relative rounded-xl p-4 sm:p-6 text-white shadow-lg bg-gradient-to-br from-purple-500 to-purple-400">
        <div class="flex items-center justify-between">
          <div class="flex-1">
            <h3 class="text-xs sm:text-sm font-semibold opacity-90">Periode</h3>
            <div class="text-lg sm:text-2xl font-extrabold mt-1"><?= $nama_bulan[$bulan] ?></div>
            <div class="text-xs mt-1 opacity-80"><?= $tahun ?></div>
          </div>
          <div class="text-2xl sm:text-3xl opacity-80">🗓️</div>
        </div>
      </div>
      
      <div class="relative rounded-xl p-4 sm:p-6 text-white shadow-lg bg-gradient-to-br from-pink-500 to-pink-400">
        <div class="flex items-center justify-between">
          <div class="flex-1">
            <h3 class="text-xs sm:text-sm font-semibold opacity-90">Tipe Aset Dominan</h3>
            <div class="text-sm sm:text-base font-extrabold mt-1"><?= !empty($grouped) ? array_key_first($grouped) : '-' ?></div>
            <div class="text-xs mt-1 opacity-80">Kategori tertinggi</div>
          </div>
          <div class="text-2xl sm:text-3xl opacity-80">🏢</div>
        </div>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="chart-section grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
      
      <!-- Tren Pendapatan -->
      <div class="chart-card lg:col-span-2 bg-gray-50 p-4 sm:p-6 rounded-xl shadow-md border border-gray-200 order-2 lg:order-1">
        <h3 class="text-base sm:text-xl font-bold text-dark-accent mb-4 sm:mb-6">📊 Tren Pendapatan (6 Bulan)</h3>
        <div class="h-48 sm:h-64 relative">
          <div class="monthly-chart h-full">
            <?php 
            if (!empty($monthly_data) && is_array($monthly_data)):
              $max = 0; 
              foreach($monthly_data as $d) {
                if(isset($d['pendapatan']) && $d['pendapatan'] > $max) $max = $d['pendapatan'];
              }
              
              $md = array_reverse($monthly_data);
              
              foreach($md as $d):
                $pendapatan = isset($d['pendapatan']) ? (float)$d['pendapatan'] : 0;
                $transaksi = isset($d['transaksi']) ? (int)$d['transaksi'] : 0;
                $height = $max > 0 ? ($pendapatan / $max * 100) : 10;
                $label = date('M', strtotime($d['bulan'].'-01'));
            ?>
              <div class="bar bg-amber-500 hover:bg-amber-600" 
                   style="height:<?= $height ?>%" 
                   title="<?= $label ?>: <?= rupiah($pendapatan) ?>">
                <span class="bar-value text-gray-900"><?= $transaksi ?></span>
                <span class="bar-label text-gray-600"><?= $label ?></span>
              </div>
            <?php 
              endforeach;
            else:
            ?>
              <div class="flex items-center justify-center w-full h-full text-center p-4 text-gray-500 italic text-xs sm:text-sm">
                Tidak ada data tren
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Top Pendapatan -->
      <div class="chart-card lg:col-span-1 bg-gray-50 p-4 sm:p-6 rounded-xl shadow-md border border-gray-200 order-1 lg:order-2">
        <h3 class="text-base sm:text-xl font-bold text-dark-accent mb-4 sm:mb-6">🏆 Top Tipe Aset</h3>
        <ul class="space-y-2 sm:space-y-3">
          <?php if(!empty($grouped)): $i=1; foreach($grouped as $tipe=>$val): ?>
          <li class="flex justify-between items-center bg-white p-2 sm:p-3 rounded-lg shadow-sm border border-gray-100">
            <span class="text-xs sm:text-sm text-gray-700 truncate flex-1">
              <strong><?= $i++ ?>.</strong> <?= htmlspecialchars($tipe) ?>
            </span>
            <span class="text-xs sm:text-base font-bold text-green-600 ml-2"><?= rupiah($val) ?></span>
          </li>
          <?php endforeach; else: ?>
          <li class="text-center p-4 text-gray-500 italic text-xs sm:text-sm">Belum ada data</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>

    <!-- Detail Transaksi Table -->
    <h3 class="text-base sm:text-xl font-bold text-dark-accent mt-6 sm:mt-8 mb-3 sm:mb-4">Detail Transaksi</h3>
    
    <!-- Desktop Table -->
    <div class="hidden lg:block overflow-x-auto rounded-xl shadow-md border border-gray-200">
      <?php if(!empty($transactions)): ?>
      <table class="min-w-full divide-y divide-gray-200">
        <thead>
          <tr class="bg-gray-700 text-white">
            <th class="px-4 py-3 text-left text-xs font-bold uppercase">No</th>
            <th class="px-4 py-3 text-left text-xs font-bold uppercase">Tanggal</th>
            <th class="px-4 py-3 text-left text-xs font-bold uppercase">Ruang</th>
            <th class="px-4 py-3 text-left text-xs font-bold uppercase">Pemohon</th>
            <th class="px-4 py-3 text-left text-xs font-bold uppercase">Tipe</th>
            <th class="px-4 py-3 text-left text-xs font-bold uppercase">Durasi</th>
            <th class="px-4 py-3 text-left text-xs font-bold uppercase">Total</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <?php foreach($transactions as $i=>$tr): ?>
          <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 text-sm font-medium text-gray-700"><?= $i+1 ?></td>
            <td class="px-4 py-3 text-sm text-gray-700"><?= date('d/m/Y', strtotime($tr['created_at'])) ?></td>
            <td class="px-4 py-3 text-sm"><?= htmlspecialchars($tr['nama_ruang']) ?></td>
            <td class="px-4 py-3 text-sm"><?= htmlspecialchars($tr['pemohon']) ?></td>
            <td class="px-4 py-3 text-sm"><?= ucfirst(str_replace('_', ' ', $tr['tipe_aset'] ?? '-')) ?></td>
            <td class="px-4 py-3 text-sm"><?= $tr['durasi_hari'] ?> hari</td>
            <td class="px-4 py-3 text-sm font-bold text-green-600"><?= rupiah($tr['total_biaya']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr class="bg-gray-200 font-bold text-dark-accent">
            <td colspan="6" class="px-4 py-3 text-right text-sm sm:text-base">TOTAL:</td>
            <td class="px-4 py-3 text-sm sm:text-base text-green-700"><?= rupiah($total_pendapatan) ?></td>
          </tr>
        </tfoot>
      </table>
      <?php else: ?>
      <div class="text-center p-6 text-gray-500 italic text-sm">Tidak ada transaksi</div>
      <?php endif; ?>
    </div>

    <!-- Mobile Cards -->
    <div class="lg:hidden space-y-3">
      <?php if(!empty($transactions)): foreach($transactions as $i=>$tr): ?>
      <div class="bg-gray-50 border-2 border-gray-200 rounded-xl p-3">
        <div class="flex justify-between items-start mb-2">
          <div class="flex-1">
            <div class="text-xs text-gray-500 mb-1">No. <?= $i+1 ?> • <?= date('d/m/Y', strtotime($tr['created_at'])) ?></div>
            <h4 class="font-bold text-sm text-gray-900 truncate"><?= htmlspecialchars($tr['nama_ruang']) ?></h4>
          </div>
          <span class="text-xs font-bold text-green-600 ml-2"><?= rupiah($tr['total_biaya']) ?></span>
        </div>
        <div class="text-xs text-gray-600 space-y-1">
          <div>👤 <?= htmlspecialchars($tr['pemohon']) ?></div>
          <div>📦 <?= ucfirst(str_replace('_', ' ', $tr['tipe_aset'] ?? '-')) ?></div>
          <div>📅 <?= $tr['durasi_hari'] ?> hari</div>
        </div>
      </div>
      <?php endforeach; ?>
      
      <div class="bg-gray-200 p-3 rounded-xl">
        <div class="flex justify-between items-center">
          <span class="font-bold text-sm text-gray-800">TOTAL:</span>
          <span class="font-bold text-sm text-green-700"><?= rupiah($total_pendapatan) ?></span>
        </div>
      </div>
      <?php else: ?>
      <div class="text-center p-6 text-gray-500 italic text-sm">Tidak ada transaksi</div>
      <?php endif; ?>
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
});
</script>
</body>
</html>