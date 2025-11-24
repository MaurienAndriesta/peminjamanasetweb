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
    .fade-in { animation: fadeIn 0.8s ease-in-out; }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body class="bg-gradient-to-b from-[#E8F3F7] to-[#CFE4EB] min-h-screen text-gray-800">

  <!-- Tombol Hamburger -->
  <button id="hamburgerBtn" onclick="toggleSidebar()" 
    class="fixed top-4 left-3 z-50 bg-gradient-to-r from-gray-800 to-gray-900 text-white p-3.5 rounded-xl shadow-lg hover:scale-105 active:scale-95 transition-all duration-300">
    ☰
  </button>

  <!-- Overlay -->
  <div id="overlay" onclick="closeSidebar()" 
       class="hidden fixed inset-0 bg-black bg-opacity-40 backdrop-blur-sm z-40 transition"></div>

  <!-- Sidebar -->
  <div id="sidebar" 
    class="fixed top-0 left-0 w-64 h-full bg-gradient-to-b from-gray-900 to-gray-800 text-white transform -translate-x-full transition-transform duration-300 z-50 shadow-xl">
    <div class="px-6 py-5 font-bold uppercase tracking-widest text-sm border-b border-gray-700">Menu Utama</div>
    <ul class="divide-y divide-gray-700">
      <?php foreach ($menu_items as $item): ?>
        <li>
          <a href="<?= $item['url']; ?>" 
             class="block px-6 py-3 hover:bg-gray-700 transition"><?= $item['title']; ?></a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <!-- Konten Utama -->
  <main class="p-6 md:p-10 fade-in">
    <!-- Hero -->
    <section class="relative text-center mb-16">
      <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1560439514-4e9645039924?w=1600&q=80')] bg-cover bg-center rounded-3xl blur-sm opacity-40"></div>
      <div class="relative z-10 py-16 px-6 bg-white/60 backdrop-blur-lg rounded-3xl shadow-2xl max-w-4xl mx-auto">
        <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 mb-4 leading-tight">
          Selamat Datang di <span class="text-[#004C6D]">Sistem Peminjaman Aset Kampus</span>
        </h1>
        <p class="text-gray-700 text-lg max-w-2xl mx-auto">
          Platform digital yang membantu civitas akademika <b>Institut Teknologi PLN</b> dalam pengajuan dan pengelolaan peminjaman aset kampus secara efisien dan transparan.
        </p>
        <div class="mt-8">
          <a href="login.php" 
             class="inline-block bg-gradient-to-r from-[#004C6D] to-[#007B9E] text-white px-10 py-3.5 rounded-full shadow-lg hover:opacity-90 hover:scale-105 transition">
            🚀 Masuk 
          </a>
        </div>
      </div>
    </section>

    <!-- Informational Cards -->
    <section class="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto">
      <div class="bg-white/80 backdrop-blur-md shadow-xl rounded-2xl p-6 hover:-translate-y-2 hover:shadow-2xl transition-all">
        <h3 class="text-xl font-semibold text-[#004C6D] mb-3">📌 Apa itu Sistem Peminjaman?</h3>
        <p class="text-gray-700 leading-relaxed">
          Sistem ini dirancang untuk memudahkan proses peminjaman ruangan, laboratorium, dan fasilitas kampus secara <b>efisien</b> dan <b>terstruktur</b>.
        </p>
      </div>

      <div class="bg-white/80 backdrop-blur-md shadow-xl rounded-2xl p-6 hover:-translate-y-2 hover:shadow-2xl transition-all">
        <h3 class="text-xl font-semibold text-[#004C6D] mb-3">🎯 Tujuan</h3>
        <ul class="text-gray-700 list-disc list-inside space-y-2">
          <li>Mempermudah proses pengajuan aset</li>
          <li>Menjamin transparansi peminjaman</li>
          <li>Menghindari bentrok jadwal penggunaan</li>
        </ul>
      </div>

      <div class="bg-white/80 backdrop-blur-md shadow-xl rounded-2xl p-6 hover:-translate-y-2 hover:shadow-2xl transition-all">
        <h3 class="text-xl font-semibold text-[#004C6D] mb-3">⚡ Alur Singkat</h3>
        <ol class="text-gray-700 list-decimal list-inside space-y-1">
          <li>Login atau buat akun pengguna</li>
          <li>Pilih ruangan / fasilitas</li>
          <li>Isi jadwal dan ajukan</li>
          <li>Tunggu konfirmasi admin</li>
          <li>Gunakan aset sesuai jadwal</li>
        </ol>
      </div>
    </section>
  </main>

  <!-- Footer -->
  <footer class="bg-gray-900 text-gray-300 text-center py-5 mt-16">
    <p>&copy; <?= date("Y"); ?> Institut Teknologi PLN — <span class="font-semibold">Sistem Peminjaman Aset Kampus</span></p>
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
