<?php
// ================= DATA MENU =================
$menu_items = [
        [
          'title' => 'Beranda', 'url' => 'dashboarduser.php'],
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

/// ================= FUNGSI RENDER MENU REKURSIF =================
function renderMenu($items, $prefix = 'root') {
    echo "<ul class='list-none pl-3 space-y-1'>";
    foreach ($items as $index => $item) {
        $uniqueId = $prefix . '-' . $index;
        if (isset($item['type']) && $item['type'] === 'dropdown') {
            echo "
            <li>
                <button onclick='toggleDropdown(\"{$uniqueId}\")'
                    class='flex justify-between items-center w-full px-3 py-2 text-left rounded hover:bg-gray-700'>
                    <span>{$item['title']}</span>
                    <span id='icon-{$uniqueId}'>▼</span>
                </button>
                <div class='hidden pl-4' id='submenu-{$uniqueId}'>";
                    renderMenu($item['submenu'], $uniqueId);
            echo "</div>
            </li>";
        } else {
            echo "<li><a href='{$item['url']}' class='block px-3 py-2 rounded hover:bg-gray-700'>{$item['title']}</a></li>";
        }
    }
    echo "</ul>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Jadwal Ketersediaan Aset</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-[#D1E5EA] to-white min-h-screen">

<!-- Overlay -->
<div id="overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-40" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<div id="sidebar" 
     class="fixed top-0 left-0 w-64 h-full bg-gray-800 text-white text-sm transform -translate-x-full transition-transform duration-300 z-50">
  <div class="bg-gray-900 px-5 py-4 font-bold uppercase tracking-widest">Menu Utama</div>
  <nav class="p-2">
    <?php renderMenu($menu_items); ?>
  </nav>
</div>

<!-- Main -->
<main class="flex-1 p-6">
  <!-- Header Bar -->
  <div class="flex items-center justify-between mb-6">
    <!-- Tombol Sidebar + Judul -->
    <div class="flex items-center gap-4">
      <button id="menuBtn" class="p-2 bg-gray-800 text-white rounded-md shadow-md">
        ☰
      </button>
      <h2 class="text-2xl font-bold">Jadwal Ketersediaan Aset</h2>
    </div>

    <!-- User -->
    <div class="relative">
      <button id="userBtn" class="flex items-center gap-2 bg-gray-200 px-4 py-2 rounded-full text-sm shadow-sm">
        <span class="font-medium">Nama Pengguna</span> 
      </button>
      <!-- Dropdown User -->
      <div id="userDropdown" 
           class="hidden absolute right-0 mt-2 w-40 bg-white border border-gray-200 rounded-lg shadow-lg z-50">
        <a href="halamanutama.php" 
           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-t-lg">
           Keluar
        </a>
      </div>
    </div>
  </div>

  <!-- Filter -->
  <!-- Filter -->
<div class="flex flex-wrap gap-4 items-center mb-4">
  <select id="filterSelect" class="border border-gray-300 rounded px-3 py-2 text-sm">
    <option value="">-- Semua --</option>
    <option value="Ruang Multiguna">Ruang Multiguna</option>
    <option value="Fasilitas">Fasilitas</option>
    <option value="Usaha">Usaha</option>
    <optgroup label="Laboratorium">
      <option value="Fakultas Teknologi dan Bisnis Energi (FTBE)">Fakultas Teknologi dan Bisnis Energi (FTBE)</option>
      <option value="Fakultas Telematika Energi (FTEN)">Fakultas Telematika Energi (FTEN)</option>
      <option value="Fakultas Teknologi Infrastruktur dan Kewilayahan (FTIK)">Fakultas Teknologi Infrastruktur dan Kewilayahan (FTIK)</option>
      <option value="Fakultas Ketenagalistrikan dan Energi Terbarukan (FKET)">Fakultas Ketenagalistrikan dan Energi Terbarukan (FKET)</option>
    </optgroup>
  </select>
  <input id="searchInput" type="text" placeholder="Cari" 
         class="flex-1 px-4 py-2 rounded-full border border-gray-300 text-sm shadow-sm">
</div>


  <!-- Tabel -->
  <div class="bg-white border-2 border-indigo-400 rounded-xl shadow p-4 overflow-x-auto mb-6">
    <table class="w-full text-sm border-collapse" id="dataTable">
      <thead>
        <tr class="bg-indigo-50 text-gray-700">
          <th class="border border-gray-200 px-4 py-2 text-left">No</th>
          <th class="border border-gray-200 px-4 py-2 text-left">Nama Ruangan / Jenis Pelayanan</th>
          <th class="border border-gray-200 px-4 py-2 text-left">Kapasitas</th>
          <th class="border border-gray-200 px-4 py-2 text-left">Status</th>
        </tr>
      </thead>
      <tbody id="tableBody" class="text-gray-800">
        <tr class="hover:bg-indigo-50">
          <td class="border border-gray-200 px-4 py-2">1</td>
          <td class="border border-gray-200 px-4 py-2">Lab. Information Retrieval</td>
          <td class="border border-gray-200 px-4 py-2">15 Orang</td>
          <td class="border border-gray-200 px-4 py-2">
            <div class="flex flex-col items-start gap-1"> 
              <span class="bg-green-500 text-white text-xs px-2 py-1 rounded">Tersedia</span>
              <a href="ajukan.php" 
              class="bg-gray-200 hover:bg-gray-300 text-xs px-2 py-1 rounded block text-center">
              Ajukan </a>
            </div>
          </td>
        </tr>
        <tr class="hover:bg-indigo-50">
          <td class="border border-gray-200 px-4 py-2">2</td>
          <td class="border border-gray-200 px-4 py-2">Auditorium</td>
          <td class="border border-gray-200 px-4 py-2">750 Orang</td>
          <td class="border border-gray-200 px-4 py-2">
            <span class="bg-red-500 text-white text-xs px-2 py-1 rounded">Tidak Tersedia</span>
          </td>
        </tr>
        <tr class="hover:bg-indigo-50">
          <td class="border border-gray-200 px-4 py-2">3</td>
          <td class="border border-gray-200 px-4 py-2">Lapangan Futsal</td>
          <td class="border border-gray-200 px-4 py-2">Standar</td>
          <td class="border border-gray-200 px-4 py-2">
            <span class="bg-red-500 text-white text-xs px-2 py-1 rounded">Tidak Tersedia</span>
          </td>
        </tr>
        <tr class="hover:bg-indigo-50">
          <td class="border border-gray-200 px-4 py-2">4</td>
          <td class="border border-gray-200 px-4 py-2">Bus Eksekutif</td>
          <td class="border border-gray-200 px-4 py-2">30 Seat</td>
          <td class="border border-gray-200 px-4 py-2">
            <div class="flex flex-col items-start gap-1">
              <span class="bg-green-500 text-white text-xs px-2 py-1 rounded">Tersedia</span>
              <a href="ajukan.php" 
              class="bg-gray-200 hover:bg-gray-300 text-xs px-2 py-1 rounded block text-center">
              Ajukan </a>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- Kalender -->
  <div class="bg-white border-2 border-indigo-400 rounded-xl shadow p-4">
    <h3 class="font-semibold mb-2">Kalender Ketersediaan Aset</h3>
    <div class="flex justify-center">
      <div class="bg-gray-100 p-4 rounded-lg shadow">
        <div class="flex justify-between items-center mb-2">
          <button class="px-2">◀</button>
          <span class="font-semibold">September 2025</span>
          <button class="px-2">▶</button>
        </div>
        <div class="grid grid-cols-7 text-center text-sm font-medium mb-2">
          <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div>
          <div>Thu</div><div>Fri</div><div>Sat</div>
        </div>
        <div class="grid grid-cols-7 text-center text-sm gap-y-2">
          <div class="text-gray-400">31</div><div>1</div><div>2</div>
          <div class="bg-red-500 text-white rounded-full w-8 h-8 mx-auto flex items-center justify-center">3</div>
          <div class="bg-red-500 text-white rounded-full w-8 h-8 mx-auto flex items-center justify-center">4</div>
          <div class="bg-red-500 text-white rounded-full w-8 h-8 mx-auto flex items-center justify-center">5</div>
          <div>6</div>
          <div>7</div><div>8</div><div>9</div>
          <div class="bg-red-500 text-white rounded-full w-8 h-8 mx-auto flex items-center justify-center">10</div>
          <div>11</div><div>12</div><div>13</div>
          <div>14</div><div>15</div><div>16</div><div>17</div><div>18</div><div>19</div><div>20</div>
          <div>21</div><div>22</div><div>23</div><div>24</div><div>25</div><div>26</div><div>27</div>
          <div>28</div><div>29</div><div>30</div><div>1</div><div>2</div><div>3</div>
        </div>
        <div class="flex items-center mt-3 text-xs">
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

// Dropdown toggle menu
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



// ======== Fungsi Pencarian ========
const searchInput = document.getElementById('searchInput');
const filterSelect = document.getElementById('filterSelect');
const tableBody = document.getElementById('tableBody');

function filterTable() {
  const searchValue = searchInput.value.toLowerCase();
  const filterValue = filterSelect.value.toLowerCase();

  const rows = tableBody.getElementsByTagName('tr');
  for (let row of rows) {
    const namaCell = row.cells[1].textContent.toLowerCase();
    let matchSearch = namaCell.includes(searchValue);
    let matchFilter = filterValue === "" || namaCell.includes(filterValue);

    if (matchSearch && matchFilter) {
      row.style.display = "";
    } else {
      row.style.display = "none";
    }
  }
}

searchInput.addEventListener('keyup', filterTable);
filterSelect.addEventListener('change', filterTable);
</script>
</body>
</html>
