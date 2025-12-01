<?php
require_once '../koneksi.php';
$db = $koneksi;

$error_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_ruang = trim($_POST['nama_ruang'] ?? '');
    $kapasitas = trim($_POST['kapasitas'] ?? ''); // Kembali menjadi input teks
    $lokasi = trim($_POST['lokasi'] ?? ''); // Field lokasi dikembalikan
    $tipe_tarif = $_POST['tipe_tarif'] ?? 'berbayar';
    $tarif_internal = (float)($_POST['tarif_internal'] ?? 0);
    $tarif_eksternal = (float)($_POST['tarif_eksternal'] ?? 0);
    $keterangan = trim($_POST['keterangan'] ?? '');

    $errors = [];
    if (!$nama_ruang) $errors[] = "Nama Ruang harus diisi";
    if (!$kapasitas) $errors[] = "Kapasitas harus diisi";
    if (!$lokasi) $errors[] = "Lokasi harus diisi"; // Validasi lokasi dikembalikan
    if ($tipe_tarif === 'berbayar') {
        if ($tarif_internal <= 0) $errors[] = "Tarif internal harus lebih dari 0";
        if ($tarif_eksternal <= 0) $errors[] = "Tarif eksternal harus lebih dari 0";
    }
    if (!$keterangan) $errors[] = "Keterangan harus diisi";

    // Logika upload gambar tetap sama
    $gambar_name = null;
    $upload_path = '';
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === 0) {
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
            $gambar_name = 'ruangan_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $upload_path = $upload_dir . $gambar_name;

            if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path)) {
                $errors[] = "Gagal mengupload gambar";
                $gambar_name = null;
            }
        }
    }

    if (empty($errors)) {
        if ($tipe_tarif === 'gratis') {
            $tarif_internal = 0;
            $tarif_eksternal = 0;
        }

        // Query INSERT dikembalikan untuk menyertakan 'lokasi'
        $stmt = $db->prepare("
            INSERT INTO tbl_ruangmultiguna 
            (nama, kapasitas, lokasi, tarif_internal, tarif_eksternal, keterangan, gambar, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'tersedia')
        ");

        if (!$stmt) {
            $error_message = "Prepare failed: " . $db->error;
        } else {
            // bind_param disesuaikan, 's' untuk lokasi ditambahkan kembali
            $stmt->bind_param(
                "sssddss",
                $nama_ruang,
                $kapasitas,
                $lokasi,
                $tarif_internal,
                $tarif_eksternal,
                $keterangan,
                $gambar_name
            );

            if ($stmt->execute()) {
                header('Location: dataruangmultiguna_admin.php?status=success_add');
                exit;
            } else {
                $error_message = "Query failed: " . $stmt->error;
                if ($gambar_name && file_exists($upload_path)) unlink($upload_path);
            }
            $stmt->close();
        }
    } else {
        $error_message = implode("<br>", $errors);
        if ($gambar_name && file_exists($upload_path)) unlink($upload_path);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Data Ruang Multiguna - Admin Pengelola</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

    <?php if (isset($error_message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 shadow-md" role="alert">
            ❌ <?= $error_message ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-xl shadow-lg">
        <div class="flex items-center mb-6 border-b pb-4">
            <a href="dataruangmultiguna_admin.php" class="bg-gray-700 hover:bg-gray-800 text-white p-3 rounded-lg mr-4 transition-colors">←</a>
            
            <div>
                <h1 class="text-2xl font-bold text-amber-700">Tambah Data Ruang Multiguna</h1>
                <p class="text-gray-500 text-sm">Tambahkan Ruangan baru ke sistem</p>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data" id="tambahForm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                
                <div class="space-y-6">
                    <div class="image-upload-area w-full h-64 bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl flex items-center justify-center hover:border-amber-500">
                        <input type="file" id="fileInput" name="gambar" accept="image/*" onchange="previewImage(event)">
                        <img id="previewImg" class="preview-image absolute inset-0" style="display: none;" alt="Pratinjau Gambar">
                        <div class="text-center text-gray-500 upload-placeholder" id="uploadPlaceholder">
                            <p class="font-semibold">Klik untuk menambah gambar</p>
                            <small>JPG, PNG maksimal 2MB (Opsional)</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="block font-semibold mb-2">Tipe Tarif : <span class="text-red-500">*</span></label>
                        <div class="flex items-center gap-6">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" id="gratis" name="tipe_tarif" value="gratis" class="form-radio text-amber-500 h-4 w-4"> Gratis
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" id="berbayar" name="tipe_tarif" value="berbayar" class="form-radio text-amber-500 h-4 w-4" checked> Berbayar
                            </label>
                        </div>
                    </div>

                    <div id="tarifSection">
                        <div class="form-group">
                            <label class="block font-semibold mb-2">Tarif Sewa Internal : <span class="text-red-500">*</span></label>
                            <div class="tarif-row">
                                <input type="hidden" id="tarif_internal" name="tarif_internal" value="0">
                                <div class="input-group">
                                    <span class="input-prefix">Rp</span>
                                    <input type="text" id="tarif_internal_display" class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500" placeholder="Masukkan tarif internal">
                                </div>
                                <div class="inline-flex items-center px-4 py-3 border border-gray-300 rounded-lg bg-white">
                                    <span class="text-sm font-medium">perhari</span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="block font-semibold mb-2">Tarif Sewa Eksternal : <span class="text-red-500">*</span></label>
                            <div class="tarif-row">
                                <input type="hidden" id="tarif_eksternal" name="tarif_eksternal" value="0">
                                <div class="input-group">
                                    <span class="input-prefix">Rp</span>
                                    <input type="text" id="tarif_eksternal_display" class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500" placeholder="Masukkan tarif eksternal">
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
                        <label for="nama_ruang" class="block font-semibold mb-2">Nama Ruang : <span class="text-red-500">*</span></label>
                        <input type="text" id="nama_ruang" name="nama_ruang" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" placeholder="Isi Nama Ruang" required>
                    </div>

                    <!-- KAPASITAS: Kembali menjadi input teks -->
                    <div class="form-group">
                        <label for="kapasitas" class="block font-semibold mb-2">Kapasitas : <span class="text-red-500">*</span></label>
                        <input type="text" id="kapasitas" name="kapasitas" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                               placeholder="Contoh: 150 orang" required>
                    </div>

                    <!-- LOKASI: Input field dikembalikan -->
                    <div class="form-group">
                        <label for="lokasi" class="block font-semibold mb-2">Lokasi : <span class="text-red-500">*</span></label>
                        <input type="text" id="lokasi" name="lokasi" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                               placeholder="Contoh: Gedung A, Lantai 5" required>
                    </div>

                    <div class="form-group">
                        <label for="keterangan" class="block font-semibold mb-2">Keterangan : <span class="text-red-500">*</span></label>
                        <textarea id="keterangan" name="keterangan" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" placeholder="Isi Keterangan" required></textarea>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-gray-200">
                <a href="dataruangmultiguna_admin.php" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-6 py-3 rounded-lg shadow transition-colors" onclick="confirmCancel(event);">
                    Batal
                </a>
                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-semibold px-6 py-3 rounded-lg shadow transition-colors">
                    Tambah Data
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Seluruh JavaScript di bawah ini identik dengan file fasilitas dan tidak perlu diubah
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

function previewImage(event) {
    const file = event.target.files[0];
    const img = document.getElementById('previewImg');
    const placeholder = document.getElementById('uploadPlaceholder');
    if (!file) return;

    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    const maxSize = 2 * 1024 * 1024;

    if (!allowedTypes.includes(file.type)) {
        Swal.fire({ icon: 'error', title: 'Format Tidak Didukung', text: 'Format gambar harus JPG, JPEG, atau PNG.', confirmButtonColor: '#f59e0b' });
        event.target.value = '';
        img.style.display = 'none';
        placeholder.style.opacity = '1';
        return;
    }

    if (file.size > maxSize) {
        Swal.fire({ icon: 'error', title: 'Ukuran File Terlalu Besar', text: 'Ukuran maksimal gambar adalah 2MB.', confirmButtonColor: '#f59e0b' });
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

function resetForm() {
    Swal.fire({
        title: 'Batalkan Penambahan Data?',
        text: 'Perubahan yang sudah kamu buat tidak akan disimpan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Batalkan',
        cancelButtonText: 'Tidak'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'dataruangmultiguna_admin.php';
        }
    });
}

document.getElementById('tambahForm').addEventListener('submit', function(e) {
    const isBerbayar = document.getElementById('berbayar').checked;
    let isValid = true;
    
    this.querySelectorAll('input, textarea, select').forEach(input => input.style.borderColor = '');

    this.querySelectorAll('[required]').forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.style.borderColor = '#ef4444';
        }
    });

    if (isBerbayar) {
        const tarifInternal = document.getElementById('tarif_internal');
        const tarifEksternal = document.getElementById('tarif_eksternal');
        
        if (!tarifInternal.value || parseFloat(tarifInternal.value) <= 0) {
            isValid = false;
            document.getElementById('tarif_internal_display').style.borderColor = '#ef4444';
        }
        if (!tarifEksternal.value || parseFloat(tarifEksternal.value) <= 0) {
            isValid = false;
            document.getElementById('tarif_eksternal_display').style.borderColor = '#ef4444';
        }
    }
    
    if (!isValid) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Form Belum Lengkap',
            text: 'Mohon lengkapi semua field yang ditandai bintang (*) sebelum menyimpan.',
            confirmButtonColor: '#f59e0b'
        });
    }
});

