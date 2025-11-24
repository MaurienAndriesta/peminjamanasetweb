<?php
session_start();
require_once '../koneksi.php';
$db = $koneksi;

// ==================== AMBIL DATA DARI DATABASE ====================

// Ruang Multiguna
$ruang_multiguna = [];
$res = $db->query("SELECT nama FROM tbl_ruangmultiguna");
if($res){
    while($row = $res->fetch_assoc()){
        $ruang_multiguna[] = $row['nama'];
    }
}

// Fasilitas
$fasilitas = [];
$res = $db->query("SELECT nama FROM tbl_fasilitas");
if($res){
    while($row = $res->fetch_assoc()){
        $fasilitas[] = $row['nama'];
    }
}

// Usaha
$usaha = [];
$res = $db->query("SELECT nama FROM tbl_usaha");
if($res){
    while($row = $res->fetch_assoc()){
        $usaha[] = $row['nama'];
    }
}

// Laboratorium per fakultas
$laboratorium = [
    'FTBE'=>[], 'FTEN'=>[], 'FTIK'=>[], 'FKET'=>[]
];
$fakultas_tabel = [
    'FTBE'=>'labftbe',
    'FTEN'=>'labften',
    'FTIK'=>'labftik',
    'FKET'=>'labfket'
];

foreach($fakultas_tabel as $fak => $tbl){
    $res = $db->query("SELECT nama FROM $tbl");
    if($res){
        while($row = $res->fetch_assoc()){
            $laboratorium[$fak][] = $row['nama'];
        }
    }
}

