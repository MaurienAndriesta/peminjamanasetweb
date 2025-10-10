<?php
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

// ================= DATA DUMMY PEMINJAMAN =================
$peminjaman = [
    [
        "kode"     => "BK001",
        "ruangan"  => "Auditorium",
        "tanggal"  => "16 September 2025",
        "tarif"    => "Internal",
        "biaya"    => "Rp. 25.250.000",
        "agenda"   => "Seminar Nasional",
        "waktu"    => "08:00 - 11:00",
        "peserta"  => "100 Orang",
        "kategori" => "Ruang Multiguna",
        "status"   => "Menunggu"
    ],
    [
        "kode"     => "BK002",
        "ruangan"  => "Laboratorium Intelligent Computing",
        "tanggal"  => "18 September 2025",
        "tarif"    => "Ruangan",
        "biaya"    => "Rp. 4.500.000",
        "agenda"   => "Praktikum Machine Learning",
        "waktu"    => "08:00 - 14:00",
        "peserta"  => "50 Orang",
        "kategori" => "Laboratorium",
        "status"   => "Disetujui"
    ],
    [
        "kode"     => "BK003",
        "ruangan"  => "Ruang Presentasi",
        "tanggal"  => "28 September 2025",
        "tarif"    => "Eksternal",
        "biaya"    => "Rp. 5.700.000",
        "agenda"   => "Workshop Nasional",
        "waktu"    => "13:00 - 16:00",
        "peserta"  => "120 Orang",
        "kategori" => "Ruang Multiguna",
        "status"   => "Ditolak"
    ],
];

