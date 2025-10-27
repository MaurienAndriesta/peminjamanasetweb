<?php
// Include database configuration
require_once 'config/database.php';

// Initialize database connection
$db = new Database();

// Initialize message variable
$error_message = null;

// Processing form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_usaha = trim($_POST['nama_usaha'] ?? '');
    $kapasitas = trim($_POST['kapasitas'] ?? '');
    $lokasi = trim($_POST['lokasi'] ?? '');
    $tipe_tarif = $_POST['tipe_tarif'] ?? 'berbayar';
    $periode = $_POST['periode'] ?? 'hari';
    $tarif_internal = (float)($_POST['tarif_internal'] ?? 0);
    $tarif_eksternal = (float)($_POST['tarif_eksternal'] ?? 0);
    $keterangan = trim($_POST['keterangan'] ?? '');
    
    // Validation
    $errors = [];
    if (empty($nama_usaha)) $errors[] = "Nama usaha harus diisi";
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
            $gambar_name = 'usaha_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
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
            
            $db->query("INSERT INTO usaha (nama, kapasitas, lokasi, tarif_internal, tarif_eksternal, keterangan, gambar, status) 
                        VALUES (:nama, :kapasitas, :lokasi, :tarif_internal, :tarif_eksternal, :keterangan, :gambar, 'aktif')");
            
            $db->bind(':nama', $nama_usaha);
            $db->bind(':kapasitas', $kapasitas);
            $db->bind(':lokasi', $lokasi);
            $db->bind(':tarif_internal', $tarif_internal);
            $db->bind(':tarif_eksternal', $tarif_eksternal);
            $db->bind(':keterangan', $keterangan); 
            $db->bind(':gambar', $gambar_name);
            
            if ($db->execute()) {
                header('Location: datausaha_admin.php?status=success_add');
                exit; 
            } else {
                $error_message = "Gagal menambahkan data usaha";
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
    <title>Tambah Data Usaha - Admin Pengelola</title>
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

    <?php if (isset($error_message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 shadow-md" role="alert">
            ❌ <?= $error_message ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-xl shadow-lg">
        <div class="flex items-center mb-6 border-b pb-4">
            <a href="datausaha_admin.php" class="bg-gray-700 hover:bg-gray-800 text-white p-3 rounded-lg mr-4 transition-colors">←</a>
            
            <div>
                <h1 class="text-2xl font-bold text-amber-700">Tambah Data</h1>
                <p class="text-gray-500 text-sm">Sewa Usaha</p>
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
                        <label for="nama_usaha" class="block font-semibold mb-2">Nama Usaha : <span class="text-red-500">*</span></label>
                        <input type="text" id="nama_usaha" name="nama_usaha" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                               placeholder="Isi Nama Usaha" required 
                               value="<?= htmlspecialchars($_POST['nama_usaha'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="kapasitas" class="block font-semibold mb-2">Kapasitas : <span class="text-red-500">*</span></label>
                        <input type="text" id="kapasitas" name="kapasitas" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                               placeholder="Isi Kapasitas" required
                               value="<?= htmlspecialchars($_POST['kapasitas'] ?? '') ?>">
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="form-group">
                        <label for="lokasi" class="block font-semibold mb-2">Lokasi : <span class="text-red-500">*</span></label>
                        <input type="text" id="lokasi" name="lokasi" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                               placeholder="Isi Lokasi" required
                               value="<?= htmlspecialchars($_POST['lokasi'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="block font-semibold mb-2">Tipe Tarif : <span class="text-red-500">*</span></label>
                        <div class="flex flex-col sm:flex-row sm:items-center sm:gap-6">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" id="gratis" name="tipe_tarif" value="gratis" class="form-radio text-amber-500 h-4 w-4"
                                       <?= (isset($_POST['tipe_tarif']) && $_POST['tipe_tarif'] === 'gratis') ? 'checked' : '' ?>>
                                Gratis
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" id="berbayar" name="tipe_tarif" value="berbayar" class="form-radio text-amber-500 h-4 w-4"
                                       <?= (!isset($_POST['tipe_tarif']) || $_POST['tipe_tarif'] === 'berbayar') ? 'checked' : '' ?>>
                                Berbayar
                            </label>
                             <div class="flex items-center gap-2 text-sm text-gray-600 border-t sm:border-t-0 sm:border-l pt-2 sm:pt-0 pl-0 sm:pl-4 mt-2 sm:mt-0">
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
                        <label class="block font-semibold mb-2">Tarif Sewa Internal/Eksternal IT PLN : <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <input type="number" name="tarif_internal" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                                       placeholder="Isi Tarif Internal" required
                                       value="<?= htmlspecialchars($_POST['tarif_internal'] ?? '') ?>">
                                <div class="flex items-center justify-start mt-2 text-sm text-gray-600">
                                    <span class="font-medium">Internal</span>
                                </div>
                            </div>
                            <div>
                                <input type="number" name="tarif_eksternal" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                                       placeholder="Isi Tarif Eksternal" required
                                       value="<?= htmlspecialchars($_POST['tarif_eksternal'] ?? '') ?>">
                                <div class="flex items-center justify-start mt-2 text-sm text-gray-600">
                                    <span class="font-medium">Eksternal</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="keterangan" class="block font-semibold mb-2">Keterangan : <span class="text-red-500">*</span></label>
                        <textarea id="keterangan" name="keterangan" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                                 placeholder="Isi Keterangan" required><?= htmlspecialchars($_POST['keterangan'] ?? '') ?></textarea>
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
        // Desktop: Toggle width
        sidebar.classList.toggle('lg:w-60');
        sidebar.classList.toggle('lg:w-16');
        main.classList.toggle('lg:ml-60');
        main.classList.toggle('lg:ml-16');
    } else {
        // Mobile: Toggle visibility
        sidebar.classList.toggle('translate-x-0');
        sidebar.classList.toggle('-translate-x-full');
    }

    const is_expanded = sidebar.classList.contains('lg:w-60') || sidebar.classList.contains('translate-x-0');
    
    if (typeof updateSidebarVisibility === 'function') {
        updateSidebarVisibility(is_expanded);
    }
    
    localStorage.setItem('sidebarStatus', is_expanded ? 'open' : 'collapsed');
}

// Image preview
function previewImage(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.getElementById('previewImg');
            const placeholder = document.getElementById('uploadPlaceholder');
            
            img.src = e.target.result;
            img.style.display = 'block';
            
            placeholder.classList.add('opacity-0');
            placeholder.classList.remove('opacity-100');
        };
        reader.readAsDataURL(file);
    }
}

