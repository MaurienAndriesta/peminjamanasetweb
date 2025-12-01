<?php
session_start();
require_once '../koneksi.php';
$db = $koneksi;

// Cek Session Super Admin
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
//     header("Location: ../login.php");
//     exit;
// }

$success_msg = '';
$error_msg = '';

// ==========================================
// 1. HANDLE TAMBAH / EDIT / HAPUS
// ==========================================

// --- TAMBAH USER ---
if (isset($_POST['add_user'])) {
    $email = mysqli_real_escape_string($db, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Enkripsi
    $role = $_POST['role'];

    // Cek email kembar
    $cek = mysqli_query($db, "SELECT id FROM tbl_user WHERE email = '$email'");
    if (mysqli_num_rows($cek) > 0) {
        $error_msg = "Email sudah terdaftar!";
    } else {
        $q = "INSERT INTO tbl_user (email, password, role) VALUES ('$email', '$password', '$role')";
        if (mysqli_query($db, $q)) {
            $success_msg = "User berhasil ditambahkan!";
        } else {
            $error_msg = "Gagal menambah user: " . mysqli_error($db);
        }
    }
}

// --- HAPUS USER ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id != $_SESSION['user_id']) { // Jangan hapus diri sendiri
        mysqli_query($db, "DELETE FROM tbl_user WHERE id = $id");
        $success_msg = "User berhasil dihapus.";
    } else {
        $error_msg = "Anda tidak bisa menghapus akun sendiri.";
    }
}

// --- EDIT PASSWORD (RESET) ---
if (isset($_POST['reset_password'])) {
    $id = (int)$_POST['user_id'];
    $new_pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    
    $q = "UPDATE tbl_user SET password = '$new_pass' WHERE id = $id";
    if (mysqli_query($db, $q)) {
        $success_msg = "Password berhasil direset.";
    } else {
        $error_msg = "Gagal reset password.";
    }
}

// ==========================================
// 2. AMBIL DATA USER
// ==========================================
$users = [];
$q_user = mysqli_query($db, "SELECT * FROM tbl_user ORDER BY role ASC, created_at DESC");
while ($row = mysqli_fetch_assoc($q_user)) {
    $users[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manajemen User - Super Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    body { font-family: 'Poppins', sans-serif; }
    
    /* Sidebar & Layout (Sama seperti Dashboard) */
    .main { transition: margin-left 0.3s; }
    #sidebar { transition: transform 0.3s; }
    
    @media (min-width: 1024px) {
        .main { margin-left: 16rem; } 
    }
    @media (max-width: 1023px) {
        .main { margin-left: 0; }
    }

    /* Card Soft Style */
    .card-soft {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
    }

    /* Modal */
    .modal { opacity: 0; visibility: hidden; transition: all 0.3s ease; }
    .modal.show { opacity: 1; visibility: visible; }
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

        <a href="manajemen_user.php" class="flex items-center px-4 py-3.5 rounded-xl bg-[#6366f1] text-white shadow-lg shadow-indigo-500/30 transition-all hover:scale-[1.02]">
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

    <div class="flex flex-col md:flex-row justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Manajemen User</h1>
            <p class="text-sm text-slate-500">Kelola akun Admin Pengelola dan User Peminjam.</p>
        </div>
        <button onclick="openModal('addModal')" class="mt-4 md:mt-0 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg shadow-md transition-colors flex items-center gap-2">
            <i class="fas fa-plus"></i> Tambah User
        </button>
    </div>

    <?php if ($success_msg): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            <?= $success_msg ?>
        </div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            <?= $error_msg ?>
        </div>
    <?php endif; ?>

    <div class="card-soft overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">No</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Terdaftar</th>
                        <th class="px-6 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php $no=1; foreach($users as $u): ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $no++ ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-8 w-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold mr-3">
                                    <?= strtoupper(substr($u['email'], 0, 1)) ?>
                                </div>
                                <span class="text-sm font-medium text-gray-900"><?= htmlspecialchars($u['email']) ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php 
                                $roleClass = 'bg-gray-100 text-gray-800';
                                if($u['role'] == 'superadmin') $roleClass = 'bg-purple-100 text-purple-800 border border-purple-200';
                                elseif($u['role'] == 'admin') $roleClass = 'bg-pink-100 text-pink-800 border border-pink-200';
                                elseif($u['role'] == 'user') $roleClass = 'bg-blue-100 text-blue-800 border border-blue-200';
                            ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $roleClass ?>">
                                <?= ucfirst($u['role']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= date('d M Y', strtotime($u['created_at'])) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <button onclick="openResetModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['email']) ?>')" class="text-indigo-600 hover:text-indigo-900 mx-2" title="Reset Password">
                                <i class="fas fa-key"></i>
                            </button>
                            <?php if($u['id'] != $_SESSION['user_id']): // Tdk bisa hapus diri sendiri ?>
                                <a href="?delete=<?= $u['id'] ?>" onclick="return confirm('Hapus user ini secara permanen?')" class="text-red-600 hover:text-red-900 mx-2" title="Hapus User">
                                    <i class="fas fa-trash"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<div id="addModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 relative transform transition-all scale-95">
        <button onclick="closeModal('addModal')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">✕</button>
        <h2 class="text-xl font-bold text-slate-800 mb-4">Tambah User Baru</h2>
        
        <form method="POST">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Role / Peran</label>
                <select name="role" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="user">User (Peminjam)</option>
                    <option value="admin">Admin (Pengelola)</option>
                    <option value="superadmin">Super Admin</option>
                </select>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeModal('addModal')" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Batal</button>
                <button type="submit" name="add_user" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div id="resetModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 relative transform transition-all scale-95">
        <button onclick="closeModal('resetModal')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">✕</button>
        <h2 class="text-xl font-bold text-slate-800 mb-2">Reset Password</h2>
        <p class="text-sm text-slate-500 mb-4">Ganti password untuk <span id="resetEmail" class="font-bold"></span></p>
        
        <form method="POST">
            <input type="hidden" name="user_id" id="resetId">
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                <input type="password" name="new_password" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Minimal 6 karakter">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeModal('resetModal')" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Batal</button>
                <button type="submit" name="reset_password" class="px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600">Reset Password</button>
            </div>
        </form>
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

function openModal(id) {
    document.getElementById(id).classList.add('show');
    document.getElementById(id).children[0].classList.remove('scale-95');
    document.getElementById(id).children[0].classList.add('scale-100');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('show');
    document.getElementById(id).children[0].classList.remove('scale-100');
    document.getElementById(id).children[0].classList.add('scale-95');
}

function openResetModal(id, email) {
    document.getElementById('resetId').value = id;
    document.getElementById('resetEmail').innerText = email;
    openModal('resetModal');
}
</script>
</body>
</html>