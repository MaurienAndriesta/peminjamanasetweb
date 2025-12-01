<?php
session_start();
require_once '../koneksi.php'; 
$db = $koneksi;

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // header("Location: ../login.php"); 
    // exit;
}

if (!isset($_SESSION['fullname'])) {
    $_SESSION['fullname'] = 'Admin Pengelola';
}
$nama_admin = $_SESSION['fullname'];

$total_ruangan = 0;
try {
    $q1 = mysqli_query($db, "SELECT COUNT(*) as total FROM tbl_ruangmultiguna");
    $total_multiguna = ($q1) ? mysqli_fetch_assoc($q1)['total'] : 0;
    $q2 = mysqli_query($db, "SELECT COUNT(*) as total FROM tbl_fasilitas");
    $total_fasilitas = ($q2) ? mysqli_fetch_assoc($q2)['total'] : 0;
    $q3 = mysqli_query($db, "SELECT COUNT(*) as total FROM tbl_usaha");
    $total_usaha = ($q3) ? mysqli_fetch_assoc($q3)['total'] : 0;
    $q_lab = mysqli_query($db, "SELECT (SELECT COUNT(*) FROM labftbe) + (SELECT COUNT(*) FROM labften) + (SELECT COUNT(*) FROM labftik) + (SELECT COUNT(*) FROM labfket) AS total_lab");
    $d_lab = ($q_lab) ? mysqli_fetch_assoc($q_lab) : ['total_lab' => 0];
    $total_ruangan = $total_multiguna + $total_fasilitas + $d_lab['total_lab'] + $total_usaha;
} catch (Exception $e) { $total_ruangan = 0; }

$pemesanan_selesai = 0; $menunggu_persetujuan = 0; $pendapatan = 0;
$booked_data = []; 

$q_selesai = mysqli_query($db, "SELECT COUNT(*) as total FROM tbl_pengajuan WHERE status = 'disetujui'");
if($q_selesai) $pemesanan_selesai = mysqli_fetch_assoc($q_selesai)['total'];

$q_pending = mysqli_query($db, "SELECT COUNT(*) as total FROM tbl_pengajuan WHERE status = 'pending' OR status = 'Menunggu'");
if($q_pending) $menunggu_persetujuan = mysqli_fetch_assoc($q_pending)['total'];

$q_income = mysqli_query($db, "SELECT * FROM tbl_pengajuan WHERE status = 'disetujui'");
if($q_income){
    while ($row = mysqli_fetch_assoc($q_income)) {
        if (date('Y-m', strtotime($row['created_at'])) == date('Y-m')) {
            $harga = 0; $tabel = ''; $kategori = $row['kategori'];
            if ($kategori == 'Ruang Multiguna') $tabel = 'tbl_ruangmultiguna';
            elseif ($kategori == 'Fasilitas') $tabel = 'tbl_fasilitas';
            elseif ($kategori == 'Usaha') $tabel = 'tbl_usaha';
            elseif ($kategori == 'Laboratorium') {
                $fak = $row['fakultas'];
                if($fak == 'FTIK') $tabel = 'labftik'; elseif($fak == 'FTEN') $tabel = 'labften'; elseif($fak == 'FKET') $tabel = 'labfket'; elseif($fak == 'FTBE') $tabel = 'labftbe';
            }
            if ($tabel) {
                $nama = mysqli_real_escape_string($db, $row['subpilihan']);
                $col_h_int = ($kategori == 'Laboratorium') ? 'tarif_sewa_laboratorium' : 'tarif_internal';
                $col_h_eks = ($kategori == 'Laboratorium') ? 'tarif_sewa_peralatan' : 'tarif_eksternal';
                $q_h = mysqli_query($db, "SELECT $col_h_int as internal, $col_h_eks as eksternal FROM $tabel WHERE nama = '$nama'");
                if ($q_h && $d_h = mysqli_fetch_assoc($q_h)) {
                    if (stripos($row['status_peminjam'], 'Mahasiswa') !== false) { $harga = 0; } 
                    else {
                        if ($row['tarif_sewa'] == 1) $harga = $d_h['internal'];
                        elseif ($row['tarif_sewa'] == 2) $harga = $d_h['eksternal'];
                        else $harga = $d_h['eksternal'];
                    }
                }
            }
            $start = new DateTime($row['tanggal_peminjaman']); $end = new DateTime($row['tanggal_selesai']);
            $diff = $end->diff($start)->days + 1;
            $pendapatan += ($harga * $diff);
        }

        $tgl_awal = $row['tanggal_peminjaman'];
        $tgl_akhir = $row['tanggal_selesai'];
        $aset = $row['subpilihan'];
        try {
            $period = new DatePeriod(new DateTime($tgl_awal), new DateInterval('P1D'), (new DateTime($tgl_akhir))->modify('+1 day'));
            foreach ($period as $date) {
                $tgl = $date->format('Y-m-d');
                if (isset($booked_data[$tgl])) { $booked_data[$tgl] .= ", " . $aset; } 
                else { $booked_data[$tgl] = $aset; }
            }
        } catch(Exception $e){}
    }
}
$json_booked_data = json_encode($booked_data);

