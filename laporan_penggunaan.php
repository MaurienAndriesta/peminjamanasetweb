<?php
require_once 'config/database.php';
$db = new Database();

// Ambil data pemesanan yang disetujui
$db->query("SELECT nama_ruang, 
                    TIMESTAMPDIFF(HOUR, tanggal_mulai, tanggal_selesai) as durasi,
                    status
             FROM pemesanan
             WHERE status = 'disetujui'");
$rows = $db->resultSet();

// Hitung laporan per ruang
$laporan = [];
foreach ($rows as $row) {
    $ruang = $row['nama_ruang'];
    $durasi = (int)($row['durasi'] ?? 0); 
    if (!isset($laporan[$ruang])) {
        $laporan[$ruang] = ["nama"=>$ruang,"jam"=>0,"pemesanan"=>0];
    }
    $laporan[$ruang]["jam"] += $durasi;
    $laporan[$ruang]["pemesanan"]++;
}

// Hitung total kapasitas jam sebulan (30 hari × 24 jam)
$totalKapasitasBulan = 30 * 24;

foreach ($laporan as &$r) {
    $r["okupansi"] = $totalKapasitasBulan > 0 ? round(($r["jam"] / $totalKapasitasBulan) * 100) : 0;
}
unset($r);

// Statistik keseluruhan
$totalJam = array_sum(array_column($laporan, "jam"));
$totalPemesanan = array_sum(array_column($laporan, "pemesanan"));
$avgOkupansi = $laporan ? round(array_sum(array_column($laporan, "okupansi")) / count($laporan)) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Penggunaan Ruangan</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body {
  font-family: 'Poppins', sans-serif;
}
.text-dark-accent {
    color: #202938;
}

@media (min-width: 1024px) {
    .main { margin-left: 4rem; }
    .main.lg\:ml-60 { margin-left: 15rem; }
}

@media (max-width: 1023px) {
    .main { margin-left: 0 !important; }
}

#toggleBtn {
    position: relative;
    z-index: 51 !important;
}

.stat-card {
    transition: all 0.3s ease;
}
.stat-card:hover {
    transform: translateY(-5px);
}
</style>
</head>
<body class="bg-blue-100 min-h-screen">

<?php include 'sidebar_admin.php'; ?>

<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