// ================= FUNGSI RENDER MENU =================
function renderMenu($items, $prefix = 'root') {
    echo "<ul class='list-none pl-3 space-y-1'>";
    foreach ($items as $index => $item) {
        $uniqueId = $prefix . '-' . $index;

        if (isset($item['type']) && $item['type'] === 'dropdown') {
            echo "
            <li>
                <button onclick='toggleDropdown(\"{$uniqueId}\")'
                    class='flex justify-between items-center w-full px-3 py-2 text-left rounded hover:bg-gray-700 transition-all duration-200'>
                    <span>{$item['title']}</span>
                    <span id='icon-{$uniqueId}'>▼</span>
                </button>
                <div class='hidden pl-4 transition-all duration-300' id='submenu-{$uniqueId}'>";
                    renderMenu($item['submenu'], $uniqueId);
            echo "</div>
            </li>";
        } else {
            echo "
            <li>
                <a href='{$item['url']}' class='block px-3 py-2 rounded hover:bg-gray-700 transition-all duration-200'>
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
<title>Status Peminjaman</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    /* Fade-in animasi untuk kartu */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(8px) scale(0.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .fade-in {
        animation: fadeIn 0.25s ease-in-out forwards;
    }

    /* Highlight hasil pencarian */
    mark {
        background-color: #fde68a;
        color: #1f2937;
        padding: 0 2px;
        border-radius: 2px;
    }

    /* Smooth sidebar transition */
    #sidebar {
        transition: transform 0.35s ease-in-out;
    }

    /* Hover efek interaktif */
    .card-hover:hover {
        transform: scale(1.02);
        box-shadow: 0 10px 18px rgba(0,0,0,0.12);
    }

    .transition-all {
        transition: all 0.3s ease;
    }
</style>
</head>
<body class="bg-[#D1E5EA] min-h-screen font-sans text-gray-800">

<!-- Overlay -->
<div id="overlay"
     class="hidden fixed inset-0 bg-black bg-opacity-50 z-40 transition-opacity duration-300"
     onclick="closeSidebar()"></div>

<!-- Sidebar -->
<div id="sidebar" class="fixed top-0 left-0 w-72 h-full bg-gray-800 text-white text-base transform -translate-x-full transition-transform duration-300 z-50 shadow-xl">
  <div class="bg-gray-900 px-6 py-5 font-bold uppercase tracking-widest text-center border-b border-gray-700 text-lg">
    Menu Utama
  </div>
  <nav class="p-3 space-y-1">
    <?php renderMenu($menu_items); ?>
  </nav>
</div>


<!-- Header -->
<header class="bg-[#132544] text-white shadow-md relative">
  <div class="absolute top-1/2 left-6 -translate-y-1/2">
    <button id="menuBtn"
            class="p-2 bg-gray-800 text-white rounded-md shadow hover:bg-gray-700 transition">
      ☰
    </button>
  </div>

  <div class="flex flex-col items-center justify-center py-6">
    <h1 class="text-2xl font-extrabold tracking-wide">STATUS PEMINJAMAN ASET</h1>
    <p class="text-sm opacity-90">Sistem Manajemen Aset Kampus ITPLN</p>
  </div>
</header>

<!-- Content -->
<div class="container mx-auto p-6">
    <h2 class="text-xl font-bold mb-6 text-center">PEMINJAMAN SAYA</h2>

    <!-- Search -->
    <div class="mb-8 flex justify-center">
        <input id="searchInput"
               type="text"
               placeholder="Cari peminjaman..."
               class="w-full sm:w-1/2 px-4 py-2 border border-gray-300 rounded-full shadow focus:ring-2 focus:ring-blue-400 focus:outline-none text-center transition-all">
    </div>

    <h3 class="text-lg font-semibold mb-4 border-b-2 border-gray-300 pb-2 text-gray-700">Daftar Peminjaman</h3>

    <div id="peminjamanList">
        <?php foreach ($peminjaman as $i => $data): ?>
            <div class="fade-in bg-white rounded-2xl shadow-md mb-6 p-6 transition card-hover" style="animation-delay: <?= $i * 0.1 ?>s">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm text-gray-500">Kode Booking</p>
                        <p class="text-sm font-semibold text-gray-700"><?= $data['kode']; ?></p>
                        <p class="text-xl font-bold text-[#1E3A8A]"><?= $data['ruangan']; ?></p>
                        <p class="text-gray-700"><?= $data['tanggal']; ?></p>
                        <p class="text-gray-700">Tarif Sewa: <?= $data['tarif']; ?></p>
                        <p class="text-gray-700">Biaya: <?= $data['biaya']; ?></p>
                    </div>

                    <div class="font-bold px-3 py-1 rounded-full text-sm shadow-sm 
                        <?php if ($data['status'] === 'Menunggu') echo 'bg-yellow-100 text-yellow-700';
                              elseif ($data['status'] === 'Disetujui') echo 'bg-green-100 text-green-700';
                              else echo 'bg-red-100 text-red-700'; ?>">
                        <?php if ($data['status'] === 'Menunggu'): ?>
                            ⏳ Menunggu
                        <?php elseif ($data['status'] === 'Disetujui'): ?>
                            ✅ Disetujui
                        <?php else: ?>
                            ❌ Ditolak
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 text-sm mb-4 transition-all hover:border-gray-400">
                    <p><strong>Agenda:</strong> <?= $data['agenda']; ?></p>
                    <p><strong>Waktu:</strong> <?= $data['waktu']; ?></p>
                    <p><strong>Peserta:</strong> <?= $data['peserta']; ?></p>
                    <p><strong>Kategori:</strong> <?= $data['kategori']; ?></p>
                </div>

                <div class="text-center">
                    <?php if ($data['status'] === "Menunggu" || $data['status'] === "Disetujui"): ?>
                        <button onclick="openModal('<?= $data['kode']; ?>')"
                            class="bg-red-600 hover:bg-red-700 text-white font-semibold px-8 py-2 rounded-full shadow transition-all">
                            🚫 Batalkan Peminjaman
                        </button>
                    <?php else: ?>
                        <button class="bg-gray-400 text-white px-8 py-2 rounded-full shadow" disabled>SELESAI</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- MODAL -->
<div id="confirmModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 transition-all">
    <div class="bg-white rounded-2xl shadow-lg p-6 w-80 text-center fade-in">
        <h3 class="text-lg font-semibold mb-2 text-gray-800">Konfirmasi Pembatalan</h3>
        <p class="text-gray-600 mb-6">
            Apakah Anda yakin ingin membatalkan peminjaman
            <span id="kodeBooking" class="font-semibold text-red-600"></span>?
        </p>
        <div class="flex justify-around">
            <button onclick="closeModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold px-4 py-2 rounded-lg">
                Tidak
            </button>
            <button onclick="confirmCancel()" class="bg-red-600 hover:bg-red-700 text-white font-semibold px-4 py-2 rounded-lg">
                Ya, Batalkan
            </button>
        </div>
    </div>
</div>

<script>
const menuBtn = document.getElementById('menuBtn');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');

menuBtn.addEventListener('click', () => {
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
    overlay.classList.toggle('opacity-100');
});

function closeSidebar() {
    sidebar.classList.add('-translate-x-full');
    overlay.classList.add('hidden');
}

function toggleDropdown(id) {
    const submenu = document.getElementById('submenu-' + id);
    const icon = document.getElementById('icon-' + id);
    submenu.classList.toggle('hidden');
    icon.textContent = submenu.classList.contains('hidden') ? '▼' : '▲';
}

// Modal
let currentBooking = '';
function openModal(kode) {
    currentBooking = kode;
    document.getElementById('kodeBooking').textContent = kode;
    document.getElementById('confirmModal').classList.remove('hidden');
}
function closeModal() {
    document.getElementById('confirmModal').classList.add('hidden');
}
function confirmCancel() {
    alert('Peminjaman dengan kode ' + currentBooking + ' berhasil dibatalkan.');
    closeModal();
}

// 🔍 Pencarian real-time + highlight
const searchInput = document.getElementById('searchInput');
const peminjamanList = document.getElementById('peminjamanList');
const cards = peminjamanList.children;

searchInput.addEventListener('input', () => {
    const keyword = searchInput.value.toLowerCase();

    Array.from(cards).forEach(card => {
        const text = card.innerText.toLowerCase();
        if (text.includes(keyword)) {
            card.style.display = 'block';
            const regex = new RegExp(`(${keyword})`, 'gi');
            card.innerHTML = card.innerHTML.replace(/<mark>|<\/mark>/g, '');
            if (keyword) card.innerHTML = card.innerHTML.replace(regex, '<mark>$1</mark>');
        } else {
            card.style.display = 'none';
        }
    });
});
</script>
</body>
</html>