$riwayat = []; 
$keyword = isset($_GET['q']) ? mysqli_real_escape_string($db, $_GET['q']) : '';
$sql_riwayat = "SELECT * FROM tbl_pengajuan WHERE 1=1";
if ($keyword !== '') { $sql_riwayat .= " AND (nama_peminjam LIKE '%$keyword%' OR subpilihan LIKE '%$keyword%')"; }
$sql_riwayat .= " ORDER BY created_at DESC LIMIT 5";
$q_riwayat = mysqli_query($db, $sql_riwayat);
if ($q_riwayat) { while ($r = mysqli_fetch_assoc($q_riwayat)) { $riwayat[] = $r; } }

$notifikasi = []; $jumlah_notif = 0;
$q_notif = mysqli_query($db, "SELECT * FROM tbl_pengajuan WHERE status IN ('pending', 'Menunggu') ORDER BY created_at DESC LIMIT 5");
if ($q_notif) {
    while ($rn = mysqli_fetch_assoc($q_notif)) { $notifikasi[] = $rn; }
    $q_c = mysqli_query($db, "SELECT COUNT(*) as total FROM tbl_pengajuan WHERE status IN ('pending', 'Menunggu')");
    $jumlah_notif = mysqli_fetch_assoc($q_c)['total'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin Pengelola</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    body { font-family: 'Poppins', sans-serif; background-color: #ffffff; overflow-x: hidden; }
    .text-dark-accent { color: #1e293b; }

    .main { transition: margin-left 0.3s ease-in-out; }
    @media (min-width: 1024px) { .main { margin-left: 16rem; } }
    @media (max-width: 1023px) { .main { margin-left: 0 !important; } }

    #sidebar { position: fixed; z-index: 50; top: 0; left: 0; height: 100%; box-shadow: 4px 0 24px rgba(0,0,0,0.1); }
    #sidebar.lg\:w-16 .sidebar-text { display: none; }
    #sidebar.lg\:w-60 .sidebar-text { display: inline; }
    @media (max-width: 1023px) { .sidebar-text { display: inline !important; } }

    .header-panel {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        border-radius: 1rem;
        z-index: 40;
    }
    
    .list-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        border-radius: 1rem;
        transition: all 0.3s ease;
    }
    .list-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.08);
    }

    .stat-card { 
        position: relative; overflow: hidden; border-radius: 1.5rem; padding: 1.75rem; 
        box-shadow: 0 10px 20px -5px rgba(0,0,0,0.15); 
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
        border: 1px solid rgba(255,255,255,0.2);
    }
    .stat-card:hover { transform: translateY(-6px); box-shadow: 0 20px 30px -5px rgba(0,0,0,0.25); }
    
    .stat-card::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
        background: linear-gradient(135deg, rgba(255,255,255,0.3) 0%, rgba(255,255,255,0) 60%);
        pointer-events: none;
    }
    .stat-icon-bg { position: absolute; right: -15px; bottom: -20px; font-size: 6.5rem; opacity: 0.25; transform: rotate(-15deg); }

    .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; gap: 0.4rem; }
    .cal-day-name { font-size: 0.7rem; font-weight: 800; color: #64748B; text-transform: uppercase; letter-spacing: 0.05em; }
    .cal-date { 
        height: 2.2rem; width: 2.2rem; margin: 0 auto; 
        display: flex; align-items: center; justify-content: center; 
        border-radius: 0.75rem; font-size: 0.85rem; font-weight: 600; 
        transition: all 0.2s; cursor: default;
    }
    .is-today { background: #2563EB; color: white; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3); }
    .is-booked { background: #EF4444; color: white; cursor: pointer; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3); }
    .is-free:hover { background: #F1F5F9; }
    
    @keyframes ring { 0%{transform:rotate(0)} 10%{transform:rotate(15deg)} 20%{transform:rotate(-15deg)} 30%{transform:rotate(10deg)} 40%{transform:rotate(-10deg)} 50%{transform:rotate(0)} }
    .bell-ring { animation: ring 2s ease-in-out infinite; color: #F59E0B; }
    .animate-fade-in { animation: fadeIn 0.6s cubic-bezier(0.4, 0, 0.2, 1); }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
</style>
</head>
<body class="bg-blue-100 min-h-screen">

<div id="sidebarOverlay" class="fixed inset-0 bg-black/60 hidden lg:hidden z-40" onclick="toggleSidebar()"></div>

<?php include 'sidebar_admin.php'; ?>

<div id="mainContent" class="main flex-1 p-6 lg:p-10 min-h-screen relative z-10">
    
    <div class="header-panel p-4 mb-10 sticky top-4 z-50 flex flex-col md:flex-row justify-between items-center gap-4 transition-all duration-300">
        
        <div class="flex items-center gap-4 w-full md:w-auto">
            <button id="toggleBtn" onclick="toggleSidebar()" class="bg-amber-500 text-white p-2.5 rounded-xl shadow-lg hover:bg-amber-600 active:scale-95 transition-all">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>

            <form method="get" class="relative flex-1 md:w-80">
                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-blue-500">
                    <i class="fas fa-search text-lg"></i>
                </span>
                <input type="text" name="q" placeholder="Cari Permintaan..." value="<?= htmlspecialchars($keyword) ?>" 
                    class="w-full pl-12 pr-5 py-2.5 bg-white border border-gray-200 rounded-full text-sm font-medium focus:ring-4 focus:ring-blue-200/50 transition-all outline-none shadow-sm placeholder-gray-400">
            </form>
        </div>

        <div class="flex items-center gap-6 pr-2">
            <div class="relative">
                <button id="notifBtn" class="relative p-2.5 rounded-full bg-white hover:bg-gray-50 text-slate-600 hover:text-amber-500 transition-all shadow-sm border border-gray-200 <?= $jumlah_notif > 0 ? 'bell-ring' : '' ?>">
                    <i class="fas fa-bell text-xl"></i>
                    <?php if($jumlah_notif > 0): ?>
                        <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-[10px] font-bold flex items-center justify-center rounded-full border-2 border-white shadow-sm">
                            <?= $jumlah_notif ?>
                        </span>
                    <?php endif; ?>
                </button>
                
                <div id="notifDropdown" class="hidden absolute right-0 mt-4 w-80 bg-white rounded-2xl shadow-2xl border border-gray-200 z-50 overflow-hidden animate-fade-in">
                    <div class="px-5 py-3 bg-gradient-to-r from-amber-50 to-orange-50 border-b border-amber-100 font-bold text-xs text-amber-800 uppercase flex justify-between items-center">
                        <span>Notifikasi</span>
                        <span class="bg-amber-200 text-amber-800 px-2 py-0.5 rounded-full text-[10px]"><?= $jumlah_notif ?> Baru</span>
                    </div>
                    <div class="max-h-64 overflow-y-auto">
                        <?php if(!empty($notifikasi)): foreach($notifikasi as $n): ?>
                            <a href="detailpermintaan_admin.php?id=<?= $n['id'] ?>" class="block px-5 py-3 hover:bg-blue-50 border-b border-gray-50 last:border-0 transition-colors">
                                <div class="flex justify-between items-start mb-1">
                                    <p class="text-sm font-bold text-gray-800 truncate w-3/4"><?= htmlspecialchars($n['nama_peminjam']) ?></p>
                                    <span class="text-[10px] text-gray-400 font-medium"><?= date('d/m', strtotime($n['created_at'])) ?></span>
                                </div>
                                <p class="text-xs text-gray-500 truncate">Mengajukan: <span class="text-gray-700 font-medium"><?= htmlspecialchars($n['subpilihan']) ?></span></p>
                            </a>
                        <?php endforeach; else: ?>
                            <div class="p-8 text-center text-gray-400">
                                <i class="far fa-bell-slash text-3xl mb-2 opacity-50"></i>
                                <p class="text-xs">Tidak ada notifikasi.</p>
                            </div>
                        <?php endif; ?>
                        <div class="border-t border-gray-200 p-3">
        <a href="notifikasi_admin.php" class="block text-center text-sm font-bold text-blue-600 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 py-2 rounded-lg transition-colors">
            Lihat Semua Notifikasi <i class="fas fa-arrow-right ml-1"></i>
        </a>
    </div>
                    </div>
                </div>
            </div>

            <div class="relative">
                <button id="profileBtn" class="flex items-center gap-3 pl-4 border-l border-gray-300/50 focus:outline-none">
                    <div class="text-right hidden md:block leading-tight">
                        <p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($nama_admin) ?></p>
                        <p class="text-[10px] text-blue-600 font-bold tracking-wide uppercase bg-blue-100 px-2 py-0.5 rounded-full inline-block mt-0.5">Admin</p>
                    </div>
                    <div class="w-11 h-11 rounded-full bg-gradient-to-tr from-blue-500 to-indigo-600 p-0.5 shadow-lg cursor-pointer hover:scale-105 transition-transform">
                        <div class="w-full h-full bg-white rounded-full flex items-center justify-center text-blue-600 font-bold text-lg">
                            <?= strtoupper(substr($nama_admin, 0, 1)) ?>
                        </div>
                    </div>
                </button>

                <div id="profileDropdown" class="hidden absolute right-0 mt-4 w-48 bg-white rounded-2xl shadow-2xl border border-gray-200 z-50 overflow-hidden animate-fade-in">
                    <div class="py-1">
                        <a href="profil_admin.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors">
                            <i class="far fa-user-circle mr-2"></i> Profil Saya
                        </a>
                        <div class="border-t border-gray-100 my-1"></div>
                        <a href="../halamanutama.php" onclick="return confirm('Yakin ingin keluar?')" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors">
                            <i class="fas fa-sign-out-alt mr-2"></i> Keluar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-8 animate-fade-in">
        <h1 class="text-3xl font-extrabold text-slate-800">Dashboard Pengelola</h1>
        <p class="text-slate-500 mt-1">Ringkasan aktivitas peminjaman aset kampus.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-10 animate-fade-in">
        
        <div class="stat-card bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 text-white">
            <div class="relative z-10">
                <p class="text-indigo-100 text-xs font-bold uppercase tracking-wider mb-1">Total Aset</p>
                <h3 class="text-4xl font-black tracking-tight"><?= $total_ruangan ?></h3>
            </div>
            <i class="fas fa-building stat-icon-bg text-white"></i>
        </div>

        <div class="stat-card bg-gradient-to-br from-emerald-400 to-teal-600 text-white">
            <div class="relative z-10">
                <p class="text-emerald-100 text-xs font-bold uppercase tracking-wider mb-1">Selesai</p>
                <h3 class="text-4xl font-black tracking-tight"><?= $pemesanan_selesai ?></h3>
            </div>
            <i class="fas fa-clipboard-check stat-icon-bg text-white"></i>
        </div>

        <div class="stat-card bg-gradient-to-br from-amber-400 to-orange-500 text-white">
            <div class="relative z-10">
                <p class="text-amber-100 text-xs font-bold uppercase tracking-wider mb-1">Menunggu</p>
                <h3 class="text-4xl font-black tracking-tight"><?= $menunggu_persetujuan ?></h3>
            </div>
            <i class="fas fa-clock stat-icon-bg text-white"></i>
        </div>

        <div class="stat-card bg-gradient-to-br from-rose-500 to-red-600 text-white">
            <div class="relative z-10">
                <p class="text-rose-100 text-xs font-bold uppercase tracking-wider mb-1">Pendapatan (Bulan Ini)</p>
                <h3 class="text-2xl font-black tracking-tight">Rp <?= number_format($pendapatan, 0, ',', '.') ?></h3>
            </div>
            <i class="fas fa-wallet stat-icon-bg text-white"></i>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8 animate-fade-in" style="animation-delay: 0.2s;">
        
        <div class="xl:col-span-2 bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-bold text-slate-700 text-lg flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center"><i class="fas fa-history"></i></span>
                    Permintaan Terbaru
                </h3>
                <a href="permintaan_admin.php" class="text-xs font-bold text-blue-600 bg-blue-50 px-3 py-1.5 rounded-lg hover:bg-blue-100 transition-colors">
                    Lihat Semua <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            
            <div class="space-y-3">
                <?php if(!empty($riwayat)): foreach($riwayat as $r): 
                    $st = strtolower($r['status']);
                    $bgRow = "bg-white"; $borderL = "border-l-4 border-gray-300"; $iconSt = "fa-clock text-gray-400"; $textSt = "Pending";
                    
                    if($st == 'pending' || $st == 'menunggu') {
                        $bgRow = "hover:bg-amber-50"; $borderL = "border-l-4 border-amber-400"; $iconSt = "fa-hourglass-start text-amber-500"; $textSt = "Pending";
                    } elseif($st == 'disetujui') {
                        $bgRow = "hover:bg-emerald-50"; $borderL = "border-l-4 border-emerald-500"; $iconSt = "fa-check-circle text-emerald-500"; $textSt = "Disetujui";
                    } elseif($st == 'ditolak') {
                        $bgRow = "hover:bg-red-50"; $borderL = "border-l-4 border-red-500"; $iconSt = "fa-times-circle text-red-500"; $textSt = "Ditolak";
                    }
                ?>
                <div class="list-card p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 <?= $bgRow ?> <?= $borderL ?>">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center text-gray-500 font-bold text-sm shadow-inner">
                            <?= strtoupper(substr($r['nama_peminjam'], 0, 2)) ?>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-800"><?= htmlspecialchars($r['subpilihan']) ?></h4>
                            <p class="text-xs text-gray-500 flex items-center gap-1">
                                <i class="fas fa-user-circle text-gray-400"></i> <?= htmlspecialchars($r['nama_peminjam']) ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-1.5 text-xs font-bold bg-white px-3 py-1.5 rounded-full shadow-sm border border-gray-100">
                            <i class="fas <?= $iconSt ?>"></i> <span class="text-gray-600"><?= $textSt ?></span>
                        </div>
                        <a href="detailpermintaan_admin.php?id=<?= $r['id'] ?>" class="w-8 h-8 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-400 hover:text-blue-600 transition-all shadow-sm">
                            <i class="fas fa-chevron-right text-xs"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; else: ?>
                    <div class="py-10 text-center"><p class="text-sm text-slate-400">Belum ada permintaan.</p></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="xl:col-span-1 bg-white border border-gray-200 rounded-2xl shadow-sm p-6 h-fit">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-bold text-gray-700 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center"><i class="far fa-calendar-alt"></i></span>
                    Kalender
                </h3>
                <div class="flex gap-1">
                    <button class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600" onclick="changeMonth(-1)"><i class="fas fa-chevron-left text-xs"></i></button>
                    <button class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600" onclick="changeMonth(1)"><i class="fas fa-chevron-right text-xs"></i></button>
                </div>
            </div>
            <div class="text-center font-bold text-indigo-800 mb-4 text-sm tracking-wide" id="calendarTitle">...</div>
            <div class="calendar-container bg-gray-50 rounded-xl p-3">
                <div class="cal-grid mb-2">
                    <div class="cal-day-name">M</div><div class="cal-day-name">S</div><div class="cal-day-name">S</div>
                    <div class="cal-day-name">R</div><div class="cal-day-name">K</div><div class="cal-day-name">J</div>
                    <div class="cal-day-name">S</div>
                </div>
                <div id="calendarGrid" class="cal-grid"></div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-200">
                <div id="bookingInfo" class="text-xs text-slate-500 text-center italic bg-gray-50 p-3 rounded-xl">Klik tanggal merah untuk detail.</div>
            </div>
        </div>
    </div>
</div>


<script>
// === DROPDOWN TOGGLE LOGIC ===
const notifBtn = document.getElementById('notifBtn');
const notifDropdown = document.getElementById('notifDropdown');
const profileBtn = document.getElementById('profileBtn');
const profileDropdown = document.getElementById('profileDropdown');

notifBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    notifDropdown.classList.toggle('hidden');
    profileDropdown.classList.add('hidden');
});

profileBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    profileDropdown.classList.toggle('hidden');
    notifDropdown.classList.add('hidden');
});

document.addEventListener('click', (e) => {
    if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) notifDropdown.classList.add('hidden');
    if (!profileBtn.contains(e.target) && !profileDropdown.contains(e.target)) profileDropdown.classList.add('hidden');
});

