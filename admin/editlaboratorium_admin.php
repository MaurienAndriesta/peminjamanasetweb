<?php
require_once '../koneksi.php';
$db = $koneksi;

$laboratorium_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
// Perbaikan keamanan: Cek juga apakah parameter source ada
$source_table = isset($_GET['source']) ? $_GET['source'] : '';

// Daftar tabel yang diizinkan
$allowed_tables = ['labftik', 'labften', 'labfket', 'labftbe'];

if ($laboratorium_id <= 0 || !in_array($source_table, $allowed_tables)) {
    header("Location: datalaboratorium_admin.php");
    exit;
}

/* ==========================================
   AMBIL DATA DARI TABEL SPESIFIK
   ========================================== */

// Kita gunakan $source_table langsung karena sudah divalidasi di atas
$sql = "SELECT * FROM $source_table WHERE id = $laboratorium_id";
$q = mysqli_query($koneksi, $sql);

if (!$q || mysqli_num_rows($q) == 0) {
    header("Location: datalaboratorium_admin.php");
    exit;
}

$laboratorium_data = mysqli_fetch_assoc($q);
$id_column = "id"; 

/* ==========================================
   UPDATE DATA
   ========================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $kapasitas = mysqli_real_escape_string($koneksi, $_POST['kapasitas']);
    $lokasi = mysqli_real_escape_string($koneksi, $_POST['lokasi']);
    $status = mysqli_real_escape_string($koneksi, $_POST['status']);
    
    // Hapus titik/koma dari format rupiah
    $tarif_laboratorium = str_replace(['.', ','], ['', '.'], $_POST['tarif_sewa_laboratorium']);
    $tarif_peralatan = str_replace(['.', ','], ['', '.'], $_POST['tarif_sewa_peralatan']);

    $gambar_name = $laboratorium_data['foto']; 

    // Upload gambar baru
    if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === 0) {

        $allowed = ['jpg','jpeg','png'];
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {

            $upload_dir = "assets/images/";
            $gambar_new = "lab_" . $source_table . "_" . $laboratorium_id . "_" . time() . "." . $ext;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $gambar_new)) {

                if (!empty($gambar_name) && file_exists($upload_dir . $gambar_name)) {
                    unlink($upload_dir . $gambar_name);
                }

                $gambar_name = $gambar_new;
            }
        }
    }

    // UPDATE SQL (BAGIAN INI YANG DIPERBAIKI: updated_at DIHAPUS)
    $update_sql = "
        UPDATE $source_table SET
            nama = '$nama',
            kapasitas = '$kapasitas',
            lokasi = '$lokasi',
            status = '$status',
            tarif_sewa_laboratorium = '$tarif_laboratorium',
            tarif_sewa_peralatan = '$tarif_peralatan',
            foto = '$gambar_name'
        WHERE $id_column = $laboratorium_id
    ";

    if (mysqli_query($koneksi, $update_sql)) {
        header("Location: datalaboratorium_admin.php?status=success_edit");
        exit;
    } else {
        // Tampilkan error sql untuk debugging
        echo "Gagal update: " . mysqli_error($koneksi);
        exit;
    }
}

// Tentukan current fakultas untuk tampilan (disabled input)
$current_fakultas = '';
if ($source_table == 'labften') $current_fakultas = 'FTEN';
elseif ($source_table == 'labftbe') $current_fakultas = 'FTBE';
elseif ($source_table == 'labftik') $current_fakultas = 'FTIK';
elseif ($source_table == 'labfket') $current_fakultas = 'FKET';

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Laboratorium - Admin Pengelola</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
.text-dark-accent { color: #202938; }

/* === STYLING YANG SAMA DENGAN DASHBOARDADMIN === */
@media (min-width: 1024px) {
    .main { margin-left: 4rem; }
    .main.lg\:ml-60 { margin-left: 15rem; }
}
@media (max-width: 1023px) {
    .main { margin-left: 0 !important; }
}

/* Tombol Toggle - Fixed Position */
#toggleBtn {
    position: fixed; 
    top: 1.25rem; 
    left: 1.25rem; 
    z-index: 60 !important; 
    transition: left 0.3s ease; 
}

