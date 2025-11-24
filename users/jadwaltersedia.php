<?php
require_once '../koneksi.php';

// ================= DATA MENU =================
$menu_items = [
  ['title' => 'Beranda', 'url' => 'dashboarduser.php'],
  [
    'title' => 'Daftar Ruangan & Fasilitas',
    'type' => 'dropdown',
    'submenu' => [
      ['title' => 'Ruangan Multiguna', 'url' => 'ruangmultiguna.php'],
      ['title' => 'Fasilitas', 'url' => 'fasilitas.php'],
      ['title' => 'Usaha', 'url' => 'usaha.php'],
      [
        'title' => 'Laboratorium',
        'type'  => 'dropdown',
        'submenu' => [
          ['title' => 'Fakultas Teknologi dan Bisnis Energi (FTBE)', 'url' => 'labftbe.php'],
          ['title' => 'Fakultas Telematika Energi (FTEN)', 'url' => 'labften.php'],
          ['title' => 'Fakultas Teknologi Infrastruktur dan Kewilayahan (FTIK)', 'url' => 'labftik.php'],
          ['title' => 'Fakultas Ketenagalistrikan dan Energi Terbarukan (FKET)', 'url' => 'labfket.php'],
        ]
      ]
    ]
  ],
  ['title' => 'Jadwal Ketersediaan', 'url'  => 'jadwaltersedia.php'],
  ['title' => 'Status Peminjaman', 'url'  => 'status.php'],
  ['title' => 'Riwayat Peminjaman', 'url'  => 'riwayat.php'],
];

function renderMenu($items, $prefix = 'root') {
    echo "<ul class='list-none pl-3 space-y-1'>";
    foreach ($items as $index => $item) {
        $uniqueId = $prefix . '-' . $index;

        if (isset($item['type']) && $item['type'] === 'dropdown') {
            echo "
            <li>
                <button onclick='toggleDropdown(\"{$uniqueId}\")'
                    class='flex justify-between items-center w-full px-3 py-2 text-left rounded-lg hover:bg-gray-700/70 transition'>
                    <span>{$item['title']}</span>
                    <span id='icon-{$uniqueId}'>▼</span>
                </button>
                <div class='hidden pl-4 border-l border-gray-700 ml-2 mt-1' id='submenu-{$uniqueId}'>";
                    renderMenu($item['submenu'], $uniqueId);
            echo "</div>
            </li>";
        } else {
            echo "
            <li>
                <a href='{$item['url']}' class='block px-3 py-2 rounded-lg hover:bg-gray-700/70 transition'>
                    {$item['title']}
                </a>
            </li>";
        }
    }
    echo "</ul>";
}

// ================== TARIK DATA DARI DATABASE ==================
// ================== MAP TABEL SESUAI DATABASE ==================
$tabel_map = [
    'Ruang Multiguna' => 'tbl_ruangmultiguna',
    'Fasilitas'       => 'tbl_fasilitas',
    'Usaha'           => 'tbl_usaha',
    'Lab FTBE'        => 'labftbe',
    'Lab FTIK'        => 'labftik',
    'Lab FKET'        => 'labfket',
    'Lab FTEN'        => 'labften'
];

// Ambil filter dari URL
$filter = $_GET['filter'] ?? '';

$results = [];
$counter = 1;

