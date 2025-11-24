<?php
require_once '../koneksi.php';
$db = $koneksi;

// =================== INISIALISASI ===================
$success_message = null;
$error_message = null;

// --- LOGIC PESAN SUKSES ---
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success_edit') {
        $success_message = "✅ Data usaha berhasil diperbarui!";
    } elseif ($_GET['status'] === 'success_add') {
        $success_message = "✅ Data usaha berhasil ditambahkan!";
    } elseif ($_GET['status'] === 'success_delete') {
        $success_message = "✅ Data usaha berhasil dinonaktifkan!";
    }
}

// =================== HAPUS DATA PERMANEN ===================
if (isset($_GET['action'], $_GET['id'], $_GET['permanent']) 
    && $_GET['action'] === 'delete' && $_GET['permanent'] == 1) {

    $id = (int)$_GET['id'];

    // Opsional: cek dan hapus file jika ada (misal kolom 'gambar')
    $column_exists = $db->query("SHOW COLUMNS FROM tbl_usaha LIKE 'gambar'")->num_rows > 0;
    if ($column_exists) {
        $stmt_file = $db->prepare("SELECT gambar FROM tbl_usaha WHERE id = ?");
        $stmt_file->bind_param("i", $id);
        $stmt_file->execute();
        $result_file = $stmt_file->get_result();
        if ($row_file = $result_file->fetch_assoc()) {
            if (!empty($row_file['gambar']) && file_exists('../' . $row_file['gambar'])) {
                unlink('../' . $row_file['gambar']);
            }
        }
        $stmt_file->close();
    }

    // Hapus data usaha
    $stmt_delete = $db->prepare("DELETE FROM tbl_usaha WHERE id = ?");
    $stmt_delete->bind_param("i", $id);
    if ($stmt_delete->execute()) {
        header("Location: datausaha_admin.php?status=success_delete");
        exit;
    } else {
        $error_message = "Gagal menghapus data usaha: " . $stmt_delete->error;
    }
    $stmt_delete->close();
}


// =================== PAGINATION & SEARCH ===================
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = "WHERE 1";
if (!empty($search)) {
    $where .= " AND nama LIKE '%$search%'"; 
}



// Hitung total data
$sql_total = "SELECT COUNT(*) AS total FROM tbl_usaha $where";
$result_total = $db->query($sql_total);
$total_records = ($result_total) ? (int)$result_total->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_records / $limit);

// Ambil data usaha
$usaha_data = [];
$sql_data = "SELECT id, nama, kapasitas, gambar, tarif_eksternal, keterangan, status, created_at
             FROM tbl_usaha 
             $where 
             ORDER BY created_at DESC 
             LIMIT $limit OFFSET $offset";

