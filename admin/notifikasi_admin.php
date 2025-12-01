<?php
session_start();
require_once '../koneksi.php'; 
$db = $koneksi;

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'tahun', 'm' => 'bulan', 'w' => 'minggu',
        'd' => 'hari', 'h' => 'jam', 'i' => 'menit', 's' => 'detik',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? '' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' yang lalu' : 'Baru saja';
}

$query_new = "SELECT * FROM tbl_pengajuan 
              WHERE status IN ('pending', 'Menunggu', 'menunggu') 
              ORDER BY created_at DESC";
$res_new = $db->query($query_new);
$new_orders = [];
while ($row = $res_new->fetch_assoc()) {
    $new_orders[] = $row;
}

$query_log = "SELECT * FROM tbl_pengajuan 
              WHERE status NOT IN ('pending', 'Menunggu', 'menunggu') 
              ORDER BY updated_at DESC LIMIT 10";
$res_log = $db->query($query_log);
$logs = [];
while ($row = $res_log->fetch_assoc()) {
    $logs[] = $row;
}

$unread_count = count($new_orders);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi - Admin Pengelola</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
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

        #toggleBtn {
            position: fixed; 
            top: 1.25rem; 
            left: 1.25rem; 
            z-index: 60 !important; 
            transition: left 0.3s ease; 
        }

        #sidebar {
            position: fixed;
            z-index: 50; 
            top: 0;
            left: 0;
            height: 100%;
        }

        #sidebar.lg\:w-16 .sidebar-text {
            display: none;
        }

        #sidebar.lg\:w-60 .sidebar-text {
            display: inline;
        }

        @media (max-width: 1023px) {
            .sidebar-text {
                display: inline !important;
            }
        }
        
        @keyframes ring {
            0% { transform: rotate(0); } 10% { transform: rotate(15deg); } 20% { transform: rotate(-15deg); }
            30% { transform: rotate(10deg); } 40% { transform: rotate(-10deg); } 50% { transform: rotate(0); }
        }
        .bell-ring { animation: ring 2s ease-in-out infinite; transform-origin: top center; }
        
        .animate-fade-in-up { animation: fadeInUp 0.5s ease-out forwards; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-blue-100 flex min-h-screen">

<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

<?php include 'sidebar_admin.php'; ?>

<button class="hamburger bg-amber-500 hover:bg-amber-600 text-gray-900 p-2 rounded-lg transition-colors shadow-md" id="toggleBtn" onclick="toggleSidebar()">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
    </svg>
</button>

<div id="mainContent" class="main flex-1 p-5 transition-all duration-300 lg:ml-16 animate-fade-in-up">
    
    <div class="header flex justify-end items-center mb-6">
        <div class="flex items-center gap-4">
            <h1 class="text-lg sm:text-xl font-bold text-gray-800">Pusat Notifikasi</h1>
            
            <div class="relative flex-shrink-0">
                <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-md">
                    <span class="text-xl <?= $unread_count > 0 ? 'bell-ring text-amber-500' : 'text-gray-400' ?>">🔔</span>
                </div>
                <?php if($unread_count > 0): ?>
                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold w-5 h-5 flex items-center justify-center rounded-full border-2 border-blue-100">
                        <?= $unread_count ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto">
        
        <div class="mb-8">
            <h2 class="text-sm font-bold text-gray-600 uppercase tracking-wider mb-4 flex items-center gap-2">
                <span class="w-2 h-2 bg-amber-500 rounded-full animate-pulse"></span> 
                Permintaan Baru (<?= $unread_count ?>)
            </h2>

            <?php if (count($new_orders) > 0): ?>
                <div class="space-y-3">
                    <?php foreach ($new_orders as $item): ?>
                        <div class="bg-white border-l-4 border-amber-500 rounded-xl shadow-md p-5 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 relative overflow-hidden group">
                            <div class="relative flex items-start gap-4">
                                <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center flex-shrink-0 text-xl">
                                    📩
                                </div>

                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="font-bold text-gray-800">Permohonan Masuk</h3>
                                            <p class="text-sm text-gray-600 mt-1">
                                                <span class="font-semibold text-gray-900"><?= htmlspecialchars($item['nama_peminjam']) ?></span> 
                                                mengajukan peminjaman 
                                                <span class="font-semibold text-blue-600"><?= htmlspecialchars($item['subpilihan']) ?></span>
                                            </p>
                                        </div>
                                        <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-1 rounded whitespace-nowrap">
                                            <?= time_elapsed_string($item['created_at']) ?>
                                        </span>
                                    </div>
                                    
                                    <div class="mt-3 flex items-center gap-4 text-xs text-gray-500 bg-gray-50 p-2 rounded-lg border border-gray-100 w-fit">
                                        <span class="flex items-center gap-1">📅 <?= date('d M Y', strtotime($item['tanggal_peminjaman'])) ?></span>
                                        <span class="flex items-center gap-1">⏰ <?= substr($item['jam_mulai'],0,5) ?> - <?= substr($item['jam_selesai'],0,5) ?></span>
                                    </div>

                                    <div class="mt-4">
                                        <a href="detailpermintaan_admin.php?id=<?= $item['id'] ?>" class="inline-flex items-center gap-2 text-xs bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg font-bold transition-colors shadow-sm">
                                            Tinjau & Proses ➜
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-white/60 rounded-xl border border-dashed border-gray-300 p-8 text-center backdrop-blur-sm">
                    <p class="text-gray-500 text-sm font-medium">✨ Tidak ada permintaan baru saat ini.</p>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4">Riwayat Aktivitas</h2>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 divide-y divide-gray-100 overflow-hidden">
                <?php foreach ($logs as $log): ?>
                    <?php 
                        $status_color = 'bg-gray-100 text-gray-500';
                        $icon = '📋';
                        $msg_status = 'memperbarui status';
                        $st = strtolower($log['status']);

                        if($st == 'disetujui') {
                            $status_color = 'bg-green-100 text-green-600';
                            $icon = '✔';
                            $msg_status = 'menyetujui peminjaman';
                        } elseif($st == 'ditolak') {
                            $status_color = 'bg-red-100 text-red-600';
                            $icon = '✖';
                            $msg_status = 'menolak peminjaman';
                        }
                    ?>
                    <a href="detailpermintaan_admin.php?id=<?= $log['id'] ?>" class="block p-4 hover:bg-gray-50 transition-colors flex items-center gap-4 group">
                        <div class="w-10 h-10 <?= $status_color ?> rounded-full flex items-center justify-center text-sm flex-shrink-0 group-hover:scale-110 transition-transform">
                            <?= $icon ?>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-700">
                                Status <span class="font-bold text-gray-900"><?= htmlspecialchars($log['subpilihan']) ?></span> 
                                diperbarui menjadi <span class="font-semibold uppercase"><?= $log['status'] ?></span>
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">Oleh Admin • <?= time_elapsed_string($log['updated_at']) ?></p>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                <?php endforeach; ?>
                
                <?php if(count($logs) == 0): ?>
                    <div class="p-6 text-center text-sm text-gray-400">Belum ada riwayat aktivitas.</div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
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

    function toggleNotifDropdown(){
        const dropdown = document.getElementById("notifDropdown");
        dropdown.classList.toggle("hidden");
        document.querySelector(".profile-dropdown").classList.add("hidden"); 
    }

    function toggleProfileDropdown(){
        const dropdown = document.querySelector(".profile-dropdown");
        dropdown.classList.toggle("hidden");
        document.getElementById("notifDropdown").classList.add("hidden"); 
    }

    document.addEventListener('click',function(e){
        if(!e.target.closest('.notif-wrapper') && !e.target.closest('.profile') && !e.target.closest('#toggleBtn') && !e.target.closest('#sidebar') && !e.target.closest('#sidebarOverlay')){
            document.getElementById("notifDropdown").classList.add("hidden");
            document.querySelector(".profile-dropdown").classList.add("hidden");
        }
    });

    const searchInputDesktop=document.getElementById('searchInput'); 
    const clearBtn=document.getElementById('clearBtn'); 
    if (clearBtn && searchInputDesktop) {
        searchInputDesktop.addEventListener('input', () => {
            clearBtn.style.display = searchInputDesktop.value.length > 0 ? 'block' : 'none';
        });
        clearBtn.addEventListener('click',() => { window.location.href='dashboardadmin.php'; });
    }

 document.addEventListener("DOMContentLoaded", () => {
    const sidebar = document.getElementById("sidebar");
    const main = document.getElementById("mainContent");
    const overlay = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('toggleBtn'); 
    const status = localStorage.getItem('sidebarStatus');
    const is_desktop = window.innerWidth >= 1024;

    if (sidebar) {
        if (status === 'open') {
            if (is_desktop) {
                main.classList.add('lg:ml-60');
                main.classList.remove('lg:ml-16');
                sidebar.classList.add('lg:w-60');
                sidebar.classList.remove('lg:w-16');
                toggleBtn.style.left = '16rem'; 
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
                toggleBtn.style.left = '5rem'; 
            } else {
                sidebar.classList.remove('translate-x-0');
                sidebar.classList.add('-translate-x-full');
                if (overlay) overlay.classList.add('hidden');
            }
        }
    }

    if (overlay) {
        overlay.addEventListener('click', toggleSidebar);
    }

    // Auto-hide success alert
    const successAlert = document.getElementById('successAlert');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.opacity = '0';
            setTimeout(() => { successAlert.style.display = 'none'; }, 500);
        }, 5000);
    }
});

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