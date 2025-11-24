<?php
require_once '../koneksi.php';
$db = $koneksi;

// --- Inisialisasi pesan error ---
$error_message = null;

// --- Proses form ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $kapasitas = trim($_POST['kapasitas'] ?? '');
    $lokasi = trim($_POST['lokasi'] ?? '');
    $fakultas = $_POST['fakultas'] ?? '';
    $status = $_POST['status'] ?? 'tersedia';
    $tarif_laboratorium = trim($_POST['tarif_laboratorium'] ?? '');
    $tarif_peralatan = trim($_POST['tarif_peralatan'] ?? '');
    
    ;

    $errors = [];

    // --- Validasi dasar ---
    if (empty($nama)) $errors[] = "Nama laboratorium harus diisi";
    if (empty($kapasitas)) $errors[] = "Kapasitas harus diisi";
    if (empty($lokasi)) $errors[] = "Lokasi harus diisi";
    if (empty($fakultas)) $errors[] = "Fakultas harus dipilih";
    if (empty($tarif_laboratorium) || (float)$tarif_laboratorium <= 0) $errors[] = "Tarif laboratorium harus diisi dan lebih dari 0";
    if (empty($tarif_peralatan) || (float)$tarif_peralatan <= 0) $errors[] = "Tarif peralatan harus diisi dan lebih dari 0";

    // --- Upload Gambar ---
    $gambar_name = '';
    $upload_path = '';
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 2 * 1024 * 1024;
        $upload_dir = 'assets/images/';

        if (!in_array($_FILES['gambar']['type'], $allowed_types)) {
            $errors[] = "Format gambar harus JPG, JPEG, atau PNG";
        } elseif ($_FILES['gambar']['size'] > $max_size) {
            $errors[] = "Ukuran gambar maksimal 2MB";
        } else {
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $file_ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $gambar_name = 'lab_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $upload_path = $upload_dir . $gambar_name;

            if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path)) {
                $errors[] = "Gagal upload gambar";
                $gambar_name = '';
            }
        }
    }

    // --- Jika tidak ada error ---
    if (empty($errors)) {
        // Tentukan tabel sesuai fakultas
        switch ($fakultas) {
            case 'FTBE': $tabel = 'labftbe'; break;
            case 'FTEN': $tabel = 'labften'; break;
            case 'FTIK': $tabel = 'labftik'; break;
            case 'FKET': $tabel = 'labfket'; break;
            default:
                $error_message = "Fakultas tidak valid.";
                $tabel = null;
        }

        if ($tabel) {
            // --- Siapkan query ---
            $sql = "INSERT INTO $tabel 
        (nama, kapasitas, lokasi, foto,
         tarif_sewa_laboratorium, tarif_sewa_peralatan, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)";
           $stmt = $db->prepare($sql);

    if ($stmt) {
    $stmt->bind_param(
        "ssssdds",
        $nama,
        $kapasitas,
        $lokasi,
        $gambar_name,
        $tarif_laboratorium,
        $tarif_peralatan,
        $status
    );

                if ($stmt->execute()) {
                    header("Location: datalaboratorium_admin.php?status=success_add");
                    exit;
                } else {
                    $error_message = "Gagal menambahkan data laboratorium: " . $stmt->error;
                    if ($gambar_name && file_exists($upload_path)) unlink($upload_path);
                }

                $stmt->close();
            } else {
                $error_message = "Kesalahan query: " . $db->error;
            }
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
    <title>Tambah Data Laboratorium - Admin Pengelola</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
             /* Mengganti grid-template-columns: 1fr 120px */
            display: grid;
            grid-template-columns: 1fr max-content; 
            gap: 0.5rem; /* gap: 10px */
            align-items: center;
        }
        .input-group {
            position: relative;
        }
        .input-group .form-input { 
            padding-left: 3rem; /* Menambah padding agar prefix 'Rp' terlihat */
        }
        .input-prefix { 
            position: absolute; 
            left: 0.5rem; /* left: 16px */
            top: 50%; 
            transform: translateY(-50%); 
            color: #4b5563; /* gray-600 */
            font-weight: 600; /* font-semibold */
            pointer-events: none; /* agar tidak menghalangi klik input */
        }
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
            <a href="datalaboratorium_admin.php" class="bg-gray-700 hover:bg-gray-800 text-white p-3 rounded-lg mr-4 transition-colors">←</a>
            
            <div>
                <h1 class="text-2xl font-bold text-amber-700">Tambah Data</h1>
                <p class="text-gray-500 text-sm">Laboratorium</p>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data" id="tambahForm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                
                <div class="space-y-6">
                    <div class="image-upload-area w-full h-64 bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl flex items-center justify-center hover:border-amber-500">
                        <input type="file" id="fileInput" name="gambar" accept="image/*" onchange="previewImage(event)">
                        
                        <div class="text-center text-gray-500 upload-placeholder" id="uploadPlaceholder">
                            <p class="font-semibold">Klik untuk menambah gambar</p>
                            <small>JPG, PNG maksimal 2MB (Opsional)</small>
                        </div>
                        <img id="previewImg" class="preview-image absolute inset-0" style="display: none;" alt="Preview">
                    </div>

                    <div class="form-group">
                        <label for="tarif_laboratorium" class="block font-semibold mb-2">Tarif Sewa Laboratorium : <span class="text-red-500">*</span></label>
                        <div class="tarif-row">
                            <div class="input-group">
                                <span class="input-prefix">Rp</span>
                                <input type="number" id="tarif_laboratorium" name="tarif_laboratorium" class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                                       placeholder="Isi Tarif Sewa Laboratorium" required min="0" step="0.01"
                                       value="<?= htmlspecialchars($_POST['tarif_laboratorium'] ?? '') ?>">
                            </div>
                            
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="tarif_peralatan" class="block font-semibold mb-2">Tarif Sewa Peralatan : <span class="text-red-500">*</span></label>
                        <div class="tarif-row">
                            <div class="input-group">
                                <span class="input-prefix">Rp</span>
                                <input type="number" id="tarif_peralatan" name="tarif_peralatan" class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                                       placeholder="Isi Tarif Sewa Peralatan" required min="0" step="0.01"
                                       value="<?= htmlspecialchars($_POST['tarif_peralatan'] ?? '') ?>">
                            </div>
                            
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="form-group">
                        <label for="nama" class="block font-semibold mb-2">Nama Laboratorium : <span class="text-red-500">*</span></label>
                        <input type="text" id="nama" name="nama" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                               placeholder="Isi Nama Laboratorium" required 
                               value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="kapasitas" class="block font-semibold mb-2">Kapasitas : <span class="text-red-500">*</span></label>
                        <input type="text" id="kapasitas" name="kapasitas" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                               placeholder="Isi Kapasitas" required
                               value="<?= htmlspecialchars($_POST['kapasitas'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="lokasi" class="block font-semibold mb-2">Lokasi : <span class="text-red-500">*</span></label>
                        <input type="text" id="lokasi" name="lokasi" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                               placeholder="Isi Lokasi" required
                               value="<?= htmlspecialchars($_POST['lokasi'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="fakultas" class="block font-semibold mb-2">Fakultas : <span class="text-red-500">*</span></label>
                        <select id="fakultas" name="fakultas" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" required>
                            <option value="">-- Pilih Fakultas --</option>
                            <option value="FTEN" <?= (isset($_POST['fakultas']) && $_POST['fakultas'] === 'FTEN') ? 'selected' : '' ?>>Fakultas Telematika Energi (FTEN)</option>
                            <option value="FTBE" <?= (isset($_POST['fakultas']) && $_POST['fakultas'] === 'FTBE') ? 'selected' : '' ?>>Fakultas Teknologi dan Bisnis Energi (FTBE)</option>
                            <option value="FTIK" <?= (isset($_POST['fakultas']) && $_POST['fakultas'] === 'FTIK') ? 'selected' : '' ?>>Fakultas Teknologi Infrastruktur dan Kewilayahan (FTIK)</option>
                            <option value="FKET" <?= (isset($_POST['fakultas']) && $_POST['fakultas'] === 'FKET') ? 'selected' : '' ?>>Fakultas Ketenagalistrikan dan Energi Terbarukan (FKET)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status" class="block font-semibold mb-2">Status : <span class="text-red-500">*</span></label>
                        <select id="status" name="status" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" required>
                            <option value="tersedia" <?= (!isset($_POST['status']) || $_POST['status'] === 'tersedia') ? 'selected' : '' ?>>Tersedia</option>
                            <option value="tidak_tersedia" <?= (isset($_POST['status']) && $_POST['status'] === 'tidak_tersedia') ? 'selected' : '' ?>>Tidak Tersedia</option>
                        </select>
                    </div>

                    
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-gray-200">
                <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-6 py-3 rounded-lg shadow transition-colors" onclick="resetForm()">
                    Batal
                </button>
                <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-gray-900 font-semibold px-6 py-3 rounded-lg shadow transition-colors">
                    Tambah Data
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// PENTING: Fungsi toggleSidebar() harus sama persis dengan yang ada di sidebar_admin.php
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

// 🔸 Preview gambar
function previewImage(event) {
    const file = event.target.files[0];
    const img = document.getElementById('previewImg');
    const placeholder = document.getElementById('uploadPlaceholder');

    if (file) {
        const maxSize = 2 * 1024 * 1024; // 2MB
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];

        // Cek ukuran file
        if (file.size > maxSize) {
            Swal.fire({
                icon: 'warning',
                title: 'Ukuran Terlalu Besar!',
                text: 'Ukuran gambar maksimal 2MB.',
                confirmButtonColor: '#f59e0b'
            });
            event.target.value = ''; // reset input
            img.src = ''; // hapus preview
            img.style.display = 'none';
            placeholder.classList.remove('opacity-0');
            placeholder.classList.add('opacity-100');
            return;
        }

        // Cek tipe file
        if (!allowedTypes.includes(file.type)) {
            Swal.fire({
                icon: 'error',
                title: 'Format Tidak Didukung!',
                text: 'Format gambar harus JPG, JPEG, atau PNG.',
                confirmButtonColor: '#f59e0b'
            });
            event.target.value = ''; // reset input
            img.src = ''; // hapus preview
            img.style.display = 'none';
            placeholder.classList.remove('opacity-0');
            placeholder.classList.add('opacity-100');
            return;
        }

        // Kalau lolos validasi, tampilkan preview seperti biasa
        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            img.style.display = 'block';
            placeholder.classList.add('opacity-0');
            placeholder.classList.remove('opacity-100');
        };
        reader.readAsDataURL(file);
    }
}

