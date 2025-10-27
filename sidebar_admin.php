<?php
// sidebar_admin.php
// PENTING: Pastikan tidak ada karakter atau spasi di luar tag PHP di baris ini.

$current_page = basename($_SERVER['PHP_SELF']);

// Halaman terkait Data Sewa Ruangan
$is_dataruang_active = in_array($current_page, [
    'dataruangmultiguna_admin.php',
    'tambahdatamultiguna_admin.php',
    'editruangmultiguna_admin.php',
    'detailmultiguna_admin.php',
    'datafasilitas_admin.php',
    'tambahdatafasilitas_admin.php',
    'editfasilitas_admin.php',
    'detailfasilitas_admin.php',
    'datausaha_admin.php',
    'tambahdatausaha_admin.php',
    'editdatausaha_admin.php',
    'detailusaha_admin.php',
    'datalaboratorium_admin.php',
    'tambahlaboratorium_admin.php',
    'editlaboratorium_admin.php',
    'detaillaboratorium_admin.php'
]);

// Halaman terkait Laporan
$is_laporan_active = in_array($current_page, [
    'laporan_penggunaan.php',
    'laporan_keuangan.php'
]);

// Tentukan dropdown yang terbuka
$is_dataruang_open = $is_dataruang_active;
$is_laporan_open = $is_laporan_active;
?>

<div
    id="sidebar"
    class="fixed top-0 left-0 h-screen bg-[#202938] shadow-xl w-60 lg:w-16 transition-all duration-300 overflow-x-hidden z-50 text-gray-200 transform -translate-x-full lg:translate-x-0"
