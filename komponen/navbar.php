<?php
require 'auth/auth.php';
require_role('user');
require 'config/config.php';

$id_user = $_SESSION['user_id'];
$nama_user = 'User';

$sql = "SELECT nama_user FROM user WHERE id_user = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $nama_user_dari_db);
if (mysqli_stmt_fetch($stmt)) {
    $nama_user = $nama_user_dari_db;
}
mysqli_stmt_close($stmt);
?>


<nav class="navbar">
    <button class="burger" onclick="toggleSidebar()">☰</button>
    <a href="homepage.php" class="logo">EVENTLY</a>
    <div class="search-bar">
        <input type="text" placeholder="Search...">
    </div>
    <div class="nav-links">
        <a href="homepage.php" class="home-link">BERANDA</a>
    </div>
</nav>
<nav>
    <div id="userSidebar" class="sidebar hidden">
        <img src="./assets/achil.jpg" alt="Foto Profil" class="user-avatar">
        <div class="user-name">Hai, <?= htmlspecialchars($nama_user) ?>!</div>
     <!-- Menu Sidebar -->
    <div class="sidebar-menu">
        <a href="riwayat_pembelian.php" class="sidebar-link">
            <i class="fas fa-history"></i> Riwayat Pembelian
        </a>
    </div>
    
    <button class="logout-btn" onclick="logout()">
        <i class="fas fa-sign-out-alt"></i> Logout
    </button>
</nav>

<script>
    function logout() {
        window.location.href = 'login.php';
    }
</script>