function toggleTarifSection() {
    const isBerbayar = document.getElementById('berbayar').checked;
    const tarifSection = document.getElementById('tarifSection');
    const tarifInputs = [document.getElementById('tarif_internal'), document.getElementById('tarif_eksternal')];
    
    if (isBerbayar) {
        tarifSection.style.display = 'block';
        tarifInputs.forEach(input => {
            if (input) input.required = true;
        });
    } else {
        tarifSection.style.display = 'none';
        tarifInputs.forEach(input => {
            if (input) {
                input.required = false;
                input.value = '0';
                let displayId = input.id + '_display';
                let displayInput = document.getElementById(displayId);
                if(displayInput) displayInput.value = '0';
            }
        });
    }
}

document.getElementById('gratis').addEventListener('change', toggleTarifSection);
document.getElementById('berbayar').addEventListener('change', toggleTarifSection);

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    const overlay = document.getElementById('sidebarOverlay');
    const status = localStorage.getItem('sidebarStatus');
    const is_desktop = window.innerWidth >= 1024;

    if (status === 'open') {
        if (is_desktop) {
            main.classList.add('lg:ml-60'); main.classList.remove('lg:ml-16');
            sidebar.classList.add('lg:w-60'); sidebar.classList.remove('lg:w-16');
        } else {
            sidebar.classList.remove('-translate-x-full'); sidebar.classList.add('translate-x-0');
            overlay.classList.remove('hidden');
        }
    } else {
        if (is_desktop) {
            main.classList.remove('lg:ml-60'); main.classList.add('lg:ml-16');
            sidebar.classList.remove('lg:w-60'); sidebar.classList.add('lg:w-16');
        } else {
            sidebar.classList.remove('translate-x-0'); sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }
    }

    if (overlay) overlay.addEventListener('click', toggleSidebar);
    toggleTarifSection();
});

function onlyNumber(str) {
    return str.replace(/[^0-9]/g, '');
}

document.getElementById('tarif_internal_display').addEventListener('input', function () {
    let rawValue = onlyNumber(this.value);
    document.getElementById('tarif_internal').value = rawValue;
    this.value = new Intl.NumberFormat('id-ID').format(rawValue || 0);
});

document.getElementById('tarif_eksternal_display').addEventListener('input', function () {
    let rawValue = onlyNumber(this.value);
    document.getElementById('tarif_eksternal').value = rawValue;
    this.value = new Intl.NumberFormat('id-ID').format(rawValue || 0);
});
</script>

</body>
</html>