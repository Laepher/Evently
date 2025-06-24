<?php
include 'config/config.php';

// Ambil id_event dari URL
if (!isset($_GET['id_event'])) {
    echo "Event tidak ditemukan.";
    exit;
}

$id_event = $_GET['id_event'];

// Query data event berdasarkan id_event
$sql = "SELECT * FROM event WHERE id_event = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_event);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Event tidak ditemukan.";
    exit;
}

$event = $result->fetch_assoc();

// Format tanggal
$tanggal_mulai = date('d M Y', strtotime($event['tanggal_event']));
$tanggal_selesai = date('d M Y', strtotime($event['tanggal_selesai']));

// Ambil poster (longblob) dan encode ke base64
$poster_base64 = base64_encode($event['poster_event']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($event['nama_event']) ?></title>
    <link rel="stylesheet" href="style/deskripsi_styles.css" />
    <link rel="stylesheet" href="style/main.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
</head>

<body>
    <?php include 'komponen/navbar.php'; ?>

    <div class="container">
        <div class="poster-container">
            <div class="poster" onclick="openModal()">
                <img src="data:image/jpeg;base64,<?= $poster_base64 ?>" alt="<?= htmlspecialchars($event['nama_event']) ?> Poster" />
                <div class="zoom-hint">🔍 Click to enlarge</div>
            </div>
        </div>

        <div id="posterModal" class="modal" style="display: none;">
            <span class="close" onclick="closeModal()">&times;</span>
            <img class="modal-content" id="modalImg" />
        </div>

        <div class="details">
            <h2><?= htmlspecialchars($event['nama_event']) ?></h2>

            <div class="detail-item">
                <i class="fa-solid fa-calendar-days"></i>
                <span><?= $tanggal_mulai ?><?php if ($tanggal_mulai != $tanggal_selesai) echo " - $tanggal_selesai"; ?></span>

                <!-- ⭐⭐ Tampilkan rata-rata rating ⭐⭐ -->
                <?php
                $sql_avg_rating = "SELECT AVG(rating_value) AS avg_rating, COUNT(*) AS jumlah_rating 
                       FROM rating 
                       INNER JOIN tiket ON rating.id_tiket = tiket.id_tiket
                       WHERE tiket.id_event = ?";
                $stmt_avg = $conn->prepare($sql_avg_rating);
                $stmt_avg->bind_param("s", $id_event);
                $stmt_avg->execute();
                $result_avg = $stmt_avg->get_result();
                $row_avg = $result_avg->fetch_assoc();

                if ($row_avg['jumlah_rating'] > 0):
                    $avg_rating = number_format($row_avg['avg_rating'], 1);
                ?>
                    <div class="detail-item">
                        <i class="fas fa-star text-warning"></i>
                        <span><?= $avg_rating ?>/5 (<?= $row_avg['jumlah_rating'] ?> rating)</span>
                    </div>
                <?php else: ?>
                    <div class="detail-item">
                        <i class="fas fa-star text-warning"></i>
                        <span>Belum ada rating</span>
                    </div>
                <?php endif; ?>
                <?php $stmt_avg->close(); ?>
            </div>

            <!-- ⭐⭐ Form untuk memberikan rating ⭐⭐ -->
            <?php if (isset($_SESSION['id_user'])): ?>
                <div style="margin-top: 15px;">
                    <form action="beri_rating.php" method="POST">
                        <input type="hidden" name="id_tiket" value="<?php
                                                                    // Ambil 1 id_tiket event ini (boleh tiket reguler misalnya)
                                                                    $sql_tiket = "SELECT id_tiket FROM tiket WHERE id_event = ? LIMIT 1";
                                                                    $stmt_tiket = $conn->prepare($sql_tiket);
                                                                    $stmt_tiket->bind_param("s", $id_event);
                                                                    $stmt_tiket->execute();
                                                                    $result_tiket = $stmt_tiket->get_result();
                                                                    $row_tiket = $result_tiket->fetch_assoc();
                                                                    echo $row_tiket['id_tiket'] ?? 0;
                                                                    $stmt_tiket->close();
                                                                    ?>">
                        <input type="hidden" name="id_event" value="<?= htmlspecialchars($id_event) ?>">

                        <label for="rating_value">Beri rating (1-5): </label>
                        <select name="rating_value" required>
                            <option value="">Pilih rating</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                        <button type="submit" style="margin-left: 5px;">Submit Rating</button>
                    </form>
                </div>
            <?php else: ?>
                <div style="margin-top: 15px; color: #888;">
                    <em>Login untuk memberikan rating.</em>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>

    <div class="content-section">
        <div class="description">
            <h3>Deskripsi Event</h3>
            <div class="description-content">
                <p><?= nl2br(htmlspecialchars($event['deskripsi_event'])) ?></p>
            </div>
        </div>

        <div class="buy-section">
            <?php
            // Query harga tiket tunggal dari tabel tiket berdasarkan id_event
            $sql_tiket = "SELECT harga_tiket FROM tiket WHERE id_event = ? LIMIT 1";
            $stmt_tiket = $conn->prepare($sql_tiket);
            $stmt_tiket->bind_param("s", $id_event);
            $stmt_tiket->execute();
            $result_tiket = $stmt_tiket->get_result();

            if ($result_tiket->num_rows > 0) {
                $tiket = $result_tiket->fetch_assoc();
                $harga = number_format($tiket['harga_tiket'], 0, ',', '.');
            ?>
                <div class="price-container">
                    <div class="price-value">Rp <?= $harga ?></div>
                    <div class="price-note">Termasuk pajak dan biaya layanan</div>
                </div>

                <div class="buy-button-container">
                    <a href="Transaksi_tiket.php?id_event=<?= urlencode($id_event) ?>" class="buy-button">
                        <i class="fas fa-ticket-alt"></i> Beli Tiket
                    </a>
                </div>
            <?php
            } else {
                echo "<p>Harga tiket belum tersedia.</p>";
            }
            ?>
            <div class="benefits">
                <h4><i class="fas fa-check-circle"></i> Keuntungan Pembelian</h4>
                <ul>
                    <li><i class="fas fa-check"></i> Tiket elektronik langsung diterima</li>
                    <li><i class="fas fa-check"></i> Pembatalan mudah hingga H-3</li>
                    <li><i class="fas fa-check"></i> Garansi harga terbaik</li>
                    <li><i class="fas fa-check"></i> Customer support 24/7</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Syarat & Ketentuan tetap seperti semula -->
    <div class="syarat-ketentuan">
        <div class="sk-header">
            <h3><i class="fas fa-file-contract"></i> Syarat & Ketentuan</h3>
            <div class="sk-note">Jika ada kendala mohon hubungi admin!</div>
        </div>

        <div class="sk-container">
            <div class="sk-column">
                <div class="sk-item">
                    <h4><i class="fas fa-ticket-alt"></i> Tiket Masuk</h4>
                    <p>Pengunjung wajib memiliki tiket resmi untuk memasuki area event. Tiket tidak dapat dipindahtangankan dan berlaku untuk satu orang saja.</p>
                </div>

                <div class="sk-item">
                    <h4><i class="fas fa-id-card"></i> Identitas</h4>
                    <p>Wajib menunjukkan kartu identitas asli (KTP/SIM/Paspor) yang sesuai dengan data pembelian saat penukaran tiket dan masuk venue.</p>
                </div>

                <div class="sk-item">
                    <h4><i class="fas fa-money-bill-wave"></i> Pembayaran</h4>
                    <p>Semua harga sudah termasuk Pajak Hiburan Daerah 10% dan biaya administrasi. Pembayaran dilakukan secara penuh saat transaksi.</p>
                </div>

                <div class="sk-important">
                    <h4><i class="fas fa-exclamation-triangle"></i> Barang Terlarang</h4>
                    <p>Dilarang membawa senjata tajam, obat-obatan terlarang, minuman keras, makanan/minuman dari luar, dan binatang peliharaan. Barang yang disita tidak dapat dikembalikan.</p>
                </div>
            </div>

            <div class="sk-column">
                <div class="sk-item">
                    <h4><i class="fas fa-exchange-alt"></i> Kebijakan Pembatalan</h4>
                    <p>Tiket yang sudah dibeli tidak dapat dikembalikan/ditukar kecuali event dibatalkan oleh penyelenggara. Pembatalan dapat dilakukan maksimal H-3 event.</p>
                </div>

                <div class="sk-item">
                    <h4><i class="fas fa-camera"></i> Hak Gambar</h4>
                    <p>Penyelenggara berhak menggunakan gambar/foto pengunjung untuk keperluan dokumentasi dan promosi tanpa kompensasi lebih lanjut.</p>
                </div>

                <div class="sk-item">
                    <h4><i class="fas fa-user-shield"></i> Keamanan</h4>
                    <p>Penyelenggara berhak mengeluarkan pengunjung yang melanggar peraturan tanpa kompensasi. Pengunjung bertanggung jawab penuh atas barang bawaan.</p>
                </div>

                <div class="sk-item">
                    <h4><i class="fas fa-umbrella-beach"></i> Kebijakan Cuaca</h4>
                    <p>Event akan berlangsung tanpa pandang cuaca. Tidak ada pengembalian dana karena alasan cuaca kecuali event dibatalkan penyelenggara.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>&copy; 2025 EVENTLY. All Rights Reserved.</p>
        <ul class="footer-links">
            <li><a href="#">Contact-Admin</a></li>
        </ul>
    </div>

    <script>
        function openModal() {
            var modal = document.getElementById("posterModal");
            var modalImg = document.getElementById("modalImg");
            var posterImg = document.querySelector(".poster img");

            modal.style.display = "flex";
            modalImg.src = posterImg.src;
        }

        function closeModal() {
            document.getElementById("posterModal").style.display = "none";
        }
    </script>
    <script src="Homepage_script.js"></script>
</body>

</html>