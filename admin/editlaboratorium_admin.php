<?php
// Include database configuration
require_once 'config/database.php';

// Initialize database connection
$db = new Database();

// Get laboratorium ID from URL parameter
$laboratorium_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($laboratorium_id <= 0) {
    header('Location: datalaboratorium_admin.php');
    exit;
}

// Inisialisasi variabel untuk form data dan pesan
$laboratorium_data = [];
$errors = [];
$error_message = null;

// --- 1. Ambil data lama sebelum POST (untuk pre-fill atau fallback) ---
$db->query("SELECT * FROM laboratorium WHERE id = :id AND status = 'aktif'");
$db->bind(':id', $laboratorium_id);
try {
    $fetched_data = $db->single();
    if (!$fetched_data) {
        header('Location: datalaboratorium_admin.php');
        exit;
    }
    $laboratorium_data = $fetched_data;
} catch (Exception $e) {
    $error_message = "Terjadi kesalahan saat mengambil data: " . $e->getMessage();
}


// --- 2. Processing form submission ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $kapasitas = trim($_POST['kapasitas'] ?? '');
    $lokasi = trim($_POST['lokasi'] ?? '');
    $fakultas = $_POST['fakultas'] ?? '';
    $status = $_POST['status'] ?? 'aktif';
    $keterangan = trim($_POST['keterangan'] ?? '');
    $tarif_laboratorium = trim($_POST['tarif_laboratorium'] ?? '');
    $tarif_peralatan = trim($_POST['tarif_peralatan'] ?? '');
    $satuan_laboratorium = $_POST['satuan_laboratorium'] ?? '';
    $satuan_peralatan = $_POST['satuan_peralatan'] ?? '';

    // Validation
    if (empty($nama)) $errors[] = "Nama laboratorium harus diisi";
    if (empty($kapasitas)) $errors[] = "Kapasitas harus diisi";
    if (empty($lokasi)) $errors[] = "Lokasi harus diisi";
    if (empty($fakultas)) $errors[] = "Fakultas harus dipilih";
    if (empty($keterangan)) $errors[] = "Keterangan harus diisi";
    if ((float)$tarif_laboratorium <= 0) $errors[] = "Tarif sewa laboratorium harus lebih dari 0";
    if ((float)$tarif_peralatan <= 0) $errors[] = "Tarif sewa peralatan harus lebih dari 0";
    if (empty($satuan_laboratorium)) $errors[] = "Satuan tarif laboratorium harus dipilih";
    if (empty($satuan_peralatan)) $errors[] = "Satuan tarif peralatan harus dipilih";

    // --- Handle image upload (Perbaikan Gambar) ---
    $gambar_name = $laboratorium_data['gambar']; // Default: pertahankan gambar lama
    $upload_dir = 'assets/images/';
    
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
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
            $gambar_name_new = 'laboratorium_' . $laboratorium_id . '_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $gambar_name_new;
            
            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path)) {
                // Hapus gambar lama jika ada dan berhasil upload gambar baru
                if (!empty($laboratorium_data['gambar']) && file_exists($upload_dir . $laboratorium_data['gambar'])) {
                     unlink($upload_dir . $laboratorium_data['gambar']);
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
        $laboratorium_data = array_merge($laboratorium_data, [
            'nama' => $nama, 'kapasitas' => $kapasitas, 'lokasi' => $lokasi, 'fakultas' => $fakultas, 
            'keterangan' => $keterangan, 'status' => $status,
            'tarif_laboratorium' => $tarif_laboratorium, 'tarif_peralatan' => $tarif_peralatan,
            'satuan_laboratorium' => $satuan_laboratorium, 'satuan_peralatan' => $satuan_peralatan,
            'gambar' => $gambar_name 
        ]);
    } else {
        try {
            // Prepare update query
            $update_fields = "nama = :nama, kapasitas = :kapasitas, lokasi = :lokasi, 
                              fakultas = :fakultas, keterangan = :keterangan, 
                              tarif_laboratorium = :tarif_laboratorium, satuan_laboratorium = :satuan_laboratorium,
                              tarif_peralatan = :tarif_peralatan, satuan_peralatan = :satuan_peralatan,
                              status = :status, updated_at = NOW()";

            if (!empty($gambar_name)) {
                $update_fields .= ", gambar = :gambar";
            }

            $db->query("UPDATE laboratorium SET $update_fields WHERE id = :id");
            $db->bind(':nama', $nama);
            $db->bind(':kapasitas', $kapasitas);
            $db->bind(':lokasi', $lokasi);
            $db->bind(':fakultas', $fakultas);
            $db->bind(':keterangan', $keterangan);
            $db->bind(':tarif_laboratorium', $tarif_laboratorium);
            $db->bind(':satuan_laboratorium', $satuan_laboratorium);
            $db->bind(':tarif_peralatan', $tarif_peralatan);
            $db->bind(':satuan_peralatan', $satuan_peralatan);
            $db->bind(':status', $status);
            $db->bind(':id', $laboratorium_id);

            if (!empty($gambar_name)) {
                $db->bind(':gambar', $gambar_name);
            }

            if ($db->execute()) {
                header("Location: datalaboratorium_admin.php?status=success_edit");
                exit;
            } else {
                $error_message = "Gagal memperbarui data laboratorium";
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
    <title>Edit Data Laboratorium - Admin Pengelola</title>
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
            <a href="datalaboratorium_admin.php" class="bg-gray-700 hover:bg-gray-800 text-white p-3 rounded-lg mr-4 transition-colors">←</a>
            
            <div>
                <h1 class="text-2xl font-bold text-amber-700">Edit Data Laboratorium: <?= htmlspecialchars($laboratorium_data['nama'] ?? 'N/A') ?></h1>
                <p class="text-gray-500 text-sm">Perbarui informasi laboratorium</p>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data" id="editForm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                
                <div class="space-y-6">
                    <?php if (!empty($laboratorium_data['gambar'])): ?>
                    <div class="bg-blue-50 text-blue-800 p-3 rounded-lg text-sm">
                        📷 Gambar saat ini: <?= htmlspecialchars($laboratorium_data['gambar']) ?>
                    </div>
                    <?php endif; ?>

                    <div class="image-upload-area w-full h-64 bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl flex items-center justify-center hover:border-amber-500">
                        <input type="file" id="fileInput" name="gambar" accept="image/*" onchange="previewImage(event)">
                        
                        <img id="previewImg" class="preview-image absolute inset-0" 
                            src="<?= !empty($laboratorium_data['gambar']) ? 'assets/images/' . htmlspecialchars($laboratorium_data['gambar']) : '' ?>" 
                            alt="Pratinjau Gambar"
                            onerror="this.style.display='none'">
                        
                        <div class="text-center text-gray-500 upload-placeholder" id="uploadPlaceholder">
                            <p class="font-semibold">Klik untuk <?= !empty($laboratorium_data['gambar']) ? 'mengubah' : 'menambah' ?> gambar</p>
                            <small>JPG, PNG maksimal 2MB</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="tarif_laboratorium" class="block font-semibold mb-2">Tarif Sewa Laboratorium : <span class="text-red-500">*</span></label>
                        <div class="tarif-row">
                            <div class="input-group">
                                <span class="input-prefix">Rp</span>
                                <input type="number" id="tarif_laboratorium" name="tarif_laboratorium" class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                                       placeholder="Isi Tarif Sewa Laboratorium" required min="0" step="0.01"
                                       value="<?= htmlspecialchars($laboratorium_data['tarif_laboratorium'] ?? '') ?>">
                            </div>
                            <select id="satuan_laboratorium" name="satuan_laboratorium" class="form-input px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" required>
                                <option value="jam" <?= ($laboratorium_data['satuan_laboratorium'] ?? '') === 'jam' ? 'selected' : '' ?>>/ Jam</option>
                                <option value="hari" <?= ($laboratorium_data['satuan_laboratorium'] ?? '') === 'hari' ? 'selected' : '' ?>>/ Hari</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="tarif_peralatan" class="block font-semibold mb-2">Tarif Sewa Peralatan : <span class="text-red-500">*</span></label>
                        <div class="tarif-row">
                            <div class="input-group">
                                <span class="input-prefix">Rp</span>
                                <input type="number" id="tarif_peralatan" name="tarif_peralatan" class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                                       placeholder="Isi Tarif Sewa Peralatan" required min="0" step="0.01"
                                       value="<?= htmlspecialchars($laboratorium_data['tarif_peralatan'] ?? '') ?>">
                            </div>
                            <select id="satuan_peralatan" name="satuan_peralatan" class="form-input px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" required>
                                <option value="jam" <?= ($laboratorium_data['satuan_peralatan'] ?? '') === 'jam' ? 'selected' : '' ?>>/ Jam</option>
                                <option value="hari" <?= ($laboratorium_data['satuan_peralatan'] ?? '') === 'hari' ? 'selected' : '' ?>>/ Hari</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="form-group">
                        <label for="nama" class="block font-semibold mb-2">Nama Laboratorium : <span class="text-red-500">*</span></label>
                        <input type="text" id="nama" name="nama" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                               placeholder="Isi Nama Laboratorium" required 
                               value="<?= htmlspecialchars($laboratorium_data['nama'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="kapasitas" class="block font-semibold mb-2">Kapasitas : <span class="text-red-500">*</span></label>
                        <input type="text" id="kapasitas" name="kapasitas" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                               placeholder="Isi Kapasitas" required
                               value="<?= htmlspecialchars($laboratorium_data['kapasitas'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="lokasi" class="block font-semibold mb-2">Lokasi : <span class="text-red-500">*</span></label>
                        <input type="text" id="lokasi" name="lokasi" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" 
                               placeholder="Isi Lokasi" required
                               value="<?= htmlspecialchars($laboratorium_data['lokasi'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="fakultas" class="block font-semibold mb-2">Fakultas : <span class="text-red-500">*</span></label>
                        <select id="fakultas" name="fakultas" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" required>
                            <option value="">-- Pilih Fakultas --</option>
                            <option value="FTEN" <?= ($laboratorium_data['fakultas'] ?? '') === 'FTEN' ? 'selected' : '' ?>>Fakultas Telematika Energi (FTEN)</option>
                            <option value="FTBE" <?= ($laboratorium_data['fakultas'] ?? '') === 'FTBE' ? 'selected' : '' ?>>Fakultas Teknologi dan Bisnis Energi (FTBE)</option>
                            <option value="FTIK" <?= ($laboratorium_data['fakultas'] ?? '') === 'FTIK' ? 'selected' : '' ?>>Fakultas Teknologi Infrastruktur dan Kewilayahan (FTIK)</option>
                            <option value="FKET" <?= ($laboratorium_data['fakultas'] ?? '') === 'FKET' ? 'selected' : '' ?>>Fakultas Ketenagalistrikan dan Energi Terbarukan (FKET)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status" class="block font-semibold mb-2">Status : <span class="text-red-500">*</span></label>
                        <select id="status" name="status" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" required>
                            <option value="aktif" <?= ($laboratorium_data['status'] ?? '') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="tidak_aktif" <?= ($laboratorium_data['status'] ?? '') === 'tidak_aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="keterangan" class="block font-semibold mb-2">Keterangan : <span class="text-red-500">*</span></label>
                        <textarea id="keterangan" name="keterangan" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500"
                                 placeholder="Isi Keterangan" required><?= htmlspecialchars($laboratorium_data['keterangan'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-gray-200">
                <a href="datalaboratorium_admin.php" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-6 py-3 rounded-lg shadow transition-colors"
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
            window.location.href = 'datalaboratorium_admin.php';
        }
    });
}

// === Validasi Form ===
document.getElementById('editForm').addEventListener('submit', function(e) {
    let isValid = true;

    this.querySelectorAll('input, select, textarea').forEach(input => input.style.borderColor = '');

    // Field yang harus diisi
    const requiredFields = ['nama', 'kapasitas', 'lokasi', 'fakultas', 'keterangan', 'tarif_laboratorium', 'tarif_peralatan', 'satuan_laboratorium', 'satuan_peralatan'];

    requiredFields.forEach(field => {
        const input = document.getElementById(field);
        if (input && !input.value.trim()) {
            isValid = false;
            input.style.borderColor = '#ef4444';
        }
    });

    // Cek tarif harus lebih dari 0
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
            icon: 'warning',
            title: 'Form Belum Lengkap',
            text: 'Mohon lengkapi semua field yang diperlukan dan pastikan tarif lebih dari 0.',
            confirmButtonColor: '#f59e0b'
        });
    }
});

// === Inisialisasi ===
document.addEventListener('DOMContentLoaded', function() {
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