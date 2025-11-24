<?php
require_once '../koneksi.php';

// Get usaha ID
$usaha_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($usaha_id <= 0) {
    header('Location: datausaha_admin.php');
    exit;
}

// Variabel
$usaha_data = [];
$error_message = null;

// ---- 1. Ambil data lama ----
$stmt = mysqli_prepare($koneksi, "SELECT id, nama, kapasitas, tarif_eksternal, keterangan, gambar FROM tbl_usaha WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $usaha_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $usaha_data = mysqli_fetch_assoc($result);
} else {
    header("Location: datausaha_admin.php?error=not_found");
    exit;
}
mysqli_stmt_close($stmt);
$periode = $_REQUEST['periode'] ?? 'bulan';



// ---- 2. Jika form disubmit ----
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $nama_usaha = trim($_POST['nama_usaha'] ?? '');
    $kapasitas = trim($_POST['kapasitas'] ?? '');
    $tarif_eksternal = trim($_POST['tarif_eksternal'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    // Validasi sederhana
    if ($nama_usaha == '' || $kapasitas == '' || $tarif_eksternal == '') {
        $error_message = "Semua field wajib diisi.";
    } else {

        // ---- Cek upload gambar ----
        $gambar_name = $usaha_data['gambar']; // default: gambar lama

        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $new_name = "usaha_" . time() . "." . $ext;

            $target = "../uploads/" . $new_name;

            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target)) {
                $gambar_name = $new_name;
            }
        }

        // ---- 3. UPDATE DATA ----
        $update_fields = "nama=?, kapasitas=?, tarif_eksternal=?, keterangan=?, updated_at=NOW()";
        $types = "ssds";
        $params = [$nama_usaha, $kapasitas, $tarif_eksternal, $keterangan];

        if (!empty($gambar_name)) {
            $update_fields .= ", gambar=?";
            $types .= "s";
            $params[] = $gambar_name;
        }

        $update_fields .= " WHERE id=?";
        $types .= "i";
        $params[] = $usaha_id;

        $sql = "UPDATE tbl_usaha SET $update_fields";
        $stmt = mysqli_prepare($koneksi, $sql);

        mysqli_stmt_bind_param($stmt, $types, ...$params);

        if (mysqli_stmt_execute($stmt)) {
            header("Location: datausaha_admin.php?status=success_edit");
            exit;
        } else {
            $error_message = "Gagal update: " . mysqli_error($koneksi);
        }

        mysqli_stmt_close($stmt);
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
        /* Style untuk tarif inputs */
        .tarif-inputs > div {
            display: flex;
            flex-direction: column; 
            gap: 0.5rem;
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
            <a href="datausaha_admin.php" class="bg-gray-700 hover:bg-gray-800 text-white p-3 rounded-lg mr-4 transition-colors">←</a>
            
            <div>
                <h1 class="text-2xl font-bold text-amber-700">Edit Data Usaha</h1>
                <p class="text-gray-500 text-sm">Perbarui informasi usaha</p>
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
                            alt="Pratinjau Gambar"
                            onerror="this.style.display='none'">
                        
                        <div class="text-center text-gray-500 upload-placeholder" id="uploadPlaceholder">
                            <p class="font-semibold">Klik untuk <?= !empty($usaha_data['gambar']) ? 'mengubah' : 'menambah' ?> gambar</p>
                            <small>JPG, PNG maksimal 2MB</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="nama_usaha" class="block font-semibold mb-2">Usaha : <span class="text-red-500">*</span></label>
                        <input type="text" id="nama_usaha" name="nama_usaha" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                               placeholder="Isi Nama Usaha" required 
                               value="<?= htmlspecialchars($usaha_data['nama'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="kapasitas" class="block font-semibold mb-2">Kapasitas : <span class="text-red-500">*</span></label>
                        <input type="text" id="kapasitas" name="kapasitas" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                               placeholder="Isi Kapasitas" required
                               value="<?= htmlspecialchars($usaha_data['kapasitas'] ?? '') ?>">
                    </div>
                </div>

                <div class="space-y-6">
                    

                    <div>
        <label class="block font-semibold mb-2">Tipe Tarif : <span class="text-red-500">*</span></label>
        <div class="flex items-center gap-6 mt-1">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="periode" value="bulan" <?= ($periode === 'bulan') ? 'checked' : '' ?>>
                    Bulan
                </label>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="periode" value="tahun" <?= ($periode === 'tahun') ? 'checked' : '' ?>>
                    Tahun
                </label>
            </div>

      </div>

                    <div class="form-group" id="tarifSection">
                        <label class="block font-semibold mb-2">Tarif Sewa Eksternal : <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-4">
                            
                            <div>
                                <input type="number" name="tarif_eksternal" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                                       placeholder="Isi Tarif Eksternal" 
                                       value="<?= isset($usaha_data['tarif_eksternal']) && $usaha_data['tarif_eksternal'] > 0 ? $usaha_data['tarif_eksternal'] : '' ?>">
                                <div class="flex items-center justify-start mt-2 text-sm text-gray-600">
                                    <span class="font-medium">Eksternal</span>
                                </div>
                            </div>
                        </div>
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
            window.location.href = 'datausaha_admin.php';
        }
    });
}

// === Validasi Form ===
document.getElementById('editForm').addEventListener('submit', function(e) {
    const isBerbayar = document.getElementById('berbayar').checked;
    let isValid = true;

    this.querySelectorAll('input, textarea').forEach(input => input.style.borderColor = '');

    if (isBerbayar) {
        const tarifInternal = document.querySelector('input[name="tarif_internal"]');
        const tarifEksternal = document.querySelector('input[name="tarif_eksternal"]');
        
        if (tarifInternal && (!tarifInternal.value || parseFloat(tarifInternal.value) <= 0)) {
            isValid = false;
            tarifInternal.style.borderColor = '#ef4444';
        }
        if (tarifEksternal && (!tarifEksternal.value || parseFloat(tarifEksternal.value) <= 0)) {
            isValid = false;
            tarifEksternal.style.borderColor = '#ef4444';
        }
    }

    this.querySelectorAll('[required]').forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.style.borderColor = '#ef4444';
        }
    });

    if (!isValid) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Form Belum Lengkap',
            text: 'Mohon lengkapi semua field yang diperlukan, terutama tarif jika "Berbayar" dipilih.',
            confirmButtonColor: '#f59e0b'
        });
    }
});

// === Toggle Tarif ===
function toggleTarifSection() {
    const isBerbayar = document.getElementById('berbayar').checked;
    const tarifSection = document.getElementById('tarifSection');
    const tarifInputs = tarifSection.querySelectorAll('input[type="number"]');
    
    if (isBerbayar) {
        tarifSection.style.display = 'block';
        tarifSection.style.opacity = '1';
        tarifInputs.forEach(input => input.disabled = false);
    } else {
        tarifSection.style.display = 'none';
        tarifSection.style.opacity = '0.5';
        tarifInputs.forEach(input => input.disabled = true);
    }
}

document.getElementById('gratis').addEventListener('change', toggleTarifSection);
document.getElementById('berbayar').addEventListener('change', toggleTarifSection);

// === Inisialisasi ===
document.addEventListener('DOMContentLoaded', function() {
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