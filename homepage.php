<?php
// include 'config/config.php';

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

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Homepage</title>
    <link rel="stylesheet" href="style/Homepage_style.css">
    <link rel="stylesheet" href="style/main.css">
</head>

<body>
    <?php include 'komponen/navbar.php'; ?>

    <main>
        <!-- SLIDER -->
        <div class="carousel-container">
            <div class="carousel">
                <?php
                function limit_words($string, $word_limit)
                {
                    $words = explode(' ', $string);
                    if (count($words) <= $word_limit) {
                        return $string;
                    }
                    return implode(' ', array_slice($words, 0, $word_limit)) . '...';
                }
                $query = "SELECT * FROM event LIMIT 3";
                $result = mysqli_query($conn, $query);
                while ($row = mysqli_fetch_assoc($result)) {
                    $poster = base64_encode($row['poster_event']);
                    $deskripsi_terbatas = limit_words($row['deskripsi_event'], 12);
                    echo '
                    <div class="slide">
                        <img src="data:image/jpeg;base64,' . $poster . '" alt="' . htmlspecialchars($row['nama_event']) . '">
                        <div class="slide-content">
                            <h2>' . htmlspecialchars($row['nama_event']) . '</h2>
                            <p>' . htmlspecialchars($deskripsi_terbatas) . '</p>
                        </div>
                    </div>';
                }
                ?>
            </div>
            <div class="carousel-nav">
                <div class="nav-dot active" data-index="0"></div>
                <div class="nav-dot" data-index="1"></div>
                <div class="nav-dot" data-index="2"></div>
            </div>
        </div>

        <!-- EVENT CARDS -->
        <div class="events-section">
            <h2 class="events-title">Mungkin Anda Akan Tertarik Dengan Ini!</h2>
            <div class="event-cards-container">
                <div class="event-cards-slide">
                    <?php
                    $eventQuery = "SELECT e.*, t.harga_tiket, t.stok_tiket, t.id_tiket
                                   FROM event e
                                   LEFT JOIN tiket t ON e.id_event = t.id_event
                                   GROUP BY e.id_event
                                   ORDER BY e.tanggal_event ASC
                                   LIMIT 6";
                    $eventResult = mysqli_query($conn, $eventQuery);

                    while ($event = mysqli_fetch_assoc($eventResult)) {
                        $poster = base64_encode($event['poster_event']);
                        $posterSrc = "data:image/jpeg;base64," . $poster;
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
                    ?>
                </div>
            </div>
        </div>
    </main>

    <div class="footer">
        <p>&copy; 2025 EVENTLY. All Rights Reserved.</p>
        <ul class="footer-links">
            <li><a href="#">Contact-Admin</a></li>
        </ul>
    </div>
    <script src="Homepage_script.js"></script>
</body>

</html>