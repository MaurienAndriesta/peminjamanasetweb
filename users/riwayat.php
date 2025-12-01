<?php
session_start();
require_once '../koneksi.php';
$db = $koneksi;

// ================= CEK LOGIN =================
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

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

// ================= AMBIL DATA RIWAYAT DARI DATABASE =================
$peminjaman = [];
// Kita ambil data yang statusnya SUDAH SELESAI atau DITOLAK (Bukan Pending/Menunggu)
$sql = "SELECT * FROM tbl_pengajuan WHERE user_id = ? AND (status = 'Disetujui' OR status = 'Ditolak' OR status = 'Selesai') ORDER BY created_at DESC";

$stmt = $db->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Format waktu
    $jam_mulai = date('H:i', strtotime($row['jam_mulai']));
    $jam_selesai = date('H:i', strtotime($row['jam_selesai']));
    
    $peminjaman[] = [
        "nama" => $row['subpilihan'], // Nama Ruang/Lab
        "tanggal" => date('d F Y', strtotime($row['tanggal_peminjaman'])),
        "waktu" => "$jam_mulai - $jam_selesai",
        "agenda" => $row['agenda'],
        // Mapping status database ke tampilan (bisa disesuaikan)
        "status" => ucfirst($row['status']) 
    ];
}
$stmt->close();


