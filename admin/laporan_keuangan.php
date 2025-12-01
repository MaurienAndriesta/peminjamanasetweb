<?php
// =======================================
// LAPORAN KEUANGAN - ADMIN PENGELOLA (REVISI LOGIKA TARIF)
// =======================================

require_once '../koneksi.php';
$db = $koneksi;

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

// PERBAIKAN QUERY: Ambil juga kolom 'tarif_sewa', 'status_peminjam', dan 'fakultas'
$sql = "SELECT 
            id, 
            subpilihan AS nama_ruang, 
            nama_peminjam AS pemohon, 
            status_peminjam,
            tarif_sewa,
            kategori AS tipe_aset, 
            fakultas,
            tanggal_peminjaman AS tanggal_mulai, 
            tanggal_selesai,
            DATEDIFF(tanggal_selesai, tanggal_peminjaman) + 1 AS durasi_hari,
            created_at
        FROM tbl_pengajuan
        WHERE status = 'disetujui'
          AND MONTH(created_at) = ?
          AND YEAR(created_at) = ?
        ORDER BY created_at DESC";

$stmt = $db->prepare($sql);
if ($stmt) {
    $stmt->bind_param("ii", $bulan, $tahun);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // 1. Tentukan Tabel Sumber
        $tabel_sumber = '';
        $kategori = $row['tipe_aset'];
        $fakultas = $row['fakultas'];

        if ($kategori == 'Ruang Multiguna') $tabel_sumber = 'tbl_ruangmultiguna';
        elseif ($kategori == 'Fasilitas') $tabel_sumber = 'tbl_fasilitas';
        elseif ($kategori == 'Usaha') $tabel_sumber = 'tbl_usaha';
        elseif ($kategori == 'Laboratorium') {
            if ($fakultas === 'FTIK') $tabel_sumber = 'labftik';
            elseif ($fakultas === 'FTEN') $tabel_sumber = 'labften';
            elseif ($fakultas === 'FKET') $tabel_sumber = 'labfket';
            elseif ($fakultas === 'FTBE') $tabel_sumber = 'labftbe';
        }
        
        // 2. Ambil Harga Master (Internal & Eksternal)
        $harga_internal_master = 0;
        $harga_eksternal_master = 0;

        if ($tabel_sumber) {
             $nama_aset = $db->real_escape_string($row['nama_ruang']);
             
             // Sesuaikan nama kolom tarif di tabel master
             $sql_harga = "";
             if ($kategori === 'Laboratorium') {
                 $sql_harga = "SELECT tarif_sewa_laboratorium AS internal, tarif_sewa_laboratorium AS eksternal FROM $tabel_sumber WHERE nama = '$nama_aset'";
             } elseif ($kategori === 'Usaha') {
                 $sql_harga = "SELECT tarif_eksternal AS internal, tarif_eksternal AS eksternal FROM $tabel_sumber WHERE nama = '$nama_aset'";
             } else {
                 $sql_harga = "SELECT tarif_internal AS internal, tarif_eksternal AS eksternal FROM $tabel_sumber WHERE nama = '$nama_aset'";
             }
             
             $q_harga = $db->query($sql_harga);
             if ($q_harga && $d_harga = $q_harga->fetch_assoc()) {
                 $harga_internal_master = $d_harga['internal'];
                 $harga_eksternal_master = $d_harga['eksternal'];
             }
        }
        
        // 3. LOGIKA HITUNG HARGA (SAMA DENGAN DETAIL PERMINTAAN)
        $harga_final = 0;
        $tarif_kode = (int)$row['tarif_sewa']; // 1=Internal, 2=Eksternal
        $status_peminjam = strtoupper($row['status_peminjam']);

        if (strpos($status_peminjam, 'MAHASISWA') !== false) {
            $harga_final = 0; // Mahasiswa Gratis
        } else {
            // Umum
            if ($tarif_kode == 1) {
                $harga_final = $harga_internal_master; // Pakai Harga Internal
            } elseif ($tarif_kode == 2) {
                $harga_final = $harga_eksternal_master; // Pakai Harga Eksternal
            } else {
                // Fallback jika data lama/kosong, default ke Eksternal
                $harga_final = $harga_eksternal_master;
            }
        }

        // 4. Hitung Total
        $row['harga_tarif'] = $harga_final;
        $row['total_biaya'] = $harga_final * $row['durasi_hari'];
        
        $transactions[] = $row;
    }
    $stmt->close();
}

// --- RINGKASAN DATA ---
$total_pendapatan = 0;
foreach ($transactions as $t) {
    $total_pendapatan += (float)$t['total_biaya'];
}
$total_transaksi = count($transactions);
$rata_rata = $total_transaksi > 0 ? $total_pendapatan / $total_transaksi : 0;

// --- GRAFIK ---
$grouped = [];
foreach ($transactions as $t) {
    $tipe = $t['tipe_aset'] ?? 'Lainnya';
    if (!isset($grouped[$tipe])) $grouped[$tipe] = 0;
    $grouped[$tipe] += (float)$t['total_biaya'];
}
arsort($grouped);

