<?php

// KONFIGURASI DATABASE

$host     = "localhost";     
$username = "root";          
$password = "";              
$database = "peminjamanaset"; 


// MEMBUAT KONEKSI
$koneksi = new mysqli($host, $username, $password, $database);


// CEK KONEKSI
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
} else {
    // echo "Koneksi berhasil"; // bisa diaktifkan untuk testing
}
?>
