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

// contoh data dummy
$peminjaman = [
    [
        "kode" => "BK001",
        "ruangan" => "Auditorium",
        "tanggal" => "16 September 2025",
        "tarif" => "Internal",
        "biaya" => "Rp. 25.250.000",
        "agenda" => "Seminar Nasional",
        "waktu" => "08:00 - 11:00",
        "peserta" => "100 Orang",
        "kategori" => "Ruang Multiguna",
        "status" => "Menunggu"
    ],
    [
        "kode" => "BK002",
        "ruangan" => "Laboratorium Intelligent Computing",
        "tanggal" => "18 September 2025",
        "tarif" => "Ruangan",
        "biaya" => "Rp. 4.500.000",
        "agenda" => "Praktikum Machine Learning",
        "waktu" => "08:00 - 14:00",
        "peserta" => "50 Orang",
        "kategori" => "Laboratorium",
        "status" => "Disetujui"
    ],
    [
        "kode" => "BK003",
        "ruangan" => "Ruang Presentasi",
        "tanggal" => "28 September 2025",
        "tarif" => "Eksternal",
        "biaya" => "Rp. 5.700.000",
        "agenda" => "Workshop Nasional",
        "waktu" => "13:00 - 16:00",
        "peserta" => "120 Orang",
        "kategori" => "Ruang Multiguna",
        "status" => "Ditolak"
    ],
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
  <title>Status Peminjaman</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#D1E5EA] min-h-screen">

<!-- Overlay -->
<div id="overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-40" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<div id="sidebar" class="fixed top-0 left-0 w-64 h-full bg-gray-800 text-white text-sm transform -translate-x-full transition-transform duration-300 z-50">
  <div class="bg-gray-900 px-5 py-4 font-bold uppercase tracking-widest">Menu Utama</div>
  <nav class="p-2">
    <?php renderMenu($menu_items); ?>
  </nav>
</div>

<!-- Header -->
<div class="relative bg-[#D1E5EA] text-black py-4 px-6 shadow flex items-center justify-between">
  <!-- Tombol menu tetap di kiri -->
  <button id="menuBtn" class="p-2 bg-gray-800 text-white rounded-md shadow-md">☰</button>

  <!-- Teks di tengah -->
  <div class="absolute left-1/2 transform -translate-x-1/2 text-center">
    <h1 class="text-2xl font-bold">STATUS PEMINJAMAN ASET</h1>
    <p class="text-sm">Sistem Manajemen Aset Kampus ITPLN</p>
  </div>
</div>


<!-- Content -->
<div class="container mx-auto p-6">
  <h2 class="text-xl font-bold mb-4 text-center">PEMINJAMAN SAYA</h2>

  <!-- Search -->
  <div class="mb-6 flex justify-center">
    <input type="text" placeholder="Search.." class="w-1/2 p-2 border rounded-full text-center shadow-sm focus:outline-none">
  </div>

  <h3 class="text-lg font-semibold mb-3 border-b pb-2">Daftar Peminjaman</h3>

  <!-- Loop data peminjaman -->
  <?php foreach ($peminjaman as $data): ?>
    <div class="bg-[#e5e7eb] rounded-lg shadow mb-6 p-6">

      <!-- Header -->
      <div class="flex justify-between items-center mb-4">
        <div>
          <p class="text-sm font-semibold">Kode Booking</p>
          <p class="text-sm"><?= $data['kode']; ?></p>
          <p class="font-semibold"><?= $data['ruangan']; ?></p>
          <p class="text-gray-700"><?= $data['tanggal']; ?></p>
          <p class="text-gray-700">Tarif Sewa: <?= $data['tarif']; ?></p>
          <p class="text-gray-700">Biaya: <?= $data['biaya']; ?></p>
        </div>
        <div class="font-bold">
          <?php if($data['status']=="Menunggu"): ?>
            <span class="text-yellow-600">⏳ Menunggu Persetujuan</span>
          <?php elseif($data['status']=="Disetujui"): ?>
            <span class="text-green-600">✅ Disetujui</span>
          <?php else: ?>
            <span class="text-red-600">❌ Tidak Disetujui</span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Detail -->
      <div class="bg-gray-100 p-4 rounded text-sm mb-4">
        <p><strong>Agenda:</strong> <?= $data['agenda']; ?></p>
        <p><strong>Waktu:</strong> <?= $data['waktu']; ?></p>
        <p><strong>Peserta:</strong> <?= $data['peserta']; ?></p>
        <p><strong>Kategori:</strong> <?= $data['kategori']; ?></p>
      </div>

      <!-- Tombol -->
      <div class="mt-2 text-center">
        <?php if($data['status']=="Menunggu" || $data['status']=="Disetujui"): ?>
          <button class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded shadow flex items-center gap-2 mx-auto">
            🚫 BATALKAN PEMINJAMAN
          </button>
        <?php else: ?>
          <button class="bg-gray-500 text-white px-6 py-2 rounded shadow" disabled>SELESAI</button>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>



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

// Dropdown
function toggleDropdown(id) {
  const submenu = document.getElementById('submenu-' + id);
  const icon = document.getElementById('icon-' + id);
  submenu.classList.toggle('hidden');
  icon.textContent = submenu.classList.contains('hidden') ? '▼' : '▲';
}
</script>
</body>
</html>
