<?php
// Include database configuration
require_once '../koneksi.php';
$db = $koneksi;

// Initialize messages
$success_message = null;
$error_message = null;

// --- LOGIC PESAN SUKSES ---
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success_edit') {
        $success_message = "✅ Data ruangan berhasil diperbarui!";
    } elseif ($_GET['status'] === 'success_add') {
        $success_message = "✅ Data ruangan berhasil ditambahkan!";
    } elseif ($_GET['status'] === 'success_delete') {
        $success_message = "✅ Data ruangan berhasil dinonaktifkan!";
    }
}

// --- HANDLE DELETE PERMANEN ---
// --- HANDLE DELETE PERMANEN ---
if (isset($_GET['action'], $_GET['id'], $_GET['permanent']) && $_GET['action'] === 'delete' && $_GET['permanent'] == 1) {
    $id = (int)$_GET['id'];

    // Cek apakah kolom file_path ada
    $column_exists = $db->query("SHOW COLUMNS FROM tbl_ruangmultiguna LIKE 'file_path'")->num_rows > 0;

    if ($column_exists) {
        $stmt_file = $db->prepare("SELECT file_path FROM tbl_ruangmultiguna WHERE id = ?");
        $stmt_file->bind_param("i", $id);
        $stmt_file->execute();
        $result_file = $stmt_file->get_result();
        if ($row_file = $result_file->fetch_assoc()) {
            if (!empty($row_file['file_path']) && file_exists('../' . $row_file['file_path'])) {
                unlink('../' . $row_file['file_path']);
            }
        }
        $stmt_file->close();
    }

    // Hapus data dari database
    $stmt_delete = $db->prepare("DELETE FROM tbl_ruangmultiguna WHERE id = ?");
    $stmt_delete->bind_param("i", $id);
    if ($stmt_delete->execute()) {
        header("Location: dataruangmultiguna_admin.php?status=success_delete");
        exit;
    } else {
        $error_message = "Gagal menghapus data: " . $stmt_delete->error;
    }
    $stmt_delete->close();
}




// --- PAGINATION & SEARCH LOGIC ---
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$search_query = '';
if (!empty($search)) {
    $search_query = " AND (nama LIKE ? OR kapasitas LIKE ?)";
}

// Hitung total data (tampilkan semua status)
$total_records = 0;
if (!empty($search)) {
    $search_param = "%{$search}%";
    $stmt_total = $db->prepare("SELECT COUNT(*) as total FROM tbl_ruangmultiguna WHERE status IS NOT NULL $search_query");
    $stmt_total->bind_param("ss", $search_param, $search_param);
} else {
    $stmt_total = $db->prepare("SELECT COUNT(*) as total FROM tbl_ruangmultiguna WHERE status IS NOT NULL");
}
$stmt_total->execute();
$result_total = $stmt_total->get_result();
if ($result_total && $row = $result_total->fetch_assoc()) {
    $total_records = (int)$row['total'];
}
$stmt_total->close();

$total_pages = ceil($total_records / $limit);

