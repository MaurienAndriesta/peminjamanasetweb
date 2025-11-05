<?php
session_start();
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = "Nama Pengguna"; 
}

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
                    ['title' => 'Fakultas Teknologi dan Infrastruktur Kewilayahan (FTIK)', 'url' => 'labftik.php'],
                    ['title' => 'Fakultas Ketenagalistrikan dan Energi Terbarukan (FKET)', 'url' => 'labfket.php'],
                ]
            ]
        ]
    ],
    ['title' => 'Jadwal Ketersediaan', 'url' => 'jadwaltersedia.php'],
    ['title' => 'Status Peminjaman', 'url' => 'status.php'],
    ['title' => 'Riwayat Peminjaman', 'url' => 'riwayat.php'],
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

$user_name = $_SESSION['user'];
$total_peminjaman_aktif = 12;
$total_menunggu_persetujuan = 3;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Sistem Peminjaman Aset</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif !important;
    }
    .rotate-180 {
      transform: rotate(180deg);
    }
    @keyframes slideIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .animate-slide-in {
      animation: slideIn 0.5s ease-out;
    }
  </style>
</head>
<body class="bg-gradient-to-br from-[#D1E5EA] via-[#E8F4F8] to-[#D1E5EA] min-h-screen text-gray-800">

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

<!-- Menu Button -->
<div class="fixed top-2 left-2 z-50 px-5 py-5">
  <button id="menuBtn" onclick="toggleSidebar()" 
    class="bg-gray-800 text-white p-3 rounded-md shadow-md hover:bg-gray-700 active:scale-95 transition-all duration-200">
    ☰
  </button>
</div>


