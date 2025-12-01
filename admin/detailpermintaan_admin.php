<?php
require_once '../koneksi.php';
$db = $koneksi;

// Validasi ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: permintaan_admin.php");
    exit;
}

$id = (int)$_GET['id'];
$error_fetch = null;
$pemesanan = null;

// --- 1. AMBIL DATA DETAIL ---
$stmt = $db->prepare("SELECT * FROM tbl_pengajuan WHERE id = ?");
if ($stmt) {
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $pemesanan = $result->fetch_assoc();
    } else {
        $error_fetch = "Data pemesanan dengan ID $id tidak ditemukan.";
    }
    $stmt->close();
} else {
    $error_fetch = "Query Error: " . $db->error;
}

/* =============================================
   LOGIKA HITUNG ESTIMASI BIAYA (INTEGER MAPPING)
   ============================================= */
$total_biaya = 0;
$harga_per_hari = 0;
$durasi = 0;
$keterangan_tarif = '-'; 

if ($pemesanan) {
    // 1. Hitung Durasi Hari
    $tgl_mulai = new DateTime($pemesanan['tanggal_peminjaman']);
    $tgl_selesai = new DateTime($pemesanan['tanggal_selesai']);
    $diff = $tgl_selesai->diff($tgl_mulai);
    $durasi = $diff->days + 1; 

    // 2. Tentukan Tabel Sumber Data
    $tabel_sumber = '';
    $kategori = $pemesanan['kategori'];
    $fakultas = $pemesanan['fakultas'];
    
    if ($kategori === 'Ruang Multiguna') {
        $tabel_sumber = 'tbl_ruangmultiguna';
    } elseif ($kategori === 'Fasilitas') {
        $tabel_sumber = 'tbl_fasilitas';
    } elseif ($kategori === 'Usaha') {
        $tabel_sumber = 'tbl_usaha';
    } elseif ($kategori === 'Laboratorium') {
        if ($fakultas === 'FTIK') $tabel_sumber = 'labftik';
        elseif ($fakultas === 'FTEN') $tabel_sumber = 'labften';
        elseif ($fakultas === 'FKET') $tabel_sumber = 'labfket';
        elseif ($fakultas === 'FTBE') $tabel_sumber = 'labftbe';
    }

    // 3. Ambil Harga dari Database Master
    if (!empty($tabel_sumber)) {
        $nama_aset = $pemesanan['subpilihan']; 
        
        $sql_harga = "";
        if ($kategori === 'Laboratorium') {
            $sql_harga = "SELECT tarif_sewa_laboratorium AS internal, tarif_sewa_laboratorium AS eksternal FROM $tabel_sumber WHERE nama = ?";
        } elseif ($kategori === 'Usaha') {
            $sql_harga = "SELECT tarif_eksternal AS internal, tarif_eksternal AS eksternal FROM $tabel_sumber WHERE nama = ?";
        } else {
            $sql_harga = "SELECT tarif_internal AS internal, tarif_eksternal AS eksternal FROM $tabel_sumber WHERE nama = ?";
        }

        $stmt_harga = $db->prepare($sql_harga);
        if ($stmt_harga) {
            $stmt_harga->bind_param('s', $nama_aset);
            $stmt_harga->execute();
            $res_harga = $stmt_harga->get_result();
            
            if ($row_harga = $res_harga->fetch_assoc()) {
                
                // --- LOGIKA PENENTUAN HARGA (CEK ANGKA) ---
                
                // Ambil data tarif (INT)
                $tarif_kode = (int)$pemesanan['tarif_sewa']; // 1=Internal, 2=Eksternal, 0=Mhs
                $status_raw = strtoupper($pemesanan['status_peminjam']);

                // 1. Cek Mahasiswa
                if (strpos($status_raw, 'MAHASISWA') !== false) {
                    $harga_per_hari = 0;
                    $keterangan_tarif = "Gratis (Mahasiswa)";
                } 
                else {
                    // 2. Cek Umum berdasarkan KODE ANGKA
                    
                    if ($tarif_kode == 1) { // 1 = INTERNAL
                        $harga_per_hari = $row_harga['internal'];
                        $keterangan_tarif = "Tarif Internal";
                    } 
                    elseif ($tarif_kode == 2) { // 2 = EKSTERNAL
                        $harga_per_hari = $row_harga['eksternal'];
                        $keterangan_tarif = "Tarif Eksternal";
                    } 
                    else {
                        // Fallback jika data 0 tapi status Umum (Default Eksternal)
                        $harga_per_hari = $row_harga['eksternal']; 
                        $keterangan_tarif = "Tarif Eksternal (Default)"; 
                    }
                }
            }
            $stmt_harga->close();
        }
    }
    $total_biaya = $harga_per_hari * $durasi;
}

