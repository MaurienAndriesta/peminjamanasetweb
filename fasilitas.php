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

// ================= DATA FASILITAS =================
$data = [
    ['no'=>1,'nama_fasilitas'=>'Lapangan Futsal','kapasitas'=>'71 Orang s.d 100 Orang','internal_itpln'=>'','eksternal_itpln'=>'','keterangan'=>''],
    ['no'=>2,'nama_fasilitas'=>'Bus Eksekutif','kapasitas'=>'51 Orang s.d 70 Orang','internal_itpln'=>'','eksternal_itpln'=>'','keterangan'=>''],
    ['no'=>3,'nama_fasilitas'=>'LCD Projector','kapasitas'=>'31 Orang s.d 50 Orang','internal_itpln'=>'','eksternal_itpln'=>'','keterangan'=>''],
    ['no'=>4,'nama_fasilitas'=>'Whiteboard','kapasitas'=>'10 Orang s.d 30 Orang','internal_itpln'=>'','eksternal_itpln'=>'','keterangan'=>''],
];

// ================= FUNGSI RENDER MENU REKURSIF =================
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
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Halaman Daftar Fasilitas</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-[#D1E5EA] to-white min-h-screen">

<nav class="fixed top-0 left-0 right-0 bg-[#D1E5EA] shadow px-4 h-16 z-50 flex items-center gap-4">
  <!-- Tombol Hamburger -->
  <button id="hamburgerBtn" onclick="toggleSidebar()" 
    class="bg-gray-800 text-white p-3 rounded-md">☰</button>

  <!-- Search -->
  <form class="flex-1">
    <input id="searchInput" type="text" placeholder="Cari" 
      class="w-full px-4 py-2 rounded-full border border-gray-300 text-sm shadow-sm focus:ring focus:ring-blue-200">
  </form>

  <!-- User -->
  <div class="relative">
    <button id="userBtn" class="flex items-center gap-2 bg-gray-200 px-4 py-2 rounded-full text-sm shadow-sm">
      <span class="font-medium">Nama Pengguna</span> 
    </button>
    <!-- Dropdown User -->
    <div id="userDropdown" 
         class="hidden absolute right-0 mt-2 w-40 bg-white border border-gray-200 rounded-lg shadow-lg z-50">
      <a href="logout.php" 
         class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-t-lg">
         Keluar
      </a>
    </div>
  </div>
</nav>

<!-- Overlay -->
<div id="overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-40"></div>

<!-- Sidebar -->
<div id="sidebar" class="fixed top-0 left-0 w-72 h-full bg-gray-800 text-white transform -translate-x-full transition-transform duration-300 z-50">
  <div class="bg-gray-900 px-5 py-4 font-bold uppercase text-sm tracking-widest">Menu Utama</div>
  <nav class="p-2">
    <?php renderMenu($menu_items); ?>
  </nav>
</div>

<!-- Main -->
<main class="pt-20 px-6">
  <h2 class="text-xl font-semibold mb-4">Daftar Fasilitas</h2>
  <div class="bg-white border-2 border-blue-400 rounded-xl shadow-sm p-4 overflow-x-auto">
    <table class="w-full border-collapse text-sm" id="fasilitasTable">
      <thead>
        <tr class="bg-blue-50 text-gray-700">
          <th class="border border-gray-200 px-4 py-3 text-left">No</th>
          <th class="border border-gray-200 px-4 py-3 text-left">Foto</th>
          <th class="border border-gray-200 px-4 py-3 text-left">Nama Fasilitas</th>
          <th class="border border-gray-200 px-4 py-3 text-left">Kapasitas</th>
          <th class="border border-gray-200 px-4 py-3 text-left">Internal ITPLN</th>
          <th class="border border-gray-200 px-4 py-3 text-left">Eksternal ITPLN</th>
          <th class="border border-gray-200 px-4 py-3 text-left">Keterangan</th>
        </tr>
      </thead>
      <tbody class="text-gray-800">
        <?php foreach($data as $row): ?>
        <tr class="hover:bg-blue-50">
          <td class="border border-gray-200 px-4 py-3"><?= $row['no'] ?></td>
          <td class="border border-gray-200 px-4 py-3 text-center">-</td>
          <td class="border border-gray-200 px-4 py-3"><?= $row['nama_fasilitas'] ?></td>
          <td class="border border-gray-200 px-4 py-3"><?= $row['kapasitas'] ?></td>
          <td class="border border-gray-200 px-4 py-3"><?= $row['internal_itpln'] ?></td>
          <td class="border border-gray-200 px-4 py-3"><?= $row['eksternal_itpln'] ?></td>
          <td class="border border-gray-200 px-4 py-3"><?= $row['keterangan'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>

<footer class="fixed bottom-0 left-0 w-full bg-gray-800 text-white text-center py-3">
  © <?= date('Y'); ?> Institut Teknologi PLN - Sistem Peminjaman Aset
</footer>


<script>
// Sidebar toggle
const hamburgerBtn = document.getElementById('hamburgerBtn');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');

hamburgerBtn.addEventListener('click', () => {
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

// Klik di luar -> dropdown nutup
document.addEventListener('click', (e) => {
  if (!userBtn.contains(e.target) && !userDropdown.contains(e.target)) {
    userDropdown.classList.add('hidden');
  }
});

// ================= SEARCH FUNCTION =================
const searchInput = document.getElementById("searchInput");
const tableRows = document.querySelectorAll("#fasilitasTable tbody tr");

searchInput.addEventListener("keyup", function () {
  let keyword = this.value.toLowerCase();
  tableRows.forEach(row => {
    let rowText = row.textContent.toLowerCase();
    row.style.display = rowText.includes(keyword) ? "" : "none";
  });
});
</script>
</body>
</html>
