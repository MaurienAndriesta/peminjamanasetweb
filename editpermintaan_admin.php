<?php
require_once 'config/database.php';
$db = new Database();

// === INISIALISASI VARIABEL AWAL ===
$pemesanan = null;
$error_fetch = null;
$success_message = null;
// ===================================

if (!isset($_GET['id'])) {
    die("ID tidak ditemukan.");
}

$id = (int)$_GET['id'];
$db->query("SELECT * FROM pemesanan WHERE id = :id");
$db->bind(':id', $id);
$pemesanan = $db->single();

if (!$pemesanan) {
    $pemesanan = null;
    $error_fetch = "Data pemesanan tidak ditemukan di database.";
}

// Proses update status & catatan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($pemesanan) { // Pastikan data ada sebelum update
        $status = $_POST['status'];
        $catatan_admin = trim($_POST['catatan_admin'] ?? '');

        // Validasi Status (untuk keamanan tambahan)
        if (!in_array($status, ['pending', 'disetujui', 'ditolak'])) {
            $status = 'pending';
        }

        $db->query("UPDATE pemesanan 
                    SET status = :status, catatan_admin = :catatan_admin 
                    WHERE id = :id");
        $db->bind(':status', $status);
        $db->bind(':catatan_admin', $catatan_admin);
        $db->bind(':id', $id);
        
        if ($db->execute()) {
            header("Location: permintaan_admin.php?success_action=status_updated");
            exit;
        } else {
            $success_message = "❌ Gagal menyimpan perubahan status.";
        }
    }
}

// Cek file surat (Perlu diulang untuk memastikan data terbaru setelah POST)
$fileSurat = null;
$ext = null;
if ($pemesanan && !empty($pemesanan['file_surat'])) {
    $path = 'uploads/' . $pemesanan['file_surat']; 
    if (file_exists($path)) {
        $fileSurat = $path;
    }
}
$ext = $fileSurat ? strtolower(pathinfo($fileSurat, PATHINFO_EXTENSION)) : null;

