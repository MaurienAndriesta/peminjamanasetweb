<?php
session_start();
require_once '../koneksi.php';
$db = $koneksi;

// --- 1. LOGIKA TAB, PENCARIAN & FILTER FAKULTAS ---
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'ruang'; 
$search = isset($_GET['q']) ? mysqli_real_escape_string($db, $_GET['q']) : '';
// Filter Fakultas (Default: all)
$filter_fakultas = isset($_GET['filter_fakultas']) ? $_GET['filter_fakultas'] : 'all';

$data_aset = [];
$judul_tab = "";

// --- 2. QUERY BERDASARKAN TAB ---
if ($tab == 'ruang') {
    $judul_tab = "Ruang Multiguna";
    $sql = "SELECT *, 'Ruangan' as kategori FROM tbl_ruangmultiguna WHERE 1=1";
    if($search) $sql .= " AND (nama LIKE '%$search%' OR lokasi LIKE '%$search%')";
    $sql .= " ORDER BY id DESC";
    
} elseif ($tab == 'fasilitas') {
    $judul_tab = "Fasilitas Kampus";
    $sql = "SELECT *, 'Fasilitas' as kategori FROM tbl_fasilitas WHERE 1=1";
    if($search) $sql .= " AND (nama LIKE '%$search%')";
    $sql .= " ORDER BY id DESC";

} elseif ($tab == 'usaha') {
    $judul_tab = "Unit Usaha";
    $sql = "SELECT *, 'Usaha' as kategori, 0 as tarif_internal FROM tbl_usaha WHERE 1=1";
    if($search) $sql .= " AND (nama LIKE '%$search%')";
    $sql .= " ORDER BY id DESC";

} elseif ($tab == 'lab') {
    // Judul Tab dinamis berdasarkan filter
    if ($filter_fakultas == 'all') {
        $judul_tab = "Laboratorium (Semua Fakultas)";
    } else {
        $judul_tab = "Laboratorium (" . $filter_fakultas . ")";
    }

    // Siapkan sub-query untuk masing-masing tabel
    $queries = [];

    // PERBAIKAN: Mengganti "'Tersedia' as status" menjadi "status" (mengambil dari database)

    // Query FTBE
    if ($filter_fakultas == 'all' || $filter_fakultas == 'FTBE') {
        $queries[] = "SELECT id, nama, lokasi, foto as gambar, tarif_sewa_laboratorium as tarif_internal, tarif_sewa_peralatan as tarif_eksternal, 'Lab FTBE' as kategori, status FROM labftbe WHERE nama LIKE '%$search%'";
    }

    // Query FTEN
    if ($filter_fakultas == 'all' || $filter_fakultas == 'FTEN') {
        $queries[] = "SELECT id, nama, lokasi, foto as gambar, tarif_sewa_laboratorium as tarif_internal, tarif_sewa_peralatan as tarif_eksternal, 'Lab FTEN' as kategori, status FROM labften WHERE nama LIKE '%$search%'";
    }

    // Query FTIK
    if ($filter_fakultas == 'all' || $filter_fakultas == 'FTIK') {
        $queries[] = "SELECT id, nama, lokasi, foto as gambar, tarif_sewa_laboratorium as tarif_internal, tarif_sewa_peralatan as tarif_eksternal, 'Lab FTIK' as kategori, status FROM labftik WHERE nama LIKE '%$search%'";
    }

    // Query FKET
    if ($filter_fakultas == 'all' || $filter_fakultas == 'FKET') {
        $queries[] = "SELECT id, nama, lokasi, foto as gambar, tarif_sewa_laboratorium as tarif_internal, tarif_sewa_peralatan as tarif_eksternal, 'Lab FKET' as kategori, status FROM labfket WHERE nama LIKE '%$search%'";
    }

    // Gabungkan query yang terpilih dengan UNION ALL
    if (!empty($queries)) {
        $sql = implode(" UNION ALL ", $queries);
    } else {
        $sql = "SELECT * FROM labftbe WHERE 0"; 
    }
}

