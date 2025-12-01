<?php
session_start();
require_once '../koneksi.php';
$db = $koneksi;

// --- 1. FILTER PERIODE ---
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

$nama_bulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

// --- 2. QUERY DATA ---
$sql = "SELECT * FROM tbl_pengajuan 
        WHERE status = 'disetujui' 
        AND MONTH(tanggal_peminjaman) = $bulan 
        AND YEAR(tanggal_peminjaman) = $tahun 
        ORDER BY tanggal_peminjaman DESC";

$result = mysqli_query($db, $sql);
$laporan = [];

// --- 3. LOGIKA PENENTUAN HARGA FINAL ---
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        
        // Variabel Default
        $harga_master_internal = 0;
        $harga_master_eksternal = 0;
        $harga_final_satuan = 0;
        $label_tarif_final = "-";
        
        // Tentukan Sumber Data
        $tabel_sumber = '';
        $kategori = $row['kategori'];
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

        // Ambil Harga Master
        if (!empty($tabel_sumber)) {
            $nama_aset = mysqli_real_escape_string($db, $row['subpilihan']);
            
            $sql_harga = "";
            if ($kategori === 'Laboratorium') {
                $sql_harga = "SELECT tarif_sewa_laboratorium AS h1, tarif_sewa_peralatan AS h2 FROM $tabel_sumber WHERE nama = '$nama_aset'";
            } elseif ($kategori === 'Usaha') {
                $sql_harga = "SELECT tarif_eksternal AS h1, tarif_eksternal AS h2 FROM $tabel_sumber WHERE nama = '$nama_aset'";
            } else {
                $sql_harga = "SELECT tarif_internal AS h1, tarif_eksternal AS h2 FROM $tabel_sumber WHERE nama = '$nama_aset'";
            }

            $q_harga = mysqli_query($db, $sql_harga);
            if ($q_harga && $d_harga = mysqli_fetch_assoc($q_harga)) {
                $harga_master_internal = $d_harga['h1'];
                $harga_master_eksternal = $d_harga['h2'];
            }
        }

        // --- LOGIKA PEMILIHAN HARGA (YANG DIAMBIL ORANGNYA) ---
        $status_peminjam = strtoupper($row['status_peminjam']);
        $pilihan_tarif_kode = $row['tarif_sewa']; // 1 (Internal) atau 2 (Eksternal)

        // 1. Cek Mahasiswa (Selalu 0)
        if (strpos($status_peminjam, 'MAHASISWA') !== false) {
            $harga_final_satuan = 0;
            $label_tarif_final = "Gratis (Mahasiswa)";
        } 
        // 2. Cek Umum
        else {
            if ($pilihan_tarif_kode == 1) {
                // Orang pilih INTERNAL
                $harga_final_satuan = $harga_master_internal;
                $label_tarif_final = ($kategori === 'Laboratorium') ? "Sewa Lab" : "Tarif Internal";
            } elseif ($pilihan_tarif_kode == 2) {
                // Orang pilih EKSTERNAL
                $harga_final_satuan = $harga_master_eksternal;
                $label_tarif_final = ($kategori === 'Laboratorium') ? "Sewa Alat" : "Tarif Eksternal";
            } else {
                // Fallback (Default ke Eksternal jika data error)
                $harga_final_satuan = $harga_master_eksternal;
                $label_tarif_final = "Tarif Eksternal (Default)";
            }
        }

        // Hitung Total
        $durasi = (new DateTime($row['tanggal_selesai']))->diff(new DateTime($row['tanggal_peminjaman']))->days + 1;
        $row['durasi'] = $durasi;
        $row['harga_satuan_fix'] = $harga_final_satuan;
        $row['label_tarif_fix'] = $label_tarif_final;
        $row['total_estimasi'] = $harga_final_satuan * $durasi;

        $laporan[] = $row;
    }
}

