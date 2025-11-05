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
  <title>Jadwal Pemakaian Aset</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

  <style>
    body {
      font-family: 'Poppins', sans-serif;
    }
    .text-dark-accent { color: #202938; }
    .main { transition: margin-left 0.3s ease; }

    #calendar {
      background: white;
      border-radius: 1rem;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      padding: 1rem;
    }

    .fc-toolbar-title {
      font-weight: 700;
      font-size: 1.2rem !important;
    }

    @media (max-width: 640px) {
      .fc-toolbar {
        flex-direction: column !important;
        gap: 0.5rem;
      }
      .fc-toolbar-title {
        font-size: 1rem !important;
      }
      .fc-button {
        font-size: 0.75rem !important;
        padding: 0.25rem 0.5rem !important;
      }
    }

    .stat-card {
      @apply flex flex-wrap items-center justify-between gap-2 p-4 sm:p-5 rounded-xl shadow-md transform hover:scale-[1.02] transition-all;
    }
  </style>
</head>
<body class="bg-blue-100 min-h-screen">

<?php include 'sidebar_admin.php'; ?>
<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

<!-- MAIN CONTENT -->
<div id="mainContent" class="main flex-1 p-3 sm:p-5 transition-all duration-300 lg:ml-16 min-h-screen">

  <div class="flex justify-between items-center mb-4 sm:mb-6">
    <button id="toggleBtn" class="bg-amber-500 hover:bg-amber-600 text-gray-900 p-2 rounded-lg transition-colors shadow-md" onclick="toggleSidebar()">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
      </svg>
    </button>
  </div>

  <?php if (!empty($success_message)): ?>
    <div id="successAlert" class="bg-green-100 border border-green-400 text-green-700 px-3 sm:px-4 py-3 rounded-lg mb-4 shadow-md text-sm" role="alert">
      <?= $success_message ?>
    </div>
  <?php endif; ?>

  <div class="bg-white p-4 sm:p-6 rounded-xl shadow-lg">
    <div class="border-b pb-4 mb-5">
      <h1 class="text-xl sm:text-2xl font-bold text-dark-accent">📅 Jadwal Pemakaian Aset</h1>
      <p class="text-gray-500 text-xs sm:text-sm">Kalender penggunaan ruang, laboratorium, & fasilitas IT PLN</p>
    </div>

 <!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 mb-6">

  <!-- Card: Sedang Aktif -->
  <div class="relative bg-gradient-to-br from-blue-50 to-blue-100 p-5 rounded-3xl shadow-[0_8px_20px_rgba(0,0,0,0.08),0_2px_4px_rgba(255,255,255,0.6)_inset] border border-blue-100 transition-all duration-500 hover:shadow-[0_12px_28px_rgba(0,0,0,0.12),0_2px_6px_rgba(255,255,255,0.7)_inset] hover:-translate-y-1">
    <div class="absolute inset-0 bg-gradient-radial from-white/40 to-transparent rounded-3xl pointer-events-none"></div>
    <div class="relative flex items-center justify-between">
      <div class="text-4xl sm:text-5xl">🗓️</div>
      <div class="text-right">
        <h3 class="text-sm sm:text-base font-semibold text-blue-700 mb-1">Sedang Aktif Hari Ini</h3>
        <p class="text-3xl sm:text-4xl font-bold text-blue-900"><?= $stats_summary['today_active'] ?? 0 ?></p>
      </div>
    </div>
  </div>

  <!-- Card: Selesai -->
  <div class="relative bg-gradient-to-br from-green-50 to-green-100 p-5 rounded-3xl shadow-[0_8px_20px_rgba(0,0,0,0.08),0_2px_4px_rgba(255,255,255,0.6)_inset] border border-green-100 transition-all duration-500 hover:shadow-[0_12px_28px_rgba(0,0,0,0.12),0_2px_6px_rgba(255,255,255,0.7)_inset] hover:-translate-y-1">
    <div class="absolute inset-0 bg-gradient-radial from-white/40 to-transparent rounded-3xl pointer-events-none"></div>
    <div class="relative flex items-center justify-between">
      <div class="text-4xl sm:text-5xl">✅</div>
      <div class="text-right">
        <h3 class="text-sm sm:text-base font-semibold text-green-700 mb-1">Selesai Dipinjam</h3>
        <p class="text-3xl sm:text-4xl font-bold text-green-900"><?= $stats_summary['total_selesai_pinjam'] ?? 0 ?></p>
      </div>
    </div>
  </div>

  <!-- Card: Pending -->
  <div class="relative bg-gradient-to-br from-amber-50 to-yellow-100 p-5 rounded-3xl shadow-[0_8px_20px_rgba(0,0,0,0.08),0_2px_4px_rgba(255,255,255,0.6)_inset] border border-amber-100 transition-all duration-500 hover:shadow-[0_12px_28px_rgba(0,0,0,0.12),0_2px_6px_rgba(255,255,255,0.7)_inset] hover:-translate-y-1">
    <div class="absolute inset-0 bg-gradient-radial from-white/40 to-transparent rounded-3xl pointer-events-none"></div>
    <div class="relative flex items-center justify-between">
      <div class="text-4xl sm:text-5xl">⏳</div>
      <div class="text-right">
        <h3 class="text-sm sm:text-base font-semibold text-amber-700 mb-1">Total Pending</h3>
        <p class="text-3xl sm:text-4xl font-bold text-amber-900"><?= $stats_summary['total_pending'] ?? 0 ?></p>
      </div>
    </div>
  </div>