<!-- Main Content --> 
<main class="ml-0 transition-all duration-300 px-6 md:px-12 py-8 md:py-12">
  <div class="max-w-7xl mx-auto">
    <!-- Header Section -->
    <div class="flex justify-between items-start flex-wrap gap-6 mb-12 animate-slide-in">
      <div>
        <h1 class="text-4xl md:text-5xl font-extrabold text-[#1E3A8A] leading-tight mb-2">
          Selamat Datang 👋
        </h1>
        <p class="text-lg text-gray-600 font-medium">
          Sistem Peminjaman Aset Kampus Institut Teknologi PLN
        </p>
      </div>

      <!-- User Dropdown -->
      <div class="relative">
        <button id="userBtn" 
          class="bg-white px-6 py-3.5 rounded-xl text-sm font-semibold text-[#1E3A8A] border-2 border-[#132544]/20 hover:border-[#132544] hover:shadow-lg transition-all duration-300 flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
          </svg>
          <?= htmlspecialchars($user_name); ?>
        </button>
        <div id="userDropdown" 
             class="hidden absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden">
          <a href="halamanutama.php" 
             class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-[#1E3A8A]/10 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
            </svg>
            Keluar
          </a>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
      <!-- Left side -->
      <div class="xl:col-span-2 space-y-8">

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Card 1 -->
          <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl p-8 hover:scale-[1.02] transition-all duration-300 border-l-4 border-[#132544] group cursor-default animate-slide-in">
            <div class="flex items-start justify-between mb-6">
              <div class="flex-1">
                <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Peminjaman Aktif</p>
                <h3 class="text-5xl font-extrabold text-[#1E3A8A] mb-1"><?= $total_peminjaman_aktif; ?></h3>
                <p class="text-sm text-gray-600">Total peminjaman yang sedang berjalan</p>
              </div>
              <div class="w-16 h-16 flex items-center justify-center rounded-2xl bg-gradient-to-br from-[#132544] to-[#1E3A8A] text-white text-3xl shadow-lg group-hover:scale-110 transition-transform duration-300">
                📋
              </div>
            </div>
            <div class="pt-4 border-t border-gray-100">
              <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                Status terkini
              </div>
            </div>
          </div>

          <!-- Card 2 -->
          <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl p-8 hover:scale-[1.02] transition-all duration-300 border-l-4 border-yellow-400 group cursor-default animate-slide-in" style="animation-delay: 0.1s">
            <div class="flex items-start justify-between mb-6">
              <div class="flex-1">
                <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Menunggu Persetujuan</p>
                <h3 class="text-5xl font-extrabold text-yellow-500 mb-1"><?= $total_menunggu_persetujuan; ?></h3>
                <p class="text-sm text-gray-600">Pengajuan yang menunggu validasi</p>
              </div>
              <div class="w-16 h-16 flex items-center justify-center rounded-2xl bg-gradient-to-br from-yellow-400 to-yellow-500 text-white text-3xl shadow-lg group-hover:scale-110 transition-transform duration-300">
                ⏳
              </div>
            </div>
            <div class="pt-4 border-t border-gray-100">
              <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                </svg>
                Proses verifikasi
              </div>
            </div>
          </div>
        </div>

        <!-- Tips Section -->
        <div class="bg-gradient-to-br from-[#EAF6F8] to-white rounded-2xl shadow-lg p-8 border border-[#132544]/10 animate-slide-in" style="animation-delay: 0.2s">
          <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-yellow-400 to-yellow-500 flex items-center justify-center text-2xl shadow-md">
              💡
            </div>
            <div>
              <h3 class="font-bold text-xl text-[#1E3A8A]">Tips Peminjaman</h3>
              <p class="text-sm text-gray-600">Panduan untuk peminjaman yang lancar</p>
            </div>
          </div>
          <div class="grid md:grid-cols-2 gap-4">
            <div class="flex items-start gap-3 p-4 bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow">
              <div class="w-8 h-8 rounded-lg bg-[#1E3A8A]/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                <span class="text-lg">📅</span>
              </div>
              <div>
                <p class="text-sm font-semibold text-gray-800 mb-1">Ajukan Lebih Awal</p>
                <p class="text-xs text-gray-600 leading-relaxed">Minimal 2 hari sebelum tanggal penggunaan</p>
              </div>
            </div>
            <div class="flex items-start gap-3 p-4 bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow">
              <div class="w-8 h-8 rounded-lg bg-[#1E3A8A]/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                <span class="text-lg">✅</span>
              </div>
              <div>
                <p class="text-sm font-semibold text-gray-800 mb-1">Cek Ketersediaan</p>
                <p class="text-xs text-gray-600 leading-relaxed">Pastikan aset tersedia pada tanggal yang diinginkan</p>
              </div>
            </div>
            <div class="flex items-start gap-3 p-4 bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow">
              <div class="w-8 h-8 rounded-lg bg-[#1E3A8A]/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                <span class="text-lg">📝</span>
              </div>
              <div>
                <p class="text-sm font-semibold text-gray-800 mb-1">Isi Detail Lengkap</p>
                <p class="text-xs text-gray-600 leading-relaxed">Lengkapi form peminjaman dengan jelas dan detail</p>
              </div>
            </div>
            <div class="flex items-start gap-3 p-4 bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow">
              <div class="w-8 h-8 rounded-lg bg-[#1E3A8A]/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                <span class="text-lg">🔄</span>
              </div>
              <div>
                <p class="text-sm font-semibold text-gray-800 mb-1">Kembalikan Tepat Waktu</p>
                <p class="text-xs text-gray-600 leading-relaxed">Sesuai jadwal yang telah disepakati</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right side (Calendar) -->
      <div class="bg-white rounded-2xl shadow-lg p-8 border-t-4 border-[#132544] animate-slide-in" style="animation-delay: 0.3s">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-12 h-12 rounded-xl bg-[#1E3A8A]/10 flex items-center justify-center text-2xl">
            📅
          </div>
          <div>
            <h3 class="font-bold text-xl text-[#1E3A8A]">Kalender Ketersediaan</h3>
            <p class="text-sm text-gray-600">Jadwal booking aset</p>
          </div>
        </div>

        <div class="bg-gradient-to-br from-[#F8FBFC] to-[#EAF6F8] p-6 rounded-xl shadow-inner">
          <div class="flex justify-between items-center mb-5">
            <button id="prevMonth" class="w-10 h-10 rounded-lg bg-white shadow hover:shadow-md hover:scale-105 transition-all duration-200 flex items-center justify-center text-[#1E3A8A] font-bold">
              ◀
            </button>
            <span id="monthYear" class="font-bold text-lg text-[#1E3A8A] tracking-wide">Loading...</span>
            <button id="nextMonth" class="w-10 h-10 rounded-lg bg-white shadow hover:shadow-md hover:scale-105 transition-all duration-200 flex items-center justify-center text-[#1E3A8A] font-bold">
              ▶
            </button>
          </div>

          <div class="grid grid-cols-7 text-center text-xs font-bold mb-3 text-gray-600 uppercase tracking-wider">
            <div>Min</div><div>Sen</div><div>Sel</div><div>Rab</div><div>Kam</div><div>Jum</div><div>Sab</div>
          </div>

          <div id="calendarGrid" class="grid grid-cols-7 text-center text-sm gap-1"></div>

          <div class="flex items-center gap-4 mt-6 pt-4 border-t border-gray-300">
            <div class="flex items-center gap-2 text-xs text-gray-600">
              <span class="w-4 h-4 bg-[#1E3A8A] rounded-full shadow"></span> 
              <span>Hari Ini</span>
            </div>
            <div class="flex items-center gap-2 text-xs text-gray-600">
              <span class="w-4 h-4 bg-red-500 rounded-full shadow"></span> 
              <span>Booked</span>
            </div>
          </div>

          <div id="bookedList" class="mt-4 text-xs text-gray-800 border-t border-gray-300 pt-4">
            <h4 class="font-bold text-sm text-[#1E3A8A] mb-3 flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              Keterangan Booking
            </h4>
            <ul class="space-y-2" id="bookedInfoList"></ul>
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
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('-translate-x-full');
  document.getElementById('overlay').classList.toggle('hidden');
  document.getElementById('menuBtn').classList.toggle('hidden');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.add('-translate-x-full');
  document.getElementById('overlay').classList.add('hidden');
  document.getElementById('menuBtn').classList.remove('hidden');
}
function toggleDropdown(id) {
  const submenu = document.getElementById("submenu-" + id);
  const icon = document.getElementById("icon-" + id);
  submenu.classList.toggle("hidden");
  icon.classList.toggle("rotate-180");
}

// Dropdown user
const userBtn = document.getElementById('userBtn');
const userDropdown = document.getElementById('userDropdown');
userBtn.addEventListener('click', (e) => {
  e.stopPropagation();
  userDropdown.classList.toggle('hidden');
});
document.addEventListener('click', (e) => {
  if (!userBtn.contains(e.target) && !userDropdown.contains(e.target)) {
    userDropdown.classList.add('hidden');
  }
});

// === Kalender ===
const monthYear = document.getElementById("monthYear");
const calendarGrid = document.getElementById("calendarGrid");
const bookedInfoList = document.getElementById("bookedInfoList");
const prevMonthBtn = document.getElementById("prevMonth");
const nextMonthBtn = document.getElementById("nextMonth");

let currentDate = new Date();
const bookedDates = {
  "2025-10-03": "Ruang Aula dipakai Seminar",
  "2025-10-04": "Aula Kampus dipesan",
  "2025-10-05": "Lab FTBE digunakan",
  "2025-10-10": "Ruang Rapat sudah dipesan"
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
      li.className = "flex items-start gap-2 p-2 bg-white rounded-lg";
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
</script>
</body>
</html>