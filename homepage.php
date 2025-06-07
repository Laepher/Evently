<?php 
// Enable error reporting untuk debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'config/config.php'; 

// Cek koneksi database
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset untuk menghindari masalah encoding
mysqli_set_charset($conn, "utf8");
?>

<?php 
// Load banners from DB for carousel
$bannerQuery = "SELECT banner_id, banner_image, description FROM banners ORDER BY banner_id ASC LIMIT 3";
$bannerResult = mysqli_query($conn, $bannerQuery);
$banners = [];

if ($bannerResult) {
    while ($row = mysqli_fetch_assoc($bannerResult)) {
        $banners[] = $row;
    }
} else {
    echo "<!-- Banner Query Error: " . mysqli_error($conn) . " -->";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Homepage - Evently</title>
    <link rel="stylesheet" href="style/Homepage_style.css">
    <link rel="stylesheet" href="style/main.css">
</head>

<body>
    <?php include 'komponen/navbar.php'; ?>

    <main>
        <!-- SLIDER -->
        <div class="carousel-container">
            <div class="carousel">
                <?php if (!empty($banners)): ?>
                    <?php foreach ($banners as $banner): 
                        $imgSrc = 'data:image/jpeg;base64,' . base64_encode($banner['banner_image']);
                        $desc = htmlspecialchars($banner['description']);
                    ?>
                        <div class="slide">
                            <img src="<?= $imgSrc ?>" alt="Banner <?= $banner['banner_id'] ?>" />
                            <div class="slide-content">
                                <p><?= $desc ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="slide">
                        <div style="background: linear-gradient(135deg, #3B8BFF, #2a6adf); color: white; padding: 100px 20px; text-align: center;">
                            <h2>Selamat Datang di Evently</h2>
                            <p>Platform terbaik untuk menemukan event-event menarik</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="carousel-nav">
                <div class="nav-dot active" data-index="0"></div>
                <div class="nav-dot" data-index="1"></div>
                <div class="nav-dot" data-index="2"></div>
            </div>
        </div>

        <!-- EVENT CARDS - STRUKTUR YANG SAMA DENGAN HOMEPAGE KEDUA -->
        <div class="events-section">
            <h2 class="events-title">Mungkin Anda Akan Tertarik Dengan Ini!</h2>
            
            <!-- GANTI STRUKTUR INI MENJADI SAMA SEPERTI HOMEPAGE KEDUA -->
            <div class="event-cards-container">
                <div class="event-cards-slide">
                    <?php
                    // Query untuk mendapatkan event dengan JOIN yang lebih robust
                    $eventQuery = "SELECT 
                                    e.id_event,
                                    e.nama_event,
                                    e.kategori,
                                    e.deskripsi_event,
                                    e.tanggal_event,
                                    e.poster_event,
                                    COALESCE(t.harga_tiket, 0) as harga_tiket,
                                    COALESCE(t.stok_tiket, 0) as stok_tiket,
                                    t.id_tiket
                                FROM event e
                                LEFT JOIN tiket t ON e.id_event = t.id_event
                                WHERE e.tanggal_event >= CURDATE()
                                GROUP BY e.id_event
                                ORDER BY e.tanggal_event ASC
                                LIMIT 6";

                    $eventResult = mysqli_query($conn, $eventQuery);

                    // Debugging: Cek apakah query berhasil
                    if (!$eventResult) {
                        echo '<div class="debug-info">Query Error: ' . mysqli_error($conn) . '</div>';
                        die("Query failed");
                    }

                    // Ambil semua data event ke dalam array
                    $allEvents = [];
                    while ($event = mysqli_fetch_assoc($eventResult)) {
                        $allEvents[] = $event;
                    }

                    // Debug: Tampilkan info untuk debugging (hapus di production)
                    echo '<!-- DEBUG INFO -->';
                    echo '<!-- Total events found: ' . count($allEvents) . ' -->';
                    if (!empty($allEvents)) {
                        echo '<!-- First event: ' . htmlspecialchars($allEvents[0]['nama_event']) . ' -->';
                        echo '<!-- Event date: ' . $allEvents[0]['tanggal_event'] . ' -->';
                    }
                    echo '<!-- END DEBUG -->';

                    // Cek apakah ada event
                    if (empty($allEvents)) {
                        echo '<div class="no-events-message">
                                <p>Belum ada event yang tersedia saat ini.</p>
                                <p>Silakan cek kembali nanti atau hubungi admin untuk menambah event baru!</p>
                              </div>';
                    } else {
                        // Loop untuk menampilkan event cards
                        foreach ($allEvents as $index => $event) {
                            // Pastikan poster_event tidak null
                            if (!empty($event['poster_event'])) {
                                $poster = base64_encode($event['poster_event']);
                                $posterSrc = "data:image/jpeg;base64," . $poster;
                            } else {
                                // Gunakan gambar default atau placeholder
                                $posterSrc = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='200' viewBox='0 0 300 200'%3E%3Crect width='300' height='200' fill='%23f0f0f0'/%3E%3Ctext x='150' y='100' text-anchor='middle' fill='%23666' font-family='Arial' font-size='16'%3ENo Image%3C/text%3E%3C/svg%3E";
                            }
                            
                            $tanggal = date("d M Y", strtotime($event['tanggal_event']));
                            $harga = number_format($event['harga_tiket'], 0, ',', '.');
                            $stok = $event['stok_tiket'];

                            echo '<div class="event-card">
                                    <div class="event-image" style="background-image: url(\'' . $posterSrc . '\')"></div>
                                    <div class="event-details">
                                        <div class="event-name">' . htmlspecialchars($event['nama_event']) . '</div>
                                        <div class="event-date">' . $tanggal . '</div>
                                        <div class="event-price">Rp' . $harga . '</div>';
                            
                            if ($stok > 0) {
                                echo '<a href="Deskripsi_tiket.php?id_event=' . $event['id_event'] . '" class="event-button">LIHAT</a>';
                            } else {
                                echo '<a href="#" class="empty-event-button">Stok Tidak Tersedia</a>';
                            }
                            echo '</div></div>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </main>

   <?php include 'komponen/footer.php'; ?>

    <script src="Homepage_script.js"></script>
    <script>
        // Additional JavaScript for better debugging
        document.addEventListener('DOMContentLoaded', function() {
            // Cek apakah ada event cards
            const eventCards = document.querySelectorAll('.event-card');
            console.log('Total event cards found:', eventCards.length);
            
            if (eventCards.length === 0) {
                console.log('No event cards found - check database query and data');
            } else {
                console.log('Event cards loaded successfully');
            }
        });
    </script>
</body>

</html>