// ==========================================
// EXPORT EXCEL
// ==========================================
if (isset($_GET['export']) && $_GET['export'] == '1') {
    $filename = "Laporan_Keuangan_ITPLN_" . $nama_bulan[$bulan] . "_" . $tahun . ".xls";
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    ?>
    <html>
    <head>
        <style>
            .header { font-size: 16pt; font-weight: bold; text-align: center; }
            .sub-header { font-size: 12pt; font-weight: bold; text-align: center; }
            .table-head { background-color: #FFC000; color: black; font-weight: bold; text-align: center; border: 1px solid black; }
            .table-row { border: 1px solid black; vertical-align: middle; }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .total-row { background-color: #FFFF00; font-weight: bold; border: 1px solid black; }
        </style>
    </head>
    <body>
        <table border="1" style="width: 100%;">
            <tr><th colspan="7" class="header" height="40" style="vertical-align: middle; background-color: #132544; color: white;">PEMINJAMAN ASET ITPLN</th></tr>
            <tr><th colspan="7" class="sub-header" height="30" style="vertical-align: middle;">LAPORAN KEUANGAN PERIODE <?= strtoupper($nama_bulan[$bulan]) ?> <?= $tahun ?></th></tr>
            <tr><th colspan="7"></th></tr>
            <thead>
                <tr style="background-color: #FFC000;">
                    <th class="table-head">NO</th><th class="table-head">TANGGAL</th><th class="table-head">NAMA RUANG / ASET</th>
                    <th class="table-head">PEMOHON</th><th class="table-head">KATEGORI</th><th class="table-head">DURASI</th><th class="table-head">TOTAL BIAYA</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($transactions as $tr): ?>
                <tr>
                    <td class="table-row text-center"><?= $no++ ?></td>
                    <td class="table-row text-center"><?= date('d/m/Y', strtotime($tr['created_at'])) ?></td>
                    <td class="table-row"><?= htmlspecialchars($tr['nama_ruang']) ?></td>
                    <td class="table-row"><?= htmlspecialchars($tr['pemohon']) ?></td>
                    <td class="table-row text-center"><?= htmlspecialchars($tr['tipe_aset']) ?></td>
                    <td class="table-row text-center"><?= $tr['durasi_hari'] ?> Hari</td>
                    <td class="table-row text-right">Rp <?= number_format($tr['total_biaya'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="6" class="total-row text-right" style="background-color: #e0e0e0;">TOTAL PENDAPATAN</td>
                    <td class="total-row text-right" style="background-color: #ffff99;">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></td>
                </tr>
            </tbody>
        </table>
    </body>
    </html>
    <?php exit;
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
    body { font-family: 'Poppins', sans-serif; }
    .text-dark-accent { color: #202938; }
    .main { transition: margin-left 0.3s ease; }
    @media (min-width: 1024px) {
        .main { margin-left: 4rem; }
        .main.lg\:ml-60 { margin-left: 15rem; }
    }
    @media (max-width: 1023px) {
        .main { margin-left: 0 !important; }
    }
    #toggleBtn { position: relative; z-index: 51 !important; }
    .stat-card { transition: all 0.3s ease; }
    .stat-card:hover { transform: translateY(-5px); }
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

        <form method="GET" class="bg-gray-50 border border-gray-200 rounded-xl p-3 sm:p-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-end mb-6 sm:mb-8 shadow-sm">
            <div>
                <label class="text-xs sm:text-sm font-semibold text-gray-600 block mb-1">Bulan</label>
                <select name="bulan" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white focus:ring-amber-500 focus:border-amber-500 text-sm">
                    <?php for($m=1; $m<=12; $m++): ?>
                        <option value="<?= $m ?>" <?= $bulan==$m ? 'selected' : '' ?>><?= $nama_bulan[$m] ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="text-xs sm:text-sm font-semibold text-gray-600 block mb-1">Tahun</label>
                <select name="tahun" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white focus:ring-amber-500 focus:border-amber-500 text-sm">
                    <?php for($y=date('Y'); $y>=date('Y')-3; $y--): ?>
                        <option value="<?= $y ?>" <?= $tahun==$y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-gray-900 px-3 py-2 rounded-lg font-semibold transition-colors text-sm">
                    🔍 Tampilkan
                </button>
            </div>
            <div>
                <a href="?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&export=1" class="flex items-center justify-center w-full bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded-lg font-semibold transition-colors text-sm shadow-md">
                    📊 Export Excel
                </a>
            </div>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6 mb-6 sm:mb-8">
            <div class="stat-card relative rounded-xl p-4 sm:p-6 bg-gradient-to-br from-blue-50 to-blue-200 shadow-md border border-blue-100">
                <div class="flex items-center justify-between">
                    <div class="stat-icon text-3xl sm:text-4xl text-blue-700">💸</div>
                    <div class="stat-info text-right">
                        <h3 class="text-xs sm:text-sm font-semibold text-gray-600 mb-1">Total Pendapatan</h3>
                        <div class="text-lg sm:text-2xl font-extrabold text-blue-800 break-all"><?= rupiah($total_pendapatan) ?></div>
                    </div>
                </div>
            </div>

            <div class="stat-card relative rounded-xl p-4 sm:p-6 bg-gradient-to-br from-green-50 to-green-200 shadow-md border border-green-100">
                <div class="flex items-center justify-between">
                    <div class="stat-icon text-3xl sm:text-4xl text-green-700">📈</div>
                    <div class="stat-info text-right">
                        <h3 class="text-xs sm:text-sm font-semibold text-gray-600 mb-1">Rata-rata</h3>
                        <div class="text-lg sm:text-2xl font-extrabold text-green-800 break-all"><?= rupiah($rata_rata) ?></div>
                    </div>
                </div>
            </div>

            <div class="stat-card relative rounded-xl p-4 sm:p-6 bg-gradient-to-br from-yellow-50 to-yellow-200 shadow-md border border-yellow-100">
                <div class="flex items-center justify-between">
                    <div class="stat-icon text-3xl sm:text-4xl text-amber-700">📅</div>
                    <div class="stat-info text-right">
                        <h3 class="text-xs sm:text-sm font-semibold text-gray-600 mb-1">Transaksi</h3>
                        <div class="text-lg sm:text-3xl font-extrabold text-amber-800"><?= $total_transaksi ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <div class="table-container lg:col-span-2 bg-white p-4 sm:p-6 rounded-xl shadow-lg border border-gray-100 order-2 lg:order-1">
                <h2 class="text-base sm:text-xl font-bold mb-3 sm:mb-4 text-dark-accent flex items-center gap-2">
                    <span>📄</span>
                    <span>Detail Transaksi</span>
                </h2>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-700 text-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">No</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Aset</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider hidden md:table-cell">Pemohon</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if(!empty($transactions)): $i=1; foreach($transactions as $tr): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-700"><?= $i++ ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700"><?= date('d/m/y', strtotime($tr['created_at'])) ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-800 max-w-[150px] truncate" title="<?= htmlspecialchars($tr['nama_ruang']) ?>">
                                    <?= htmlspecialchars($tr['nama_ruang']) ?>
                                    <br>
                                    <span class="text-xs text-gray-500">
                                        <?= ($tr['tarif_sewa'] ?? 0) == 1 ? 'Internal' : (($tr['tarif_sewa'] ?? 0) == 2 ? 'Eksternal' : 'Mhs') ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-800 hidden md:table-cell"><?= htmlspecialchars($tr['pemohon']) ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-green-600"><?= rupiah($tr['total_biaya']) ?></td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-500 italic text-sm">Tidak ada transaksi</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="chart-container lg:col-span-1 bg-white p-4 sm:p-6 rounded-xl shadow-lg border border-gray-100 order-1 lg:order-2">
                <h2 class="text-base sm:text-xl font-bold mb-3 sm:mb-4 text-dark-accent flex items-center gap-2">
                    <span>🏆</span>
                    <span>Kategori</span>
                </h2>
                
                <div class="relative h-64 sm:h-80 w-full">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const main = document.getElementById('mainContent');
        const is_desktop = window.innerWidth >= 1024;

    if (is_desktop) {
        sidebar.classList.toggle('lg:w-60');
        sidebar.classList.toggle('lg:w-16');
        main.classList.toggle('lg:ml-60');
        main.classList.toggle('lg:ml-16');
    } else {
        sidebar.classList.toggle('translate-x-0');
        sidebar.classList.toggle('-translate-x-full');
    }

    const is_expanded = sidebar.classList.contains('lg:w-60') || sidebar.classList.contains('translate-x-0');
    
    if (typeof updateSidebarVisibility === 'function') {
        updateSidebarVisibility(is_expanded);
    }
    
    localStorage.setItem('sidebarStatus', is_expanded ? 'open' : 'collapsed');
}

document.addEventListener("DOMContentLoaded", function() {

    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    const overlay = document.getElementById('sidebarOverlay');
    const status = localStorage.getItem('sidebarStatus');
    const is_desktop = window.innerWidth >= 1024;
    
    if (status === 'open') {
        if(is_desktop) {
            sidebar.classList.add('lg:w-60');
            sidebar.classList.remove('lg:w-16');
            main.classList.add('lg:ml-60');
            main.classList.remove('lg:ml-16');
        }
    } else {
        // Default collapsed logic
    }

    if (overlay) overlay.addEventListener('click', toggleSidebar);

    // Chart.js
    const ctx = document.getElementById('revenueChart').getContext('2d');
    const chartLabels = <?= json_encode(array_keys($grouped)) ?>;
    const chartData = <?= json_encode(array_values($grouped)) ?>;

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: chartLabels,
            datasets: [{
                data: chartData,
                backgroundColor: ['#f59e0b','#10b981','#6366f1','#06b6d4','#f43f5e'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: { 
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { size: 11 } }
                }
            }
        }
    });
});
</script>
</body>
</html>