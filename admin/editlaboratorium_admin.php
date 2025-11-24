<?php
require_once '../koneksi.php';
$db = $koneksi;

$laboratorium_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($laboratorium_id <= 0) {
    header("Location: datalaboratorium_admin.php");
    exit;
}

/* ==========================================
   AMBIL DATA DARI 4 TABEL MENGGUNAKAN UNION
   ========================================== */

$sql = "
    SELECT *, 'labftik' AS table_name 
    FROM labftik WHERE id = $laboratorium_id

    UNION ALL

    SELECT *, 'labften' AS table_name 
    FROM labften WHERE id = $laboratorium_id

    UNION ALL

    SELECT *, 'labfket' AS table_name 
    FROM labfket WHERE id = $laboratorium_id

    UNION ALL

    SELECT *, 'labftbe' AS table_name 
    FROM labftbe WHERE id = $laboratorium_id
";

$q = mysqli_query($koneksi, $sql);

if (!$q || mysqli_num_rows($q) == 0) {
    header("Location: datalaboratorium_admin.php");
    exit;
}

$laboratorium_data = mysqli_fetch_assoc($q);
$source_table = $laboratorium_data['table_name'];
$id_column = "id"; // PK universal



/* ==========================================
   UPDATE DATA
   ========================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $kapasitas = mysqli_real_escape_string($koneksi, $_POST['kapasitas']);
    $lokasi = mysqli_real_escape_string($koneksi, $_POST['lokasi']);
    $status = mysqli_real_escape_string($koneksi, $_POST['status']);
    $tarif_laboratorium = mysqli_real_escape_string($koneksi, $_POST['tarif_sewa_laboratorium']);
    $tarif_peralatan = mysqli_real_escape_string($koneksi, $_POST['tarif_sewa_peralatan']);

    $gambar_name = $laboratorium_data['foto']; // nama kolom yang benar

    // Upload gambar baru
    if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === 0) {

        $allowed = ['jpg','jpeg','png'];
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {

            $upload_dir = "assets/images/";
            $gambar_new = "lab_" . $laboratorium_id . "_" . time() . "." . $ext;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $gambar_new)) {

                if (!empty($gambar_name) && file_exists($upload_dir . $gambar_name)) {
                    unlink($upload_dir . $gambar_name);
                }

                $gambar_name = $gambar_new;
            }
        }
    }

    // UPDATE clean & sesuai tabel kamu
    $update_sql = "
        UPDATE $source_table SET
            nama = '$nama',
            kapasitas = '$kapasitas',
            lokasi = '$lokasi',
            status = '$status',
            tarif_sewa_laboratorium = '$tarif_laboratorium',
            tarif_sewa_peralatan = '$tarif_peralatan',
            foto = '$gambar_name',
            updated_at = NOW()
        WHERE $id_column = $laboratorium_id
    ";

    if (mysqli_query($koneksi, $update_sql)) {
        header("Location: datalaboratorium_admin.php?status=success_edit");
        exit;
    } else {
        echo "Gagal update: " . mysqli_error($koneksi);
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

    <?php if (!empty($error_message)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 shadow-md" role="alert">
        ❌ <?php echo $error_message; ?>
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
    <label class="block font-semibold mb-2">Tarif Sewa Laboratorium : <span class="text-red-500">*</span></label>
    <div class="tarif-row">

        <!-- INPUT ASLI (hidden) -->
        <input type="hidden" 
               id="tarif_sewa_laboratorium" 
               name="tarif_sewa_laboratorium"
               value="<?= $laboratorium_data['tarif_sewa_laboratorium'] ?? '' ?>">

        <!-- INPUT UNTUK USER (formatted) -->
        <div class="input-group">
            <span class="input-prefix">Rp</span>
            <input type="text" 
                   id="tarif_laboratorium_display"
                   class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500"
                   value="<?= number_format($laboratorium_data['tarif_sewa_laboratorium'] ?? 0, 0, ',', '.') ?>">
        </div>

        <div class="inline-flex items-center px-4 py-3 border border-gray-300 rounded-lg bg-white">
            <span class="text-sm font-medium">perhari</span>
        </div>
        <input type="hidden" name="satuan_laboratorium" value="hari">
    </div>
</div>



<div class="form-group">
    <label class="block font-semibold mb-2">Tarif Sewa Peralatan : <span class="text-red-500">*</span></label>
    <div class="tarif-row">

        <input type="hidden" 
               id="tarif_sewa_peralatan" 
               name="tarif_sewa_peralatan"
               value="<?= $laboratorium_data['tarif_sewa_peralatan'] ?? '' ?>">

        <div class="input-group">
            <span class="input-prefix">Rp</span>
            <input type="text" 
                   id="tarif_peralatan_display"
                   class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500"
                   value="<?= number_format($laboratorium_data['tarif_sewa_peralatan'] ?? 0, 0, ',', '.') ?>">
        </div>

        <div class="inline-flex items-center px-4 py-3 border border-gray-300 rounded-lg bg-white">
            <span class="text-sm font-medium">perhari</span>
        </div>
        <input type="hidden" name="satuan_peralatan" value="hari">

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
                        <option value="tersedia" <?= ($laboratorium_data['status'] ?? '') === 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
                        <option value="tidak_tersedia" <?= ($laboratorium_data['status'] ?? '') === 'tidak_tersedia' ? 'selected' : '' ?>>Tidak Tersedia</option>
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