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
// Mengambil semua data (termasuk yang tidak tersedia)
$result = $koneksi->query("SELECT * FROM tbl_ruangmultiguna ORDER BY id ASC");

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
  <title>Daftar Ruangan Multiguna</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>

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
    body { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
    .fade-in { animation: fadeIn 0.5s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    
    /* Custom Scrollbar untuk Sidebar */
    .custom-scroll::-webkit-scrollbar { width: 6px; }
    .custom-scroll::-webkit-scrollbar-track { background: #1f2937; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 3px; }
    .custom-scroll::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
  </style>
</head>

<body class="flex flex-col min-h-screen bg-gradient-to-br from-[#D1E5EA] to-white font-sans text-gray-800">

  <nav class="fixed top-0 left-0 right-0 bg-[#D1E5EA]/90 backdrop-blur-md shadow-md h-16 z-50 flex items-center gap-4 px-6 transition-all">
    <button id="hamburgerBtn" onclick="toggleSidebar()" class="bg-gray-800 text-white p-3 rounded-md">☰</button>
    <form class="flex-1">
      <input id="searchInput" type="text" placeholder="Cari ruangan..." class="w-full px-4 py-2 rounded-full border border-gray-300 text-sm shadow-sm focus:ring-2 focus:ring-blue-200 transition-all">
    </form>
  </nav>

  <div id="overlay" class="hidden fixed inset-0 bg-black/40 z-40" onclick="closeSidebar()"></div>

  <div id="sidebar" class="fixed top-0 left-0 w-72 h-full bg-gray-800 text-white transform -translate-x-full transition-transform duration-300 z-50 shadow-2xl flex flex-col">
    <div class="bg-gray-900 px-6 py-5 font-semibold text-center border-b border-gray-700 text-lg uppercase tracking-wide flex-shrink-0">
      Menu Utama
    </div>
    <nav class="p-4 flex-1 overflow-y-auto custom-scroll">
      <?php renderMenu($menu_items); ?>
    </nav>
  </div>

  <main class="flex-grow pt-24 px-6 fade-in w-full">
  <h2 class="text-2xl font-bold text-gray-700 mb-6 border-l-4 border-blue-400 pl-3">
    Daftar Ruangan Multiguna
  </h2>

  <div class="bg-white border border-blue-200 rounded-2xl shadow-lg overflow-x-auto transition-all w-full">
    <table class="min-w-full text-sm" id="ruanganTable">
        <thead>
          <tr class="bg-[#E3F2F7] text-gray-700 uppercase text-xs tracking-wider">
            <th class="border border-gray-200 px-4 py-3 text-left">No</th>
            <th class="border border-gray-200 px-4 py-3 text-left">Foto</th>
            <th class="border border-gray-200 px-4 py-3 text-left">Nama Ruangan</th>
            <th class="border border-gray-200 px-4 py-3 text-left">Kapasitas</th>
            <th class="border border-gray-200 px-4 py-3 text-left">Lokasi</th>
            <th class="border border-gray-200 px-4 py-3 text-left">Internal ITPLN</th>
            <th class="border border-gray-200 px-4 py-3 text-left">Eksternal ITPLN</th>
            <th class="border border-gray-200 px-4 py-3 text-left w-64">Keterangan</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $no = 1; 
          if(count($data) > 0): 
            foreach($data as $row): 
            // LOGIKA CEK STATUS
            $status_raw = strtolower(str_replace(['_', ' '], '', $row['status']));
            $is_unavailable = ($status_raw === 'tidaktersedia');
          ?>
          
          <tr class="transition duration-200 <?= $is_unavailable ? 'bg-gray-100 opacity-75' : 'hover:bg-blue-50' ?>">
            
            <td class="border border-gray-200 px-4 py-3 text-center"><?= $no++ ?></td>
            
            <td class="border border-gray-200 px-4 py-3 text-center">
              <?php if (!empty($row['gambar'])): ?>
                <div class="flex flex-col items-center space-y-2">
                  <img src="../admin/assets/images/<?= htmlspecialchars($row['gambar']); ?>" 
                      alt="Foto <?= htmlspecialchars($row['nama']); ?>" 
                      class="w-24 h-20 object-cover rounded-lg shadow-md hover:scale-105 hover:shadow-lg transition-all duration-300 cursor-pointer"
                      onclick="showImageModal('../admin/assets/images/<?= htmlspecialchars($row['gambar']); ?>')">
                  
                  <button onclick="showImageModal('../admin/assets/images/<?= htmlspecialchars($row['gambar']); ?>')" 
                          class="text-blue-600 text-xs hover:underline transition">Lihat Foto</button>
                </div>
              <?php else: ?>
                <span class="text-gray-400 italic">Tidak ada foto</span>
              <?php endif; ?>
            </td>
            
            <td class="border border-gray-200 px-4 py-3 font-medium">
                <?= $row['nama'] ?>
                <?php if($is_unavailable): ?>
                    <br>
                    <span class="mt-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-600 border border-red-200 shadow-sm">
                        ⛔ Tidak Tersedia
                    </span>
                <?php endif; ?>
            </td>
            
            <td class="border border-gray-200 px-4 py-3"><?= $row['kapasitas'] ?></td>
            <td class="border border-gray-200 px-4 py-3"><?= $row['lokasi'] ?></td>
            <td class="border border-gray-200 px-4 py-3 text-green-700 font-semibold">
              <?= 'Rp ' . number_format($row['tarif_internal'], 0, ',', '.') . '/hari' ?>
            </td>
            <td class="border border-gray-200 px-4 py-3 text-blue-700 font-semibold">
              <?= 'Rp ' . number_format($row['tarif_eksternal'], 0, ',', '.') . '/hari' ?>
            </td>
            
            <td class="border border-gray-200 px-4 py-3 text-sm text-gray-600">
               <?= !empty($row['keterangan']) ? nl2br(htmlspecialchars($row['keterangan'])) : '-' ?>
            </td>

          </tr>
          <?php endforeach; ?>
          
          <?php else: ?>
          <tr>
              <td colspan="8" class="border border-gray-200 px-4 py-6 text-center text-gray-500 italic">
                  Belum ada data ruangan.
              </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

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

// Modal Foto
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