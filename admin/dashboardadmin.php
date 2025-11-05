<?php
// ... (Bagian PHP tetap sama) ...
session_start();
require_once 'config/koneksi.php';
$db = new Database();

// ====== Simulasi nama admin ======
if (!isset($_SESSION['nama_admin'])) {
    $_SESSION['nama_admin'] = 'Admin Pengelola';
}
$nama_admin = $_SESSION['nama_admin'];

// ... (Bagian Hitung Total Ruangan dan Statistik Pemesanan tetap sama) ...
try {
    // Menghitung hanya ruangan yang aktif
    $db->query("SELECT COUNT(*) as total FROM ruangan_multiguna WHERE status='aktif'");
    $total_multiguna = $db->single()['total'] ?? 0;

    $db->query("SELECT COUNT(*) as total FROM fasilitas WHERE status='aktif'");
    $total_fasilitas = $db->single()['total'] ?? 0;

    $db->query("SELECT COUNT(*) as total FROM laboratorium WHERE status='aktif'");
    $total_laboratorium = $db->single()['total'] ?? 0;

    $db->query("SELECT COUNT(*) as total FROM usaha WHERE status='aktif'");
    $total_usaha = $db->single()['total'] ?? 0;
} catch (Exception $e) {
    // Fallback jika query gagal
    $total_multiguna = $total_fasilitas = $total_laboratorium = $total_usaha = 0;
}

$total_ruangan = $total_multiguna + $total_fasilitas + $total_laboratorium + $total_usaha;

// =====================
// Statistik Pemesanan
// =====================
try {
    $db->query("SELECT COUNT(*) as total FROM pemesanan WHERE status = 'disetujui'");
    $pemesanan_selesai = $db->single()['total'] ?? 0;

    $db->query("SELECT COUNT(*) as total FROM pemesanan WHERE status = 'pending'");
    $menunggu_persetujuan = $db->single()['total'] ?? 0;
} catch (Exception $e) {
    $pemesanan_selesai = $menunggu_persetujuan = 0;
}

// Catatan: Pendapatan ini adalah nilai statis (contoh)
$pendapatan = 5000000;

// =====================
// Riwayat Permintaan (5 Terbaru)
// =====================
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$sql = "SELECT * FROM pemesanan WHERE 1=1";
$params = [];

if ($keyword !== '') {
    $sql .= " AND (pemohon LIKE :kw OR nama_ruang LIKE :kw)";
    $params[':kw'] = "%$keyword%";
}
$sql .= " ORDER BY created_at DESC LIMIT 5";

try {
    $db->query($sql);
    foreach ($params as $key => $value) {
        $db->bind($key, $value);
    }
    $riwayat = $db->resultSet();
} catch (Exception $e) {
    $riwayat = [];
}


// =====================
// Notifikasi Terbaru
// =====================
try {
    $db->query("SELECT * FROM notifikasi WHERE status='baru' ORDER BY tanggal DESC LIMIT 5");
    $notifikasi = $db->resultSet();
    $jumlah_notif = count($notifikasi);
} catch (Exception $e) {
    $notifikasi = [];
    $jumlah_notif = 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin Pengelola</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
/* CSS umum dan Poppins */
body {  
    font-family: 'Poppins', sans-serif; 
}
.text-dark-accent {
    color: #202938;
}

/* Memastikan Sidebar menggunakan z-index tinggi di mobile dan posisi tetap */
@media (min-width: 1024px) {
    .main { margin-left: 4rem; }
    .main.lg\:ml-60 { margin-left: 15rem; }
}
@media (max-width: 1023px) {
    .main { margin-left: 0 !important; }
}

/* Menetapkan z-index pada header agar tidak menutupi dropdown notif/profil */
.header {
    z-index: 40; 
}

/* REVISI CSS UNTUK TOMBOL TOGGLE */
#toggleBtn {
    position: fixed; 
    top: 1.25rem; 
    left: 1.25rem; 
    z-index: 60 !important; 
    transition: left 0.3s ease; 
}
/* END REVISI CSS */

