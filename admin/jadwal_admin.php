<?php
require_once '../koneksi.php';
$db = $koneksi;

// --- AMBIL DATA BOOKING (HANYA YANG DISETUJUI) ---
$sql_bookings = "SELECT * FROM tbl_pengajuan WHERE status = 'disetujui' ORDER BY tanggal_peminjaman ASC";
$result_bookings = mysqli_query($db, $sql_bookings);

$bookings = [];
while ($row = mysqli_fetch_assoc($result_bookings)) {
    // Warna Hijau (Emerald-500) untuk yang disetujui
    $color = '#10B981'; 
    $textColor = '#FFFFFF'; 

    $title = $row['subpilihan'];

    $bookings[] = [
        'id' => $row['id'],
        'title' => $title,
        'start' => $row['tanggal_peminjaman'] . 'T' . $row['jam_mulai'],
        'end' => $row['tanggal_selesai'] . 'T' . $row['jam_selesai'],
        'backgroundColor' => $color,
        'borderColor' => $color,
        'textColor' => $textColor,
        'extendedProps' => [
            'status' => ucfirst($row['status']),
            'pemohon' => $row['nama_peminjam'],
            'ruangan' => $row['subpilihan'],
            'agenda' => $row['agenda'] ?? '-',
            'waktu' => substr($row['jam_mulai'], 0, 5) . ' - ' . substr($row['jam_selesai'], 0, 5)
        ]
    ];
}

// --- LOGIC STATISTIK ---
// today_active: Menghitung jumlah ruangan yang tanggal pinjamnya mencakup hari ini
// upcoming_booked: Menghitung total booking yang belum selesai (hari ini ke depan)
$sql_stats = "SELECT 
    SUM(CASE WHEN status = 'disetujui' AND CURDATE() BETWEEN tanggal_peminjaman AND tanggal_selesai THEN 1 ELSE 0 END) AS today_active,
    SUM(CASE WHEN status = 'disetujui' AND tanggal_selesai >= CURDATE() THEN 1 ELSE 0 END) AS upcoming_booked
    FROM tbl_pengajuan";