/* Sidebar Fixed */
#sidebar {
    position: fixed;
    z-index: 50; 
    top: 0;
    left: 0;
    height: 100%;
}

        .image-upload-area { position: relative; cursor: pointer; overflow: hidden; transition: all .2s; }
        .image-upload-area input[type="file"] { position: absolute; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 10; }
        .preview-image { width: 100%; height: 100%; object-fit: cover; border-radius: 0.75rem; }
        .upload-placeholder { transition: opacity .3s; z-index: 5; }
        .tarif-row { display: grid; grid-template-columns: 1fr max-content; gap: 0.5rem; align-items: center; }
        .input-group { position: relative; }
        .input-group .form-input { padding-left: 3rem; }
        .input-prefix { position: absolute; left: 0.5rem; top: 50%; transform: translateY(-50%); color: #4b5563; font-weight: 600; pointer-events: none; }
    </style>
</head>
<body class="bg-blue-100 flex min-h-screen text-gray-800">
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

<?php include 'sidebar_admin.php'; ?>
    
    <button class="hamburger bg-amber-500 hover:bg-amber-600 text-gray-900 p-2 rounded-lg transition-colors shadow-md" id="toggleBtn" onclick="toggleSidebar()">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
    </svg>
</button>

<div id="mainContent" class="main flex-1 p-3 sm:p-5 transition-all duration-300 lg:ml-16 min-h-screen">


    <div class="bg-white p-6 rounded-xl shadow-lg">
        <div class="flex items-center mb-6 border-b pb-4">
            <a href="datalaboratorium_admin.php" class="bg-gray-700 hover:bg-gray-800 text-white p-3 rounded-lg mr-4 transition-colors">←</a>
            <div>
                <h1 class="text-2xl font-bold text-amber-700">Edit Data Laboratorium: <?= htmlspecialchars($laboratorium_data['nama'] ?? 'N/A') ?></h1>
                <p class="text-gray-500 text-sm">Perbarui informasi laboratorium (Tabel: <?= htmlspecialchars($source_table) ?>)</p>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data" id="editForm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                
                <div class="space-y-6">
                    <?php if (!empty($laboratorium_data['foto'])): ?>
                    <div class="bg-blue-50 text-blue-800 p-3 rounded-lg text-sm">
                        📷 Gambar saat ini: <?= htmlspecialchars($laboratorium_data['foto']) ?>
                    </div>
                    <?php endif; ?>

                    <div class="image-upload-area w-full h-64 bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl flex items-center justify-center hover:border-amber-500">
                        <input type="file" id="fileInput" name="foto" accept="image/*" onchange="previewImage(event)">
                        <img id="previewImg" class="preview-image absolute inset-0" 
                            src="<?= !empty($laboratorium_data['foto']) ? 'assets/images/' . htmlspecialchars($laboratorium_data['foto']) : '' ?>" 
                            alt="Pratinjau Gambar"
                            onerror="this.style.display='none'">
                        <div class="text-center text-gray-500 upload-placeholder" id="uploadPlaceholder">
                            <p class="font-semibold">Klik untuk <?= !empty($laboratorium_data['foto']) ? 'mengubah' : 'menambah' ?> gambar</p>
                            <small>JPG, PNG maksimal 2MB</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="block font-semibold mb-2">Tarif Sewa Laboratorium : <span class="text-red-500">*</span></label>
                        <div class="tarif-row">
                            <input type="hidden" id="tarif_sewa_laboratorium" name="tarif_sewa_laboratorium" value="<?= $laboratorium_data['tarif_sewa_laboratorium'] ?? '' ?>">
                            <div class="input-group">
                                <span class="input-prefix">Rp</span>
                                <input type="text" id="tarif_laboratorium_display" class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500"
                                       value="<?= number_format($laboratorium_data['tarif_sewa_laboratorium'] ?? 0, 0, ',', '.') ?>">
                            </div>
                            <div class="inline-flex items-center px-4 py-3 border border-gray-300 rounded-lg bg-white">
                                <span class="text-sm font-medium">perhari</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="block font-semibold mb-2">Tarif Sewa Peralatan : <span class="text-red-500">*</span></label>
                        <div class="tarif-row">
                            <input type="hidden" id="tarif_sewa_peralatan" name="tarif_sewa_peralatan" value="<?= $laboratorium_data['tarif_sewa_peralatan'] ?? '' ?>">
                            <div class="input-group">
                                <span class="input-prefix">Rp</span>
                                <input type="text" id="tarif_peralatan_display" class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500"
                                       value="<?= number_format($laboratorium_data['tarif_sewa_peralatan'] ?? 0, 0, ',', '.') ?>">
                            </div>
                            <div class="inline-flex items-center px-4 py-3 border border-gray-300 rounded-lg bg-white">
                                <span class="text-sm font-medium">perhari</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="form-group">
                        <label for="nama" class="block font-semibold mb-2">Nama Laboratorium : <span class="text-red-500">*</span></label>
                        <input type="text" id="nama" name="nama" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" required 
                               value="<?= htmlspecialchars($laboratorium_data['nama'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="kapasitas" class="block font-semibold mb-2">Kapasitas : <span class="text-red-500">*</span></label>
                        <input type="text" id="kapasitas" name="kapasitas" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" required 
                               value="<?= htmlspecialchars($laboratorium_data['kapasitas'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="lokasi" class="block font-semibold mb-2">Lokasi : <span class="text-red-500">*</span></label>
                        <input type="text" id="lokasi" name="lokasi" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" required 
                               value="<?= htmlspecialchars($laboratorium_data['lokasi'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="fakultas" class="block font-semibold mb-2">Fakultas : <span class="text-red-500">*</span></label>
                        <input type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed" 
                               value="<?= $current_fakultas ?>" readonly disabled>
                        <p class="text-xs text-gray-500 mt-1">⚠️ Fakultas tidak bisa diubah saat edit.</p>
                    </div>

                    <div class="form-group">
                        <label for="status" class="block font-semibold mb-2">Status : <span class="text-red-500">*</span></label>
                        <select id="status" name="status" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" required>
                            <?php $st = isset($laboratorium_data['status']) ? $laboratorium_data['status'] : 'Tersedia'; ?>
                            <option value="Tersedia" <?= ($st === 'Tersedia') ? 'selected' : '' ?>>Tersedia</option>
                            <option value="Tidak Tersedia" <?= ($st === 'Tidak Tersedia' || $st === 'Tidak Tersedia') ? 'selected' : '' ?>>Tidak Tersedia</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-gray-200">
                <a href="datalaboratorium_admin.php" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-6 py-3 rounded-lg shadow transition-colors" onclick="confirmCancel(event);">
                    Batal
                </a>
                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-semibold px-6 py-3 rounded-lg shadow transition-colors">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    const overlay = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('toggleBtn'); 
    const is_desktop = window.innerWidth >= 1024;

    if (is_desktop) {
        sidebar.classList.toggle('lg:w-60');
        sidebar.classList.toggle('lg:w-16');
        main.classList.toggle('lg:ml-60');
        main.classList.toggle('lg:ml-16');
        
        const is_expanded = sidebar.classList.contains('lg:w-60');
        if (is_expanded) {
            toggleBtn.style.left = '16rem'; 
        } else {
            toggleBtn.style.left = '5rem'; 
        }
    } else {
        sidebar.classList.toggle('translate-x-0');
        sidebar.classList.toggle('-translate-x-full');
        if (overlay) {
            overlay.classList.toggle('hidden');
        }
    }

    const is_expanded = sidebar.classList.contains('lg:w-60') || sidebar.classList.contains('translate-x-0');
    
    if (typeof updateSidebarVisibility === 'function') {
        updateSidebarVisibility(is_expanded);
    }
    
    localStorage.setItem('sidebarStatus', is_expanded ? 'open' : 'collapsed');
}

