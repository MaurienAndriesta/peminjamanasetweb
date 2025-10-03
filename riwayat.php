<?php
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

// ================= DATA PEMINJAMAN =================
$peminjaman = [
    [
        "nama" => "Laboratorium Intelligent Computing",
        "tanggal" => "22 September 2025",
        "waktu" => "08:00 - 11:30",
        "agenda" => "Praktikum Basis Data",
        "status" => "Selesai"
    ],
    [
        "nama" => "Ruang Pembangkit",
        "tanggal" => "28 September 2025",
        "waktu" => "08:00 - 11:30",
        "agenda" => "Workshop Nasional",
        "status" => "Ditolak"
    ],
    [
        "nama" => "Ruang Auditorium",
        "tanggal" => "02 Oktober 2025",
        "waktu" => "13:00 - 16:30",
        "agenda" => "Seminar AI",
        "status" => "Selesai"
    ]
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
  <title>Riwayat Peminjaman</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#D1E5EA] min-h-screen">

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
  <!-- Header -->
  <div class="flex items-center justify-between mb-6">
    <!-- Tombol Sidebar + Judul -->
    <div class="flex items-center gap-4">
      <button id="menuBtn" class="p-2 bg-gray-800 text-white rounded-md shadow-md">
        ☰
      </button>
    </div>
  </div>

  <!-- Konten -->
  <div class="bg-[#132544] text-white p-4 rounded-t-2xl text-center font-bold text-lg">
    RIWAYAT PEMINJAMAN ASET
  </div>

  <div class="bg-white shadow rounded-b-2xl p-6">
    <h2 class="text-center font-bold text-lg mb-6">RIWAYAT PEMINJAMAN SAYA</h2>

    <!-- Statistik -->
    <div class="grid grid-cols-4 gap-4 text-center mb-6">
      <div class="bg-purple-200 p-4 rounded-xl">
        <div class="text-2xl font-bold">10</div>
        <div>Total Peminjaman</div>
      </div>
      <div class="bg-green-300 p-4 rounded-xl">
        <div class="text-2xl font-bold">8</div>
        <div>Selesai</div>
      </div>
      <div class="bg-yellow-200 p-4 rounded-xl">
        <div class="text-2xl font-bold">1</div>
        <div>Sedang Dipinjam</div>
      </div>
      <div class="bg-red-300 p-4 rounded-xl">
        <div class="text-2xl font-bold">1</div>
        <div>Ditolak/Dibatalkan</div>
      </div>
    </div>

    <!-- Search -->
  <form class="flex-1">
    <input id="searchInput" type="text" placeholder="Cari" 
      class="w-full px-4 py-2 rounded-full border border-gray-300 text-sm shadow-sm focus:ring focus:ring-blue-200">
  </form>

    <!-- Data Peminjaman -->
    <div class="space-y-4">
      <?php foreach($peminjaman as $item): ?>
        <div class="bg-gray-50 p-4 rounded-lg shadow-sm flex justify-between items-start">
          <div>
            <div class="font-bold"><?= $item['nama'] ?></div>
            <div class="text-sm">Tanggal Pinjam : <?= $item['tanggal'] ?></div>
            <div class="text-sm">Waktu : <?= $item['waktu'] ?></div>
            <div class="text-sm">Agenda : <?= $item['agenda'] ?></div>
          </div>
          <div>
            <?php if($item['status']=="Selesai"): ?>
              <span class="bg-green-500 text-white px-3 py-1 rounded-full text-sm">Selesai</span>
            <?php elseif($item['status']=="Ditolak"): ?>
              <span class="bg-red-500 text-white px-3 py-1 rounded-full text-sm">Ditolak</span>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
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

</script>
</body>
</html>
