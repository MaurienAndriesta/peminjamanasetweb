<?php
require_once '../koneksi.php'; 
$success_message = null;
$db = $koneksi;

/* =========================
   PROSES ACTION (POST VIA MODAL)
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_btn'], $_POST['request_id'])) {
    $id = (int) $_POST['request_id'];
    $action = $_POST['action_btn'];
    $catatan = trim($_POST['catatan_admin']);

    $new_status = '';
    if ($action === 'approve') {
        $new_status = 'disetujui';
    } elseif ($action === 'reject') {
        $new_status = 'ditolak';
    }

    if ($new_status) {
        // Update Status DAN Catatan Admin
        $sql = "UPDATE tbl_pengajuan SET status = ?, catatan_admin = ? WHERE id = ?";
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, "ssi", $new_status, $catatan, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_message = ($new_status === 'disetujui') 
                ? "✅ Permintaan berhasil disetujui." 
                : "❌ Permintaan berhasil ditolak.";
            
            // Redirect agar tidak resubmit saat refresh
            header("Location: permintaan_admin.php?success_msg=" . urlencode($success_message));
            exit;
        }
        mysqli_stmt_close($stmt);
    }
}

/* =========================
   AMBIL PESAN SUKSES (REDIRECT)
   ========================= */
if (isset($_GET['success_msg'])) {
    $success_message = htmlspecialchars($_GET['success_msg']);
}

/* =========================
   FILTER & PENCARIAN
   ========================= */
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

$sql = "SELECT * FROM tbl_pengajuan WHERE 1=1";
$params = [];
$types = "";

if ($status_filter !== 'all') {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search)) {
    $sql .= " AND (nama_peminjam LIKE ? OR subpilihan LIKE ? OR agenda LIKE ?)";
    $keyword = "%$search%";
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
    $types .= "sss";
}

$sql .= " ORDER BY created_at DESC";

$stmt = mysqli_prepare($koneksi, $sql);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$pemesanan = [];
while ($row = mysqli_fetch_assoc($result)) {
    $pemesanan[] = $row;
}

/* =========================
   STATISTIK
   ========================= */
$sql_stats = "
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'Menunggu' OR status = 'pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status = 'disetujui' THEN 1 ELSE 0 END) AS disetujui,
        SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) AS ditolak
    FROM tbl_pengajuan
