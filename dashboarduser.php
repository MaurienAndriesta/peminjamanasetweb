<?php
session_start();
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = "Nama Pengguna"; 
}

$menu_items = [
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
                   ['title' => 'Fakultas Teknologi dan Infrasturktur Kewilayahan (FTIK)', 'url' => 'labftik.php'],
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
    echo "<ul class='space-y-1'>";
    foreach ($items as $index => $item) {
        $uniqueId = $prefix . '-' . $index;
        echo "<li>";
        if (isset($item['type']) && $item['type'] === 'dropdown') {
            echo "<button onclick='toggleDropdown(\"{$uniqueId}\")' class='w-full flex justify-between items-center px-4 py-2 text-left text-sm font-medium text-white hover:bg-gray-700 rounded-md'>";
            echo "<span>{$item['title']}</span>";
            echo "<span id='icon-{$uniqueId}' class='transition-transform'>▼</span>";
            echo "</button>";
            echo "<div id='submenu-{$uniqueId}' class='hidden pl-4 space-y-1'>";
            renderMenu($item['submenu'], $uniqueId);
            echo "</div>";
        } else {
            echo "<a href='{$item['url']}' class='block px-4 py-2 text-sm text-white hover:bg-gray-700 rounded-md'>{$item['title']}</a>";
        }
        echo "</li>";
    }
    echo "</ul>";
}

