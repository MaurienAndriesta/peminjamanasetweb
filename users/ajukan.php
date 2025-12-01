<?php
session_start();

// Ambil Parameter dari URL (jika ada)
$pre_kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$pre_aset     = isset($_GET['aset']) ? $_GET['aset'] : '';
$pre_fakultas = isset($_GET['fakultas']) ? $_GET['fakultas'] : '';
require_once '../koneksi.php';
$db = $koneksi;

// Set Timezone agar sesuai waktu lokal (WIB)
date_default_timezone_set('Asia/Jakarta');

// ==================== AMBIL DATA DARI DATABASE ====================
$ruang_multiguna = [];
$res = $db->query("SELECT nama FROM tbl_ruangmultiguna WHERE status = 'Tersedia'");
if($res){ while($row = $res->fetch_assoc()){ $ruang_multiguna[] = $row['nama']; } }

$fasilitas = [];
$res = $db->query("SELECT nama FROM tbl_fasilitas WHERE status = 'Tersedia'");
if($res){ while($row = $res->fetch_assoc()){ $fasilitas[] = $row['nama']; } }

$usaha = [];
$res = $db->query("SELECT nama FROM tbl_usaha WHERE status = 'Tersedia'");
if($res){ while($row = $res->fetch_assoc()){ $usaha[] = $row['nama']; } }

$laboratorium = ['FTBE'=>[], 'FTEN'=>[], 'FTIK'=>[], 'FKET'=>[]];
$fakultas_tabel = ['FTBE'=>'labftbe', 'FTEN'=>'labften', 'FTIK'=>'labftik', 'FKET'=>'labfket'];
foreach($fakultas_tabel as $fak => $tbl){
    $res = $db->query("SELECT nama FROM $tbl"); 
    if($res){ while($row = $res->fetch_assoc()){ $laboratorium[$fak][] = $row['nama']; } }
}

