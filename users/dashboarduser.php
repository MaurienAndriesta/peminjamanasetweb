<?php
session_start();
require_once '../koneksi.php'; 

// ================= 1. CEK LOGIN =================
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = "Pengguna"; 

// ================= 2. AMBIL DATA USER =================
if (isset($koneksi)) {
    $stmt = $koneksi->prepare("SELECT * FROM tbl_user WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $user_name = $row['nama'] ?? $row['username'] ?? $row['fullname'] ?? $row['nama_lengkap'] ?? "Pengguna";
        }
        $stmt->close();
    }
}

// ================= 3. AMBIL PENGATURAN SISTEM =================
$settings = [];
$q_set = mysqli_query($koneksi, "SELECT * FROM tbl_pengaturan");
while ($r = mysqli_fetch_assoc($q_set)) {
    $settings[$r['kunci']] = $r['nilai'];
}

$nama_website = $settings['nama_website'] ?? 'Sistem Peminjaman Aset';
$pengumuman_global = $settings['pengumuman_global'] ?? '';
$email_kontak = $settings['email_kontak'] ?? 'admin@itpln.ac.id';
$no_telepon   = $settings['no_telepon'] ?? '021-1234567';
$alamat       = $settings['alamat'] ?? 'Jl. Lingkar Luar Barat, Jakarta Barat';

// ================= 4. LOGIKA DATA KALENDER =================
$booked_data = []; 
$total_peminjaman_aktif = 0;
$total_menunggu_persetujuan = 0;

if (isset($koneksi)) {
    // A. Statistik User
    $q_stat = $koneksi->prepare("SELECT 
        SUM(CASE WHEN status = 'disetujui' AND tanggal_selesai >= CURDATE() THEN 1 ELSE 0 END) as aktif,
        SUM(CASE WHEN status IN ('pending', 'Menunggu') THEN 1 ELSE 0 END) as menunggu
        FROM tbl_pengajuan WHERE user_id = ?");
    
    if ($q_stat) {
        $q_stat->bind_param("i", $user_id);
        $q_stat->execute();
        $res_stat = $q_stat->get_result();
        if ($d_stat = $res_stat->fetch_assoc()) {
            $total_peminjaman_aktif = $d_stat['aktif'] ?? 0;
            $total_menunggu_persetujuan = $d_stat['menunggu'] ?? 0;
        }
    }

    // B. Kalender
    $q_cal = $koneksi->query("SELECT * FROM tbl_pengajuan WHERE status = 'disetujui'");
    if ($q_cal) {
        while ($row = $q_cal->fetch_assoc()) {
            $tgl_awal = $row['tanggal_peminjaman'] ?? null;
            $tgl_akhir = $row['tanggal_selesai'] ?? $tgl_awal;
            $nama_aset = $row['subpilihan'] ?? 'Aset';
            $agenda = $row['agenda'] ?? 'Digunakan';

            if ($tgl_awal && $tgl_akhir) {
                try {
                    $period = new DatePeriod(
                        new DateTime($tgl_awal),
                        new DateInterval('P1D'),
                        (new DateTime($tgl_akhir))->modify('+1 day')
                    );
                    $ket = "$nama_aset ($agenda)";
                    foreach ($period as $date) {
                        $tgl = $date->format('Y-m-d');
                        if (isset($booked_data[$tgl])) {
                            $booked_data[$tgl] .= ", " . $ket;
                        } else {
                            $booked_data[$tgl] = $ket;
                        }
                    }
                } catch (Exception $e) { }
            }
        }
    }
}
$json_booked_data = json_encode($booked_data);

// ================= MENU NAVIGASI =================
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
                    ['title' => 'FTBE', 'url' => 'labftbe.php'],
                    ['title' => 'FTEN', 'url' => 'labften.php'],
                    ['title' => 'FTIK', 'url' => 'labftik.php'],
                    ['title' => 'FKET', 'url' => 'labfket.php'],
                ]
            ]
        ]
    ],
    ['title' => 'Jadwal Ketersediaan', 'url' => 'jadwaltersedia.php'],
    ['title' => 'Status Peminjaman', 'url' => 'status.php'],
    ['title' => 'Riwayat Peminjaman', 'url' => 'riwayat.php'],
];

