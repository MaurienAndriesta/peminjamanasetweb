<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama       = $_POST['nama'] ?? '';
    $status     = $_POST['status'] ?? '';
    $kategori   = $_POST['kategori'] ?? '';
    $subpilihan = $_POST['subpilihan'] ?? '';
    $fakultas   = $_POST['fakultas'] ?? '';
    $laboratorium = $_POST['laboratorium'] ?? '';
    $tanggal    = $_POST['tanggal'] ?? '';
    $jam_mulai  = $_POST['jam_mulai'] ?? '';
    $jam_selesai= $_POST['jam_selesai'] ?? '';
    $peserta    = $_POST['peserta'] ?? '';
    $tarif      = $_POST['tarif'] ?? '';
    $tarif_lab  = $_POST['tarif_lab'] ?? '';
    $agenda     = $_POST['agenda'] ?? '';

    if (!empty($_FILES['surat']['name'])) {
        $target = "uploads/" . basename($_FILES['surat']['name']);
        move_uploaded_file($_FILES['surat']['tmp_name'], $target);
    }

    echo "<div class='p-4 bg-green-100 text-green-700 text-center animate-fadeIn'>Form berhasil dikirim!</div>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pengajuan Peminjaman Aset</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Fade-in animasi lembut */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .animate-fadeIn {
      animation: fadeIn 0.6s ease forwards;
    }

    /* Efek hover interaktif pada elemen form */
    input:hover, select:hover, textarea:hover {
      transform: scale(1.02);
      transition: all 0.2s ease-in-out;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      border-color: #3b82f6;
    }

    /* Hover tombol */
    button {
      transition: all 0.3s ease;
    }
    button:hover {
      transform: scale(1.05);
      box-shadow: 0 6px 14px rgba(0,0,0,0.2);
    }
  </style>
