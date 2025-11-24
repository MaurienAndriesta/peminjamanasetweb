<?php
session_start();
include 'koneksi.php'; 

// 🔹 Cek apakah sudah ada super_admin di database
$cekSuperAdmin = $koneksi->query("SELECT COUNT(*) AS total FROM tbl_user WHERE role = 'super_admin'");
$dataAdmin = $cekSuperAdmin->fetch_assoc();

if ($dataAdmin['total'] == 0) {
    // Kalau belum ada super admin, buat default akun
    $fullname = "Super Admin ITPLN";
    $email = "superadmin@itpln.ac.id";
    $passwordHash = password_hash("password", PASSWORD_DEFAULT);
    $role = "super_admin";

    $insertAdmin = $koneksi->prepare("INSERT INTO tbl_user (fullname, email, password, role) VALUES (?, ?, ?, ?)");
    $insertAdmin->bind_param("ssss", $fullname, $email, $passwordHash, $role);

    $insertAdmin->execute();
}



// 🔹 Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $error = '';

    $stmt = $koneksi->prepare("SELECT * FROM tbl_user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
             // Simpan session yang seragam
              $_SESSION['user_id'] = $user['id'];      // Wajib pakai ini
              $_SESSION['fullname'] = $user['fullname'];
              $_SESSION['role'] = $user['role'];

            // 🔸 Redirect sesuai role
             if ($user['role'] === 'super_admin') {
        header("Location: superadmin/dashboard.php");
    } elseif ($user['role'] === 'admin') {
        header("Location: admin/dashboardadmin.php");
    } else {
        header("Location: users/dashboarduser.php");
    }
    exit;
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
    .error {color:red;font-size:14px;text-align:center;margin-top:10px;}
  </style>
</head>
<body>
  <div class="container">
    <div class="left"></div>
    <div class="right">
      <img src="pln.png" alt="Logo IT-PLN">
      <div class="form-box">
        <h2>Login</h2>
        <form method="POST">
          <input type="email" name="email" placeholder="Email Address" required>
          <input type="password" name="password" placeholder="Password" required>
          <button type="submit">Login</button>
          <div class="extra">
            <a href="register.php">Belum punya akun? Register sekarang!</a>
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
