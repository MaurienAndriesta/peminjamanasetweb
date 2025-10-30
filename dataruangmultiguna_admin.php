<?php
// Include database configuration
require_once 'config/database.php';

// Initialize database connection
$db = new Database();

// Initialize messages
$success_message = null;
$error_message = null;

// --- LOGIC PENANGANAN PESAN SUKSES (dari redirect) ---
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success_edit') {
        $success_message = "✅ Data ruangan berhasil diperbarui!";
    } elseif ($_GET['status'] === 'success_add') {
        $success_message = "✅ Data ruangan berhasil ditambahkan!";
    } elseif ($_GET['status'] === 'success_delete') {
        $success_message = "✅ Data ruangan berhasil dihapus (non-aktif)!";
    }
}

// Handle Delete Action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $db->query("UPDATE ruangan_multiguna SET status = 'tidak_aktif' WHERE id = :id"); 
        $db->bind(':id', $id);
        if ($db->execute()) {
            header('Location: dataruangmultiguna_admin.php?status=success_delete');
            exit;
        } else {
            $error_message = "Gagal menghapus data ruangan";
        }
    } catch (Exception $e) {
        $error_message = "Gagal menghapus data: " . $e->getMessage();
    }
}

// --- PAGINATION & SEARCH LOGIC ---
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_query = '';
$search_param = '';

if (!empty($search)) {
    $search_query = " AND (nama LIKE :search OR kapasitas LIKE :search OR lokasi LIKE :search)";
    $search_param = '%' . $search . '%';
}

$ruangan_data = [];
$total_records = 0;

