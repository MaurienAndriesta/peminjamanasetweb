<?php
ob_start(); // Mencegah error header location
session_start();
include 'koneksi.php'; 

// 🔹 Cek & Buat Super Admin Default (Fitur Safety)
$cekSuperAdmin = $koneksi->query("SELECT COUNT(*) AS total FROM tbl_user WHERE role = 'super_admin'");
$dataAdmin = $cekSuperAdmin->fetch_assoc();

if ($dataAdmin['total'] == 0) {
    $fullname = "Super Admin ITPLN";
    $email = "superadmin@itpln.ac.id";
    $passwordHash = password_hash("password", PASSWORD_DEFAULT);
    $role = "super_admin";

    $insertAdmin = $koneksi->prepare("INSERT INTO tbl_user (fullname, email, password, role) VALUES (?, ?, ?, ?)");
    $insertAdmin->bind_param("ssss", $fullname, $email, $passwordHash, $role);
    $insertAdmin->execute();
}

// 🔹 Proses Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $error = '';

    // Ambil data user
    $stmt = $koneksi->prepare("SELECT * FROM tbl_user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            
            // Ambil Role User (Bersihkan spasi & huruf kecil)
            $role = strtolower(trim($user['role'])); 

            // ============================================================
            // 🔥 LOGIKA MODE PERBAIKAN (MAINTENANCE) - REVISI 🔥
            // ============================================================
            
            // 1. Cek Status Maintenance dari Database
            $is_maintenance = '0'; 
            $q_maint = $koneksi->query("SELECT nilai FROM tbl_pengaturan WHERE kunci = 'maintenance_mode'");
            if ($q_maint && $q_maint->num_rows > 0) {
                $is_maintenance = $q_maint->fetch_assoc()['nilai'];
            }

            // 2. LOGIKA BARU:
            // Blokir JIKA Maintenance Aktif (1) 
            // DAN Role BUKAN 'super_admin' 
            // DAN Role BUKAN 'admin'
            // (Artinya hanya User biasa yang diblokir)
            
            if ($is_maintenance == '1' && $role !== 'super_admin' && $role !== 'admin') {
                $error = "⚠️ <b>SISTEM DALAM PERBAIKAN</b><br>Mohon maaf, saat ini hanya Admin yang dapat login.";
            } 
            else {
                // === LOGIN BERHASIL (LANJUTKAN) ===
                
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['role'] = $user['role'];

                // Redirect Berdasarkan Role
                if ($role === 'super_admin') {
                    header("Location: superadmin/dashboardsuper.php");
                } 
                elseif ($role === 'admin') {
                    header("Location: admin/dashboardadmin.php");
                } 
                elseif ($role === 'user') {
                    header("Location: users/dashboarduser.php");
                } 
                else {
                    // Default ke user jika role tidak dikenal
                    header("Location: users/dashboarduser.php");
                }
                exit;
            }
            // ============================================================

        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Email tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Sistem Peminjaman Aset IT-PLN</title>
  <style>
    * {margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI', sans-serif;}

    body {
      display:flex;
      height:100vh;
      background:#e0f1f6;
    }

    .container {
      margin:auto;
      display:flex;
      background:#fff;
      border-radius:20px;
      box-shadow:0 8px 25px rgba(0,0,0,0.1);
      overflow:hidden;
      width:850px;
      max-width:95%;
      min-height:480px;
    }

    .left {
      background:#d6ebf2;
      flex:1;
      display:flex;
      flex-direction:column;
      justify-content:center;
      align-items:center;
      position:relative;
      padding:30px;
    }

    .right {
      flex:1.3;
      padding:50px;
      position:relative;
    }
    .right img {
      position:absolute;
      top:20px;
      right:20px;
      width:120px;
    }
    .form-box {max-width:300px;margin:80px auto 0;}
    .form-box h2 {
      margin-bottom:25px;
      color:#111;
    }
    .form-box input {
      width:100%;
      padding:10px;
      margin:12px 0;
      border:none;
      border-bottom:2px solid #ccc;
      font-size:15px;
      outline:none;
      transition:all 0.2s;
    }
    .form-box input:focus {border-bottom-color:#2c3e50;}

    .form-box button {
      width:100%;
      padding:10px;
      margin-top:20px;
      border:none;
      border-radius:20px;
      background:#c6e1eb;
      font-size:15px;
      cursor:pointer;
      transition:0.2s;
    }
    .form-box button:hover {background:#2c3e50;color:#fff;}
    .form-box .extra {margin-top:15px;text-align:center;}
    
    /* Style Error */
    .error {
        background-color: #ffe6e6;
        color: #d63031;
        border: 1px solid #fab1a0;
        padding: 10px;
        border-radius: 8px;
        font-size:13px;
        text-align:center;
        margin-top:15px;
        line-height: 1.5;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="left">
    </div>
    <div class="right">
      <img src="pln.png" alt="Logo IT-PLN"> 
      <div class="form-box">
        <h2>Login</h2>
        <form method="POST">
          <input type="email" name="email" placeholder="Email Address" required>
          <input type="password" name="password" placeholder="Password" required>
          <button type="submit">Login</button>
          <div class="extra">
            <a href="register.php" style="text-decoration:none; color:#555; font-size:14px;">Belum punya akun? Register sekarang!</a>
          </div>
        </form>
        <?php if (!empty($error)): ?>
          <div class="error"><?= $error; ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>