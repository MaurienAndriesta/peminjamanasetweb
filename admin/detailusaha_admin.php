<?php
// Include database configuration
require_once 'config/database.php';

// Initialize database connection
$db = new Database();

// Get usaha ID from URL parameter
$usaha_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($usaha_id <= 0) {
    header('Location: datausaha_admin.php');
    exit;
}

// Query untuk mendapatkan detail usaha
$db->query("SELECT id, nama, kapasitas, lokasi, tarif_internal, tarif_eksternal, keterangan, gambar, created_at, updated_at 
            FROM usaha 
            WHERE id = :id AND status = 'aktif'");
$db->bind(':id', $usaha_id);

$usaha_detail = [];
$error_message = null;

try {
    $usaha_detail = $db->single();
    
    if (!$usaha_detail) {
        $error_message = "Data usaha tidak ditemukan atau sudah tidak aktif.";
    }
} catch (Exception $e) {
    $error_message = "Terjadi kesalahan saat mengambil data: Pastikan tabel 'usaha' sudah memiliki kolom: gambar, keterangan, tarif_internal, dan tarif_eksternal.";
    $usaha_detail = [];
}

// Process keterangan (split by newline for display)
$keterangan_array = [];
if (isset($usaha_detail['keterangan']) && $usaha_detail['keterangan']) {
    $keterangan_text = str_replace('\n', "\n", $usaha_detail['keterangan']);
    $keterangan_array = explode("\n", $keterangan_text);
    $keterangan_array = array_filter($keterangan_array, 'trim'); 
}

// Process gambar (ambil gambar pertama)
$image_path = '';
if (isset($usaha_detail['gambar']) && !empty($usaha_detail['gambar'])) {
    $images = explode(',', $usaha_detail['gambar']);
    $image_path = trim($images[0]);
}

$is_usaha_available = !empty($usaha_detail);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Usaha - Admin Pengelola</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS khusus untuk integrasi dan font */
        body { 
            font-family: 'Poppins', sans-serif; 
        }
        /* Style untuk list keterangan */
        .keterangan-list {
            list-style: none; padding: 0; margin: 0;
            counter-reset: keterangan-counter;
        }
        .keterangan-list li {
            margin-bottom: 0.5rem; padding-left: 2rem;
            color: #4b5563; line-height: 1.6; position: relative;
            counter-increment: keterangan-counter;
        }
        .keterangan-list li::before {
            content: counter(keterangan-counter) ". ";
            position: absolute; left: 0; top: 0;
            font-weight: 600; color: #f59e0b; /* Amber */
        }
    </style>
</head>
<body class="bg-blue-100 flex min-h-screen text-gray-800">

<?php include 'sidebar_admin.php'; ?>

<div id="mainContent" class="flex-1 p-5 ml-16 transition-all duration-300">
    
    <div class="header flex justify-start items-center mb-6">
        <button class="bg-amber-500 hover:bg-amber-600 text-gray-900 p-2 rounded-lg transition-colors" onclick="toggleSidebar()">☰</button>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 shadow-md" role="alert">
            ❌ <?= htmlspecialchars($error_message) ?>
            <div class="text-center pt-4">
                <a href="datausaha_admin.php" class="bg-gray-700 hover:bg-gray-800 text-white font-semibold px-4 py-2 rounded-lg inline-block transition-colors">← Kembali ke Data Usaha</a>
            </div>
        </div>
    <?php elseif (!$is_usaha_available): ?>
        <div class="text-center p-16 text-gray-500 bg-white rounded-xl shadow-lg">⏳ Memuat data usaha...</div>
    <?php else: ?>
    
    <div class="bg-white p-6 rounded-xl shadow-lg">
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6 border-b pb-4">
            <div class="flex items-center gap-4">
                <a href="datausaha_admin.php" class="bg-gray-700 hover:bg-gray-800 text-white p-3 rounded-lg transition-colors">←</a>
                
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Detail Usaha: <?= htmlspecialchars($usaha_detail['nama']) ?></h2>
                    <p class="text-gray-500 text-sm">Informasi lengkap usaha</p>
                </div>
            </div>
            <a href="editusaha_admin.php?id=<?= $usaha_detail['id'] ?>" class="bg-amber-500 hover:bg-amber-600 text-gray-900 font-semibold px-6 py-3 rounded-lg shadow transition-colors w-full md:w-auto text-center">Edit Data</a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-4">
            
            <div class="space-y-6">
                <div class="room-image relative w-full h-72 overflow-hidden rounded-xl bg-gray-100 flex items-center justify-center shadow-md">
                    <?php if (!empty($usaha_detail['gambar'])): ?>
                        <img src="assets/images/<?= htmlspecialchars($image_path) ?>" 
                             alt="<?= htmlspecialchars($usaha_detail['nama']) ?>"
                             class="w-full h-full object-cover rounded-xl"
                             onerror="this.parentElement.innerHTML='<div class=text-gray-500>Gambar tidak tersedia</div>'">
                    <?php else: ?>
                        <div class="text-gray-500 text-center">📷<br>Gambar Usaha<br>Tidak Tersedia</div>
                    <?php endif; ?>
                </div>
                
                <div class="room-details space-y-4">
                    <h3 class="text-xl font-semibold border-b pb-2 text-gray-700">Informasi Dasar</h3>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="detail-item">
                            <label class="block font-semibold mb-1 text-sm text-gray-600">Nama Usaha</label>
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 text-gray-800"><?= htmlspecialchars($usaha_detail['nama']) ?></div>
                        </div>
                        <div class="detail-item">
                            <label class="block font-semibold mb-1 text-sm text-gray-600">Kapasitas</label>
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 text-gray-800"><?= htmlspecialchars($usaha_detail['kapasitas']) ?></div>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <label class="block font-semibold mb-1 text-sm text-gray-600">Lokasi</label>
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 text-gray-800"><?= htmlspecialchars($usaha_detail['lokasi']) ?></div>
                    </div>
                </div>
            </div>

            <div class="info-section space-y-6">
                
                <h3 class="text-xl font-semibold border-b pb-2 text-gray-700">Informasi Tarif (Per Hari)</h3>
                
                <?php if ($usaha_detail['tarif_internal'] > 0 || $usaha_detail['tarif_eksternal'] > 0): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-4 rounded-xl border border-amber-300 bg-amber-50">
                            <h4 class="text-base font-semibold text-gray-700 mb-2">💼 Tarif Sewa Internal</h4>
                            <div class="text-xl font-extrabold text-amber-700">
                                Rp <?= number_format($usaha_detail['tarif_internal'], 0, ',', '.') ?>
                            </div>
                        </div>

                        <div class="p-4 rounded-xl border border-amber-300 bg-amber-50">
                            <h4 class="text-base font-semibold text-gray-700 mb-2">🏢 Tarif Sewa Eksternal</h4>
                            <div class="text-xl font-extrabold text-amber-700">
                                Rp <?= number_format($usaha_detail['tarif_eksternal'], 0, ',', '.') ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="p-4 rounded-xl border border-green-300 bg-green-50">
                        <h4 class="text-base font-semibold text-gray-700 mb-2">Status Tarif</h4>
                        <div class="text-xl font-extrabold text-green-700">
                            Usaha ini bersifat Gratis
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($keterangan_array)): ?>
                <div class="keterangan pt-2">
                    <h4 class="text-xl font-semibold border-b pb-2 mb-4 text-gray-700">📋 Keterangan & Ketentuan</h4>
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <ol class="keterangan-list">
                            <?php foreach($keterangan_array as $item): ?>
                                <li><?= htmlspecialchars(trim($item)) ?></li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                </div>
                <?php endif; ?>

                <div class="metadata pt-2">
                    <h4 class="text-base font-semibold uppercase mb-3 text-gray-600">Informasi Sistem</h4>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div class="metadata-item">
                            <label class="text-xs uppercase font-medium text-gray-500">Dibuat Pada</label>
                            <div class="font-medium text-gray-700">
                                <?= date('d M Y, H:i', strtotime($usaha_detail['created_at'])) ?> WIB
                            </div>
                        </div>
                        <div class="metadata-item">
                            <label class="text-xs uppercase font-medium text-gray-500">Terakhir Diupdate</label>
                            <div class="font-medium text-gray-700">
                                <?= date('d M Y, H:i', strtotime($usaha_detail['updated_at'])) ?> WIB
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// PENTING: Fungsi toggleSidebar() harus sama persis dengan yang ada di sidebar_admin.php
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    const is_desktop = window.innerWidth >= 1024;

    if (is_desktop) {
        // Desktop: Toggle width
        sidebar.classList.toggle('lg:w-60');
        sidebar.classList.toggle('lg:w-16');
        main.classList.toggle('lg:ml-60');
        main.classList.toggle('lg:ml-16');
    } else {
        // Mobile: Toggle visibility
        sidebar.classList.toggle('translate-x-0');
        sidebar.classList.toggle('-translate-x-full');
    }

    const is_expanded = sidebar.classList.contains('lg:w-60') || sidebar.classList.contains('translate-x-0');
    
    if (typeof updateSidebarVisibility === 'function') {
        updateSidebarVisibility(is_expanded);
    }
    
    localStorage.setItem('sidebarStatus', is_expanded ? 'open' : 'collapsed');
}

// Initialization Sidebar State
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById("sidebar");
    const main = document.getElementById('mainContent');
    const storedStatus = localStorage.getItem('sidebarStatus');

    if (storedStatus === 'open') {
        sidebar.classList.add('w-60');
        sidebar.classList.remove('w-16');
        main.classList.add('ml-60');
        main.classList.remove('ml-16');
    } else {
        sidebar.classList.remove('w-60');
        sidebar.classList.add('w-16');
        main.classList.remove('ml-60');
        main.classList.add('ml-16');
    }
});
</script>
</body>
</html>