// ... (Sisa kode ke bawah sama, tidak ada perubahan logika tarif) ...
$success_message = null;
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'disetujui') $success_message = "✅ Permintaan berhasil disetujui.";
    elseif ($_GET['success'] === 'ditolak') $success_message = "❌ Permintaan berhasil ditolak.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_btn'])) {
    $action = $_POST['action_btn'];
    $catatan = trim($_POST['catatan_admin']);
    $new_status = ($action === 'approve') ? 'disetujui' : (($action === 'reject') ? 'ditolak' : '');

    if ($new_status) {
        $stmt_update = $db->prepare("UPDATE tbl_pengajuan SET status = ?, catatan_admin = ? WHERE id = ?");
        $stmt_update->bind_param('ssi', $new_status, $catatan, $id);
        if ($stmt_update->execute()) {
            header("Location: detailpermintaan_admin.php?id=$id&success=$new_status");
            exit;
        }
        $stmt_update->close();
    }
}

$fileSurat = null;
if ($pemesanan && !empty($pemesanan['surat_peminjaman'])) {
    $path = '../uploads/' . $pemesanan['surat_peminjaman']; 
    if (file_exists($path)) $fileSurat = $path;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Permintaan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .text-dark-accent { color: #202938; }
        .detail-card {
            background-color: #f9fafb;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem; 
            border: 1px solid #e5e7eb;
        }
        @media (min-width: 1024px) {
            .main { margin-left: 4rem; }
            .main.lg\:ml-60 { margin-left: 15rem; }
        }
        @media (max-width: 1023px) {
            .main { margin-left: 0 !important; }
        }
        #toggleBtn { position: relative; z-index: 51 !important; }
        .modal { opacity: 0; visibility: hidden; transition: all 0.3s ease; }
        .modal.show { opacity: 1; visibility: visible; }
        .modal-content { transform: scale(0.9); transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .modal.show .modal-content { transform: scale(1); }
    </style>
</head>
<body class="bg-blue-100 flex min-h-screen text-gray-800">

<?php include 'sidebar_admin.php'; ?>

<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

<div id="mainContent" class="main flex-1 p-5 transition-all duration-300 lg:ml-16">
    <div class="flex justify-between items-center mb-4 sm:mb-6">
        <button id="toggleBtn" class="bg-amber-500 hover:bg-amber-600 text-gray-900 p-2 rounded-lg transition-colors shadow-md relative z-50" onclick="toggleSidebar()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        <div class="text-sm text-gray-600 hidden sm:block">
            <span class="font-semibold">Detail Permintaan</span>
        </div>
    </div>

    <?php if (!empty($success_message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-4 shadow-md" role="alert">
            <span class="block sm:inline"><?= htmlspecialchars($success_message) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error_fetch): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-4 shadow-md" role="alert">
            <span class="block sm:inline">❌ <?= htmlspecialchars($error_fetch) ?></span>
            <div class="mt-3">
                <a href="permintaan_admin.php" class="px-4 py-2 bg-white border border-red-200 text-red-600 rounded-lg hover:bg-red-50 transition-colors text-sm">← Kembali</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($pemesanan): ?>
    <div class="bg-white p-6 rounded-xl shadow-lg grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="section lg:col-span-2 space-y-6">
            <div class="flex items-center justify-between border-b border-gray-200 pb-4">
                <h2 class="text-xl font-bold text-dark-accent">Informasi Peminjam</h2>
                <span class="text-sm text-gray-500">ID: #<?= $pemesanan['id'] ?></span>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div class="detail-card">
                    <span class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Nama Peminjam</span>
                    <span class="block text-base font-medium text-gray-900"><?= htmlspecialchars($pemesanan['nama_peminjam'] ?? '-') ?></span>
                </div>
                <div class="detail-card bg-yellow-50 border-yellow-100">
                    <span class="block text-xs font-bold text-yellow-700 uppercase tracking-wide mb-1">Status Peminjam</span>
                    <span class="block text-base font-bold text-yellow-900"><?= htmlspecialchars($pemesanan['status_peminjam'] ?? '-') ?></span>
                </div>
                <div class="detail-card">
                    <span class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Kategori</span>
                    <span class="block text-base font-medium text-gray-900"><?= htmlspecialchars($pemesanan['kategori'] ?? '-') ?></span>
                </div>
                <div class="detail-card col-span-1 sm:col-span-2">
                    <span class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Ruang / Aset</span>
                    <span class="block text-lg font-bold text-blue-600"><?= htmlspecialchars($pemesanan['subpilihan'] ?? '-') ?></span>
                </div>
                
                <div class="detail-card bg-blue-50 border-blue-100">
                    <span class="block text-xs font-bold text-blue-700 uppercase tracking-wide mb-1">Jenis Tarif (Sistem)</span>
                    <span class="block text-base font-bold text-blue-900">
                        <?= htmlspecialchars($keterangan_tarif) ?>
                    </span>
                </div>

                <div class="detail-card">
                    <span class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Peserta</span>
                    <span class="block text-base font-medium text-gray-900"><?= $pemesanan['jumlah_peserta'] ?> Orang</span>
                </div>
                <div class="detail-card">
                    <span class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Tanggal Mulai</span>
                    <span class="block text-base font-medium text-gray-900"><?= date('d F Y', strtotime($pemesanan['tanggal_peminjaman'])) ?></span>
                </div>
                <div class="detail-card">
                    <span class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Tanggal Selesai</span>
                    <span class="block text-base font-medium text-gray-900"><?= date('d F Y', strtotime($pemesanan['tanggal_selesai'])) ?></span>
                </div>
                <div class="detail-card col-span-1 sm:col-span-2">
                    <span class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Waktu</span>
                    <span class="block text-base font-medium text-gray-900">
                        <?= date('H:i', strtotime($pemesanan['jam_mulai'])) ?> - <?= date('H:i', strtotime($pemesanan['jam_selesai'])) ?>
                    </span>
                </div>
                <div class="detail-card col-span-1 sm:col-span-2 bg-gray-50 border-gray-200">
                    <span class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Agenda Kegiatan</span>
                    <p class="text-sm text-gray-800 whitespace-pre-wrap leading-relaxed"><?= htmlspecialchars($pemesanan['agenda'] ?? '-') ?></p>
                </div>
            </div>

            <div class="pt-4 border-t border-gray-200 mt-6">
                <h3 class="text-lg font-bold text-dark-accent mb-3">Dokumen Pendukung</h3>
                <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">📄</span>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Surat Peminjaman</p>
                            <?php if($fileSurat): ?>
                                <p class="text-xs text-gray-500 truncate max-w-[200px]"><?= htmlspecialchars($pemesanan['surat_peminjaman']) ?></p>
                            <?php else: ?>
                                <p class="text-xs text-red-500 italic">File tidak ditemukan</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if($fileSurat): ?>
                        <a href="<?= $fileSurat ?>" download class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition-colors">⬇ Download</a>
                    <?php else: ?>
                         <button disabled class="px-4 py-2 bg-gray-100 border border-gray-200 rounded-lg text-sm text-gray-400 cursor-not-allowed">Tidak Ada</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="section lg:col-span-1 border-t lg:border-t-0 pt-6 lg:pt-0 lg:border-l lg:pl-8 border-gray-200">
            <h2 class="text-xl font-bold mb-6 pb-2 border-b border-gray-200 text-dark-accent">Status & Aksi</h2>
            <?php
                $st = strtolower($pemesanan['status']);
                $bg_status = 'bg-gray-100 border-gray-300 text-gray-600';
                if($st == 'pending' || $st == 'menunggu') $bg_status = 'bg-yellow-50 border-yellow-200 text-yellow-700';
                if($st == 'disetujui') $bg_status = 'bg-green-50 border-green-200 text-green-700';
                if($st == 'ditolak') $bg_status = 'bg-red-50 border-red-200 text-red-700';
            ?>
            <div class="p-6 rounded-xl border-2 <?= $bg_status ?> text-center mb-8">
                <span class="block text-xs font-bold uppercase tracking-wide opacity-70 mb-1">Status Saat Ini</span>
                <span class="block text-2xl font-extrabold"><?= ucfirst($pemesanan['status']) ?></span>
            </div>

            <div class="mt-4 p-4 bg-indigo-50 rounded-xl border border-indigo-100 text-center shadow-sm">
                <h4 class="text-xs font-bold text-indigo-500 uppercase tracking-wide mb-2">Estimasi Biaya</h4>
                <p class="text-2xl font-extrabold text-indigo-700">
                    Rp <?= number_format($total_biaya, 0, ',', '.') ?>
                </p>
                <div class="text-xs text-indigo-400 mt-2 flex justify-between px-2 border-t border-indigo-200 pt-2">
                    <span><?= $keterangan_tarif ?></span>
                    <span><?= $durasi ?> Hari</span>
                </div>
            </div>

            <div class="space-y-3 mt-8">
                <?php if ($st == 'pending' || $st == 'menunggu'): ?>
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" onclick="openModal('approve')" class="block w-full py-3 px-4 bg-green-500 hover:bg-green-600 text-white font-bold rounded-lg text-center shadow-md transition-transform hover:scale-105">
                            ✔ Setujui
                        </button>
                        <button type="button" onclick="openModal('reject')" class="block w-full py-3 px-4 bg-red-500 hover:bg-red-600 text-white font-bold rounded-lg text-center shadow-md transition-transform hover:scale-105">
                            ✖ Tolak
                        </button>
                    </div>
                <?php else: ?>
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <h4 class="text-sm font-bold text-gray-700 mb-1">Catatan Admin:</h4>
                        <p class="text-sm text-gray-600 italic">
                            <?= !empty($pemesanan['catatan_admin']) ? nl2br(htmlspecialchars($pemesanan['catatan_admin'])) : '(Tidak ada catatan)' ?>
                        </p>
                    </div>
                <?php endif; ?>

                <a href="permintaan_admin.php" class="block w-full py-3 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg text-center transition-colors mt-4">
                    ⬅ Kembali ke Daftar
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div id="actionModal" class="modal fixed inset-0 z-[100] flex items-center justify-center">
    <div class="absolute inset-0 bg-black bg-opacity-60 transition-opacity" onclick="closeModal()"></div>
    <div class="modal-content bg-white w-full max-w-md rounded-2xl shadow-2xl z-10 relative overflow-hidden">
        <div id="modalHeader" class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800" id="modalTitle">Konfirmasi Aksi</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>
        <form method="POST" action="">
            <div class="p-6 space-y-4">
                <p class="text-sm text-gray-600" id="modalDesc">Silakan berikan catatan untuk pemohon (Opsional).</p>
                <input type="hidden" name="action_btn" id="modalActionInput">
                <div>
                    <label for="catatan_admin" class="block text-sm font-semibold text-gray-700 mb-2">Catatan Admin:</label>
                    <textarea name="catatan_admin" id="catatan_admin" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-shadow" placeholder="Contoh: Disetujui, kunci ambil di satpam... atau Ditolak karena ruangan penuh..."></textarea>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex gap-3 justify-end border-t border-gray-100">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-100 transition-colors">Batal</button>
                <button type="submit" id="modalSubmitBtn" class="px-6 py-2 text-white font-bold rounded-lg shadow-md transition-transform hover:scale-105">Kirim Keputusan</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    const overlay = document.getElementById('sidebarOverlay');
    const is_desktop = window.innerWidth >= 1024;
    if (is_desktop) {
        sidebar.classList.toggle('lg:w-60');
        sidebar.classList.toggle('lg:w-16');
        main.classList.toggle('lg:ml-60');
        main.classList.toggle('lg:ml-16');
    } else {
        sidebar.classList.toggle('translate-x-0');
        sidebar.classList.toggle('-translate-x-full');
        if (overlay) overlay.classList.toggle('hidden');
    }
    localStorage.setItem('sidebarStatus', sidebar.classList.contains('lg:w-60') ? 'open' : 'collapsed');
}
function openModal(action) {
    const modal = document.getElementById('actionModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalDesc = document.getElementById('modalDesc');
    const modalInput = document.getElementById('modalActionInput');
    const submitBtn = document.getElementById('modalSubmitBtn');
    const header = document.getElementById('modalHeader');
    modalInput.value = action;
    if (action === 'approve') {
        modalTitle.innerText = "✅ Setujui Permintaan";
        modalTitle.className = "text-lg font-bold text-green-700";
        modalDesc.innerText = "Anda akan menyetujui permintaan ini. Berikan catatan tambahan jika perlu.";
        submitBtn.innerText = "Ya, Setujui";
        submitBtn.className = "px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg shadow-md transition-transform hover:scale-105";
        header.className = "px-6 py-4 border-b border-green-100 bg-green-50 flex justify-between items-center";
    } else {
        modalTitle.innerText = "❌ Tolak Permintaan";
        modalTitle.className = "text-lg font-bold text-red-700";
        modalDesc.innerText = "Anda akan menolak permintaan ini. Mohon berikan alasan penolakan.";
        submitBtn.innerText = "Ya, Tolak";
        submitBtn.className = "px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg shadow-md transition-transform hover:scale-105";
        header.className = "px-6 py-4 border-b border-red-100 bg-red-50 flex justify-between items-center";
    }
    modal.classList.remove('hidden');
    setTimeout(() => { modal.classList.add('show'); }, 10);
}
function closeModal() {
    const modal = document.getElementById('actionModal');
    modal.classList.remove('show');
    setTimeout(() => { modal.classList.add('hidden'); }, 300);
}
document.addEventListener("DOMContentLoaded", () => {
    const sidebar = document.getElementById("sidebar");
    const main = document.getElementById("mainContent");
    const status = localStorage.getItem('sidebarStatus');
    const is_desktop = window.innerWidth >= 1024;
    if (status === 'open' && is_desktop) {
        sidebar.classList.add('lg:w-60');
        sidebar.classList.remove('lg:w-16');
        main.classList.add('lg:ml-60');
        main.classList.remove('lg:ml-16');
    }
});
</script>
</body>
</html>