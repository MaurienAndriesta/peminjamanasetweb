<?php
/**
 * MOCK ADMIN SESSION - Simulasi Login
 * File ini agar bisa akses admin tanpa login
 * Nanti hapus file ini kalau sistem login sudah jadi
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set session admin otomatis
if (!isset($_SESSION['admin_id'])) {
    $_SESSION['admin_id'] = 1;
    $_SESSION['admin_nama'] = 'Admin Pengelola';
    $_SESSION['admin_email'] = 'admin@itpln.ac.id';
    $_SESSION['admin_role'] = 'admin';
    $_SESSION['logged_in'] = true;
}

// Function helper
function getAdminData() {
    return [
        'id' => $_SESSION['admin_id'],
        'nama' => $_SESSION['admin_nama'],
        'email' => $_SESSION['admin_email'],
        'no_telepon' => '081234567890',
        'instansi' => 'IT PLN',
        'role' => 'admin',
        'status' => 'aktif',
        'created_at' => '2024-01-01 00:00:00'
    ];
}

function isAdmin() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin';
}
?>