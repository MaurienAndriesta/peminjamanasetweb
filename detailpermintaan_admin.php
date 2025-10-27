<?php
require_once 'config/database.php';
$db = new Database();

if (!isset($_GET['id'])) {
    die("ID tidak ditemukan.");
}

$id = (int)$_GET['id'];
$db->query("SELECT * FROM pemesanan WHERE id = :id");
$db->bind(':id', $id);
$pemesanan = $db->single();

if (!$pemesanan) {
    $pemesanan = null; 
    $error_fetch = "Data pemesanan dengan ID " . $id . " tidak ditemukan di database.";
} else {
    $error_fetch = null;
}

$success_message = null;
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'disetujui') {
        $success_message = "✅ Pemesanan berhasil disetujui.";
    } elseif ($_GET['success'] === 'ditolak') {
        $success_message = "❌ Pemesanan berhasil ditolak.";
    }
}

// Proses setujui / tolak HANYA jika data ditemukan
if ($pemesanan && isset($_GET['action'])) {
    $action = $_GET['action'];
    if ($action === 'approve') {
        $db->query("UPDATE pemesanan SET status = 'disetujui' WHERE id = :id");
        $db->bind(':id', $id);
        $db->execute();
        header("Location: detailpermintaan_admin.php?id=" . $id . "&success=disetujui");
        exit;
    } elseif ($action === 'reject') {
        $db->query("UPDATE pemesanan SET status = 'ditolak' WHERE id = :id");
        $db->bind(':id', $id);
        $db->execute();
        header("Location: detailpermintaan_admin.php?id=" . $id . "&success=ditolak");
        exit;
    }
}

