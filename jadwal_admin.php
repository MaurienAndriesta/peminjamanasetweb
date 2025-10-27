<?php
require_once 'config/database.php';
$db = new Database();

// Ambil data booking
$db->query("SELECT * FROM pemesanan ORDER BY tanggal_mulai ASC");
$bookings = $db->resultSet();

// --- LOGIC STATISTIK ---
$db->query("SELECT 
    SUM(CASE WHEN status = 'disetujui' AND tanggal_selesai < CURDATE() THEN 1 ELSE 0 END) AS total_selesai_pinjam,
    SUM(CASE WHEN status = 'disetujui' AND tanggal_mulai <= CURDATE() AND tanggal_selesai >= CURDATE() THEN 1 ELSE 0 END) AS today_active,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS total_pending
    FROM pemesanan");
$stats_summary = $db->single();

$today_date = date('Y-m-d');
$success_message = isset($_GET['success_msg']) ? htmlspecialchars($_GET['success_msg']) : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js"></script>
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

        @media (max-width: 1023px) {
            .main { margin-left: 0 !important; }
        }

        #toggleBtn {
            position: relative;
            z-index: 51 !important;
        }
        
        /* FullCalendar Custom */
        #calendar {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            background: white; 
            border-radius: 1rem;
            font-size: 0.85rem;
        }
        
        @media (max-width: 640px) {
            #calendar {
                font-size: 0.75rem;
            }
        }
        
        .fc .fc-toolbar-title {
            font-size: 1.25rem !important;
            font-weight: 700;
        }
        
        @media (max-width: 640px) {
            .fc .fc-toolbar-title {
                font-size: 1rem !important;
            }
            .fc .fc-button {
                padding: 0.25rem 0.5rem !important;
                font-size: 0.75rem !important;
            }
            .fc .fc-toolbar {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
        
        .fc-event {
            border-radius: 4px !important;
            padding: 2px 4px !important;
            font-size: 0.75rem;
        }
        
        .max-h-list {
            max-height: 300px;
        }
        
        @media (max-width: 1023px) {
            .max-h-list {
                max-height: 200px;
            }
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
    </div>

    <?php if (!empty($success_message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-3 sm:px-4 py-3 rounded-lg mb-4 shadow-md text-sm" role="alert" id="successAlert">
            <span class="block"><?= $success_message ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white p-4 sm:p-6 rounded-xl shadow-lg">
        <div class="content-header mb-4 sm:mb-6 border-b pb-4">
            <h1 class="text-xl sm:text-2xl font-bold text-dark-accent">📅 Jadwal Pemakaian Aset</h1>
            <p class="text-gray-500 text-xs sm:text-sm">Kalender pemakaian ruang, lab, & fasilitas IT PLN</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 mb-4 sm:mb-6">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-4 rounded-xl shadow-lg transform hover:scale-105 transition-transform">
                <div class="text-xs sm:text-sm font-medium mb-1">🗓️ Sedang Aktif Hari Ini</div>
                <div class="text-2xl sm:text-3xl font-bold"><?= $stats_summary['today_active'] ?? 0 ?></div>
            </div>
            
            <div class="bg-gradient-to-br from-green-500 to-green-600 text-white p-4 rounded-xl shadow-lg transform hover:scale-105 transition-transform">
                <div class="text-xs sm:text-sm font-medium mb-1">✅ Selesai Dipinjam</div>
                <div class="text-2xl sm:text-3xl font-bold"><?= $stats_summary['total_selesai_pinjam'] ?? 0 ?></div>
            </div>

            <div class="bg-gradient-to-br from-amber-400 to-amber-500 text-gray-900 p-4 rounded-xl shadow-lg transform hover:scale-105 transition-transform">
                <div class="text-xs sm:text-sm font-medium mb-1">⏳ Total Pending</div>
                <div class="text-2xl sm:text-3xl font-bold"><?= $stats_summary['total_pending'] ?? 0 ?></div>
            </div>
        </div>

        <!-- Calendar & Lists -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
            
            <!-- Calendar -->
            <div class="lg:col-span-2 order-2 lg:order-1">
                <div id="calendar" class="rounded-xl p-3 sm:p-4"></div>
            </div>

            <!-- Booking Lists -->
            <div class="lg:col-span-1 grid grid-cols-1 gap-4 order-1 lg:order-2">
                
                <!-- Booking Aktif -->
                <div class="bg-white p-3 sm:p-4 rounded-xl shadow-lg border border-gray-200">
                    <h2 class="text-base sm:text-lg font-bold mb-3 text-dark-accent border-b pb-2 flex items-center gap-2">
                        <span>📅</span>
                        <span>Detail Booking Aktif</span>
                    </h2>
                    <div class="max-h-list overflow-y-auto">
                        <?php 
                        $active_found = false;
                        foreach ($bookings as $row): 
                            $status = strtolower($row['status']);
                            $is_active_or_pending = ($status === 'pending') || ($status === 'disetujui' && strtotime($row['tanggal_selesai']) >= strtotime($today_date));
                            
                            if (!$is_active_or_pending) continue;
                            $active_found = true;

                            $is_today_active = (date('Y-m-d', strtotime($row['tanggal_mulai'])) <= $today_date && date('Y-m-d', strtotime($row['tanggal_selesai'])) >= $today_date && $status === 'disetujui');
                            
                            $class = 'p-2.5 sm:p-3 rounded-lg mb-2 sm:mb-3 border cursor-pointer hover:shadow-md transition-all duration-150';
                            $icon = '';
                            $title_color = '';

                            if ($status === 'pending') {
                                $class .= ' bg-yellow-50 border-yellow-300';
                                $icon = '⏳';
                                $title_color = 'text-yellow-800';
                            } elseif ($status === 'disetujui') {
                                $class .= ' bg-green-50 border-green-300';
                                $icon = '✅';
                                $title_color = 'text-green-800';
                            }
                            
                            if ($is_today_active) {
                                $class = 'p-2.5 sm:p-3 rounded-lg mb-2 sm:mb-3 border-2 border-red-500 bg-red-100 shadow-xl cursor-pointer hover:bg-red-200 transition-all duration-150';
                                $title_color = 'text-red-800';
                                $icon = '🔥';
                            }
                        ?>
                            <a href="detailpermintaan_admin.php?id=<?= $row['id'] ?>" class="block">
                                <div class="<?= $class ?>">
                                    <strong class="text-xs sm:text-sm font-bold <?= $title_color ?> flex items-center gap-1.5 line-clamp-1">
                                        <span><?= $icon ?></span>
                                        <span class="truncate"><?= htmlspecialchars($row['nama_ruang']) ?></span>
                                    </strong>
                                    <p class="text-xs text-gray-700 mt-1 truncate">👤 <?= htmlspecialchars($row['pemohon']) ?></p>
                                    <small class="block mt-1 text-xs text-gray-500">
                                        📅 <?= date('d M', strtotime($row['tanggal_mulai'])) ?> - <?= date('d M Y', strtotime($row['tanggal_selesai'])) ?>
                                    </small>
                                </div>
                            </a>
                        <?php endforeach; 
                        if (!$active_found): ?>
                            <div class="text-center py-6 sm:py-8">
                                <div class="text-3xl sm:text-4xl mb-2">📭</div>
                                <p class="text-gray-500 text-xs sm:text-sm">Tidak ada booking aktif</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Riwayat -->
                <div class="bg-white p-3 sm:p-4 rounded-xl shadow-lg border border-gray-200">
                    <h2 class="text-base sm:text-lg font-bold mb-3 text-dark-accent border-b pb-2 flex items-center gap-2">
                        <span>📜</span>
                        <span>Riwayat Peminjaman</span>
                    </h2>
                    <div class="max-h-list overflow-y-auto">
                        <?php 
                        $history_found = false;
                        $history_bookings = array_reverse($bookings); 
                        foreach ($history_bookings as $row): 
                            $status = strtolower($row['status']);
                            $is_finished = ($status === 'disetujui' && strtotime($row['tanggal_selesai']) < strtotime($today_date));
                            
                            if (!$is_finished) continue;
                            $history_found = true;

                            $class = 'p-2.5 sm:p-3 rounded-lg mb-2 sm:mb-3 border cursor-pointer bg-gray-50 border-gray-300 hover:shadow-md transition-all duration-150';
                        ?>
                            <a href="detailpermintaan_admin.php?id=<?= $row['id'] ?>" class="block">
                                <div class="<?= $class ?>">
                                    <strong class="text-xs sm:text-sm font-bold text-gray-800 flex items-center gap-1.5 line-clamp-1">
                                        <span>✔️</span>
                                        <span class="truncate"><?= htmlspecialchars($row['nama_ruang']) ?></span>
                                    </strong>
                                    <p class="text-xs text-gray-700 mt-1 truncate">👤 <?= htmlspecialchars($row['pemohon']) ?></p>
                                    <small class="block mt-1 text-xs text-gray-500">
                                        Selesai: <?= date('d M Y', strtotime($row['tanggal_selesai'])) ?>
                                    </small>
                                </div>
                            </a>
                        <?php endforeach; 
                        if (!$history_found): ?>
                            <div class="text-center py-6 sm:py-8">
                                <div class="text-3xl sm:text-4xl mb-2">📋</div>
                                <p class="text-gray-500 text-xs sm:text-sm">Tidak ada riwayat</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
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

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById("sidebar");
    const main = document.getElementById("mainContent");
    const overlay = document.getElementById("sidebarOverlay");
    const status = localStorage.getItem('sidebarStatus');
    const is_desktop = window.innerWidth >= 1024;
    const successAlert = document.getElementById('successAlert');

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

    if (overlay) {
        overlay.addEventListener('click', toggleSidebar);
    }

    if (successAlert) {
        setTimeout(() => {
            successAlert.style.opacity = '0'; 
            setTimeout(() => { successAlert.style.display = 'none'; }, 500);
        }, 5000);
    }

    // FullCalendar
    var calendarEl = document.getElementById('calendar');
    var bookings = <?= json_encode($bookings) ?>;
    
    var events = bookings.map(function(row) {
        var endDate = new Date(row.tanggal_selesai);
        endDate.setDate(endDate.getDate() + 1);

        var status = row.status.toLowerCase();
        var color;
        if (status === 'disetujui') color = '#10B981';
        else if (status === 'pending') color = '#F59E0B';
        else return null;

        return {
            title: row.nama_ruang + " (" + row.pemohon + ")",
            start: row.tanggal_mulai,
            end: endDate.toISOString().split('T')[0],
            color: color,
            extendedProps: { id: row.id }
        };
    }).filter(event => event !== null);

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: window.innerWidth < 768 ? 'timeGridDay' : 'dayGridMonth',
        height: window.innerWidth < 640 ? 400 : 600,
        locale: 'id',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: window.innerWidth < 640 ? 'dayGridMonth' : 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: events,
        eventClick: function(info) {
            if (info.event.extendedProps.id) {
                window.location.href = 'detailpermintaan_admin.php?id=' + info.event.extendedProps.id;
            }
        },
        windowResize: function() {
            calendar.setOption('height', window.innerWidth < 640 ? 400 : 600);
        }
    });
    calendar.render();
});
</script>
</body>
</html>