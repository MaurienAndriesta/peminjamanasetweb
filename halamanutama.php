<?php
$menu_items = [
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
  <style>
    .fade-in {
      animation: fadeIn 0.8s ease-in-out;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .rotate-90 { transform: rotate(90deg); }
  </style>
</head>
<body class="bg-[#D1E5EA] min-h-screen font-sans text-gray-800">

  <!-- Tombol Hamburger -->
  <button id="hamburgerBtn" onclick="toggleSidebar()" 
          class="fixed top-4 left-2 z-50 bg-gray-800 text-white p-3 rounded-md shadow-md hover:scale-105 transition">
    ☰
  </button>

  <!-- Overlay -->
  <div id="overlay" onclick="closeSidebar()" 
       class="hidden fixed inset-0 bg-black bg-opacity-50 z-40"></div>

  <!-- Sidebar (warna tetap abu-abu tua) -->
  <div id="sidebar" class="fixed top-0 left-0 w-64 h-full bg-gray-800 text-white transform -translate-x-full transition-transform duration-300 z-50">
    <div class="bg-gray-900 px-5 py-4 font-bold uppercase text-sm tracking-widest">Menu Utama</div>
    <ul class="divide-y divide-gray-700">
      <?php foreach ($menu_items as $index => $item): ?>
        <li>
          <a href="<?= $item['url']; ?>" 
             class="block px-5 py-3 hover:bg-gray-700"><?= $item['title']; ?></a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <!-- Main Content -->
  <main class="p-6 md:p-10 fade-in">
    <!-- Hero Section -->
    <div class="relative text-center mb-16">
      <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1560439514-4e9645039924?w=1600&q=80')] bg-cover bg-center rounded-2xl blur-sm opacity-40"></div>
      <div class="relative z-10 py-16 px-4 md:px-10 bg-white/40 backdrop-blur-md rounded-2xl shadow-lg max-w-4xl mx-auto">
        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-3">
          Selamat Datang di Sistem Peminjaman Aset Kampus Institut Teknologi PLN
        </h2>
        <p class="text-gray-700 text-base md:text-lg">
          Platform digital untuk memudahkan pengajuan dan pengelolaan peminjaman aset kampus secara efisien dan transparan.
        </p>
        <div class="mt-6">
          <a href="login.php" 
             class="inline-block bg-gray-800 text-white px-8 py-3 rounded-full shadow-md hover:bg-gray-700 transition hover:scale-105">
            🚀 Masuk
          </a>
        </div>
      </div>
    </div>

    <!-- Informational Cards -->
    <div class="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto">
      <div class="bg-white/70 backdrop-blur-md shadow-lg rounded-2xl p-6 hover:-translate-y-2 transition transform">
        <h3 class="text-lg font-semibold text-gray-800 mb-3">📌 Apa itu Sistem Peminjaman Aset?</h3>
        <p class="text-gray-700 leading-relaxed">
          Sistem ini dirancang untuk mempermudah civitas akademika IT-PLN dalam melakukan 
          peminjaman ruangan, laboratorium, dan fasilitas kampus secara <b>teratur</b> dan <b>transparan</b>.
        </p>
      </div>

      <div class="bg-white/70 backdrop-blur-md shadow-lg rounded-2xl p-6 hover:-translate-y-2 transition transform">
        <h3 class="text-lg font-semibold text-gray-800 mb-3">🎯 Tujuan</h3>
        <ul class="text-gray-700 list-disc list-inside space-y-2">
          <li>Mempermudah proses peminjaman aset</li>
          <li>Menjamin transparansi dan keteraturan</li>
          <li>Mengurangi konflik jadwal penggunaan</li>
        </ul>
      </div>

      <div class="bg-white/70 backdrop-blur-md shadow-lg rounded-2xl p-6 hover:-translate-y-2 transition transform">
        <h3 class="text-lg font-semibold text-gray-800 mb-3">⚡ Alur Singkat Peminjaman</h3>
        <ol class="text-gray-700 list-decimal list-inside space-y-1">
          <li>Login atau buat akun</li>
          <li>Pilih ruangan / fasilitas</li>
          <li>Ajukan peminjaman dengan jadwal</li>
          <li>Tunggu konfirmasi admin</li>
          <li>Gunakan aset sesuai jadwal</li>
        </ol>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="bg-gray-800 text-white text-center py-4 mt-10">
    &copy; <?= date("Y"); ?> Institut Teknologi PLN - Sistem Peminjaman Aset
  </footer>

  <!-- Script -->
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

    window.addEventListener('resize', function() {
      if (window.innerWidth > 768) closeSidebar();
    });
  </script>
</body>
</html>