";
$res_stats = mysqli_query($koneksi, $sql_stats);
$stats = mysqli_fetch_assoc($res_stats);
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
    body { font-family: 'Poppins', sans-serif; }
    .text-dark-accent { color: #202938; }
    
    /* --- ANIMASI TAMBAHAN --- */
    
    /* 1. Fade In Up (Untuk konten utama saat load) */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in-up {
        animation: fadeInUp 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
    }

    /* 2. Slide Down (Untuk pesan sukses) */
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-slide-down {
        animation: slideDown 0.4s ease-out forwards;
    }

    /* --- MODAL STYLES (YANG LAMA TETAP DIPAKAI) --- */
    .modal { opacity: 0; visibility: hidden; transition: all 0.3s ease; }
    .modal.show { opacity: 1; visibility: visible; }
    .modal-content { transform: scale(0.9); transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
    .modal.show .modal-content { transform: scale(1); }

    @media (min-width: 1024px) {
        .main { margin-left: 4rem; }
        .main.lg\:ml-60 { margin-left: 15rem; }
    }
    @media (max-width: 1023px) {
        .main { margin-left: 0 !important; }
    }
    #toggleBtn { position: relative; z-index: 51 !important; }
    
    /* Haluskan transisi umum */
    .transition-all-ease { transition: all 0.3s ease-in-out; }
</style>
</head>
<body class="bg-blue-100 min-h-screen text-gray-800">

<?php include 'sidebar_admin.php'; ?>

<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden transition-opacity duration-300"></div>

<div id="mainContent" class="main flex-1 p-3 sm:p-5 transition-all duration-300 lg:ml-16 animate-fade-in-up">
    
  <div class="flex justify-between items-center mb-4 sm:mb-6">
    <button id="toggleBtn" class="bg-amber-500 hover:bg-amber-600 text-gray-900 p-2 rounded-lg transition-colors shadow-md relative z-50 active:scale-95" onclick="toggleSidebar()">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
      </svg>
    </button>
  </div>

  <div class="content bg-white p-4 sm:p-6 rounded-xl shadow-lg transition-all-ease">
    <div class="mb-4 sm:mb-6 pb-4 border-b">
      <h1 class="text-xl sm:text-2xl font-bold text-dark-accent flex items-center gap-2">
        <span class="text-2xl">📄</span>
        <span>Daftar Permintaan Peminjaman</span>
      </h1>
      <p class="text-xs sm:text-sm text-gray-600 mt-1">Kelola permohonan yang masuk dari user.</p>
    </div>

    <?php if (!empty($success_message)): ?>
      <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded-lg mb-4 shadow-sm text-sm animate-slide-down">
        <?= $success_message ?>
      </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 mb-6 sm:mb-8">
      <div class="bg-gray-50 p-4 rounded-xl shadow border border-gray-200 flex justify-between items-center transition-all duration-300 hover:-translate-y-1 hover:shadow-md">
          <div>
            <p class="text-xs text-gray-500 font-bold uppercase">Total</p>
            <p class="text-xl font-bold text-gray-800"><?= $stats['total'] ?? 0 ?></p>
          </div>
          <div class="text-2xl">📦</div>
      </div>
      <div class="bg-yellow-50 p-4 rounded-xl shadow border border-yellow-200 flex justify-between items-center transition-all duration-300 hover:-translate-y-1 hover:shadow-md">
          <div>
            <p class="text-xs text-yellow-600 font-bold uppercase">Menunggu</p>
            <p class="text-xl font-bold text-yellow-800"><?= $stats['pending'] ?? 0 ?></p>
          </div>
          <div class="text-2xl">⏳</div>
      </div>
      <div class="bg-green-50 p-4 rounded-xl shadow border border-green-200 flex justify-between items-center transition-all duration-300 hover:-translate-y-1 hover:shadow-md">
          <div>
            <p class="text-xs text-green-600 font-bold uppercase">Disetujui</p>
            <p class="text-xl font-bold text-green-800"><?= $stats['disetujui'] ?? 0 ?></p>
          </div>
          <div class="text-2xl">✅</div>
      </div>
      <div class="bg-red-50 p-4 rounded-xl shadow border border-red-200 flex justify-between items-center transition-all duration-300 hover:-translate-y-1 hover:shadow-md">
          <div>
            <p class="text-xs text-red-600 font-bold uppercase">Ditolak</p>
            <p class="text-xl font-bold text-red-800"><?= $stats['ditolak'] ?? 0 ?></p>
          </div>
          <div class="text-2xl">❌</div>
      </div>
    </div>

    <form method="GET" class="bg-gray-50 border border-gray-200 rounded-xl p-3 sm:p-4 flex flex-wrap gap-3 items-end mb-6 sm:mb-8 shadow-sm transition-all-ease hover:shadow-md">
      <input type="text" name="search" placeholder="🔍 Cari nama / ruangan..." value="<?= htmlspecialchars($search) ?>" class="flex-1 min-w-[180px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500 text-sm transition-shadow focus:shadow-sm">
      <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg bg-white focus:ring-amber-500 focus:border-amber-500 text-sm transition-shadow focus:shadow-sm">
        <option value="all" <?= $status_filter==='all'?'selected':'' ?>>Semua Status</option>
        <option value="Menunggu" <?= $status_filter==='Menunggu'?'selected':'' ?>>Menunggu</option>
        <option value="disetujui" <?= $status_filter==='disetujui'?'selected':'' ?>>Disetujui</option>
        <option value="ditolak" <?= $status_filter==='ditolak'?'selected':'' ?>>Ditolak</option>
      </select>
      <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-gray-900 px-3 sm:px-4 py-2 rounded-lg font-semibold transition-all text-xs sm:text-sm whitespace-nowrap active:scale-95 hover:shadow">
        🔍 Tampilkan
      </button>
    </form>

    <div class="overflow-x-auto border border-gray-100 rounded-xl shadow-md transition-all-ease hover:shadow-lg">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-700 text-white">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">No</th>
            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Peminjam</th>
            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Ruang / Aset</th>
            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Kategori</th>
            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Tanggal</th>
            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Status</th>
            <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Aksi</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <?php if (empty($pemesanan)): ?>
          <tr>
            <td colspan="7" class="px-4 py-6 text-center text-gray-500 italic text-sm">
              Belum ada permintaan peminjaman.
            </td>
          </tr>
          <?php else: foreach($pemesanan as $i => $r): ?>
          <tr class="hover:bg-gray-50 transition-colors duration-200">
            <td class="px-4 py-3 text-sm"><?= $i + 1 ?></td>
            <td class="px-4 py-3 text-sm font-semibold text-gray-800"><?= htmlspecialchars($r['nama_peminjam']) ?></td>
            <td class="px-4 py-3 text-sm"><?= htmlspecialchars($r['subpilihan']) ?></td>
            <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($r['kategori']) ?></td>
            <td class="px-4 py-3 text-sm text-gray-600">
                <?= date('d/m/Y', strtotime($r['tanggal_peminjaman'])) ?> <br>
                <span class="text-xs text-gray-500"><?= substr($r['jam_mulai'],0,5) ?> - <?= substr($r['jam_selesai'],0,5) ?></span>
            </td>
            <td class="px-4 py-3 text-sm">
              <?php
                $st = strtolower($r['status']);
                $bg = 'bg-gray-100 text-gray-800';
                if ($st == 'menunggu' || $st == 'pending') $bg = 'bg-yellow-100 text-yellow-800';
                if ($st == 'disetujui') $bg = 'bg-green-100 text-green-800';
                if ($st == 'ditolak') $bg = 'bg-red-100 text-red-800';
              ?>
              <span class="px-2 py-1 rounded-full text-xs font-bold <?= $bg ?> transition-all">
                <?= ucfirst($r['status']) ?>
              </span>
            </td>
            <td class="px-4 py-3 text-center">
              <div class="flex gap-2 justify-center">
                <a href="detailpermintaan_admin.php?id=<?= $r['id'] ?>" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-all active:scale-95 hover:shadow">Detail</a>
                
                <?php if ($st == 'menunggu' || $st == 'pending'): ?>
                    <button type="button" onclick="openModal(<?= $r['id'] ?>, 'approve')" 
                            class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-xs transition-all hover:scale-105 active:scale-95 hover:shadow">
                        ✔
                    </button>
                    <button type="button" onclick="openModal(<?= $r['id'] ?>, 'reject')" 
                            class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs transition-all hover:scale-105 active:scale-95 hover:shadow">
                        ✖
                    </button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<div id="actionModal" class="modal fixed inset-0 z-[100] flex items-center justify-center">
    <div class="absolute inset-0 bg-black bg-opacity-60 transition-opacity duration-300" onclick="closeModal()"></div>
    <div class="modal-content bg-white w-full max-w-md rounded-2xl shadow-2xl z-10 relative overflow-hidden">
        <div id="modalHeader" class="px-6 py-4 border-b border-gray-100 flex justify-between items-center transition-colors duration-300">
            <h3 class="text-lg font-bold text-gray-800 transition-colors duration-300" id="modalTitle">Konfirmasi Aksi</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl transition-colors">&times;</button>
        </div>
        
        <form method="POST" action="">
            <div class="p-6 space-y-4">
                <p class="text-sm text-gray-600" id="modalDesc">Silakan berikan catatan untuk pemohon (Opsional).</p>
                <input type="hidden" name="request_id" id="modalRequestId">
                <input type="hidden" name="action_btn" id="modalActionInput">
                <div>
                    <label for="catatan_admin" class="block text-sm font-semibold text-gray-700 mb-2">Catatan Admin:</label>
                    <textarea name="catatan_admin" id="catatan_admin" rows="4" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-shadow focus:shadow-md"
                        placeholder="Contoh: Disetujui, kunci ambil di satpam... atau Ditolak karena ruangan penuh..."></textarea>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 flex gap-3 justify-end border-t border-gray-100">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-100 transition-all active:scale-95">
                    Batal
                </button>
                <button type="submit" id="modalSubmitBtn" class="px-6 py-2 text-white font-bold rounded-lg shadow-md transition-all hover:scale-105 active:scale-95 hover:shadow-lg">
                    Kirim Keputusan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    const is_desktop = window.innerWidth >= 1024;

    if (is_desktop) {
        // Desktop: Toggle width
        sidebar.classList.toggle('lg:w-60');
        sidebar.classList.toggle('lg:w-16');
        main.classList.toggle('lg:ml-60');
        main.classList.toggle('lg:ml-16');
    } else {
        // Mobile: Toggle visibility
        sidebar.classList.toggle('translate-x-0');
        sidebar.classList.toggle('-translate-x-full');
    }

    const is_expanded = sidebar.classList.contains('lg:w-60') || sidebar.classList.contains('translate-x-0');
    
    if (typeof updateSidebarVisibility === 'function') {
        updateSidebarVisibility(is_expanded);
    }
    
    localStorage.setItem('sidebarStatus', is_expanded ? 'open' : 'collapsed');
}

// --- FUNGSI MODAL ---
function openModal(id, action) {
    const modal = document.getElementById('actionModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalDesc = document.getElementById('modalDesc');
    const modalIdInput = document.getElementById('modalRequestId');
    const modalActionInput = document.getElementById('modalActionInput');
    const submitBtn = document.getElementById('modalSubmitBtn');
    const header = document.getElementById('modalHeader');
    const textarea = document.getElementById('catatan_admin');

    textarea.value = '';
    modalIdInput.value = id;
    modalActionInput.value = action;

    // Reset kelas warna sebelum menambahkan yang baru
    modalTitle.classList.remove('text-green-700', 'text-red-700');
    submitBtn.classList.remove('bg-green-600', 'hover:bg-green-700', 'bg-red-600', 'hover:bg-red-700');
    header.classList.remove('border-green-100', 'bg-green-50', 'border-red-100', 'bg-red-50');

    if (action === 'approve') {
        modalTitle.innerText = "✅ Setujui Permintaan";
        modalTitle.classList.add('text-green-700');
        modalDesc.innerText = "Anda akan menyetujui permintaan ini. Berikan catatan tambahan jika perlu.";
        submitBtn.innerText = "Ya, Setujui";
        submitBtn.classList.add('bg-green-600', 'hover:bg-green-700');
        header.classList.add('border-green-100', 'bg-green-50');
    } else {
        modalTitle.innerText = "❌ Tolak Permintaan";
        modalTitle.classList.add('text-red-700');
        modalDesc.innerText = "Anda akan menolak permintaan ini. Mohon berikan alasan penolakan.";
        submitBtn.innerText = "Ya, Tolak";
        submitBtn.classList.add('bg-red-600', 'hover:bg-red-700');
        header.classList.add('border-red-100', 'bg-red-50');
    }

    modal.classList.remove('hidden');
    // Sedikit delay agar transisi CSS berjalan
    requestAnimationFrame(() => {
        modal.classList.add('show');
    });
}

function closeModal() {
    const modal = document.getElementById('actionModal');
    modal.classList.remove('show');
    // Tunggu transisi selesai baru hide
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300); // Sesuai durasi transisi CSS
}

document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    const status = localStorage.getItem('sidebarStatus');
    
    if (status === 'open') {
        sidebar.classList.add('w-60');
        sidebar.classList.remove('w-16');
        main.classList.add('ml-60');
        main.classList.remove('ml-16');
    } else {
        sidebar.classList.remove('w-60');
        sidebar.classList.add('w-16');
        main.classList.remove('ml-60');
        main.classList.add('ml-16');
    }
});
</script>
</body>
</html>