$result_data = $db->query($sql_data);
if ($result_data) {
    while ($row = $result_data->fetch_assoc()) {
        $usaha_data[] = $row;
    }
} else {
    $error_message = "Query gagal: " . $db->error;
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
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .text-dark-accent { color: #202938; }
        @media (min-width: 1024px) {
            .main { margin-left: 4rem; }
            .main.lg\:ml-60 { margin-left: 15rem; }
        }
        @media (max-width: 1023px) {
            .main { margin-left: 0 !important; }
        }
        #toggleBtn { position: relative; z-index: 51 !important; }
    </style>
</head>
<body class="bg-blue-100 min-h-screen">

<?php include 'sidebar_admin.php'; ?>

<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

<div id="mainContent" class="main flex-1 p-3 sm:p-5 transition-all duration-300 lg:ml-16 min-h-screen">

    <div class="flex justify-between items-center mb-4 sm:mb-6">
        <button id="toggleBtn" class="bg-amber-500 hover:bg-amber-600 text-gray-900 p-2 rounded-lg transition-colors shadow-md" onclick="toggleSidebar()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        <h1 class="text-lg sm:text-2xl font-bold text-dark-accent">Data Usaha</h1>
    </div>

    <?php if ($success_message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-3 py-3 rounded-lg mb-4 shadow-md text-sm" id="successAlert">
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-3 rounded-lg mb-4 shadow-md text-sm">
            ❌ <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-4 sm:p-6 rounded-xl shadow-lg">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6">
            <a href="tambahdatausaha_admin.php" class="bg-amber-500 hover:bg-amber-600 text-gray-900 font-semibold px-4 py-3 rounded-lg shadow-md text-sm sm:text-base flex items-center gap-2">
                ➕ Tambah Data
            </a>
            <form method="GET" class="relative w-full sm:max-w-md mt-3 sm:mt-0">
                <input type="text" name="search" 
                    class="w-full px-4 py-2 pr-10 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 text-sm"
                    placeholder="🔍 Cari usaha..." 
                    value="<?= htmlspecialchars($search) ?>">
                <?php if (!empty($search)): ?>
                    <button type="button" onclick="window.location='datausaha_admin.php'" 
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 text-xl font-bold">
                        &times;
                    </button>
                <?php endif; ?>
            </form>
        </div>

        <div class="overflow-x-auto">
            <?php if (empty($usaha_data)): ?>
                <div class="text-center p-8">
                    <p class="text-gray-600">📂 Belum ada data usaha ditemukan.</p>
                </div>
            <?php else: ?>
                <table class="min-w-full border-collapse border border-gray-200 text-sm">
                    <thead class="bg-gray-700 text-white">
                        <tr>
                            <th class="border px-4 py-2">No</th>
                            <th class="border px-4 py-2 text-left">Nama Usaha</th>
                            <th class="border px-4 py-2 text-left">Kapasitas</th>
                            <th class="border px-4 py-2">Tarif</th>
                            <th class="border px-4 py-2">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
<?php $no = $offset + 1; foreach ($usaha_data as $u): ?>
<tr class="hover:bg-gray-50">
    <td class="border px-4 py-2 text-center"><?= $no++ ?></td>
    <td class="border px-4 py-2"><?= htmlspecialchars($u['nama']) ?></td>
    <td class="border px-4 py-2"><?= htmlspecialchars($u['kapasitas']) ?></td>
    <td class="border px-4 py-2 text-green-700">Rp <?= number_format($u['tarif_eksternal'] ?? 0, 0, ',', '.') ?></td>
    <td class="border px-4 py-2 text-center">
    <?php if (!empty($u['id'])): ?>
        <a href="editusaha_admin.php?id=<?= urlencode($u['id']) ?>" 
           class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded-md text-xs">
           ✏️ Edit
        </a>
        <a href="detailusaha_admin.php?id=<?= urlencode($u['id']) ?>" 
           class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-1 rounded-md text-xs">
           👁️ Detail
        </a>
        <button onclick="confirmDelete(<?= (int)$u['id'] ?>)" 
        class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-md text-xs">
    🗑️ Hapus
</button>
    <?php else: ?>
        <span class="text-gray-400 italic">ID tidak valid</span>
    <?php endif; ?>
</td>

</tr>
<?php endforeach; ?>
</tbody>

                </table>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="flex justify-center gap-2 mt-6 text-sm">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-2 bg-gray-700 text-white rounded-md">‹</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" 
                   class="px-3 py-2 rounded-md <?= $i == $page ? 'bg-amber-500 text-gray-900 font-bold' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                   <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-2 bg-gray-700 text-white rounded-md">›</a>
            <?php endif; ?>
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
        title: 'Hapus permanen data usaha?',
        text: 'Aksi ini akan menghapus data dari database dan file (jika ada). Tidak bisa dikembalikan!',
        showCancelButton: true,
        confirmButtonColor: '#d9534f',
        cancelButtonColor: '#f59e0b',
        confirmButtonText: 'Ya, Hapus Permanen',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'datausaha_admin.php?action=delete&id=' + id + '&permanent=1';
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
            window.location.href = 'datausaha_admin.php';
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