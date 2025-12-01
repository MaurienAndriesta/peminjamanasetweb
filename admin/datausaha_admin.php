<?php
// =================== KONFIGURASI DATABASE ===================
require_once '../koneksi.php';
$db = $koneksi;

// =================== INISIALISASI ===================
$success_message = null;
$error_message = null;

// --- Pesan sukses dari redirect ---
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success_edit') {
        $success_message = "✅ Data usaha berhasil diperbarui!";
    } elseif ($_GET['status'] === 'success_add') {
        $success_message = "✅ Data usaha berhasil ditambahkan!";
    } elseif ($_GET['status'] === 'success_delete') {
        $success_message = "✅ Data usaha berhasil dihapus!";
    }
}

// =================== HAPUS DATA PERMANEN ===================
if (isset($_GET['action'], $_GET['id'], $_GET['permanent']) 
    && $_GET['action'] === 'delete' && $_GET['permanent'] == 1) {

    $id = (int)$_GET['id'];
    $nama_kolom_gambar = 'gambar'; 
    $gambar_to_delete = null;

    $stmt_file = $db->prepare("SELECT $nama_kolom_gambar FROM tbl_usaha WHERE id = ?");
    if ($stmt_file) {
        $stmt_file->bind_param("i", $id);
        $stmt_file->execute();
        $result_file = $stmt_file->get_result();
        if ($row_file = $result_file->fetch_assoc()) {
            $gambar_to_delete = $row_file[$nama_kolom_gambar];
        }
        $stmt_file->close();
    }

    try {
        $stmt_delete = $db->prepare("DELETE FROM tbl_usaha WHERE id = ?");
        $stmt_delete->bind_param("i", $id);
        
        if ($stmt_delete->execute()) {
            if (!empty($gambar_to_delete)) {
                $path_gambar = 'assets/images/' . $gambar_to_delete;
                if (file_exists($path_gambar)) {
                    @unlink($path_gambar);
                }
            }
            header("Location: datausaha_admin.php?status=success_delete");
            exit;
        } else {
            throw new Exception($stmt_delete->error);
        }
        $stmt_delete->close();

    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
            $error_message = "Gagal menghapus! Data usaha ini sedang digunakan dalam riwayat peminjaman.";
        } else {
            $error_message = "Gagal menghapus data usaha: " . $e->getMessage();
        }
    }
}

// =================== PAGINATION & SEARCH ===================
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = "WHERE 1=1";
if (!empty($search)) {
    $search_safe = mysqli_real_escape_string($db, $search);
    $where .= " AND (nama LIKE '%$search_safe%' OR kapasitas LIKE '%$search_safe%')";
}

$total_records = 0;
$sql_total = "SELECT COUNT(*) AS total FROM tbl_usaha $where";
$result_total = mysqli_query($db, $sql_total);
if ($result_total && mysqli_num_rows($result_total) > 0) {
    $row_total = mysqli_fetch_assoc($result_total);
    $total_records = (int)$row_total['total'];
}
$total_pages = ceil($total_records / $limit);

$usaha_data = [];
$sql_data = "SELECT * FROM tbl_usaha 
             $where 
             ORDER BY created_at DESC 
             LIMIT $limit OFFSET $offset";
