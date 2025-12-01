<?php
session_start();
require_once '../koneksi.php'; 

// Cek Login & Role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$db = $koneksi; 
$user_id = $_SESSION['user_id'];

$success_message = null;
$error_message = null;
$tab_active = 'info';

// --- 1. AMBIL DATA ADMIN ---
$admin_data = [];
$stmt = $db->prepare("SELECT * FROM tbl_user WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $admin_data = $result->fetch_assoc();
    } else {
        session_destroy();
        header("Location: ../login.php");
        exit;
    }
    $stmt->close();
}

// --- 2. LOGIC STATISTIK ---
$stats = ['total_aset' => 0, 'pemesanan_bulan_ini' => 0, 'pending_approval' => 0];
try {
    $tables = ['tbl_ruangmultiguna', 'tbl_fasilitas', 'tbl_usaha']; 
    $total_aset = 0;
    foreach ($tables as $tb) {
        $q = $db->query("SELECT COUNT(*) as c FROM $tb");
        if ($q) $total_aset += $q->fetch_assoc()['c'];
    }
    $labs = ['labftik', 'labften', 'labfket', 'labftbe'];
    foreach ($labs as $lb) {
        $q = $db->query("SELECT COUNT(*) as c FROM $lb");
        if ($q) $total_aset += $q->fetch_assoc()['c'];
    }
    $stats['total_aset'] = $total_aset;

    $res_pesan = $db->query("SELECT COUNT(*) as total FROM tbl_pengajuan WHERE MONTH(tanggal_peminjaman) = MONTH(NOW()) AND YEAR(tanggal_peminjaman) = YEAR(NOW()) AND status != 'ditolak'");
    if($res_pesan) $stats['pemesanan_bulan_ini'] = $res_pesan->fetch_assoc()['total'];

    $res_pending = $db->query("SELECT COUNT(*) as total FROM tbl_pengajuan WHERE status = 'pending' OR status = 'Menunggu'");
    if($res_pending) $stats['pending_approval'] = $res_pending->fetch_assoc()['total'];

} catch (Exception $e) {}

// --- 3. HANDLE UPDATE PROFIL ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password_baru = $_POST['password_baru'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';
    
    $errors = [];
    if (empty($email)) $errors[] = "Email harus diisi";
    
    if (!empty($password_baru)) {
        $tab_active = 'edit';
        if (strlen($password_baru) < 6) $errors[] = "Password baru minimal 6 karakter";
        if ($password_baru !== $konfirmasi_password) $errors[] = "Konfirmasi password tidak cocok";
    }
    
    if (empty($errors)) {
        $sql_update = "UPDATE tbl_user SET email = ? WHERE id = ?";
        $stmt = $db->prepare($sql_update);
        $stmt->bind_param("si", $email, $user_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            if (!empty($password_baru)) {
                $pass_hash = password_hash($password_baru, PASSWORD_DEFAULT);
                $stmt_pass = $db->prepare("UPDATE tbl_user SET password = ? WHERE id = ?");
                $stmt_pass->bind_param("si", $pass_hash, $user_id);
                $stmt_pass->execute();
                $stmt_pass->close();
            }
            $success_message = "Profil berhasil diperbarui!";
            $tab_active = 'info';
            
            // Refresh Data
            $stmt = $db->prepare("SELECT * FROM tbl_user WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $admin_data = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } else {
            $error_message = "Gagal update database: " . $db->error;
            $tab_active = 'edit';
        }
    } else {
        $error_message = implode("<br>", $errors);
        $tab_active = 'edit';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #F3F4F6; }
        .text-dark-accent { color: #1e293b; }
        
        /* Sidebar Transitions */
        .main { transition: margin-left 0.3s ease-in-out; }
        @media (min-width: 1024px) {
            .main { margin-left: 4rem; }
            .main.lg\:ml-60 { margin-left: 15rem; }
        }
        @media (max-width: 1023px) {
            .main { margin-left: 0 !important; }
        }

        #toggleBtn {
            position: fixed; 
            top: 1.25rem; 
            left: 1.25rem; 
            z-index: 60 !important; 
            transition: left 0.3s ease; 
        }

        #sidebar {
            position: fixed;
            z-index: 50; 
            top: 0;
            left: 0;
            height: 100%;
        }

        /* Animasi Tab */
        .tab-content { 
            animation: fadeIn 0.4s ease-out; 
        }
        @keyframes fadeIn { 
            from { opacity: 0; transform: translateY(10px); } 
            to { opacity: 1; transform: translateY(0); } 
        }

        /* Styling Kartu */
        .glass-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid #e5e7eb;
        }
        
        /* Tombol Tab */
        .tab-btn {
            position: relative;
            transition: all 0.3s ease;
        }
        .tab-btn::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 3px;
            background-color: #2563eb; /* Blue-600 */
            transition: all 0.3s ease;
            transform: translateX(-50%);
            border-radius: 99px;
        }
        .tab-btn.active {
            color: #2563eb;
        }
        .tab-btn.active::after {
            width: 80%;
        }
    </style>
