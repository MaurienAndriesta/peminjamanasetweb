<?php
require_once 'config/database.php';
$db = new Database();

$success_message = null;

// ✅ Proses update status (dan mendapatkan pesan sukses dari aksi)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action === 'approve') {
        $db->query("UPDATE pemesanan SET status = 'disetujui' WHERE id = :id");
        $db->bind(':id', $id);
        $db->execute();
        $success_message = "✅ Pemesanan berhasil disetujui.";
    } elseif ($action === 'reject') {
        $db->query("UPDATE pemesanan SET status = 'ditolak' WHERE id = :id");
        $db->bind(':id', $id);
        $db->execute();
        $success_message = "❌ Pemesanan berhasil ditolak.";
    }
    
    // Siapkan parameter yang akan dikembalikan
    $redirect_params = [];
    if (isset($success_message)) {
        $redirect_params['success_msg'] = urlencode($success_message);
    }
    if (isset($_GET['status'])) {
        $redirect_params['status'] = urlencode($_GET['status']);
    }
    if (isset($_GET['search'])) {
        $redirect_params['search'] = urlencode($_GET['search']);
    }
    
    // Bentuk URL redirect
    $query_string = http_build_query($redirect_params);
    header("Location: permintaan_admin.php" . ($query_string ? "?" . $query_string : ""));
    exit;
}

// Ambil pesan sukses dari redirect
if (isset($_GET['success_msg'])) {
    $success_message = htmlspecialchars($_GET['success_msg']);
}

// ✅ Filter dan pencarian
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

$query = "SELECT * FROM pemesanan WHERE 1=1";
if ($status_filter !== 'all') {
    $query .= " AND status = :status";
}
if (!empty($search)) {
    $query .= " AND (pemohon LIKE :search OR nama_ruang LIKE :search OR keterangan LIKE :search)";
}
$query .= " ORDER BY created_at DESC";

$db->query($query);
if ($status_filter !== 'all') $db->bind(':status', $status_filter);
if (!empty($search)) $db->bind(':search', "%$search%");
$pemesanan = $db->resultSet();

// ✅ Statistik
$db->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'disetujui' THEN 1 ELSE 0 END) as disetujui,
    SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak
    FROM pemesanan");
$stats = $db->single();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permintaan Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
        }
        .text-dark-accent {
            color: #202938;
        }
        #successAlert {
            opacity: 1;
            transition: opacity 0.5s ease-out;
        }
        
        /* KONSISTENSI RESPONSIF */
        @media (min-width: 1024px) {
            .main { margin-left: 4rem; }
            .main.lg\:ml-60 { margin-left: 15rem; }
        }
        @media (max-width: 1023px) {
            .main { margin-left: 0 !important; }
        }
        #toggleBtn {
            position: relative;
            z-index: 51 !important;
        }
    </style>
</head>
<body class="bg-blue-100 flex min-h-screen text-gray-800">

<?php include 'sidebar_admin.php'; ?>

<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

