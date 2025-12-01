<?php
session_start();
require_once '../koneksi.php'; 
$db = $koneksi;

// Cek Session
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') { ... }

$success_msg = '';
$error_msg = '';

// --- 1. HANDLE UPDATE PENGATURAN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $kunci => $nilai) {
        $kunci = mysqli_real_escape_string($db, $kunci);
        $nilai = mysqli_real_escape_string($db, $nilai);
        
        $sql = "UPDATE tbl_pengaturan SET nilai = '$nilai' WHERE kunci = '$kunci'";
        if (!mysqli_query($db, $sql)) {
            $error_msg = "Gagal menyimpan $kunci";
        }
    }
    
    if (empty($error_msg)) {
        $success_msg = "Pengaturan sistem berhasil diperbarui!";
    }
}

// --- 2. AMBIL DATA PENGATURAN ---
$settings = [];
$q = mysqli_query($db, "SELECT * FROM tbl_pengaturan");
while ($r = mysqli_fetch_assoc($q)) {
    $settings[$r['kunci']] = $r['nilai'];
}

$nama_web = $settings['nama_website'] ?? 'Sistem Aset';
$email_kontak = $settings['email_kontak'] ?? '';
$no_telp = $settings['no_telepon'] ?? '';
$alamat = $settings['alamat'] ?? '';
$maintenance = $settings['maintenance_mode'] ?? '0';
$pengumuman = $settings['pengumuman_global'] ?? '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pengaturan Sistem</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    body { font-family: 'Poppins', sans-serif; background-color: #DBEAFE; }
    
    .main { transition: margin-left 0.3s; }
    #sidebar { transition: transform 0.3s; }
    
    @media (min-width: 1024px) { .main { margin-left: 16rem; } }
    @media (max-width: 1023px) { .main { margin-left: 0; } }

    /* Toggle Switch CSS */
    .toggle-checkbox:checked { right: 0; border-color: #68D391; }
    .toggle-checkbox:checked + .toggle-label { background-color: #68D391; }

    /* Modal Animation */
    .modal { opacity: 0; visibility: hidden; transition: all 0.3s ease; }
    .modal.show { opacity: 1; visibility: visible; }
    .modal-content { transform: scale(0.9); transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
    .modal.show .modal-content { transform: scale(1); }
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

        <a href="laporan_pusat.php" class="flex items-center px-4 py-3 rounded-xl text-gray-400 hover:bg-white/5 hover:text-white transition-all duration-200 group">
            <div class="w-6 flex justify-center">
                <i class="fas fa-file-contract text-lg group-hover:text-emerald-400 transition-colors"></i>
            </div>
            <span class="ml-3 font-medium">Laporan Penggunaan</span>
        </a>

        <a href="pengaturan_sistem.php" class="flex items-center px-4 py-3.5 rounded-xl bg-[#6366f1] text-white shadow-lg shadow-indigo-500/30 transition-all hover:scale-[1.02]">
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

    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Pengaturan Sistem</h1>
            <p class="text-sm text-slate-500">Konfigurasi global aplikasi peminjaman aset.</p>
        </div>
        <button form="settingsForm" type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl shadow-md hover:shadow-lg transition-all flex items-center gap-2">
            <i class="fas fa-save"></i> Simpan Perubahan
        </button>
    </div>

    <?php if ($success_msg): ?>
        <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded-lg mb-6 flex items-center shadow-sm" id="alertBox">
            <i class="fas fa-check-circle mr-2"></i> <?= $success_msg ?>
        </div>
    <?php endif; ?>

    <form id="settingsForm" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h2 class="text-lg font-bold text-slate-700 mb-4 flex items-center gap-2">
                    <span class="bg-blue-100 text-blue-600 p-1.5 rounded-lg"><i class="fas fa-globe"></i></span> 
                    Identitas Website
                </h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-600 mb-1">Nama Website</label>
                        <input type="text" name="nama_website" value="<?= htmlspecialchars($nama_web) ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-600 mb-1">Pengumuman Global</label>
                        <textarea name="pengumuman_global" rows="3" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"><?= htmlspecialchars($pengumuman) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h2 class="text-lg font-bold text-slate-700 mb-4 flex items-center gap-2">
                    <span class="bg-amber-100 text-amber-600 p-1.5 rounded-lg"><i class="fas fa-address-book"></i></span> 
                    Informasi Kontak
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-600 mb-1">Email Resmi</label>
                        <input type="email" name="email_kontak" value="<?= htmlspecialchars($email_kontak) ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-600 mb-1">No. Telepon</label>
                        <input type="text" name="no_telepon" value="<?= htmlspecialchars($no_telp) ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg outline-none">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-600 mb-1">Alamat Kampus</label>
                        <input type="text" name="alamat" value="<?= htmlspecialchars($alamat) ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg outline-none">
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h2 class="text-lg font-bold text-slate-700 mb-4 flex items-center gap-2">
                    <span class="bg-red-100 text-red-600 p-1.5 rounded-lg"><i class="fas fa-tools"></i></span> 
                    Status Sistem
                </h2>
                
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="font-semibold text-slate-800">Mode Perbaikan</p>
                        <p class="text-xs text-slate-500">Jika aktif, user tidak bisa login.</p>
                    </div>
                    
                    <div class="relative inline-block w-12 mr-2 align-middle select-none transition duration-200 ease-in">
                        <input type="hidden" name="maintenance_mode" value="0">
                        <input type="checkbox" name="maintenance_mode" value="1" id="toggle" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer transition-all duration-300 ease-in-out transform translate-x-0 checked:translate-x-6 checked:border-emerald-400" <?= $maintenance == '1' ? 'checked' : '' ?>>
                        <label for="toggle" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer checked:bg-emerald-400 transition-colors duration-300"></label>
                    </div>
                </div>
                
                <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                    <p class="text-xs text-blue-800">
                        <i class="fas fa-info-circle mr-1"></i> 
                        <strong>Info:</strong> Pastikan tidak ada transaksi mendesak sebelum mengaktifkan mode perbaikan.
                    </p>
                </div>
            </div>

            <div class="bg-gradient-to-br from-indigo-600 to-blue-600 p-6 rounded-2xl shadow-lg text-white">
                <h3 class="font-bold text-lg mb-2">Butuh Bantuan?</h3>
                <p class="text-sm text-indigo-100 mb-4">Jika terjadi kendala teknis pada server atau database.</p>
                <button type="button" onclick="openSupportModal()" class="w-full bg-white text-indigo-600 py-2 rounded-lg font-bold text-sm hover:bg-indigo-50 transition">
                    Hubungi IT Support
                </button>
            </div>
        </div>
    </form>
</div>

<div id="supportModal" class="modal fixed inset-0 z-[100] flex items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeSupportModal()"></div>
    <div class="modal-content bg-white w-full max-w-sm rounded-2xl shadow-2xl z-10 relative overflow-hidden">
        
        <div class="px-6 py-4 bg-gradient-to-r from-indigo-600 to-blue-600 flex justify-between items-center">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <i class="fas fa-headset"></i> Tim IT Support
            </h3>
            <button onclick="closeSupportModal()" class="text-white/80 hover:text-white text-2xl">&times;</button>
        </div>

        <div class="p-6 space-y-4">
            
            <div class="flex items-center gap-4 p-3 rounded-xl bg-indigo-50 border border-indigo-100 hover:shadow-md transition-all">
                <div class="w-10 h-10 rounded-full bg-indigo-200 text-indigo-700 flex items-center justify-center font-bold text-lg shadow-sm">M</div>
                <div class="flex-1">
                    <h4 class="font-bold text-slate-800 text-lg">Maurin</h4>
                </div>
                <a href="https://wa.me/6282112216706" target="_blank" class="w-9 h-9 rounded-full bg-green-100 text-green-600 flex items-center justify-center hover:bg-green-200 transition-colors">
                    <i class="fab fa-whatsapp text-xl"></i>
                </a>
            </div>

            <div class="flex items-center gap-4 p-3 rounded-xl bg-pink-50 border border-pink-100 hover:shadow-md transition-all">
                <div class="w-10 h-10 rounded-full bg-pink-200 text-pink-700 flex items-center justify-center font-bold text-lg shadow-sm">T</div>
                <div class="flex-1">
                    <h4 class="font-bold text-slate-800 text-lg">Tarissa</h4>
                </div>
                <a href="https://wa.me/6289643680037" target="_blank" class="w-9 h-9 rounded-full bg-green-100 text-green-600 flex items-center justify-center hover:bg-green-200 transition-colors">
                    <i class="fab fa-whatsapp text-xl"></i>
                </a>
            </div>

            <div class="flex items-center gap-4 p-3 rounded-xl bg-amber-50 border border-amber-100 hover:shadow-md transition-all">
                <div class="w-10 h-10 rounded-full bg-amber-200 text-amber-700 flex items-center justify-center font-bold text-lg shadow-sm">R</div>
                <div class="flex-1">
                    <h4 class="font-bold text-slate-800 text-lg">Renny</h4>
                </div>
                <a href="https://wa.me/6281247474435" target="_blank" class="w-9 h-9 rounded-full bg-green-100 text-green-600 flex items-center justify-center hover:bg-green-200 transition-colors">
                    <i class="fab fa-whatsapp text-xl"></i>
                </a>
            </div>

        </div>

        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 text-center">
            <button onclick="closeSupportModal()" class="w-full py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-lg transition-colors">Tutup</button>
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

// --- LOGIC MODAL POPUP ---
function openSupportModal() {
    const modal = document.getElementById('supportModal');
    const content = modal.querySelector('.modal-content');
    
    modal.classList.remove('hidden');
    // Sedikit delay agar animasi jalan
    setTimeout(() => {
        modal.classList.add('show');
        content.classList.remove('scale-90');
        content.classList.add('scale-100');
    }, 10);
}

function closeSupportModal() {
    const modal = document.getElementById('supportModal');
    const content = modal.querySelector('.modal-content');
    
    modal.classList.remove('show');
    content.classList.remove('scale-100');
    content.classList.add('scale-90');
    
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300); 
}

// Auto hide alert
setTimeout(function() {
    const alert = document.getElementById('alertBox');
    if(alert) alert.style.display = 'none';
}, 3000);
</script>
</body>
</html>