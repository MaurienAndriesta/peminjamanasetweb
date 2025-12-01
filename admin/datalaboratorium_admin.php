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

    // 1. Cek Gambar
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

    // 2. Hapus Data dengan Try-Catch
    try {
        $stmt = $db->prepare("DELETE FROM `$source` WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $del_id);
            if ($stmt->execute()) {
                $stmt->close();
                
                // Hapus file fisik jika ada
                if (!empty($foto)) {
                    $p = __DIR__ . '/assets/images/' . $foto;
                    if (!file_exists($p)) $p = 'assets/images/' . $foto;
                    if (file_exists($p)) @unlink($p);
                }
                
                header("Location: datalaboratorium_admin.php?status=deleted");
                exit;
            } else {
                throw new Exception($stmt->error);
            }
        } else {
            throw new Exception("Kesalahan SQL Prepare");
        }
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
            $error_message = "Gagal menghapus! Laboratorium ini sedang digunakan dalam riwayat peminjaman.";
        } else {
            $error_message = "Gagal menghapus: " . $e->getMessage();
        }
    }
}

// ========================
// INIT & VARIABLE
// ========================
$success_message = null;
if (!isset($error_message)) $error_message = null;

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
$where_clauses = ["1=1"];

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
    $types = $param_types . 'ii';
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
    
    <!-- HEADER PANEL -->
    <div class="header-panel p-4 mb-10 sticky top-4 z-50 flex flex-col xl:flex-row justify-between items-center gap-4 transition-all duration-300">
        <div class="flex items-center gap-4 w-full xl:w-auto">
            <button id="toggleBtn" onclick="toggleSidebar()" class="bg-amber-500 text-white p-2.5 rounded-xl shadow-lg hover:bg-amber-600 active:scale-95 transition-all flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>

            <!-- Combined Filter Form -->
            <form method="get" class="flex flex-col md:flex-row gap-3 w-full xl:w-[32rem]">
                <!-- Select Fakultas -->
                <div class="relative md:w-1/3">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 pointer-events-none">
                        <i class="fas fa-filter text-sm"></i>
                    </span>
                    <select name="fakultas" onchange="this.form.submit()"
                        class="w-full pl-8 pr-1 py-2.5 bg-gray-50 border border-gray-200 rounded-full text-sm font-medium focus:ring-4 focus:ring-blue-200/50 transition-all outline-none shadow-sm cursor-pointer appearance-none">
                        <option value="">Semua Fakultas</option>
                        <option value="FTBE" <?= $fakultas=='FTBE'?'selected':'' ?>>FTBE</option>
                        <option value="FTEN" <?= $fakultas=='FTEN'?'selected':'' ?>>FTEN</option>
                        <option value="FTIK" <?= $fakultas=='FTIK'?'selected':'' ?>>FTIK</option>
                        <option value="FKET" <?= $fakultas=='FKET'?'selected':'' ?>>FKET</option>
                    </select>
                    <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-400">
                        <i class="fas fa-chevron-down text-xs"></i>
                    </span>
                </div>

                <!-- Search Input -->
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-blue-500">
                        <i class="fas fa-search text-lg"></i>
                    </span>
                    <input type="text" name="search" placeholder="Cari Laboratorium..." value="<?= htmlspecialchars($search) ?>" 
                        class="w-full pl-12 pr-10 py-2.5 bg-white border border-gray-200 rounded-full text-sm font-medium focus:ring-4 focus:ring-blue-200/50 transition-all outline-none shadow-sm placeholder-gray-400">
                    <?php if (!empty($search)): ?>
                        <button type="button" onclick="window.location='datalaboratorium_admin.php?fakultas=<?= urlencode($fakultas) ?>'" 
                            class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    <?php endif; ?>
                </div>
                <input type="hidden" name="page" value="1">
            </form>
        </div>

        <a href="tambahlaboratorium_admin.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-xl shadow-md transition-all flex items-center gap-2 w-full md:w-auto justify-center">
            <i class="fas fa-plus"></i> <span>Tambah Data</span>
        </a>
    </div>

    <!-- ALERTS -->
    <?php if (isset($_GET['status']) && $_GET['status'] === 'deleted'): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl mb-6 shadow-sm animate-fade-in flex items-center gap-3" id="successAlert">
            <i class="fas fa-check-circle text-xl"></i>
            <span>✅ Data berhasil dihapus permanen.</span>
        </div>
    <?php elseif (isset($_GET['status']) && $_GET['status'] === 'error'): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 shadow-sm animate-fade-in flex items-center gap-3">
            <i class="fas fa-exclamation-triangle text-xl"></i>
            <span>❌ <?= htmlspecialchars($_GET['msg'] ?? 'Terjadi kesalahan') ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 shadow-sm animate-fade-in flex items-center gap-3">
            <i class="fas fa-exclamation-circle text-xl"></i>
            <span>❌ <?= htmlspecialchars($error_message) ?></span>
        </div>
    <?php endif; ?>

    <!-- PAGE TITLE -->
    <div class="mb-6 animate-fade-in">
        <h1 class="text-3xl font-extrabold text-slate-800">Data Laboratorium</h1>
        <p class="text-slate-500 mt-1">Kelola data laboratorium dari berbagai fakultas.</p>
    </div>

    <!-- CONTENT CARD -->
    <div class="content-card p-6 animate-fade-in">
        
        <!-- Desktop Table -->
        <div class="hidden lg:block overflow-x-auto">
            <?php if (empty($laboratorium_data)): ?>
                <div class="text-center p-8">
                    <p class="text-gray-600">📂 Belum ada data laboratorium ditemukan.</p>
                </div>
            <?php else: ?>
                <table class="min-w-full border-collapse border border-gray-200 text-sm">
                    <thead class="bg-gray-800 text-white">
                        <tr>
                            <th class="px-3 py-3 border-b border-gray-300 text-left text-xs font-bold uppercase tracking-wider w-10">No.</th>
                            <th class="px-3 py-3 border-b border-gray-300 text-left text-xs font-bold uppercase tracking-wider">Nama Laboratorium</th>
                            <th class="px-3 py-3 border-b border-gray-300 text-left text-xs font-bold uppercase tracking-wider">Kapasitas</th>
                            <th class="px-3 py-3 border-b border-gray-300 text-left text-xs font-bold uppercase tracking-wider">Fakultas</th>
                            <th class="px-3 py-3 border-b border-gray-300 text-center text-xs font-bold uppercase tracking-wider w-48">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php $no = $offset + 1; foreach($laboratorium_data as $lab): 
                            $status_raw = strtolower(str_replace(['_', ' '], '', $lab['status']));
                            $is_unavailable = ($status_raw === 'tidaktersedia');
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors <?= $is_unavailable ? 'bg-gray-100' : '' ?>">
                            <td class="px-3 py-3 whitespace-nowrap text-sm font-bold text-gray-700 text-center"><?= $no++ ?>.</td>
                            <td class="px-3 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?= htmlspecialchars($lab['nama']) ?>
                                <?php if($is_unavailable): ?>
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-red-100 text-red-800 border border-red-200">
                                        Tidak Tersedia
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-sm">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    👥 <?= htmlspecialchars($lab['kapasitas']) ?>
                                </span>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-sm">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                    🏫 <?= htmlspecialchars($lab['fakultas']) ?>
                                </span>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-center text-sm font-medium">
                                <div class="flex justify-center gap-1">
                                    <a href="<?= $editPage ?>?id=<?= $lab['id'] ?>&source=<?= urlencode($lab['source_table'] ?? '') ?>" 
                                       class="inline-flex items-center px-2 py-1 bg-green-500 hover:bg-green-600 text-white text-xs font-bold rounded shadow-sm">
                                        ✏️ Edit
                                    </a>
                                    <a href="<?= $detailPage ?>?id=<?= $lab['id'] ?>&source=<?= urlencode($lab['source_table'] ?? '') ?>" 
                                       class="inline-flex items-center px-2 py-1 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded shadow-sm">
                                        👁️ Detail
                                    </a>
                                    <button onclick="confirmDelete(<?= (int)$lab['id'] ?>, '<?= htmlspecialchars($lab['source_table']) ?>')" 
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
            <?php if (empty($laboratorium_data)): ?>
                <div class="text-center p-8">
                    <p class="text-gray-600">📂 Belum ada data laboratorium.</p>
                </div>
            <?php else: ?>
                <?php $no = $offset + 1; foreach($laboratorium_data as $lab): 
                    $status_raw = strtolower(str_replace(['_', ' '], '', $lab['status']));
                    $is_unavailable = ($status_raw === 'tidaktersedia');
                ?>
                <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm <?= $is_unavailable ? 'bg-gray-100' : '' ?>">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex-1">
                            <div class="text-xs text-gray-500 mb-1">No. <?= $no++ ?></div>
                            <h3 class="font-bold text-base text-gray-900 mb-2">
                                <?= htmlspecialchars($lab['nama']) ?>
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
                                👥 <?= htmlspecialchars($lab['kapasitas']) ?>
                            </span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                🏫 <?= htmlspecialchars($lab['fakultas']) ?>
                            </span>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a href="<?= $editPage ?>?id=<?= $lab['id'] ?>&source=<?= urlencode($lab['source_table'] ?? '') ?>" class="flex-1 text-center bg-green-500 text-white px-3 py-2 rounded-md text-xs font-bold shadow-sm">Edit</a>
                        <a href="<?= $detailPage ?>?id=<?= $lab['id'] ?>&source=<?= urlencode($lab['source_table'] ?? '') ?>" class="flex-1 text-center bg-amber-500 text-white px-3 py-2 rounded-md text-xs font-bold shadow-sm">Detail</a>
                        <button onclick="confirmDelete(<?= (int)$lab['id'] ?>, '<?= htmlspecialchars($lab['source_table']) ?>')" class="flex-1 bg-red-500 text-white px-3 py-2 rounded-md text-xs font-bold shadow-sm">Hapus</button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="flex justify-center gap-2 mt-4 text-sm">
            <?php 
            $base_url = "search=".urlencode($search)."&fakultas=".urlencode($fakultas);
            if ($page > 1): ?>
                <a href="?page=<?= $page-1 ?>&<?= $base_url ?>" class="px-3 py-1 bg-gray-700 text-white rounded-md">‹</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>&<?= $base_url ?>" 
                   class="px-3 py-1 rounded-md <?= $i == $page ? 'bg-amber-500 text-gray-900 font-bold' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                   <?= $i ?>
                </a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page+1 ?>&<?= $base_url ?>" class="px-3 py-1 bg-gray-700 text-white rounded-md">›</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Fungsi Sidebar (Konsisten dengan datafasilitas)
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
function confirmDelete(id, source) {
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
            const url = new URL(window.location.href);
            url.searchParams.set('action', 'delete');
            url.searchParams.set('id', id);
            url.searchParams.set('source', source);
            window.location.href = url.toString();
        }
    });
}

// Event Listeners
document.addEventListener("DOMContentLoaded", () => {
    const sidebar = document.getElementById("sidebar");
    const status = localStorage.getItem('sidebarStatus');
    
    if (status === 'open' && window.innerWidth >= 1024) {
        sidebar.classList.add('lg:w-60'); 
        sidebar.classList.remove('lg:w-16');
        document.querySelector('.main').classList.add('lg:ml-60'); 
        document.querySelector('.main').classList.remove('lg:ml-16');
    }

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