/* Menambahkan style fixed z-index 50 untuk konsistensi sidebar */
#sidebar {
    position: fixed;
    z-index: 50; 
    top: 0;
    left: 0;
    height: 100%;
}


/* === Custom Card Style === */
.stat-card {
    position: relative;
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    overflow: hidden;
    cursor: default;
    display: flex;
    flex-direction: column;
}
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}
.stat-icon {
    font-size: 2.25rem;
    opacity: 0.9;
    position: absolute;
    right: 1rem;
    top: 1rem;
}
</style>
</head>
<body class="bg-blue-100 flex min-h-screen text-gray-800">

<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

<?php 
include 'sidebar_admin.php'; 
?>

<button class="hamburger bg-amber-500 hover:bg-amber-600 text-gray-900 p-2 rounded-lg transition-colors shadow-md" id="toggleBtn" onclick="toggleSidebar()">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
    </svg>
</button>
<div id="mainContent" class="main flex-1 p-5 transition-all duration-300 lg:ml-16">
    <div class="header sticky top-0 bg-blue-100/95 backdrop-blur-sm z-40 py-2 -mx-5 px-5 flex justify-end items-center mb-5 border-b border-gray-200 relative">
        
        <form method="get" class="absolute left-1/2 -translate-x-1/2 flex items-center max-w-sm flex-grow mx-4 md:mx-0 md:flex-grow-0 w-full lg:w-96 hidden lg:flex">
            <input type="text" name="q" id="searchInput" placeholder="Cari Permintaan..." value="<?= htmlspecialchars($keyword) ?>" class="w-full px-3 py-2 rounded-full border border-gray-300 text-sm focus:ring-amber-500 focus:border-amber-500">
            <span class="search-clear absolute right-3 text-gray-500 text-lg cursor-pointer <?= $keyword !== '' ? 'block' : 'hidden' ?>" id="clearBtn" title="Hapus Pencarian">&times;</span>
        </form>

        <form method="get" class="relative flex items-center max-w-sm flex-grow mx-4 md:mx-0 md:flex-grow-0 lg:hidden">
            <input type="text" name="q" id="searchInputMobile" placeholder="Cari Permintaan..." value="<?= htmlspecialchars($keyword) ?>" class="w-full px-3 py-2 rounded-full border border-gray-300 text-sm focus:ring-amber-500 focus:border-amber-500">
            </form>

        <div class="flex items-center gap-5">
            <div class="notif-wrapper relative">
                <div class="notif-icon text-xl cursor-pointer transition-transform text-gray-600 hover:text-orange-600" onclick="toggleNotifDropdown()">🔔
                    <?php if ($jumlah_notif > 0): ?>
                        <span class="notif-badge absolute -top-1 -right-1 bg-orange-600 text-white text-xs px-2 py-0.5 rounded-full font-bold leading-none"><?= $jumlah_notif ?></span>
                    <?php endif; ?>
                </div>
                <div class="notif-dropdown hidden absolute right-0 top-10 w-72 bg-white shadow-xl rounded-xl overflow-hidden z-50 border border-gray-200" id="notifDropdown">
                    <div class="notif-header flex justify-between items-center bg-orange-200 px-3 py-2 font-semibold text-sm border-b">
                        <strong>Notifikasi Terbaru</strong>
                        <a href="notifikasi_admin.php" class="text-orange-700 text-xs hover:underline">Lihat Semua</a>
                    </div>
                    <div class="notif-list max-h-56 overflow-y-auto">
                        <?php if ($notifikasi): foreach ($notifikasi as $n): ?>
                            <div class="notif-item px-3 py-2 border-b border-gray-100 text-sm cursor-pointer hover:bg-orange-50 transition-colors" onclick="markAsRead(<?= $n['id'] ?>)">
                                <div><?= htmlspecialchars($n['judul']) ?></div>
                                <small class="text-gray-500 text-xs"><?= date('d M Y, H:i', strtotime($n['tanggal'])) ?></small>
                            </div>
                        <?php endforeach; else: ?>
                            <div class="notif-item text-center text-gray-500 italic py-4">Tidak ada notifikasi baru</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="profile relative flex items-center gap-2 font-semibold cursor-pointer select-none text-dark-accent" onclick="toggleProfileDropdown()">
                👤 <span class="hidden sm:inline"><?= htmlspecialchars($nama_admin) ?></span> ⏷
                <div class="profile-dropdown hidden absolute right-0 top-10 bg-white rounded-xl shadow-lg overflow-hidden z-50 w-44 border border-gray-200">
                    <a href="profil_admin.php" class="block px-4 py-2 text-gray-800 text-sm border-b border-gray-100 hover:bg-amber-50 hover:text-dark-accent">👤 Profil Saya</a>
                    <a href="#" onclick="logoutAdmin()" class="block px-4 py-2 text-gray-800 text-sm hover:bg-red-50 hover:text-red-700">🚪 Keluar</a>
                </div>
            </div>
        </div>
    </div>

    <h2 class="text-2xl font-bold mb-5 text-dark-accent">Ringkasan Statistik</h2>
    <div class="stats grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        
        <div class="stat-card bg-gray-700 text-white">
            <div class="stat-icon text-gray-400">🏢</div>
            <div class="text-sm opacity-90 mb-1">TOTAL RUANGAN IT PLN</div>
            <span class="text-4xl font-extrabold mt-1"><?= $total_ruangan ?></span>
        </div>
        
        <div class="stat-card bg-green-500 text-white">
            <div class="stat-icon text-green-200">✅</div>
            <div class="text-sm opacity-90 mb-1">PEMESANAN SELESAI</div>
            <span class="text-4xl font-extrabold mt-1"><?= $pemesanan_selesai ?></span>
        </div>
        
        <div class="stat-card bg-yellow-400 text-gray-900">
            <div class="stat-icon text-yellow-700">⏳</div>
            <div class="text-sm opacity-90 mb-1">MENUNGGU PERSETUJUAN</div>
            <span class="text-4xl font-extrabold mt-1"><?= $menunggu_persetujuan ?></span>
        </div>
        
        <div class="stat-card bg-red-500 text-white">
            <div class="stat-icon text-red-200">💸</div>
            <div class="text-sm opacity-90 mb-1">PENDAPATAN BULAN INI</div>
            <span class="text-3xl font-extrabold mt-1">Rp <?= number_format($pendapatan,0,',','.') ?></span>
        </div>
    </div>

    <div class="riwayat">
        <h2 class="text-2xl font-bold mb-5 text-dark-accent">Riwayat Permintaan</h2>
        <?php if ($riwayat): foreach($riwayat as $r): ?>
            <div class="bg-blue-50 p-4 rounded-xl mb-3 flex flex-col md:flex-row justify-between items-start md:items-center shadow-md hover:shadow-lg transition-all duration-200">
                <div class="mb-2 md:mb-0 max-w-full md:max-w-[70%]">
                    <strong class="block text-lg text-dark-accent"><?= htmlspecialchars($r['nama_ruang']); ?></strong>
                    <div class="text-gray-600 text-sm space-x-2">
                        <span>Pemohon: <?= htmlspecialchars($r['pemohon']); ?></span>
                        <span class="text-gray-400">|</span>
                        <span><?= date('d/m/Y', strtotime($r['tanggal_mulai'])); ?> - <?= date('d/m/Y', strtotime($r['tanggal_selesai'])); ?></span>
                    </div>
                </div>
                
                <div class="flex items-center gap-3">
                    <?php if($r["status"]=="pending"): ?>
                        <span class="bg-yellow-300 text-gray-800 px-3 py-1 rounded-full text-xs font-semibold whitespace-nowrap">Pending</span>
                    <?php elseif($r["status"]=="disetujui"): ?>
                        <span class="bg-green-300 text-gray-900 px-3 py-1 rounded-full text-xs font-semibold whitespace-nowrap">Disetujui</span>
                    <?php else: ?>
                        <span class="bg-red-300 text-gray-900 px-3 py-1 rounded-full text-xs font-semibold whitespace-nowrap">Ditolak</span>
                    <?php endif; ?>
                    <a href="detailpermintaan_admin.php?id=<?= $r['id']; ?>" class="text-dark-accent font-semibold hover:text-gray-900 transition-colors whitespace-nowrap">Lihat & Proses</a>
                </div>
            </div>
        <?php endforeach; else: ?>
            <p class="text-gray-500 italic p-4 bg-white rounded-xl shadow-md">Belum ada permintaan.</p>
        <?php endif; ?>
    </div>