</div>


    <!-- KONTEN UTAMA -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
      
      <!-- Kalender -->
      <div class="lg:col-span-2">
        <div id="calendar"></div>
      </div>

      <!-- Daftar Booking -->
      <div class="lg:col-span-1 space-y-4">
        <!-- Aktif -->
        <div class="bg-white p-3 sm:p-4 rounded-xl shadow-lg border border-gray-200">
          <h2 class="text-base sm:text-lg font-bold mb-3 text-dark-accent border-b pb-2 flex items-center gap-2">
            <span>📅</span>
            <span>Detail Booking Aktif</span>
          </h2>
          <div class="max-h-[300px] overflow-y-auto">
            <?php 
            $active_found = false;
            foreach ($bookings as $row): 
              $status = strtolower($row['status']);
              $is_active_or_pending = ($status === 'pending') || ($status === 'disetujui' && strtotime($row['tanggal_selesai']) >= strtotime($today_date));
              if (!$is_active_or_pending) continue;
              $active_found = true;

              $is_today_active = (date('Y-m-d', strtotime($row['tanggal_mulai'])) <= $today_date && date('Y-m-d', strtotime($row['tanggal_selesai'])) >= $today_date && $status === 'disetujui');
              
              $card_class = 'p-2.5 sm:p-3 rounded-lg mb-2 sm:mb-3 border cursor-pointer transition-all duration-150';
              $icon = '⏳'; $color = 'text-yellow-700';
              
              if ($status === 'disetujui') {
                $card_class .= ' bg-green-50 border-green-300';
                $icon = '✅'; $color = 'text-green-800';
              } elseif ($status === 'pending') {
                $card_class .= ' bg-yellow-50 border-yellow-300';
              }
              if ($is_today_active) {
                $card_class = 'p-2.5 sm:p-3 rounded-lg mb-2 sm:mb-3 border-2 border-red-500 bg-red-100 shadow-lg hover:bg-red-200';
                $color = 'text-red-800'; $icon = '🔥';
              }
            ?>
              <a href="detailpermintaan_admin.php?id=<?= $row['id'] ?>" class="block">
                <div class="<?= $card_class ?>">
                  <strong class="text-xs sm:text-sm font-bold <?= $color ?> flex items-center gap-1.5 line-clamp-1">
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
              <div class="text-center py-6 sm:py-8 text-gray-500">
                <div class="text-3xl sm:text-4xl mb-2">📭</div>
                Tidak ada booking aktif
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
          <div class="max-h-[300px] overflow-y-auto">
            <?php 
            $history_found = false;
            foreach (array_reverse($bookings) as $row): 
              $status = strtolower($row['status']);
              $is_finished = ($status === 'disetujui' && strtotime($row['tanggal_selesai']) < strtotime($today_date));
              if (!$is_finished) continue;
              $history_found = true;
            ?>
              <a href="detailpermintaan_admin.php?id=<?= $row['id'] ?>" class="block">
                <div class="p-2.5 sm:p-3 rounded-lg mb-2 sm:mb-3 border bg-gray-50 border-gray-300 hover:shadow-md transition-all duration-150">
                  <strong class="text-xs sm:text-sm font-bold text-gray-800 flex items-center gap-1.5">
                    <span>✔️</span>
                    <span class="truncate"><?= htmlspecialchars($row['nama_ruang']) ?></span>
                  </strong>
                  <p class="text-xs text-gray-700 mt-1 truncate">👤 <?= htmlspecialchars($row['pemohon']) ?></p>
                  <small class="block mt-1 text-xs text-gray-500">Selesai: <?= date('d M Y', strtotime($row['tanggal_selesai'])) ?></small>
                </div>
              </a>
            <?php endforeach; 
            if (!$history_found): ?>
              <div class="text-center py-6 sm:py-8 text-gray-500">
                <div class="text-3xl sm:text-4xl mb-2">📋</div>
                Tidak ada riwayat
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
    const toggleBtn = document.getElementById('toggleBtn'); // Ambil toggle button
    const is_desktop = window.innerWidth >= 1024;

    if (is_desktop) {
        sidebar.classList.toggle('lg:w-60');
        sidebar.classList.toggle('lg:w-16');
        main.classList.toggle('lg:ml-60');
        main.classList.toggle('lg:ml-16');
        
        // KODE REVISI: Geser tombol toggle di desktop
        const is_expanded = sidebar.classList.contains('lg:w-60');
        if (is_expanded) {
             // Jika sidebar terbuka (15rem), pindahkan tombol ke kanan
            toggleBtn.style.left = '16rem'; 
        } else {
             // Jika sidebar tertutup (4rem), pindahkan tombol di samping sidebar kecil
            toggleBtn.style.left = '5rem'; 
        }

    } else {
        sidebar.classList.toggle('translate-x-0');
        sidebar.classList.toggle('-translate-x-full');
        // Di mobile, tombol toggle tetap di kiri (1.25rem)
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

document.addEventListener('DOMContentLoaded', function() {
  const sidebar = document.getElementById("sidebar");
  const main = document.getElementById("mainContent");
  const overlay = document.getElementById("sidebarOverlay");
  const status = localStorage.getItem('sidebarStatus');
  const is_desktop = window.innerWidth >= 1024;
  const successAlert = document.getElementById('successAlert');

  if (status === 'open') {
    if (is_desktop) {
      sidebar.classList.add('lg:w-60');
      sidebar.classList.remove('lg:w-16');
      main.classList.add('lg:ml-60');
      main.classList.remove('lg:ml-16');
    } else {
      sidebar.classList.add('translate-x-0');
      sidebar.classList.remove('-translate-x-full');
      overlay.classList.remove('hidden');
    }
  }
  

  if (overlay) overlay.addEventListener('click', toggleSidebar);

  if (successAlert) {
    setTimeout(() => {
      successAlert.style.opacity = '0';
      setTimeout(() => successAlert.style.display = 'none', 500);
    }, 4000);
  }

  // FullCalendar
  const calendarEl = document.getElementById('calendar');
  const bookings = <?= json_encode($bookings) ?>;
  const events = bookings.map(row => {
    const end = new Date(row.tanggal_selesai);
    end.setDate(end.getDate() + 1);
    const status = row.status.toLowerCase();
    if (status !== 'pending' && status !== 'disetujui') return null;
    return {
      title: row.nama_ruang + " (" + row.pemohon + ")",
      start: row.tanggal_mulai,
      end: end.toISOString().split('T')[0],
      color: status === 'disetujui' ? '#10B981' : '#F59E0B',
      extendedProps: { id: row.id }
    };
  }).filter(Boolean);

  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: window.innerWidth < 768 ? 'timeGridDay' : 'dayGridMonth',
    height: window.innerWidth < 640 ? 400 : 600,
    locale: 'id',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    events,
    eventClick: info => {
      const id = info.event.extendedProps.id;
      if (id) window.location.href = 'detailpermintaan_admin.php?id=' + id;
    },
    windowResize: () => {
      calendar.setOption('height', window.innerWidth < 640 ? 400 : 600);
    }
  });
  calendar.render();
});
</script>

</body>
</html>
