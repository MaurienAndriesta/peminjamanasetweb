<?php
require_once 'config/database.php';
$db = new Database();

$success_message = null;

// ✅ Proses update status
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action === 'approve') {
        $db->query("UPDATE pemesanan SET status = 'disetujui' WHERE id = :id");
        $db->bind(':id', $id);
        $db->execute();
        $success_message = "✅ Pemesanan berhasil disetujui.";
    } elseif ($action === 'reject') {
        $db->query("UPDATE pemesanan SET status = 'ditolak' WHERE id = :id");
        $db->bind(':id', $id);
        $db->execute();
        $success_message = "❌ Pemesanan berhasil ditolak.";
    }

    $redirect_params = [];
    if ($success_message) $redirect_params['success_msg'] = urlencode($success_message);
    if (isset($_GET['status'])) $redirect_params['status'] = urlencode($_GET['status']);
    if (isset($_GET['search'])) $redirect_params['search'] = urlencode($_GET['search']);
    $query_string = http_build_query($redirect_params);
    header("Location: permintaan_admin.php" . ($query_string ? "?" . $query_string : ""));
    exit;
}

if (isset($_GET['success_msg'])) {
    $success_message = htmlspecialchars($_GET['success_msg']);
}

// ✅ Filter dan pencarian
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

$query = "SELECT * FROM pemesanan WHERE 1=1";
if ($status_filter !== 'all') {
    $query .= " AND status = :status";
}
if (!empty($search)) {
    $query .= " AND (pemohon LIKE :search OR nama_ruang LIKE :search OR keterangan LIKE :search)";
}
$query .= " ORDER BY created_at DESC";

$db->query($query);
if ($status_filter !== 'all') $db->bind(':status', $status_filter);
if (!empty($search)) $db->bind(':search', "%$search%");
$pemesanan = $db->resultSet();

// ✅ Statistik
$db->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'disetujui' THEN 1 ELSE 0 END) as disetujui,
    SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak
    FROM pemesanan");