</div>

<script>
// PENTING: Fungsi toggleSidebar() yang KONSISTEN
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    const overlay = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('toggleBtn'); // Ambil toggle button
    const is_desktop = window.innerWidth >= 1024;

    if (is_desktop) {
        sidebar.classList.toggle('lg:w-60');
        sidebar.classList.toggle('lg:w-16');
        main.classList.toggle('lg:ml-60');
        main.classList.toggle('lg:ml-16');
        
        // KODE REVISI: Geser tombol toggle di desktop
        const is_expanded = sidebar.classList.contains('lg:w-60');
        if (is_expanded) {
             // Jika sidebar terbuka (15rem), pindahkan tombol ke kanan
            toggleBtn.style.left = '16rem'; 
        } else {
             // Jika sidebar tertutup (4rem), pindahkan tombol di samping sidebar kecil
            toggleBtn.style.left = '5rem'; 
        }

    } else {
        sidebar.classList.toggle('translate-x-0');
        sidebar.classList.toggle('-translate-x-full');
        // Di mobile, tombol toggle tetap di kiri (1.25rem)
        if (overlay) {
            overlay.classList.toggle('hidden');
        }
    }

    const is_expanded = sidebar.classList.contains('lg:w-60') || sidebar.classList.contains('translate-x-0');
    
    // Asumsi fungsi updateSidebarVisibility ada di sidebar_admin.php
    if (typeof updateSidebarVisibility === 'function') {
        updateSidebarVisibility(is_expanded);
    }
    
    localStorage.setItem('sidebarStatus', is_expanded ? 'open' : 'collapsed');
}