<div id="mainContent" class="main flex-1 p-3 sm:p-5 transition-all duration-300 lg:ml-16 min-h-screen">
    
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
                <span class="text-2xl">📊</span>
                <span>Laporan Penggunaan Ruangan</span>
            </h1>
            <p class="text-xs sm:text-sm text-gray-600 mt-1">Statistik dan analisis penggunaan ruangan</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 mb-6 sm:mb-8">
            
            <div class="stat-card relative rounded-xl p-4 sm:p-6 bg-gradient-to-br from-blue-50 to-blue-200 shadow-md">
                <div class="flex items-center justify-between">
                    <div class="stat-icon text-3xl sm:text-4xl text-blue-700">📊</div>
                    <div class="stat-info text-right">
                        <h3 class="text-xs sm:text-sm font-semibold text-gray-600 mb-1">Okupansi Rata-rata</h3>
                        <div class="text-2xl sm:text-3xl font-extrabold text-blue-800"><?= $avgOkupansi ?>%</div>
                    </div>
                </div>
            </div>

            <div class="stat-card relative rounded-xl p-4 sm:p-6 bg-gradient-to-br from-green-50 to-green-200 shadow-md">
                <div class="flex items-center justify-between">
                    <div class="stat-icon text-3xl sm:text-4xl text-green-700">⏱️</div>
                    <div class="stat-info text-right">
                        <h3 class="text-xs sm:text-sm font-semibold text-gray-600 mb-1">Total Jam Penggunaan</h3>
                        <div class="text-2xl sm:text-3xl font-extrabold text-green-800"><?= $totalJam ?> Jam</div>
                    </div>
                </div>
            </div>

            <div class="stat-card relative rounded-xl p-4 sm:p-6 bg-gradient-to-br from-yellow-50 to-yellow-200 shadow-md">
                <div class="flex items-center justify-between">
                    <div class="stat-icon text-3xl sm:text-4xl text-amber-700">📅</div>
                    <div class="stat-info text-right">
                        <h3 class="text-xs sm:text-sm font-semibold text-gray-600 mb-1">Total Pemesanan</h3>
                        <div class="text-2xl sm:text-3xl font-extrabold text-amber-800"><?= $totalPemesanan ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table & Chart -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
            
            <!-- Table - Desktop: 2 cols, Mobile: Full width on top -->
            <div class="table-container lg:col-span-2 bg-white p-4 sm:p-6 rounded-xl shadow-lg border border-gray-100 order-2 lg:order-1">
                <h2 class="text-base sm:text-xl font-bold mb-3 sm:mb-4 text-dark-accent flex items-center gap-2">
                    <span>📄</span>
                    <span>Detail Penggunaan Ruangan</span>
                </h2>
                
                <!-- Desktop Table -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-700 text-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">No</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Nama Ruangan</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Total Jam</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Okupansi</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Pemesanan</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php $i=1; foreach($laporan as $row): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-700"><?= $i++ ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-800"><?= htmlspecialchars($row['nama']) ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm"><?= $row['jam'] ?> jam</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                                        <?= $row['okupansi'] ?>%
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm"><?= $row['pemesanan'] ?>x</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="lg:hidden space-y-3">
                    <?php $i=1; foreach($laporan as $row): ?>
                    <div class="bg-gray-50 border-2 border-gray-200 rounded-xl p-3 hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex-1">
                                <div class="text-xs text-gray-500 mb-1">No. <?= $i++ ?></div>
                                <h3 class="font-bold text-sm text-gray-900"><?= htmlspecialchars($row['nama']) ?></h3>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                                <?= $row['okupansi'] ?>%
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="bg-white p-2 rounded-lg">
                                <div class="text-gray-600 mb-1">Total Jam</div>
                                <div class="font-bold text-green-600"><?= $row['jam'] ?> jam</div>
                            </div>
                            <div class="bg-white p-2 rounded-lg">
                                <div class="text-gray-600 mb-1">Pemesanan</div>
                                <div class="font-bold text-amber-600"><?= $row['pemesanan'] ?>x</div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Chart -->
            <div class="chart-container lg:col-span-1 bg-white p-4 sm:p-6 rounded-xl shadow-lg border border-gray-100 order-1 lg:order-2">
                <h2 class="text-base sm:text-xl font-bold mb-3 sm:mb-4 text-dark-accent flex items-center gap-2">
                    <span>🏆</span>
                    <span>Visualisasi Okupansi</span>
                </h2>
                
                <div class="relative h-64 sm:h-80">
                    <canvas id="usageChart"></canvas>
                </div>

                <!-- Legend Mobile -->
                <div class="mt-4 lg:hidden">
                    <div class="text-xs font-semibold text-gray-600 mb-2">Keterangan:</div>
                    <div class="grid grid-cols-2 gap-2">
                        <?php 
                        $colors = ['#f59e0b','#10b981','#6366f1','#06b6d4','#f43f5e','#a855f7','#facc15','#94a3b8'];
                        $idx = 0;
                        foreach($laporan as $row): 
                            $color = $colors[$idx % count($colors)];
                        ?>
                        <div class="flex items-center gap-1.5 text-xs">
                            <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: <?= $color ?>"></span>
                            <span class="truncate"><?= htmlspecialchars($row['nama']) ?></span>
                        </div>
                        <?php $idx++; endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
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
        overlay.classList.toggle('hidden');
    }

    const is_expanded = sidebar.classList.contains('lg:w-60') || sidebar.classList.contains('translate-x-0');
    
    if (typeof updateSidebarVisibility === 'function') {
        updateSidebarVisibility(is_expanded);
    }
    
    localStorage.setItem('sidebarStatus', is_expanded ? 'open' : 'collapsed');
}

document.addEventListener("DOMContentLoaded", function() {
    const sidebar = document.getElementById("sidebar");
    const main = document.getElementById("mainContent");
    const overlay = document.getElementById("sidebarOverlay");
    const status = localStorage.getItem('sidebarStatus');
    const is_desktop = window.innerWidth >= 1024;

    if (status === 'open') {
        if (is_desktop) {
            main.classList.add('lg:ml-60');
            main.classList.remove('lg:ml-16');
            sidebar.classList.add('lg:w-60');
            sidebar.classList.remove('lg:w-16');
        } else {
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
            overlay.classList.remove('hidden');
        }
    } else {
        if (is_desktop) {
            main.classList.remove('lg:ml-60');
            main.classList.add('lg:ml-16');
            sidebar.classList.remove('lg:w-60');
            sidebar.classList.add('lg:w-16');
        } else {
            sidebar.classList.remove('translate-x-0');
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }
    }

    if (overlay) {
        overlay.addEventListener('click', toggleSidebar);
    }

    // Chart.js Initialization
    const ctx = document.getElementById('usageChart').getContext('2d');
    const chartLabels = <?= json_encode(array_column($laporan, "nama")) ?>;
    const chartData = <?= json_encode(array_column($laporan, "okupansi")) ?>;

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: chartLabels,
            datasets: [{
                data: chartData,
                backgroundColor: [
                    '#f59e0b','#10b981','#6366f1','#06b6d4',
                    '#f43f5e','#a855f7','#facc15','#94a3b8'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { 
                    position: 'bottom',
                    display: window.innerWidth >= 1024,
                    labels: { 
                        padding: 10,
                        font: { size: window.innerWidth < 640 ? 10 : 12 }
                    }
                },
                tooltip: { 
                    callbacks: { 
                        label: ctx => ctx.label + ': ' + ctx.formattedValue + '%' 
                    }
                }
            },
            animation: { animateScale: true }
        }
    });
});
</script>
</body>
</html>