// Cek file surat
$fileSurat = null;
$ext = null;
if ($pemesanan && !empty($pemesanan['file_surat'])) {
    $path = 'uploads/' . $pemesanan['file_surat']; 
    if (file_exists($path)) {
        $fileSurat = $path;
        $ext = strtolower(pathinfo($fileSurat, PATHINFO_EXTENSION));
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Permintaan Pemesanan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS khusus untuk integrasi dan font */
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

        /* Estetika: Style untuk setiap item detail */
        .detail-card {
            background-color: #f9fafb;
            border-radius: 0.5rem;
            padding: 0.6rem 0.75rem; 
            border: 1px solid #f3f4f6;
        }
        /* Tampilan file */
        .file-name-display {
            font-weight: 600;
            color: #1d4ed8; /* blue-700 */
        }
    </style>
</head>
<body class="bg-blue-100 flex min-h-screen text-gray-800">

<?php include 'sidebar_admin.php'; ?>

<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

<div id="mainContent" class="main flex-1 p-5 transition-all duration-300 lg:ml-16">
    
    <div class="flex justify-between items-center mb-4 sm:mb-6">
        <button id="toggleBtn" class="bg-amber-500 hover:bg-amber-600 text-gray-900 p-2 rounded-lg transition-colors shadow-md relative z-50" onclick="toggleSidebar()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        
        <div class="text-sm text-gray-600 hidden sm:block">
            <span class="font-semibold">Detail Permintaan</span>
        </div>
    </div>

    <?php if (!empty($success_message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-4 shadow-md" role="alert" id="successAlert">
            <span class="block sm:inline"><?= htmlspecialchars($success_message) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error_fetch): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-4 shadow-md" role="alert">
            <span class="block sm:inline">❌ <?= htmlspecialchars($error_fetch) ?></span>
            <a href="permintaan_admin.php" class="inline-block mt-3 px-4 py-2 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg transition-colors text-sm">← Kembali ke Daftar Permintaan</a>
        </div>
    <?php endif; ?>

    <?php if ($pemesanan): ?>
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow-lg grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
        
        <div class="section lg:col-span-2 space-y-6">
            <h2 class="text-xl font-bold mb-4 pb-2 border-b border-gray-200 text-dark-accent">Informasi Pemesanan</h2>
            
            <div class="grid grid-cols-2 gap-3 sm:gap-4 text-sm">
                <div class="detail-card">
                    <span class="label text-xs font-semibold text-gray-600">Nama Pemohon</span>
                    <span class="value block text-base font-medium text-dark-accent break-words"><?= htmlspecialchars($pemesanan['pemohon'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-card">
                    <span class="label text-xs font-semibold text-gray-600">Kategori</span>
                    <span class="value block text-base font-medium text-dark-accent"><?= htmlspecialchars($pemesanan['kategori'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-card">
                    <span class="label text-xs font-semibold text-gray-600">Ruang/Fasilitas</span>
                    <span class="value block text-base font-medium text-dark-accent"><?= htmlspecialchars($pemesanan['nama_ruang'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-card">
                    <span class="label text-xs font-semibold text-gray-600">Tanggal Mulai</span>
                    <span class="value block text-base font-medium text-dark-accent"><?= date('d/m/Y', strtotime($pemesanan['tanggal_mulai'] ?? '')) ?></span>
                </div>
                <div class="detail-card">
                    <span class="label text-xs font-semibold text-gray-600">Tanggal Selesai</span>
                    <span class="value block text-base font-medium text-dark-accent"><?= date('d/m/Y', strtotime($pemesanan['tanggal_selesai'] ?? '')) ?></span>
                </div>
                <div class="detail-card">
                    <span class="label text-xs font-semibold text-gray-600">Tarif Pilihan</span>
                    <span class="value block text-base font-medium text-dark-accent"><?= htmlspecialchars($pemesanan['tarif_pilihan'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-card col-span-2">
                    <span class="label text-xs font-semibold text-gray-600">Harga Total</span>
                    <span class="value block text-xl font-bold text-green-600">Rp <?= number_format($pemesanan['harga_tarif'] ?? 0,0,',','.') ?></span>
                </div>
            </div>
            
            <div class="pt-4 border-t border-gray-100 mt-6">
                <h3 class="text-lg sm:text-xl font-bold text-dark-accent mb-3">Keterangan Tambahan</h3>
                <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                    <p class="text-sm sm:text-base text-dark-accent whitespace-pre-wrap"><?= htmlspecialchars($pemesanan['keterangan'] ?? '') ?></p>
                </div>
            </div>

            <div class="file-preview pt-4 border-t border-gray-200 mt-6">
                <h3 class="text-lg sm:text-xl font-bold mb-3 text-dark-accent">File Surat</h3>
                
                <div class="p-4 bg-gray-50 border rounded-lg">
                    <?php if ($fileSurat): ?>
                        <p class="mb-2 text-sm text-gray-600">Dokumen tersedia untuk diunduh:</p>
                        <span class="file-name-display block font-semibold text-blue-700 break-words mb-3 text-base">
                            📄 <?= htmlspecialchars($pemesanan['file_surat']) ?>
                        </span>
                        
                        <a href="<?= $fileSurat ?>" download class="inline-block mt-1 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-gray-900 font-semibold rounded-lg transition-colors shadow-md text-sm">
                            ⬇ Download Surat
                        </a>
                    <?php else: ?>
                        <p class="text-gray-500 italic">File surat tidak ditemukan.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="section lg:col-span-1 border-t lg:border-t-0 pt-6 lg:pt-0">
            <h2 class="text-xl font-bold mb-4 pb-2 border-b border-gray-200 text-dark-accent">Aksi & Status</h2>
            
            <?php
                $status = strtolower($pemesanan['status'] ?? 'N/A');
                $status_class = '';
                if ($status === 'pending') $status_class = 'bg-yellow-100 text-yellow-800 border-yellow-300';
                elseif ($status === 'disetujui') $status_class = 'bg-green-100 text-green-800 border-green-300';
                elseif ($status === 'ditolak') $status_class = 'bg-red-100 text-red-800 border-red-300';
                else $status_class = 'bg-gray-100 text-gray-800 border-gray-300';
            ?>
            <div class="status-box p-4 rounded-xl text-center font-bold border <?= $status_class ?>">
                <span class="text-sm block mb-1 text-gray-600">Status saat ini:</span>
                <p class="text-3xl mt-1"><?= ucfirst($status) ?></p>
            </div>

            <div class="actions mt-6 flex flex-col gap-3">
                <a href="permintaan_admin.php" class="px-4 py-3 text-center rounded-lg font-semibold bg-gray-500 hover:bg-gray-600 text-white transition-colors shadow-md">⬅ Kembali ke Daftar</a>
                
                <?php if ($status === 'pending'): ?>
                    <a href="?id=<?= $pemesanan['id'] ?>&action=approve" class="px-4 py-3 text-center rounded-lg font-semibold bg-green-500 hover:bg-green-600 text-white transition-colors shadow-md" onclick="return confirm('Setujui pemesanan ini?')">✅ Setujui Permintaan</a>
                    <a href="?id=<?= $pemesanan['id'] ?>&action=reject" class="px-4 py-3 text-center rounded-lg font-semibold bg-red-500 hover:bg-red-600 text-white transition-colors shadow-md" onclick="return confirm('Tolak pemesanan ini?')">❌ Tolak Permintaan</a>
                <?php endif; ?>
                
                <a href="editpermintaan_admin.php?id=<?= $pemesanan['id'] ?>" class="px-4 py-3 text-center rounded-lg font-semibold bg-blue-500 hover:bg-blue-600 text-white transition-colors mt-4 shadow-md">✏️ Edit Permintaan</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
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
    
    if (typeof updateSidebarVisibility === 'function') {
        updateSidebarVisibility(is_expanded);
    }
    
    localStorage.setItem('sidebarStatus', is_expanded ? 'open' : 'collapsed');
}

document.addEventListener("DOMContentLoaded", () => {
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
    
    // Close sidebar when clicking overlay
    if (overlay) {
        overlay.addEventListener('click', toggleSidebar);
    }


    // Auto hide success message
    const successAlert = document.getElementById('successAlert');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.opacity = '0'; 
            setTimeout(() => { successAlert.style.display = 'none'; }, 500);
        }, 6000);
    }
});
</script>
</body>
</html>