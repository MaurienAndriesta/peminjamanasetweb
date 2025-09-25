<?php
session_start();
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = "NAMA PENGGUNA"; 
}

$menu_items = [
    [
        'title' => 'Daftar Ruangan & Fasilitas',
        'type' => 'dropdown',
        'submenu' => [
            ['title' => 'Ruangan Multiguna', 'url' => 'ruanganmultiguna.php'],
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
    ['title' => 'Jadwal Ketersediaan', 'url'  => 'jadwaltersedia.php'],
    ['title' => 'Status Peminjaman', 'url'  => 'status.php'],
    ['title' => 'Riwayat Peminjaman', 'url'  => 'riwayat.php'],
];

// fungsi render menu rekursif
function renderMenu($items, $prefix = 'root') {
    echo "<ul class='menu-list'>";
    foreach ($items as $index => $item) {
        $uniqueId = $prefix . '-' . $index;
        echo "<li class='menu-item'>";
        if (isset($item['type']) && $item['type'] === 'dropdown') {
            echo "<a href='#' class='menu-link' onclick='event.preventDefault(); toggleDropdown(\"{$uniqueId}\");'>";
            echo "<span>{$item['title']}</span>";
            echo "<span class='menu-icon' id='icon-{$uniqueId}'>▼</span>";
            echo "</a>";
            echo "<ul class='submenu' id='submenu-{$uniqueId}'>";
            renderMenu($item['submenu'], $uniqueId);
            echo "</ul>";
        } else {
            echo "<a href='{$item['url']}' class='menu-link'>{$item['title']}</a>";
        }
        echo "</li>";
    }
    echo "</ul>";
}

$user_name = $_SESSION['user'];

// contoh data kalender
$months = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];
$total_peminjaman_aktif = 12;
$total_menunggu_persetujuan = 3;
$booked_dates = [22, 29];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Peminjaman Aset Kampus - Institut Teknologi PLN</title>
    <link rel="stylesheet" href="dashboarduser.css">
</head>
<body>
    <div class="container">
        <!-- Hamburger Button -->
        <button class="hamburger-btn" id="hamburgerBtn" onclick="toggleSidebar()">☰</button>
        
        <!-- Overlay -->
        <div class="overlay" id="overlay" onclick="closeSidebar()"></div>
        
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="menu-header">
                <div class="menu-title">Menu Utama</div>
            </div>
            <?php renderMenu($menu_items); ?>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="main-container">
                <div class="header">
                    <h1>Selamat Datang di Sistem Peminjaman Aset Kampus<br>Institut Teknologi PLN !</h1>
                    <div class="user-info">
                        <div class="user-name"><?= htmlspecialchars($user_name); ?></div>
                        <a href="halaman utama.php">Keluar</a>
                    </div>
                </div>

                <div class="content-grid">
                    <div class="left-side">
                        <div class="cards-group">
                            <!-- Active Loans Card -->
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-icon active-loans">📋</div>
                                    <div>
                                        <div class="card-title">Peminjaman Aktif</div>
                                        <div class="card-subtitle">Total Peminjaman Aktif</div>
                                    </div>
                                </div>
                                <div style="font-size: 32px; font-weight: bold; color: #667eea;">
                                    <?= $total_peminjaman_aktif; ?>
                                </div>
                            </div>

                            <!-- Pending Approval Card -->
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-icon pending-approval">⏳</div>
                                    <div>
                                        <div class="card-title">Menunggu Persetujuan</div>
                                        <div class="card-subtitle">Total Menunggu Persetujuan</div>
                                    </div>
                                </div>
                                <div style="font-size: 32px; font-weight: bold; color: #ffa500;">
                                    <?= $total_menunggu_persetujuan; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Tips Section -->
                        <div class="card tips">
                            <div class="tips-header">
                                <span style="font-size: 24px;">💡</span>
                                <span style="font-weight: 600; font-size: 18px;">Tips Peminjaman</span>
                            </div>
                            <ul>
                                <li>Ajukan peminjaman minimal 2 hari sebelum tanggal penggunaan</li>
                                <li>Pastikan mengecek ketersediaan aset terlebih dahulu</li>
                                <li>Lengkapi form peminjaman dengan detail yang jelas</li>
                                <li>Kembalikan aset sesuai jadwal yang telah disepakati</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Calendar Sidebar -->
                    <div class="right-side">
                        <div class="calendar">
                            <div class="calendar-header">
                                <div class="calendar-title">Kalender Ketersediaan Aset</div>
                                <div class="calendar-nav">
                                    <button class="nav-btn">‹</button>
                                    <button class="nav-btn">›</button>
                                </div>
                            </div>
                            
                            <div style="text-align: center; margin-bottom: 20px; font-weight: 600;">
                                <?= $months[9] . ' 2025'; ?> <!-- September 2025 -->
                            </div>

                            <div class="calendar-grid">
                                <!-- Day headers -->
                                <div class="calendar-day-header">Sun</div>
                                <div class="calendar-day-header">Mon</div>
                                <div class="calendar-day-header">Tue</div>
                                <div class="calendar-day-header">Wed</div>
                                <div class="calendar-day-header">Thu</div>
                                <div class="calendar-day-header">Fri</div>
                                <div class="calendar-day-header">Sat</div>

                                <?php
                                // Generate calendar days for September 2025
                                $first_day = mktime(0, 0, 0, 9, 1, 2025);
                                $first_day_week = date('w', $first_day);
                                $days_in_month = date('t', $first_day);
                                
                                // Previous month days
                                for ($i = 0; $i < $first_day_week; $i++) {
                                    $prev_day = 31 - ($first_day_week - 1 - $i);
                                    echo '<div class="calendar-day other-month">' . $prev_day . '</div>';
                                }
                                
                                // Current month days
                                for ($day = 1; $day <= $days_in_month; $day++) {
                                    $class = 'calendar-day';
                                    if ($day == 25) {
                                        $class .= ' current'; // Tanggal sekarang
                                    } elseif (in_array($day, $booked_dates)) {
                                        $class .= ' booked';
                                    }
                                    echo '<div class="' . $class . '">' . $day . '</div>';
                                }
                                
                                // Next month filler days
                                $total_cells = ceil(($days_in_month + $first_day_week) / 7) * 7;
                                $remaining_cells = $total_cells - ($days_in_month + $first_day_week);
                                for ($i = 1; $i <= $remaining_cells; $i++) {
                                    echo '<div class="calendar-day other-month">' . $i . '</div>';
                                }
                                ?>
                            </div>

                            <div class="calendar-legend">
                                <div class="legend-item">
                                    <div class="legend-color legend-booked"></div>
                                    <span>Booked</span>
                                </div>
                                <button style="width: 100%; padding: 12px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; margin-top: 15px; font-weight: 600;">
                                    Lihat Detail
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer style="background: #2c3e50; color: #fff; text-align: center; padding: 10px 0; margin-top: 40px;">
        © <?= date('Y'); ?> Institut Teknologi PLN - Sistem Peminjaman Aset
    </footer>

    <script>
      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('overlay');
      const hamburgerBtn = document.getElementById('hamburgerBtn');

      function toggleSidebar() {
          sidebar.classList.toggle('active');
          overlay.classList.toggle('active');
          hamburgerBtn.classList.toggle('active');
      }

      function closeSidebar() {
          sidebar.classList.remove('active');
          overlay.classList.remove('active');
          hamburgerBtn.classList.remove('active');
      }

      function toggleDropdown(id) {
          const submenu = document.getElementById("submenu-" + id);
          const icon = document.getElementById("icon-" + id);
          if (!submenu) return;

          const parentLi = submenu.closest("li");
          const parentUl = parentLi ? parentLi.parentElement : null;

          if (parentUl) {
              const siblingSubmenus = parentUl.querySelectorAll(":scope > .menu-item > .submenu");
              const siblingIcons = parentUl.querySelectorAll(":scope > .menu-item > a > .menu-icon");

              siblingSubmenus.forEach(menu => {
                  if (menu !== submenu) menu.classList.remove("active");
              });

              siblingIcons.forEach(iconEl => {
                  if (iconEl !== icon) iconEl.style.transform = "rotate(0deg)";
              });
          }

          submenu.classList.toggle("active");
          if (submenu.classList.contains("active")) {
              if (icon) icon.style.transform = "rotate(180deg)";
          } else {
              if (icon) icon.style.transform = "rotate(0deg)";
          }
      }
    </script>
</body>
</html>
