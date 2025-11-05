<?php
// Include database configuration
require_once 'config/database.php';

// Initialize database connection
$db = new Database();

// Get fasilitas ID from URL parameter
$fasilitas_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($fasilitas_id <= 0) {
    header('Location: datafasilitas_admin.php');
    exit;
}

// Inisialisasi variabel untuk form data dan pesan
$fasilitas_data = [];
$errors = [];
$error_message = null;

// --- 1. Ambil data lama sebelum POST (untuk pre-fill atau fallback) ---
// Perhatian: Ada logika CREATE TABLE di kode asli yang tidak diperlukan di sini, saya hapus.
$db->query("SELECT * FROM fasilitas WHERE id = :id AND status = 'aktif'");
$db->bind(':id', $fasilitas_id);
try {
    $fetched_data = $db->single();
    if (!$fetched_data) {
        header('Location: datafasilitas_admin.php');
        exit;
    }
    $fasilitas_data = $fetched_data;
} catch (Exception $e) {
    $error_message = "Terjadi kesalahan saat mengambil data: " . $e->getMessage();
}


// --- 2. Processing form submission ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_fasilitas = trim($_POST['nama_fasilitas'] ?? '');
    $kapasitas = trim($_POST['kapasitas'] ?? '');
    $lokasi = trim($_POST['lokasi'] ?? '');
    $tipe_tarif = $_POST['tipe_tarif'] ?? 'berbayar';
    $tarif_internal = (float)($_POST['tarif_internal'] ?? 0);
    $tarif_eksternal = (float)($_POST['tarif_eksternal'] ?? 0);
    $keterangan = trim($_POST['keterangan'] ?? '');
    
    // Validation
    if (empty($nama_fasilitas)) $errors[] = "Nama fasilitas harus diisi";
    if (empty($kapasitas)) $errors[] = "Kapasitas harus diisi";
    if (empty($lokasi)) $errors[] = "Lokasi harus diisi";
    if (empty($keterangan)) $errors[] = "Keterangan harus diisi";
    
    // Handle Tarif
    if ($tipe_tarif === 'gratis') {
        $tarif_internal = 0;
        $tarif_eksternal = 0;
    } else {
        if ($tarif_internal <= 0) $errors[] = "Tarif internal harus lebih dari 0 jika berbayar";
        if ($tarif_eksternal <= 0) $errors[] = "Tarif eksternal harus lebih dari 0 jika berbayar";
    }

    // --- Handle image upload (Perbaikan Gambar) ---
    $gambar_name = $fasilitas_data['gambar']; // Default: pertahankan gambar lama
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
            // Proses upload gambar baru
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $file_ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $gambar_name_new = 'fasilitas_' . $fasilitas_id . '_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $gambar_name_new;
            
            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path)) {
                // Hapus gambar lama jika ada dan berhasil upload gambar baru
                if (!empty($fasilitas_data['gambar']) && file_exists($upload_dir . $fasilitas_data['gambar'])) {
                     unlink($upload_dir . $fasilitas_data['gambar']);
                }
                $gambar_name = $gambar_name_new; // Set nama gambar baru
            } else {
                $errors[] = "Gagal mengupload gambar baru";
            }
        }
    }
    
    // Jika ada error, tampilkan error dan isi form dengan data POST yang gagal
    if (!empty($errors)) {
        $error_message = implode("<br>", $errors);
        // Fallback data form yang sudah diisi
        $fasilitas_data = array_merge($fasilitas_data, [
            'nama' => $nama_fasilitas,
            'kapasitas' => $kapasitas,
            'lokasi' => $lokasi,
            'tarif_internal' => $tarif_internal,
            'tarif_eksternal' => $tarif_eksternal,
            'keterangan' => $keterangan,
            'gambar' => $gambar_name // Gunakan gambar name yang dipertahankan/baru
        ]);
    } else {
        try {
            // Prepare update query
            $update_fields = "nama = :nama, kapasitas = :kapasitas, lokasi = :lokasi, 
                              tarif_internal = :tarif_internal, tarif_eksternal = :tarif_eksternal, 
                              keterangan = :keterangan, updated_at = NOW()";
            
            if (!empty($gambar_name)) {
                $update_fields .= ", gambar = :gambar";
            }
            
            $db->query("UPDATE fasilitas SET $update_fields WHERE id = :id");
            $db->bind(':nama', $nama_fasilitas);
            $db->bind(':kapasitas', $kapasitas);
            $db->bind(':lokasi', $lokasi);
            $db->bind(':tarif_internal', $tarif_internal);
            $db->bind(':tarif_eksternal', $tarif_eksternal);
            $db->bind(':keterangan', $keterangan);
            $db->bind(':id', $fasilitas_id);
            
            if (!empty($gambar_name)) {
                $db->bind(':gambar', $gambar_name);
            }
            
            if ($db->execute()) {
                header("Location: datafasilitas_admin.php?status=success_edit");
                exit;
            } else {
                $error_message = "Gagal memperbarui data fasilitas";
            }
        } catch (Exception $e) {
            $error_message = "Terjadi kesalahan: " . $e->getMessage();
        }
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
                        <label for="nama_fasilitas" class="block font-semibold mb-2">Fasilitas : <span class="text-red-500">*</span></label>
                        <input type="text" id="nama_fasilitas" name="nama_fasilitas" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                               placeholder="Isi Nama Fasilitas" required 
                               value="<?= htmlspecialchars($fasilitas_data['nama'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="kapasitas" class="block font-semibold mb-2">Kapasitas : <span class="text-red-500">*</span></label>
                        <input type="text" id="kapasitas" name="kapasitas" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                               placeholder="Isi Kapasitas" required
                               value="<?= htmlspecialchars($fasilitas_data['kapasitas'] ?? '') ?>">
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="form-group">
                        <label for="lokasi" class="block font-semibold mb-2">Lokasi : <span class="text-red-500">*</span></label>
                        <input type="text" id="lokasi" name="lokasi" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                               placeholder="Isi Lokasi" required
                               value="<?= htmlspecialchars($fasilitas_data['lokasi'] ?? '') ?>">
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
                             <div class="flex items-center gap-2 text-sm text-gray-600 border-l pl-4">
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" id="hari" name="periode" value="hari" class="form-radio text-amber-500 h-4 w-4" checked> Hari
                                </label>
                                <span>/</span>
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" id="jam" name="periode" value="jam" class="form-radio text-amber-500 h-4 w-4"> Jam
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" id="tarifSection">
                        <label class="block font-semibold mb-2">Tarif Sewa Internal/Eksternal IT PLN : <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <input type="number" name="tarif_internal" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                                       placeholder="Isi Tarif Internal" 
                                       value="<?= isset($fasilitas_data['tarif_internal']) && $fasilitas_data['tarif_internal'] > 0 ? $fasilitas_data['tarif_internal'] : '' ?>">
                                <div class="flex items-center justify-start mt-2 text-sm text-gray-600">
                                    <span class="font-medium">Internal</span>
                                </div>
                            </div>
                            <div>
                                <input type="number" name="tarif_eksternal" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                                       placeholder="Isi Tarif Eksternal" 
                                       value="<?= isset($fasilitas_data['tarif_eksternal']) && $fasilitas_data['tarif_eksternal'] > 0 ? $fasilitas_data['tarif_eksternal'] : '' ?>">
                                <div class="flex items-center justify-start mt-2 text-sm text-gray-600">
                                    <span class="font-medium">Eksternal</span>
                                </div>
                            </div>
                        </div>
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