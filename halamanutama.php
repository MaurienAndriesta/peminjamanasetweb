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
            ['title' => 'Ruangan Multiguna', 'url' => 'ruanganmultiguna.php'],
            ['title' => 'Fasilitas', 'url' => 'fasilitas.php'],
            ['title' => 'Usaha', 'url' => 'usaha.php'],
            ['title' => 'Laboratorium', 'url' => 'laboratorium.php']
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - Sistem Peminjaman Aset</title>
    <style>
        * {margin:0;padding:0;box-sizing:border-box;}
        body {font-family: 'Segoe UI', Tahoma, sans-serif;background:#D1E5EA;color:#333;min-height:100vh;}

        
        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: #fff;
            min-height: 100vh;
            position: fixed;
            left: 0; top: 0;
            transform: translateX(-250px);
            transition: transform 0.3s ease;
            z-index: 1000;
        }
        .sidebar.active {transform: translateX(0);}
        .menu-header {background:#1a252f;padding:15px 20px;}
        .menu-title {font-size:14px;font-weight:bold;text-transform:uppercase;letter-spacing:1px;}

       
        .hamburger-btn {
            position: fixed;top: 20px;left: 20px;
            z-index: 1001;
            background: #D9D9D9;color:#fff;
            border: none;padding: 10px 14px;
            cursor: pointer;border-radius: 4px;
            font-size: 18px;transition: background 0.2s;
        }
        .hamburger-btn:hover {background:#1a252f;}
        .hamburger-btn.hidden {display: none;}

        
        .overlay {
            position: fixed;top:0;left:0;width:100%;height:100%;
            background: rgba(0,0,0,0.5);z-index: 999;
            display: none;
        }
        .overlay.active {display:block;}

        
        .menu-list {list-style:none;margin:0;padding:0;}
        .menu-item {border-bottom:1px solid rgba(255,255,255,0.1);}
        .menu-link {
            display:flex;justify-content:space-between;align-items:center;
            padding:12px 20px;color:#fff;text-decoration:none;font-size:14px;
            transition: background 0.2s;
        }
        .menu-link:hover {background:#1a252f;}
        .menu-icon {font-size:12px;margin-left:10px;transition: transform 0.2s;}

        .submenu {background:#34495e;display:none;}
        .submenu-link {
            display:block;padding:10px 40px;
            color:#fff;text-decoration:none;font-size:13px;
            transition: background 0.2s;
        }
        .submenu-link:hover {background:#2c3e50;}

        
        .main-content {margin-left:0;padding:70px 20px 20px 20px;min-height:100vh;}
        .welcome-text {text-align:center;margin-bottom:40px;}
        .welcome-text h2 {color:#2c3e50;margin-bottom:10px;}
        .welcome-text p {color:#555;}

        .card {
            background:#fff;padding:25px;
            border-radius:10px;
            margin:20px auto;
            box-shadow:0 4px 12px rgba(0,0,0,0.1);
            max-width:850px;
            transition: transform 0.2s;
        }
        .card:hover {transform: translateY(-5px);}
        .card h3 {margin-bottom:10px;color:#2c3e50;}
        .card p {line-height:1.6;color:#444;}

        
        footer {
            text-align:center;padding:15px;margin-top:40px;
            background:#2c3e50;color:#fff;
            font-size:14px;
        }
    </style>
</head>
<body>
    
    
    <button class="hamburger-btn" id="hamburgerBtn" onclick="toggleSidebar()">☰</button>

    
    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>

    
    <div class="sidebar" id="sidebar">
        <div class="menu-header"><div class="menu-title">Menu Utama</div></div>
        <ul class="menu-list">
            <?php foreach ($menu_items as $index => $item): ?>
                <li class="menu-item">
                    <?php if ($item['type'] == 'dropdown'): ?>
                        <a href="#" class="menu-link" onclick="toggleDropdown(<?php echo $index; ?>)">
                            <span><?= $item['title']; ?></span>
                            <span class="menu-icon" id="icon-<?= $index; ?>"><?= $item['icon']; ?></span>
                        </a>
                        <ul class="submenu" id="submenu-<?= $index; ?>">
                            <?php foreach ($item['submenu'] as $subitem): ?>
                                <li><a href="<?= $subitem['url']; ?>" class="submenu-link"><?= $subitem['title']; ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <a href="<?= $item['url']; ?>" class="menu-link"><?= $item['title']; ?></a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    
    <div class="main-content">
        <div class="welcome-text">
            <h2>Selamat Datang di Sistem Peminjaman Aset Kampus Institut Teknologi PLN</h2>
            <p>Platform digital untuk memudahkan pengajuan & pengelolaan peminjaman aset kampus</p>
        </div>

        <div class="card">
            <h3>📌 Apa itu Sistem Peminjaman Aset?</h3>
            <p>
                Sistem ini dirancang untuk mempermudah civitas akademika IT-PLN dalam 
                melakukan peminjaman ruangan, laboratorium, fasilitas usaha, maupun 
                aset kampus lainnya secara <b>teratur</b>, <b>transparan</b>, dan <b>efisien</b>.
            </p>
        </div>

        <div class="card">
            <h3>🎯 Tujuan</h3>
            <p>
                ✔ Mempermudah proses peminjaman aset.<br>
                ✔ Menjamin transparansi dan keteraturan penggunaan.<br>
                ✔ Mengurangi konflik jadwal penggunaan fasilitas.<br>
            </p>
        </div>

        <div class="card">
            <h3>⚡ Alur Singkat Peminjaman</h3>
            <p>
                1️⃣ Login atau buat akun.<br>
                2️⃣ Pilih ruangan / fasilitas yang tersedia.<br>
                3️⃣ Ajukan peminjaman dengan jadwal.<br>
                4️⃣ Tunggu konfirmasi admin.<br>
                5️⃣ Gunakan aset sesuai jadwal yang disetujui.<br>
            </p>
        </div>
    </div>

    
    <footer>
        &copy; <?= date("Y"); ?> Institut Teknologi PLN - Sistem Peminjaman Aset
    </footer>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.add('active');
            document.getElementById('overlay').classList.add('active');
            document.getElementById('hamburgerBtn').classList.add('hidden');
        }

        function closeSidebar() {
            document.getElementById('sidebar').classList.remove('active');
            document.getElementById('overlay').classList.remove('active');
            document.getElementById('hamburgerBtn').classList.remove('hidden');
        }

        function toggleDropdown(index) {
            const submenu = document.getElementById('submenu-' + index);
            const icon = document.getElementById('icon-' + index);
            if (submenu.style.display === 'none' || submenu.style.display === '') {
                submenu.style.display = 'block';
                icon.style.transform = 'rotate(90deg)';
            } else {
                submenu.style.display = 'none';
                icon.style.transform = 'rotate(0deg)';
            }
        }

    
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) closeSidebar();
        });
    </script>
</body>
</html>