try {
    // Hitung Total Records
    $db->query("SELECT COUNT(*) as total FROM ruangan_multiguna WHERE status = 'aktif'" . $search_query);
    if (!empty($search)) {
        $db->bind(':search', $search_param);
    }
    $total_records = $db->single()['total'];
    $total_pages = ceil($total_records / $limit);

    // Ambil Data dengan Limit & Offset
    $db->query("SELECT id, nama, kapasitas, lokasi, tarif_internal, tarif_eksternal, created_at 
                FROM ruangan_multiguna 
                WHERE status = 'aktif'" . $search_query . " 
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset");

    if (!empty($search)) {
        $db->bind(':search', $search_param);
    }
    $db->bind(':limit', $limit, PDO::PARAM_INT);
    $db->bind(':offset', $offset, PDO::PARAM_INT);
    
    $ruangan_data = $db->resultSet();

} catch (Exception $e) {
    $error_message = "Terjadi kesalahan database: " . $e->getMessage();
    $ruangan_data = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Ruangan Multiguna</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
        }
        .text-dark-accent { color: #202938; }

        /* Responsive Sidebar - Content menyesuaikan lebar sidebar */
        @media (min-width: 1024px) {
            .main { margin-left: 4rem; }
            .main.lg\:ml-60 { margin-left: 15rem; }
        }

        /* Mobile: Sidebar sebagai overlay, content tidak bergeser */
        @media (max-width: 1023px) {
            .main { margin-left: 0 !important; }
        }

        /* Toggle button selalu terlihat di atas sidebar */
        #toggleBtn {
            position: relative;
            z-index: 51 !important;
        }

        /* SweetAlert Custom Colors */
        .swal2-confirm { background-color: #d9534f !important; }
        .swal2-cancel { background-color: #f59e0b !important; }
    </style>
</head>
<body class="bg-blue-100 min-h-screen">

<?php include 'sidebar_admin.php'; ?>

<!-- Overlay untuk mobile ketika sidebar terbuka -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

<div id="mainContent" class="main flex-1 p-3 sm:p-5 transition-all duration-300 lg:ml-16 min-h-screen">
    
    <!-- Header dengan Hamburger (Fixed Position) -->
    <div class="flex justify-between items-center mb-4 sm:mb-6">
        <button id="toggleBtn" class="bg-amber-500 hover:bg-amber-600 text-gray-900 p-2 rounded-lg transition-colors shadow-md relative z-50" onclick="toggleSidebar()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        
        <!-- Breadcrumb untuk mobile -->
        <div class="text-sm text-gray-600 hidden sm:block">
            <span class="font-semibold">Data Ruangan Multiguna</span>
        </div>
    </div>

    <!-- Messages -->
    <?php if ($success_message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-3 sm:px-4 py-3 rounded-lg mb-4 shadow-md transition-opacity duration-500 text-sm" role="alert" id="successAlert">
            <span class="block"><?= htmlspecialchars($success_message) ?></span>
        </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-3 sm:px-4 py-3 rounded-lg mb-4 shadow-md text-sm" role="alert">
            <span class="block">❌ <?= htmlspecialchars($error_message) ?></span>
        </div>
    <?php endif; ?>

    <!-- Main Content Card -->
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow-lg">
        <!-- Title -->
        <div class="mb-4 sm:mb-6">
            <h1 class="text-xl sm:text-2xl font-bold text-dark-accent flex items-center gap-2">
                <span class="text-2xl sm:text-3xl">📋</span>
                <span>Data Ruangan Multiguna</span>
            </h1>
            <p class="text-xs sm:text-sm text-gray-600 mt-1">Kelola data ruangan multiguna kampus</p>
        </div>
        
        <!-- Action Bar -->
        <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center mb-4 sm:mb-6 gap-3">
            <a href="tambahdatamultiguna_admin.php" 
               class="bg-amber-500 hover:bg-amber-600 text-gray-900 font-semibold px-4 sm:px-6 py-2.5 sm:py-3 rounded-lg shadow-md transition-all hover:shadow-lg text-center text-sm sm:text-base flex items-center justify-center gap-2">
                <span class="text-lg">➕</span>
                <span>Tambah Data</span>
            </a>
            
            <form method="GET" class="relative w-full sm:max-w-md">
                <input type="text" name="search" 
                       class="w-full px-4 sm:px-5 py-2.5 sm:py-3 pr-10 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all text-sm" 
                       placeholder="🔍 Cari ruangan..." 
                       value="<?= htmlspecialchars($search) ?>" 
                       id="searchInput">
                <?php if (!empty($search)): ?>
                    <button type="button" 
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 text-2xl font-bold" 
                            id="searchClearBtn">&times;</button>
                <?php endif; ?>
                <input type="hidden" name="page" value="1">
            </form>
        </div>

        <!-- Table Container -->
        <div class="bg-white rounded-xl overflow-hidden shadow-md border border-gray-200">
            <?php if (empty($ruangan_data)): ?>
                <!-- Empty State -->
                <div class="text-center p-8 sm:p-16">
                    <div class="text-5xl sm:text-6xl mb-3 sm:mb-4">📂</div>
                    <?php if (!empty($search)): ?>
                        <h3 class="text-lg sm:text-xl font-bold text-gray-700 mb-2">Tidak ada ruangan ditemukan</h3>
                        <p class="text-sm sm:text-base text-gray-600 mb-4">Pencarian "<strong><?= htmlspecialchars($search) ?></strong>" tidak menghasilkan data</p>
                        <a href="dataruangmultiguna_admin.php" 
                           class="bg-amber-500 hover:bg-amber-600 text-gray-900 font-semibold px-4 sm:px-6 py-2 sm:py-3 rounded-lg inline-block transition-colors shadow-md text-sm sm:text-base">
                            Tampilkan Semua Data
                        </a>
                    <?php else: ?>
                        <h3 class="text-lg sm:text-xl font-bold text-gray-700 mb-2">Belum ada data ruangan</h3>
                        <p class="text-sm sm:text-base text-gray-600 mb-4">Tambahkan ruangan multiguna pertama Anda</p>
                        <a href="tambahdatamultiguna_admin.php" 
                           class="bg-amber-500 hover:bg-amber-600 text-gray-900 font-semibold px-4 sm:px-6 py-2 sm:py-3 rounded-lg inline-block transition-colors shadow-md text-sm sm:text-base">
                            ➕ Tambah Ruangan
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Desktop Table View -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-700 text-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">No.</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Nama Ruangan</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Kapasitas</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Lokasi</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Tarif Internal</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Tarif Eksternal</th>
                                <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php $no=$offset+1; foreach($ruangan_data as $r): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-gray-700"><?= $no ?>.</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($r['nama']) ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                     <?= htmlspecialchars($r['kapasitas']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                                    <?= htmlspecialchars($r['lokasi']) ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-green-600">
                                    Rp <?= number_format($r['tarif_internal']??0,0,',','.') ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-blue-600">
                                    Rp <?= number_format($r['tarif_eksternal']??0,0,',','.') ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    <div class="flex gap-2 justify-center">
                                        <a href="editruangmultiguna_admin.php?id=<?= $r['id'] ?>" 
                                           class="inline-flex items-center px-3 py-1.5 text-xs font-bold rounded-lg bg-green-500 hover:bg-green-600 text-white transition-all shadow-sm">
                                            ✏️ Edit
                                        </a>
                                        <a href="detailmultiguna_admin.php?id=<?= $r['id'] ?>" 
                                           class="inline-flex items-center px-3 py-1.5 text-xs font-bold rounded-lg bg-amber-500 hover:bg-amber-600 text-white transition-all shadow-sm">
                                            👁️ Detail
                                        </a>
                                        <button onclick="confirmDelete(<?= $r['id'] ?>)" 
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-bold rounded-lg bg-red-500 hover:bg-red-600 text-white transition-all shadow-sm">
                                            🗑️ Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php $no++; endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div class="lg:hidden space-y-4 p-3">
                    <?php $no=$offset+1; foreach($ruangan_data as $r): ?>
                    <div class="bg-white border-2 border-gray-200 rounded-xl p-4 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex-1">
                                <div class="text-xs text-gray-500 mb-1">No. <?= $no ?></div>
                                <h3 class="font-bold text-base text-gray-900 mb-2"><?= htmlspecialchars($r['nama']) ?></h3>
                            </div>
                        </div>
                        
                        <div class="space-y-2 mb-3">
                            <div class="flex items-center text-sm gap-2">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    👥 <?= htmlspecialchars($r['kapasitas']) ?>
                                </span>
                                <span class="text-gray-600">
                                    📍 <?= htmlspecialchars($r['lokasi']) ?>
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div class="bg-green-50 p-2 rounded-lg">
                                    <div class="text-gray-600 mb-1">Tarif Internal</div>
                                    <div class="font-bold text-green-600">Rp <?= number_format($r['tarif_internal']??0,0,',','.') ?></div>
                                </div>
                                <div class="bg-blue-50 p-2 rounded-lg">
                                    <div class="text-gray-600 mb-1">Tarif Eksternal</div>
                                    <div class="font-bold text-blue-600">Rp <?= number_format($r['tarif_eksternal']??0,0,',','.') ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex gap-2">
                            <a href="editruangmultiguna_admin.php?id=<?= $r['id'] ?>" 
                               class="flex-1 text-center px-3 py-2 text-xs font-bold rounded-lg bg-green-500 hover:bg-green-600 text-white transition-all">
                                ✏️ Edit
                            </a>
                            <a href="detailmultiguna_admin.php?id=<?= $r['id'] ?>" 
                               class="flex-1 text-center px-3 py-2 text-xs font-bold rounded-lg bg-amber-500 hover:bg-amber-600 text-white transition-all">
                                👁️ Detail
                            </a>
                            <button onclick="confirmDelete(<?= $r['id'] ?>)" 
                                    class="flex-1 px-3 py-2 text-xs font-bold rounded-lg bg-red-500 hover:bg-red-600 text-white transition-all">
                                🗑️
                            </button>
                        </div>
                    </div>
                    <?php $no++; endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="flex flex-wrap justify-center gap-2 mt-6">
            <?php 
            $base_url = "search=".urlencode($search);
            
            // Tombol Sebelumnya
            if ($page > 1): ?>
                <a href="?page=<?= $page-1 ?>&<?= $base_url ?>" 
                   class="px-3 sm:px-4 py-2 rounded-lg bg-gray-700 text-white hover:bg-gray-800 transition-colors text-sm">
                    ‹ Sebelumnya
                </a>
            <?php else: ?>
                <span class="px-3 sm:px-4 py-2 rounded-lg bg-gray-300 text-gray-600 cursor-not-allowed text-sm">‹ Sebelumnya</span>
            <?php endif; ?>

            <?php for($i=max(1,$page-2); $i<=min($total_pages,$page+2); $i++): ?>
                <?php if ($i==$page): ?>
                    <span class="px-3 sm:px-4 py-2 rounded-lg bg-amber-500 text-gray-900 font-semibold text-sm"><?= $i ?></span>
                <?php else: ?>
                    <a href="?page=<?= $i ?>&<?= $base_url ?>" 
                       class="px-3 sm:px-4 py-2 rounded-lg bg-gray-700 text-white hover:bg-gray-800 transition-colors text-sm">
                        <?= $i ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php 
            // Tombol Selanjutnya
            if ($page<$total_pages): ?>
                <a href="?page=<?= $page+1 ?>&<?= $base_url ?>" 
                   class="px-3 sm:px-4 py-2 rounded-lg bg-gray-700 text-white hover:bg-gray-800 transition-colors text-sm">
                    Selanjutnya ›
                </a>
            <?php else: ?>
                <span class="px-3 sm:px-4 py-2 rounded-lg bg-gray-300 text-gray-600 cursor-not-allowed text-sm">Selanjutnya ›</span>
            <?php endif; ?>
        </div>
        
        <div class="text-gray-500 text-xs sm:text-sm mt-3 text-center">
            Menampilkan <?= count($ruangan_data) ?> dari <?= $total_records ?> total data
            <?= !empty($search) ? '(hasil pencarian: "'.htmlspecialchars($search).'")' : '' ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    const overlay = document.getElementById('sidebarOverlay');
    const is_desktop = window.innerWidth >= 1024;

    if (is_desktop) {
        sidebar.classList.toggle('lg:w-60');
        sidebar.classList.toggle('lg:w-16');
        main.classList.toggle('lg:ml-60');
        main.classList.toggle('lg:ml-16');
    } else {
        sidebar.classList.toggle('translate-x-0');
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    }

    const is_expanded = sidebar.classList.contains('lg:w-60') || sidebar.classList.contains('translate-x-0');
    
    if (typeof updateSidebarVisibility === 'function') {
        updateSidebarVisibility(is_expanded);
    }
    
    localStorage.setItem('sidebarStatus', is_expanded ? 'open' : 'collapsed');
}

function confirmDelete(id) {
    Swal.fire({
        title: 'Hapus Data Ruangan?',
        text: 'Ruangan akan dinonaktifkan dan tidak ditampilkan dalam daftar',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d9534f',
        cancelButtonColor: '#f59e0b', 
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'dataruangmultiguna_admin.php?action=delete&id=' + id;
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    const overlay = document.getElementById('sidebarOverlay');
    const status = localStorage.getItem('sidebarStatus');
    const is_desktop = window.innerWidth >= 1024;

    // Restore sidebar state
    if (status === 'open') {
        if (is_desktop) {
            main.classList.add('lg:ml-60');
            main.classList.remove('lg:ml-16');
            sidebar.classList.add('lg:w-60');
            sidebar.classList.remove('lg:w-16');
        } else {
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
            overlay.classList.remove('hidden');
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
            overlay.classList.add('hidden');
        }
    }
    
    // Close sidebar when clicking overlay
    if (overlay) {
        overlay.addEventListener('click', toggleSidebar);
    }
    
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const clearBtn = document.getElementById('searchClearBtn');
    
    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            window.location.href = 'dataruangmultiguna_admin.php';
        });
    }
    
    // Success alert auto-hide
    const successAlert = document.getElementById('successAlert');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.opacity = '0';
            setTimeout(() => { successAlert.style.display = 'none'; }, 500);
        }, 5000);
    }
});
</script>
</body>
</html>