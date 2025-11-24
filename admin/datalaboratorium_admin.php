<?php
require_once '../koneksi.php';
$db = $koneksi;

// ========================
// HANDLE DELETE PERMANEN
// ========================
$allowed_tables = ['labftbe','labften','labftik','labfket'];
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && isset($_GET['source'])) {
    $del_id = (int) $_GET['id'];
    $source = $_GET['source'];

    if (!in_array($source, $allowed_tables)) {
        header("Location: datalaboratorium_admin.php?status=error&msg=" . urlencode("Tabel sumber tidak valid."));
        exit;
    }

    // Ambil nama file foto bila ada, sebelum delete
    $foto = null;
    $stmt = $db->prepare("SELECT foto FROM `$source` WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $del_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $foto = $row['foto'];
        }
        $stmt->close();
    }

    // Hapus record
    $stmt = $db->prepare("DELETE FROM `$source` WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $del_id);
        if ($stmt->execute()) {
            $stmt->close();
            // Hapus file foto jika ada (path relatif sesuai implementasi)
            if (!empty($foto)) {
                $p = __DIR__ . '/../assets/images/' . $foto;
                if (file_exists($p)) @unlink($p);
            }
            header("Location: datalaboratorium_admin.php?status=deleted");
            exit;
        } else {
            $err = $stmt->error;
            $stmt->close();
            header("Location: datalaboratorium_admin.php?status=error&msg=" . urlencode("Gagal menghapus: $err"));
            exit;
        }
    } else {
        header("Location: datalaboratorium_admin.php?status=error&msg=" . urlencode("Kesalahan SQL: " . $db->error));
        exit;
    }
}

// ========================
// INIT & VARIABLE
// ========================
$success_message = null;
$error_message = null;

$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$search   = trim($_GET['search'] ?? '');
$fakultas = $_GET['fakultas'] ?? '';

$params = [];
$param_types = '';

// ========================
// UNION 4 TABEL LAB
// ========================
$union_query = "
    SELECT id, foto, nama, lokasi, tarif_sewa_laboratorium, tarif_sewa_peralatan,
           kapasitas, status, 'FTBE' AS fakultas, 'labftbe' AS source_table FROM labftbe
    UNION ALL
    SELECT id, foto, nama, lokasi, tarif_sewa_laboratorium, tarif_sewa_peralatan,
           kapasitas, status, 'FTEN' AS fakultas, 'labften' AS source_table FROM labften
    UNION ALL
    SELECT id, foto, nama, lokasi, tarif_sewa_laboratorium, tarif_sewa_peralatan,
           kapasitas, status, 'FTIK' AS fakultas, 'labftik' AS source_table FROM labftik
    UNION ALL
    SELECT id, foto, nama, lokasi, tarif_sewa_laboratorium, tarif_sewa_peralatan,
           kapasitas, status, 'FKET' AS fakultas, 'labfket' AS source_table FROM labfket
";

// ========================
// FILTER
// ========================
$where_clauses = ["status = 'tersedia'"];
if ($search !== '') {
    $where_clauses[] = "nama LIKE ?";
    $params[] = "%$search%";
    $param_types .= 's';
}
if ($fakultas !== '') {
    $where_clauses[] = "fakultas = ?";
    $params[] = $fakultas;
    $param_types .= 's';
}
$where = ' WHERE ' . implode(' AND ', $where_clauses);