function previewImage(event) {
    const file = event.target.files[0];
    const img = document.getElementById('previewImg');
    const placeholder = document.getElementById('uploadPlaceholder');
    if (!file) return;

    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    const maxSize = 2 * 1024 * 1024;

    if (!allowedTypes.includes(file.type)) {
        Swal.fire({ icon: 'error', title: 'Format Salah', text: 'Harus JPG/PNG.', confirmButtonColor: '#f59e0b' });
        event.target.value = ''; return;
    }
    if (file.size > maxSize) {
        Swal.fire({ icon: 'error', title: 'Terlalu Besar', text: 'Maksimal 2MB.', confirmButtonColor: '#f59e0b' });
        event.target.value = ''; return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        img.src = e.target.result;
        img.style.display = 'block';
        placeholder.style.opacity = '0';
    };
    reader.readAsDataURL(file);
}

function confirmCancel(event) {
    event.preventDefault();
    Swal.fire({
        title: 'Batalkan?', text: 'Perubahan tidak disimpan.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#f59e0b', cancelButtonColor: '#6b7280', confirmButtonText: 'Ya', cancelButtonText: 'Tidak'
    }).then((result) => {
        if (result.isConfirmed) window.location.href = 'datalaboratorium_admin.php';
    });
}

function formatRupiah(angka) {
    const number = parseInt(String(angka).replace(/[^0-9]/g, ''));
    if (isNaN(number)) return '';
    return number.toLocaleString('id-ID');
}