$user_name = $_SESSION['user'];
$total_peminjaman_aktif = 12;
$total_menunggu_persetujuan = 3;
$booked_dates = [22, 29];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Sistem Peminjaman Aset</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-[#D1E5EA] to-white min-h-screen">

<!-- Sidebar -->
<div id="sidebar" class="fixed top-0 left-0 w-72 h-full bg-gray-800 text-white transform -translate-x-full transition-transform duration-300 z-50">
  <div class="bg-gray-900 px-5 py-4 font-bold uppercase text-sm tracking-widest">Menu Utama</div>
  <nav class="p-2">
    <?php renderMenu($menu_items); ?>
  </nav>
</div>

<!-- Overlay -->
<div id="overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-40" onclick="closeSidebar()"></div>

<!-- Hamburger -->
<button id="hamburgerBtn" onclick="toggleSidebar()" class="fixed top-4 left-2 z-50 bg-gray-800 text-white p-3 rounded-md">☰</button>

<!-- Main Content -->
<main class="ml-0 transition-all duration-300 p-6 md:p-10">
  <br>
  <div class="flex justify-between items-start flex-wrap gap-4 mb-10">
    <h2 class="text-2xl font-bold text-gray-800">Selamat Datang di Sistem Peminjaman Aset Kampus<br>Institut Teknologi PLN !</h2>
    
    <!-- User Dropdown -->
    <div class="relative">
      <button id="userBtn" 
        class="bg-gray-200 px-4 py-2 rounded-full text-sm font-semibold text-gray-800 hover:bg-gray-300">
        <?= htmlspecialchars($user_name); ?>
      </button>
      <div id="userDropdown" 
           class="hidden absolute right-0 mt-2 w-40 bg-white rounded-md shadow-md border border-gray-200">
        <a href="halamanutama.php" 
           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Keluar</a>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left side -->
    <div class="lg:col-span-2 space-y-6">
      <!-- Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow p-6 hover:-translate-y-1 transition">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gradient-to-r from-blue-400 to-cyan-400 text-white text-2xl">📋</div>
            <div>
              <div class="font-semibold">Peminjaman Aktif</div>
              <div class="text-sm text-gray-500">Total Peminjaman Aktif</div>
            </div>
          </div>
          <div class="text-3xl font-bold text-blue-500"><?= $total_peminjaman_aktif; ?></div>
        </div>

        <div class="bg-white rounded-xl shadow p-6 hover:-translate-y-1 transition">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gradient-to-r from-yellow-400 to-yellow-200 text-gray-800 text-2xl">⏳</div>
            <div>
              <div class="font-semibold">Menunggu Persetujuan</div>
              <div class="text-sm text-gray-500">Total Menunggu Persetujuan</div>
            </div>
          </div>
          <div class="text-3xl font-bold text-yellow-500"><?= $total_menunggu_persetujuan; ?></div>
        </div>
      </div>

      <!-- Tips -->
      <div class="bg-gradient-to-r from-yellow-300 to-yellow-200 rounded-xl shadow p-6">
        <div class="flex items-center gap-2 mb-4">
          <span class="text-2xl">💡</span>
          <span class="font-semibold text-lg">Tips Peminjaman</span>
        </div>
        <ul class="list-disc list-inside text-gray-800 space-y-1 text-sm">
          <li>Ajukan peminjaman minimal 2 hari sebelum tanggal penggunaan</li>
          <li>Pastikan mengecek ketersediaan aset terlebih dahulu</li>
          <li>Lengkapi form peminjaman dengan detail yang jelas</li>
          <li>Kembalikan aset sesuai jadwal yang telah disepakati</li>
        </ul>
      </div>
    </div>

    <!-- Right side (Calendar) -->
    <div class="bg-white rounded-xl shadow p-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-lg text-gray-800">Kalender Ketersediaan Aset</h3>
        <div class="flex gap-2">
          <button class="w-8 h-8 flex items-center justify-center bg-indigo-500 text-white rounded-md">‹</button>
          <button class="w-8 h-8 flex items-center justify-center bg-indigo-500 text-white rounded-md">›</button>
        </div>
      </div>
      <div class="text-center font-medium mb-3">September 2025</div>
      <div class="grid grid-cols-7 text-sm gap-1">
        <?php
        $days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        foreach ($days as $d) echo "<div class='text-center font-semibold text-gray-500'>$d</div>";
        $first_day = mktime(0, 0, 0, 9, 1, 2025);
        $first_day_week = date('w', $first_day);
        $days_in_month = date('t', $first_day);
        for ($i = 0; $i < $first_day_week; $i++) {
            $prev_day = 31 - ($first_day_week - 1 - $i);
            echo "<div class='text-gray-300 text-center p-2'>$prev_day</div>";
        }
        for ($day = 1; $day <= $days_in_month; $day++) {
            $class = "text-center p-2 rounded-md cursor-pointer ";
            if ($day == 25) $class .= "bg-indigo-500 text-white";
            elseif (in_array($day, $booked_dates)) $class .= "bg-red-500 text-white";
            else $class .= "hover:bg-indigo-100";
            echo "<div class='$class'>$day</div>";
        }
        $total_cells = ceil(($days_in_month + $first_day_week) / 7) * 7;
        $remaining = $total_cells - ($days_in_month + $first_day_week);
        for ($i = 1; $i <= $remaining; $i++) {
            echo "<div class='text-gray-300 text-center p-2'>$i</div>";
        }
        ?>
      </div>
      <div class="mt-4">
        <div class="flex items-center gap-2 text-sm text-gray-600">
          <span class="w-3 h-3 rounded-full bg-red-500"></span> Booked
        </div>
        <button class="mt-4 w-full bg-indigo-500 hover:bg-indigo-600 text-white py-2 rounded-lg font-semibold">Lihat Detail</button>
      </div>
    </div>
  </div>
</main>

<!-- Footer -->
<footer class="bg-gray-800 text-white text-center py-3 mt-10">
  © <?= date('Y'); ?> Institut Teknologi PLN - Sistem Peminjaman Aset
</footer>

<script>
  function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('-translate-x-full');
    document.getElementById('overlay').classList.toggle('hidden');
    document.getElementById('hamburgerBtn').classList.toggle('hidden');
  }
  function closeSidebar() {
    document.getElementById('sidebar').classList.add('-translate-x-full');
    document.getElementById('overlay').classList.add('hidden');
    document.getElementById('hamburgerBtn').classList.remove('hidden');
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
</script>
</body>
</html>