// ================== CEK FILTER KHUSUS ==================
if ($filter && isset($tabel_map[$filter])) {

    $tabel = $tabel_map[$filter];

    // Cegah error kalau tabel tidak ada
    $cek_tabel = mysqli_query($koneksi, "SHOW TABLES LIKE '$tabel'");
    if (mysqli_num_rows($cek_tabel) == 0) {
        echo "<p style='color:red'>Error: Tabel <b>$tabel</b> tidak ditemukan di database.</p>";
        exit;
    }

    // Cek apakah kolom kapasitas ada
    $cek_kapasitas = mysqli_query($koneksi, "SHOW COLUMNS FROM `$tabel` LIKE 'kapasitas'");
    $show_capacity = mysqli_num_rows($cek_kapasitas) > 0;

    // Query sesuai ada/tidaknya kolom kapasitas
    $query = $show_capacity 
        ? "SELECT id, nama, kapasitas, status FROM `$tabel`"
        : "SELECT id, nama, status FROM `$tabel`";

    $res = mysqli_query($koneksi, $query);

    while ($row = mysqli_fetch_assoc($res)) {
        $row['no'] = $counter++;
        $row['tabel'] = $tabel;  // supaya tau asal tabel
        $results[] = $row;
    }

} else {

    // ================== TANPA FILTER → TARIK SEMUA TABEL ==================
    foreach ($tabel_map as $tbl) {

        // Lewati jika tabel tidak ada
        $cek_tabel = mysqli_query($koneksi, "SHOW TABLES LIKE '$tbl'");
        if (mysqli_num_rows($cek_tabel) == 0) {
            continue; // skip tabel hilang
        }

        // Cek kapasitas
        $cek_kapasitas = mysqli_query($koneksi, "SHOW COLUMNS FROM `$tbl` LIKE 'kapasitas'");
        $show_capacity = mysqli_num_rows($cek_kapasitas) > 0;

        $query = $show_capacity 
            ? "SELECT id, nama, kapasitas, status FROM `$tbl`"
            : "SELECT id, nama, status FROM `$tbl`";

        $res = mysqli_query($koneksi, $query);

        while ($row = mysqli_fetch_assoc($res)) {
            $row['no'] = $counter++;
            $row['tabel'] = $tbl;
            $results[] = $row;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Jadwal Ketersediaan Aset</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    body { 
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #D1E5EA 0%, #B8D4DB 100%);
    }
    
    .glass-effect {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.3);
    }
    
    .animate-fade-in {
      animation: fadeIn 0.6s ease-out;
    }
    
    @keyframes fadeIn {
      from { 
        opacity: 0; 
        transform: translateY(20px); 
      }
      to { 
        opacity: 1; 
        transform: translateY(0); 
      }
    }

    .table-row-hover {
      transition: all 0.2s ease;
    }

    .table-row-hover:hover {
      transform: translateX(4px);
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
    }

    .menu-icon {
      transition: transform 0.3s ease;
    }

    .menu-icon:hover {
      transform: scale(1.1);
    }

    .badge-status {
      font-size: 0.75rem;
      padding: 0.375rem 0.75rem;
      font-weight: 600;
      letter-spacing: 0.025em;
    }

    .search-input:focus {
      transform: scale(1.02);
      transition: transform 0.2s ease;
    }

    .calendar-cell {
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .calendar-cell:hover:not(.booked):not(.today) {
      transform: translateY(-2px);
    }
  </style>
</head>

<body class="min-h-screen">

<!-- Overlay -->
<div id="overlay" class="hidden fixed inset-0 bg-black/50 z-40 transition-opacity duration-300" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<div id="sidebar" class="fixed top-0 left-0 w-72 h-full bg-gray-800 text-white transform -translate-x-full transition-transform duration-300 z-50 shadow-2xl">
  <div class="bg-gray-900 px-6 py-5 font-semibold text-center border-b border-gray-700 text-lg uppercase tracking-wide">
    Menu Utama
  </div>
  <nav class="p-4">
    <?php renderMenu($menu_items); ?>
  </nav>
</div>

<!-- MAIN -->
<main class="min-h-screen transition-all duration-300 pb-8">

  <!-- Top Bar -->
  <div class="px-6 py-5">
   <button id="menuBtn" class="bg-gray-800 text-white p-3 rounded-md">☰</button>
  </div>

  <!-- Header Jadwal Ketersediaan -->
<div class="max-w-7xl mx-auto px-6 -mt-2 mb-8 animate-fade-in">
  <div class="relative overflow-hidden bg-gradient-to-r from-indigo-500 via-sky-500 to-blue-500 rounded-3xl shadow-2xl p-[1px]">
    <div class="bg-white rounded-3xl p-8 flex flex-col sm:flex-row items-center justify-between gap-6 relative overflow-hidden">
      <!-- Efek animasi lingkaran blur -->
      <div class="absolute top-0 left-0 w-40 h-40 bg-blue-400/20 rounded-full blur-3xl -translate-x-10 -translate-y-10 animate-pulse"></div>
      <div class="absolute bottom-0 right-0 w-40 h-40 bg-indigo-400/20 rounded-full blur-3xl translate-x-10 translate-y-10 animate-pulse"></div>

      <!-- Bagian kiri: Icon + Judul -->
      <div class="flex items-center gap-5">
        <div class="bg-gradient-to-br from-indigo-500 to-blue-500 p-3 rounded-2xl shadow-md transform hover:scale-110 transition duration-300">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-9 w-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <div>
          <h1 class="text-3xl sm:text-4xl font-extrabold bg-gradient-to-r from-indigo-600 to-blue-600 bg-clip-text text-transparent">
            Jadwal Ketersediaan
          </h1>
          <p class="text-gray-500 mt-1 text-sm">Cek ketersediaan ruangan dan fasilitas dengan mudah</p>
        </div>
      </div>

      <!-- Bagian kanan: tombol interaktif -->
      <button 
        onclick="window.scrollTo({top: document.body.scrollHeight, behavior: 'smooth'})" 
        class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-6 py-2.5 rounded-xl font-semibold shadow-md hover:shadow-lg hover:scale-105 active:scale-95 transition duration-300 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        Lihat Jadwal
      </button>
    </div>
  </div>
</div>



  <!-- Filter + Search -->
  <div class="max-w-7xl mx-auto px-6 mb-6 animate-fade-in" style="animation-delay: 0.1s;">
    <div class="glass-effect rounded-2xl shadow-lg p-6 border border-gray-200">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        
        <!-- Filter Dropdown -->
        <div class="flex items-center gap-3">
          <label class="font-semibold text-gray-700 text-sm flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
            </svg>
            Filter Kategori
          </label>
          <form method="GET" id="filterForm">
            <select name="filter" onchange="document.getElementById('filterForm').submit();" 
              class="border-2 border-gray-300 rounded-xl px-4 py-2.5 text-sm bg-white hover:border-blue-400 transition-all duration-200 focus:ring-2 focus:ring-blue-200 focus:border-blue-400 outline-none cursor-pointer shadow-sm">
              <option value="">-- Semua Kategori --</option>
              <option value="Ruang Multiguna" <?= $filter == 'Ruang Multiguna' ? 'selected' : '' ?>>Ruang Multiguna</option>
              <option value="Fasilitas" <?= $filter == 'Fasilitas' ? 'selected' : '' ?>>Fasilitas</option>
              <option value="Usaha" <?= $filter == 'Usaha' ? 'selected' : '' ?>>Usaha</option>
              <option value="Lab FTBE" <?= $filter == 'Lab FTBE' ? 'selected' : '' ?>>Laboratorium FTBE</option>
              <option value="Lab FTEN" <?= $filter == 'Lab FTEN' ? 'selected' : '' ?>>Laboratorium FTEN</option>
              <option value="Lab FTIK" <?= $filter == 'Lab FTIK' ? 'selected' : '' ?>>Laboratorium FTIK</option>
              <option value="Lab FKET" <?= $filter == 'Lab FKET' ? 'selected' : '' ?>>Laboratorium FKET</option>
            </select>
          </form>
        </div>

        <!-- Search Input -->
        <div class="flex items-center gap-3">
          <label class="font-semibold text-gray-700 text-sm flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            Cari
          </label>
          <input type="text" id="searchInput" placeholder="Cari nama ruangan / pelayanan..." 
            class="search-input border-2 border-gray-300 rounded-xl px-4 py-2.5 text-sm bg-white focus:ring-2 focus:ring-blue-200 focus:border-blue-400 outline-none shadow-sm min-w-[280px]"
            onkeyup="filterTable()">
        </div>

      </div>
    </div>
  </div>

  <!-- Tabel -->
  <div class="max-w-7xl mx-auto px-6 mb-8 animate-fade-in" style="animation-delay: 0.2s;">
    <div class="glass-effect rounded-2xl shadow-lg overflow-hidden border border-gray-200">
      <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
        <h3 class="font-bold text-gray-800 text-lg flex items-center gap-2">
          <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
          </svg>
          Data Ketersediaan
        </h3>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm" id="dataTable">
          <thead>
            <tr class="bg-gradient-to-r from-blue-50 to-indigo-50 text-gray-700 border-b-2 border-blue-200">
              <th class="px-6 py-4 text-left font-bold text-sm">No</th>
              <th class="px-6 py-4 text-left font-bold text-sm">Nama Ruangan / Jenis Pelayanan</th>
              <th class="px-6 py-4 text-left font-bold text-sm">Kapasitas</th>
              <th class="px-6 py-4 text-left font-bold text-sm">Status & Aksi</th>
            </tr>
          </thead>
          <tbody id="tableBody">
            <?php if (!empty($results)): ?>
              <?php foreach ($results as $row): ?>
                <tr class="border-b border-gray-100 table-row-hover bg-white">
                  <td class="px-6 py-4">
                    <span class="font-semibold text-gray-700"><?= $row['no']; ?></span>
                  </td>
                  <td class="px-6 py-4">
                    <span class="font-semibold text-gray-800"><?= htmlspecialchars($row['nama']); ?></span>
                  </td>
                  <td class="px-6 py-4">
                    <?php if ($show_capacity && isset($row['kapasitas'])): ?>
                      <span class="inline-flex items-center gap-1.5 text-sm text-gray-700 font-medium bg-gray-50 px-3 py-1.5 rounded-lg">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <?= htmlspecialchars($row['kapasitas']); ?>
                      </span>
                    <?php else: ?>
                      <span class="text-sm text-gray-400 italic">Tidak ada data</span>
                    <?php endif; ?>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                      <?php if (strtolower($row['status']) === 'tersedia'): ?>
                        <span class="badge-status px-3 py-1.5 bg-green-500 text-white rounded-lg shadow-sm">
                          ✓ Tersedia
                        </span>
                        <a href="ajukan.php" class="badge-status px-4 py-1.5 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-all duration-200 shadow-sm hover:shadow-md">
                          Ajukan Peminjaman
                        </a>
                      <?php else: ?>
                        <span class="badge-status px-3 py-1.5 bg-red-500 text-white rounded-lg shadow-sm">
                          ✕ Tidak Tersedia
                        </span>
                        <button disabled class="badge-status px-4 py-1.5 bg-gray-300 text-gray-500 rounded-lg cursor-not-allowed opacity-60">
                          Tidak Dapat Diajukan
                        </button>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="4" class="text-center py-16">
                  <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                  </svg>
                  <p class="text-gray-500 font-semibold text-lg">Tidak ada data tersedia</p>
                  <p class="text-gray-400 text-sm mt-1">Silakan pilih filter atau periksa kembali pencarian Anda</p>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

 <!-- Kalender + Status Booking (Versi Rapi & Lurus) -->
<div class="max-w-7xl mx-auto px-6 mb-8 animate-slide-in" style="animation-delay: 0.3s;">
  <div class="bg-white rounded-2xl shadow-lg p-8 border-t-4 border-[#132544]">
    <div class="flex flex-col lg:flex-row gap-10 items-start lg:items-stretch">

      <!-- === Kalender === -->
      <div class="flex-1">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-12 h-12 rounded-xl bg-[#1E3A8A]/10 flex items-center justify-center text-2xl">
            📅
          </div>
          <div>
            <h3 class="font-bold text-xl text-[#1E3A8A]">Kalender Ketersediaan</h3>
            <p class="text-sm text-gray-600">Jadwal booking aset</p>
          </div>
        </div>

        <div class="bg-gradient-to-br from-[#F8FBFC] to-[#EAF6F8] p-6 rounded-xl shadow-inner w-full">
          <div class="flex justify-between items-center mb-5">
            <button id="prevMonth" 
              class="w-10 h-10 rounded-lg bg-white shadow hover:shadow-md hover:scale-105 transition-all duration-200 flex items-center justify-center text-[#1E3A8A] font-bold">
              ◀
            </button>
            <span id="monthYear" class="font-bold text-lg text-[#1E3A8A] tracking-wide">Loading...</span>
            <button id="nextMonth" 
              class="w-10 h-10 rounded-lg bg-white shadow hover:shadow-md hover:scale-105 transition-all duration-200 flex items-center justify-center text-[#1E3A8A] font-bold">
              ▶
            </button>
          </div>

          <!-- Header Hari -->
          <div class="grid grid-cols-7 text-center text-xs font-bold mb-3 text-gray-600 uppercase tracking-wider">
            <div>Min</div><div>Sen</div><div>Sel</div><div>Rab</div><div>Kam</div><div>Jum</div><div>Sab</div>
          </div>

          <!-- Grid Tanggal -->
          <div class="flex justify-center">
            <div id="calendarGrid" 
                 class="grid grid-cols-7 text-center text-sm gap-2 place-items-center w-full">
              <!-- Tanggal akan dimasukkan via JS -->
            </div>
          </div>
        </div>
      </div>

      <!-- === Status Booking (di samping kanan) === -->
      <div class="lg:w-80">
        <div class="bg-gradient-to-br from-[#EAF6F8] to-white p-6 rounded-2xl border border-[#132544]/10 shadow-inner sticky top-6">
          <h4 class="font-bold text-[#1E3A8A] mb-5 flex items-center gap-2 text-lg">
            <svg class="w-6 h-6 text-[#1E3A8A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Status Booking
          </h4>

          <!-- Keterangan warna -->
          <div class="space-y-3 mb-6">
            <div class="flex items-center gap-3 p-3 bg-white rounded-xl shadow-sm">
              <div class="w-5 h-5 bg-red-500 rounded-full shadow-md flex-shrink-0"></div>
              <span class="text-sm text-gray-700 font-medium">Sudah Dipesan</span>
            </div>
            <div class="flex items-center gap-3 p-3 bg-white rounded-xl shadow-sm">
              <div class="w-5 h-5 bg-[#1E3A8A] rounded-full shadow-md flex-shrink-0"></div>
              <span class="text-sm text-gray-700 font-medium">Hari Ini</span>
            </div>
            <div class="flex items-center gap-3 p-3 bg-white rounded-xl shadow-sm">
              <div class="w-5 h-5 bg-white border-2 border-[#132544]/30 rounded-full shadow-sm flex-shrink-0"></div>
              <span class="text-sm text-gray-700 font-medium">Tersedia</span>
            </div>
          </div>

          <!-- Daftar booking dinamis -->
          <div>
            <h5 class="font-bold text-[#1E3A8A] mb-3 text-sm flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              Daftar Tanggal Booking
            </h5>
            <ul id="bookedInfoList" class="space-y-2 text-xs text-gray-800"></ul>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>



</main>

<footer class="w-full bg-[#132544] text-white text-center py-3 mt-10">
  © <?= date('Y'); ?> Institut Teknologi PLN - Sistem Peminjaman Aset
</footer>

<script>
// Sidebar Toggle
const menuBtn = document.getElementById('menuBtn');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');

menuBtn.addEventListener('click', () => {
  sidebar.classList.toggle('-translate-x-full');
  overlay.classList.toggle('hidden');
});

function closeSidebar() {
  sidebar.classList.add('-translate-x-full');
  overlay.classList.add('hidden');
}

// Dropdown Menu
function toggleDropdown(id) {
  const submenu = document.getElementById('submenu-' + id);
  const icon = document.getElementById('icon-' + id);
  submenu.classList.toggle('hidden');
  icon.style.transform = submenu.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
}

function filterTable() {
  const input = document.getElementById("searchInput").value.toLowerCase();
  const table = document.getElementById("dataTable");
  const tr = table.getElementsByTagName("tr");

  for (let i = 1; i < tr.length; i++) {
    const td = tr[i].getElementsByTagName("td")[1];
    if (td) {
      const txtValue = td.textContent || td.innerText;
      tr[i].style.display = txtValue.toLowerCase().includes(input) ? "" : "none";
    }
  }
}

// ================= KALENDER =================
document.addEventListener("DOMContentLoaded", function() {
  const monthYear = document.getElementById("monthYear");
const calendarGrid = document.getElementById("calendarGrid");
const bookedInfoList = document.getElementById("bookedInfoList");
const prevMonthBtn = document.getElementById("prevMonth");
const nextMonthBtn = document.getElementById("nextMonth");

let currentDate = new Date();
const bookedDates = {
  "2025-11-03": "Ruang Aula dipakai Seminar",
  "2025-11-05": "Lab FTBE digunakan",
  "2025-11-10": "Ruang Rapat dipesan",
};

function renderCalendar(date) {
  const year = date.getFullYear();
  const month = date.getMonth();
  const monthNames = ["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
  monthYear.textContent = `${monthNames[month]} ${year}`;
  const firstDay = new Date(year, month, 1).getDay();
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  calendarGrid.innerHTML = "";
  bookedInfoList.innerHTML = "";

  for (let i = 0; i < firstDay; i++) {
    const empty = document.createElement("div");
    calendarGrid.appendChild(empty);
  }

  const today = new Date();
  for (let d = 1; d <= daysInMonth; d++) {
    const cell = document.createElement("div");
    cell.textContent = d;
    cell.classList.add("flex", "items-center", "justify-center", "w-10", "h-10", "rounded-lg", "transition-all", "duration-200", "font-medium");

    const dateKey = `${year}-${String(month + 1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;

    if (bookedDates[dateKey]) {
      cell.classList.add("bg-red-500", "text-white", "shadow-md", "cursor-not-allowed", "hover:scale-110", "font-bold");
      cell.title = bookedDates[dateKey];
      const li = document.createElement("li");
      li.className = "flex items-start gap-2 p-2 bg-white rounded-lg shadow-sm";
      li.innerHTML = `
        <span class="w-2 h-2 bg-red-500 rounded-full mt-1.5 flex-shrink-0"></span>
        <span><strong>${d} ${monthNames[month]}:</strong> ${bookedDates[dateKey]}</span>
      `;
      bookedInfoList.appendChild(li);
    } else if (
      d === today.getDate() && month === today.getMonth() && year === today.getFullYear()
    ) {
      cell.classList.add("bg-[#1E3A8A]", "text-white", "shadow-md", "hover:scale-110", "font-bold", "ring-2", "ring-[#1E3A8A]/30", "ring-offset-2");
      cell.title = "Hari ini";
    } else {
      cell.classList.add("hover:bg-[#1E3A8A]/20", "text-gray-700", "cursor-pointer", "hover:shadow-md", "hover:scale-110");
    }
    calendarGrid.appendChild(cell);
  }

  if (bookedInfoList.children.length === 0) {
    bookedInfoList.innerHTML = '<li class="text-center text-gray-500 py-2">Tidak ada booking bulan ini</li>';
  }
}

prevMonthBtn.addEventListener("click", () => {
  currentDate.setMonth(currentDate.getMonth() - 1);
  renderCalendar(currentDate);
});
nextMonthBtn.addEventListener("click", () => {
  currentDate.setMonth(currentDate.getMonth() + 1);
  renderCalendar(currentDate);
});
renderCalendar(currentDate);
});
</script>

</body>
</html>