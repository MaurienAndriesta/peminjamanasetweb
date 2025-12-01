<?php
require_once '../koneksi.php';
$db = $koneksi;

// Ambil ID dari URL
$usaha_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($usaha_id <= 0) {
    header('Location: datausaha_admin.php');
    exit;
}

$usaha_data = [];
$error_message = null;

// --- 1. Ambil data lama dari database ---
$sql = "SELECT * FROM tbl_usaha WHERE id = $usaha_id";
$result = mysqli_query($db, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $usaha_data = mysqli_fetch_assoc($result);
} else {
    header('Location: datausaha_admin.php');
    exit;
}

// Inisialisasi nilai awal status untuk dropdown (Default dari Database)
// Kita gunakan trim() untuk memastikan tidak ada spasi yang mengganggu
$status_sekarang = isset($usaha_data['status']) ? trim($usaha_data['status']) : 'tersedia';

// --- 2. Jika form disubmit ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_usaha = trim($_POST['nama_usaha'] ?? '');
    $kapasitas = trim($_POST['kapasitas'] ?? '');
    
    // Ambil status dari form
    $status_input = $_POST['status'] ?? 'tersedia';
    
    $tipe_tarif = $_POST['tipe_tarif'] ?? 'berbayar';
    $tarif_eksternal = (float)str_replace(['.', ','], ['', '.'], $_POST['tarif_eksternal'] ?? '0');
    $keterangan = trim($_POST['keterangan'] ?? '');

    // Update variable status_sekarang agar jika error, dropdown tetap ngikutin inputan terakhir user
    $status_sekarang = $status_input;

    $errors = [];

    // Validasi
    if (empty($nama_usaha)) $errors[] = "Nama usaha harus diisi";
    if (empty($kapasitas)) $errors[] = "Kapasitas harus diisi";
    if (empty($keterangan)) $errors[] = "Keterangan harus diisi";

    if ($tipe_tarif === 'gratis') {
        $tarif_eksternal = 0;
    } else {
        if ($tarif_eksternal <= 0) $errors[] = "Tarif eksternal harus lebih dari 0";
    }

    // --- Upload Gambar ---
    $gambar_name = $usaha_data['gambar'];
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 2 * 1024 * 1024; // 2MB
        $upload_dir = 'assets/images/';

        if (!in_array($_FILES['gambar']['type'], $allowed_types)) {
            $errors[] = "Format gambar harus JPG, JPEG, atau PNG";
        } elseif ($_FILES['gambar']['size'] > $max_size) {
            $errors[] = "Ukuran gambar maksimal 2MB";
        } else {
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $file_ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $gambar_name_new = 'usaha_' . $usaha_id . '_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $gambar_name_new;

            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path)) {
                if (!empty($usaha_data['gambar']) && file_exists($upload_dir . $usaha_data['gambar'])) {
                    unlink($upload_dir . $usaha_data['gambar']);
                }
                $gambar_name = $gambar_name_new;
            } else {
                $errors[] = "Gagal mengupload gambar baru";
            }
        }
    }

    // Jika tidak ada error → update database
    if (empty($errors)) {
        $gambar_sql = !empty($gambar_name) ? ", gambar = '$gambar_name'" : "";
        
        $update_sql = "
            UPDATE tbl_usaha 
            SET 
                nama = '$nama_usaha',
                kapasitas = '$kapasitas',
                status = '$status_input', 
                tarif_eksternal = '$tarif_eksternal',
                keterangan = '$keterangan',
                updated_at = NOW()
                $gambar_sql
            WHERE id = $usaha_id
        ";

        if (mysqli_query($db, $update_sql)) {
            // Redirect dengan status sukses
            header("Location: datausaha_admin.php?status=success_edit");
            exit;
        } else {
            $error_message = "Gagal memperbarui data: " . mysqli_error($db);
        }
    } else {
        $error_message = implode("<br>", $errors);
        // Pertahankan input user jika error (agar form tidak kosong)
        $usaha_data['nama'] = $nama_usaha;
        $usaha_data['kapasitas'] = $kapasitas;
        $usaha_data['tarif_eksternal'] = $tarif_eksternal;
        $usaha_data['keterangan'] = $keterangan;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Usaha - Admin Pengelola</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
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

<?php include 'sidebar_admin.php'; ?>

<div id="mainContent" class="flex-1 p-5 ml-16 transition-all duration-300">
    
    <div class="header flex justify-start items-center mb-6">
        <button class="bg-amber-500 hover:bg-amber-600 text-gray-900 p-2 rounded-lg transition-colors" onclick="toggleSidebar()">☰</button>
    </div>

    <?php if ($error_message): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 shadow-md" role="alert">
            ❌ <?= $error_message ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-xl shadow-lg">
        <div class="flex items-center mb-6 border-b pb-4">
            <a href="datausaha_admin.php" class="bg-gray-700 hover:bg-gray-800 text-white p-3 rounded-lg mr-4 transition-colors">←</a>
            <div>
                <h1 class="text-2xl font-bold text-amber-700">Edit Data Usaha: <?= htmlspecialchars($usaha_data['nama'] ?? 'N/A') ?></h1>
                <p class="text-gray-500 text-sm">Perbarui informasi penyewaan usaha</p>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data" id="editForm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                
                <div class="space-y-6">
                    <?php if (!empty($usaha_data['gambar'])): ?>
                    <div class="bg-blue-50 text-blue-800 p-3 rounded-lg text-sm">
                        📷 Gambar saat ini: <?= htmlspecialchars($usaha_data['gambar']) ?>
                    </div>
                    <?php endif; ?>

                    <div class="image-upload-area w-full h-64 bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl flex items-center justify-center hover:border-amber-500">
                        <input type="file" id="fileInput" name="gambar" accept="image/*" onchange="previewImage(event)">
                        <img id="previewImg" class="preview-image absolute inset-0" 
                            src="<?= !empty($usaha_data['gambar']) ? 'assets/images/' . htmlspecialchars($usaha_data['gambar']) : '' ?>" 
                            alt="Pratinjau Gambar" onerror="this.style.display='none'">
                        <div class="text-center text-gray-500 upload-placeholder" id="uploadPlaceholder">
                            <p class="font-semibold">Klik untuk <?= !empty($usaha_data['gambar']) ? 'mengubah' : 'menambah' ?> gambar</p>
                            <small>JPG, PNG maksimal 2MB</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="block font-semibold mb-2">Tipe Tarif : <span class="text-red-500">*</span></label>
                        <div class="flex items-center gap-6">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" id="gratis" name="tipe_tarif" value="gratis" class="form-radio text-amber-500 h-4 w-4"
                                    <?= (!isset($usaha_data['tarif_eksternal']) || $usaha_data['tarif_eksternal'] == 0) ? 'checked' : '' ?>>
                                Gratis
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" id="berbayar" name="tipe_tarif" value="berbayar" class="form-radio text-amber-500 h-4 w-4"
                                    <?= (isset($usaha_data['tarif_eksternal']) && $usaha_data['tarif_eksternal'] > 0) ? 'checked' : '' ?>>
                                Berbayar
                            </label>
                        </div>
                    </div>

                    <div id="tarifSection">
                        <div class="form-group">
                            <label class="block font-semibold mb-2">Tarif Sewa Eksternal : <span class="text-red-500">*</span></label>
                            <div class="tarif-row">
                                <input type="hidden" id="tarif_eksternal" name="tarif_eksternal" value="<?= $usaha_data['tarif_eksternal'] ?? '' ?>">
                                <div class="input-group">
                                    <span class="input-prefix">Rp</span>
                                    <input type="text" id="tarif_eksternal_display" class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500"
                                           value="<?= number_format($usaha_data['tarif_eksternal'] ?? 0, 0, ',', '.') ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="form-group">
                        <label for="nama_usaha" class="block font-semibold mb-2">Nama Usaha : <span class="text-red-500">*</span></label>
                        <input type="text" id="nama_usaha" name="nama_usaha" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                               placeholder="Isi Nama Usaha" required 
                               value="<?= htmlspecialchars($usaha_data['nama'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="kapasitas" class="block font-semibold mb-2">Kapasitas / Luas : <span class="text-red-500">*</span></label>
                        <input type="text" id="kapasitas" name="kapasitas" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                               placeholder="Isi Kapasitas (Contoh: 3x4m)" required
                               value="<?= htmlspecialchars($usaha_data['kapasitas'] ?? '') ?>">
                    </div>

                    <div class="form-group">
    <label for="status" class="block font-semibold mb-2">Status : <span class="text-red-500">*</span></label>
    <select id="status" name="status" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" required>
        <option value="tersedia" <?= ($usaha_data['status'] ?? 'tersedia') === 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
        <option value="tidak tersedia" <?= ($usaha_data['status'] ?? 'tersedia') === 'tidak tersedia' ? 'selected' : '' ?>>Tidak Tersedia</option>
    </select>
</div>

                    <div class="form-group">
                        <label for="keterangan" class="block font-semibold mb-2">Keterangan : <span class="text-red-500">*</span></label>
                        <textarea id="keterangan" name="keterangan" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                                  placeholder="Isi Keterangan" required><?= htmlspecialchars($usaha_data['keterangan'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-gray-200">
                <a href="datausaha_admin.php" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-6 py-3 rounded-lg shadow transition-colors"
                   onclick="confirmCancel(event);">
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
    const is_desktop = window.innerWidth >= 1024;
    if (is_desktop) {
        sidebar.classList.toggle('lg:w-60'); sidebar.classList.toggle('lg:w-16');
        main.classList.toggle('lg:ml-60'); main.classList.toggle('lg:ml-16');
    } else {
        sidebar.classList.toggle('translate-x-0'); sidebar.classList.toggle('-translate-x-full');
    }
    localStorage.setItem('sidebarStatus', sidebar.classList.contains('lg:w-60') ? 'open' : 'collapsed');
}

function previewImage(event) {
    const file = event.target.files[0];
    const img = document.getElementById('previewImg');
    const placeholder = document.getElementById('uploadPlaceholder');
    if (!file) return;

    if (!['image/jpeg', 'image/png', 'image/jpg'].includes(file.type) || file.size > 2 * 1024 * 1024) {
        Swal.fire({ icon: 'error', title: 'File Error', text: 'Format harus JPG/PNG dan Max 2MB', confirmButtonColor: '#f59e0b' });
        event.target.value = ''; img.style.display = 'none'; placeholder.style.opacity = '1';
        return;
    }
    const reader = new FileReader();
    reader.onload = function(e) { img.src = e.target.result; img.style.display = 'block'; placeholder.style.opacity = '0'; };
    reader.readAsDataURL(file);
}

function confirmCancel(event) {
    event.preventDefault();
    Swal.fire({ title: 'Batalkan?', text: 'Perubahan tidak disimpan.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#f59e0b', confirmButtonText: 'Ya', cancelButtonText: 'Tidak' })
    .then((result) => { if (result.isConfirmed) window.location.href = 'datausaha_admin.php'; });
}

function formatRupiah(angka) {
    const number = parseInt(angka.replace(/[^0-9]/g, ''));
    return isNaN(number) ? '' : number.toLocaleString('id-ID');
}

function setupTarifInput(displayId, hiddenId) {
    const display = document.getElementById(displayId);
    const hidden = document.getElementById(hiddenId);
    if (display && hidden) {
        display.addEventListener('input', function(e) {
            const formatted = formatRupiah(e.target.value);
            e.target.value = formatted;
            hidden.value = formatted.replace(/\./g, '');
        });
    }
}

function toggleTarifSection() {
    const isBerbayar = document.getElementById('berbayar').checked;
    const section = document.getElementById('tarifSection');
    const inputs = section.querySelectorAll('input');
    if (isBerbayar) {
        section.style.display = 'block'; section.style.opacity = '1';
        inputs.forEach(i => i.type !== 'hidden' && (i.disabled = false));
    } else {
        section.style.display = 'none'; section.style.opacity = '0.5';
        inputs.forEach(i => i.type !== 'hidden' && (i.disabled = true));
        document.getElementById('tarif_eksternal').value = '0';
        document.getElementById('tarif_eksternal_display').value = '';
    }
}

document.getElementById('editForm').addEventListener('submit', function(e) {
    const isBerbayar = document.getElementById('berbayar').checked;
    let isValid = true;
    this.querySelectorAll('input, select, textarea').forEach(i => i.style.borderColor = '');

    if (isBerbayar) {
        const tarif = document.getElementById('tarif_eksternal');
        if (tarif && parseFloat(tarif.value) <= 0) {
            isValid = false; document.getElementById('tarif_eksternal_display').style.borderColor = '#ef4444';
        }
    }
    
    ['nama_usaha', 'kapasitas', 'status', 'keterangan'].forEach(id => {
        const el = document.getElementById(id);
        if (el && !el.value.trim()) { isValid = false; el.style.borderColor = '#ef4444'; }
    });

    if (!isValid) { e.preventDefault(); Swal.fire({ icon: 'warning', title: 'Lengkapi Form', text: 'Periksa field kosong / tarif.', confirmButtonColor: '#f59e0b' }); }
});

document.getElementById('gratis').addEventListener('change', toggleTarifSection);
document.getElementById('berbayar').addEventListener('change', toggleTarifSection);

document.addEventListener('DOMContentLoaded', function() {
    setupTarifInput('tarif_eksternal_display', 'tarif_eksternal');
    toggleTarifSection();
    
    const img = document.getElementById('previewImg');
    const ph = document.getElementById('uploadPlaceholder');
    if (img.src && img.src.includes('assets/images/') && img.src.length > 30) { img.style.display = 'block'; ph.style.opacity = '0'; } 
    else { img.style.display = 'none'; ph.style.opacity = '1'; }

    const status = localStorage.getItem('sidebarStatus');
    const main = document.getElementById('mainContent');
    const sidebar = document.getElementById('sidebar');
    if (status === 'open' && window.innerWidth >= 1024) {
        main.classList.add('lg:ml-60'); main.classList.remove('lg:ml-16');
        sidebar.classList.add('lg:w-60'); sidebar.classList.remove('lg:w-16');
    }
});
</script>

</body>
</html>