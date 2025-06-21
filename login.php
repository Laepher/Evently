<?php
require 'config/config.php';
session_start();

if ($conn->connect_error) {
  die("Koneksi gagal: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_POST['email'];
  $password = $_POST['password'];
  $error = null;

  // Coba login sebagai admin
  $sql_admin = "SELECT id_admin, password_admin FROM admin WHERE email_admin = ?";
  $stmt = $conn->prepare($sql_admin);
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows === 1) {
    $stmt->bind_result($id_admin, $hashed_password_admin);
    $stmt->fetch();

    if (password_verify($password, $hashed_password_admin)) {
      $_SESSION['id_admin'] = $id_admin;
      $_SESSION['role'] = 'admin';
      header("Location: dashboardadmin.php");
      exit;
    } else {
      $error = "Password salah.";
    }
  } else {
    // Login sebagai user
    $sql_user = "SELECT id_user, password_user, status FROM user WHERE email_user = ?";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("s", $email);
    $stmt_user->execute();
    $stmt_user->store_result();

    if ($stmt_user->num_rows === 1) {
      $stmt_user->bind_result($id_user, $hashed_password_user, $status_user);
      $stmt_user->fetch();

      if ($status_user !== 'aktif') {
        $error = "Akun Anda sedang nonaktif. Silakan hubungi admin.";
      } elseif (password_verify($password, $hashed_password_user)) {
        $_SESSION['id_user'] = $id_user;
        $_SESSION['role'] = 'user';
        header("Location: homepage.php");
        exit;
      } else {
        $error = "Password salah.";
      }
    } else {
      $error = "Email tidak ditemukan.";
    }
    $stmt_user->close();
  }

  $stmt->close();
  $conn->close();
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Halaman Login</title>
  <link rel="stylesheet" href="style/login.css" />
</head>

<body>
  <header>
    <a href="homepage.php" style="text-decoration: none; color: blue; font-size: 25px">EVENTLY</a>
  </header>
  <main>
    <div class="login-box">
      <h2>LOGIN PAGE</h2>
      <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
      <form method="POST">
        <input type="email" name="email" placeholder="Email" required />
        <input type="password" name="password" placeholder="Password" required />
        <button type="submit">SIGN IN</button>
      </form>
      <a href="#">Forgot Password?</a>
      <br />
      <a href="register.php">Sign Up</a>
    </div>
  </main>
  <footer>
    <span>© 2025 EVENTLY. All Rights Reserved.</span>
    <a href="#">Contact Admin</a>
  </footer>
</body>

</html>