</head>
<body class="flex min-h-screen bg-blue-50">

<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

<?php include 'sidebar_admin.php'; ?>

<button class="hamburger bg-amber-500 hover:bg-amber-600 text-gray-900 p-2 rounded-lg transition-colors shadow-md" id="toggleBtn" onclick="toggleSidebar()">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
    </svg>
</button>

<div id="mainContent" class="main flex-1 p-6 lg:p-10 transition-all duration-300">

    <?php if (isset($success_message)): ?>
        <div class="bg-green-50 border-l-4 border-green-500 text-green-800 p-4 rounded-r-lg shadow-sm mb-6 flex items-center animate-pulse-short">
            <i class="fas fa-check-circle mr-3 text-xl"></i>
            <span class="font-medium"><?= htmlspecialchars($success_message) ?></span>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-800 p-4 rounded-r-lg shadow-sm mb-6 flex items-center animate-pulse-short">
            <i class="fas fa-exclamation-circle mr-3 text-xl"></i>
            <span class="font-medium"><?= $error_message ?></span>
        </div>
    <?php endif; ?>

    <div class="max-w-5xl mx-auto">
        
        <div class="relative bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-900 rounded-3xl p-8 sm:p-10 text-white shadow-2xl overflow-hidden mb-8">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white opacity-5 rounded-full blur-3xl transform translate-x-20 -translate-y-20"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-amber-500 opacity-10 rounded-full blur-3xl transform -translate-x-10 translate-y-10"></div>

            <div class="relative z-10 flex flex-col sm:flex-row items-center gap-6">
                <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-full bg-white text-blue-900 flex items-center justify-center shadow-2xl border-4 border-white/20 transform hover:scale-105 transition-transform duration-300">
                    <span class="text-4xl sm:text-5xl font-bold">
                        <?= strtoupper(substr($admin_data['email'] ?? 'A', 0, 1)) ?>
                    </span>
                </div>
                <div class="text-center sm:text-left">
                    <h1 class="text-2xl sm:text-4xl font-bold tracking-tight mb-2"><?= htmlspecialchars($admin_data['email'] ?? 'Administrator') ?></h1>
                    <div class="inline-flex items-center bg-white/10 px-4 py-1.5 rounded-full backdrop-blur-md border border-white/20">
                        <i class="fas fa-user-shield mr-2 text-amber-400"></i>
                        <span class="text-sm font-semibold uppercase tracking-wide text-white"><?= ucfirst($admin_data['role'] ?? 'Admin') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="glass-card overflow-hidden">
            <div class="flex border-b border-gray-200 overflow-x-auto scrollbar-hide bg-white">
                <button class="tab-btn flex-1 py-4 text-center font-semibold text-gray-500 hover:text-blue-600 transition-colors active" onclick="switchTab(event, 'info')">
                    <i class="far fa-id-card mr-2"></i> Informasi
                </button>
                <button class="tab-btn flex-1 py-4 text-center font-semibold text-gray-500 hover:text-blue-600 transition-colors" onclick="switchTab(event, 'edit')">
                    <i class="far fa-edit mr-2"></i> Edit Profil
                </button>
                <button class="tab-btn flex-1 py-4 text-center font-semibold text-gray-500 hover:text-blue-600 transition-colors" onclick="switchTab(event, 'stats')">
                    <i class="fas fa-chart-pie mr-2"></i> Statistik
                </button>
            </div>

            <div class="p-6 sm:p-8 bg-white">
                <div id="info" class="tab-content">
                    <h2 class="text-lg font-bold text-gray-800 mb-6 flex items-center border-b pb-3">
                        <span class="text-blue-600 mr-2"><i class="fas fa-info-circle"></i></span>
                        Detail Akun
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="group p-5 rounded-xl border border-gray-200 hover:border-blue-400 hover:bg-blue-50/30 transition-all duration-300">
                            <label class="text-xs uppercase font-bold text-gray-400 tracking-wider mb-2 block">Email Terdaftar</label>
                            <div class="flex items-center text-gray-800 font-bold text-lg">
                                <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center mr-3">
                                    <i class="far fa-envelope"></i>
                                </div>
                                <?= htmlspecialchars($admin_data['email'] ?? '-') ?>
                            </div>
                        </div>

                        <div class="group p-5 rounded-xl border border-gray-200 hover:border-amber-400 hover:bg-amber-50/30 transition-all duration-300">
                            <label class="text-xs uppercase font-bold text-gray-400 tracking-wider mb-2 block">Hak Akses</label>
                            <div class="flex items-center text-gray-800 font-bold text-lg">
                                <div class="w-10 h-10 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center mr-3">
                                    <i class="fas fa-key"></i>
                                </div>
                                <?= ucfirst($admin_data['role'] ?? '-') ?>
                            </div>
                        </div>

                        <div class="group p-5 rounded-xl border border-gray-200 hover:border-green-400 hover:bg-green-50/30 transition-all duration-300 md:col-span-2">
                            <label class="text-xs uppercase font-bold text-gray-400 tracking-wider mb-2 block">Bergabung Sejak</label>
                            <div class="flex items-center text-gray-800 font-bold text-lg">
                                <div class="w-10 h-10 rounded-lg bg-green-100 text-green-600 flex items-center justify-center mr-3">
                                    <i class="far fa-calendar-alt"></i>
                                </div>
                                <?= isset($admin_data['created_at']) ? date('l, d F Y - H:i', strtotime($admin_data['created_at'])) : '-' ?> WIB
                            </div>
                        </div>
                    </div>
                </div>

                <div id="edit" class="tab-content hidden">
                    <h2 class="text-lg font-bold text-gray-800 mb-6 flex items-center border-b pb-3">
                        <span class="text-amber-500 mr-2"><i class="fas fa-user-edit"></i></span>
                        Pengaturan Akun
                    </h2>
                    <form method="POST" class="space-y-6">
                        <div>
                            <label for="email" class="block text-sm font-bold text-gray-700 mb-2">Alamat Email</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400"><i class="far fa-envelope"></i></span>
                                <input type="email" id="email" name="email" 
                                    class="w-full pl-10 pr-4 py-3 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-700 text-sm shadow-sm" 
                                    value="<?= htmlspecialchars($admin_data['email'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="bg-amber-50 border border-amber-200 p-6 rounded-xl">
                            <h3 class="text-sm font-bold text-amber-800 mb-4 flex items-center uppercase tracking-wide">
                                <i class="fas fa-lock mr-2"></i> Ganti Password
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label for="password_baru" class="block text-xs font-bold text-gray-600 mb-2 uppercase">Password Baru</label>
                                    <input type="password" id="password_baru" name="password_baru" 
                                        class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm transition-all shadow-sm" 
                                        placeholder="Min. 6 karakter">
                                </div>
                                <div>
                                    <label for="konfirmasi_password" class="block text-xs font-bold text-gray-600 mb-2 uppercase">Ulangi Password</label>
                                    <input type="password" id="konfirmasi_password" name="konfirmasi_password" 
                                        class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm transition-all shadow-sm" 
                                        placeholder="Ketik ulang password">
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-4">
                            <button type="button" class="px-5 py-2.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-semibold rounded-lg transition-colors text-sm shadow-sm" onclick="switchTab(event, 'info')">Batal</button>
                            <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5 text-sm">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>

                <div id="stats" class="tab-content hidden">
                    <h2 class="text-lg font-bold text-gray-800 mb-6 flex items-center border-b pb-3">
                        <span class="text-green-600 mr-2"><i class="fas fa-chart-line"></i></span>
                        Ringkasan Data
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="relative bg-gradient-to-br from-blue-600 to-blue-800 p-6 rounded-2xl shadow-lg text-white overflow-hidden group hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                            <div class="relative z-10">
                                <p class="text-blue-100 text-xs font-bold uppercase tracking-wider mb-1">Total Aset</p>
                                <h3 class="text-4xl font-extrabold mb-2"><?= $stats['total_aset'] ?? 0 ?></h3>
                                <span class="inline-block px-2 py-1 bg-white/20 rounded text-xs font-medium backdrop-blur-sm">Lab + Ruang + Fasilitas</span>
                            </div>
                            <i class="fas fa-building absolute -bottom-4 -right-4 text-9xl text-white opacity-10 group-hover:scale-110 transition-transform duration-500"></i>
                        </div>

                        <div class="relative bg-gradient-to-br from-emerald-500 to-emerald-700 p-6 rounded-2xl shadow-lg text-white overflow-hidden group hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                            <div class="relative z-10">
                                <p class="text-emerald-100 text-xs font-bold uppercase tracking-wider mb-1">Pemesanan Bulan Ini</p>
                                <h3 class="text-4xl font-extrabold mb-2"><?= $stats['pemesanan_bulan_ini'] ?? 0 ?></h3>
                                <span class="inline-block px-2 py-1 bg-white/20 rounded text-xs font-medium backdrop-blur-sm">Transaksi Aktif</span>
                            </div>
                            <i class="fas fa-file-invoice-dollar absolute -bottom-4 -right-4 text-9xl text-white opacity-10 group-hover:scale-110 transition-transform duration-500"></i>
                        </div>

                        <div class="relative bg-gradient-to-br from-amber-500 to-orange-600 p-6 rounded-2xl shadow-lg text-white overflow-hidden group hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                            <div class="relative z-10">
                                <p class="text-orange-100 text-xs font-bold uppercase tracking-wider mb-1">Menunggu Persetujuan</p>
                                <h3 class="text-4xl font-extrabold mb-2"><?= $stats['pending_approval'] ?? 0 ?></h3>
                                <span class="inline-block px-2 py-1 bg-white/20 rounded text-xs font-medium backdrop-blur-sm">Butuh Tindakan</span>
                            </div>
                            <i class="fas fa-clock absolute -bottom-4 -right-4 text-9xl text-white opacity-10 group-hover:scale-110 transition-transform duration-500"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle Sidebar Logic
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

