<?php
// Include mock session (hapus ini setelah sistem login terintegrasi)
require_once 'mock_admin_session.php';

// Include database configuration
require_once 'config/database.php';

// Initialize database connection
$db = new Database();

// Get admin ID from session
$admin_id = $_SESSION['admin_id'] ?? 1;

// Default admin data (dari mock)
$admin_data = getAdminData();

// Inisialisasi pesan dan tab
$success_message = null;
$error_message = null;
$tab_active = 'info'; // Default tab

// --- LOGIC PENGAMBILAN DATA STATISTIK ---
$stats = [
    'total_lab' => 0,
    'pemesanan_bulan_ini' => 0,
    'pending_approval' => 0,
];

try {
    // 1. Total Laboratorium
    $db->query("SELECT COUNT(*) as total FROM laboratorium WHERE status = 'aktif'");
    $stats['total_lab'] = $db->single()['total'] ?? 0;

    // 2. Pemesanan Bulan Ini
    $db->query("SELECT COUNT(*) as total FROM pemesanan WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW()) AND status != 'ditolak'");
    $stats['pemesanan_bulan_ini'] = $db->single()['total'] ?? 0;

    // 3. Pending Approval
    $db->query("SELECT COUNT(*) as total FROM pemesanan WHERE status = 'pending'");
    $stats['pending_approval'] = $db->single()['total'] ?? 0;
    
    // Ambil data admin dari DB
    $db->query("SELECT * FROM users WHERE id = :id AND role = 'admin'");
    $db->bind(':id', $admin_id);
    $result = $db->single();
    
    if ($result) {
        $admin_data = $result;
    }
} catch (Exception $e) {
    error_log("Database error in profile: " . $e->getMessage());
}
// --- AKHIR LOGIC PENGAMBILAN DATA STATISTIK ---