// ================= FUNGSI RENDER MENU =================
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
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Riwayat Peminjaman</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #D1E5EA 0%, #B8D4DB 100%);
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes slideIn {
      from { opacity: 0; transform: translateX(-20px); }
      to { opacity: 1; transform: translateX(0); }
    }

    .fade-in {
      animation: fadeIn 0.6s ease forwards;
    }

    .slide-in {
      animation: slideIn 0.5s ease forwards;
    }

    .highlight {
      background: linear-gradient(135deg, #FFF176 0%, #FFE082 100%);
      transition: all 0.4s ease;
      transform: scale(1.02);
    }

    .toast {
      position: fixed;
      bottom: 30px;
      right: 30px;
      background: linear-gradient(135deg, #333 0%, #1a1a1a 100%);
      color: #fff;
      padding: 16px 24px;
      border-radius: 12px;
      opacity: 0;
      transform: translateY(20px);
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      z-index: 9999;
      font-size: 0.875rem;
      font-weight: 600;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .toast.show {
      opacity: 1;
      transform: translateY(0);
    }

    .glass-effect {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .history-card {
      transition: all 0.3s ease;
    }

    .history-card:hover {
      transform: translateX(8px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }

    .search-glow:focus {
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1), 0 0 20px rgba(59, 130, 246, 0.2);
    }
  </style>
</head>

<!-- PERUBAHAN 1: Menambahkan 'flex flex-col' pada body -->
<body class="min-h-screen flex flex-col">

<div id="overlay" class="hidden fixed inset-0 bg-black/50 z-40 transition-opacity duration-300" onclick="closeSidebar()"></div>

<div id="sidebar" class="fixed top-0 left-0 w-72 h-full bg-gray-800 text-white transform -translate-x-full transition-transform duration-300 z-50 shadow-2xl">
  <div class="bg-gray-900 px-6 py-5 font-semibold text-center border-b border-gray-700 text-lg uppercase tracking-wide">
    Menu Utama
  </div>
  <nav class="p-4">
    <?php renderMenu($menu_items); ?>
  </nav>
</div>

<header class="bg-gradient-to-r from-[#1B2A49] to-[#23416C] text-white shadow-xl relative overflow-hidden">
  <div class="absolute inset-0 opacity-10">
    <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
          <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="1"/>
        </pattern>
      </defs>
      <rect width="100%" height="100%" fill="url(#grid)" />
    </svg>
  </div>

  <div class="flex items-center justify-between px-6 py-4 relative z-10">
    <button id="menuBtn" class="bg-white/10 hover:bg-white/20 text-white p-2.5 rounded-lg transition duration-200 shadow-md backdrop-blur-sm">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
    </button>

    <div class="flex flex-col items-center text-center mx-auto">
      <div class="flex items-center gap-3">
        <div class="bg-white/10 p-2.5 rounded-lg backdrop-blur-sm">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-wide drop-shadow-lg">
          Riwayat Peminjaman
        </h1>
      </div>
      <p class="text-xs sm:text-sm text-gray-200 tracking-wide mt-1">
        Sistem Manajemen Aset Kampus ITPLN
      </p>
    </div>

    <div class="w-10"></div>
  </div>
</header>

<!-- PERUBAHAN 2: Menambahkan 'flex-grow' pada main -->
<main class="container mx-auto px-6 py-10 flex-grow">
  <section class="glass-effect shadow-2xl rounded-3xl p-8 mb-8">
    <div class="text-center mb-10 fade-in">
      <div class="inline-flex items-center gap-3 bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-3 rounded-2xl shadow-md">
        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <h2 class="text-2xl font-bold text-gray-800">Riwayat Peminjaman Saya</h2>
      </div>
    </div>

    <div class="mb-8 fade-in" style="animation-delay: 0.2s;">
      <div class="relative">
        <div class="absolute left-5 top-1/2 -translate-y-1/2 text-gray-400">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
        </div>
        <input id="searchInput" type="text" placeholder="Cari peminjaman berdasarkan ruangan, agenda, atau tanggal..." 
          class="search-glow w-full pl-12 pr-4 py-4 rounded-2xl border-2 border-gray-200 text-sm shadow-lg focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-300 outline-none bg-white text-gray-800 font-medium">
      </div>
    </div>

    <?php if (empty($peminjaman)): ?>
        <div class="text-center py-10 text-gray-500 bg-white rounded-xl shadow-md fade-in">
            <p class="text-lg">🚫 Belum ada riwayat peminjaman yang selesai atau ditolak.</p>
        </div>
    <?php else: ?>
    <div id="dataContainer" class="space-y-4">
      <?php foreach($peminjaman as $index => $item): ?>
        <div class="history-card glass-effect p-6 rounded-2xl shadow-md hover:shadow-xl border-l-4 
          <?php 
            if($item['status'] == 'Selesai' || $item['status'] == 'Disetujui') echo 'border-green-400';
            elseif($item['status'] == 'Ditolak') echo 'border-red-400';
            else echo 'border-yellow-400';
          ?> 
          fade-in" style="animation-delay: <?= 0.3 + ($index * 0.1) ?>s;">
          
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex-1 space-y-3">
              <div class="flex items-start gap-3">
                <div class="bg-blue-100 p-2 rounded-lg">
                  <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                  </svg>
                </div>
                <div>
                  <div class="font-bold text-gray-900 text-lg"><?= htmlspecialchars($item['nama']) ?></div>
                </div>
              </div>

              <div class="grid sm:grid-cols-3 gap-3 text-sm">
                <div class="flex items-center gap-2">
                  <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                  <span class="text-gray-700"><strong>Tanggal:</strong> <?= $item['tanggal'] ?></span>
                </div>
                <div class="flex items-center gap-2">
                  <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  <span class="text-gray-700"><strong>Waktu:</strong> <?= $item['waktu'] ?></span>
                </div>
                <div class="flex items-center gap-2">
                  <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                  </svg>
                  <span class="text-gray-700"><strong>Agenda:</strong> <?= htmlspecialchars($item['agenda']) ?></span>
                </div>
              </div>
            </div>

            <div class="flex-shrink-0">
              <?php if($item['status']=="Selesai" || $item['status']=="Disetujui"): ?>
                <span class="inline-flex items-center gap-2 bg-gradient-to-r from-green-500 to-green-600 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-lg">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                  </svg>
                  <?= $item['status'] ?>
                </span>
              <?php elseif($item['status']=="Ditolak"): ?>
                <span class="inline-flex items-center gap-2 bg-gradient-to-r from-red-500 to-red-600 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-lg">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                  Ditolak
                </span>
              <?php else: ?>
                <span class="inline-flex items-center gap-2 bg-gradient-to-r from-yellow-400 to-yellow-500 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-lg">
                   <?= $item['status'] ?>
                </span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>
</main>

<!-- PERUBAHAN 3: Menambahkan Footer di paling bawah -->
<footer class="w-full bg-[#132544] text-white text-center py-3 mt-auto">
  © <?= date('Y'); ?> Institut Teknologi PLN - Sistem Peminjaman Aset
</footer>

<div id="toast" class="toast">
  <div class="flex items-center gap-2">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <span id="toastMessage"></span>
  </div>
</div>

<script>
const menuBtn = document.getElementById('menuBtn');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
const toast = document.getElementById('toast');
const toastMessage = document.getElementById('toastMessage');

// Sidebar toggle
menuBtn.addEventListener('click', () => {
  sidebar.classList.toggle('-translate-x-full');
  overlay.classList.toggle('hidden');
});

function closeSidebar() {
  sidebar.classList.add('-translate-x-full');
  overlay.classList.add('hidden');
}

// Dropdown animasi
function toggleDropdown(id) {
  const submenu = document.getElementById('submenu-' + id);
  const icon = document.getElementById('icon-' + id);
  submenu.classList.toggle('hidden');
  icon.style.transform = submenu.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
}

// Toast notif
function showToast(message) {
  toastMessage.textContent = message;
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 2000);
}

// Pencarian
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
  if (!found && query.trim() !== '') showToast('Tidak ada hasil ditemukan');
});
</script>

</body>
</html>