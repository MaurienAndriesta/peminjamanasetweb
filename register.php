<?php
// Proses register sederhana
session_start();
$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = $_POST['name'] ?? '';
    $email    = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // contoh simpan ke file (sementara), nanti bisa ganti ke database
    if (!empty($name) && !empty($email) && !empty($password)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $data = $name . "|" . $email . "|" . $hashed . PHP_EOL;
        file_put_contents("users.txt", $data, FILE_APPEND);
        $success = "Registrasi berhasil! Silakan login.";
    } else {
        $error = "Semua field wajib diisi!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - Sistem Peminjaman Aset IT-PLN</title>
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
    .switch-tab {
      position:absolute;
      right:-40px;
      top:50%;
      transform:translateY(-50%);
      display:flex;
      flex-direction:column;
      gap:10px;
    }
    .switch-tab a {
      text-decoration:none;
      background:#fff;
      color:#333;
      padding:6px 18px;
      border-radius:20px;
      font-size:14px;
      text-align:center;
      transition:all 0.2s;
    }
    .switch-tab a.active {
      background:#2c3e50;
      color:#fff;
      font-weight:bold;
    }
    .switch-tab a:hover {background:#34495e;color:#fff;}

    
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

    .form-box .extra {
      margin-top:15px;
      text-align:center;
    }
    .error {color:red;font-size:14px;text-align:center;margin-top:10px;}
    .success {color:green;font-size:14px;text-align:center;margin-top:10px;}
  </style>
</head>
<body>
  <div class="container">
    <!-- Bagian Kiri -->
    <div class="left">
      <div class="switch-tab">
        <a href="register.php" class="active">Register</a>
        <a href="login.php">Login</a>
      </div>
    </div>

    
    <div class="right">
      <img src="pln.png" alt="Logo IT-PLN">
      <div class="form-box">
        <h2>Register</h2>
        <form method="POST">
          <input type="text" name="name" placeholder="Full Name" required>
          <input type="email" name="email" placeholder="Email Address" required>
          <input type="password" name="password" placeholder="Password" required>
          <button type="submit">Register</button>
          <div class="extra">
            <a href="login.php">Sudah punya akun? Login</a>
          </div>
        </form>
        <?php if (!empty($error)): ?>
          <div class="error"><?= $error; ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
          <div class="success"><?= $success; ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>