function renderMenu($items, $prefix = 'root') {
    echo "<ul class='list-none pl-3 space-y-1'>";
    foreach ($items as $index => $item) {
        $uniqueId = $prefix . '-' . $index;
        if (isset($item['type']) && $item['type'] === 'dropdown') {
            echo "<li>
                <button onclick='toggleDropdown(\"{$uniqueId}\")' class='flex justify-between items-center w-full px-3 py-2 text-left rounded-lg hover:bg-gray-700/70 transition'>
                    <span>{$item['title']}</span> <span id='icon-{$uniqueId}'>▼</span>
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
  <title>Dashboard - <?= htmlspecialchars($nama_website) ?></title>
  
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <style>
    body { font-family: 'Inter', sans-serif !important; }
    .rotate-180 { transform: rotate(180deg); }
    @keyframes slideIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .animate-slide-in { animation: slideIn 0.5s ease-out; }

    /* Custom Scrollbar untuk Sidebar */
.custom-scroll::-webkit-scrollbar {
    width: 6px;
}
.custom-scroll::-webkit-scrollbar-track {
    background: #1f2937; /* gray-800 */
}
.custom-scroll::-webkit-scrollbar-thumb {
    background: #4b5563; /* gray-600 */
    border-radius: 3px;
}
.custom-scroll::-webkit-scrollbar-thumb:hover {
    background: #9ca3af; /* gray-400 */
}
    
    /* CSS KALENDER */
    .calendar-container { background-color: #F8FAFC; border-radius: 1rem; padding: 1.5rem; border: 1px solid #E2E8F0; }
    .cal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
    .nav-btn { width: 2.5rem; height: 2.5rem; background: white; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 2px rgba(0,0,0,0.05); color: #1E3A8A; transition: all 0.2s; cursor: pointer; }
    .nav-btn:hover { background: #EFF6FF; }
    .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; gap: 0.5rem; }
    .cal-day-name { font-size: 0.75rem; font-weight: 700; color: #64748B; text-transform: uppercase; margin-bottom: 0.5rem; }
    .cal-date { height: 2.5rem; width: 2.5rem; margin: 0 auto; display: flex; align-items: center; justify-content: center; border-radius: 0.5rem; font-size: 0.875rem; color: #334155; font-weight: 500; transition: all 0.2s; }
    .is-today { background-color: #1E3A8A; color: white; font-weight: bold; }
    .is-booked { background-color: #EF4444; color: white; cursor: pointer; }
    .is-booked:hover { background-color: #DC2626; transform: scale(1.1); }
    .is-free:hover { background-color: #E2E8F0; cursor: default; }
  </style>
</head>
<body class="bg-gradient-to-br from-[#D1E5EA] via-[#E8F4F8] to-[#D1E5EA] min-h-screen text-gray-800">

<div id="overlay" class="hidden fixed inset-0 bg-black/50 z-40 transition-opacity duration-300" onclick="closeSidebar()"></div>
<div id="sidebar" class="fixed top-0 left-0 w-72 h-full bg-gray-800 text-white transform -translate-x-full transition-transform duration-300 z-50 shadow-2xl flex flex-col">
  <div class="bg-gray-900 px-6 py-5 font-semibold text-center border-b border-gray-700 text-lg uppercase tracking-wide flex-shrink-0">Menu Utama</div>
  <nav class="p-4 overflow-y-auto custom-scroll flex-1"><?php renderMenu($menu_items); ?></nav>
</div>

<div class="fixed top-4 left-4 z-50">
  <button id="menuBtn" onclick="toggleSidebar()" class="bg-gray-800 text-white p-3 rounded-md shadow-md hover:bg-gray-700 active:scale-95 transition-all duration-200">☰</button>
</div>

<main class="ml-0 transition-all duration-300 px-6 md:px-12 pt-24 pb-12">
  <div class="max-w-7xl mx-auto">
    
    <div class="flex justify-between items-start flex-wrap gap-6 mb-8 animate-slide-in">
      <div>
        <h1 class="text-4xl md:text-5xl font-extrabold text-[#1E3A8A] leading-tight mb-2">Selamat Datang 👋</h1>
        <p class="text-lg text-gray-600 font-medium"><?= htmlspecialchars($nama_website); ?></p>
      </div>
      <div class="relative">
        <button id="userBtn" onclick="toggleUserDropdown()" class="bg-white px-6 py-3.5 rounded-xl text-sm font-semibold text-[#1E3A8A] border-2 border-[#132544]/20 hover:border-[#132544] hover:shadow-lg transition-all duration-300 flex items-center gap-2">
          <i class="fas fa-user-circle text-lg"></i> <?= htmlspecialchars($user_name); ?> 
        </button>
        <div id="userDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden z-50">
          <a href="../halamanutama.php" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-[#1E3A8A]/10 transition">
            <i class="fas fa-sign-out-alt"></i> Keluar
          </a>
        </div>
      </div>
    </div>

    <?php if (!empty($pengumuman_global)): ?>
    <div class="bg-indigo-50 border-l-4 border-indigo-500 p-4 rounded-lg mb-8 flex items-start gap-3 animate-slide-in shadow-sm" style="animation-delay: 0.05s">
        <div class="flex-shrink-0 mt-0.5">
            <i class="fas fa-info-circle text-indigo-500 text-lg"></i>
        </div>
        <div class="flex-1">
            <p class="text-sm text-indigo-900 leading-relaxed">
                <span class="font-bold mr-1">Informasi:</span>
                <?= nl2br(htmlspecialchars($pengumuman_global)) ?>
            </p>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
      
      <div class="xl:col-span-2 space-y-8">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl p-8 hover:scale-[1.02] transition-all duration-300 border-l-4 border-[#132544] group cursor-default animate-slide-in">
            <div class="flex items-start justify-between mb-6">
              <div class="flex-1">
                <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Peminjaman Aktif</p>
                <h3 class="text-5xl font-extrabold text-[#1E3A8A] mb-1"><?= $total_peminjaman_aktif; ?></h3>
                <p class="text-sm text-gray-600">Sedang berjalan</p>
              </div>
              <div class="w-16 h-16 flex items-center justify-center rounded-2xl bg-gradient-to-br from-[#132544] to-[#1E3A8A] text-white text-3xl shadow-lg">📋</div>
            </div>
          </div>
          <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl p-8 hover:scale-[1.02] transition-all duration-300 border-l-4 border-yellow-400 group cursor-default animate-slide-in" style="animation-delay: 0.1s">
            <div class="flex items-start justify-between mb-6">
              <div class="flex-1">
                <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Menunggu Persetujuan</p>
                <h3 class="text-5xl font-extrabold text-yellow-500 mb-1"><?= $total_menunggu_persetujuan; ?></h3>
                <p class="text-sm text-gray-600">Menunggu validasi</p>
              </div>
              <div class="w-16 h-16 flex items-center justify-center rounded-2xl bg-gradient-to-br from-yellow-400 to-yellow-500 text-white text-3xl shadow-lg">⏳</div>
            </div>
          </div>
        </div>

        <div class="bg-gradient-to-br from-[#EAF6F8] to-white rounded-2xl shadow-lg p-8 border border-[#132544]/10 animate-slide-in" style="animation-delay: 0.2s">
          <div class="flex items-start gap-4 mb-6">
             <div class="w-12 h-12 bg-yellow-400 rounded-2xl flex items-center justify-center text-white text-2xl shadow-md flex-shrink-0">💡</div>
             <div><h3 class="text-xl font-bold text-[#132544]">Tips Peminjaman</h3><p class="text-gray-500 text-sm mt-1">Panduan untuk peminjaman yang lancar</p></div>
          </div>
          <div class="grid md:grid-cols-2 gap-4">
             <div class="bg-white p-4 rounded-xl flex items-start gap-3 shadow-sm border border-gray-100">
                <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center flex-shrink-0"><i class="fas fa-calendar-alt"></i></div>
                <div><h4 class="font-bold text-gray-800 text-sm">Ajukan Lebih Awal</h4><p class="text-xs text-gray-500 mt-1 leading-relaxed">Minimal 2 hari sebelum tanggal penggunaan</p></div>
             </div>
             <div class="bg-white p-4 rounded-xl flex items-start gap-3 shadow-sm border border-gray-100">
                <div class="w-10 h-10 bg-green-100 text-green-600 rounded-lg flex items-center justify-center flex-shrink-0"><i class="fas fa-check-circle"></i></div>
                <div><h4 class="font-bold text-gray-800 text-sm">Cek Ketersediaan</h4><p class="text-xs text-gray-500 mt-1 leading-relaxed">Pastikan aset tersedia (lihat kalender)</p></div>
             </div>
             <div class="bg-white p-4 rounded-xl flex items-start gap-3 shadow-sm border border-gray-100">
                <div class="w-10 h-10 bg-orange-100 text-orange-600 rounded-lg flex items-center justify-center flex-shrink-0"><i class="fas fa-edit"></i></div>
                <div><h4 class="font-bold text-gray-800 text-sm">Isi Detail Lengkap</h4><p class="text-xs text-gray-500 mt-1 leading-relaxed">Lengkapi form peminjaman dengan jelas</p></div>
             </div>
             <div class="bg-white p-4 rounded-xl flex items-start gap-3 shadow-sm border border-gray-100">
                <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center flex-shrink-0"><i class="fas fa-sync-alt"></i></div>
                <div><h4 class="font-bold text-gray-800 text-sm">Kembalikan Tepat Waktu</h4><p class="text-xs text-gray-500 mt-1 leading-relaxed">Sesuai jadwal yang telah disepakati</p></div>
             </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-emerald-500 animate-slide-in" style="animation-delay: 0.3s">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center text-xl">
                    <i class="fas fa-headset"></i>
                </div>
                <div>
                    <h3 class="font-bold text-lg text-[#1E3A8A]">Pusat Bantuan</h3>
                    <p class="text-xs text-gray-500">Hubungi kami jika ada kendala</p>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2 flex items-start gap-3 bg-gray-50 p-4 rounded-lg border border-gray-100 hover:bg-emerald-50 transition-colors">
                    <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center text-red-500 flex-shrink-0 mt-0.5"><i class="fas fa-map-marker-alt"></i></div>
                    <span class="text-sm text-gray-600 font-medium leading-relaxed"><?= htmlspecialchars($alamat) ?></span>
                </div>
                <div class="flex items-center gap-3 bg-gray-50 p-3 rounded-lg border border-gray-100 hover:bg-emerald-50 transition-colors">
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-500 flex-shrink-0"><i class="fas fa-envelope"></i></div>
                    <span class="text-sm text-gray-600 font-medium truncate"><?= htmlspecialchars($email_kontak) ?></span>
                </div>
                <div class="flex items-center gap-3 bg-gray-50 p-3 rounded-lg border border-gray-100 hover:bg-emerald-50 transition-colors">
                    <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-500 flex-shrink-0"><i class="fas fa-phone"></i></div>
                    <span class="text-sm text-gray-600 font-medium"><?= htmlspecialchars($no_telepon) ?></span>
                </div>
            </div>
        </div>

      </div>

      <div class="bg-white rounded-2xl shadow-lg p-6 border-t-4 border-[#1E3A8A] animate-slide-in h-fit" style="animation-delay: 0.4s">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-10 h-10 bg-blue-100 text-blue-700 rounded-xl flex items-center justify-center text-xl">📅</div>
          <div><h3 class="font-bold text-lg text-[#1E3A8A]">Kalender Ketersediaan</h3><p class="text-xs text-gray-500">Jadwal booking aset</p></div>
        </div>
        <div class="calendar-container">
            <div class="cal-header">
                <button class="nav-btn" onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                <div class="text-[#1E3A8A] font-bold text-lg" id="calendarTitle">Loading...</div>
                <button class="nav-btn" onclick="changeMonth(1)"><i class="fas fa-chevron-right"></i></button>
            </div>
            <div class="cal-grid mb-2">
                <div class="cal-day-name">Min</div><div class="cal-day-name">Sen</div><div class="cal-day-name">Sel</div>
                <div class="cal-day-name">Rab</div><div class="cal-day-name">Kam</div><div class="cal-day-name">Jum</div>
                <div class="cal-day-name">Sab</div>
            </div>
            <div id="calendarGrid" class="cal-grid"></div>
            <div class="flex items-center gap-6 mt-6 pt-4 border-t border-gray-200">
                 <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded-full bg-[#1E3A8A]"></div><span class="text-xs text-gray-600 font-medium">Hari Ini</span>
                 </div>
                 <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded-full bg-[#EF4444]"></div><span class="text-xs text-gray-600 font-medium">Booked</span>
                 </div>
            </div>
        </div>
        <div class="mt-6">
            <h4 class="flex items-center gap-2 font-bold text-[#1E3A8A] text-sm mb-2">
                <i class="fas fa-info-circle"></i> Keterangan Booking
            </h4>
            <div id="bookingInfo" class="text-xs text-gray-400 text-center py-4 bg-gray-50 rounded-lg border border-dashed border-gray-200 transition-all">
                Tidak ada booking bulan ini
            </div>
        </div>
      </div>

    </div>
  </div>
</main>

<footer class="w-full bg-[#132544] text-white text-center py-3 mt-10">
  © <?= date('Y'); ?> Institut Teknologi PLN - Sistem Peminjaman Aset
</footer>

<script>
const bookedData = <?= $json_booked_data; ?>; 
let currDate = new Date();
const monthNames = ["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];

function renderCalendar() {
    const year = currDate.getFullYear();
    const month = currDate.getMonth();
    document.getElementById('calendarTitle').innerText = `${monthNames[month]} ${year}`;
    const grid = document.getElementById('calendarGrid');
    grid.innerHTML = "";
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    for(let i=0; i<firstDay; i++){ grid.appendChild(document.createElement("div")); }
    
    let hasBookingThisMonth = false;
    for(let i=1; i<=daysInMonth; i++){
        const div = document.createElement("div");
        div.innerText = i;
        div.className = "cal-date is-free"; 
        const monthStr = String(month + 1).padStart(2, '0');
        const dayStr = String(i).padStart(2, '0');
        const fullDate = `${year}-${monthStr}-${dayStr}`;
        const today = new Date();
        const isToday = (i === today.getDate() && month === today.getMonth() && year === today.getFullYear());
        
        if (bookedData[fullDate]) {
            div.className = "cal-date is-booked"; 
            div.onclick = function() { showInfo(fullDate); };
            hasBookingThisMonth = true;
        } else if (isToday) {
            div.className = "cal-date is-today"; 
        }
        grid.appendChild(div);
    }
    const infoBox = document.getElementById('bookingInfo');
    if(!hasBookingThisMonth) {
        infoBox.innerHTML = "Tidak ada booking bulan ini";
        infoBox.className = "text-xs text-gray-400 text-center py-4 bg-gray-50 rounded-lg border border-dashed border-gray-200";
    } else {
        infoBox.innerHTML = "Klik tanggal merah untuk detail.";
    }
}

function changeMonth(dir) {
    currDate.setMonth(currDate.getMonth() + dir);
    renderCalendar();
}

function showInfo(dateStr) {
    const infoBox = document.getElementById('bookingInfo');
    const desc = bookedData[dateStr];
    const d = new Date(dateStr);
    const dateIndo = d.getDate() + ' ' + monthNames[d.getMonth()] + ' ' + d.getFullYear();
    infoBox.innerHTML = `<div class="text-left pl-2 border-l-4 border-red-500"><div class="font-bold text-[#1E3A8A] text-sm">${dateIndo}</div><div class="text-gray-700 mt-1">${desc}</div></div>`;
    infoBox.className = "text-xs bg-red-50 p-4 rounded-lg border border-red-100 shadow-sm";
}

document.addEventListener('DOMContentLoaded', renderCalendar);

function toggleSidebar() {
  const sb = document.getElementById('sidebar');
  const ov = document.getElementById('overlay');
  sb.classList.toggle('-translate-x-full');
  if(sb.classList.contains('-translate-x-full')) ov.classList.add('hidden');
  else ov.classList.remove('hidden');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.add('-translate-x-full');
  document.getElementById('overlay').classList.add('hidden');
}
function toggleDropdown(id) {
  document.getElementById('submenu-'+id).classList.toggle('hidden');
  document.getElementById('icon-'+id).classList.toggle('rotate-180');
}
function toggleUserDropdown() {
    document.getElementById('userDropdown').classList.toggle('hidden');
}
document.addEventListener('click', function(e) {
  const btn = document.getElementById('userBtn');
  const drop = document.getElementById('userDropdown');
  if (btn && drop && !btn.contains(e.target) && !drop.contains(e.target)) {
    drop.classList.add('hidden');
  }
});
</script>
</body>
</html>