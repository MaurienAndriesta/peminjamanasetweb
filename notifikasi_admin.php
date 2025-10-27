<?php
require_once 'config/database.php';
$db = new Database();

/*
=========================================================
 NOTIFIKASI OTOMATIS DARI TABEL PEMESANAN
=========================================================
*/

$db->query("
    SELECT id, pemohon, nama_ruang, status, created_at, updated_at
    FROM pemesanan
    ORDER BY updated_at DESC, created_at DESC
");
$notifikasi = $db->resultSet();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi Admin - Admin Pengelola</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .text-dark-accent { color: #202938; }
        
        @media (min-width: 1024px) {
            .main { margin-left: 4rem; }
            .main.lg\:ml-60 { margin-left: 15rem; }
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .notification-card {
            animation: slideIn 0.4s ease-out forwards;
            opacity: 0;
        }
    </style>
</head>
<body class="bg-blue-100 flex min-h-screen">

<?php include 'sidebar_admin.php'; ?>

<div id="mainContent" class="main flex-1 p-5 transition-all duration-300 lg:ml-16">
    <!-- Header -->
    <div class="header flex justify-start items-center mb-6">
        <button class="bg-amber-500 hover:bg-amber-600 text-gray-900 p-2 rounded-lg transition-colors" onclick="toggleSidebar()">☰</button>
    </div>

    <!-- Content Wrapper -->
    <div class="max-w-6xl mx-auto">
        <!-- Page Header -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-6">
            <div class="flex items-center gap-4 mb-2">
                <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center text-3xl shadow-lg">
                    🔔
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-dark-accent">Notifikasi Pemesanan</h1>
                    <p class="text-gray-600 text-sm">Pantau status pemesanan ruangan secara real-time</p>
                </div>
            </div>
        </div>

        <!-- Stats Summary -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <?php
                $stats = [
                    'pending' => ['count' => 0, 'label' => 'Pending', 'icon' => '⏳', 'gradient' => 'from-orange-400 to-orange-600'],
                    'disetujui' => ['count' => 0, 'label' => 'Disetujui', 'icon' => '✓', 'gradient' => 'from-green-400 to-green-600'],
                    'ditolak' => ['count' => 0, 'label' => 'Ditolak', 'icon' => '✗', 'gradient' => 'from-red-400 to-red-600'],
                    'selesai' => ['count' => 0, 'label' => 'Selesai', 'icon' => '✔', 'gradient' => 'from-blue-400 to-blue-600']
                ];
                
                foreach ($notifikasi as $n) {
                    if (isset($stats[$n['status']])) {
                        $stats[$n['status']]['count']++;
                    }
                }
            ?>
            <?php foreach ($stats as $key => $stat): ?>
                <div class="bg-gradient-to-br <?= $stat['gradient'] ?> text-white p-6 rounded-xl shadow-lg transform hover:scale-105 transition-transform">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-3xl"><?= $stat['icon'] ?></span>
                        <span class="text-4xl font-bold"><?= $stat['count'] ?></span>
                    </div>
                    <div class="text-sm font-semibold opacity-90"><?= $stat['label'] ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Notifications List -->
        <?php if (count($notifikasi) > 0): ?>
            <div class="space-y-3">
                <?php foreach ($notifikasi as $index => $n): ?>
                    <?php
                        // Konfigurasi per status
                        $config = match ($n['status']) {
                            'pending' => [
                                'bg' => 'bg-orange-50',
                                'border' => 'border-orange-400',
                                'badge' => 'bg-orange-500',
                                'icon' => '⏳',
                                'iconBg' => 'bg-orange-100',
                                'iconText' => 'text-orange-600',
                                'title' => 'Menunggu Persetujuan'
                            ],
                            'disetujui' => [
                                'bg' => 'bg-green-50',
                                'border' => 'border-green-400',
                                'badge' => 'bg-green-500',
                                'icon' => '✓',
                                'iconBg' => 'bg-green-100',
                                'iconText' => 'text-green-600',
                                'title' => 'Pemesanan Disetujui'
                            ],
                            'ditolak' => [
                                'bg' => 'bg-red-50',
                                'border' => 'border-red-400',
                                'badge' => 'bg-red-500',
                                'icon' => '✗',
                                'iconBg' => 'bg-red-100',
                                'iconText' => 'text-red-600',
                                'title' => 'Pemesanan Ditolak'
                            ],
                            'selesai' => [
                                'bg' => 'bg-blue-50',
                                'border' => 'border-blue-400',
                                'badge' => 'bg-blue-500',
                                'icon' => '✔',
                                'iconBg' => 'bg-blue-100',
                                'iconText' => 'text-blue-600',
                                'title' => 'Pemesanan Selesai'
                            ],
                            default => [
                                'bg' => 'bg-gray-50',
                                'border' => 'border-gray-400',
                                'badge' => 'bg-gray-500',
                                'icon' => '📢',
                                'iconBg' => 'bg-gray-100',
                                'iconText' => 'text-gray-600',
                                'title' => 'Status Terbaru'
                            ]
                        };

                        $link = "detailpermintaan_admin.php?id=" . urlencode($n['id']);
                        $timestamp = strtotime($n['updated_at'] ?: $n['created_at']);
                        $tanggal = date('d M Y', $timestamp);
                        $waktu = date('H:i', $timestamp);
                    ?>

                    <a href="<?= $link ?>" 
                       class="notification-card block bg-white rounded-xl shadow-md hover:shadow-xl border-l-4 <?= $config['border'] ?> overflow-hidden transition-all duration-300 hover:-translate-y-1"
                       style="animation-delay: <?= $index * 0.1 ?>s;">
                        <div class="p-5">
                            <div class="flex items-start gap-4">
                                <!-- Icon -->
                                <div class="flex-shrink-0 w-12 h-12 <?= $config['iconBg'] ?> rounded-lg flex items-center justify-center text-2xl <?= $config['iconText'] ?>">
                                    <?= $config['icon'] ?>
                                </div>

                                <!-- Content -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-4 mb-2">
                                        <div class="flex-1">
                                            <h3 class="font-bold text-lg text-gray-800 mb-1 line-clamp-1">
                                                <?= htmlspecialchars($n['nama_ruang']) ?>
                                            </h3>
                                            <p class="text-sm text-gray-600 font-medium mb-1">
                                                <?= $config['title'] ?>
                                            </p>
                                        </div>
                                        <!-- Badge -->
                                        <span class="flex-shrink-0 px-3 py-1 rounded-full text-white text-xs font-bold <?= $config['badge'] ?> uppercase">
                                            <?= ucfirst($n['status']) ?>
                                        </span>
                                    </div>

                                    <!-- Pemohon & Timestamp -->
                                    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-base">👤</span>
                                            <span class="font-medium"><?= htmlspecialchars($n['pemohon']) ?></span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-base">📅</span>
                                            <span><?= $tanggal ?></span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-base">🕐</span>
                                            <span><?= $waktu ?> WIB</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Hover Indicator -->
                        <div class="h-1 <?= $config['bg'] ?> transition-all duration-300"></div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Empty State -->
            <div class="bg-white rounded-2xl shadow-lg p-16 text-center">
                <div class="w-32 h-32 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-6 text-6xl">
                    📭
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-3">Tidak Ada Notifikasi</h3>
                <p class="text-gray-600 max-w-md mx-auto">
                    Belum ada pemesanan atau perubahan status terbaru. Notifikasi akan muncul di sini saat ada aktivitas baru.
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    const is_desktop = window.innerWidth >= 1024;

    if (is_desktop) {
        sidebar.classList.toggle('lg:w-60');
        sidebar.classList.toggle('lg:w-16');
        main.classList.toggle('lg:ml-60');
        main.classList.toggle('lg:ml-16');
    } else {
        sidebar.classList.toggle('translate-x-0');
        sidebar.classList.toggle('-translate-x-full');
    }

    const is_expanded = sidebar.classList.contains('lg:w-60') || sidebar.classList.contains('translate-x-0');
    
    if (typeof updateSidebarVisibility === 'function') {
        updateSidebarVisibility(is_expanded);
    }
    
    localStorage.setItem('sidebarStatus', is_expanded ? 'open' : 'collapsed');
}

// Initialize sidebar state
document.addEventListener("DOMContentLoaded", function() {
    const sidebar = document.getElementById("sidebar");
    const main = document.getElementById("mainContent");
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
        }
    }
});
</script>

</body>
</html>