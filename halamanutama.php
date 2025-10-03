<?php
$menu_items = [
    [
        'title' => 'Masuk',
        'type' => 'dropdown',
        'icon' => '▼',
        'submenu' => [
            ['title' => 'Login', 'type' => 'link', 'url' => 'login.php'],
            ['title' => 'Register', 'type' => 'link', 'url' => 'register.php'],
        ]
    ],
    [
        'title' => 'Daftar Ruangan & Fasilitas',
        'type' => 'dropdown',
        'icon' => '▼',
        'submenu' => [
            ['title' => 'Ruangan Multiguna', 'url' => 'login.php'],
            ['title' => 'Fasilitas', 'url' => 'login.php'],
            ['title' => 'Usaha', 'url' => 'login.php'],
            ['title' => 'Laboratorium', 'url' => 'login.php']
        ]
    ],
    [
        'title' => 'Tentang Alur Peminjaman',
        'type' => 'link',
        'url' => 'tentangalur.php'
    ]
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Beranda - Sistem Peminjaman Aset</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#D1E5EA] min-h-screen font-sans">

  <!-- Tombol Hamburger -->
  <button id="hamburgerBtn" onclick="toggleSidebar()" 
          class="fixed top-4 left-2 z-50 bg-gray-800 text-white p-3 rounded-md">
    ☰
  </button>

  <!-- Overlay -->
  <div id="overlay" onclick="closeSidebar()" 
       class="hidden fixed inset-0 bg-black bg-opacity-50 z-40"></div>

  <!-- Sidebar -->
  <div id="sidebar" class="fixed top-0 left-0 w-64 h-full bg-gray-800 text-white transform -translate-x-full transition-transform duration-300 z-50">
    <div class="bg-gray-900 px-5 py-4 font-bold uppercase text-sm tracking-widest">Menu Utama</div>
    <ul class="divide-y divide-gray-700">
      <?php foreach ($menu_items as $index => $item): ?>
        <li>
          <?php if ($item['type'] == 'dropdown'): ?>
            <button onclick="toggleDropdown(<?= $index; ?>)" 
                    class="w-full flex justify-between items-center px-5 py-3 hover:bg-gray-700 text-left">
              <span><?= $item['title']; ?></span>
              <span id="icon-<?= $index; ?>" class="text-xs transition-transform">▼</span>
            </button>
            <ul id="submenu-<?= $index; ?>" class="hidden bg-gray-700">
              <?php foreach ($item['submenu'] as $subitem): ?>
                <li>
                  <a href="<?= $subitem['url']; ?>" 
                     class="block px-8 py-2 hover:bg-gray-600 text-sm"><?= $subitem['title']; ?></a>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <a href="<?= $item['url']; ?>" 
               class="block px-5 py-3 hover:bg-gray-700"><?= $item['title']; ?></a>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <!-- Main Content -->
  <main class="p-6 md:p-10">
    <div class="text-center mb-10">
      <h2 class="text-2xl font-bold text-gray-800">Selamat Datang di Sistem Peminjaman Aset Kampus Institut Teknologi PLN</h2>
      <p class="text-gray-600 mt-2">Platform digital untuk memudahkan pengajuan & pengelolaan peminjaman aset kampus</p>
    </div>

    <div class="max-w-3xl mx-auto space-y-6">
      <div class="bg-white shadow-md rounded-xl p-6 hover:-translate-y-1 transition">
        <h3 class="text-lg font-semibold text-gray-800 mb-2">📌 Apa itu Sistem Peminjaman Aset?</h3>
        <p class="text-gray-700 leading-relaxed">
          Sistem ini dirancang untuk mempermudah civitas akademika IT-PLN dalam 
          melakukan peminjaman ruangan, laboratorium, fasilitas usaha, maupun 
          aset kampus lainnya secara <b>teratur</b>, <b>transparan</b>, dan <b>efisien</b>.
        </p>
      </div>

      <div class="bg-white shadow-md rounded-xl p-6 hover:-translate-y-1 transition">
        <h3 class="text-lg font-semibold text-gray-800 mb-2">🎯 Tujuan</h3>
        <p class="text-gray-700 leading-relaxed">
          ✔ Mempermudah proses peminjaman aset.<br>
          ✔ Menjamin transparansi dan keteraturan penggunaan.<br>
          ✔ Mengurangi konflik jadwal penggunaan fasilitas.<br>
        </p>
      </div>

      <div class="bg-white shadow-md rounded-xl p-6 hover:-translate-y-1 transition">
        <h3 class="text-lg font-semibold text-gray-800 mb-2">⚡ Alur Singkat Peminjaman</h3>
        <p class="text-gray-700 leading-relaxed">
          1️⃣ Login atau buat akun.<br>
          2️⃣ Pilih ruangan / fasilitas yang tersedia.<br>
          3️⃣ Ajukan peminjaman dengan jadwal.<br>
          4️⃣ Tunggu konfirmasi admin.<br>
          5️⃣ Gunakan aset sesuai jadwal yang disetujui.<br>
        </p>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="bg-gray-800 text-white text-center py-4 mt-10">
    &copy; <?= date("Y"); ?> Institut Teknologi PLN - Sistem Peminjaman Aset
  </footer>

  <script>
    function toggleSidebar() {
      document.getElementById('sidebar').classList.remove('-translate-x-full');
      document.getElementById('overlay').classList.remove('hidden');
      document.getElementById('hamburgerBtn').classList.add('hidden');
    }

    function closeSidebar() {
      document.getElementById('sidebar').classList.add('-translate-x-full');
      document.getElementById('overlay').classList.add('hidden');
      document.getElementById('hamburgerBtn').classList.remove('hidden');
    }

    function toggleDropdown(index) {
      const submenu = document.getElementById('submenu-' + index);
      const icon = document.getElementById('icon-' + index);
      submenu.classList.toggle('hidden');
      icon.classList.toggle('rotate-90');
    }

    window.addEventListener('resize', function() {
      if (window.innerWidth > 768) closeSidebar();
    });
  </script>
</body>
</html>