// Eksekusi Query
$query = mysqli_query($db, $sql);
if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $data_aset[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Aset - Super Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    body { font-family: 'Poppins', sans-serif; }
    
    /* Layout & Sidebar */
    .main { transition: margin-left 0.3s; }
    #sidebar { transition: transform 0.3s; }
    @media (min-width: 1024px) { .main { margin-left: 16rem; } }
    @media (max-width: 1023px) { .main { margin-left: 0; } }

    /* Tab Active State */
    .tab-active {
        background-color: #4f46e5; /* indigo-600 */
        color: white;
        box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);
    }
    .tab-inactive {
        background-color: white;
        color: #64748b; /* slate-500 */
        border: 1px solid #e2e8f0;
    }
    .tab-inactive:hover {
        background-color: #f8fafc;
        color: #475569;
    }

    /* Card Soft */
    .card-soft {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }
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
        
        <a href="dashboardsuper.php"class="flex items-center px-4 py-3 rounded-xl text-gray-400 hover:bg-white/5 hover:text-white transition-all duration-200 group">
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

         <a href="data_aset_super.php" class="flex items-center px-4 py-3.5 rounded-xl bg-[#6366f1] text-white shadow-lg shadow-indigo-500/30 transition-all hover:scale-[1.02]">
            <div class="w-6 flex justify-center">
                <i class="fas fa-building text-lg group-hover:text-blue-400 transition-colors"></i>
            </div>
            <span class="ml-3 font-medium">Data Aset</span>
        </a>

        <div class="text-[11px] font-bold text-gray-500 uppercase mt-6 mb-2 px-2 tracking-widest">Laporan & Sistem</div>

        <a href="laporan_pusat.php" class="flex items-center px-4 py-3 rounded-xl text-gray-400 hover:bg-white/5 hover:text-white transition-all duration-200 group">
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
        <button onclick="toggleSidebar()" class="bg-white p-2 rounded-lg shadow text-slate-600">
            <i class="fas fa-bars text-xl"></i>
        </button>
    </div>

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Data Seluruh Aset</h1>
            <p class="text-sm text-slate-500">Monitoring ketersediaan ruangan dan fasilitas kampus.</p>
        </div>
        
        <!-- FORM PENCARIAN DAN FILTER -->
        <form method="GET" class="w-full md:w-auto flex flex-col md:flex-row gap-2">
            <input type="hidden" name="tab" value="<?= $tab ?>">
            
            <!-- Filter Fakultasi (Hanya Muncul Jika Tab = Lab) -->
            <?php if ($tab == 'lab'): ?>
                <select name="filter_fakultas" onchange="this.form.submit()" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none shadow-sm text-sm text-slate-600">
                    <option value="all" <?= $filter_fakultas == 'all' ? 'selected' : '' ?>>Semua Fakultas</option>
                    <option value="FTBE" <?= $filter_fakultas == 'FTBE' ? 'selected' : '' ?>>FTBE</option>
                    <option value="FTEN" <?= $filter_fakultas == 'FTEN' ? 'selected' : '' ?>>FTEN</option>
                    <option value="FTIK" <?= $filter_fakultas == 'FTIK' ? 'selected' : '' ?>>FTIK</option>
                    <option value="FKET" <?= $filter_fakultas == 'FKET' ? 'selected' : '' ?>>FKET</option>
                </select>
            <?php endif; ?>

            <div class="relative w-full md:w-80">
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari aset..." 
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none shadow-sm">
                <span class="absolute left-3 top-2.5 text-slate-400"><i class="fas fa-search"></i></span>
            </div>
        </form>
    </div>

    <div class="flex overflow-x-auto pb-2 mb-6 gap-3 no-scrollbar">
        <a href="?tab=ruang" class="px-5 py-2.5 rounded-full font-medium text-sm whitespace-nowrap transition-all <?= $tab == 'ruang' ? 'tab-active' : 'tab-inactive' ?>">
            <i class="fas fa-building mr-2"></i> Ruang Multiguna
        </a>
        <a href="?tab=fasilitas" class="px-5 py-2.5 rounded-full font-medium text-sm whitespace-nowrap transition-all <?= $tab == 'fasilitas' ? 'tab-active' : 'tab-inactive' ?>">
            <i class="fas fa-couch mr-2"></i> Fasilitas
        </a>
        <a href="?tab=usaha" class="px-5 py-2.5 rounded-full font-medium text-sm whitespace-nowrap transition-all <?= $tab == 'usaha' ? 'tab-active' : 'tab-inactive' ?>">
            <i class="fas fa-store mr-2"></i> Unit Usaha
        </a>
        <a href="?tab=lab" class="px-5 py-2.5 rounded-full font-medium text-sm whitespace-nowrap transition-all <?= $tab == 'lab' ? 'tab-active' : 'tab-inactive' ?>">
            <i class="fas fa-microscope mr-2"></i> Laboratorium
        </a>
    </div>

    <div class="card-soft">
        <div class="border-b border-slate-100 p-5 flex justify-between items-center">
            <h2 class="font-bold text-slate-700"><?= $judul_tab ?></h2>
            <span class="text-xs font-bold bg-slate-100 text-slate-600 px-3 py-1 rounded-full">Total: <?= count($data_aset) ?></span>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-xs">
                    <tr>
                        <th class="px-6 py-4 text-center w-16">No</th>
                        <th class="px-6 py-4">Nama Aset</th>
                        <th class="px-6 py-4">Kategori / Lokasi</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-center">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if(empty($data_aset)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400 italic">
                                <i class="fas fa-box-open text-3xl mb-2"></i><br>
                                Tidak ada data aset ditemukan.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no=1; foreach($data_aset as $d): 
                            // 1. Normalisasi Status
                            $status_raw = isset($d['status']) ? strtolower(str_replace(['_', ' '], '', $d['status'])) : 'tersedia';
                            $is_available = ($status_raw !== 'tidaktersedia');
                            
                            // 2. Siapkan Data Harga untuk Modal (TIDAK DITAMPILKAN DI TABEL)
                            $is_lab = (strpos($d['kategori'], 'Lab') !== false);
                            
                            $label_1 = $is_lab ? "Sewa Lab" : "Internal";
                            $label_2 = $is_lab ? "Sewa Alat" : "Eksternal";
                            
                            $harga_1 = isset($d['tarif_internal']) ? $d['tarif_internal'] : 0;
                            $harga_2 = isset($d['tarif_eksternal']) ? $d['tarif_eksternal'] : 0;
                        ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 text-center font-medium"><?= $no++ ?></td>
                            
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <?php if(!empty($d['gambar'])): ?>
                                        <img src="../admin/assets/images/<?= $d['gambar'] ?>" class="w-10 h-10 rounded-lg object-cover border border-slate-200">
                                    <?php else: ?>
                                        <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                    <span class="font-bold text-slate-700"><?= htmlspecialchars($d['nama']) ?></span>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <span class="block text-xs font-bold text-indigo-500 uppercase"><?= $d['kategori'] ?></span>
                                <span class="text-xs text-slate-400"><?= isset($d['lokasi']) ? htmlspecialchars($d['lokasi']) : '-' ?></span>
                            </td>

                            <td class="px-6 py-4 text-center">
                                <?php if($is_available): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                        <span class="w-2 h-2 mr-1.5 bg-emerald-500 rounded-full"></span> Tersedia
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <span class="w-2 h-2 mr-1.5 bg-red-500 rounded-full"></span> Tidak Tersedia
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="px-6 py-4 text-center">
                                <button onclick="showDetail(
                                    '<?= htmlspecialchars($d['nama']) ?>', 
                                    '<?= htmlspecialchars($d['kategori']) ?>',
                                    '<?= $label_1 ?>', '<?= number_format($harga_1,0,',','.') ?>',
                                    '<?= $label_2 ?>', '<?= number_format($harga_2,0,',','.') ?>'
                                )" 
                                class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-2 rounded-lg transition-colors" title="Lihat Detail & Tarif">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<div id="detailModal" class="fixed inset-0 bg-slate-900/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 transform transition-all scale-95 opacity-0" id="modalContent">
        <div class="text-center">
            <div class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-tags text-3xl"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-800 mb-1" id="modalNama">Nama Aset</h3>
            <span class="text-xs font-bold uppercase tracking-wide text-indigo-500 bg-indigo-50 px-2 py-1 rounded" id="modalKategori">Kategori</span>
            
            <div class="mt-6 border-t border-slate-100 pt-4 space-y-3 text-left">
                <div class="flex justify-between items-center p-3 bg-slate-50 rounded-lg">
                    <span class="text-sm font-medium text-slate-500" id="label1">Label 1</span>
                    <span class="font-bold text-slate-800 text-lg">Rp <span id="modalHarga1">0</span></span>
                </div>
                <div class="flex justify-between items-center p-3 bg-slate-50 rounded-lg">
                    <span class="text-sm font-medium text-slate-500" id="label2">Label 2</span>
                    <span class="font-bold text-slate-800 text-lg">Rp <span id="modalHarga2">0</span></span>
                </div>
            </div>
        </div>
        <div class="mt-6">
            <button onclick="closeModal()" class="w-full bg-slate-800 text-white py-2.5 rounded-xl font-medium hover:bg-slate-700 transition-colors">Tutup</button>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (sidebar.classList.contains('-translate-x-full')) {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
    } else {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    }
}

function showDetail(nama, kategori, lbl1, hrg1, lbl2, hrg2) {
    document.getElementById('modalNama').innerText = nama;
    document.getElementById('modalKategori').innerText = kategori;
    
    document.getElementById('label1').innerText = lbl1;
    document.getElementById('modalHarga1').innerText = hrg1;
    
    document.getElementById('label2').innerText = lbl2;
    document.getElementById('modalHarga2').innerText = hrg2;
    
    const modal = document.getElementById('detailModal');
    const content = document.getElementById('modalContent');
    
    modal.classList.remove('hidden');
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function closeModal() {
    const modal = document.getElementById('detailModal');
    const content = document.getElementById('modalContent');
    
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}
</script>
</body>
</html>