</head>
<body class="bg-[#D1E5EA] min-h-screen flex justify-center items-start py-10">

  <div class="bg-white shadow-xl rounded-lg w-full max-w-2xl animate-fadeIn">
    <!-- Header -->
    <div class="bg-gray-700 text-white text-center py-4 rounded-t-lg">
      <h1 class="text-lg font-bold">PENGAJUAN PEMINJAMAN ASET</h1>
      <p class="text-sm">Sistem Manajemen Aset Kampus Institut Teknologi PLN</p>
    </div>

    <!-- Form -->
    <form method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
      
      <!-- Nama -->
      <div>
        <label class="block font-medium">Nama Lengkap <span class="text-red-500">*</span></label>
        <input type="text" name="nama" class="mt-1 w-full border rounded-md p-2" required>
      </div>

      <!-- Status -->
      <div>
        <label class="block font-medium">Status Peminjam <span class="text-red-500">*</span></label>
        <select id="status" name="status" class="mt-1 w-full border rounded-md p-2" required>
          <option value="">Pilih Status</option>
          <option value="Mahasiswa">Mahasiswa</option>
          <option value="Umum">Umum</option>
        </select>
      </div>

      
      <!-- Kategori -->
      <div>
        <label class="block font-medium">Kategori <span class="text-red-500">*</span></label>
        <select id="kategori" name="kategori" class="mt-1 w-full border rounded-md p-2" required>
          <option value="">Pilih Kategori</option>
          <option value="Ruang Multiguna">Ruang Multiguna</option>
          <option value="Fasilitas">Fasilitas</option>
          <option value="Usaha">Usaha</option>
          <option value="Laboratorium">Laboratorium</option>
        </select>
      </div>

      <!-- Ruangan Multiguna -->
      <div id="ruanganWrapper" class="hidden">
        <label class="block font-medium">Pilih Ruangan Multiguna <span class="text-red-500">*</span></label>
        <select name="subpilihan" class="mt-1 w-full border rounded-md p-2">
          <option value="">Pilih Ruangan</option>
          <option value="Aula Utama">Ruang Rapat</option>
          <option value="Ruang Seminar">Auditorium</option>
          <option value="Ruang Kelas Besar">Ruang Kelas</option>
        </select>
      </div>

      <!-- Fasilitas -->
      <div id="fasilitasWrapper" class="hidden">
        <label class="block font-medium">Pilih Fasilitas <span class="text-red-500">*</span></label>
        <select name="subpilihan" class="mt-1 w-full border rounded-md p-2">
          <option value="">Pilih Fasilitas</option>
          <option value="Proyektor">Proyektor</option>
          <option value="Sound System">Sound System</option>
          <option value="Kendaraan">Kendaraan</option>
        </select>
      </div>

      <!-- Usaha -->
      <div id="usahaWrapper" class="hidden">
        <label class="block font-medium">Pilih Usaha <span class="text-red-500">*</span></label>
        <select name="subpilihan" class="mt-1 w-full border rounded-md p-2">
          <option value="">Pilih Usaha</option>
          <option value="Kantin">Kantin</option>
          <option value="Fotokopi">Fotokopi</option>
          <option value="Toko Buku">Toko Buku</option>
        </select>
      </div>

      <!-- Fakultas -->
      <div id="fakultasWrapper" class="hidden">
        <label class="block font-medium">Fakultas <span class="text-red-500">*</span></label>
        <select id="fakultas" name="fakultas" class="mt-1 w-full border rounded-md p-2">
          <option value="">Pilih Fakultas</option>
          <option value="FTBE">FTBE - Teknologi Dan Bisnis Energi</option>
          <option value="FTEN">FTEN - Telematika Energi</option>
          <option value="FTIK">FTIK - Teknologi Infrastruktur & Kewilayahan</option>
          <option value="FKET">FKET - Ketenagalistrikan & Energi Terbarukan</option>
        </select>
      </div>

      <!-- Laboratorium FTBE -->
      <div id="labFTBE" class="hidden">
        <label class="block font-medium">Laboratorium FTBE</label>
        <select name="laboratorium" class="mt-1 w-full border rounded-md p-2">
          <option value="">Pilih Laboratorium</option>
          <option value="PLTU">Lab. PLTU</option>
          <option value="Fenomena">Lab. Fenomena & Sistem Kontrol</option>
          <option value="Konversi Energi">Lab. Konversi Energi</option>
          <option value="Motor Listrik">Lab. Konversi Motor Listrik</option>
        </select>
      </div>

      <!-- Laboratorium FTEN -->
      <div id="labFTEN" class="hidden">
        <label class="block font-medium">Laboratorium FTEN</label>
        <select name="laboratorium" class="mt-1 w-full border rounded-md p-2">
          <option value="">Pilih Laboratorium</option>
          <option value="IR">Lab. Information Retrieval</option>
          <option value="SE">Lab. Software Engineering</option>
          <option value="Multimedia">Lab. Multimedia</option>
          <option value="Embedded">Lab. Embedded System</option>
        </select>
      </div>

      <!-- Tanggal -->
      <div>
        <label class="block font-medium">Tanggal Peminjaman <span class="text-red-500">*</span></label>
        <input type="date" name="tanggal" class="mt-1 w-full border rounded-md p-2" required>
      </div>

      <!-- Jam -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block font-medium">Jam Mulai <span class="text-red-500">*</span></label>
          <input type="time" name="jam_mulai" class="mt-1 w-full border rounded-md p-2" required>
        </div>
        <div>
          <label class="block font-medium">Jam Selesai <span class="text-red-500">*</span></label>
          <input type="time" name="jam_selesai" class="mt-1 w-full border rounded-md p-2" required>
        </div>
      </div>

      <!-- Jumlah Peserta -->
      <div>
        <label class="block font-medium">Jumlah Peserta<span class="text-red-500">*</span></label>
        <input type="number" name="peserta" class="mt-1 w-full border rounded-md p-2">
      </div>

      <!-- Tarif -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block font-medium">Tarif Sewa <span class="text-red-500">*</span></label>
          <select id="tarif" name="tarif" class="mt-1 w-full border rounded-md p-2" required>
            <option value="">Pilih</option>
            <option value="Mahasiswa">Mahasiswa</option>
            <option value="Internal">Internal</option>
            <option value="Eksternal">Eksternal</option>
          </select>
        </div>
        <div id="tarifLabWrapper" class="hidden">
          <label class="block font-medium">Tarif Sewa (Laboratorium)</label>
          <select name="tarif_lab" class="mt-1 w-full border rounded-md p-2">
            <option value="">Pilih</option>
            <option value="Mahasiswa">Mahasiswa</option>
            <option value="Internal">Internal</option>
            <option value="Eksternal">Eksternal</option>
          </select>
        </div>
      </div>

      <!-- Upload Surat -->
      <div>
        <label class="block font-medium">Surat Peminjaman<span class="text-red-500">*</span></label>
        <input type="file" name="surat" accept="application/pdf" class="mt-1 w-full border rounded-md p-2">
      </div>

      <!-- Agenda -->
      <div>
        <label class="block font-medium">Agenda</label>
        <textarea name="agenda" rows="3" class="mt-1 w-full border rounded-md p-2"></textarea>
      </div>

      <!-- Submit -->
      <div class="pt-4">
        <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded-md hover:bg-blue-600">
          Ajukan Peminjaman
        </button>
      </div>

    </form>
  </div>

  <script>
    const kategoriSelect = document.getElementById('kategori');
    const fakultasWrapper = document.getElementById('fakultasWrapper');
    const fakultasSelect = document.getElementById('fakultas');

    const ruanganWrapper = document.getElementById('ruanganWrapper');
    const fasilitasWrapper = document.getElementById('fasilitasWrapper');
    const usahaWrapper = document.getElementById('usahaWrapper');

    const labFTBE = document.getElementById('labFTBE');
    const labFTEN = document.getElementById('labFTEN');
    const tarifLabWrapper = document.getElementById('tarifLabWrapper');

    const statusSelect = document.getElementById('status');
    const tarifSelect = document.getElementById('tarif');
    const tarifLabSelect = document.querySelector('select[name="tarif_lab"]');

    kategoriSelect.addEventListener('change', function () {
      ruanganWrapper.classList.add('hidden');
      fasilitasWrapper.classList.add('hidden');
      usahaWrapper.classList.add('hidden');
      fakultasWrapper.classList.add('hidden');
      tarifLabWrapper.classList.add('hidden');
      labFTBE.classList.add('hidden');
      labFTEN.classList.add('hidden');

      if (this.value === 'Ruang Multiguna') ruanganWrapper.classList.remove('hidden');
      else if (this.value === 'Fasilitas') fasilitasWrapper.classList.remove('hidden');
      else if (this.value === 'Usaha') usahaWrapper.classList.remove('hidden');
      else if (this.value === 'Laboratorium') {
        fakultasWrapper.classList.remove('hidden');
        tarifLabWrapper.classList.remove('hidden');
      }
    });

    fakultasSelect.addEventListener('change', function () {
      labFTBE.classList.add('hidden');
      labFTEN.classList.add('hidden');
      if (this.value === 'FTBE') labFTBE.classList.remove('hidden');
      else if (this.value === 'FTEN') labFTEN.classList.remove('hidden');
    });

    statusSelect.addEventListener('change', function () {
      const status = this.value;
      tarifSelect.innerHTML = '';
      tarifLabSelect.innerHTML = '';

      if (status === 'Mahasiswa') {
        tarifSelect.innerHTML = '<option value="Mahasiswa" selected>Mahasiswa</option>';
        tarifSelect.setAttribute('disabled', 'disabled');
        tarifLabSelect.innerHTML = '<option value="Mahasiswa" selected>Mahasiswa</option>';
        tarifLabSelect.setAttribute('disabled', 'disabled');
      } else if (status === 'Umum') {
        tarifSelect.innerHTML = `
          <option value="">Pilih</option>
          <option value="Internal">Internal</option>
          <option value="Eksternal">Eksternal</option>
        `;
        tarifSelect.removeAttribute('disabled');
        tarifLabSelect.innerHTML = `
          <option value="">Pilih</option>
          <option value="Internal">Internal</option>
          <option value="Eksternal">Eksternal</option>
        `;
        tarifLabSelect.removeAttribute('disabled');
      } else {
        tarifSelect.innerHTML = '<option value="">Pilih</option>';
        tarifSelect.removeAttribute('disabled');
        tarifLabSelect.innerHTML = '<option value="">Pilih</option>';
        tarifLabSelect.removeAttribute('disabled');
      }
    });
  </script>

</body>
</html>