<div id="mainContent" class="main flex-1 p-5 transition-all duration-300 lg:ml-16">
    
    <div class="header flex justify-between items-center mb-6">
        <button id="toggleBtn" class="bg-amber-500 hover:bg-amber-600 text-gray-900 p-2 rounded-lg transition-colors shadow-md relative z-50" onclick="toggleSidebar()">
             <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        
        <div class="text-sm text-gray-600 hidden sm:block">
            <span class="font-semibold">Data Permintaan</span>
        </div>
    </div>

    <?php if (!empty($success_message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-4 shadow-md" role="alert" id="successAlert">
            <span class="block sm:inline"><?= htmlspecialchars($success_message) ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white p-4 sm:p-6 rounded-xl shadow-lg">
        <div class="content-title mb-4 border-b pb-4">
            <h1 class="text-xl sm:text-2xl font-bold text-dark-accent flex items-center gap-2">
                <span class="text-2xl sm:text-3xl">📄</span>
                <span>Data Permintaan</span>
            </h1>
            <p class="text-gray-500 text-xs sm:text-sm mt-1">Daftar seluruh pemesanan ruang & fasilitas</p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 text-sm">
            
            <div class="stat-card p-4 rounded-xl text-white font-semibold shadow-md bg-gray-700">
                <div>Total Permintaan</div><div class="text-2xl font-bold mt-1"><?= $stats['total'] ?? 0 ?></div>
            </div>
            
            <div class="stat-card p-4 rounded-xl font-semibold shadow-md bg-yellow-400 text-gray-900">
                <div>Menunggu</div><div class="text-2xl font-bold mt-1"><?= $stats['pending'] ?? 0 ?></div>
            </div>
            
            <div class="stat-card p-4 rounded-xl text-white font-semibold shadow-md bg-green-500">
                <div>Disetujui</div><div class="text-2xl font-bold mt-1"><?= $stats['disetujui'] ?? 0 ?></div>
            </div>
            
            <div class="stat-card p-4 rounded-xl text-white font-semibold shadow-md bg-red-500">
                <div>Ditolak</div><div class="text-2xl font-bold mt-1"><?= $stats['ditolak'] ?? 0 ?></div>
            </div>
        </div>

        <form method="GET" class="flex flex-col sm:flex-row flex-wrap items-center gap-3 mb-6">
            <input type="text" name="search" placeholder="🔍 Cari pemohon / ruang..." value="<?= htmlspecialchars($search) ?>" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500 w-full sm:w-auto flex-grow text-sm">
            
            <select name="status" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500 w-full sm:w-auto text-sm">
                <option value="all" <?= $status_filter==='all'?'selected':'' ?>>Semua Status</option>
                <option value="pending" <?= $status_filter==='pending'?'selected':'' ?>>Menunggu</option>
                <option value="disetujui" <?= $status_filter==='disetujui'?'selected':'' ?>>Disetujui</option>
                <option value="ditolak" <?= $status_filter==='ditolak'?'selected':'' ?>>Ditolak</option>
            </select>
            <button type="submit" class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded-lg font-semibold transition-colors w-full sm:w-auto text-sm">Filter</button>
        </form>

        <div class="hidden lg:block table-container overflow-x-auto shadow-md rounded-xl border border-gray-200">
            <table class="min-w-full leading-normal">
                <thead>
                    <tr class="bg-gray-700 text-white whitespace-nowrap">
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Pemohon</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Ruang</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Mulai</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Selesai</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Keterangan</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pemesanan)): ?>
                        <tr><td colspan="8" class="text-center p-6 text-gray-500">Tidak ada data permintaan.</td></tr>
                    <?php else: foreach($pemesanan as $i=>$r): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 border-b border-gray-200 text-sm"><?= $i+1 ?></td>
                            <td class="px-4 py-3 border-b border-gray-200 text-sm font-medium"><?= htmlspecialchars($r['pemohon']) ?></td>
                            <td class="px-4 py-3 border-b border-gray-200 text-sm"><?= htmlspecialchars($r['nama_ruang']) ?></td>
                            <td class="px-4 py-3 border-b border-gray-200 whitespace-nowrap text-xs"><?= date('d/m/Y', strtotime($r['tanggal_mulai'])) ?></td>
                            <td class="px-4 py-3 border-b border-gray-200 whitespace-nowrap text-xs"><?= date('d/m/Y', strtotime($r['tanggal_selesai'])) ?></td>
                            <td class="px-4 py-3 border-b border-gray-200">
                                <?php
                                    $status = strtolower($r['status']);
                                    $status_class = '';
                                    if ($status === 'pending') $status_class = 'bg-yellow-100 text-yellow-800';
                                    elseif ($status === 'disetujui') $status_class = 'bg-green-100 text-green-800';
                                    elseif ($status === 'ditolak') $status_class = 'bg-red-100 text-red-800';
                                ?>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $status_class ?>"><?= ucfirst($r['status']) ?></span>
                            </td>
                            <td class="px-4 py-3 border-b border-gray-200 max-w-xs truncate text-xs text-gray-600"><?= htmlspecialchars($r['keterangan']) ?></td>
                            <td class="px-4 py-3 border-b border-gray-200 text-center">
                                <div class="flex gap-2 justify-center flex-wrap">
                                    <a href="detailpermintaan_admin.php?id=<?= $r['id'] ?>" class="px-3 py-1 text-xs font-semibold rounded-lg bg-gray-500 hover:bg-gray-600 text-white transition-colors">Detail</a>
                                    <?php if (strtolower($r['status']) === 'pending'): ?>
                                        <a href="?action=approve&id=<?= $r['id'] ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>" class="px-3 py-1 text-xs font-semibold rounded-lg bg-green-500 hover:bg-green-600 text-white transition-colors" onclick="return confirm('Setujui pemesanan ini?')">Setujui</a>
                                        <a href="?action=reject&id=<?= $r['id'] ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>" class="px-3 py-1 text-xs font-semibold rounded-lg bg-red-500 hover:bg-red-600 text-white transition-colors" onclick="return confirm('Tolak pemesanan ini?')">Tolak</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="lg:hidden space-y-4 pt-4">
            <?php if (empty($pemesanan)): ?>
                <div class="text-center p-6 text-gray-500 bg-gray-50 rounded-lg">Tidak ada data permintaan.</div>
            <?php else: foreach($pemesanan as $i=>$r): ?>
            <div class="bg-gray-50 p-4 rounded-xl shadow-sm border border-gray-200">
                <div class="flex justify-between items-start mb-3 border-b pb-2">
                    <div>
                        <div class="text-xs text-gray-500">No. <?= $i+1 ?></div>
                        <strong class="text-base text-gray-900"><?= htmlspecialchars($r['nama_ruang']) ?></strong>
                        <div class="text-sm text-gray-600">Pemohon: <?= htmlspecialchars($r['pemohon']) ?></div>
                    </div>
                    <?php
                        $status = strtolower($r['status']);
                        $status_class = '';
                        $text_color = '';
                        if ($status === 'pending') {$status_class = 'bg-yellow-400'; $text_color = 'text-gray-900';}
                        elseif ($status === 'disetujui') {$status_class = 'bg-green-500'; $text_color = 'text-white';}
                        elseif ($status === 'ditolak') {$status_class = 'bg-red-500'; $text_color = 'text-white';}
                    ?>
                    <span class="px-3 py-1 rounded-full text-xs font-bold <?= $status_class ?> <?= $text_color ?> whitespace-nowrap"><?= ucfirst($r['status']) ?></span>
                </div>
                
                <div class="text-xs text-gray-700 mb-3 space-y-1">
                    <div>Mulai: <span class="font-medium"><?= date('d/m/Y', strtotime($r['tanggal_mulai'])) ?></span></div>
                    <div>Selesai: <span class="font-medium"><?= date('d/m/Y', strtotime($r['tanggal_selesai'])) ?></span></div>
                    <div class="truncate">Keterangan: <span class="italic"><?= htmlspecialchars($r['keterangan']) ?></span></div>
                </div>
                
                <div class="flex gap-2 justify-start flex-wrap">
                    <a href="detailpermintaan_admin.php?id=<?= $r['id'] ?>" class="px-3 py-2 text-xs font-bold rounded-lg bg-gray-500 hover:bg-gray-600 text-white transition-colors flex-1 text-center">Detail</a>
                    <?php if (strtolower($r['status']) === 'pending'): ?>
                        <a href="?action=approve&id=<?= $r['id'] ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>" class="px-3 py-2 text-xs font-bold rounded-lg bg-green-500 hover:bg-green-600 text-white transition-colors flex-1 text-center" onclick="return confirm('Setujui pemesanan ini?')">Setujui</a>
                        <a href="?action=reject&id=<?= $r['id'] ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>" class="px-3 py-2 text-xs font-bold rounded-lg bg-red-500 hover:bg-red-600 text-white transition-colors flex-1 text-center" onclick="return confirm('Tolak pemesanan ini?')">Tolak</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>

    </div>
