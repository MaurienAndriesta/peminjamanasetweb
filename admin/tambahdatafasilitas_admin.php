<?php
// Include database configuration
require_once 'config/database.php';

// Initialize database connection
$db = new Database();

// Initialize message variable
$error_message = null;

// Processing form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_fasilitas = trim($_POST['nama_fasilitas'] ?? '');
    $kapasitas = trim($_POST['kapasitas'] ?? '');
    $lokasi = trim($_POST['lokasi'] ?? '');
    $tipe_tarif = $_POST['tipe_tarif'] ?? 'berbayar';
    $periode = $_POST['periode'] ?? 'hari';
    $tarif_internal = (float)($_POST['tarif_internal'] ?? 0);
    $tarif_eksternal = (float)($_POST['tarif_eksternal'] ?? 0);
    $keterangan = trim($_POST['keterangan'] ?? '');
    
    // Validation
    $errors = [];
    if (empty($nama_fasilitas)) $errors[] = "Nama fasilitas harus diisi";
    if (empty($kapasitas)) $errors[] = "Kapasitas harus diisi";
    if (empty($lokasi)) $errors[] = "Lokasi harus diisi";
    
    // Validasi tarif
    if ($tipe_tarif === 'berbayar' && $tarif_internal <= 0) $errors[] = "Tarif internal harus lebih dari 0";
    if ($tipe_tarif === 'berbayar' && $tarif_eksternal <= 0) $errors[] = "Tarif eksternal harus lebih dari 0";
    
    if (empty($keterangan)) $errors[] = "Keterangan harus diisi";
    
    // Handle image upload
    $gambar_name = '';
    $upload_path = '';
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 2 * 1024 * 1024; // 2MB
        $upload_dir = 'assets/images/';

        if (!in_array($_FILES['gambar']['type'], $allowed_types)) {
            $errors[] = "Format gambar harus JPG, JPEG, atau PNG";
        } elseif ($_FILES['gambar']['size'] > $max_size) {
            $errors[] = "Ukuran gambar maksimal 2MB";
        } else {
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $gambar_name = 'fasilitas_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $upload_path = $upload_dir . $gambar_name;
            
            if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path)) {
                $errors[] = "Gagal mengupload gambar";
                $gambar_name = '';
            }
        }
    }
    
    if (empty($errors)) {
        try {
            // Set tarif ke 0 jika gratis
            if ($tipe_tarif === 'gratis') {
                $tarif_internal = 0;
                $tarif_eksternal = 0;
            }
            
            $db->query("INSERT INTO fasilitas (nama, kapasitas, lokasi, tarif_internal, tarif_eksternal, keterangan, gambar, status) 
                        VALUES (:nama, :kapasitas, :lokasi, :tarif_internal, :tarif_eksternal, :keterangan, :gambar, 'aktif')");
            
            $db->bind(':nama', $nama_fasilitas);
            $db->bind(':kapasitas', $kapasitas);
            $db->bind(':lokasi', $lokasi);
            $db->bind(':tarif_internal', $tarif_internal);
            $db->bind(':tarif_eksternal', $tarif_eksternal);
            $db->bind(':keterangan', $keterangan); 
            $db->bind(':gambar', $gambar_name);
            
            if ($db->execute()) {
                header('Location: datafasilitas_admin.php?status=success_add');
                exit; 
            } else {
                $error_message = "Gagal menambahkan data fasilitas";
                if (!empty($gambar_name) && file_exists($upload_path)) {
                    unlink($upload_path);
                }
            }
        } catch (Exception $e) {
            $error_message = "Terjadi kesalahan: " . $e->getMessage();
            if (!empty($gambar_name) && file_exists($upload_path)) {
                unlink($upload_path);
            }
        }
    } else {
        $error_message = implode("<br>", $errors);
        if (!empty($gambar_name) && file_exists($upload_path)) {
            unlink($upload_path);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Data Fasilitas - Admin Pengelola</title>
    <script src="https://cdn.tailwindcss.com"></script>
    
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
        }
        .text-dark-accent { color: #202938; }

        /* Responsive Sidebar */
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

        /* Image upload */
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
            transition: opacity .2s;
            pointer-events: none;
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

    <?php if (isset($error_message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-3 sm:px-4 py-3 rounded-lg mb-4 shadow-md text-sm" role="alert">
            ❌ <?= $error_message ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-4 sm:p-6 rounded-xl shadow-lg">
        <div class="flex items-center mb-6 border-b pb-4">
            <a href="datafasilitas_admin.php" class="bg-gray-700 hover:bg-gray-800 text-white p-2 sm:p-3 rounded-lg mr-3 sm:mr-4 transition-colors text-sm sm:text-base">←</a>
            
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-dark-accent">Tambah Data Fasilitas</h1>
                <p class="text-gray-500 text-xs sm:text-sm">Tambahkan fasilitas baru ke sistem</p>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data" id="tambahForm">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-10">
                
                <!-- Left Column -->
                <div class="space-y-5">
                    <div class="image-upload-area w-full h-48 sm:h-64 bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl flex items-center justify-center hover:border-amber-500 relative">
                        <input type="file" id="fileInput" name="gambar" accept="image/*" onchange="previewImage(event)">
                        
                        <div class="text-center text-gray-500 upload-placeholder" id="uploadPlaceholder">
                            <p class="font-semibold text-sm sm:text-base">📷 Klik untuk menambah gambar</p>
                            <small class="text-xs">JPG, PNG maksimal 2MB (Opsional)</small>
                        </div>
                        <img id="previewImg" class="preview-image absolute inset-0" style="display: none;" alt="Preview">
                    </div>

                    <div class="form-group">
                        <label for="nama_fasilitas" class="block font-semibold mb-2 text-sm sm:text-base">Nama Fasilitas <span class="text-red-500">*</span></label>
                        <input type="text" id="nama_fasilitas" name="nama_fasilitas" 
                               class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm sm:text-base" 
                               placeholder="Isi Nama Fasilitas" required 
                               value="<?= htmlspecialchars($_POST['nama_fasilitas'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="kapasitas" class="block font-semibold mb-2 text-sm sm:text-base">Kapasitas <span class="text-red-500">*</span></label>
                        <input type="text" id="kapasitas" name="kapasitas" 
                               class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm sm:text-base" 
                               placeholder="Isi Kapasitas" required
                               value="<?= htmlspecialchars($_POST['kapasitas'] ?? '') ?>">
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-5">
                    <div class="form-group">
                        <label for="lokasi" class="block font-semibold mb-2 text-sm sm:text-base">Lokasi <span class="text-red-500">*</span></label>
                        <input type="text" id="lokasi" name="lokasi" 
                               class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm sm:text-base" 
                               placeholder="Isi Lokasi" required
                               value="<?= htmlspecialchars($_POST['lokasi'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="block font-semibold mb-2 text-sm sm:text-base">Tipe Tarif <span class="text-red-500">*</span></label>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-6">
                            <div class="flex items-center gap-4 sm:gap-6">
                                <label class="flex items-center gap-2 cursor-pointer text-sm sm:text-base">
                                    <input type="radio" id="gratis" name="tipe_tarif" value="gratis" class="form-radio text-amber-500 h-4 w-4"
                                           <?= (isset($_POST['tipe_tarif']) && $_POST['tipe_tarif'] === 'gratis') ? 'checked' : '' ?>>
                                    Gratis
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer text-sm sm:text-base">
                                    <input type="radio" id="berbayar" name="tipe_tarif" value="berbayar" class="form-radio text-amber-500 h-4 w-4"
                                           <?= (!isset($_POST['tipe_tarif']) || $_POST['tipe_tarif'] === 'berbayar') ? 'checked' : '' ?>>
                                    Berbayar
                                </label>
                            </div>
                            <div class="flex items-center gap-2 text-xs sm:text-sm text-gray-600 sm:border-l sm:pl-4">
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" id="hari" name="periode" value="hari" class="form-radio text-amber-500 h-4 w-4" 
                                        <?= (!isset($_POST['periode']) || $_POST['periode'] === 'hari') ? 'checked' : '' ?>> Hari
                                </label>
                                <span>/</span>
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" id="jam" name="periode" value="jam" class="form-radio text-amber-500 h-4 w-4"
                                        <?= (isset($_POST['periode']) && $_POST['periode'] === 'jam') ? 'checked' : '' ?>> Jam
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" id="tarifSection">
                        <label class="block font-semibold mb-2 text-sm sm:text-base">Tarif Sewa Internal/Eksternal <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <input type="number" name="tarif_internal" 
                                       class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm sm:text-base" 
                                       placeholder="Tarif Internal" required
                                       value="<?= htmlspecialchars($_POST['tarif_internal'] ?? '') ?>">
                                <div class="mt-2 text-xs sm:text-sm text-gray-600">
                                    <span class="font-medium">Internal IT PLN</span>
                                </div>
                            </div>
                            <div>
                                <input type="number" name="tarif_eksternal" 
                                       class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm sm:text-base" 
                                       placeholder="Tarif Eksternal" required
                                       value="<?= htmlspecialchars($_POST['tarif_eksternal'] ?? '') ?>">
                                <div class="mt-2 text-xs sm:text-sm text-gray-600">
                                    <span class="font-medium">Eksternal</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="keterangan" class="block font-semibold mb-2 text-sm sm:text-base">Keterangan <span class="text-red-500">*</span></label>
                        <textarea id="keterangan" name="keterangan" rows="4" 
                                 class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm sm:text-base" 
                                 placeholder="Isi Keterangan" required><?= htmlspecialchars($_POST['keterangan'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-end gap-3 mt-6 sm:mt-8 pt-4 border-t border-gray-200">
                <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-4 sm:px-6 py-2 sm:py-3 rounded-lg shadow transition-colors text-sm sm:text-base order-2 sm:order-1" onclick="resetForm()">
                    Batal
                </button>
                <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-gray-900 font-semibold px-4 sm:px-6 py-2 sm:py-3 rounded-lg shadow transition-colors text-sm sm:text-base order-1 sm:order-2">
                    Tambah Data
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
            window.location.href = 'datafasilitas_admin.php';
        }
    });
}

// === Validasi Form dengan SweetAlert ===
document.getElementById('tambahForm').addEventListener('submit', function(e) {
    const isBerbayar = document.getElementById('berbayar').checked;
    let isValid = true;
    
    this.querySelectorAll('input, textarea').forEach(input => input.style.borderColor = '');

    this.querySelectorAll('[required]').forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.style.borderColor = '#ef4444';
        }
    });

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
    
    if (!isValid) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Form Belum Lengkap',
            text: 'Mohon lengkapi semua field yang diperlukan sebelum menyimpan.',
            confirmButtonColor: '#f59e0b'
        });
    }
});

// === Tipe Tarif Toggle ===
function toggleTarifSection() {
    const isBerbayar = document.getElementById('berbayar').checked;
    const tarifSection = document.getElementById('tarifSection');
    const tarifInputs = tarifSection.querySelectorAll('input[type="number"]');
    
    if (isBerbayar) {
        tarifSection.style.display = 'block';
        tarifInputs.forEach(input => {
            input.disabled = false;
            input.required = true;
            if (input.value === '0') input.value = '';
        });
    } else {
        tarifSection.style.display = 'none';
        tarifInputs.forEach(input => {
            input.disabled = true;
            input.required = false;
            input.value = '0';
        });
    }
}

document.getElementById('gratis').addEventListener('change', toggleTarifSection);
document.getElementById('berbayar').addEventListener('change', toggleTarifSection);

// === Inisialisasi ===
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    const overlay = document.getElementById('sidebarOverlay');
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

    if (overlay) overlay.addEventListener('click', toggleSidebar);

    toggleTarifSection();
});
</script>

</body>
</html>