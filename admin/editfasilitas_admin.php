<?php
require_once '../koneksi.php';
$db = $koneksi;

// Ambil ID dari URL
$fasilitas_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($fasilitas_id <= 0) {
    header('Location: datafasilitas_admin.php');
    exit;
}

$fasilitas_data = [];
$error_message = null;

// --- 1. Ambil data lama dari database ---
$sql = "SELECT * FROM tbl_fasilitas WHERE id = $fasilitas_id";
$result = mysqli_query($db, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $fasilitas_data = mysqli_fetch_assoc($result);
} else {
    header('Location: datafasilitas_admin.php');
    exit;
}

// --- 2. Jika form disubmit ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_fasilitas = trim($_POST['nama_fasilitas'] ?? '');
    $kapasitas = trim($_POST['kapasitas'] ?? '');
    $status = $_POST['status'] ?? 'tersedia';
    $tipe_tarif = $_POST['tipe_tarif'] ?? 'berbayar';
    $tarif_internal = (float)str_replace(['.', ','], ['', '.'], $_POST['tarif_internal'] ?? '0');
    $tarif_eksternal = (float)str_replace(['.', ','], ['', '.'], $_POST['tarif_eksternal'] ?? '0');
    $keterangan = trim($_POST['keterangan'] ?? '');

    $errors = [];

    // Validasi
    if (empty($nama_fasilitas)) $errors[] = "Nama fasilitas harus diisi";
    if (empty($kapasitas)) $errors[] = "Kapasitas harus diisi";
    if (empty($keterangan)) $errors[] = "Keterangan harus diisi";

    if ($tipe_tarif === 'gratis') {
        $tarif_internal = 0;
        $tarif_eksternal = 0;
    } else {
        if ($tarif_internal <= 0) $errors[] = "Tarif internal harus lebih dari 0";
        if ($tarif_eksternal <= 0) $errors[] = "Tarif eksternal harus lebih dari 0";
    }

    // --- Upload Gambar ---
    $gambar_name = $fasilitas_data['gambar'];
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
            $gambar_name_new = 'fasilitas_' . $fasilitas_id . '_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $gambar_name_new;

            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path)) {
                if (!empty($fasilitas_data['gambar']) && file_exists($upload_dir . $fasilitas_data['gambar'])) {
                    unlink($upload_dir . $fasilitas_data['gambar']);
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
            UPDATE tbl_fasilitas 
            SET 
                nama = '$nama_fasilitas',
                kapasitas = '$kapasitas',
                status = '$status',
                tarif_internal = '$tarif_internal',
                tarif_eksternal = '$tarif_eksternal',
                keterangan = '$keterangan',
                updated_at = NOW()
                $gambar_sql
            WHERE id = $fasilitas_id
        ";

        if (mysqli_query($db, $update_sql)) {
            header("Location: datafasilitas_admin.php?status=success_edit");
            exit;
        } else {
            $error_message = "Gagal memperbarui data: " . mysqli_error($db);
        }
    } else {
        $error_message = implode("<br>", $errors);
        $fasilitas_data = array_merge($fasilitas_data, [
            'nama' => $nama_fasilitas,
            'kapasitas' => $kapasitas,
            'status' => $status,
            'tarif_internal' => $tarif_internal,
            'tarif_eksternal' => $tarif_eksternal,
            'keterangan' => $keterangan,
            'gambar' => $gambar_name
        ]);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Fasilitas - Admin Pengelola</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS khusus untuk integrasi dan font */
        body { 
            font-family: 'Poppins', sans-serif; 
        }
        /* Style untuk image upload */
        .image-upload-area {
            position: relative;
            cursor: pointer;
            overflow: hidden;
            transition: all .2s;
        }
        /* PENTING: Input file harus selalu visible dan di atas elemen lain */
        .image-upload-area input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            z-index: 10; 
        }
        .preview-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 0.75rem; 
        }
        .upload-placeholder {
            transition: opacity .3s;
            z-index: 5;
        }
        /* Style untuk tarif row */
        .tarif-row {
            display: grid;
            grid-template-columns: 1fr max-content; 
            gap: 0.5rem;
            align-items: center;
        }
        .input-group {
            position: relative;
        }
        .input-group .form-input { 
            padding-left: 3rem; 
        }
        .input-prefix { 
            position: absolute; 
            left: 0.5rem;
            top: 50%; 
            transform: translateY(-50%); 
            color: #4b5563;
            font-weight: 600;
            pointer-events: none;
        }
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
            <a href="datafasilitas_admin.php" class="bg-gray-700 hover:bg-gray-800 text-white p-3 rounded-lg mr-4 transition-colors">←</a>
            
            <div>
                <h1 class="text-2xl font-bold text-amber-700">Edit Data Fasilitas: <?= htmlspecialchars($fasilitas_data['nama'] ?? 'N/A') ?></h1>
                <p class="text-gray-500 text-sm">Perbarui informasi fasilitas</p>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data" id="editForm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                
                <div class="space-y-6">
                    <?php if (!empty($fasilitas_data['gambar'])): ?>
                    <div class="bg-blue-50 text-blue-800 p-3 rounded-lg text-sm">
                        📷 Gambar saat ini: <?= htmlspecialchars($fasilitas_data['gambar']) ?>
                    </div>
                    <?php endif; ?>

                    <div class="image-upload-area w-full h-64 bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl flex items-center justify-center hover:border-amber-500">
                        <input type="file" id="fileInput" name="gambar" accept="image/*" onchange="previewImage(event)">
                        
                        <img id="previewImg" class="preview-image absolute inset-0" 
                            src="<?= !empty($fasilitas_data['gambar']) ? 'assets/images/' . htmlspecialchars($fasilitas_data['gambar']) : '' ?>" 
                            alt="Pratinjau Gambar"
                            onerror="this.style.display='none'">
                        
                        <div class="text-center text-gray-500 upload-placeholder" id="uploadPlaceholder">
                            <p class="font-semibold">Klik untuk <?= !empty($fasilitas_data['gambar']) ? 'mengubah' : 'menambah' ?> gambar</p>
                            <small>JPG, PNG maksimal 2MB</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="block font-semibold mb-2">Tipe Tarif : <span class="text-red-500">*</span></label>
                        <div class="flex items-center gap-6">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" id="gratis" name="tipe_tarif" value="gratis" class="form-radio text-amber-500 h-4 w-4"
                                       <?= (!isset($fasilitas_data['tarif_internal']) || $fasilitas_data['tarif_internal'] == 0) ? 'checked' : '' ?>>
                                Gratis
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" id="berbayar" name="tipe_tarif" value="berbayar" class="form-radio text-amber-500 h-4 w-4"
                                       <?= (isset($fasilitas_data['tarif_internal']) && $fasilitas_data['tarif_internal'] > 0) ? 'checked' : '' ?>>
                                Berbayar
                            </label>
                        </div>
                    </div>

                    <div id="tarifSection">
                        <div class="form-group">
                            <label class="block font-semibold mb-2">Tarif Sewa Internal : <span class="text-red-500">*</span></label>
                            <div class="tarif-row">
                                <!-- INPUT ASLI (hidden) -->
                                <input type="hidden" 
                                       id="tarif_internal" 
                                       name="tarif_internal"
                                       value="<?= $fasilitas_data['tarif_internal'] ?? '' ?>">

                                <!-- INPUT UNTUK USER (formatted) -->
                                <div class="input-group">
                                    <span class="input-prefix">Rp</span>
                                    <input type="text" 
                                           id="tarif_internal_display"
                                           class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500"
                                           value="<?= number_format($fasilitas_data['tarif_internal'] ?? 0, 0, ',', '.') ?>">
                                </div>

                                <div class="inline-flex items-center px-4 py-3 border border-gray-300 rounded-lg bg-white">
                                    <span class="text-sm font-medium">perhari</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="block font-semibold mb-2">Tarif Sewa Eksternal : <span class="text-red-500">*</span></label>
                            <div class="tarif-row">
                                <input type="hidden" 
                                       id="tarif_eksternal" 
                                       name="tarif_eksternal"
                                       value="<?= $fasilitas_data['tarif_eksternal'] ?? '' ?>">

                                <div class="input-group">
                                    <span class="input-prefix">Rp</span>
                                    <input type="text" 
                                           id="tarif_eksternal_display"
                                           class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500"
                                           value="<?= number_format($fasilitas_data['tarif_eksternal'] ?? 0, 0, ',', '.') ?>">
                                </div>

                                <div class="inline-flex items-center px-4 py-3 border border-gray-300 rounded-lg bg-white">
                                    <span class="text-sm font-medium">perhari</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="form-group">
                        <label for="nama_fasilitas" class="block font-semibold mb-2">Nama Fasilitas : <span class="text-red-500">*</span></label>
                        <input type="text" id="nama_fasilitas" name="nama_fasilitas" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                               placeholder="Isi Nama Fasilitas" required 
                               value="<?= htmlspecialchars($fasilitas_data['nama'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="kapasitas" class="block font-semibold mb-2">Kapasitas : <span class="text-red-500">*</span></label>
                        <select id="kapasitas" name="kapasitas" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                            <option value="">-- Pilih Kapasitas --</option>
                            <option value="Dalam Kota" <?= ($fasilitas_data['kapasitas'] ?? '') == 'Dalam Kota' ? 'selected' : '' ?>>Dalam Kota</option>
                            <option value="Luar Kota" <?= ($fasilitas_data['kapasitas'] ?? '') == 'Luar Kota' ? 'selected' : '' ?>>Luar Kota</option>
                            <option value="Standar" <?= ($fasilitas_data['kapasitas'] ?? '') == 'Standar' ? 'selected' : '' ?>>Standar</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status" class="block font-semibold mb-2">Status : <span class="text-red-500">*</span></label>
                        <select id="status" name="status" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" required>
                            <option value="tersedia" <?= ($fasilitas_data['status'] ?? '') === 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
                            <option value="tidak_tersedia" <?= ($fasilitas_data['status'] ?? '') === 'tidak_tersedia' ? 'selected' : '' ?>>Tidak Tersedia</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="keterangan" class="block font-semibold mb-2">Keterangan : <span class="text-red-500">*</span></label>
                        <textarea id="keterangan" name="keterangan" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                                 placeholder="Isi Keterangan" required><?= htmlspecialchars($fasilitas_data['keterangan'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-gray-200">
                <a href="datafasilitas_admin.php" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-6 py-3 rounded-lg shadow transition-colors"
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
        sidebar.classList.toggle('lg:w-60');
        sidebar.classList.toggle('lg:w-16');
        main.classList.toggle('lg:ml-60');
        main.classList.toggle('lg:ml-16');
    } else {
        sidebar.classList.toggle('translate-x-0');
        sidebar.classList.toggle('-translate-x-full');
    }

    const is_expanded = sidebar.classList.contains('lg:w-60') || sidebar.classList.contains('translate-x-0');
    
    if (typeof updateSidebarVisibility === 'function') {
        updateSidebarVisibility(is_expanded);
    }
    
    localStorage.setItem('sidebarStatus', is_expanded ? 'open' : 'collapsed');
}