// --- SIDEBAR & LAYOUT ---
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    const overlay = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('toggleBtn'); 
    const is_desktop = window.innerWidth >= 1024;

    if (is_desktop) {
        sidebar.classList.toggle('lg:w-60');
        sidebar.classList.toggle('lg:w-16');
        main.classList.toggle('lg:ml-60');
        main.classList.toggle('lg:ml-16');
        
        const is_expanded = sidebar.classList.contains('lg:w-60');
        if (is_expanded) {
            toggleBtn.style.left = '16rem'; 
        } else {
            toggleBtn.style.left = '5rem'; 
        }
    } else {
        sidebar.classList.toggle('translate-x-0');
        sidebar.classList.toggle('-translate-x-full');
        if (overlay) {
            overlay.classList.toggle('hidden');
        }
    }

    const is_expanded = sidebar.classList.contains('lg:w-60') || sidebar.classList.contains('translate-x-0');
    
    if (typeof updateSidebarVisibility === 'function') {
        updateSidebarVisibility(is_expanded);
    }
    
    localStorage.setItem('sidebarStatus', is_expanded ? 'open' : 'collapsed');
}

// --- KALENDER LOGIC ---
const bookedData = <?= $json_booked_data; ?>; 
let currDate = new Date();
const monthNames = ["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];

function renderCalendar() {
    const year = currDate.getFullYear();
    const month = currDate.getMonth();
    document.getElementById('calendarTitle').innerText = `${monthNames[month]} ${year}`;
    const grid = document.getElementById('calendarGrid');
    grid.innerHTML = "";
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    
    for(let i=0; i<firstDay; i++){ grid.appendChild(document.createElement("div")); }
    
    for(let i=1; i<=daysInMonth; i++){
        const div = document.createElement("div");
        div.innerText = i;
        div.className = "cal-date is-free"; 
        const monthStr = String(month + 1).padStart(2, '0');
        const dayStr = String(i).padStart(2, '0');
        const fullDate = `${year}-${monthStr}-${dayStr}`;
        
        if (bookedData[fullDate]) {
            div.className = "cal-date is-booked"; 
            div.onclick = function() { showInfo(fullDate); };
        }
        grid.appendChild(div);
    }
}

function changeMonth(dir) {
    currDate.setMonth(currDate.getMonth() + dir);
    renderCalendar();
}

function showInfo(dateStr) {
    const infoBox = document.getElementById('bookingInfo');
    const desc = bookedData[dateStr];
    infoBox.innerHTML = `<span class="font-bold text-blue-600 block mb-1">${dateStr}</span> ${desc}`;
    infoBox.className = "text-xs bg-white p-3 rounded-xl text-gray-700 border border-gray-200 shadow-sm text-left";
}

document.addEventListener('DOMContentLoaded', () => {
    renderCalendar();
    const sidebar = document.getElementById('sidebar');
    // Restore Sidebar
    if (localStorage.getItem('sidebarStatus') === 'open' && window.innerWidth >= 1024) {
        sidebar.classList.add('lg:w-60'); sidebar.classList.remove('lg:w-16');
        document.querySelector('.main').classList.add('lg:ml-60'); document.querySelector('.main').classList.remove('lg:ml-16');
    }
});

window.addEventListener('resize', () => {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    const overlay = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('toggleBtn'); 
    const is_desktop = window.innerWidth >= 1024;
    const status = localStorage.getItem('sidebarStatus');

    if (is_desktop) {
        sidebar.classList.remove('translate-x-0', '-translate-x-full');
        if (overlay) overlay.classList.add('hidden');

        if (status === 'open') {
            sidebar.classList.add('lg:w-60');
            sidebar.classList.remove('lg:w-16');
            main.classList.add('lg:ml-60');
            main.classList.remove('lg:ml-16');
            toggleBtn.style.left = '16rem'; 
        } else {
            sidebar.classList.add('lg:w-16');
            sidebar.classList.remove('lg:w-60');
            main.classList.add('lg:ml-16');
            main.classList.remove('lg:ml-60');
            toggleBtn.style.left = '5rem'; 
        }
    } else {
        sidebar.classList.remove('lg:w-60', 'lg:w-16');
        main.classList.remove('lg:ml-60', 'lg:ml-16');
        toggleBtn.style.left = '1.25rem'; 

        if (status === 'open') {
            sidebar.classList.add('translate-x-0');
            sidebar.classList.remove('-translate-x-full');
            if (overlay) overlay.classList.remove('hidden');
        } else {
            sidebar.classList.add('-translate-x-full');
            sidebar.classList.remove('translate-x-0');
            if (overlay) overlay.classList.add('hidden');
        }
    }
});
</script>

</body>
</html>