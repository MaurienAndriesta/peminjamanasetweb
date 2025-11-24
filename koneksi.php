<?php
// =============================================================
// FILE: includes/koneksi.php
// Fungsi: Membuat koneksi database + mengatur session role admin & user
// =============================================================

// Konfigurasi database
$host     = "localhost";
$username = "root";
$password = "";
$database = "peminjamanaset";

// Membuat koneksi MySQLi
$koneksi = new mysqli($host, $username, $password, $database);

// Cek koneksi
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

// Mulai session (kalau belum dimulai)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// =============================================================
// Fungsi bantu untuk cek role login
// =============================================================

/**
 * Mengecek apakah user saat ini adalah admin
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Mengecek apakah user saat ini adalah user biasa
 */
function isUser() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'user';
}

/**
 * Fungsi redirect aman jika belum login
 */
function checkLogin() {
    if (!isset($_SESSION['role'])) {
        header("Location: ../login.php");
        exit;
    }
}
?>