// Logic Tab Switcher
function switchTab(event, tabName) {
    document.querySelectorAll('.tab-content').forEach(content => content.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active', 'text-blue-600');
        btn.classList.add('text-gray-500');
    });
    document.getElementById(tabName).classList.remove('hidden');
    const btn = event.currentTarget;
    if (btn) {
        btn.classList.add('active', 'text-blue-600');
        btn.classList.remove('text-gray-500');
    }
}

// Init State
document.addEventListener("DOMContentLoaded", () => {
    const sidebar = document.getElementById("sidebar");
    const main = document.getElementById("mainContent");
    const overlay = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('toggleBtn'); 
    const status = localStorage.getItem('sidebarStatus');
    const is_desktop = window.innerWidth >= 1024;

    if (sidebar) {
        if (status === 'open') {
            if (is_desktop) {
                main.classList.add('lg:ml-60');
                main.classList.remove('lg:ml-16');
                sidebar.classList.add('lg:w-60');
                sidebar.classList.remove('lg:w-16');
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
                toggleBtn.style.left = '5rem'; 
            } else {
                sidebar.classList.remove('translate-x-0');
                sidebar.classList.add('-translate-x-full');
                if (overlay) overlay.classList.add('hidden');
            }
        }
    }

    if (overlay) {
        overlay.addEventListener('click', toggleSidebar);
    }

    // Auto-hide success alert
    const successAlert = document.getElementById('successAlert');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.opacity = '0';
            setTimeout(() => { successAlert.style.display = 'none'; }, 500);
        }, 5000);
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