>
    <!-- Header -->
    <div class="flex items-center h-16 border-b border-gray-700 px-4 py-3 bg-[#202938] text-white">
        <span class="text-2xl font-bold text-amber-500">🏛️</span>
        <span class="ml-3 text-lg font-semibold whitespace-nowrap sidebar-text hidden">Admin Pengelola</span>
    </div>

    <!-- Menu -->
    <ul class="menu flex flex-col p-2 space-y-1">

        <!-- Dashboard -->
        <li>
            <a href="dashboardadmin.php"
                class="flex items-center px-2 py-3 rounded-xl hover:bg-gray-700 transition-colors
                <?= ($current_page == 'dashboardadmin.php') ? 'bg-amber-500 text-gray-900 font-semibold' : 'text-gray-200' ?>">
                <span class="text-xl w-6 text-center">🏠</span>
                <span class="ml-3 text-sm sidebar-text hidden flex-grow">Dashboard</span>
            </a>
        </li>

        <!-- Data Sewa Ruangan -->
        <li class="dropdown <?= $is_dataruang_open ? 'open' : '' ?>">
            <a href="#"
                class="flex items-center px-2 py-3 rounded-xl hover:bg-gray-700 cursor-pointer transition-colors
                <?= $is_dataruang_active ? 'bg-amber-500 text-gray-900 font-semibold' : 'text-gray-200' ?>"
                onclick="toggleDropdown(event)">
                <span class="flex items-center text-xl w-6 text-center">📝</span>
                <span class="ml-3 text-sm sidebar-text hidden flex-grow">Data Sewa Ruangan</span>
                <span class="arrow ml-auto transition-transform duration-300
                    <?= $is_dataruang_open ? 'rotate-180' : 'rotate-0' ?> sidebar-text-arrow hidden">▼</span>
            </a>

            <ul class="submenu flex flex-col pl-5 ml-1 mt-1 py-1 bg-[#1a2330] rounded-lg <?= $is_dataruang_open ? '' : 'hidden' ?>">
                <li>
                    <a href="dataruangmultiguna_admin.php"
                        class="block px-3 py-2 text-sm rounded-lg hover:bg-gray-700
                        <?= in_array($current_page, ['dataruangmultiguna_admin.php', 'tambahdatamultiguna_admin.php', 'editruangmultiguna_admin.php', 'detailmultiguna_admin.php'])
                            ? 'bg-amber-600 font-semibold text-white' : 'text-gray-300' ?>">
                        Ruangan Multiguna
                    </a>
                </li>
                <li>
                    <a href="datafasilitas_admin.php"
                        class="block px-3 py-2 text-sm rounded-lg hover:bg-gray-700
                        <?= in_array($current_page, ['datafasilitas_admin.php', 'tambahdatafasilitas_admin.php', 'editfasilitas_admin.php', 'detailfasilitas_admin.php'])
                            ? 'bg-amber-600 font-semibold text-white' : 'text-gray-300' ?>">
                        Fasilitas
                    </a>
                </li>
                <li>
                    <a href="datausaha_admin.php"
                        class="block px-3 py-2 text-sm rounded-lg hover:bg-gray-700
                        <?= in_array($current_page, ['datausaha_admin.php', 'tambahdatausaha_admin.php', 'editdatausaha_admin.php', 'detailusaha_admin.php'])
                            ? 'bg-amber-600 font-semibold text-white' : 'text-gray-300' ?>">
                        Usaha
                    </a>
                </li>
                <li>
                    <a href="datalaboratorium_admin.php"
                        class="block px-3 py-2 text-sm rounded-lg hover:bg-gray-700
                        <?= in_array($current_page, ['datalaboratorium_admin.php', 'tambahlaboratorium_admin.php', 'editlaboratorium_admin.php', 'detaillaboratorium_admin.php'])
                            ? 'bg-amber-600 font-semibold text-white' : 'text-gray-300' ?>">
                        Laboratorium
                    </a>
                </li>
            </ul>
        </li>

        <!-- Permintaan -->
        <li>
            <a href="permintaan_admin.php"
                class="flex items-center px-2 py-3 rounded-xl hover:bg-gray-700 transition-colors
                <?= in_array($current_page, ['permintaan_admin.php', 'detailpermintaan_admin.php', 'editpermintaan_admin.php'])
                    ? 'bg-amber-500 text-gray-900 font-semibold' : 'text-gray-200' ?>">
                <span class="text-xl w-6 text-center">📄</span>
                <span class="ml-3 text-sm sidebar-text hidden flex-grow">Permintaan</span>
            </a>
        </li>

        <!-- Jadwal -->
        <li>
            <a href="jadwal_admin.php"
                class="flex items-center px-2 py-3 rounded-xl hover:bg-gray-700 transition-colors
                <?= ($current_page == 'jadwal_admin.php') ? 'bg-amber-500 text-gray-900 font-semibold' : 'text-gray-200' ?>">
                <span class="text-xl w-6 text-center">📅</span>
                <span class="ml-3 text-sm sidebar-text hidden flex-grow">Jadwal</span>
            </a>
        </li>

        <!-- Laporan -->
        <li class="dropdown <?= $is_laporan_open ? 'open' : '' ?>">
            <a href="#"
                class="flex items-center px-2 py-3 rounded-xl hover:bg-gray-700 cursor-pointer transition-colors
                <?= $is_laporan_active ? 'bg-amber-500 text-gray-900 font-semibold' : 'text-gray-200' ?>"
                onclick="toggleDropdown(event)">
                <span class="flex items-center text-xl w-6 text-center">📊</span>
                <span class="ml-3 text-sm sidebar-text hidden flex-grow">Laporan</span>
                <span class="arrow ml-auto transition-transform duration-300
                    <?= $is_laporan_open ? 'rotate-180' : 'rotate-0' ?> sidebar-text-arrow hidden">▼</span>
            </a>

            <ul class="submenu flex flex-col pl-5 ml-1 mt-1 py-1 bg-[#1a2330] rounded-lg <?= $is_laporan_open ? '' : 'hidden' ?>">
                <li>
                    <a href="laporan_penggunaan.php"
                        class="block px-3 py-2 text-sm rounded-lg hover:bg-gray-700
                        <?= ($current_page == 'laporan_penggunaan.php') ? 'bg-amber-600 font-semibold text-white' : 'text-gray-300' ?>">
                        Penggunaan Ruangan
                    </a>
                </li>
                <li>
                    <a href="laporan_keuangan.php"
                        class="block px-3 py-2 text-sm rounded-lg hover:bg-gray-700
                        <?= ($current_page == 'laporan_keuangan.php') ? 'bg-amber-600 font-semibold text-white' : 'text-gray-300' ?>">
                        Keuangan
                    </a>
                </li>
            </ul>
        </li>

    </ul>
</div>