</div>

<script>
// PENTING: Fungsi toggleSidebar() yang konsisten dan responsif
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    const overlay = document.getElementById('sidebarOverlay');
    const is_desktop = window.innerWidth >= 1024; // Tailwind's 'lg' breakpoint

    if (is_desktop) {
        // Pada Desktop: Toggle width dan margin
        sidebar.classList.toggle('lg:w-60');
        sidebar.classList.toggle('lg:w-16');
        main.classList.toggle('lg:ml-60');
        main.classList.toggle('lg:ml-16');
    } else {
        // Pada Mobile: Toggle transform (overlay)
        sidebar.classList.toggle('translate-x-0');
        sidebar.classList.toggle('-translate-x-full');
        if (overlay) {
            overlay.classList.toggle('hidden');
        }
    }

    const is_expanded = sidebar.classList.contains('lg:w-60') || sidebar.classList.contains('translate-x-0');
    
    // Asumsi fungsi updateSidebarVisibility ada di sidebar_admin.php
    if (typeof updateSidebarVisibility === 'function') {
        updateSidebarVisibility(is_expanded);
    }
    
    localStorage.setItem('sidebarStatus', is_expanded ? 'open' : 'collapsed');
}

document.addEventListener("DOMContentLoaded", ()=>{
    const sidebar = document.getElementById("sidebar");
    const main = document.getElementById("mainContent");
    const overlay = document.getElementById('sidebarOverlay');
    const status = localStorage.getItem('sidebarStatus');
    const is_desktop = window.innerWidth >= 1024;

    // Menerapkan status sidebar yang tersimpan saat load
    if (status === 'open') {
        if (is_desktop) {
            main.classList.add('lg:ml-60');
            main.classList.remove('lg:ml-16');
            sidebar.classList.add('lg:w-60');
            sidebar.classList.remove('lg:w-16');
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
        } else {
            sidebar.classList.remove('translate-x-0');
            sidebar.classList.add('-translate-x-full');
            if (overlay) overlay.classList.add('hidden');
        }
    }

    // Auto hide success message
    const successAlert = document.getElementById('successAlert');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.opacity = '0'; 
            setTimeout(() => { successAlert.style.display = 'none'; }, 500);
        }, 6000);
    }
    
    // Close sidebar when clicking overlay
    if (overlay) {
        overlay.addEventListener('click', toggleSidebar);
    }
});
</script>
</body>
</html>