// === Pratinjau Gambar + Validasi SweetAlert ===
function previewImage(event) {
    const file = event.target.files[0];
    const img = document.getElementById('previewImg');
    const placeholder = document.getElementById('uploadPlaceholder');
    if (!file) return;

    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    const maxSize = 2 * 1024 * 1024; // 2MB

    if (!allowedTypes.includes(file.type)) {
        Swal.fire({
            icon: 'error',
            title: 'Format Tidak Didukung',
            text: 'Format gambar harus JPG, JPEG, atau PNG.',
            confirmButtonColor: '#f59e0b'
        });
        event.target.value = '';
        img.style.display = 'none';
        placeholder.style.opacity = '1';
        return;
    }

    if (file.size > maxSize) {
        Swal.fire({
            icon: 'error',
            title: 'Ukuran File Terlalu Besar',
            text: 'Ukuran maksimal gambar adalah 2MB.',
            confirmButtonColor: '#f59e0b'
        });
        event.target.value = '';
        img.style.display = 'none';
        placeholder.style.opacity = '1';
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        img.src = e.target.result;
        img.style.display = 'block';
        placeholder.style.opacity = '0';
    };
    reader.readAsDataURL(file);
}

// === SweetAlert Tombol Batal ===
function confirmCancel(event) {
    event.preventDefault();
    Swal.fire({
        title: 'Batalkan Perubahan?',
        text: 'Perubahan yang sudah kamu buat tidak akan disimpan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Batalkan',
        cancelButtonText: 'Tidak'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'datafasilitas_admin.php';
        }
    });
}

