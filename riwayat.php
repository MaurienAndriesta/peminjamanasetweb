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
                    class='flex justify-between items-center w-full px-3 py-2 text-left rounded hover:bg-gray-700 transition-all duration-300'>
                    <span>{$item['title']}</span>
                    <span id='icon-{$uniqueId}' class='transform transition-transform duration-300'>▼</span>
                </button>
                <div class='hidden pl-4 transition-all duration-300 ease-in-out' id='submenu-{$uniqueId}'>";
                    renderMenu($item['submenu'], $uniqueId);
            echo "</div>
            </li>";
        } else {
            echo "<li><a href='{$item['url']}' class='block px-3 py-2 rounded hover:bg-gray-700 transition-colors duration-300'>{$item['title']}</a></li>";
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
  <style>
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .fade-in { animation: fadeIn 0.4s ease forwards; }
    .highlight { background-color: #FFF176; transition: background-color 0.4s ease; }
    .toast {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background-color: #333;
      color: #fff;
      padding: 12px 20px;
      border-radius: 8px;
      opacity: 0;
      transform: translateY(20px);
      transition: all 0.4s ease;
      z-index: 9999;
    }
    .toast.show {
      opacity: 1;
      transform: translateY(0);
    }
  </style>
</head>
<body class="bg-[#D1E5EA] min-h-screen">

<!-- Overlay -->
<div id="overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-40 transition-opacity duration-300" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<div id="sidebar" class="fixed top-0 left-0 w-72 h-full bg-gray-800 text-white text-base transform -translate-x-full transition-transform duration-300 z-50 shadow-xl">
  <div class="bg-gray-900 px-6 py-5 font-bold uppercase tracking-widest text-center border-b border-gray-700 text-lg">
    Menu Utama
  </div>
  <nav class="p-3 space-y-1">
    <?php renderMenu($menu_items); ?>
  </nav>
</div>


<!-- Main -->
<main class="flex-1 p-6">
  <div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-4">
      <button id="menuBtn" class="p-2 bg-gray-800 text-white rounded-md shadow-md hover:bg-gray-700 transition">
        ☰
      </button>
    </div>
  </div>

  <div class="bg-[#132544] text-white p-4 rounded-t-2xl text-center font-bold text-lg shadow-md">
    RIWAYAT PEMINJAMAN
  </div>

  <div class="bg-white shadow rounded-b-2xl p-6">
    <h2 class="text-center font-bold text-lg mb-6">RIWAYAT PEMINJAMAN SAYA</h2>

    <div class="grid grid-cols-4 gap-4 text-center mb-6">
      <div class="bg-purple-200 p-4 rounded-xl hover:scale-105 transition-transform duration-300 cursor-pointer">
        <div class="text-2xl font-bold">10</div>
        <div>Total Peminjaman</div>
      </div>
      <div class="bg-green-300 p-4 rounded-xl hover:scale-105 transition-transform duration-300 cursor-pointer">
        <div class="text-2xl font-bold">8</div>
        <div>Selesai</div>
      </div>
      <div class="bg-yellow-200 p-4 rounded-xl hover:scale-105 transition-transform duration-300 cursor-pointer">
        <div class="text-2xl font-bold">1</div>
        <div>Sedang Dipinjam</div>
      </div>
      <div class="bg-red-300 p-4 rounded-xl hover:scale-105 transition-transform duration-300 cursor-pointer">
        <div class="text-2xl font-bold">1</div>
        <div>Ditolak/Dibatalkan</div>
      </div>
    </div>

    <form class="flex-1 mb-6">
      <input id="searchInput" type="text" placeholder="Cari" 
        class="w-full px-4 py-2 rounded-full border border-gray-300 text-sm shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-400 transition">
    </form>

    <div id="dataContainer" class="space-y-4">
      <?php foreach($peminjaman as $item): ?>
        <div class="bg-gray-50 p-4 rounded-lg shadow-sm flex justify-between items-start fade-in hover:shadow-lg transition-all duration-300">
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

<!-- Toast Notification -->
<div id="toast" class="toast"></div>

<script>
// Sidebar toggle + toast
const menuBtn = document.getElementById('menuBtn');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
const toast = document.getElementById('toast');

menuBtn.addEventListener('click', () => {
  const isHidden = sidebar.classList.contains('-translate-x-full');
  sidebar.classList.toggle('-translate-x-full');
  overlay.classList.toggle('hidden');
});

function closeSidebar() {
  sidebar.classList.add('-translate-x-full');
  overlay.classList.add('hidden');
}

// Dropdown menu animasi
function toggleDropdown(id) {
  const submenu = document.getElementById('submenu-' + id);
  const icon = document.getElementById('icon-' + id);
  submenu.classList.toggle('hidden');
  icon.style.transform = submenu.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
}

// Fungsi toast
function showToast(message) {
  toast.textContent = message;
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 2000);
}

// Pencarian interaktif + toast jika kosong
const searchInput = document.getElementById('searchInput');
const cards = document.querySelectorAll('#dataContainer > div');

searchInput.addEventListener('input', (e) => {
  const query = e.target.value.toLowerCase();
  let found = false;

  cards.forEach(card => {
    const text = card.textContent.toLowerCase();
    if (text.includes(query)) {
      card.classList.remove('hidden');
      card.classList.add('highlight');
      found = true;
      setTimeout(() => card.classList.remove('highlight'), 400);
    } else {
      card.classList.add('hidden');
    }
  });

  if (!found && query.trim() !== '') {
    showToast('Tidak ada hasil ditemukan');
  }
});
</script>
</body>
</html>
