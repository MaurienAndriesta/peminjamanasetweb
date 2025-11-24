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

// ================= AMBIL DATA RUANGAN =================
$data = [];
$result = $koneksi->query("SELECT * FROM labften ORDER BY id ASC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// ================= FUNGSI RENDER MENU =================
function renderMenu($items, $prefix = 'root') {
    echo "<ul class='list-none pl-3 space-y-1'>";
    foreach ($items as $index => $item) {
        $uniqueId = $prefix . '-' . $index;
        if (isset($item['type']) && $item['type'] === 'dropdown') {
            echo "
            <li>
                <button onclick='toggleDropdown(\"{$uniqueId}\")'
                    class='flex justify-between items-center w-full px-3 py-2 rounded-lg hover:bg-gray-700/70 transition-all duration-200'>
                    <span>{$item['title']}</span>
                    <span id='icon-{$uniqueId}' class='text-xs'>▼</span>
                </button>
                <div class='hidden pl-4 border-l border-gray-700 ml-2 mt-1' id='submenu-{$uniqueId}'>";
                    renderMenu($item['submenu'], $uniqueId);
            echo "</div>
            </li>";
        } else {
            echo "<li><a href='{$item['url']}' class='block px-3 py-2 rounded-lg hover:bg-gray-700/70 transition-all duration-200'>{$item['title']}</a></li>";
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
  <title>Daftar Laboratorium FTEN</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Konfigurasi Tailwind agar default font = Inter -->
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Inter', 'ui-sans-serif', 'system-ui']
          }
        }
      }
    }
  </script>

  <style>
    /* Terapkan font Inter secara global */
    body {
      font-family: 'Inter', sans-serif;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }

    /*  Efek kaca lembut */
    .glass-effect {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.3);
    }

    /* Animasi fade-in */
    .fade-in {
      animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>

<body class="bg-gradient-to-br from-[#D1E5EA] to-white min-h-screen font-sans text-gray-800">

<!-- Navbar -->
<nav class="fixed top-0 left-0 right-0 bg-[#D1E5EA]/90 backdrop-blur-md shadow-md h-16 z-50 flex items-center gap-4 px-6 transition-all">
  <button id="hamburgerBtn" onclick="toggleSidebar()" 
    class="bg-gray-800 text-white p-3 rounded-md">☰</button>

  <form class="flex-1">
    <input id="searchInput" type="text" placeholder="Cari Laboratorium..." 
      class="w-full px-4 py-2 rounded-full border border-gray-300 text-sm shadow-sm focus:ring-2 focus:ring-blue-200 transition-all">
  </form>
</nav>

<!-- Overlay -->
<div id="overlay" class="hidden fixed inset-0 bg-black/40 z-40" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<div id="sidebar" class="fixed top-0 left-0 w-72 h-full bg-gray-800 text-white transform -translate-x-full transition-transform duration-300 z-50 shadow-2xl">
  <div class="bg-gray-900 px-6 py-5 font-semibold text-center border-b border-gray-700 text-lg uppercase tracking-wide">
    Menu Utama
  </div>
  <nav class="p-4">
    <?php renderMenu($menu_items); ?>
  </nav>
</div>

<!-- Main -->
<main class="pt-24 px-6 max-w-7xl mx-auto fade-in">
  <h2 class="text-2xl font-bold text-gray-700 mb-6 border-l-4 border-blue-400 pl-3">
    Daftar Laboratorium Fakultas Telematika Energi
  </h2>

  <div class="bg-white border border-blue-200 rounded-2xl shadow-lg overflow-hidden transition-all">
    <table class="w-full text-sm" id="ruanganTable">
      <thead>
        <tr class="bg-[#E3F2F7] text-gray-700 uppercase text-xs tracking-wider">
          <th class="border border-gray-200 px-4 py-3 text-left">No</th>
          <th class="border border-gray-200 px-4 py-3 text-left">Foto</th>
          <th class="border border-gray-200 px-4 py-3 text-left">Nama Laboratorium</th>
          
          
          <th class="border border-gray-200 px-4 py-3 text-left">Lokasi</th>
          <th class="border border-gray-200 px-4 py-3 text-left">Tarif Sewa Laboratorium</th>
          <th class="border border-gray-200 px-4 py-3 text-left">Tarif Sewa Peralatan</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($data as $row): ?>
        <tr class="hover:bg-blue-50 transition duration-200">
          <td class="border border-gray-200 px-4 py-3"><?= $row['id'] ?></td>
          <td class="border border-gray-200 px-4 py-3 text-center">
            <?php if (!empty($row['foto'])): ?>
              <div class="flex flex-col items-center space-y-2">
                <img src="uploads/ruangan/<?= htmlspecialchars($row['foto']); ?>" 
                     alt="Foto <?= htmlspecialchars($row['nama']); ?>" 
                     class="w-24 h-20 object-cover rounded-lg shadow-md hover:scale-105 hover:shadow-lg transition-all duration-300 cursor-pointer"
                     onclick="showImageModal('uploads/ruangan/<?= htmlspecialchars($row['foto']); ?>')">
                <button onclick="showImageModal('uploads/ruangan/<?= htmlspecialchars($row['foto']); ?>')" 
                        class="text-blue-600 text-xs hover:underline transition">Lihat Foto</button>
              </div>
            <?php else: ?>
              <span class="text-gray-400 italic">Tidak ada foto</span>
            <?php endif; ?>
          </td>
          <td class="border border-gray-200 px-4 py-3"><?= $row['nama'] ?></td>
          
          <td class="border border-gray-200 px-4 py-3"><?= $row['lokasi'] ?></td>
          <td class="border border-gray-200 px-4 py-3"><?= $row['tarif_sewa_laboratorium'] ?></td>
          <td class="border border-gray-200 px-4 py-3"><?= $row['tarif_sewa_peralatan'] ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="bg-blue-50">
          <td colspan="8" class="border border-gray-200 px-5 py-5 italic text-gray-700 text-sm leading-relaxed">
           <b>Keterangan:</b> Per 15 Orang/8 Jam. 
            (Biaya tersebut tidak termasuk biaya material untuk praktek dan konsumsi).
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- Modal Foto -->
  <div id="imageModal" class="fixed inset-0 bg-black/70 flex items-center justify-center hidden z-50">
    <div class="relative max-w-4xl fade-in">
      <img id="modalImage" src="" alt="Foto Ruangan" class="rounded-2xl shadow-2xl max-h-[90vh] border-4 border-white transition-all">
      <button onclick="closeImageModal()" class="absolute -top-4 -right-4 bg-white rounded-full p-2 shadow hover:bg-gray-100 text-lg font-bold transition">✕</button>
    </div>
  </div>
</main>

<footer class="w-full bg-[#132544] text-white text-center py-3 mt-10">
  © <?= date('Y'); ?> Institut Teknologi PLN - Sistem Peminjaman Aset
</footer>

<script>
// Sidebar
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
function toggleSidebar() {
  sidebar.classList.toggle('-translate-x-full');
  overlay.classList.toggle('hidden');
}
function closeSidebar() {
  sidebar.classList.add('-translate-x-full');
  overlay.classList.add('hidden');
}

// Dropdown
function toggleDropdown(id) {
  const submenu = document.getElementById('submenu-' + id);
  const icon = document.getElementById('icon-' + id);
  submenu.classList.toggle('hidden');
  icon.textContent = submenu.classList.contains('hidden') ? '▼' : '▲';
}

// Search
const searchInput = document.getElementById("searchInput");
const tableRows = document.querySelectorAll("#ruanganTable tbody tr");
searchInput.addEventListener("keyup", function () {
  let keyword = this.value.toLowerCase();
  tableRows.forEach(row => {
    let rowText = row.textContent.toLowerCase();
    row.style.display = rowText.includes(keyword) ? "" : "none";
  });
});

// Modal
function showImageModal(src) {
  document.getElementById('modalImage').src = src;
  document.getElementById('imageModal').classList.remove('hidden');
}
function closeImageModal() {
  document.getElementById('imageModal').classList.add('hidden');
}
</script>
</body>
</html>
