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
    <button class="burger">☰</button>
    <a href="homepage.php" class="logo">EVENTLY</a>
    <div class="search-bar" id="searchBarContainer"> <?php // Edit: Menambahkan ID untuk akses JS ?>
        <input type="text" placeholder="Search..." id="searchInput" oninput="searchEvents()" onclick="showSearchDropdown()"> <?php // Edit: Menambahkan ID, oninput, dan onclick ?>
        <?php // Edit: Mulai - Kontainer dropdown pencarian ?>
        <div id="searchResultsDropdown" class="search-results-dropdown">
        <div id="searchResultsContent"></div>
        <div id="searchPagination" class="search-pagination"></div>
        </div>
        <?php // Edit: Akhir - Kontainer dropdown pencarian ?>
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
        
        <button class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </button>
    </div>
</nav>

<script>
    // Initialize sidebar functionality when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        const burgerBtn = document.querySelector('.burger');
        const sidebar = document.getElementById('userSidebar');
        const logoutBtn = document.querySelector('.logout-btn');
        
        // === Sidebar Toggle Function ===
        function toggleSidebar() {
            sidebar.classList.toggle('hidden');
            
            if (!sidebar.classList.contains('hidden')) {
                // Add click outside listener when sidebar is open
                setTimeout(() => {
                    document.addEventListener('click', handleClickOutside);
                }, 0);
            } else {
                // Remove click outside listener when sidebar is closed
                document.removeEventListener('click', handleClickOutside);
            }
        }
        
        // === Handle Click Outside Sidebar ===
        function handleClickOutside(event) {
            if (sidebar && !sidebar.contains(event.target) && !burgerBtn.contains(event.target)) {
                sidebar.classList.add('hidden');
                document.removeEventListener('click', handleClickOutside);
            }
        }
        
        // === Logout Function ===
        function logout() {
            if (confirm("Apakah Anda yakin ingin logout?")) {
                window.location.href = 'login.php';
            }
        }
        
        // === Event Listeners ===
        // Burger button click
        burgerBtn?.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleSidebar();
        });
        
        // Logout button click
        logoutBtn?.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            logout();
        });
        
        // Prevent sidebar from closing when clicking inside it
        sidebar?.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
</script>

<script src="search.js"></script> <?php // Edit: Menyertakan file JavaScript baru ?>
<link rel="stylesheet" href="searchbar.css"> <?php // Edit: Menyertakan file CSS baru untuk styling dropdown pencarian ?>