// Toggle tarif section based on tipe tarif
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

// Reset form dan redirect ke halaman data ruangan 
function resetForm() {
    if (confirm('Apakah Anda yakin ingin membatalkan penambahan data? Perubahan tidak akan disimpan.')) {
        window.location.href = 'datausaha_admin.php'; 
    }
}

// Form validation (Frontend check)
document.getElementById('tambahForm').addEventListener('submit', function(e) {
    const isBerbayar = document.getElementById('berbayar').checked;
    let isValid = true;
    
    // Reset borders
    this.querySelectorAll('input, textarea').forEach(input => input.style.borderColor = '');
    
    // Check all required fields (minimal check)
    this.querySelectorAll('[required]').forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.style.borderColor = '#ef4444'; // red-500
        }
    });

    if (isBerbayar) {
        const tarifInternal = document.querySelector('input[name="tarif_internal"]');
        const tarifEksternal = document.querySelector('input[name="tarif_eksternal"]');
        
        if (tarifInternal && (!tarifInternal.value || parseFloat(tarifInternal.value) <= 0)) {
            isValid = false;
            tarifInternal.style.borderColor = '#ef4444'; // red-500
        }
        if (tarifEksternal && (!tarifEksternal.value || parseFloat(tarifEksternal.value) <= 0)) {
            isValid = false;
            tarifEksternal.style.borderColor = '#ef4444'; // red-500
        }
    }
    
    if (!isValid) {
        e.preventDefault();
        alert('Mohon lengkapi semua field yang diperlukan, terutama tarif jika "Berbayar" dipilih.');
    }
});

// Event listeners and Initialize page
document.getElementById('gratis').addEventListener('change', toggleTarifSection);
document.getElementById('berbayar').addEventListener('change', toggleTarifSection);

document.addEventListener('DOMContentLoaded', function() {
    // Set default values dan initialize tarif section
    const initialTipeTarif = document.querySelector('input[name="tipe_tarif"]:checked').value;
    
    if (initialTipeTarif === 'gratis') {
        document.getElementById('gratis').checked = true;
    } else {
        document.getElementById('berbayar').checked = true;
    }

    toggleTarifSection();
    
    // Logic untuk restore sidebar state
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