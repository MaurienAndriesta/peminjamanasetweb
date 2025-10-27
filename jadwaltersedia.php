<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['fullname'])) {
    header("Location: login.php");
    exit;
}
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

// ================= FUNGSI RENDER MENU =================
function renderMenu($items, $prefix = 'root') {
  echo "<ul class='list-none pl-3 space-y-1'>";
  foreach ($items as $index => $item) {
    $uniqueId = $prefix . '-' . $index;
    if (isset($item['type']) && $item['type'] === 'dropdown') {
      echo "
      <li>
        <button onclick='toggleDropdown(\"{$uniqueId}\")'
          class='flex justify-between items-center w-full px-3 py-2 text-left rounded-lg hover:bg-gray-700 transition'>
          <span>{$item['title']}</span>
          <span id='icon-{$uniqueId}'>▼</span>
        </button>
        <div class='hidden pl-4 border-l border-gray-700 ml-2 mt-1' id='submenu-{$uniqueId}'>";
      renderMenu($item['submenu'], $uniqueId);
      echo "</div>
      </li>";
    } else {
      echo "<li><a href='{$item['url']}' class='block px-3 py-2 rounded-lg hover:bg-gray-700 transition'>{$item['title']}</a></li>";
    }
  }
  echo "</ul>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Jadwal Ketersediaan Aset</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-[#D1E5EA] min-h-screen font-sans text-gray-800">
<!-- Overlay -->
<div id="overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-40" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<div id="sidebar" class="fixed top-0 left-0 w-72 h-full bg-gray-800 text-white text-base transform -translate-x-full transition-transform duration-300 z-50 shadow-xl">
  <div class="bg-gray-900 px-6 py-5 font-bold uppercase tracking-widest text-center border-b border-gray-700 text-lg">
    Menu Utama
  </div>
  <nav class="p-3 space-y-1">
    <?php renderMenu($menu_items); ?>
  </nav>
</div>


<!-- MAIN -->
<main class="p-6 transition-all duration-300">

  <!-- Header -->
  <div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-4">
      <button id="menuBtn" class="p-2 bg-gray-800 text-white rounded-md shadow hover:bg-gray-700 transition">
        ☰
      </button>
      <div class="flex flex-col items-center justify-center py-6"></div>
      <h2 class="absolute left-1/2 transform -translate-x-1/2 text-3xl font-bold text-gray-800">Jadwal Ketersediaan Aset</h2>
    </div>
    <div class="relative">
      <button id="userBtn" class="flex items-center gap-2 bg-gray-200 px-4 py-2 rounded-full text-sm shadow-sm hover:bg-gray-300 transition">
        <span class="font-medium"><?= htmlspecialchars($_SESSION['fullname']); ?></span>  
      </button>
      <div id="userDropdown" class="hidden absolute right-0 mt-2 w-40 bg-white border border-gray-200 rounded-lg shadow-lg z-50">
        <a href="halamanutama.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-t-lg">
          Keluar
        </a>
      </div>
    </div>
  </div>

 <!-- Filter -->
<div class="flex flex-wrap gap-4 items-center mb-6">
  <!-- Dropdown Utama -->
  <select id="filterSelect" class="border border-gray-300 rounded-lg px-3 py-2 text-sm shadow-sm bg-white hover:border-indigo-400 transition">
    <option value="">-- Semua --</option>
    <option value="Ruang Multiguna">Ruang Multiguna</option>
    <option value="Fasilitas">Fasilitas</option>
    <option value="Usaha">Usaha</option>
    <option value="Laboratorium">Laboratorium</option>
  </select>

  <!-- Dropdown Submenu Laboratorium -->
  <select id="labSubmenu" class="hidden border border-gray-300 rounded-lg px-3 py-2 text-sm shadow-sm bg-white hover:border-indigo-400 transition">
    <option value="">-- Pilih Laboratorium --</option>
    <option value="FTBE">FTBE</option>
    <option value="FTEN">FTEN</option>
    <option value="FTIK">FTIK</option>
    <option value="FKET">FKET</option>
  </select>

  <!-- Kolom Pencarian -->
  <input id="searchInput" type="text" placeholder="Cari nama ruangan..." class="flex-1 px-4 py-2 rounded-full border border-gray-300 text-sm shadow-sm focus:ring-2 focus:ring-indigo-400 transition">
</div>

  <!-- Tabel -->
  <div class="bg-white border-2 border-indigo-400 rounded-xl shadow-lg p-4 overflow-x-auto mb-8">
    <table class="w-full text-sm border-collapse" id="dataTable">
      <thead>
        <tr class="bg-indigo-100 text-gray-700">
          <th class="border border-gray-200 px-4 py-2 text-left">No</th>
          <th class="border border-gray-200 px-4 py-2 text-left">Nama Ruangan / Jenis Pelayanan</th>
          <th class="border border-gray-200 px-4 py-2 text-left">Kapasitas</th>
          <th class="border border-gray-200 px-4 py-2 text-left">Status</th>
        </tr>
      </thead>
      <tbody id="tableBody" class="text-gray-800">
        <!-- Tersedia -->
        <tr class="hover:bg-indigo-50 transition">
          <td class="border border-gray-200 px-4 py-2">1</td>
          <td class="border border-gray-200 px-4 py-2">Lab. Information Retrieval</td>
          <td class="border border-gray-200 px-4 py-2">15 Orang</td>
          <td class="border border-gray-200 px-4 py-2">
            <div class="flex flex-col items-start gap-1">
              <span class="bg-green-500 text-white text-xs px-2 py-1 rounded">Tersedia</span>
              <a href="ajukan.php" class="bg-indigo-100 hover:bg-indigo-200 text-indigo-700 text-xs px-2 py-1 rounded transition">Ajukan</a>
            </div>
          </td>
        </tr>

        <!-- Tidak Tersedia -->
        <tr class="hover:bg-indigo-50 transition">
          <td class="border border-gray-200 px-4 py-2">2</td>
          <td class="border border-gray-200 px-4 py-2">Lab.Fisika</td>
          <td class="border border-gray-200 px-4 py-2">20 Orang</td>
          <td class="border border-gray-200 px-4 py-2">
            <div class="flex flex-col items-start gap-1">
              <span class="bg-red-500 text-white text-xs px-2 py-1 rounded">Tidak Tersedia</span>
              <button disabled class="bg-gray-200 text-gray-400 text-xs px-2 py-1 rounded cursor-not-allowed">Ajukan</button>
            </div>
          </td>
        </tr>

        <!-- Contoh lain -->
        <tr class="hover:bg-indigo-50 transition">
          <td class="border border-gray-200 px-4 py-2">3</td>
          <td class="border border-gray-200 px-4 py-2">Auditorium</td>
          <td class="border border-gray-200 px-4 py-2">100 Orang</td>
          <td class="border border-gray-200 px-4 py-2">
            <div class="flex flex-col items-start gap-1">
              <span class="bg-green-500 text-white text-xs px-2 py-1 rounded">Tersedia</span>
              <a href="ajukan.php" class="bg-indigo-100 hover:bg-indigo-200 text-indigo-700 text-xs px-2 py-1 rounded transition">Ajukan</a>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- Kalender -->
  <div class="bg-white border-2 border-indigo-400 rounded-xl shadow-lg p-4">
    <h3 class="font-semibold mb-3 text-gray-800">Kalender Ketersediaan Aset</h3>
    <div class="flex justify-center">
      <div class="bg-gray-50 p-4 rounded-lg shadow-inner">
        <div class="flex justify-between items-center mb-2 text-sm font-medium">
          <button id="prevMonth" class="px-2 text-indigo-600 hover:text-indigo-800">◀</button>
          <span id="monthYear">Loading...</span>
          <button id="nextMonth" class="px-2 text-indigo-600 hover:text-indigo-800">▶</button>
        </div>
        <div class="grid grid-cols-7 text-center text-xs font-semibold mb-2 text-gray-600">
          <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
        </div>
        <div id="calendarGrid" class="grid grid-cols-7 text-center text-sm gap-y-2"></div>
        <div class="flex items-center mt-3 text-xs text-gray-600">
          <span class="inline-block w-3 h-3 bg-red-500 rounded-full mr-2"></span> Booked
        </div>
      </div>
    </div>
  </div>
</main>

<script>
// Sidebar toggle
const menuBtn = document.getElementById('menuBtn');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
menuBtn.addEventListener('click', () => {
  sidebar.classList.toggle('-translate-x-full');
  overlay.classList.toggle('hidden');
});
overlay.addEventListener('click', () => {
  sidebar.classList.add('-translate-x-full');
  overlay.classList.add('hidden');
});

// Dropdown toggle
function toggleDropdown(id) {
  const submenu = document.getElementById('submenu-' + id);
  const icon = document.getElementById('icon-' + id);
  submenu.classList.toggle('hidden');
  icon.textContent = submenu.classList.contains('hidden') ? '▼' : '▲';
}

// User dropdown
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
 
// ==================== FILTER & SUBMENU ====================
  const filterSelect = document.getElementById("filterSelect");
  const labSubmenu = document.getElementById("labSubmenu");
  const searchInput = document.getElementById("searchInput");
  const tableBody = document.getElementById("tableBody");

  // Tampilkan / sembunyikan dropdown laboratorium
  filterSelect.addEventListener("change", () => {
    if (filterSelect.value === "Laboratorium") {
      labSubmenu.classList.remove("hidden");
    } else {
      labSubmenu.classList.add("hidden");
      labSubmenu.value = "";
    }
    filterTable();
  });

  // Jalankan filter saat submenu laboratorium berubah
  labSubmenu.addEventListener("change", filterTable);
  searchInput.addEventListener("keyup", filterTable);

  function filterTable() {
    const searchValue = searchInput.value.toLowerCase();
    const filterValue = filterSelect.value.toLowerCase();
    const labValue = labSubmenu.value.toLowerCase();

    const rows = tableBody.getElementsByTagName("tr");

    for (let row of rows) {
      const namaCell = row.cells[1].textContent.toLowerCase();

      const matchSearch = namaCell.includes(searchValue);
      const matchFilter =
        filterValue === "" ||
        namaCell.includes(filterValue) ||
        (filterValue === "laboratorium" && labValue !== "" && namaCell.includes(labValue));

      row.style.display = matchSearch && matchFilter ? "" : "none";
    }
  }
// ==================== KALENDER REALTIME ====================
const monthYear = document.getElementById("monthYear");
const calendarGrid = document.getElementById("calendarGrid");
const prevMonthBtn = document.getElementById("prevMonth");
const nextMonthBtn = document.getElementById("nextMonth");

let currentDate = new Date();

function renderCalendar(date) {
  const year = date.getFullYear();
  const month = date.getMonth();
  const monthNames = ["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
  monthYear.textContent = `${monthNames[month]} ${year}`;
  const firstDay = new Date(year, month, 1).getDay();
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  calendarGrid.innerHTML = "";
  for (let i = 0; i < firstDay; i++) {
    const empty = document.createElement("div");
    calendarGrid.appendChild(empty);
  }
  const today = new Date();
  const bookedDates = [3, 4, 5, 10];
  for (let d = 1; d <= daysInMonth; d++) {
    const cell = document.createElement("div");
    const isToday = d === today.getDate() && month === today.getMonth() && year === today.getFullYear();
    if (bookedDates.includes(d)) {
      cell.className = "bg-red-500 text-white rounded-full w-8 h-8 mx-auto flex items-center justify-center";
    } else if (isToday) {
      cell.className = "bg-indigo-500 text-white rounded-full w-8 h-8 mx-auto flex items-center justify-center";
    } else {
      cell.className = "w-8 h-8 mx-auto flex items-center justify-center text-gray-700";
    }
    cell.textContent = d;
    calendarGrid.appendChild(cell);
  }
}
prevMonthBtn.addEventListener("click", () => { currentDate.setMonth(currentDate.getMonth() - 1); renderCalendar(currentDate); });
nextMonthBtn.addEventListener("click", () => { currentDate.setMonth(currentDate.getMonth() + 1); renderCalendar(currentDate); });
renderCalendar(currentDate);
</script>
</body>
</html>