$result_stats = mysqli_query($db, $sql_stats);
$stats_summary = mysqli_fetch_assoc($result_stats);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Jadwal Pemakaian Aset</title>
  <script src="https://cdn.tailwindcss.com"></script>
  
  <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
  
  <script src="https://unpkg.com/@popperjs/core@2"></script>
  <script src="https://unpkg.com/tippy.js@6"></script>
  <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/animations/scale.css"/>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

  <style>
    body { font-family: 'Poppins', sans-serif; }
    .text-dark-accent { color: #202938; }
    .main { transition: margin-left 0.3s ease; }

    /* === ANIMASI HALUS === */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in-up {
        animation: fadeInUp 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
    }

    /* === CUSTOM FULLCALENDAR STYLES === */
    #calendar {
      background: white;
      padding: 20px;
      border-radius: 16px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
      transition: box-shadow 0.3s ease;
    }
    #calendar:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .fc-toolbar-title {
        font-size: 1.25rem !important;
        font-weight: 700;
        color: #1F2937;
    }
    
    .fc-button {
        background-color: #3B82F6 !important;
        border: none !important;
        font-weight: 500 !important;
        text-transform: capitalize !important;
        border-radius: 8px !important;
        box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
        transition: all 0.2s ease !important;
    }
    .fc-button:hover { background-color: #2563EB !important; transform: scale(1.05); }
    .fc-button-active { background-color: #1D4ED8 !important; }

    /* HARI INI (BIRU MUDA) */
    .fc-day-today {
        background-color: #EFF6FF !important;
        transition: background-color 0.3s ease;
    }

    .fc-event {
        border-radius: 4px !important;
        padding: 2px 4px;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        transition: transform 0.1s, box-shadow 0.1s;
        border: none !important; 
    }
    .fc-event:hover {
        transform: scale(1.05);
        z-index: 50 !important;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .fc-list-event-dot {
        border-color: inherit !important;
    }

    @media (min-width: 1024px) {
        .main { margin-left: 4rem; }
        .main.lg\:ml-60 { margin-left: 15rem; }
    }
    @media (max-width: 1023px) {
        .main { margin-left: 0 !important; }
        .fc-header-toolbar { flex-direction: column; gap: 10px; }
    }
    
    #toggleBtn { position: relative; z-index: 51 !important; }
    
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
  </style>
</head>
<body class="bg-blue-100 min-h-screen">

<?php include 'sidebar_admin.php'; ?>
<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden transition-opacity duration-300"></div>

<div id="mainContent" class="main flex-1 p-4 sm:p-6 transition-all duration-300 lg:ml-16 min-h-screen animate-fade-in-up">

  <div class="flex justify-between items-center mb-6">
    <button id="toggleBtn" class="bg-amber-500 hover:bg-amber-600 text-gray-900 p-2 rounded-lg transition-all duration-200 shadow-md hover:shadow-lg hover:scale-105" onclick="toggleSidebar()">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
      </svg>
    </button>
    <div class="text-sm text-gray-600 hidden sm:block font-semibold">Jadwal Aset</div>
  </div>

  <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
        📅 Jadwal & Ketersediaan
      </h1>
      <p class="text-gray-600 text-sm mt-1">Pantau penggunaan aset kampus secara real-time.</p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-8">
      
      <div class="bg-white p-5 rounded-2xl shadow-sm border-l-4 border-red-500 flex items-center justify-between transition-all duration-300 hover:-translate-y-1 hover:shadow-md cursor-default">
          <div>
              <p class="text-xs text-gray-400 font-bold uppercase">Total Booked (Aktif)</p>
              <p class="text-2xl font-bold text-red-600"><?= $stats_summary['upcoming_booked'] ?? 0 ?></p>
          </div>
          <div class="p-3 bg-red-50 rounded-full text-red-500">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
          </div>
      </div>

      <div class="bg-white p-5 rounded-2xl shadow-sm border-l-4 border-blue-500 flex items-center justify-between transition-all duration-300 hover:-translate-y-1 hover:shadow-md cursor-default">
          <div>
              <p class="text-xs text-gray-400 font-bold uppercase">Ruang Terpakai Hari Ini</p>
              <p class="text-2xl font-bold text-blue-600"><?= $stats_summary['today_active'] ?? 0 ?></p>
          </div>
          <div class="p-3 bg-blue-50 rounded-full text-blue-500">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          </div>
      </div>

  </div>

  <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
      
      <div class="lg:col-span-3">
          <div class="bg-white p-1 rounded-2xl shadow-md transition-shadow hover:shadow-lg">
              <div class="flex gap-4 p-4 border-b border-gray-100 text-xs font-medium text-gray-600">
                  <div class="flex items-center gap-2">
                      <span class="w-3 h-3 rounded-full bg-blue-100 border border-blue-300 shadow-sm"></span> Hari Ini
                  </div>
                  <div class="flex items-center gap-2">
                      <span class="w-3 h-3 rounded-full bg-emerald-500 shadow-sm"></span> Terbooking
                  </div>
              </div>
              
              <div id="calendar"></div>
          </div>
      </div>

      <div class="lg:col-span-1">
          <div class="bg-white rounded-2xl shadow-md p-5 h-full transition-shadow hover:shadow-lg">
              <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                  <span>📋</span> Agenda yang akan datang
              </h3>
              
              <div class="space-y-3 max-h-[500px] overflow-y-auto pr-1 custom-scrollbar">
                  <?php 
                  // List tetap menampilkan Pending agar admin tau ada tugas
                  $sql_list = "SELECT * FROM tbl_pengajuan WHERE status IN ('disetujui', 'pending') AND tanggal_selesai >= CURDATE() ORDER BY tanggal_peminjaman ASC LIMIT 10";
                  $res_list = mysqli_query($db, $sql_list);
                  
                  if (mysqli_num_rows($res_list) > 0):
                      while ($row = mysqli_fetch_assoc($res_list)): 
                          $st = strtolower($row['status']);
                          
                          $border_color = ($st == 'disetujui') ? 'border-emerald-500' : 'border-yellow-500';
                          $bg_badge = ($st == 'disetujui') ? 'bg-emerald-100 text-emerald-700' : 'bg-yellow-100 text-yellow-700';
                          $status_text = ($st == 'disetujui') ? 'Booked' : 'Pending';
                  ?>
                    <a href="detailpermintaan_admin.php?id=<?= $row['id'] ?>" class="block group">
                        <div class="p-3 rounded-xl border-l-4 <?= $border_color ?> bg-gray-50 hover:bg-gray-100 transition-all duration-200 cursor-pointer hover:-translate-y-0.5 hover:shadow-sm">
                            <div class="flex justify-between items-start mb-1">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-md <?= $bg_badge ?>"><?= $status_text ?></span>
                                <span class="text-xs text-gray-400"><?= date('d M', strtotime($row['tanggal_peminjaman'])) ?></span>
                            </div>
                            <h4 class="text-sm font-bold text-gray-800 line-clamp-1 group-hover:text-blue-600 transition-colors"><?= htmlspecialchars($row['subpilihan']) ?></h4>
                            <p class="text-xs text-gray-500 mt-1 truncate">👤 <?= htmlspecialchars($row['nama_peminjam']) ?></p>
                            <p class="text-xs text-gray-400 mt-1">⏰ <?= substr($row['jam_mulai'],0,5) ?> - <?= substr($row['jam_selesai'],0,5) ?></p>
                        </div>
                    </a>
                  <?php endwhile; else: ?>
                      <div class="text-center py-10 text-gray-400 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                          <p class="text-sm">Tidak ada agenda dalam waktu dekat.</p>
                      </div>
                  <?php endif; ?>
              </div>
          </div>
      </div>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var events = <?= json_encode($bookings) ?>;

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'id',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,listWeek'
        },
        height: 'auto',
        events: events,
        eventClick: function(info) {
            var id = info.event.id;
            if (id) {
                window.location.href = 'detailpermintaan_admin.php?id=' + id;
            }
        },
        // TOOLTIP TIPPY.JS
        eventDidMount: function(info) {
            const props = info.event.extendedProps;
            const content = `
                <div class="text-left p-1">
                    <div class="font-bold text-sm border-b border-gray-400 pb-1 mb-1 text-white">${props.ruangan}</div>
                    <div class="text-xs text-gray-200">👤 ${props.pemohon}</div>
                    <div class="text-xs text-gray-200 mt-1">📝 ${props.agenda}</div>
                    <div class="text-xs text-gray-300 mt-1">⏰ ${props.waktu}</div>
                </div>
            `;
            
            tippy(info.el, {
                content: content,
                allowHTML: true,
                theme: 'light', 
                placement: 'top',
                animation: 'scale',
                maxWidth: 220,
                backgroundColor: '#333'
            });
        }
    });
    calendar.render();
});

// --- SIDEBAR LOGIC ---
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

document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    const status = localStorage.getItem('sidebarStatus');
    
    if (status === 'open') {
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