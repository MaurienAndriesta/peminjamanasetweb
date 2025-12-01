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

// ================= LOGIKA BATALKAN PEMINJAMAN (BARU) =================
if (isset($_GET['action']) && $_GET['action'] === 'batal' && isset($_GET['id'])) {
    $id_batal = (int)$_GET['id'];
    
    // Hapus data hanya jika milik user yang sedang login (Keamanan)
    // Dan biasanya hanya status 'Menunggu' yang bisa dibatalkan user (opsional)
    $stmt = $db->prepare("DELETE FROM tbl_pengajuan WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id_batal, $user_id);
    
    if ($stmt->execute()) {
        // Redirect biar refresh dan data hilang
        header("Location: status.php?msg=cancelled");
        exit;
    }
    $stmt->close();
}

// ================= DATA MENU =================
$menu_items = [
    ['title' => 'Beranda', 'url' => 'dashboarduser.php'],
    [
        'title'  => 'Daftar Ruangan & Fasilitas',
        'type'   => 'dropdown',
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
    ['title' => 'Jadwal Ketersediaan', 'url' => 'jadwaltersedia.php'],
    ['title' => 'Status Peminjaman', 'url' => 'status.php'],
    ['title' => 'Riwayat Peminjaman', 'url' => 'riwayat.php'],
];

// ================= AMBIL DATA DARI DATABASE =================
$peminjaman = [];
$sql = "SELECT * FROM tbl_pengajuan WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // --- LOGIKA HITUNG BIAYA ---
    $harga_fix = 0;
    $durasi = 0;
    $tgl_mulai = new DateTime($row['tanggal_peminjaman']);
    $tgl_selesai = new DateTime($row['tanggal_selesai']);
    $diff = $tgl_selesai->diff($tgl_mulai);
    $durasi = $diff->days + 1;

    $tabel_sumber = '';
    if ($row['kategori'] == 'Ruang Multiguna') $tabel_sumber = 'tbl_ruangmultiguna';
    elseif ($row['kategori'] == 'Fasilitas') $tabel_sumber = 'tbl_fasilitas';
    elseif ($row['kategori'] == 'Usaha') $tabel_sumber = 'tbl_usaha';
    elseif ($row['kategori'] == 'Laboratorium') {
        $fak = $row['fakultas'];
        if($fak == 'FTIK') $tabel_sumber = 'labftik';
        elseif($fak == 'FTEN') $tabel_sumber = 'labften';
        elseif($fak == 'FKET') $tabel_sumber = 'labfket';
        elseif($fak == 'FTBE') $tabel_sumber = 'labftbe';
    }

    if ($tabel_sumber) {
        $nama_aset = $db->real_escape_string($row['subpilihan']);
        $q_harga = $db->query("SELECT * FROM $tabel_sumber WHERE nama = '$nama_aset'");
        if ($d_harga = $q_harga->fetch_assoc()) {
            if (stripos($row['status_peminjam'], 'Mahasiswa') !== false) {
                $harga_fix = 0;
            } else {
                $tipe_tarif = $row['tarif_sewa'];
                if ($row['kategori'] == 'Laboratorium') {
                    $harga_fix = $d_harga['tarif_sewa_laboratorium'] ?? 0;
                } else {
                    if (stripos($tipe_tarif, 'Eksternal') !== false) {
                        $harga_fix = $d_harga['tarif_eksternal'] ?? 0;
                    } else {
                        $harga_fix = $d_harga['tarif_internal'] ?? 0;
                    }
                }
            }
        }
    }

    $total_biaya = $harga_fix * $durasi;
    $tampilan_biaya = ($total_biaya == 0) ? "Gratis / Free" : "Rp " . number_format($total_biaya, 0, ',', '.');

    $peminjaman[] = [
        "id" => $row['id'], // ID Database Penting untuk hapus
        "kode" => "REQ-" . str_pad($row['id'], 4, '0', STR_PAD_LEFT),
        "ruangan" => $row['subpilihan'],
        "tanggal" => date('d F Y', strtotime($row['tanggal_peminjaman'])),
        "tarif" => $row['status_peminjam'] . " (" . $row['kategori'] . ")",
        "biaya" => $tampilan_biaya,
        "agenda" => $row['agenda'],
        "waktu" => date('H:i', strtotime($row['jam_mulai'])) . " - " . date('H:i', strtotime($row['jam_selesai'])),
        "peserta" => $row['jumlah_peserta'] . " Orang",
        "kategori" => $row['kategori'],
        "status" => $row['status'],
        "catatan" => $row['catatan_admin'] ?? ''
    ];
}
$stmt->close();

// ================= RENDER MENU =================
function renderMenu($items, $prefix = 'root') {
    echo "<ul class='list-none pl-3 space-y-1'>";
    foreach ($items as $index => $item) {
        $uniqueId = $prefix . '-' . $index;
        if (isset($item['type']) && $item['type'] === 'dropdown') {
            echo "<li>
                <button onclick='toggleDropdown(\"{$uniqueId}\")' class='flex justify-between items-center w-full px-3 py-2 text-left rounded-lg hover:bg-gray-700/70 transition'>
                    <span>{$item['title']}</span><span id='icon-{$uniqueId}'>▼</span>
                </button>
                <div class='hidden pl-4 border-l border-gray-700 ml-2 mt-1' id='submenu-{$uniqueId}'>";
                renderMenu($item['submenu'], $uniqueId);
            echo "</div></li>";
        } else {
            echo "<li><a href='{$item['url']}' class='block px-3 py-2 rounded-lg hover:bg-gray-700/70 transition'>{$item['title']}</a></li>";
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
  <title>Status Peminjaman</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    * { font-family: 'Inter', sans-serif; }
    html { scroll-behavior: smooth; }
    body { background: linear-gradient(135deg, #D1E5EA 0%, #B8D4DB 100%); }
    .fade-in { animation: fadeIn 0.6s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .card-hover { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    .card-hover:hover { transform: translateY(-8px) scale(1.02); }
    .glass-effect { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3); }
    .status-badge { position: relative; overflow: hidden; }
    .status-badge::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent); transition: left 0.5s; }
    .status-badge:hover::before { left: 100%; }
    .search-glow:focus { box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1), 0 0 20px rgba(59, 130, 246, 0.2); }
    .modal-backdrop { backdrop-filter: blur(8px); animation: fadeInBackdrop 0.3s ease-out; }
    @keyframes fadeInBackdrop { from { opacity: 0; } to { opacity: 1; } }
    .modal-content { animation: slideUp 0.3s ease-out; }
    @keyframes slideUp { from { opacity: 0; transform: translateY(30px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
    .btn-cancel { position: relative; overflow: hidden; }
    .btn-cancel::before { content: ''; position: absolute; top: 50%; left: 50%; width: 0; height: 0; border-radius: 50%; background: rgba(255, 255, 255, 0.2); transform: translate(-50%, -50%); transition: width 0.6s, height 0.6s; }
    .btn-cancel:hover::before { width: 300px; height: 300px; }
  </style>
</head>

<body class="min-h-screen">
<div id="overlay" class="hidden fixed inset-0 bg-black/50 z-40 transition-opacity duration-300" onclick="closeSidebar()"></div>

<div id="sidebar" class="fixed top-0 left-0 w-72 h-full bg-gray-800 text-white transform -translate-x-full transition-transform duration-300 z-50 shadow-2xl">
  <div class="bg-gray-900 px-6 py-5 font-semibold text-center border-b border-gray-700 text-lg uppercase tracking-wide">Menu Utama</div>
  <nav class="p-4"><?php renderMenu($menu_items); ?></nav>
</div>

<header class="bg-gradient-to-r from-[#132544] to-[#1a3a5f] text-white shadow-xl relative overflow-hidden">
  <div class="absolute inset-0 opacity-10">
    <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
      <defs><pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="1"/></pattern></defs>
      <rect width="100%" height="100%" fill="url(#grid)" />
    </svg>
  </div>
  <div class="flex items-center justify-between px-6 py-4 relative z-10">
    <button id="menuBtn" class="bg-gray-800 text-white p-3 rounded-md"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
    <div class="flex items-center justify-center flex-1 gap-3">
      <div class="bg-white/10 p-2.5 rounded-xl backdrop-blur-sm"><svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div>
      <h1 class="text-2xl sm:text-3xl font-extrabold tracking-wide drop-shadow-lg">Status Peminjaman</h1>
    </div>
    <div class="w-12"></div>
  </div>
  <div class="text-center pb-3"><p class="text-sm opacity-90 font-medium">Sistem Manajemen Aset Kampus ITPLN</p></div>
</header>

<main class="container mx-auto px-6 py-10">
  <div class="text-center mb-10 fade-in">
    <div class="inline-flex items-center gap-3 bg-white/80 backdrop-blur-sm px-6 py-3 rounded-2xl shadow-lg">
      <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      <h2 class="text-2xl font-bold text-gray-800">Daftar Peminjaman Saya</h2>
    </div>
  </div>

  <?php if(isset($_GET['msg']) && $_GET['msg'] == 'cancelled'): ?>
    <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative text-center fade-in" role="alert">
        <strong class="font-bold">Berhasil!</strong>
        <span class="block sm:inline">Peminjaman telah dibatalkan dan dihapus.</span>
    </div>
  <?php endif; ?>

  <div class="mb-12 flex justify-center fade-in" style="animation-delay: 0.1s;">
    <div class="relative w-full sm:w-2/3 lg:w-1/2">
      <div class="absolute left-5 top-1/2 -translate-y-1/2 text-gray-400"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg></div>
      <input id="searchInput" type="text" placeholder="Cari berdasarkan kode, ruangan, atau status..." class="search-glow w-full pl-12 pr-4 py-4 border-2 border-gray-200 rounded-2xl shadow-lg focus:ring-2 focus:ring-blue-400 focus:border-blue-400 focus:outline-none transition-all duration-300 bg-white text-gray-800 font-medium">
    </div>
  </div>

  <?php if (empty($peminjaman)): ?>
    <div class="text-center py-10 text-gray-500 bg-white rounded-xl shadow-md">
        <p class="text-lg">🚫 Anda belum memiliki riwayat peminjaman.</p>
        <a href="jadwaltersedia.php" class="text-blue-500 hover:underline mt-2 inline-block">Ajukan Peminjaman Sekarang</a>
    </div>
  <?php else: ?>
  
  <div id="peminjamanList" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
    <?php foreach ($peminjaman as $index => $data): ?>
      <div class="fade-in card-hover glass-effect rounded-2xl shadow-lg hover:shadow-2xl p-6 border-l-4 
        <?php 
            if ($data['status'] === 'Menunggu') echo 'border-yellow-400';
            elseif ($data['status'] === 'disetujui' || $data['status'] === 'Disetujui') echo 'border-green-400';
            else echo 'border-red-400'; 
        ?>" 
        style="animation-delay: <?= $index * 0.1 ?>s;">
        
        <div class="flex justify-between items-start mb-5">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
              <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
              <p class="text-sm text-gray-500 font-medium">Kode: <span class="font-bold text-gray-700"><?= $data['kode']; ?></span></p>
            </div>
            <h3 class="text-lg font-bold text-[#132544] mb-1 leading-tight"><?= $data['ruangan']; ?></h3>
            <div class="flex items-center gap-1.5 text-gray-600 text-sm">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              <span><?= $data['tanggal']; ?></span>
            </div>
          </div>
          
          <?php
            $statusClass = '';
            if ($data['status'] === 'Menunggu') $statusClass = 'bg-gradient-to-r from-yellow-400 to-yellow-500 text-yellow-900';
            elseif ($data['status'] === 'disetujui' || $data['status'] === 'Disetujui') $statusClass = 'bg-gradient-to-r from-green-400 to-green-500 text-green-900';
            else $statusClass = 'bg-gradient-to-r from-red-400 to-red-500 text-red-900';
          ?>
          <span class="status-badge px-3 py-2 rounded-xl text-xs font-bold uppercase tracking-wide shadow-md <?= $statusClass ?>">
            <?= $data['status']; ?>
          </span>
        </div>

        <div class="border-t-2 border-gray-100 pt-4 space-y-2.5 text-sm mb-5">
          <div class="flex items-start gap-2">
            <svg class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <div><strong class="text-gray-700">Agenda:</strong> <span class="text-gray-600"><?= $data['agenda']; ?></span></div>
          </div>
          <div class="flex items-start gap-2">
            <svg class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div><strong class="text-gray-700">Waktu:</strong> <span class="text-gray-600"><?= $data['waktu']; ?></span></div>
          </div>
          <div class="flex items-start gap-2">
            <svg class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <div><strong class="text-gray-700">Peserta:</strong> <span class="text-gray-600"><?= $data['peserta']; ?></span></div>
          </div>
          <div class="flex items-start gap-2">
            <svg class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            <div><strong class="text-gray-700">Tarif:</strong> <span class="text-gray-600"><?= $data['tarif']; ?></span></div>
          </div>
          <div class="flex items-start gap-2">
            <svg class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div><strong class="text-gray-700">Biaya:</strong> <span class="font-bold text-green-600"><?= $data['biaya']; ?></span></div>
          </div>
          
          <?php if (!empty($data['catatan'])): ?>
            <div class="mt-3 p-3 rounded-lg text-sm border-l-4 <?= $data['status'] == 'Ditolak' ? 'bg-red-50 border-red-400 text-red-800' : 'bg-blue-50 border-blue-400 text-blue-800' ?>">
                <strong class="block mb-1">Catatan Admin:</strong>
                <span class="italic"><?= nl2br(htmlspecialchars($data['catatan'])) ?></span>
            </div>
          <?php endif; ?>

        </div>

        <div class="text-center">
          <?php if ($data['status'] === "Menunggu"): ?>
            <button onclick="openModal('<?= $data['kode']; ?>', <?= $data['id']; ?>)"
              class="btn-cancel bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold px-6 py-3 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 w-full flex items-center justify-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              Batalkan Peminjaman
            </button>
          <?php else: ?>
            <button class="bg-gray-300 text-gray-500 px-6 py-3 rounded-xl shadow w-full flex items-center justify-center gap-2 cursor-not-allowed" disabled>
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              Peminjaman Selesai
            </button>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</main>

<div id="confirmModal" class="hidden fixed inset-0 flex items-center justify-center bg-black/60 modal-backdrop z-50">
  <div class="modal-content glass-effect rounded-3xl shadow-2xl p-8 w-96 mx-4 border-2 border-white/50">
    <div class="text-center">
      <div class="mx-auto mb-5 w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      </div>
      <h3 class="text-2xl font-bold mb-3 text-gray-800">Konfirmasi Pembatalan</h3>
      <p class="text-gray-600 mb-6 leading-relaxed">Apakah Anda yakin ingin membatalkan peminjaman dengan kode <span id="kodeBooking" class="font-bold text-red-600 text-lg block mt-2"></span></p>
      <div class="flex gap-3">
        <button onclick="closeModal()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-6 py-3 rounded-xl transition-all duration-200 shadow hover:shadow-md">Tidak</button>
        <button onclick="confirmCancel()" class="flex-1 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold px-6 py-3 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl">Ya, Batalkan</button>
      </div>
    </div>
  </div>
</div>

<footer class="w-full bg-[#132544] text-white text-center py-3 mt-10">© <?= date('Y'); ?> Institut Teknologi PLN - Sistem Peminjaman Aset</footer>

<script>
const menuBtn = document.getElementById('menuBtn');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');

menuBtn.addEventListener('click', () => {
  sidebar.classList.toggle('-translate-x-full');
  overlay.classList.toggle('hidden');
});

function closeSidebar() {
  sidebar.classList.add('-translate-x-full');
  overlay.classList.add('hidden');
}

function toggleDropdown(id) {
  const submenu = document.getElementById('submenu-' + id);
  const icon = document.getElementById('icon-' + id);
  submenu.classList.toggle('hidden');
  icon.style.transform = submenu.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
}

let currentBooking = '';
let currentId = 0;

// Fungsi openModal menerima 2 parameter: Kode Tampilan & ID Database
function openModal(kode, id) {
  currentBooking = kode;
  currentId = id; // Simpan ID untuk dikirim ke PHP
  document.getElementById('kodeBooking').textContent = kode;
  document.getElementById('confirmModal').classList.remove('hidden');
}

function closeModal() {
  document.getElementById('confirmModal').classList.add('hidden');
}

// Fungsi ini yang mengirim perintah hapus ke PHP
function confirmCancel() {
  window.location.href = 'status.php?action=batal&id=' + currentId;
}

document.getElementById('searchInput').addEventListener('input', e => {
  const keyword = e.target.value.toLowerCase();
  document.querySelectorAll('#peminjamanList > div').forEach(card => {
    card.style.display = card.innerText.toLowerCase().includes(keyword) ? 'block' : 'none';
  });
});
</script>
</body>
</html>