function setupTarifInput(displayId, hiddenId) {
    const display = document.getElementById(displayId);
    const hidden = document.getElementById(hiddenId);
    display.addEventListener('input', function(e) {
        const formatted = formatRupiah(e.target.value);
        e.target.value = formatted;
        hidden.value = formatted.replace(/\./g, '');
    });
}

document.getElementById('editForm').addEventListener('submit', function(e) {
    let isValid = true;
    this.querySelectorAll('input, select').forEach(input => input.style.borderColor = '');
    
    const requiredFields = ['nama', 'kapasitas', 'lokasi', 'status'];
    requiredFields.forEach(fieldId => {
        const input = document.getElementById(fieldId);
        if (input && !input.value.trim()) {
            isValid = false; input.style.borderColor = '#ef4444';
        }
    });

    const tarifLab = document.getElementById('tarif_sewa_laboratorium');
    const tarifAlat = document.getElementById('tarif_sewa_peralatan');
    if (parseFloat(tarifLab.value) <= 0) { isValid = false; document.getElementById('tarif_laboratorium_display').style.borderColor = '#ef4444'; }
    if (parseFloat(tarifAlat.value) <= 0) { isValid = false; document.getElementById('tarif_peralatan_display').style.borderColor = '#ef4444'; }
    
    if (!isValid) {
        e.preventDefault();
        Swal.fire({ icon: 'warning', title: 'Data Kurang', text: 'Lengkapi form & tarif > 0.', confirmButtonColor: '#f59e0b' });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    setupTarifInput('tarif_laboratorium_display', 'tarif_sewa_laboratorium');
    setupTarifInput('tarif_peralatan_display', 'tarif_sewa_peralatan');

    const img = document.getElementById('previewImg');
    const ph = document.getElementById('uploadPlaceholder');
    if (img.src && !img.src.endsWith('/')) { img.style.display = 'block'; ph.style.opacity = '0'; } 
    else { img.style.display = 'none'; ph.style.opacity = '1'; }

    const sidebar = document.getElementById("sidebar");
    const main = document.getElementById("mainContent");
    const overlay = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('toggleBtn'); 
    const status = localStorage.getItem('sidebarStatus');
    const is_desktop = window.innerWidth >= 1024;

    if (sidebar) {
        if (status === 'open') {
            if (is_desktop) {
                main.classList.add('lg:ml-60');
                main.classList.remove('lg:ml-16');
                sidebar.classList.add('lg:w-60');
                sidebar.classList.remove('lg:w-16');
                toggleBtn.style.left = '16rem'; 
            } else {
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
                if (overlay) overlay.classList.remove('hidden');
            }
        } else if (status === 'collapsed') {
            if (is_desktop) {
                main.classList.remove('lg:ml-60');
                main.classList.add('lg:ml-16');
                sidebar.classList.remove('lg:w-60');
                sidebar.classList.add('lg:w-16');
                toggleBtn.style.left = '5rem'; 
            } else {
                sidebar.classList.remove('translate-x-0');
                sidebar.classList.add('-translate-x-full');
                if (overlay) overlay.classList.add('hidden');
            }
        }
    }

    if (overlay) {
        overlay.addEventListener('click', toggleSidebar);
    }
});

window.addEventListener('resize', () => {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    const overlay = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('toggleBtn'); 
    const is_desktop = window.innerWidth >= 1024;
    const status = localStorage.getItem('sidebarStatus');

    if (is_desktop) {
        sidebar.classList.remove('translate-x-0', '-translate-x-full');
        if (overlay) overlay.classList.add('hidden');

        if (status === 'open') {
            sidebar.classList.add('lg:w-60');
            sidebar.classList.remove('lg:w-16');
            main.classList.add('lg:ml-60');
            main.classList.remove('lg:ml-16');
            toggleBtn.style.left = '16rem'; 
        } else {
            sidebar.classList.add('lg:w-16');
            sidebar.classList.remove('lg:w-60');
            main.classList.add('lg:ml-16');
            main.classList.remove('lg:ml-60');
            toggleBtn.style.left = '5rem'; 
        }
    } else {
        sidebar.classList.remove('lg:w-60', 'lg:w-16');
        main.classList.remove('lg:ml-60', 'lg:ml-16');
        toggleBtn.style.left = '1.25rem'; 

        if (status === 'open') {
            sidebar.classList.add('translate-x-0');
            sidebar.classList.remove('-translate-x-full');
            if (overlay) overlay.classList.remove('hidden');
        } else {
            sidebar.classList.add('-translate-x-full');
            sidebar.classList.remove('translate-x-0');
            if (overlay) overlay.classList.add('hidden');
        }
    }
});
</script>

</body>
</html>