// Mengambil data pemesanan terakhir (setelah POST, untuk memastikan tampilan akurat)
if ($pemesanan) {
    $db->query("SELECT * FROM pemesanan WHERE id = :id");
    $db->bind(':id', $id);
    $pemesanan = $db->single();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Permintaan - <?= htmlspecialchars($pemesanan['pemohon'] ?? 'N/A') ?></title>
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

        .detail-card-item {
            background-color: #f9fafb;
            border-radius: 0.5rem;
            padding: 0.6rem 0.75rem; 
            border: 1px solid #f3f4f6;
        }
        .pdf-preview {
            width: 100%; 
            height: 300px; 
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
            <span class="font-semibold">Edit Permintaan</span>
        </div>
    </div>
    
    <?php if (!empty($success_message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-4 shadow-md" role="alert" id="successAlert">
            <span class="block sm:inline"><?= htmlspecialchars($success_message) ?></span>
        </div>
    <?php endif; ?>


    <div class="content bg-white p-4 sm:p-6 rounded-xl shadow-lg">
        <h1 class="text-xl sm:text-2xl font-bold mb-4 pb-2 border-b text-dark-accent flex items-center gap-2">
             <span class="text-2xl">✏️</span> Edit Permintaan dari <?= htmlspecialchars($pemesanan['pemohon'] ?? 'N/A') ?>
        </h1>

        <?php if ($error_fetch): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-4 shadow-md" role="alert">
                <span class="block sm:inline">❌ <?= htmlspecialchars($error_fetch) ?></span>
                <a href="permintaan_admin.php" class="inline-block mt-3 px-4 py-2 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg transition-colors text-sm">← Kembali ke Daftar Permintaan</a>
            </div>
        <?php endif; ?>


        <?php if ($pemesanan): ?>
        <div class="cards grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <div class="card p-4 sm:p-6 bg-gray-50 border border-gray-200 rounded-xl shadow-md">
                <h2 class="text-lg sm:text-xl font-semibold mb-4 pb-2 border-b text-dark-accent">Detail Pemesanan</h2>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div class="detail-card-item">
                        <div class="detail-label font-semibold text-xs text-gray-600">Nama Pemohon</div>
                        <div class="detail-value text-base font-medium text-dark-accent break-words"><?= htmlspecialchars($pemesanan['pemohon'] ?? '-') ?></div>
                    </div>
                    <div class="detail-card-item">
                        <div class="detail-label font-semibold text-xs text-gray-600">Kategori</div>
                        <div class="detail-value text-base font-medium text-dark-accent"><?= htmlspecialchars($pemesanan['kategori'] ?? '-') ?></div>
                    </div>

                    <div class="detail-card-item">
                        <div class="detail-label font-semibold text-xs text-gray-600">Ruang / Lab</div>
                        <div class="detail-value text-base font-medium text-dark-accent"><?= htmlspecialchars($pemesanan['nama_ruang'] ?? '-') ?></div>
                    </div>
                    <div class="detail-card-item">
                        <div class="detail-label font-semibold text-xs text-gray-600">Tgl. Mulai</div>
                        <div class="detail-value text-base font-medium text-dark-accent"><?= date('d/m/Y', strtotime($pemesanan['tanggal_mulai'] ?? '')) ?></div>
                    </div>

                    <div class="detail-card-item">
                        <div class="detail-label font-semibold text-xs text-gray-600">Tgl. Selesai</div>
                        <div class="detail-value text-base font-medium text-dark-accent"><?= date('d/m/Y', strtotime($pemesanan['tanggal_selesai'] ?? '')) ?></div>
                    </div>
                    <div class="detail-card-item">
                        <div class="detail-label font-semibold text-xs text-gray-600">Pilihan Tarif</div>
                        <div class="detail-value text-base font-medium text-dark-accent"><?= htmlspecialchars($pemesanan['tarif_pilihan'] ?? '-') ?></div>
                    </div>

                    <div class="detail-card-item col-span-1 sm:col-span-2">
                        <div class="detail-label font-semibold text-xs text-gray-600">Harga Total</div>
                        <div class="detail-value text-xl font-bold text-green-600">Rp <?= number_format($pemesanan['harga_tarif'] ?? 0, 0, ',', '.') ?></div>
                    </div>
                </div>
                
                <div class="pt-4 mt-6 border-t border-gray-300">
                    <h3 class="text-base font-semibold text-dark-accent mb-2">Keterangan Pemohon</h3>
                    <div class="p-3 bg-white border border-gray-200 rounded-lg">
                        <p class="text-sm text-dark-accent whitespace-pre-wrap"><?= htmlspecialchars($pemesanan['keterangan'] ?? 'Tidak ada keterangan.') ?></p>
                    </div>
                </div>

                <div class="file-preview pt-4 mt-6 border-t border-gray-300">
                    <h3 class="text-base font-semibold text-dark-accent mb-3">File Surat Pendukung</h3>
                    <?php if ($fileSurat): ?>
                        <div class="p-3 bg-white border border-gray-200 rounded-lg flex flex-col items-start">
                            <span class="text-sm text-gray-600 mb-2">Dokumen tersedia untuk diunduh:</span>
                            <a href="<?= $fileSurat ?>" download class="inline-block px-4 py-2 bg-amber-500 hover:bg-amber-600 text-gray-900 rounded-lg text-sm font-semibold transition-colors">
                                ⬇ Download Surat (<?= htmlspecialchars($pemesanan['file_surat']) ?>)
                            </a>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 p-3 bg-white border rounded-lg text-sm"><i>File surat tidak ditemukan.</i></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card p-4 sm:p-6 bg-white rounded-xl shadow-lg border border-gray-200">
                <h2 class="text-lg sm:text-xl font-semibold mb-4 pb-2 border-b text-dark-accent">Ubah Status & Catatan</h2>
                
                <form method="POST" class="space-y-4">
                    <div class="form-group">
                        <label for="status" class="font-semibold text-sm text-dark-accent block mb-1">Status Permintaan</label>
                        <select name="status" id="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500 text-sm">
                            <option value="pending" <?= ($pemesanan['status'] ?? '') == "pending" ? "selected" : "" ?>>Pending</option>
                            <option value="disetujui" <?= ($pemesanan['status'] ?? '') == "disetujui" ? "selected" : "" ?>>Disetujui</option>
                            <option value="ditolak" <?= ($pemesanan['status'] ?? '') == "ditolak" ? "selected" : "" ?>>Ditolak</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="catatan_admin" class="font-semibold text-sm text-dark-accent block mb-1">Catatan Admin</label>
                        <textarea name="catatan_admin" id="catatan_admin" placeholder="Tambahkan catatan persetujuan/penolakan..." class="w-full p-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500 resize-y min-h-[120px] text-sm"><?= htmlspecialchars($pemesanan['catatan_admin'] ?? '') ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">Catatan ini akan terlihat oleh pemohon.</p>
                    </div>

                    <div class="actions flex gap-3 pt-4 border-t border-gray-200">
                        <a href="permintaan_admin.php" class="btn btn-back bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-semibold transition-colors text-sm">⬅ Kembali</a>
                        <button type="submit" class="btn btn-save bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold transition-colors text-sm">Simpan & Update</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// === SCRIPT ASLI KAMU ===
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

document.addEventListener("DOMContentLoaded", () => {
    const sidebar = document.getElementById("sidebar");
    const main = document.getElementById("mainContent");
    const overlay = document.getElementById('sidebarOverlay');
    const status = localStorage.getItem('sidebarStatus');
    const is_desktop = window.innerWidth >= 1024;

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

    if (overlay) overlay.addEventListener('click', toggleSidebar);

    const successAlert = document.getElementById('successAlert');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.opacity = '0'; 
            setTimeout(() => { successAlert.style.display = 'none'; }, 500);
        }, 6000);
    }
});

// === ✨ Tambahan kecil untuk memperbaiki sidebar setelah resize layar ===
window.addEventListener('resize', () => {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    const overlay = document.getElementById('sidebarOverlay');
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
        } else {
            sidebar.classList.add('lg:w-16');
            sidebar.classList.remove('lg:w-60');
            main.classList.add('lg:ml-16');
            main.classList.remove('lg:ml-60');
        }
    } else {
        sidebar.classList.remove('lg:w-60', 'lg:w-16');
        main.classList.remove('lg:ml-60', 'lg:ml-16');

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