// ========================
// HITUNG TOTAL DATA
// ========================
$count_query = "SELECT COUNT(*) AS total FROM ($union_query) AS all_labs $where";
$stmt = $db->prepare($count_query);
if (!$stmt) {
    $error_message = "Kesalahan SQL (count prepare): " . $db->error;
} else {
    if (!empty($params)) {
        // bind params dynamically
        $bind_names = [];
        $bind_names[] = $param_types;
        foreach ($params as $k => $v) $bind_names[] = &$params[$k];
        call_user_func_array([$stmt, 'bind_param'], $bind_names);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $total_records = $res->fetch_assoc()['total'] ?? 0;
    $stmt->close();
    $total_pages = max(1, (int)ceil($total_records / $limit));
}

// ========================
// QUERY DATA PAGINATION
// ========================
$data_query = "
    SELECT * FROM ($union_query) AS all_labs
    $where
    ORDER BY nama ASC
    LIMIT ? OFFSET ?
";

$params_with_paging = $params;
$params_with_paging[] = $limit;
$params_with_paging[] = $offset;

$stmt = $db->prepare($data_query);
if (!$stmt) {
    $error_message = "Kesalahan SQL (data prepare): " . $db->error;
    $laboratorium_data = [];
} else {
    // build types
    $types = $param_types . 'ii';
    // bind dynamic
    $bind_values = [];
    $bind_values[] = $types;
    foreach ($params_with_paging as $k => $v) $bind_values[] = &$params_with_paging[$k];
    call_user_func_array([$stmt, 'bind_param'], $bind_values);

    if (!$stmt->execute()) {
        $error_message = "Eksekusi query gagal: " . $stmt->error;
        $laboratorium_data = [];
    } else {
        $laboratorium_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();
}

// === Routing halaman edit / detail
$editPage   = "editlaboratorium_admin.php";
$detailPage = "detaillaboratorium_admin.php";

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Laboratorium</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
        }
        .text-dark-accent { color: #202938; }
        @media (min-width: 1024px) {
            .main { margin-left: 4rem; }
            .main.lg\:ml-60 { margin-left: 15rem; }
        }
    </style>
</head>
<body class="bg-blue-100 min-h-screen">

<?php include 'sidebar_admin.php'; ?>

<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

<div id="mainContent" class="main flex-1 p-3 sm:p-5 transition-all duration-300 lg:ml-16 min-h-screen">
    
    <div class="flex justify-between items-center mb-4 sm:mb-6">
        <button id="toggleBtn" class="bg-amber-500 hover:bg-amber-600 text-gray-900 p-2 rounded-lg transition-colors shadow-md relative z-50" onclick="toggleSidebar()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        
        <div class="text-sm text-gray-600 hidden sm:block">
            <span class="font-semibold">Data Laboratorium</span>
        </div>
    </div>

    <?php if (isset($_GET['status']) && $_GET['status'] === 'deleted'): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-3 sm:px-4 py-3 rounded-lg mb-4 shadow-md text-sm">Data berhasil dihapus permanen.</div>
    <?php elseif (isset($_GET['status']) && $_GET['status'] === 'error'): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-3 sm:px-4 py-3 rounded-lg mb-4 shadow-md text-sm">❌ <?= htmlspecialchars($_GET['msg'] ?? 'Terjadi kesalahan') ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-3 sm:px-4 py-3 rounded-lg mb-4 shadow-md text-sm">❌ <?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <div class="bg-white p-4 sm:p-6 rounded-xl shadow-lg">
        <div class="mb-4 sm:mb-6">
            <h1 class="text-xl sm:text-2xl font-bold text-dark-accent flex items-center gap-2">
                <span class="text-2xl sm:text-3xl">🔬</span>
                <span>Data Laboratorium</span>
            </h1>
            <p class="text-xs sm:text-sm text-gray-600 mt-1">Kelola data laboratorium kampus</p>
        </div>
        
        <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center mb-4 sm:mb-6 gap-3">
            <a href="tambahlaboratorium_admin.php" 
                class="bg-amber-500 hover:bg-amber-600 text-gray-900 font-semibold px-4 py-2 rounded-lg shadow-md transition-all hover:shadow-lg text-sm inline-flex items-center justify-center gap-1.5 w-auto">
                <span>➕</span>
                <span>Tambah Data</span>
            </a>
            
            <div class="flex flex-col sm:flex-row gap-3 flex-1 sm:max-w-2xl">
                <form method="GET" class="flex-1">
                    <select name="fakultas" onchange="this.form.submit()"
                            class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all text-sm">
                        <option value="">🏫 Semua Fakultas</option>
                        <option value="FTBE" <?= $fakultas=='FTBE'?'selected':'' ?>>FTBE</option>
                        <option value="FTEN" <?= $fakultas=='FTEN'?'selected':'' ?>>FTEN</option>
                        <option value="FTIK" <?= $fakultas=='FTIK'?'selected':'' ?>>FTIK</option>
                        <option value="FKET" <?= $fakultas=='FKET'?'selected':'' ?>>FKET</option>
                    </select>
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                    <input type="hidden" name="page" value="1">
                </form>
                
                <form method="GET" class="relative flex-1">
                    <input type="text" name="search" id="searchInput"
                           class="w-full px-4 py-2 pr-10 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all text-sm"
                           placeholder="🔍 Cari laboratorium..." value="<?= htmlspecialchars($search) ?>">
                    <?php if (!empty($search)): ?>
                        <button type="button" id="searchClearBtn"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                    <?php endif; ?>
                    <input type="hidden" name="fakultas" value="<?= htmlspecialchars($fakultas) ?>">
                    <input type="hidden" name="page" value="1">
                </form>
            </div>
        </div>

        <div class="bg-white rounded-xl overflow-hidden shadow-md border border-gray-200">
            <?php if (empty($laboratorium_data)): ?>
                <div class="text-center p-8 sm:p-16">
                    <div class="text-5xl sm:text-6xl mb-3 sm:mb-4">📂</div>
                    <?php if (!empty($search) || !empty($fakultas)): ?>
                        <h3 class="text-lg sm:text-xl font-bold text-gray-700 mb-2">Tidak ada laboratorium ditemukan</h3>
                        <p class="text-sm sm:text-base text-gray-600 mb-4">Coba ubah kata kunci atau pilihan fakultas</p>
                        <a href="datalaboratorium_admin.php" class="bg-amber-500 hover:bg-amber-600 text-gray-900 font-semibold px-4 sm:px-6 py-2 sm:py-3 rounded-lg inline-block transition-colors shadow-md text-sm sm:text-base">Tampilkan Semua Data</a>
                    <?php else: ?>
                        <h3 class="text-lg sm:text-xl font-bold text-gray-700 mb-2">Belum ada data laboratorium</h3>
                        <p class="text-sm sm:text-base text-gray-600 mb-4">Tambahkan laboratorium pertama Anda</p>
                        <a href="tambahlaboratorium_admin.php" class="bg-amber-500 hover:bg-amber-600 text-gray-900 font-semibold px-4 sm:px-6 py-2 sm:py-3 rounded-lg inline-block transition-colors shadow-md text-sm sm:text-base">➕ Tambah Laboratorium</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="hidden lg:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-700 text-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">No.</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Nama Laboratorium</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Kapasitas</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Fakultas</th>
                                <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php $no = $offset + 1; foreach($laboratorium_data as $lab): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-gray-700"><?= $no ?>.</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($lab['nama']) ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm"><span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">👥 <?= htmlspecialchars($lab['kapasitas']) ?></span></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700"><span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">🏫 <?= htmlspecialchars($lab['fakultas']) ?></span></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    <div class="flex gap-2 justify-center">
                                        <a href="<?= $editPage ?>?id=<?= $lab['id'] ?>&source=<?= urlencode($lab['source_table'] ?? '') ?>" class="inline-flex items-center px-3 py-1.5 text-xs font-bold rounded-lg bg-green-500 hover:bg-green-600 text-white transition-all shadow-sm">✏️ Edit</a>
                                        <a href="<?= $detailPage ?>?id=<?= $lab['id'] ?>&source=<?= urlencode($lab['source_table'] ?? '') ?>" class="inline-flex items-center px-3 py-1.5 text-xs font-bold rounded-lg bg-amber-500 hover:bg-amber-600 text-white transition-all shadow-sm">👁️ Detail</a>
                                        <button onclick="confirmDelete(<?= (int)$lab['id'] ?>, '<?= htmlspecialchars($lab['source_table']) ?>')" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-md text-xs">🗑️ Hapus</button>
                                    </div>
                                </td>
                            </tr>
                            <?php $no++; endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="lg:hidden space-y-4 p-3">
                    <?php $no = $offset + 1; foreach($laboratorium_data as $lab): ?>
                    <div class="bg-white border-2 border-gray-200 rounded-xl p-4 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex-1">
                                <div class="text-xs text-gray-500 mb-1">No. <?= $no ?></div>
                                <h3 class="font-bold text-base text-gray-900 mb-2"><?= htmlspecialchars($lab['nama']) ?></h3>
                            </div>
                        </div>
                        <div class="space-y-2 mb-3">
                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">👥 <?= htmlspecialchars($lab['kapasitas']) ?></span>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">🏫 <?= htmlspecialchars($lab['fakultas']) ?></span>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <a href="<?= $editPage ?>?id=<?= $lab['id'] ?>&source=<?= urlencode($lab['source_table'] ?? '') ?>" class="flex-1 text-center px-3 py-2 text-xs font-bold rounded-lg bg-green-500 hover:bg-green-600 text-white transition-all">✏️ Edit</a>
                            <a href="<?= $detailPage ?>?id=<?= $lab['id'] ?>&source=<?= urlencode($lab['source_table'] ?? '') ?>" class="flex-1 text-center px-3 py-2 text-xs font-bold rounded-lg bg-amber-500 hover:bg-amber-600 text-white transition-all">👁️ Detail</a>
                            <button onclick="confirmDelete(<?= (int)$lab['id'] ?>, '<?= htmlspecialchars($lab['source_table']) ?>')" class="flex-1 px-3 py-2 text-xs font-bold rounded-lg bg-red-500 hover:bg-red-600 text-white transition-all">🗑️</button>
                        </div>
                    </div>
                    <?php $no++; endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($total_pages) && $total_pages > 1): ?>
        <div class="flex flex-wrap justify-center gap-2 mt-6">
            <?php 
            $base_url = "search=".urlencode($search)."&fakultas=".urlencode($fakultas);
            if ($page > 1): ?>
                <a href="?page=<?= $page-1 ?>&<?= $base_url ?>" class="px-3 sm:px-4 py-2 rounded-lg bg-gray-700 text-white hover:bg-gray-800 transition-colors text-sm">‹ Sebelumnya</a>
            <?php else: ?>
                <span class="px-3 sm:px-4 py-2 rounded-lg bg-gray-300 text-gray-600 cursor-not-allowed text-sm">‹ Sebelumnya</span>
            <?php endif; ?>

            <?php for($i=max(1,$page-2); $i<=min($total_pages,$page+2); $i++): ?>
                <?php if ($i==$page): ?>
                    <span class="px-3 sm:px-4 py-2 rounded-lg bg-amber-500 text-gray-900 font-semibold text-sm"><?= $i ?></span>
                <?php else: ?>
                    <a href="?page=<?= $i ?>&<?= $base_url ?>" class="px-3 sm:px-4 py-2 rounded-lg bg-gray-700 text-white hover:bg-gray-800 transition-colors text-sm"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page<$total_pages): ?>
                <a href="?page=<?= $page+1 ?>&<?= $base_url ?>" class="px-3 sm:px-4 py-2 rounded-lg bg-gray-700 text-white hover:bg-gray-800 transition-colors text-sm">Selanjutnya ›</a>
            <?php else: ?>
                <span class="px-3 sm:px-4 py-2 rounded-lg bg-gray-300 text-gray-600 cursor-not-allowed text-sm">Selanjutnya ›</span>
            <?php endif; ?>
        </div>

        <div class="text-gray-500 text-xs sm:text-sm mt-3 text-center">
            Menampilkan <strong><?= count($laboratorium_data) ?></strong> dari <strong><?= $total_records ?? 0 ?></strong> total data
            <?= !empty($search) ? '(hasil pencarian: "'.htmlspecialchars($search).'")' : '' ?>
            <?= !empty($fakultas) ? '(fakultas: '.htmlspecialchars($fakultas).')' : '' ?>
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
    localStorage.setItem('sidebarStatus', is_expanded ? 'open' : 'collapsed');
}

function confirmDelete(id, source) {
    
    Swal.fire({
        title: 'Hapus permanen data?',
        text: 'Aksi ini akan menghapus data dari database dan file (jika ada). Tindakan ini tidak dapat dibatalkan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d9534f',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus Permanen',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            // Redirect dengan parameter source agar server tahu dari tabel mana dihapus
            const url = new URL(window.location.href);
            url.searchParams.set('action', 'delete');
            url.searchParams.set('id', id);
            url.searchParams.set('source', source);
            window.location.href = url.toString();
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const clearBtn = document.getElementById('searchClearBtn');
    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            const url = new URL(window.location.href);
            url.searchParams.delete('search');
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        });
    }

    const successAlert = document.getElementById('successAlert');
    if (successAlert) {
        setTimeout(() => { successAlert.style.opacity = '0'; setTimeout(() => { successAlert.style.display = 'none'; }, 500); }, 5000);
    }
});
</script>
</body>
</html>
