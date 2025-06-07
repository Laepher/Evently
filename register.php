<?php
require 'config/config.php';
session_start();

// Debugging connection
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Proses registrasi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST["fullname"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $confirm = $_POST["confirm_password"];

    // Validasi password konfirmasi
    if ($password !== $confirm) {
        echo "<script>alert('Password dan konfirmasi tidak cocok!');</script>";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $status = "aktif";

        // Ambil id_user terakhir
        $result = $conn->query("SELECT id_user FROM user ORDER BY id_user DESC LIMIT 1");
        $lastId = 1;
        if ($result && $row = $result->fetch_assoc()) {
            $lastId = (int)substr($row['id_user'], 2) + 1;
        }

        $id_user = '11' . str_pad($lastId, 5, '0', STR_PAD_LEFT);

        // Simpan ke tabel user
        $stmt = $conn->prepare("INSERT INTO user (id_user, nama_user, email_user, password_user, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $id_user, $nama, $email, $hashed, $status);

        if ($stmt->execute()) {
            echo "<script>alert('Registrasi berhasil! ID kamu: $id_user'); window.location.href='login.php';</script>";
        } else {
            echo "<script>alert('Gagal menyimpan: " . $stmt->error . "');</script>";
        }

        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Evently Register</title>
  <link rel="stylesheet" href="style/Register.css" />
</head>
<body>
  <header>
    <a href="homepage.php" style="text-decoration: none; color: blue;">EVENTLY</a>
  </header>
  <main>
    <div class="container">
      <div class="left">
        <p1 class="logo-text">EVENTLY</p1>
        <p class="tagline">
          Event Online Ticketing<br />Anywhere, Everywhere!
        </p>
      </div>
      <div class="right">
        <form method="POST" action="register.php">
          <h2>Create Your Account</h2>
          <label for="fullname">Full Name</label>
          <input type="text" id="fullname" name="fullname" placeholder="Enter your full name" required />
          <label for="email">Email</label>
          <input type="email" id="email" name="email" placeholder="johndoe@email.com" required />
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="Enter your Password" required />
          <label for="confirm_password">Confirm Password</label>
          <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your Password" required />
          <button type="submit">Register</button>
          <h3>Already got an account?</h3> 
          <a href="login.php" class="login-button">Login</a>
        </form>
      </div>
    </div>
  </main>
  <footer>
    © 2024 Evently. All rights reserved.
  </footer>
</body>
</html>