// --- 4. EXPORT EXCEL (DIPERBAIKI TAMPILANNYA) ---
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    header("Content-type: application/vnd-ms-excel");
    header("Content-Disposition: attachment; filename=Laporan_Penggunaan_$bulan-$tahun.xls");
    
    // Style inline untuk Excel agar rapi
    $style_header = "background-color:#1e1b4b; color:#ffffff; font-weight:bold; text-align:center; vertical-align:middle; border:1px solid #000000;";
    $style_row_ganjil = "background-color:#ffffff; vertical-align:middle; border:1px solid #000000;";
    $style_row_genap = "background-color:#f3f4f6; vertical-align:middle; border:1px solid #000000;";
    $style_judul = "font-size:16px; font-weight:bold; text-align:center; background-color:#e0e7ff;";
    ?>
    
    <table border="1" style="border-collapse: collapse; width: 100%;">
        <tr>
            <td colspan="7" style="<?= $style_judul ?> height: 35px;">LAPORAN PENGGUNAAN RUANGAN & ASET</td>
        </tr>
        <tr>
            <td colspan="7" style="text-align:center; background-color:#e0e7ff; font-weight:bold;">
                Periode: <?= strtoupper($nama_bulan[$bulan]) ?> <?= $tahun ?>
            </td>
        </tr>
        <tr><td colspan="7"></td></tr> <thead>
            <tr style="height: 30px;">
                <th style="<?= $style_header ?> width: 50px;">No</th>
                <th style="<?= $style_header ?> width: 250px;">Peminjam</th>
                <th style="<?= $style_header ?> width: 200px;">Aset / Ruangan</th>
                <th style="<?= $style_header ?> width: 150px;">Jenis Tarif</th>
                <th style="<?= $style_header ?> width: 150px;">Harga Satuan</th>
                <th style="<?= $style_header ?> width: 100px;">Durasi</th>
                <th style="<?= $style_header ?> width: 180px;">Total Biaya</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no=1; 
            foreach($laporan as $d): 
                $style_row = ($no % 2 == 0) ? $style_row_genap : $style_row_ganjil;
            ?>
            <tr style="height: 25px;">
                <td style="<?= $style_row ?> text-align:center;"><?= $no++ ?></td>
                <td style="<?= $style_row ?>">
                    <strong><?= $d['nama_peminjam'] ?></strong><br>
                    <span style="color:#666;">(<?= $d['status_peminjam'] ?>)</span>
                </td>
                <td style="<?= $style_row ?>"><?= $d['subpilihan'] ?></td>
                <td style="<?= $style_row ?> text-align:center;"><?= $d['label_tarif_fix'] ?></td>
                <td style="<?= $style_row ?> text-align:right;">Rp <?= number_format($d['harga_satuan_fix'], 0, ',', '.') ?></td>
                <td style="<?= $style_row ?> text-align:center;"><?= $d['durasi'] ?> Hari</td>
                <td style="<?= $style_row ?> text-align:right; font-weight:bold;">Rp <?= number_format($d['total_estimasi'], 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
            
            <?php if(empty($laporan)): ?>
            <tr>
                <td colspan="7" style="text-align:center; padding: 20px;">Data tidak ditemukan untuk periode ini.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Penggunaan Ruangan</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    body { font-family: 'Poppins', sans-serif; }
    .main { transition: margin-left 0.3s; }
    #sidebar { transition: transform 0.3s; }
    @media (min-width: 1024px) { .main { margin-left: 16rem; } }
    @media (max-width: 1023px) { .main { margin-left: 0; } }
    .card-soft { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0; }
</style>
</head>
<body class="bg-blue-100 text-slate-800 min-h-screen">

<div id="sidebarOverlay" class="fixed inset-0 bg-slate-900/50 z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>

<div id="sidebar" class="fixed top-0 left-0 h-full w-64 bg-[#1a1c23] text-white z-50 transform -translate-x-full lg:translate-x-0 shadow-2xl flex flex-col transition-transform duration-300">
    
    <div class="h-20 flex items-center justify-center border-b border-gray-700/50 bg-[#15171e]">
        <div class="flex items-center gap-3 font-bold text-xl tracking-wider">
            <span class="text-gray-100">SUPER ADMIN</span>
        </div>
    </div>

    <nav class="flex-1 mt-6 px-4 space-y-3 overflow-y-auto custom-scroll">
        
        <a href="dashboardsuper.php" class="flex items-center px-4 py-3 rounded-xl text-gray-400 hover:bg-white/5 hover:text-white transition-all duration-200 group">
            <div class="w-6 flex justify-center">
                <i class="fas fa-home text-lg"></i>
            </div>
            <span class="ml-3 font-medium">Dashboard</span>
        </a>

        <div class="text-[11px] font-bold text-gray-500 uppercase mt-6 mb-2 px-2 tracking-widest">Master Data</div>

        <a href="manajemen_user.php" class="flex items-center px-4 py-3 rounded-xl text-gray-400 hover:bg-white/5 hover:text-white transition-all duration-200 group">
            <div class="w-6 flex justify-center">
                <i class="fas fa-users-cog text-lg group-hover:text-indigo-400 transition-colors"></i>
            </div>
            <span class="ml-3 font-medium">Kelola User</span>
        </a>

        <a href="data_aset_super.php" class="flex items-center px-4 py-3 rounded-xl text-gray-400 hover:bg-white/5 hover:text-white transition-all duration-200 group">
            <div class="w-6 flex justify-center">
                <i class="fas fa-building text-lg group-hover:text-blue-400 transition-colors"></i>
            </div>
            <span class="ml-3 font-medium">Data Aset</span>
        </a>

        <div class="text-[11px] font-bold text-gray-500 uppercase mt-6 mb-2 px-2 tracking-widest">Laporan & Sistem</div>

        <a href="laporan_pusat.php"  class="flex items-center px-4 py-3.5 rounded-xl bg-[#6366f1] text-white shadow-lg shadow-indigo-500/30 transition-all hover:scale-[1.02]">
            <div class="w-6 flex justify-center">
                <i class="fas fa-file-contract text-lg group-hover:text-emerald-400 transition-colors"></i>
            </div>
            <span class="ml-3 font-medium">Laporan Penggunaan</span>
        </a>

        <a href="pengaturan_sistem.php" class="flex items-center px-4 py-3 rounded-xl text-gray-400 hover:bg-white/5 hover:text-white transition-all duration-200 group">
            <div class="w-6 flex justify-center">
                <i class="fas fa-cogs text-lg group-hover:text-amber-400 transition-colors"></i>
            </div>
            <span class="ml-3 font-medium">Pengaturan</span>
        </a>

    </nav>

    <div class="p-4 border-t border-gray-700/50 bg-[#15171e]">
        <a href="../halamanutama.php" onclick="return confirm('Yakin ingin keluar?')" class="flex items-center justify-center gap-3 px-4 py-3 rounded-xl text-red-400 hover:bg-red-500/10 hover:text-red-300 transition-all duration-200 w-full">
            <i class="fas fa-sign-out-alt text-lg"></i>
            <span class="font-bold">Keluar</span>
        </a>
    </div>
</div>

<div id="mainContent" class="main min-h-screen p-6 lg:p-10">
    
    <div class="lg:hidden mb-6">
        <button onclick="toggleSidebar()" class="bg-white p-2 rounded-lg shadow text-slate-600"><i class="fas fa-bars text-xl"></i></button>
    </div>

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Laporan Penggunaan Ruangan</h1>
            <p class="text-sm text-slate-500">Data tarif sesuai pilihan peminjam.</p>
        </div>
        
        <form method="GET" class="flex flex-wrap items-center gap-3 bg-white p-2 rounded-xl shadow-sm border border-slate-200">
            <select name="bulan" class="px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-indigo-500 outline-none">
                <?php foreach($nama_bulan as $k => $v): ?>
                    <option value="<?= $k ?>" <?= $k == $bulan ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
            <select name="tahun" class="px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-indigo-500 outline-none">
                <?php for($y = date('Y') + 5; $y >= 2023; $y--): ?>
                    <option value="<?= $y ?>" <?= $y == $tahun ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium">Tampilkan</button>
            <a href="?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&export=excel" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center"><i class="fas fa-file-excel mr-2"></i> Export</a>
        </form>
    </div>

    <div class="card-soft p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-xs">
                    <tr>
                        <th class="px-6 py-4 text-center w-10">No</th>
                        <th class="px-6 py-4">Peminjam</th>
                        <th class="px-6 py-4">Aset / Ruangan</th>
                        <th class="px-6 py-4">Tarif Dikenakan</th> <th class="px-6 py-4 text-center">Durasi</th>
                        <th class="px-6 py-4">Total Biaya</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if(empty($laporan)): ?>
                        <tr><td colspan="6" class="px-6 py-12 text-center text-slate-400 italic">Tidak ada data.</td></tr>
                    <?php else: ?>
                        <?php $no=1; foreach($laporan as $d): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 text-center font-medium"><?= $no++ ?></td>
                            
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-800"><?= htmlspecialchars($d['nama_peminjam']) ?></div>
                                <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-500 uppercase border border-slate-200">
                                    <?= htmlspecialchars($d['status_peminjam']) ?>
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                <div class="font-medium text-indigo-600"><?= htmlspecialchars($d['subpilihan']) ?></div>
                                <div class="text-xs text-slate-400 mt-1"><?= htmlspecialchars($d['kategori']) ?></div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-slate-700">
                                    Rp <?= number_format($d['harga_satuan_fix'], 0, ',', '.') ?>
                                </div>
                                <div class="text-xs text-emerald-600 mt-0.5 font-semibold">
                                    ket: <?= htmlspecialchars($d['label_tarif_fix']) ?>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-center">
                                <span class="bg-slate-100 text-slate-600 px-2 py-1 rounded text-xs font-bold border border-slate-200">
                                    <?= $d['durasi'] ?> Hari
                                </span>
                                <div class="text-[10px] text-slate-400 mt-1">
                                    <?= date('d/m', strtotime($d['tanggal_peminjaman'])) ?> - <?= date('d/m', strtotime($d['tanggal_selesai'])) ?>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="font-bold text-lg text-indigo-700">
                                    Rp <?= number_format($d['total_estimasi'], 0, ',', '.') ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (sidebar.classList.contains('-translate-x-full')) {
        sidebar.classList.remove('-translate-x-full'); overlay.classList.remove('hidden');
    } else {
        sidebar.classList.add('-translate-x-full'); overlay.classList.add('hidden');
    }
}
</script>
</body>
</html>