// ==================== PROSES FORM ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id      = $_SESSION['user_id'] ?? 0;
    $nama         = $_POST['nama'] ?? '';
    $status_peminjam = $_POST['status'] ?? '';
    $kategori     = $_POST['kategori'] ?? '';
    $subpilihan   = $_POST['subpilihan'] ?? '';
    $fakultas     = $_POST['fakultas'] ?? '';
    $laboratorium_input = $_POST['laboratorium'] ?? '';
    $tanggal_peminjaman = $_POST['tanggal'] ?? '';
    $tanggal_selesai   = $_POST['tanggal_selesai'] ?? '';
    $jam_mulai    = $_POST['jam_mulai'] ?? '';
    $jam_selesai  = $_POST['jam_selesai'] ?? '';
    $jumlah_peserta = $_POST['peserta'] ?? 0;
    $tarif_sewa   = $_POST['tarif'] ?? '';
    $agenda       = $_POST['agenda'] ?? '';
    $status       = "Menunggu";

    // Jika kategori Laboratorium, subpilihan diisi dari laboratorium_input
    if($kategori === "Laboratorium") {
        $subpilihan = $laboratorium_input;
    }

    // upload file
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
    $stmt->bind_param(
        "issssssssssissss",
        $user_id,
        $nama,
        $status_peminjam,
        $kategori,
        $subpilihan,
        $fakultas,
        $laboratorium_input,
        $tanggal_peminjaman,
        $tanggal_selesai,
        $jam_mulai,
        $jam_selesai,
        $jumlah_peserta,
        $tarif_sewa,
        $surat_peminjaman,
        $agenda,
        $status
    );

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
<style>
.animate-fadeIn{animation:fadeIn 0.6s ease forwards}
@keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
input:hover, select:hover, textarea:hover{transform:scale(1.02);transition:all 0.2s ease-in-out;box-shadow:0 4px 10px rgba(0,0,0,0.1);border-color:#3b82f6}
button{transition:all 0.3s ease}
button:hover{transform:scale(1.05);box-shadow:0 6px 14px rgba(0,0,0,0.2)}
</style>
</head>
<body class="bg-[#D1E5EA] min-h-screen flex justify-center items-start py-10">
<div class="bg-white shadow-xl rounded-lg w-full max-w-2xl animate-fadeIn">
<div class="bg-gray-700 text-white text-center py-4 rounded-t-lg">
<h1 class="text-lg font-bold">PENGAJUAN PEMINJAMAN ASET</h1>
<p class="text-sm">Sistem Manajemen Aset Kampus Institut Teknologi PLN</p>
</div>

<form method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
<!-- Nama -->
<label class="block font-medium">Nama Lengkap <span style="color:red">*</span></label>
<input type="text" name="nama" class="border rounded p-2 w-full" required>

<!-- Status -->
<label class="block font-medium">Status Peminjam <span style="color:red">*</span></label>
<select id="status" name="status" class="mt-1 w-full border rounded-md p-2" required>
<option value="">Pilih Status</option>
<option value="Mahasiswa">Mahasiswa</option>
<option value="Umum">Umum</option>
</select>

<!-- Kategori -->
<label class="block font-medium">Kategori <span style="color:red">*</span></label>
<select id="kategori" name="kategori" class="mt-1 w-full border rounded-md p-2" required>
<option value="">Pilih Kategori</option>
<option value="Ruang Multiguna">Ruang Multiguna</option>
<option value="Fasilitas">Fasilitas</option>
<option value="Usaha">Usaha</option>
<option value="Laboratorium">Laboratorium</option>
</select>

<!-- Ruang Multiguna -->
<div id="ruanganWrapper" class="hidden">
<label class="block font-medium">Pilih Ruangan <span style="color:red">*</span></label>
<select name="subpilihan" class="mt-1 w-full border rounded-md p-2">
<option value="">Pilih Ruangan</option>
<?php foreach($ruang_multiguna as $rm): ?>
<option value="<?= htmlspecialchars($rm) ?>"><?= htmlspecialchars($rm) ?></option>
<?php endforeach; ?>
</select>
</div>

<!-- Fasilitas -->
<div id="fasilitasWrapper" class="hidden">
<label class="block font-medium">Pilih Fasilitas <span style="color:red">*</span></label>
<select name="subpilihan" class="mt-1 w-full border rounded-md p-2">
<option value="">Pilih Fasilitas</option>
<?php foreach($fasilitas as $f): ?>
<option value="<?= htmlspecialchars($f) ?>"><?= htmlspecialchars($f) ?></option>
<?php endforeach; ?>
</select>
</div>

<!-- Usaha -->
<div id="usahaWrapper" class="hidden">
<label class="block font-medium">Pilih Usaha <span style="color:red">*</span></label>
<select name="subpilihan" class="mt-1 w-full border rounded-md p-2">
<option value="">Pilih Usaha</option>
<?php foreach($usaha as $u): ?>
<option value="<?= htmlspecialchars($u) ?>"><?= htmlspecialchars($u) ?></option>
<?php endforeach; ?>
</select>
</div>

<!-- Fakultas -->
<div id="fakultasWrapper" class="hidden">
<label class="block font-medium">Pilih Fakultas <span style="color:red">*</span></label>
<select id="fakultas" name="fakultas" class="mt-1 w-full border rounded-md p-2">
<option value="">Pilih Fakultas</option>
<?php foreach($fakultas_tabel as $f => $tbl): ?>
<option value="<?= $f ?>"><?= $f ?></option>
<?php endforeach; ?>
</select>
</div>

<!-- Laboratorium -->
<div id="laboratoriumWrapper" class="hidden">
<label class="block font-medium">Laboratorium</label>
<select id="laboratorium" name="laboratorium" class="mt-1 w-full border rounded-md p-2">
<option value="">Pilih Laboratorium</option>
</select>
</div>

<!-- Tanggal & Jam -->
<div class="grid grid-cols-2 gap-4">
<div>
<label class="block font-medium">Tanggal Peminjaman <span style="color:red">*</span></label>
<input type="date" name="tanggal" class="mt-1 w-full border rounded-md p-2" required>
</div>
<div>
<label class="block font-medium">Tanggal Selesai <span style="color:red">*</span></label>
<input type="date" name="tanggal_selesai" class="mt-1 w-full border rounded-md p-2" required>
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

<!-- Peserta & Tarif -->
<label class="block font-medium">Jumlah Peserta <span style="color:red">*</span></label>
<input type="number" name="peserta" class="mt-1 w-full border rounded-md p-2">

<label class="block font-medium">Tarif Sewa <span style="color:red">*</span></label>
<select id="tarif" name="tarif" class="mt-1 w-full border rounded-md p-2" required>
<option value="">Pilih</option>
</select>

<label class="block font-medium">Surat Peminjaman <span style="color:red">*</span></label>
<input type="file" name="surat" accept="application/pdf" class="mt-1 w-full border rounded-md p-2">

<label class="block font-medium">Agenda <span style="color:red">*</span></label>
<textarea name="agenda" rows="3" class="mt-1 w-full border rounded-md p-2"></textarea>

<button type="submit" class="w-full bg-blue-500 text-white py-2 rounded-md hover:bg-blue-600">Ajukan Peminjaman</button>

</form>
</div>

<script>
// Wrapper
const kategoriSelect = document.getElementById('kategori');
const ruanganWrapper = document.getElementById('ruanganWrapper');
const fasilitasWrapper = document.getElementById('fasilitasWrapper');
const usahaWrapper = document.getElementById('usahaWrapper');
const fakultasWrapper = document.getElementById('fakultasWrapper');
const laboratoriumWrapper = document.getElementById('laboratoriumWrapper');

// Laboratorium data
const laboratoriumData = <?php echo json_encode($laboratorium); ?>;
const fakultasSelect = document.getElementById('fakultas');
const laboratoriumSelect = document.getElementById('laboratorium');

// Kategori change
kategoriSelect.addEventListener('change', function(){
    // hide semua wrapper
    ruanganWrapper.classList.add('hidden');
    fasilitasWrapper.classList.add('hidden');
    usahaWrapper.classList.add('hidden');
    fakultasWrapper.classList.add('hidden');
    laboratoriumWrapper.classList.add('hidden');

    // disable semua select subpilihan
    ruanganWrapper.querySelector('select').disabled = true;
    fasilitasWrapper.querySelector('select').disabled = true;
    usahaWrapper.querySelector('select').disabled = true;

    if(this.value==='Ruang Multiguna'){
        ruanganWrapper.classList.remove('hidden');
        ruanganWrapper.querySelector('select').disabled = false;
    } else if(this.value==='Fasilitas'){
        fasilitasWrapper.classList.remove('hidden');
        fasilitasWrapper.querySelector('select').disabled = false;
    } else if(this.value==='Usaha'){
        usahaWrapper.classList.remove('hidden');
        usahaWrapper.querySelector('select').disabled = false;
    } else if(this.value==='Laboratorium'){
        fakultasWrapper.classList.remove('hidden');
    }
});

// Fakultas change
fakultasSelect.addEventListener('change', function(){
    laboratoriumSelect.innerHTML = '<option value="">Pilih Laboratorium</option>';
    const fakultas = this.value;
    if(fakultas && laboratoriumData[fakultas]){
        laboratoriumData[fakultas].forEach(lab => {
            const option = document.createElement('option');
            option.value = lab;
            option.textContent = lab;
            laboratoriumSelect.appendChild(option);
        });
        laboratoriumWrapper.classList.remove('hidden');
    } else {
        laboratoriumWrapper.classList.add('hidden');
    }
});

// Status & tarif
const statusSelect = document.getElementById('status');
const tarifSelect = document.getElementById('tarif');
statusSelect.addEventListener('change', function(){
    const status = this.value;
    tarifSelect.innerHTML = '';
    if(status==='Mahasiswa'){
        tarifSelect.innerHTML='<option value="Free" selected>Free</option>';
        tarifSelect.setAttribute('disabled','disabled');
    } else if(status==='Umum'){
        tarifSelect.innerHTML=`<option value="">Pilih</option><option value="Internal">Internal</option><option value="Eksternal">Eksternal</option>`;
        tarifSelect.removeAttribute('disabled');
    } else {
        tarifSelect.innerHTML='<option value="">Pilih</option>';
        tarifSelect.setAttribute('disabled','disabled');
    }
});

// Validasi subpilihan sebelum submit
document.querySelector('form').addEventListener('submit', function(e){
    const kategori = kategoriSelect.value;
    let subpilihan = '';

    if(kategori==='Ruang Multiguna') subpilihan = ruanganWrapper.querySelector('select').value;
    else if(kategori==='Fasilitas') subpilihan = fasilitasWrapper.querySelector('select').value;
    else if(kategori==='Usaha') subpilihan = usahaWrapper.querySelector('select').value;
    else if(kategori==='Laboratorium') subpilihan = laboratoriumSelect.value;

    if(!subpilihan){
        e.preventDefault();
        alert('Silakan pilih subpilihan yang sesuai kategori!');
    }
});
</script>
</body>
</html>