// Handle form submission (diletakkan di bawah fetch data untuk menggunakan data lama sebagai fallback)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    $instansi = trim($_POST['instansi'] ?? '');
    $password_lama = $_POST['password_lama'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';
    
    $errors = [];
    
    // Validasi
    if (empty($nama)) $errors[] = "Nama harus diisi";
    if (empty($email)) $errors[] = "Email harus diisi";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Format email tidak valid";
    
    // Validasi password jika diisi
    if (!empty($password_baru)) {
        $tab_active = 'edit';
        if (empty($password_lama)) {
            $errors[] = "Password lama harus diisi";
        }
        if (strlen($password_baru) < 6) {
            $errors[] = "Password baru minimal 6 karakter";
        }
        if ($password_baru !== $konfirmasi_password) {
            $errors[] = "Konfirmasi password tidak cocok";
        }
    }
    
    if (empty($errors)) {
        try {
            // Update basic info
            $db->query("UPDATE users SET 
                         nama = :nama, 
                         email = :email, 
                         no_telepon = :no_telepon, 
                         instansi = :instansi 
                         WHERE id = :id");
            $db->bind(':nama', $nama);
            $db->bind(':email', $email);
            $db->bind(':no_telepon', $no_telepon);
            $db->bind(':instansi', $instansi);
            $db->bind(':id', $admin_id);
            $db->execute();
            
            // Update password if provided
            if (!empty($password_baru)) {
                $db->query("UPDATE users SET password = :password WHERE id = :id");
                $db->bind(':password', md5($password_baru));
                $db->bind(':id', $admin_id);
                $db->execute();
            }
            
            $success_message = "Profil berhasil diperbarui";
            $tab_active = 'info'; 

            // Refresh data (Update mock/session data)
            $admin_data['nama'] = $nama;
            $admin_data['email'] = $email;
            $admin_data['no_telepon'] = $no_telepon;
            $admin_data['instansi'] = $instansi;
            
        } catch (Exception $e) {
            $error_message = "Gagal memperbarui profil: " . $e->getMessage();
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
    <title>Profil Admin - Admin Pengelola</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .text-dark-accent { color: #202938; }
        
        @media (min-width: 1024px) {
            .main { margin-left: 4rem; }
            .main.lg\:ml-60 { margin-left: 15rem; }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .tab-content {
            animation: fadeIn 0.3s ease-in-out;
        }
    </style>
</head>
<body class="bg-blue-100 flex min-h-screen">

<?php include 'sidebar_admin.php'; ?>

<div id="mainContent" class="main flex-1 p-5 transition-all duration-300 lg:ml-16">
    <!-- Header -->
    <div class="header flex justify-start items-center mb-6">
        <button class="bg-amber-500 hover:bg-amber-600 text-gray-900 p-2 rounded-lg transition-colors" onclick="toggleSidebar()">☰</button>
    </div>

    <!-- Messages -->
    <?php if (isset($success_message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4 shadow-sm">
            ✓ <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4 shadow-sm">
            ✗ <?= $error_message ?>
        </div>
    <?php endif; ?>

    <!-- Profile Container -->
    <div class="max-w-5xl mx-auto">
        <!-- Profile Header -->
        <div class="bg-gradient-to-br from-purple-600 via-purple-500 to-indigo-600 p-10 rounded-t-2xl text-center text-white shadow-lg">
            <div class="w-32 h-32 rounded-full bg-white text-purple-600 text-5xl font-bold flex items-center justify-center mx-auto mb-4 border-4 border-white/30 shadow-xl">
                <?= strtoupper(substr($admin_data['nama'] ?? 'AD', 0, 2)) ?>
            </div>
            <h1 class="text-3xl font-bold mb-2"><?= htmlspecialchars($admin_data['nama'] ?? 'Administrator') ?></h1>
            <p class="text-purple-100 text-sm"><?= htmlspecialchars($admin_data['email'] ?? 'admin@itpln.ac.id') ?> • Administrator</p>
        </div>

        <!-- Profile Content -->
        <div class="bg-white rounded-b-2xl shadow-lg p-8">
            <!-- Tabs -->
            <div class="flex border-b-2 border-gray-200 mb-8">
                <button class="tab-btn px-6 py-3 font-semibold text-gray-600 hover:text-purple-600 transition-colors relative" 
                        onclick="switchTab(event, 'info')" data-active="true">
                    Informasi Profil
                    <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-purple-600"></span>
                </button>
                <button class="tab-btn px-6 py-3 font-semibold text-gray-600 hover:text-purple-600 transition-colors relative" 
                        onclick="switchTab(event, 'edit')">
                    Edit Profil
                </button>
                <button class="tab-btn px-6 py-3 font-semibold text-gray-600 hover:text-purple-600 transition-colors relative" 
                        onclick="switchTab(event, 'stats')">
                    Statistik
                </button>
            </div>

            <!-- Tab: Informasi Profil -->
            <div id="info" class="tab-content">
                <h2 class="text-2xl font-bold text-dark-accent mb-6">Informasi Akun</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="bg-gradient-to-r from-blue-50 to-blue-100 p-5 rounded-xl border-l-4 border-blue-500">
                        <label class="text-xs uppercase text-gray-600 font-semibold mb-2 block">Nama Lengkap</label>
                        <div class="text-gray-900 font-medium text-lg"><?= htmlspecialchars($admin_data['nama'] ?? '-') ?></div>
                    </div>
                    <div class="bg-gradient-to-r from-green-50 to-green-100 p-5 rounded-xl border-l-4 border-green-500">
                        <label class="text-xs uppercase text-gray-600 font-semibold mb-2 block">Email</label>
                        <div class="text-gray-900 font-medium text-lg"><?= htmlspecialchars($admin_data['email'] ?? '-') ?></div>
                    </div>
                    <div class="bg-gradient-to-r from-purple-50 to-purple-100 p-5 rounded-xl border-l-4 border-purple-500">
                        <label class="text-xs uppercase text-gray-600 font-semibold mb-2 block">No. Telepon</label>
                        <div class="text-gray-900 font-medium text-lg"><?= htmlspecialchars($admin_data['no_telepon'] ?? '-') ?></div>
                    </div>
                    <div class="bg-gradient-to-r from-pink-50 to-pink-100 p-5 rounded-xl border-l-4 border-pink-500">
                        <label class="text-xs uppercase text-gray-600 font-semibold mb-2 block">Instansi</label>
                        <div class="text-gray-900 font-medium text-lg"><?= htmlspecialchars($admin_data['instansi'] ?? '-') ?></div>
                    </div>
                    <div class="bg-gradient-to-r from-amber-50 to-amber-100 p-5 rounded-xl border-l-4 border-amber-500">
                        <label class="text-xs uppercase text-gray-600 font-semibold mb-2 block">Role</label>
                        <div class="text-gray-900 font-medium text-lg">Administrator</div>
                    </div>
                    <div class="bg-gradient-to-r from-indigo-50 to-indigo-100 p-5 rounded-xl border-l-4 border-indigo-500">
                        <label class="text-xs uppercase text-gray-600 font-semibold mb-2 block">Terdaftar Sejak</label>
                        <div class="text-gray-900 font-medium text-lg"><?= date('d F Y', strtotime($admin_data['created_at'] ?? date('Y-m-d'))) ?></div>
                    </div>
                </div>
            </div>

            <!-- Tab: Edit Profil -->
            <div id="edit" class="tab-content hidden">
                <h2 class="text-2xl font-bold text-dark-accent mb-6">Edit Profil</h2>
                <form method="POST">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                        <div>
                            <label for="nama" class="block font-semibold text-gray-700 mb-2">
                                Nama Lengkap <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="nama" name="nama" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-gray-50 transition-all" 
                                   value="<?= htmlspecialchars($admin_data['nama'] ?? '') ?>" required>
                        </div>
                        <div>
                            <label for="email" class="block font-semibold text-gray-700 mb-2">
                                Email <span class="text-red-500">*</span>
                            </label>
                            <input type="email" id="email" name="email" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-gray-50 transition-all" 
                                   value="<?= htmlspecialchars($admin_data['email'] ?? '') ?>" required>
                        </div>
                        <div>
                            <label for="no_telepon" class="block font-semibold text-gray-700 mb-2">No. Telepon</label>
                            <input type="text" id="no_telepon" name="no_telepon" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-gray-50 transition-all" 
                                   value="<?= htmlspecialchars($admin_data['no_telepon'] ?? '') ?>">
                        </div>
                        <div>
                            <label for="instansi" class="block font-semibold text-gray-700 mb-2">Instansi</label>
                            <input type="text" id="instansi" name="instansi" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-gray-50 transition-all" 
                                   value="<?= htmlspecialchars($admin_data['instansi'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Password Section -->
                    <div class="bg-amber-50 border-l-4 border-amber-500 p-6 rounded-lg mb-6">
                        <h3 class="text-lg font-bold text-amber-800 mb-4 flex items-center gap-2">
                            <span>🔒</span> Ubah Password (Opsional)
                        </h3>
                        <div class="mb-4">
                            <label for="password_lama" class="block font-semibold text-gray-700 mb-2">Password Lama</label>
                            <input type="password" id="password_lama" name="password_lama" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-white transition-all" 
                                   placeholder="Masukkan password lama">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label for="password_baru" class="block font-semibold text-gray-700 mb-2">Password Baru</label>
                                <input type="password" id="password_baru" name="password_baru" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-white transition-all" 
                                       placeholder="Minimal 6 karakter">
                            </div>
                            <div>
                                <label for="konfirmasi_password" class="block font-semibold text-gray-700 mb-2">Konfirmasi Password</label>
                                <input type="password" id="konfirmasi_password" name="konfirmasi_password" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-white transition-all" 
                                       placeholder="Ulangi password baru">
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end gap-4 pt-6 border-t border-gray-200">
                        <button type="button" class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg transition-colors" 
                                onclick="switchTab(event, 'info')">
                            Batal
                        </button>
                        <button type="submit" class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition-all hover:shadow-lg">
                            💾 Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tab: Statistik -->
            <div id="stats" class="tab-content hidden">
                <h2 class="text-2xl font-bold text-dark-accent mb-6">Statistik Aktivitas</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-6 rounded-xl shadow-lg text-center transform hover:scale-105 transition-transform">
                        <div class="text-5xl font-bold mb-2"><?= $stats['total_lab'] ?? 0 ?></div>
                        <div class="text-blue-100 text-sm">Total Laboratorium</div>
                    </div>
                    <div class="bg-gradient-to-br from-green-500 to-green-600 text-white p-6 rounded-xl shadow-lg text-center transform hover:scale-105 transition-transform">
                        <div class="text-5xl font-bold mb-2"><?= $stats['pemesanan_bulan_ini'] ?? 0 ?></div>
                        <div class="text-green-100 text-sm">Pemesanan Bulan Ini</div>
                    </div>
                    <div class="bg-gradient-to-br from-amber-500 to-amber-600 text-white p-6 rounded-xl shadow-lg text-center transform hover:scale-105 transition-transform">
                        <div class="text-5xl font-bold mb-2"><?= $stats['pending_approval'] ?? 0 ?></div>
                        <div class="text-amber-100 text-sm">Pending Approval</div>
                    </div>
                </div>
                <div class="bg-gradient-to-r from-indigo-50 to-indigo-100 p-6 rounded-xl border-l-4 border-indigo-500">
                    <label class="text-xs uppercase text-gray-600 font-semibold mb-2 block">Login Terakhir</label>
                    <div class="text-gray-900 font-medium text-lg"><?= date('d F Y, H:i') ?> WIB</div>
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

function switchTab(event, tabName) {
    // Hide all tabs
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(content => content.classList.add('hidden'));
    
    // Remove active state from all buttons
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(btn => {
        btn.classList.remove('text-purple-600');
        btn.classList.add('text-gray-600');
        const indicator = btn.querySelector('span');
        if (indicator) indicator.remove();
    });
    
    // Show selected tab
    document.getElementById(tabName).classList.remove('hidden');
    
    // Add active state to clicked button
    event.currentTarget.classList.remove('text-gray-600');
    event.currentTarget.classList.add('text-purple-600');
    
    // Add indicator
    const indicator = document.createElement('span');
    indicator.className = 'absolute bottom-0 left-0 right-0 h-0.5 bg-purple-600';
    event.currentTarget.appendChild(indicator);
}

// Initialize sidebar state and active tab
document.addEventListener("DOMContentLoaded", function() {
    const sidebar = document.getElementById("sidebar");
    const main = document.getElementById("mainContent");
    const status = localStorage.getItem('sidebarStatus');
    const is_desktop = window.innerWidth >= 1024;
    const activeTabName = '<?= $tab_active ?>';
    
    // Sidebar initialization
    if (status === 'open') {
        if (is_desktop) {
            main.classList.add('lg:ml-60');
            main.classList.remove('lg:ml-16');
            sidebar.classList.add('lg:w-60');
            sidebar.classList.remove('lg:w-16');
        } else {
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
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
        }
    }
    
    // Tab initialization
    const activeTabContent = document.getElementById(activeTabName);
    const activeTabButton = document.querySelector(`.tab-btn[onclick*="'${activeTabName}'"]`);

    if (activeTabContent) {
        document.querySelectorAll('.tab-content').forEach(content => content.classList.add('hidden'));
        activeTabContent.classList.remove('hidden');
    }
    
    if (activeTabButton) {
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('text-purple-600');
            btn.classList.add('text-gray-600');
            const ind = btn.querySelector('span');
            if (ind) ind.remove();
        });
        
        activeTabButton.classList.remove('text-gray-600');
        activeTabButton.classList.add('text-purple-600');
        const indicator = document.createElement('span');
        indicator.className = 'absolute bottom-0 left-0 right-0 h-0.5 bg-purple-600';
        activeTabButton.appendChild(indicator);
    }
});
</script>

</body>
</html>