// 🔸 SweetAlert konfirmasi sebelum batal
function resetForm() {
    Swal.fire({
        title: 'Batalkan Penambahan Data?',
        text: 'Data yang belum disimpan akan hilang.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Batalkan',
        cancelButtonText: 'Tidak'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'datalaboratorium_admin.php';
        }
    });
}

// 🔸 Validasi form + ukuran gambar maksimal 2MB
document.getElementById('tambahForm').addEventListener('submit', function(e) {
    let isValid = true;

    // Cegah submit jika file > 2MB
    const fileInput = document.getElementById('fileInput');
    const file = fileInput.files[0];
    if (file && file.size > 2097152) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Ukuran File Terlalu Besar',
            text: 'Maksimal ukuran gambar adalah 2 MB.',
            confirmButtonColor: '#f59e0b'
        });
        fileInput.value = '';
        document.getElementById('previewImg').style.display = 'none';
        document.getElementById('uploadPlaceholder').classList.remove('opacity-0');
        document.getElementById('uploadPlaceholder').classList.add('opacity-100');
        return;
    }

    // Reset border warna
    this.querySelectorAll('input, select, textarea').forEach(input => input.style.borderColor = '');

    const requiredFields = [
        'nama', 'kapasitas', 'lokasi', 'fakultas',
         'tarif_laboratorium', 'tarif_peralatan'
    ];

    requiredFields.forEach(field => {
        const input = document.getElementById(field);
        if (input && !input.value.trim()) {
            isValid = false;
            input.style.borderColor = '#ef4444';
        }
    });

    const tarifLab = document.getElementById('tarif_laboratorium');
    const tarifAlat = document.getElementById('tarif_peralatan');

    if (tarifLab && parseFloat(tarifLab.value) <= 0) {
        isValid = false;
        tarifLab.style.borderColor = '#ef4444';
    }
    if (tarifAlat && parseFloat(tarifAlat.value) <= 0) {
        isValid = false;
        tarifAlat.style.borderColor = '#ef4444';
    }

    if (!isValid) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Form Tidak Lengkap',
            text: 'Mohon lengkapi semua field dan pastikan tarif lebih dari 0!',
            confirmButtonColor: '#f59e0b'
        });
    }
});

// 🔸 Validasi otomatis saat upload gambar (maks 2MB)
document.getElementById('fileInput').addEventListener('change', function() {
    const file = this.files[0];
    if (file && file.size > 2097152) {
        Swal.fire({
            icon: 'error',
            title: 'Ukuran File Terlalu Besar',
            text: 'Maksimal ukuran gambar adalah 2 MB.',
            confirmButtonColor: '#f59e0b'
        });
        this.value = ''; 
        document.getElementById('previewImg').style.display = 'none';
        document.getElementById('uploadPlaceholder').classList.remove('opacity-0');
        document.getElementById('uploadPlaceholder').classList.add('opacity-100');
    }
});

// 🔸 Restore sidebar state
document.addEventListener('DOMContentLoaded', function() {
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