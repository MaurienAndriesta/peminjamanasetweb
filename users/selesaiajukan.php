<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pengajuan Berhasil</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#D1E5EA] min-h-screen flex items-center justify-center">
  <div class="bg-white shadow-lg rounded-lg p-8 text-center w-full max-w-md animate-fadeIn">
    <h2 class="text-2xl font-bold text-green-600 mb-3">✅ Form Berhasil Dikirim!</h2>
    <p class="text-gray-600 mb-6">Terima kasih telah mengajukan peminjaman. Kami akan segera memproses permintaan Anda.</p>

    <div class="flex flex-col gap-3">
      <a href="status.php" 
         class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md shadow-md transition-all duration-300">
        Lihat Status Peminjaman
      </a>
      <a href="dashboarduser.php" 
         class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded-md shadow-md transition-all duration-300">
        Kembali ke Beranda
      </a>
    </div>
  </div>

  <style>
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .animate-fadeIn { animation: fadeIn 0.6s ease forwards; }
  </style>
</body>
</html>