// Notifikasi dan Profil
function toggleNotifDropdown(){
    const dropdown = document.getElementById("notifDropdown");
    dropdown.classList.toggle("hidden");
    document.querySelector(".profile-dropdown").classList.add("hidden"); 
}
function toggleProfileDropdown(){
    const dropdown = document.querySelector(".profile-dropdown");
    dropdown.classList.toggle("hidden");
    document.getElementById("notifDropdown").classList.add("hidden"); 
}
document.addEventListener('click',function(e){
    // Tambahkan kondisi untuk tidak menutup saat klik toggle button atau sidebar/overlay
    if(!e.target.closest('.notif-wrapper') && !e.target.closest('.profile') && !e.target.closest('#toggleBtn') && !e.target.closest('#sidebar') && !e.target.closest('#sidebarOverlay')){
        document.getElementById("notifDropdown").classList.add("hidden");
        document.querySelector(".profile-dropdown").classList.add("hidden");
    }
});

// Tombol clear search
const searchInputMobile=document.getElementById('searchInputMobile'); // Hanya untuk mobile
const searchInputDesktop=document.getElementById('searchInput'); // Hanya untuk desktop
const clearBtn=document.getElementById('clearBtn'); // Hanya untuk desktop

// Fungsi untuk memperbarui tampilan tombol X
function updateClearBtn(){
    // Hanya berlaku untuk pencarian desktop
    if (clearBtn && searchInputDesktop) {
        clearBtn.style.display = searchInputDesktop.value.length > 0 ? 'block' : 'none';
    }
}
updateClearBtn();
// Gunakan input desktop untuk event listener karena input mobile tampil bersama tombol clear
if (searchInputDesktop) {
    searchInputDesktop.addEventListener('input',updateClearBtn);
}
if (clearBtn) {
    clearBtn.addEventListener('click',() => {
        // Arahkan ke dashboard tanpa parameter apapun
        window.location.href='dashboardadmin.php';
    });
}
// Tambahan: jika ada input di mobile, harusnya refresh untuk clear
if (searchInputMobile) {
    searchInputMobile.addEventListener('input', () => {
        // Anda mungkin perlu logika clear yang terpisah untuk mobile jika tidak menggunakan tombol X
    });
}