$result_data = mysqli_query($db, $sql_data);
if ($result_data && mysqli_num_rows($result_data) > 0) {
    while ($row = mysqli_fetch_assoc($result_data)) {
        $usaha_data[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Usaha</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
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

.content-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    border-radius: 1.5rem;
}

.animate-fade-in { animation: fadeIn 0.6s cubic-bezier(0.4, 0, 0.2, 1); }
@keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
</style>
</head>
<body class="bg-blue-100 min-h-screen">

<div id="sidebarOverlay" class="fixed inset-0 bg-black/60 hidden lg:hidden z-40" onclick="toggleSidebar()"></div>

<?php include 'sidebar_admin.php'; ?>

<div id="mainContent" class="main flex-1 p-6 lg:p-10 min-h-screen relative z-10">
    
    <!-- HEADER PANEL (SEARCH & ADD BUTTON) -->
    <div class="header-panel p-4 mb-10 sticky top-4 z-50 flex flex-col md:flex-row justify-between items-center gap-4 transition-all duration-300">
        <div class="flex items-center gap-4 w-full md:w-auto">
            <button id="toggleBtn" onclick="toggleSidebar()" class="bg-amber-500 text-white p-2.5 rounded-xl shadow-lg hover:bg-amber-600 active:scale-95 transition-all">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>

            <form method="get" class="relative flex-1 md:w-80">
                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-blue-500">
                    <i class="fas fa-search text-lg"></i>
                </span>
                <input type="text" name="search" placeholder="Cari Usaha..." value="<?= htmlspecialchars($search) ?>" 
                    class="w-full pl-12 pr-5 py-2.5 bg-white border border-gray-200 rounded-full text-sm font-medium focus:ring-4 focus:ring-blue-200/50 transition-all outline-none shadow-sm placeholder-gray-400">
                <?php if (!empty($search)): ?>
                    <button type="button" onclick="window.location='datausaha_admin.php'" 
                        class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                <?php endif; ?>
            </form>
        </div>

        <a href="tambahdatausaha_admin.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-xl shadow-md transition-all flex items-center gap-2">
            <i class="fas fa-plus"></i> Tambah Data
        </a>
    </div>

    <!-- ALERTS -->
    <?php if ($success_message): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl mb-6 shadow-sm animate-fade-in flex items-center gap-3" id="successAlert">
            <i class="fas fa-check-circle text-xl"></i>
            <span><?= htmlspecialchars($success_message) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 shadow-sm animate-fade-in flex items-center gap-3">
            <i class="fas fa-exclamation-circle text-xl"></i>
            <span><?= htmlspecialchars($error_message) ?></span>
        </div>
    <?php endif; ?>

    <!-- PAGE TITLE -->
    <div class="mb-6 animate-fade-in">
        <h1 class="text-3xl font-extrabold text-slate-800">Data Usaha</h1>
        <p class="text-slate-500 mt-1">Kelola data usaha kampus.</p>
    </div>

    <!-- CONTENT CARD (TABLE & LIST) -->
    <div class="content-card p-6 animate-fade-in">
        
        <!-- Desktop Table -->
        <div class="hidden lg:block overflow-x-auto">
            <?php if (empty($usaha_data)): ?>
                <div class="text-center p-8">
                    <p class="text-gray-600">📂 Belum ada data usaha ditemukan.</p>
                </div>
            <?php else: ?>
                <table class="min-w-full border-collapse border border-gray-200 text-sm">
                    <thead class="bg-gray-800 text-white">
                        <tr>
                            <th class="px-3 py-3 border-b border-gray-300 text-left text-xs font-bold uppercase tracking-wider w-10">No.</th>
                            <th class="px-3 py-3 border-b border-gray-300 text-left text-xs font-bold uppercase tracking-wider">Nama Usaha</th>
                            <th class="px-3 py-3 border-b border-gray-300 text-left text-xs font-bold uppercase tracking-wider">Kapasitas</th>
                            <th class="px-3 py-3 border-b border-gray-300 text-left text-xs font-bold uppercase tracking-wider">Tarif</th>
                            <th class="px-3 py-3 border-b border-gray-300 text-center text-xs font-bold uppercase tracking-wider w-48">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php $no = $offset + 1; foreach ($usaha_data as $u): ?>
                        <?php
                            $status_raw = strtolower(str_replace(['_', ' '], '', $u['status'] ?? ''));
                            $is_unavailable = ($status_raw === 'tidaktersedia');
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors <?= $is_unavailable ? 'bg-gray-100' : '' ?>">
                            <td class="px-3 py-3 whitespace-nowrap text-sm font-bold text-gray-700 text-center"><?= $no++ ?>.</td>
                            <td class="px-3 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?= htmlspecialchars($u['nama']) ?>
                                <?php if($is_unavailable): ?>
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-red-100 text-red-800 border border-red-200">
                                        Tidak Tersedia
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-sm">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    👥 <?= htmlspecialchars($u['kapasitas']) ?>
                                </span>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-sm font-medium text-green-700">
                                Rp <?= number_format($u['tarif_eksternal'] ?? 0, 0, ',', '.') ?>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-center text-sm font-medium">
                                <div class="flex justify-center gap-1">
                                    <a href="editusaha_admin.php?id=<?= $u['id'] ?>" 
                                       class="inline-flex items-center px-2 py-1 bg-green-500 hover:bg-green-600 text-white text-xs font-bold rounded shadow-sm">
                                        ✏️ Edit
                                    </a>
                                    <a href="detailusaha_admin.php?id=<?= $u['id'] ?>" 
                                       class="inline-flex items-center px-2 py-1 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded shadow-sm">
                                        👁️ Detail
                                    </a>
                                    <button onclick="confirmDelete(<?= $u['id'] ?>)" 
                                            class="inline-flex items-center px-2 py-1 bg-red-500 hover:bg-red-600 text-white text-xs font-bold rounded shadow-sm">
                                        🗑️ Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Mobile View (Cards) -->
        <div class="lg:hidden space-y-4 mt-2">
            <?php if (empty($usaha_data)): ?>
                <div class="text-center p-8">
                    <p class="text-gray-600">📂 Belum ada data usaha.</p>
                </div>
            <?php else: ?>
                <?php $no = $offset + 1; foreach ($usaha_data as $u): ?>
                    <?php
                        $status_raw = strtolower(str_replace(['_', ' '], '', $u['status'] ?? ''));
                        $is_unavailable = ($status_raw === 'tidaktersedia');
                    ?>
                    <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm <?= $is_unavailable ? 'bg-gray-100' : '' ?>">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex-1">
                                <div class="text-xs text-gray-500 mb-1">No. <?= $no++ ?></div>
                                <h3 class="font-bold text-base text-gray-900 mb-2">
                                    <?= htmlspecialchars($u['nama']) ?>
                                    <?php if($is_unavailable): ?>
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 border border-red-200">
                                            Tidak Tersedia
                                        </span>
                                    <?php endif; ?>
                                </h3>
                            </div>
                        </div>
                        <div class="space-y-2 mb-3">
                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    👥 <?= htmlspecialchars($u['kapasitas']) ?>
                                </span>
                            </div>
                            <div class="text-sm text-gray-600">
                                <p>Tarif: <span class="font-medium text-green-700">Rp <?= number_format($u['tarif_eksternal'], 0, ',', '.') ?></span></p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <a href="editusaha_admin.php?id=<?= $u['id'] ?>" class="flex-1 text-center bg-green-500 text-white px-3 py-2 rounded-md text-xs font-bold shadow-sm">Edit</a>
                            <a href="detailusaha_admin.php?id=<?= $u['id'] ?>" class="flex-1 text-center bg-amber-500 text-white px-3 py-2 rounded-md text-xs font-bold shadow-sm">Detail</a>
                            <button onclick="confirmDelete(<?= $u['id'] ?>)" class="flex-1 bg-red-500 text-white px-3 py-2 rounded-md text-xs font-bold shadow-sm">Hapus</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="flex justify-center gap-2 mt-4 text-sm">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1 bg-gray-700 text-white rounded-md">‹</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" 
                   class="px-3 py-1 rounded-md <?= $i == $page ? 'bg-amber-500 text-gray-900 font-bold' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                   <?= $i ?>
                </a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1 bg-gray-700 text-white rounded-md">›</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Fungsi Sidebar (Sama dengan datafasilitas)
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

// Konfirmasi Hapus
function confirmDelete(id) {
    Swal.fire({
        title: 'Hapus permanen?', 
        text: 'Data akan dihapus permanen!', 
        icon: 'warning',
        showCancelButton: true, 
        confirmButtonColor: '#d9534f', 
        cancelButtonColor: '#f59e0b',
        confirmButtonText: 'Hapus', 
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'datausaha_admin.php?action=delete&id=' + id + '&permanent=1';
        }
    });
}

// Event Listeners
document.addEventListener("DOMContentLoaded", () => {
    const sidebar = document.getElementById("sidebar");
    const status = localStorage.getItem('sidebarStatus');
    
    // Restore sidebar state
    if (status === 'open' && window.innerWidth >= 1024) {
        sidebar.classList.add('lg:w-60'); 
        sidebar.classList.remove('lg:w-16');
        document.querySelector('.main').classList.add('lg:ml-60'); 
        document.querySelector('.main').classList.remove('lg:ml-16');
    }

    // Auto-hide alerts
    const successAlert = document.getElementById('successAlert');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.transition = 'opacity 0.5s';
            successAlert.style.opacity = '0';
            setTimeout(() => { successAlert.remove(); }, 500);
        }, 5000);
    }
});

// Resize Handler
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