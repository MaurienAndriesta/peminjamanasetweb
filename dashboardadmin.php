<?php
// Dummy data
$total_ruangan = 50;
$pemesanan_selesai = 3;
$menunggu_persetujuan = 2;
$pendapatan = 5000000;

$riwayat = [
    ["nama"=>"Ruang Kelas Kapasitas (71 Orang s.d 100 Orang) - Rapat Bersama","pemohon"=>"Tarissa Laksono","tanggal"=>"25 - 26 September 2025","status"=>"Pending"],
    ["nama"=>"Lab. Information Retrieval - Workshop AI","pemohon"=>"Maurien Andriesta","tanggal"=>"20 - 21 September 2025","status"=>"Disetujui"],
    ["nama"=>"LCD Projector - Kebutuhan Rapat","pemohon"=>"Renny Amelia","tanggal"=>"1 - 2 September 2025","status"=>"Ditolak"]
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin Pengelola</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    *{margin:0;padding:0;box-sizing:border-box;}
    body{font-family:'Inter',sans-serif;background:#e3f2f9;display:flex;min-height:100vh;color:#333;}

    /* Sidebar */
    .sidebar{
        width:250px;background:#fff;border-right:1px solid #ccc;
        padding:20px 0;position:fixed;left:0;top:0;bottom:0;
        transition:width .3s;overflow:hidden;
    }
    .sidebar.collapsed{width:60px;}
    .sidebar h2{padding:0 20px;margin-bottom:15px;font-size:14px;text-transform:uppercase;color:#555;transition:opacity .3s;}
    .sidebar.collapsed h2{opacity:0;}
    .menu{list-style:none;}
    .menu li{margin-bottom:5px;}
    .menu a{
        display:flex;align-items:center;
        padding:10px 20px;
        text-decoration:none;color:#333;
        border-radius:10px;transition:all .2s;
        white-space:nowrap;
    }
    .menu a:hover,.menu a.active{background:#1f7a8c;color:#fff;}
    .menu a i{margin-right:10px;transition:margin .3s;}
    .sidebar.collapsed .menu a span{display:none;}
    .sidebar.collapsed .menu a i{margin:0 auto;}

    /* Main */
    .main{margin-left:250px;flex:1;padding:20px;transition:margin-left .3s;}
    .sidebar.collapsed ~ .main{margin-left:60px;}

    .header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;}
    .hamburger{
        background:#1f7a8c;color:#fff;border:none;
        padding:10px 14px;border-radius:6px;cursor:pointer;
    }
    .search-bar{flex:1;margin:0 20px;}
    .search-bar input{width:100%;padding:10px 15px;border-radius:25px;border:1px solid #ccc;}

    /* Statistik */
    .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;margin-bottom:30px;}
    .stat-box{
        padding:20px;border-radius:12px;color:#fff;font-weight:600;
        display:flex;flex-direction:column;justify-content:center;
        transition:transform .2s;
    }
    .stat-box:hover{transform:translateY(-5px);}
    .gray{background:#555;} .green{background:#4CAF50;}
    .yellow{background:#FFC107;} .red{background:#E53935;}
    .stat-box span{font-size:28px;font-weight:700;margin-top:10px;}

    /* Riwayat */
    .riwayat h2{margin-bottom:15px;color:#0077b6;}
    .riwayat-item{
        background:#f1f9ff;padding:15px;border-radius:10px;
        margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;
        transition:transform .2s;
    }
    .riwayat-item:hover{transform:scale(1.01);}
    .riwayat-info{max-width:70%;}
    .riwayat-info strong{display:block;margin-bottom:5px;}
    .status{padding:6px 12px;border-radius:20px;font-size:13px;font-weight:600;border:none;}
    .pending{background:#FFD54F;color:#333;}
    .disetujui{background:#81C784;color:#fff;}
    .ditolak{background:#E57373;color:#fff;}
    .proses{margin-left:10px;color:#0077b6;text-decoration:none;font-weight:600;}
    .proses:hover{text-decoration:underline;}

    /* Responsive */
    @media(max-width:768px){
        .sidebar{position:fixed;z-index:1000;}
        .main{margin-left:60px;}
    }
  </style>
</head>
<body>

<div class="sidebar" id="sidebar">
   <ul class="menu">
    <li>
        <a href="#" class="active">
            <i>🏠</i>
            <span>Dashboard</span>
        </a>
    </li>

    <li class="dropdown">
        <a href="#" onclick="toggleDropdown(event,'sewaMenu')">
            <i>📝</i>
            <span>Data Sewa Ruangan</span>
            <span class="arrow">▼</span>
        </a>
        <ul class="submenu" id="sewaMenu">
            <li><a href="#">Ruangan Multiguna</a></li>
            <li><a href="#">Fasilitas</a></li>
            <li><a href="#">Usaha</a></li>
            <li><a href="#">Laboratorium</a></li>
        </ul>
    </li>

    <li>
        <a href="#">
            <i>📄</i>
            <span>Permintaan</span>
        </a>
    </li>

    <li>
        <a href="#">
            <i>📅</i>
            <span>Jadwal</span>
        </a>
    </li>

    <li class="dropdown">
        <a href="#" onclick="toggleDropdown(event,'laporanMenu')">
            <i>📊</i>
            <span>Laporan</span>
            <span class="arrow">▼</span>
        </a>
        <ul class="submenu" id="laporanMenu">
            <li><a href="#">Penggunaan Ruangan</a></li>
            <li><a href="#">Keuangan</a></li>
        </ul>
    </li>
</ul>
</div>


<!-- Main -->
<div class="main">
    <div class="header">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <div class="search-bar"><input type="text" placeholder="Cari Permintaan..."></div>
        <div class="profile">🔔 <span>Nama Admin Pengelola ⏷</span></div>
    </div>

    <h2>Ringkasan Statistik</h2>
    <div class="stats">
        <div class="stat-box gray">TOTAL RUANGAN IT PLN <span><?= $total_ruangan ?></span></div>
        <div class="stat-box green">PEMESANAN SELESAI <span><?= $pemesanan_selesai ?></span></div>
        <div class="stat-box yellow">MENUNGGU PERSETUJUAN <span><?= $menunggu_persetujuan ?></span></div>
        <div class="stat-box red">PENDAPATAN BULAN INI <span>Rp <?= number_format($pendapatan,0,',','.') ?></span></div>
    </div>


    <div class="riwayat">
        <h2>Riwayat Permintaan</h2>
        <?php foreach($riwayat as $r): ?>
        <div class="riwayat-item">
            <div class="riwayat-info">
                <strong><?= $r["nama"]; ?></strong>
                Pemohon: <?= $r["pemohon"]; ?> | <?= $r["tanggal"]; ?>
            </div>
            <div class="riwayat-actions">
                <?php if($r["status"]=="Pending"): ?>
                    <span class="status pending">Pending</span>
                <?php elseif($r["status"]=="Disetujui"): ?>
                    <span class="status disetujui">Disetujui</span>
                <?php else: ?>
                    <span class="status ditolak">Ditolak</span>
                <?php endif; ?>
                <a href="#" class="proses">Lihat & Proses</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function toggleSidebar(){
    const sidebar = document.getElementById("sidebar");
    sidebar.classList.toggle("collapsed");

    // Tutup semua submenu kalau sidebar di-collapse
    if(sidebar.classList.contains("collapsed")){
        document.querySelectorAll(".submenu").forEach(s => s.style.display = "none");
        document.querySelectorAll(".dropdown").forEach(d => d.classList.remove("open"));
    }
}

function toggleDropdown(event, id) {
    event.preventDefault();
    const submenu = document.getElementById(id);
    const arrow = event.currentTarget.querySelector(".arrow");

    submenu.style.display =
        submenu.style.display === "block" ? "none" : "block";

    // putar panah
    arrow.style.transform =
        submenu.style.display === "block" ? "rotate(180deg)" : "rotate(0deg)";
}

</script>
</body>
</html>