$stats = $db->single();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Permintaan Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body {
  font-family: 'Poppins', sans-serif;
}
.text-dark-accent { color: #202938; }

@media (min-width: 1024px) {
  .main { margin-left: 4rem; }
  .main.lg\:ml-60 { margin-left: 15rem; }
}
@media (max-width: 1023px) {
  .main { margin-left: 0 !important; }
}

.stat-card { transition: all 0.3s ease; }
.stat-card:hover { transform: translateY(-5px); }

#toggleBtn { position: relative; z-index: 51 !important; }
</style>
</head>
<body class="bg-blue-100 min-h-screen text-gray-800">

<?php include 'sidebar_admin.php'; ?>

<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

<div id="mainContent" class="main flex-1 p-3 sm:p-5 transition-all duration-300 lg:ml-16">
    
  <div class="flex justify-between items-center mb-4 sm:mb-6">
    <button id="toggleBtn" class="bg-amber-500 hover:bg-amber-600 text-gray-900 p-2 rounded-lg transition-colors shadow-md relative z-50" onclick="toggleSidebar()">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
      </svg>
    </button>
  </div>

  <div class="content bg-white p-4 sm:p-6 rounded-xl shadow-lg">
    <div class="mb-4 sm:mb-6 pb-4 border-b">
      <h1 class="text-xl sm:text-2xl font-bold text-dark-accent flex items-center gap-2">
        <span class="text-2xl">📄</span>
        <span>Daftar Permintaan Peminjaman</span>
      </h1>
      <p class="text-xs sm:text-sm text-gray-600 mt-1">Daftar dan pengelolaan pemesanan ruang & fasilitas</p>
    </div>

    <?php if (!empty($success_message)): ?>
      <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded-lg mb-4 shadow-sm text-sm">
        <?= $success_message ?>
      </div>
    <?php endif; ?>

    <!-- Statistik Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 mb-6 sm:mb-8">
      <div class="stat-card bg-gradient-to-br from-gray-50 to-gray-200 p-4 sm:p-6 rounded-xl shadow-md text-gray-700">
        <div class="flex justify-between items-center">
          <div class="text-3xl">📦</div>
          <div class="text-right">
            <h3 class="text-xs font-semibold text-gray-600">Total</h3>
            <div class="text-xl font-bold"><?= $stats['total'] ?? 0 ?></div>
          </div>
        </div>
      </div>

      <div class="stat-card bg-gradient-to-br from-yellow-50 to-yellow-200 p-4 sm:p-5 rounded-xl shadow-md text-yellow-700 flex flex-col justify-between">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <div class="text-2xl sm:text-3xl shrink-0">⏳</div>
      <div class="text-right flex-1 min-w-[70px]">
        <h3 class="text-xs sm:text-sm font-semibold text-gray-600 truncate">Menunggu</h3>
        <div class="text-lg sm:text-xl font-bold text-amber-800"><?= $stats['pending'] ?? 0 ?></div>
      </div>
    </div>
  </div>


      <div class="stat-card bg-gradient-to-br from-green-50 to-green-200 p-4 sm:p-6 rounded-xl shadow-md text-green-700">
        <div class="flex justify-between items-center">
          <div class="text-3xl">✅</div>
          <div class="text-right">
            <h3 class="text-xs font-semibold text-gray-600">Disetujui</h3>
            <div class="text-xl font-bold"><?= $stats['disetujui'] ?? 0 ?></div>
          </div>
        </div>
      </div>

      <div class="stat-card bg-gradient-to-br from-red-50 to-red-200 p-4 sm:p-6 rounded-xl shadow-md text-red-700">
        <div class="flex justify-between items-center">
          <div class="text-3xl">❌</div>
          <div class="text-right">
            <h3 class="text-xs font-semibold text-gray-600">Ditolak</h3>
            <div class="text-xl font-bold"><?= $stats['ditolak'] ?? 0 ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filter -->
    <form method="GET" class="bg-gray-50 border border-gray-200 rounded-xl p-3 sm:p-4 flex flex-wrap gap-3 items-end mb-6 sm:mb-8 shadow-sm">
      <input type="text" name="search" placeholder="🔍 Cari pemohon / ruang..." value="<?= htmlspecialchars($search) ?>" class="flex-1 min-w-[180px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500 text-sm">
      <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg bg-white focus:ring-amber-500 focus:border-amber-500 text-sm">
        <option value="all" <?= $status_filter==='all'?'selected':'' ?>>Semua Status</option>
        <option value="pending" <?= $status_filter==='pending'?'selected':'' ?>>Menunggu</option>
        <option value="disetujui" <?= $status_filter==='disetujui'?'selected':'' ?>>Disetujui</option>
        <option value="ditolak" <?= $status_filter==='ditolak'?'selected':'' ?>>Ditolak</option>
      </select>
      <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-gray-900 px-3 sm:px-4 py-2 rounded-lg font-semibold transition-colors text-xs sm:text-sm whitespace-nowrap">
        🔍 <span class="hidden sm:inline">Tampilkan</span>
      </button>
    </form>

    <!-- Table -->
    <div class="hidden lg:block overflow-x-auto border border-gray-100 rounded-xl shadow-md">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-700 text-white">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">No</th>
            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Pemohon</th>
            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Ruang</th>
            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Mulai</th>
            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Selesai</th>
            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Status</th>
            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Keterangan</th>
            <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Aksi</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <?php if (empty($pemesanan)): ?>
          <tr><td colspan="8" class="px-4 py-6 text-center text-gray-500 italic text-sm">Tidak ada data permintaan</td></tr>
          <?php else: foreach($pemesanan as $i=>$r): ?>
          <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 text-sm"><?= $i+1 ?></td>
            <td class="px-4 py-3 text-sm font-semibold text-gray-800"><?= htmlspecialchars($r['pemohon']) ?></td>
            <td class="px-4 py-3 text-sm"><?= htmlspecialchars($r['nama_ruang']) ?></td>
            <td class="px-4 py-3 text-sm"><?= date('d/m/Y', strtotime($r['tanggal_mulai'])) ?></td>
            <td class="px-4 py-3 text-sm"><?= date('d/m/Y', strtotime($r['tanggal_selesai'])) ?></td>
            <td class="px-4 py-3 text-sm">
              <?php
                $status = strtolower($r['status']);
                $color = $status==='pending' ? 'bg-yellow-100 text-yellow-800' : ($status==='disetujui' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800');
              ?>
              <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $color ?>"><?= ucfirst($r['status']) ?></span>
            </td>
            <td class="px-4 py-3 text-xs text-gray-600 truncate max-w-xs"><?= htmlspecialchars($r['keterangan']) ?></td>
            <td class="px-4 py-3 text-center">
              <div class="flex gap-2 justify-center flex-wrap">
                <a href="detailpermintaan_admin.php?id=<?= $r['id'] ?>" class="px-3 py-1 text-xs font-semibold rounded-lg bg-gray-500 hover:bg-gray-600 text-white transition">Detail</a>
                <?php if ($status==='pending'): ?>
                <a href="?action=approve&id=<?= $r['id'] ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>" class="px-3 py-1 text-xs font-semibold rounded-lg bg-green-500 hover:bg-green-600 text-white transition">Setujui</a>
                <a href="?action=reject&id=<?= $r['id'] ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>" class="px-3 py-1 text-xs font-semibold rounded-lg bg-red-500 hover:bg-red-600 text-white transition">Tolak</a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Mobile Cards -->
    <div class="lg:hidden space-y-4 pt-4">
      <?php if (empty($pemesanan)): ?>
      <div class="text-center p-6 text-gray-500 bg-gray-50 rounded-lg italic text-sm">Tidak ada data permintaan</div>
      <?php else: foreach($pemesanan as $i=>$r): ?>
      <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 shadow-sm">
        <div class="flex justify-between items-start mb-2">
          <div>
            <div class="text-xs text-gray-500 mb-1">No. <?= $i+1 ?> • <?= date('d/m/Y', strtotime($r['created_at'])) ?></div>
            <h3 class="font-bold text-sm text-gray-900"><?= htmlspecialchars($r['nama_ruang']) ?></h3>
            <div class="text-xs text-gray-700">Pemohon: <?= htmlspecialchars($r['pemohon']) ?></div>
          </div>
          <?php
            $status = strtolower($r['status']);
            $color = $status==='pending' ? 'bg-yellow-400 text-gray-900' : ($status==='disetujui' ? 'bg-green-500 text-white' : 'bg-red-500 text-white');
          ?>
          <span class="px-3 py-1 rounded-full text-xs font-bold <?= $color ?>"><?= ucfirst($r['status']) ?></span>
        </div>
        <div class="text-xs text-gray-700 mb-3 space-y-1">
          <div>Mulai: <span class="font-semibold"><?= date('d/m/Y', strtotime($r['tanggal_mulai'])) ?></span></div>
          <div>Selesai: <span class="font-semibold"><?= date('d/m/Y', strtotime($r['tanggal_selesai'])) ?></span></div>
          <div>Keterangan: <span class="italic text-gray-600"><?= htmlspecialchars($r['keterangan']) ?></span></div>
        </div>
        <div class="flex gap-2 justify-start flex-wrap">
          <a href="detailpermintaan_admin.php?id=<?= $r['id'] ?>" class="px-3 py-2 text-xs font-bold rounded-lg bg-gray-500 hover:bg-gray-600 text-white transition flex-1 text-center">Detail</a>
          <?php if ($status==='pending'): ?>
          <a href="?action=approve&id=<?= $r['id'] ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>" class="px-3 py-2 text-xs font-bold rounded-lg bg-green-500 hover:bg-green-600 text-white flex-1 text-center">Setujui</a>
          <a href="?action=reject&id=<?= $r['id'] ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>" class="px-3 py-2 text-xs font-bold rounded-lg bg-red-500 hover:bg-red-600 text-white flex-1 text-center">Tolak</a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>

  </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    const overlay = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('toggleBtn'); // Ambil toggle button
    const is_desktop = window.innerWidth >= 1024;

    if (is_desktop) {
        sidebar.classList.toggle('lg:w-60');
        sidebar.classList.toggle('lg:w-16');
        main.classList.toggle('lg:ml-60');
        main.classList.toggle('lg:ml-16');
        
        // KODE REVISI: Geser tombol toggle di desktop
        const is_expanded = sidebar.classList.contains('lg:w-60');
        if (is_expanded) {
             // Jika sidebar terbuka (15rem), pindahkan tombol ke kanan
            toggleBtn.style.left = '16rem'; 
        } else {
             // Jika sidebar tertutup (4rem), pindahkan tombol di samping sidebar kecil
            toggleBtn.style.left = '5rem'; 
        }

    } else {
        sidebar.classList.toggle('translate-x-0');
        sidebar.classList.toggle('-translate-x-full');
        // Di mobile, tombol toggle tetap di kiri (1.25rem)
        if (overlay) {
            overlay.classList.toggle('hidden');
        }
    }

    const is_expanded = sidebar.classList.contains('lg:w-60') || sidebar.classList.contains('translate-x-0');
    
    // Asumsi fungsi updateSidebarVisibility ada di sidebar_admin.php
    if (typeof updateSidebarVisibility === 'function') {
        updateSidebarVisibility(is_expanded);
    }
    
    localStorage.setItem('sidebarStatus', is_expanded ? 'open' : 'collapsed');
}

document.addEventListener("DOMContentLoaded", function() {
  const sidebar = document.getElementById('sidebar');
  const main = document.getElementById('mainContent');
  const overlay = document.getElementById('sidebarOverlay');
  const is_desktop = window.innerWidth >= 1024;
  const saved_status = localStorage.getItem('sidebarStatus');

  // 🔧 Terapkan status sidebar terakhir (buka/tutup)
  if (is_desktop) {
    if (saved_status === 'open') {
      sidebar.classList.add('lg:w-60');
      sidebar.classList.remove('lg:w-16');
      main.classList.add('lg:ml-60');
      main.classList.remove('lg:ml-16');
    } else {
      sidebar.classList.add('lg:w-16');
      sidebar.classList.remove('lg:w-60');
      main.classList.add('lg:ml-16');
      main.classList.remove('lg:ml-60');
    }
  } else {
    sidebar.classList.add('-translate-x-full');
    overlay.classList.add('hidden');
  }

  // 🖱️ Klik di luar sidebar menutup sidebar (mobile)
  overlay.addEventListener('click', () => {
    sidebar.classList.add('-translate-x-full');
    overlay.classList.add('hidden');
    localStorage.setItem('sidebarStatus', 'collapsed');
  });

  // 🪄 Deteksi perubahan ukuran layar
  window.addEventListener('resize', () => {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    const overlay = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('toggleBtn'); 
    const is_desktop = window.innerWidth >= 1024;
    const status = localStorage.getItem('sidebarStatus');

    if (is_desktop) {
        // Pastikan transisi mobile dihilangkan
        sidebar.classList.remove('translate-x-0', '-translate-x-full');
        if (overlay) overlay.classList.add('hidden');

        // Terapkan state desktop
        if (status === 'open') {
            sidebar.classList.add('lg:w-60');
            sidebar.classList.remove('lg:w-16');
            main.classList.add('lg:ml-60');
            main.classList.remove('lg:ml-16');
            toggleBtn.style.left = '16rem'; // Posisikan tombol toggle saat dibuka
        } else {
            sidebar.classList.add('lg:w-16');
            sidebar.classList.remove('lg:w-60');
            main.classList.add('lg:ml-16');
            main.classList.remove('lg:ml-60');
            toggleBtn.style.left = '5rem'; // Posisikan tombol toggle saat ditutup
        }
        updateClearBtn(); // Update tombol clear desktop
    } else {
        // Pastikan transisi desktop dihilangkan
        sidebar.classList.remove('lg:w-60', 'lg:w-16');
        main.classList.remove('lg:ml-60', 'lg:ml-16');
        toggleBtn.style.left = '1.25rem'; // Reset posisi ke kiri atas di mobile

        // Terapkan state mobile
        if (status === 'open') {
            sidebar.classList.add('translate-x-0');
            sidebar.classList.remove('-translate-x-full');
            if (overlay) overlay.classList.remove('hidden');
        } else {
            sidebar.classList.add('-translate-x-full');
            sidebar.classList.remove('translate-x-0');
            if (overlay) overlay.classList.add('hidden');
        }
  
  });
});
</script>

</body>
</html>