// === Format Rupiah untuk Input Display ===
function formatRupiah(angka) {
    const number = parseInt(angka.replace(/[^0-9]/g, ''));
    if (isNaN(number)) return '';
    return number.toLocaleString('id-ID');
}

function setupTarifInput(displayId, hiddenId) {
    const display = document.getElementById(displayId);
    const hidden = document.getElementById(hiddenId);
    
    display.addEventListener('input', function(e) {
        const formatted = formatRupiah(e.target.value);
        e.target.value = formatted;
        const raw = formatted.replace(/\./g, '');
        hidden.value = raw;
    });
}

// === Toggle Tarif Section ===
function toggleTarifSection() {
    const isBerbayar = document.getElementById('berbayar').checked;
    const tarifSection = document.getElementById('tarifSection');

    if (isBerbayar) {
        tarifSection.style.display = 'block';
        tarifSection.style.opacity = '1';
    } else {
        tarifSection.style.display = 'none';
        tarifSection.style.opacity = '0.5';
        // Reset nilai saat gratis
        document.getElementById('tarif_internal').value = '0';
        document.getElementById('tarif_eksternal').value = '0';
        document.getElementById('tarif_internal_display').value = '';
        document.getElementById('tarif_eksternal_display').value = '';
    }
}

// === Validasi Form ===
document.getElementById('editForm').addEventListener('submit', function(e) {
    const isBerbayar = document.getElementById('berbayar').checked;
    let isValid = true;

    this.querySelectorAll('input, select, textarea').forEach(input => input.style.borderColor = '');

    // Field yang harus diisi
    const requiredFields = ['nama_fasilitas', 'kapasitas', 'status', 'keterangan'];

    requiredFields.forEach(field => {
        const input = document.getElementById(field);
        if (input && !input.value.trim()) {
            isValid = false;
            input.style.borderColor = '#ef4444';
        }
    });

    // Cek tarif jika berbayar
    if (isBerbayar) {
        const tarifInternal = document.getElementById('tarif_internal');
        const tarifEksternal = document.getElementById('tarif_eksternal');
        const tarifInternalDisplay = document.getElementById('tarif_internal_display');
        const tarifEksternalDisplay = document.getElementById('tarif_eksternal_display');

        if (tarifInternal && parseFloat(tarifInternal.value) <= 0) {
            isValid = false;
            tarifInternalDisplay.style.borderColor = '#ef4444'; 
        }
        if (tarifEksternal && parseFloat(tarifEksternal.value) <= 0) {
            isValid = false;
            tarifEksternalDisplay.style.borderColor = '#ef4444'; 
        }
    }
    
    if (!isValid) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Form Belum Lengkap',
            text: 'Mohon lengkapi semua field yang diperlukan dan pastikan tarif lebih dari 0 jika berbayar.',
            confirmButtonColor: '#f59e0b'
        });
    }
});

// === Inisialisasi ===
document.getElementById('gratis').addEventListener('change', toggleTarifSection);
document.getElementById('berbayar').addEventListener('change', toggleTarifSection);

document.addEventListener('DOMContentLoaded', function() {
    // Setup format rupiah untuk kedua input tarif
    setupTarifInput('tarif_internal_display', 'tarif_internal');
    setupTarifInput('tarif_eksternal_display', 'tarif_eksternal');
    
    toggleTarifSection();

    const img = document.getElementById('previewImg');
    const placeholder = document.getElementById('uploadPlaceholder');
    if (img.src && img.src.includes('assets/images/') && img.src.length > 30) {
        img.style.display = 'block';
        placeholder.style.opacity = '0';
    } else {
        img.style.display = 'none';
        placeholder.style.opacity = '1';
    }

    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    const status = localStorage.getItem('sidebarStatus');
    
    if (status === 'open') {
        main.classList.add('ml-60');
        main.classList.remove('ml-16');
    } else {
        main.classList.remove('ml-60');
        main.classList.add('ml-16');
    }
});
</script>

</body>
</html>