// Ambil data ruangan
$ruangan_data = [];
if (!empty($search)) {
    $stmt_data = $db->prepare("SELECT id, nama, kapasitas, tarif_internal, tarif_eksternal, status, created_at 
                               FROM tbl_ruangmultiguna 
                               WHERE status IS NOT NULL $search_query
                               ORDER BY created_at DESC 
                               LIMIT ? OFFSET ?");
    $stmt_data->bind_param("ssii", $search_param, $search_param, $limit, $offset);
} else {
    $stmt_data = $db->prepare("SELECT id, nama, kapasitas, tarif_internal, tarif_eksternal, status, created_at 
                               FROM tbl_ruangmultiguna 
                               WHERE status IS NOT NULL
                               ORDER BY created_at DESC 
                               LIMIT ? OFFSET ?");
    $stmt_data->bind_param("ii", $limit, $offset);
}
$stmt_data->execute();
$result_data = $stmt_data->get_result();
while ($row = $result_data->fetch_assoc()) {
    $ruangan_data[] = $row;
}
$stmt_data->close();
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
        body { font-family: 'Poppins', sans-serif; }
        .text-dark-accent { color: #202938; }
        @media (min-width: 1024px) {.main { margin-left: 4rem; } .main.lg\:ml-60 { margin-left: 15rem; }}
        @media (max-width: 1023px) {.main { margin-left: 0 !important; }}
        #toggleBtn { position: relative; z-index: 51 !important; }
        .swal2-confirm { background-color: #d9534f !important; }
        .swal2-cancel { background-color: #f59e0b !important; }
    </style>
</head>
<body class="bg-blue-100 min-h-screen">

<?php include 'sidebar_admin.php'; ?>

<!-- Overlay untuk mobile ketika sidebar terbuka -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

<div id="mainContent" class="main flex-1 p-3 sm:p-5 transition-all duration-300 lg:ml-16 min-h-screen">

    <!-- Header -->
    <div class="flex justify-between items-center mb-4 sm:mb-6">
        <button id="toggleBtn" class="bg-amber-500 hover:bg-amber-600 text-gray-900 p-2 rounded-lg shadow-md" onclick="toggleSidebar()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        <div class="text-sm text-gray-600 hidden sm:block">
            <span class="font-semibold">Data Ruangan Multiguna</span>
        </div>
    </div>

    <!-- Messages -->
    <?php if ($success_message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4 shadow-md" id="successAlert">
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4 shadow-md">
            ❌ <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <!-- Card -->
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow-lg">
        <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center mb-4 gap-3">
            <a href="tambahdatamultiguna_admin.php" 
               class="bg-amber-500 hover:bg-amber-600 text-gray-900 font-semibold px-6 py-3 rounded-lg shadow-md transition-all text-center text-sm sm:text-base flex items-center justify-center gap-2">
                ➕ Tambah Data
            </a>
            <form method="GET" class="relative w-full sm:max-w-md">
                <input type="text" name="search" 
                       class="w-full px-4 py-2.5 pr-10 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"
                       placeholder="🔍 Cari ruangan..." 
                       value="<?= htmlspecialchars($search) ?>">
                <?php if (!empty($search)): ?>
                    <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-2xl font-bold" 
                            onclick="window.location='dataruangmultiguna_admin.php'">&times;</button>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tabel -->
        <div class="bg-white rounded-xl overflow-hidden shadow-md border border-gray-200">
            <?php if (empty($ruangan_data)): ?>
                <div class="text-center p-8">
                    <div class="text-5xl mb-3">📂</div>
                    <h3 class="text-lg font-bold text-gray-700 mb-2">Tidak ada data ditemukan</h3>
                    <p class="text-sm text-gray-600 mb-4">Tambahkan data baru untuk mulai.</p>
                    <a href="tambahdatamultiguna_admin.php" class="bg-amber-500 hover:bg-amber-600 text-gray-900 font-semibold px-6 py-2 rounded-lg shadow-md inline-block">Tambah Ruangan</a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-700 text-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">No</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Nama</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Kapasitas</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Tarif Internal</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Tarif Eksternal</th>
                                <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php $no = $offset + 1; foreach ($ruangan_data as $r): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3"><?= $no++ ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($r['nama']) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($r['kapasitas']) ?></td>
                                <td class="px-4 py-3 text-green-600">Rp <?= number_format($r['tarif_internal'] ?? 0, 0, ',', '.') ?></td>
                                <td class="px-4 py-3 text-blue-600">Rp <?= number_format($r['tarif_eksternal'] ?? 0, 0, ',', '.') ?></td>
                                <td class="px-4 py-3 text-center">
                                    <a href="editruangmultiguna_admin.php?id=<?= $r['id'] ?>" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded-lg text-xs">✏️ Edit</a>
                                    <a href="detailmultiguna_admin.php?id=<?= $r['id'] ?>" class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-1 rounded-lg text-xs">👁️ Detail</a>
                                    <button onclick="confirmDelete(<?= $r['id'] ?>)" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-lg text-xs">🗑️ Hapus</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="flex justify-center gap-2 mt-6">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" 
                       class="px-3 py-2 rounded-lg <?= $i == $page ? 'bg-amber-500 text-gray-900' : 'bg-gray-700 text-white hover:bg-gray-800' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
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
        icon: 'warning',
        title: 'Hapus permanen data?',
        text: 'Aksi ini akan menghapus data dari database dan file (jika ada). Tindakan ini tidak dapat dibatalkan.',
        showCancelButton: true,
        confirmButtonColor: '#d9534f',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus Permanen',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'dataruangmultiguna_admin.php?action=delete&id=' + id + '&permanent=1';
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