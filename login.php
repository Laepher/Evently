<?php
require 'config/config.php';
session_start();

// Debugging connection
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $error = null;

    // Coba cari sebagai admin
    $sql_admin = "SELECT id_admin, password_admin FROM admin WHERE email_admin = ?";
    $stmt = mysqli_prepare($conn, $sql_admin);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) === 1) {
        mysqli_stmt_bind_result($stmt, $id_admin, $hashed_password_admin);
        mysqli_stmt_fetch($stmt);

        if (password_verify($password, $hashed_password_admin)) {
            $_SESSION['admin_id'] = $id_admin;
            $_SESSION['role'] = 'admin';
            header("Location: dashboardadmin.php");
            exit;
        } else {
            $error = "Password salah.";
        }
    } else {
        // Jika bukan admin, coba cari sebagai user
        $sql_user = "SELECT id_user, password_user FROM user WHERE email_user = ?";
        $stmt_user = mysqli_prepare($conn, $sql_user);
        mysqli_stmt_bind_param($stmt_user, "s", $email);
        mysqli_stmt_execute($stmt_user);
        mysqli_stmt_store_result($stmt_user);

        if (mysqli_stmt_num_rows($stmt_user) === 1) {
            mysqli_stmt_bind_result($stmt_user, $id_user, $hashed_password_user);
            mysqli_stmt_fetch($stmt_user);

            if (password_verify($password, $hashed_password_user)) {
                $_SESSION['user_id'] = $id_user;
                $_SESSION['role'] = 'user';
                header("Location: homepage.php");
                exit;
            } else {
                $error = "Password salah.";
            }
        } else {
            $error = "Email tidak ditemukan.";
        }
        mysqli_stmt_close($stmt_user);
    }

    mysqli_stmt_close($stmt);
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
