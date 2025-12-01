<?php
session_start();
require_once '../koneksi.php'; 
$db = $koneksi;

// Cek Session (Aktifkan jika sudah siap)
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') { ... }

// --- FUNGSI HITUNG DATA ---
function hitungData($db, $tabel) {
    // Cek dulu apakah tabel ada untuk mencegah error
    $check = mysqli_query($db, "SHOW TABLES LIKE '$tabel'");
    if ($check && mysqli_num_rows($check) > 0) {
        $q = mysqli_query($db, "SELECT COUNT(*) as total FROM $tabel");
        return ($q) ? mysqli_fetch_assoc($q)['total'] : 0;
    }
    return 0;
}

// Hitung Data Real
$totalRuangan = hitungData($db, 'tbl_ruangmultiguna');
$totalFasil   = hitungData($db, 'tbl_fasilitas');
$totalUsaha   = hitungData($db, 'tbl_usaha');
$totalLab     = hitungData($db, 'labftbe') 
              + hitungData($db, 'labften') 
              + hitungData($db, 'labftik') 
              + hitungData($db, 'labfket');
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Super Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    body { font-family: 'Poppins', sans-serif; }
    
    /* Sidebar & Layout */
    .main { transition: margin-left 0.3s; }
    #sidebar { transition: transform 0.3s; }
    
    /* Responsiveness */
    @media (min-width: 1024px) {
        .main { margin-left: 16rem; } /* 64 = 16rem */
        .sidebar-open { transform: translateX(0); }
    }
    @media (max-width: 1023px) {
        .main { margin-left: 0; }
        .sidebar-closed { transform: translateX(-100%); }
    }

    /* Card Styles (Soft & Clean) */
    .card-soft {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        border: 1px solid #e2e8f0;
        transition: transform 0.2s;
    }
    .card-soft:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08);
    }

    /* Icon Box Styles */
    .icon-box {
        width: 50px; height: 50px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 12px; font-size: 1.25rem;
    }
</style>
</head>
<body class="bg-blue-100 text-slate-800 min-h-screen"> <div id="sidebarOverlay" class="fixed inset-0 bg-slate-900/50 z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>

<div id="sidebar" class="fixed top-0 left-0 h-full w-64 bg-[#1a1c23] text-white z-50 transform -translate-x-full lg:translate-x-0 shadow-2xl flex flex-col transition-transform duration-300">
    
    <div class="h-20 flex items-center justify-center border-b border-gray-700/50 bg-[#15171e]">
        <div class="flex items-center gap-3 font-bold text-xl tracking-wider">
            <span class="text-gray-100">SUPER ADMIN</span>
        </div>
    </div>

    <nav class="flex-1 mt-6 px-4 space-y-3 overflow-y-auto custom-scroll">
        
        <a href="dashboardsuper.php" class="flex items-center px-4 py-3.5 rounded-xl bg-[#6366f1] text-white shadow-lg shadow-indigo-500/30 transition-all hover:scale-[1.02]">
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

    <div class="bg-white rounded-2xl p-8 mb-8 shadow-sm border border-blue-200">
        <h1 class="text-2xl md:text-3xl font-bold text-slate-800 mb-2">Selamat Datang, Super Admin</h1>
        <p class="text-slate-500">Silakan pilih menu di sebelah kiri untuk mengelola data ruangan & laboratorium.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        
        <div class="card-soft flex items-center gap-4 border-l-4 border-blue-400">
            <div class="icon-box bg-blue-50 text-blue-600">
                <i class="far fa-building"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ruang Multiguna</p>
                <h3 class="text-2xl font-bold text-slate-800"><?= $totalRuangan ?></h3>
            </div>
        </div>

        <div class="card-soft flex items-center gap-4 border-l-4 border-orange-400">
            <div class="icon-box bg-orange-50 text-orange-500">
                <i class="fas fa-couch"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Fasilitas & Usaha</p>
                <h3 class="text-2xl font-bold text-slate-800"><?= $totalFasil + $totalUsaha ?></h3>
            </div>
        </div>

        <div class="card-soft flex items-center gap-4 border-l-4 border-emerald-400">
            <div class="icon-box bg-emerald-50 text-emerald-600">
                <i class="fas fa-microscope"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Laboratorium</p>
                <h3 class="text-2xl font-bold text-slate-800"><?= $totalLab ?></h3>
            </div>
        </div>
    </div>

    <div class="card-soft text-center py-12">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 mb-4">
            <i class="fas fa-chart-pie text-3xl text-slate-300"></i>
        </div>
        <h2 class="text-lg font-bold text-slate-700 mb-1">Detail Penggunaan Ruangan</h2>
        <p class="text-sm text-slate-400 mb-6">Laporan lengkap tersedia di menu Laporan Penggunaan.</p>
        
        <a href="laporan_pusat.php" class="inline-flex items-center px-5 py-2.5 bg-slate-800 text-white text-sm font-medium rounded-lg hover:bg-slate-700 transition-colors shadow-md">
            <i class="fas fa-file-alt mr-2"></i> Lihat Laporan
        </a>
    </div>

</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (sidebar.classList.contains('-translate-x-full')) {
        // Buka Sidebar (Mobile)
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
    } else {
        // Tutup Sidebar
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    }
}
</script>
</body>
</html>