// Notifikasi dibaca
function markAsRead(id){
    // Ganti update_notifikasi.php dengan path yang benar
    fetch('update_notifikasi.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'id='+id
    }).then(r=>r.text()).then(res=>{
        if(res==='ok'){location.reload();}
    });
}

// Logout
function logoutAdmin(){
    if(confirm("Yakin ingin keluar?")){
        // Ganti index.php dengan path halaman login yang benar
        window.location.href='index.php'; 
    }
}

// === DOMContentLoaded (Restore State saat Load) ===
document.addEventListener("DOMContentLoaded", () => {
    const sidebar = document.getElementById("sidebar");
    const main = document.getElementById("mainContent");
    const overlay = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('toggleBtn'); 
    const status = localStorage.getItem('sidebarStatus');
    const is_desktop = window.innerWidth >= 1024;

    if (sidebar) {
        // Restore state saat load
        if (status === 'open') {
            if (is_desktop) {
                main.classList.add('lg:ml-60');
                main.classList.remove('lg:ml-16');
                sidebar.classList.add('lg:w-60');
                sidebar.classList.remove('lg:w-16');
                // Posisikan tombol toggle saat dibuka (lebar 15rem + 1rem)
                toggleBtn.style.left = '16rem'; 
            } else {
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
                if (overlay) overlay.classList.remove('hidden');
            }
        } else if (status === 'collapsed') {
            if (is_desktop) {
                main.classList.remove('lg:ml-60');
                main.classList.add('lg:ml-16');
                sidebar.classList.remove('lg:w-60');
                sidebar.classList.add('lg:w-16');
                // Posisikan tombol toggle saat ditutup (lebar 4rem + 1rem)
                toggleBtn.style.left = '5rem'; 
            } else {
                sidebar.classList.remove('translate-x-0');
                sidebar.classList.add('-translate-x-full');
                if (overlay) overlay.classList.add('hidden');
            }
        }
    }

    // Tambahkan listener untuk menutup sidebar saat overlay diklik
    if (overlay) {
        overlay.addEventListener('click', toggleSidebar);
    }
});


// === Penanganan Resize (KONSISTEN DENGAN EDITPERMINTAAN) ===
window.addEventListener('resize', () => {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    const overlay = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('toggleBtn'); 
    const is_desktop = window.innerWidth >= 1024;
    const status = localStorage.getItem('sidebarStatus');

    if (is_desktop) {
        // Pastikan transisi mobile dihilangkan
        sidebar.classList.remove('translate-x-0', '-translate-x-full');
        if (overlay) overlay.classList.add('hidden');

        // Terapkan state desktop
        if (status === 'open') {
            sidebar.classList.add('lg:w-60');
            sidebar.classList.remove('lg:w-16');
            main.classList.add('lg:ml-60');
            main.classList.remove('lg:ml-16');
            toggleBtn.style.left = '16rem'; // Posisikan tombol toggle saat dibuka
        } else {
            sidebar.classList.add('lg:w-16');
            sidebar.classList.remove('lg:w-60');
            main.classList.add('lg:ml-16');
            main.classList.remove('lg:ml-60');
            toggleBtn.style.left = '5rem'; // Posisikan tombol toggle saat ditutup
        }
        updateClearBtn(); // Update tombol clear desktop
    } else {
        // Pastikan transisi desktop dihilangkan
        sidebar.classList.remove('lg:w-60', 'lg:w-16');
        main.classList.remove('lg:ml-60', 'lg:ml-16');
        toggleBtn.style.left = '1.25rem'; // Reset posisi ke kiri atas di mobile

        // Terapkan state mobile
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