// ==================== PROSES FORM ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id            = $_SESSION['user_id'] ?? 0;
    $nama               = $_POST['nama'] ?? '';
    $status_peminjam    = $_POST['status'] ?? '';
    $kategori           = $_POST['kategori'] ?? '';
    $subpilihan         = $_POST['subpilihan'] ?? '';
    $fakultas           = $_POST['fakultas'] ?? '';
    $laboratorium_input = $_POST['laboratorium'] ?? '';
    $tanggal_peminjaman = $_POST['tanggal'] ?? '';
    $tanggal_selesai    = $_POST['tanggal_selesai'] ?? '';
    $jam_mulai          = $_POST['jam_mulai'] ?? '';
    $jam_selesai        = $_POST['jam_selesai'] ?? '';
    $jumlah_peserta     = $_POST['peserta'] ?? 0;
    
    // Mengirim Angka (1=Internal, 2=Eksternal, 0=Mhs)
    $tarif_sewa         = $_POST['tarif'] ?? 0; 
    
    $agenda             = $_POST['agenda'] ?? '';
    $status             = "Menunggu";

    // --- VALIDASI ---
    $today = date('Y-m-d');
    if ($tanggal_peminjaman < $today) {
        echo "<script>alert('Gagal! Tanggal peminjaman tidak boleh lewat.'); window.history.back();</script>"; exit;
    }
    if ($tanggal_selesai < $tanggal_peminjaman) {
        echo "<script>alert('Gagal! Tanggal selesai salah.'); window.history.back();</script>"; exit;
    }
    if ($status_peminjam === 'Umum' && empty($tarif_sewa)) {
        echo "<script>alert('Gagal! Status UMUM wajib memilih Tarif.'); window.history.back();</script>"; exit;
    }

    if($kategori === "Laboratorium") {
        $subpilihan = $laboratorium_input;
    }

    $surat_peminjaman = '';
    if (!empty($_FILES['surat']['name'])) {
        $dir = "../uploads/";
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $surat_peminjaman = time() . "_" . $_FILES['surat']['name'];
        move_uploaded_file($_FILES['surat']['tmp_name'], $dir . $surat_peminjaman);
    }

    $sql = "INSERT INTO tbl_pengajuan 
    (user_id, nama_peminjam, status_peminjam, kategori, subpilihan,
     fakultas, laboratorium, tanggal_peminjaman, tanggal_selesai,
     jam_mulai, jam_selesai, jumlah_peserta,
     tarif_sewa, surat_peminjaman, agenda, status, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

    $stmt = $db->prepare($sql);
    
    // --- PERBAIKAN DI SINI (Total 16 Karakter) ---
    // i = integer (angka), s = string (teks)
    // Urutan: 
    // 1.user_id(i), 2.nama(s), 3.status(s), 4.kategori(s), 5.sub(s), 
    // 6.fak(s), 7.lab(s), 8.tgl1(s), 9.tgl2(s), 10.jam1(s), 
    // 11.jam2(s), 12.peserta(i), 13.tarif(i), 14.surat(s), 15.agenda(s), 16.status(s)
    
    $stmt->bind_param("issssssssssiisss", 
                      $user_id, $nama, $status_peminjam, $kategori, $subpilihan, 
                      $fakultas, $laboratorium_input, $tanggal_peminjaman, $tanggal_selesai, 
                      $jam_mulai, $jam_selesai, $jumlah_peserta, $tarif_sewa, $surat_peminjaman, $agenda, $status);

    if ($stmt->execute()) {
        header("Location: selesaiajukan.php");
        exit;
    } else {
        echo "ERROR SQL: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Pengajuan Peminjaman Aset</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#D1E5EA] min-h-screen flex justify-center items-start py-10">
<div class="bg-white shadow-xl rounded-lg w-full max-w-2xl">
    <div class="bg-gray-700 text-white text-center py-4 rounded-t-lg">
        <h1 class="text-lg font-bold">PENGAJUAN PEMINJAMAN ASET</h1>
    </div>

    <form method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
        <label class="block font-medium">Nama Lengkap <span style="color:red">*</span></label>
        <input type="text" name="nama" class="border rounded p-2 w-full" required>

        <label class="block font-medium">Status Peminjam <span style="color:red">*</span></label>
        <select id="status" name="status" class="mt-1 w-full border rounded-md p-2" required>
            <option value="">Pilih Status</option>
            <option value="Mahasiswa">Mahasiswa</option>
            <option value="Umum">Umum</option>
        </select>

        <label class="block font-medium">Kategori <span style="color:red">*</span></label>
        <select id="kategori" name="kategori" class="mt-1 w-full border rounded-md p-2" required>
            <option value="">Pilih Kategori</option>
            <option value="Ruang Multiguna">Ruang Multiguna</option>
            <option value="Fasilitas">Fasilitas</option>
            <option value="Usaha">Usaha</option>
            <option value="Laboratorium">Laboratorium</option>
        </select>

        <div id="ruanganWrapper" class="hidden">
            <label class="block font-medium">Pilih Ruangan <span style="color:red">*</span></label>
            <select name="subpilihan" class="mt-1 w-full border rounded-md p-2">
                <option value="">Pilih Ruangan</option>
                <?php foreach($ruang_multiguna as $rm): ?>
                    <option value="<?= htmlspecialchars($rm) ?>"><?= htmlspecialchars($rm) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div id="fasilitasWrapper" class="hidden">
            <label class="block font-medium">Pilih Fasilitas <span style="color:red">*</span></label>
            <select name="subpilihan" class="mt-1 w-full border rounded-md p-2">
                <option value="">Pilih Fasilitas</option>
                <?php foreach($fasilitas as $f): ?>
                    <option value="<?= htmlspecialchars($f) ?>"><?= htmlspecialchars($f) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div id="usahaWrapper" class="hidden">
            <label class="block font-medium">Pilih Usaha <span style="color:red">*</span></label>
            <select name="subpilihan" class="mt-1 w-full border rounded-md p-2">
                <option value="">Pilih Usaha</option>
                <?php foreach($usaha as $u): ?>
                    <option value="<?= htmlspecialchars($u) ?>"><?= htmlspecialchars($u) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div id="fakultasWrapper" class="hidden">
            <label class="block font-medium">Pilih Fakultas</label>
            <select id="fakultas" name="fakultas" class="mt-1 w-full border rounded-md p-2">
                <option value="">Pilih Fakultas</option>
                <?php foreach($fakultas_tabel as $f => $tbl): ?>
                    <option value="<?= $f ?>"><?= $f ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div id="laboratoriumWrapper" class="hidden">
            <label class="block font-medium">Laboratorium</label>
            <select id="laboratorium" name="laboratorium" class="mt-1 w-full border rounded-md p-2">
                <option value="">Pilih Laboratorium</option>
            </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block font-medium">Tanggal Peminjaman <span style="color:red">*</span></label>
                <input type="date" id="tanggal_mulai" name="tanggal" class="mt-1 w-full border rounded-md p-2" required>
            </div>
            <div>
                <label class="block font-medium">Tanggal Selesai <span style="color:red">*</span></label>
                <input type="date" id="tanggal_selesai" name="tanggal_selesai" class="mt-1 w-full border rounded-md p-2" required>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block font-medium">Jam Mulai <span style="color:red">*</span></label>
                <input type="time" name="jam_mulai" class="mt-1 w-full border rounded-md p-2" required>
            </div>
            <div>
                <label class="block font-medium">Jam Selesai <span style="color:red">*</span></label>
                <input type="time" name="jam_selesai" class="mt-1 w-full border rounded-md p-2" required>
            </div>
        </div>

        <label class="block font-medium">Jumlah Peserta</label>
        <input type="number" name="peserta" class="mt-1 w-full border rounded-md p-2">

        <label class="block font-medium">Tarif Sewa <span style="color:red">*</span></label>
        <select id="tarif" name="tarif" class="mt-1 w-full border rounded-md p-2" required>
            <option value="">Pilih Status Dulu</option>
        </select>

        <label class="block font-medium">Surat Peminjaman</label>
        <input type="file" name="surat" accept="application/pdf" class="mt-1 w-full border rounded-md p-2">

        <label class="block font-medium">Agenda</label>
        <textarea name="agenda" rows="3" class="mt-1 w-full border rounded-md p-2"></textarea>

        <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded-md hover:bg-blue-600">Ajukan Peminjaman</button>
    </form>
</div>

<script>
// --- LOGIKA TANGGAL ---
const today = new Date().toISOString().split('T')[0];
const inputMulai = document.getElementById('tanggal_mulai');
const inputSelesai = document.getElementById('tanggal_selesai');
inputMulai.setAttribute('min', today);
inputSelesai.setAttribute('min', today);
inputMulai.addEventListener('change', function() { inputSelesai.setAttribute('min', this.value); });

// --- LOGIKA STATUS & TARIF ---
const statusSelect = document.getElementById('status');
const tarifSelect = document.getElementById('tarif');

statusSelect.addEventListener('change', function(){
    const status = this.value;
    if(status === 'Mahasiswa'){
        tarifSelect.innerHTML = '<option value="0" selected>Free (Mahasiswa)</option>';
        tarifSelect.removeAttribute('disabled');
    } 
    else if(status === 'Umum'){
        tarifSelect.removeAttribute('disabled');
        tarifSelect.innerHTML = `
            <option value="">-- Pilih Jenis Tarif --</option>
            <option value="1">Internal</option>
            <option value="2">Eksternal</option>
        `;
    } 
    else {
        tarifSelect.innerHTML = '<option value="">Pilih Status Dulu</option>';
        tarifSelect.setAttribute('disabled', 'disabled');
    }
});

// --- LOGIKA KATEGORI & WRAPPER ---
const kategoriSelect = document.getElementById('kategori');
const wrappers = {
    'Ruang Multiguna': document.getElementById('ruanganWrapper'),
    'Fasilitas': document.getElementById('fasilitasWrapper'),
    'Usaha': document.getElementById('usahaWrapper'),
    'Laboratorium': document.getElementById('fakultasWrapper')
};
const labWrapper = document.getElementById('laboratoriumWrapper');

kategoriSelect.addEventListener('change', function(){
    // Sembunyikan semua
    Object.values(wrappers).forEach(el => el.classList.add('hidden'));
    labWrapper.classList.add('hidden');
    // Disable semua input subpilihan
    document.querySelectorAll('[name="subpilihan"], [name="fakultas"]').forEach(el => el.disabled = true);

    const val = this.value;
    if(wrappers[val]){
        wrappers[val].classList.remove('hidden');
        const select = wrappers[val].querySelector('select');
        if(select) select.disabled = false;
    }
});

// --- LOGIKA LABORATORIUM ---
const laboratoriumData = <?php echo json_encode($laboratorium); ?>;
const fakultasSelect = document.getElementById('fakultas');
const labSelect = document.getElementById('laboratorium');

fakultasSelect.addEventListener('change', function(){
    labSelect.innerHTML = '<option value="">Pilih Laboratorium</option>';
    const fak = this.value;
    if(fak && laboratoriumData[fak]){
        labWrapper.classList.remove('hidden');
        laboratoriumData[fak].forEach(lab => {
            let opt = document.createElement('option');
            opt.value = lab;
            opt.text = lab;
            labSelect.appendChild(opt);
        });
    } else {
        labWrapper.classList.add('hidden');
    }
});

// ==========================================
// === FITUR AUTOFILL (DARI HALAMAN SEBELUMNYA) ===
// ==========================================
document.addEventListener("DOMContentLoaded", function() {
    const preKategori = "<?= htmlspecialchars($pre_kategori) ?>";
    const preAset     = "<?= htmlspecialchars($pre_aset) ?>";
    const preFakultas = "<?= htmlspecialchars($pre_fakultas) ?>";

    if (preKategori) {
        // 1. Set Kategori
        kategoriSelect.value = preKategori;
        
        // 2. Trigger Event Change agar wrapper muncul
        kategoriSelect.dispatchEvent(new Event('change'));

        // Beri sedikit jeda agar DOM terupdate
        setTimeout(() => {
            if (preKategori === 'Laboratorium' && preFakultas) {
                // Kasus Laboratorium
                fakultasSelect.value = preFakultas;
                // Trigger change fakultas agar dropdown nama lab muncul
                fakultasSelect.dispatchEvent(new Event('change'));
                
                // Set Nama Lab
                setTimeout(() => {
                    labSelect.value = preAset;
                }, 100);

            } else {
                // Kasus Ruang / Fasilitas / Usaha
                const activeWrapper = wrappers[preKategori];
                if (activeWrapper) {
                    const subSelect = activeWrapper.querySelector('select');
                    if (subSelect) {
                        subSelect.value = preAset;
                    }
                }
            }
        }, 100);
    }
});
</script>
</body>
</html>