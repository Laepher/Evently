<?php
session_start();
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

// Cek apakah user sudah membeli tiket dengan status confirmed
$user_has_purchased = false;
$user_current_rating = null;
if (isset($_SESSION['id_user'])) {
    $id_user = $_SESSION['id_user'];
    
    // Cek pembelian dengan status confirmed
    $sql_purchase = "SELECT p.id_pesanan, t.id_tiket, r.rating_value
                     FROM pesanan p 
                     JOIN tiket t ON p.id_tiket = t.id_tiket 
                     LEFT JOIN rating r ON (t.id_tiket = r.id_tiket AND r.id_user = ?)
                     WHERE p.id_user = ? AND t.id_event = ? 
                     AND (p.status_pesanan = 'confirmed' OR p.status_pesanan = 'terbayar')
                     LIMIT 1";
    
    $stmt_purchase = $conn->prepare($sql_purchase);
    $stmt_purchase->bind_param("sss", $id_user, $id_user, $id_event);
    $stmt_purchase->execute();
    $result_purchase = $stmt_purchase->get_result();
    
    if ($result_purchase->num_rows > 0) {
        $user_has_purchased = true;
        $purchase_data = $result_purchase->fetch_assoc();
        $user_current_rating = $purchase_data['rating_value'];
    }
    $stmt_purchase->close();
}
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
    <style>
        .rating-section {
            margin: 15px 0;
            padding: 20px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            color: #333;
        }
        
        .rating-section h4 {
            margin: 0 0 15px 0;
            font-size: 1.1em;
            font-weight: 600;
            color: #495057;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .rating-section h4 i {
            color: #ffc107;
        }
        
        .rating-form {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .star-rating {
            display: flex;
            gap: 5px;
        }
        
        .star {
            font-size: 24px;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s ease;
        }
        
        .star:hover,
        .star.active {
            color: #ffc107;
        }
        
        .rating-submit {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s ease;
        }
        
        .rating-submit:hover:not(:disabled) {
            background-color: #0056b3;
        }
        
        .rating-submit:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }
        
        .current-rating {
            color: #28a745;
            font-weight: 600;
            font-size: 14px;
        }
        
        .rating-info {
            color: #6c757d;
            font-size: 14px;
            margin: 8px 0;
        }
        
        .rating-info a {
            color: #007bff;
            text-decoration: none;
        }
        
        .rating-info a:hover {
            text-decoration: underline;
        }
        
        /* Average Rating Styles */
        .average-rating {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fff;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            margin: 8px 0;
        }
        
        .average-rating i.fas.fa-star.text-warning {
            color: #ffc107 !important;
        }
        
        .rating-score {
            font-weight: 600;
            color: #495057;
        }
        
        .rating-stars i {
            color: #ffc107;
            font-size: 14px;
        }
        
        .rating-count {
            color: #6c757d;
            font-size: 13px;
        }
        
        .no-rating {
            background: #fff;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            color: #6c757d;
            margin: 8px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .no-rating i {
            color: #ffc107;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .rating-form {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .star-rating {
                align-self: center;
            }
        }
    </style>
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
            </div>

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
                $full_stars = floor($row_avg['avg_rating']);
                $half_star = ($row_avg['avg_rating'] - $full_stars) >= 0.5;
            ?>
                <div class="detail-item">
                    <div class="average-rating">
                        <i class="fas fa-star text-warning"></i>
                        <span class="rating-score"><?= $avg_rating ?>/5</span>
                        <div class="rating-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= $full_stars): ?>
                                    <i class="fas fa-star"></i>
                                <?php elseif ($i == $full_stars + 1 && $half_star): ?>
                                    <i class="fas fa-star-half-alt"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <span class="rating-count"><?= $row_avg['jumlah_rating'] ?> rating</span>
                    </div>
                </div>
            <?php else: ?>
                <div class="detail-item">
                    <div class="no-rating">
                        <i class="fas fa-star"></i>
                        <span>Belum ada rating untuk event ini</span>
                    </div>
                </div>
            <?php endif; ?>
            <?php $stmt_avg->close(); ?>

            <!-- ⭐⭐ Form untuk memberikan rating ⭐⭐ -->
            <?php if (isset($_SESSION['id_user'])): ?>
                <?php if ($user_has_purchased): ?>
                    <div class="rating-section">
                        <h4><i class="fas fa-star"></i> Berikan Rating Event</h4>
                        
                        <?php if ($user_current_rating): ?>
                            <div class="current-rating">
                                Rating Anda saat ini: <?= $user_current_rating ?>/5 bintang
                            </div>
                            <p class="rating-info">
                                <i class="fas fa-info-circle"></i>
                                Klik bintang untuk mengubah rating Anda
                            </p>
                        <?php else: ?>
                            <p class="rating-info">
                                <i class="fas fa-thumbs-up"></i>
                                Bagaimana pengalaman Anda dengan event ini?
                            </p>
                        <?php endif; ?>
                        
                        <form action="beri_rating.php" method="POST" id="ratingForm">
                            <input type="hidden" name="id_event" value="<?= htmlspecialchars($id_event) ?>">
                            <input type="hidden" name="rating_value" id="selectedRating" value="<?= $user_current_rating ?? '' ?>">
                            
                            <div class="rating-form">
                                <div class="star-rating" id="starRating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?= ($user_current_rating && $i <= $user_current_rating) ? 'active' : '' ?>" 
                                              data-rating="<?= $i ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                                <button type="submit" class="rating-submit" id="submitBtn" 
                                        <?= !$user_current_rating ? 'disabled' : '' ?>>
                                    <i class="fas fa-paper-plane"></i>
                                    <?= $user_current_rating ? 'Update Rating' : 'Kirim Rating' ?>
                                </button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="rating-section">
                        <h4><i class="fas fa-lock"></i> Rating Terkunci</h4>
                        <div class="rating-info">
                            <i class="fas fa-shopping-cart"></i> 
                            Anda dapat memberikan rating setelah membeli tiket dan pembayaran dikonfirmasi.
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="rating-section">
                    <h4><i class="fas fa-sign-in-alt"></i> Login Diperlukan</h4>
                    <div class="rating-info">
                        <i class="fas fa-user"></i> 
                        <a href="login.php">Login terlebih dahulu</a> untuk dapat memberikan rating event ini.
                    </div>
                </div>
            <?php endif; ?>
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

        // Enhanced Rating functionality
        document.addEventListener('DOMContentLoaded', function() {
            const stars = document.querySelectorAll('.star');
            const selectedRatingInput = document.getElementById('selectedRating');
            const submitBtn = document.getElementById('submitBtn');
            const ratingSection = document.querySelector('.rating-section');
            let currentRating = parseInt(selectedRatingInput.value) || 0;

            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const rating = parseInt(this.dataset.rating);
                    currentRating = rating;
                    selectedRatingInput.value = rating;
                    
                    // Update visual feedback
                    updateStars(rating);
                    
                    // Enable submit button
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = `<i class="fas fa-paper-plane"></i> ${currentRating ? 'Update Rating' : 'Kirim Rating'}`;
                    
                    // Add success animation
                    ratingSection.classList.add('rating-success');
                    setTimeout(() => {
                        ratingSection.classList.remove('rating-success');
                    }, 500);
                });

                star.addEventListener('mouseover', function() {
                    const rating = parseInt(this.dataset.rating);
                    updateStars(rating);
                });
            });

            // Reset stars on mouse leave
            const starRating = document.getElementById('starRating');
            if (starRating) {
                starRating.addEventListener('mouseleave', function() {
                    updateStars(currentRating);
                });
            }

            function updateStars(rating) {
                stars.forEach((star, index) => {
                    if (index < rating) {
                        star.classList.add('active');
                    } else {
                        star.classList.remove('active');
                    }
                });
            }

            // Form validation with better UX
            const ratingForm = document.getElementById('ratingForm');
            if (ratingForm) {
                ratingForm.addEventListener('submit', function(e) {
                    if (!selectedRatingInput.value) {
                        e.preventDefault();
                        
                        // Animate stars to draw attention
                        stars.forEach((star, index) => {
                            setTimeout(() => {
                                star.style.transform = 'scale(1.3)';
                                star.style.color = '#ff6b6b';
                                setTimeout(() => {
                                    star.style.transform = 'scale(1)';
                                    star.style.color = 'rgba(255, 255, 255, 0.3)';
                                }, 200);
                            }, index * 100);
                        });
                        
                        // Show friendly message
                        alert('Silakan pilih rating dengan mengklik bintang terlebih dahulu! ⭐');
                    } else {
                        // Loading state
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';
                        submitBtn.disabled = true;
                    }
                });
            }

            // Initialize stars display
            updateStars(currentRating);
        });
    </script>
    <script src="Homepage_script.js"></script>
</body>

</html>