<script>
/* ----------------------------------------------------------
   Fungsi Sidebar Admin
---------------------------------------------------------- */

// Mengatur visibilitas teks dan panah
function updateSidebarVisibility(is_expanded) {
    const texts = document.querySelectorAll('.sidebar-text');
    const arrows = document.querySelectorAll('.sidebar-text-arrow');
    const submenus = document.querySelectorAll('.dropdown .submenu');

    texts.forEach(t => {
        const visible = window.innerWidth >= 1024 ? is_expanded : true;
        t.classList.toggle('hidden', !visible);
    });

    arrows.forEach(a => {
        const visible = window.innerWidth >= 1024 ? is_expanded : true;
        a.classList.toggle('hidden', !visible);
    });

    if (window.innerWidth >= 1024 && !is_expanded) {
        submenus.forEach(sub => sub.classList.add('hidden'));
        document.querySelectorAll('.dropdown').forEach(drop => drop.classList.remove('open'));
    } else {
        document.querySelectorAll('.dropdown.open .submenu').forEach(sub => sub.classList.remove('hidden'));
    }
}

// Toggle sidebar
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    const is_desktop = window.innerWidth >= 1024;

    if (is_desktop) {
        sidebar.classList.toggle('lg:w-60');
        sidebar.classList.toggle('lg:w-16');
        main?.classList.toggle('lg:ml-60');
        main?.classList.toggle('lg:ml-16');
    } else {
        sidebar.classList.toggle('translate-x-0');
        sidebar.classList.toggle('-translate-x-full');
    }

    const is_expanded = sidebar.classList.contains('lg:w-60') || sidebar.classList.contains('translate-x-0');
    updateSidebarVisibility(is_expanded);
    localStorage.setItem('sidebarStatus', is_expanded ? 'open' : 'collapsed');
}

// Toggle dropdown menu
function toggleDropdown(event) {
    event.preventDefault();
    const parent = event.currentTarget.parentElement;
    const sidebar = document.getElementById('sidebar');
    const is_desktop_expanded = window.innerWidth >= 1024 && sidebar.classList.contains('lg:w-60');
    const is_mobile_open = window.innerWidth < 1024 && sidebar.classList.contains('translate-x-0');

    if (is_mobile_open || is_desktop_expanded) {
        document.querySelectorAll('.dropdown').forEach(drop => {
            if (drop !== parent) {
                drop.classList.remove('open');
                drop.querySelector('.submenu')?.classList.add('hidden');
                drop.querySelector('.arrow')?.style.setProperty('transform', 'rotate(0deg)');
            }
        });

        const submenu = parent.querySelector('.submenu');
        const arrow = parent.querySelector('.arrow');
        parent.classList.toggle('open');

        if (parent.classList.contains('open')) {
            submenu.classList.remove('hidden');
            arrow.style.transform = 'rotate(180deg)';
        } else {
            submenu.classList.add('hidden');
            arrow.style.transform = 'rotate(0deg)';
        }
    } else if (window.innerWidth >= 1024 && sidebar.classList.contains('lg:w-16')) {
        toggleSidebar();
    } else if (window.innerWidth < 1024 && !sidebar.classList.contains('translate-x-0')) {
        toggleSidebar();
    }
}

// Restore sidebar state
window.addEventListener('load', () => {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    const status = localStorage.getItem('sidebarStatus');
    const is_desktop = window.innerWidth >= 1024;

    if (status === 'open') {
        if (is_desktop) {
            sidebar.classList.add('lg:w-60');
            sidebar.classList.remove('lg:w-16');
            main?.classList.add('lg:ml-60');
            main?.classList.remove('lg:ml-16');
        } else {
            sidebar.classList.add('translate-x-0');
            sidebar.classList.remove('-translate-x-full');
        }
    } else {
        if (is_desktop) {
            sidebar.classList.remove('lg:w-60');
            sidebar.classList.add('lg:w-16');
            main?.classList.remove('lg:ml-60');
            main?.classList.add('lg:ml-16');
        } else {
            sidebar.classList.add('-translate-x-full');
        }
    }

    updateSidebarVisibility(
        sidebar.classList.contains('lg:w-60') || sidebar.classList.contains('translate-x-0')
    );
});
</script>
