<?php
include 'config/config.php';

// Function to format currency
function formatCurrency($amount)
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Function to format date
function formatDate($date)
{
    if (empty($date) || $date == '0000-00-00 00:00:00') {
        return '-';
    }
    return date('d/m/Y H:i', strtotime($date));
}

// Handle search functionality
$searchTerm = '';
$whereClause = '';

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = trim($_GET['search']);
    $searchTerm = mysqli_real_escape_string($conn, $searchTerm);
    $whereClause = "WHERE (p.id_pembayaran LIKE '%$searchTerm%' 
                    OR p.id_pesanan LIKE '%$searchTerm%' 
                    OR ps.id_user LIKE '%$searchTerm%' 
                    OR e.nama_event LIKE '%$searchTerm%')";
}

// Get payment data with joins
$query = "SELECT p.id_pembayaran, p.id_pesanan, p.bukti_bayar, 
                 ps.id_user, ps.banyak_tiket, ps.total_harga, ps.metode_bayar, ps.tanggal_pesanan,
                 e.nama_event, t.harga_tiket
          FROM pembayaran p
          INNER JOIN pesanan ps ON p.id_pesanan = ps.id_pesanan
          INNER JOIN tiket t ON ps.id_tiket = t.id_tiket
          INNER JOIN event e ON t.id_event = e.id_event
          $whereClause
          ORDER BY p.id_pembayaran DESC";

$paymentResult = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Evently - Pembayaran</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Admin Styles -->
    <link rel="stylesheet" href="style/admin-styles.css">
    <style>
        
        .bukti-image {
            max-height: 60px;
            max-width: 60px;
            object-fit: cover;
            border-radius: 0.375rem;
            cursor: pointer;
            border: 2px solid #dee2e6;
            transition: all 0.2s ease;
        }
        
        .bukti-image:hover {
            border-color: #3B8BFF;
            transform: scale(1.05);
        }
        
        .modal-image {
            max-width: 100%;
            max-height: 80vh;
            object-fit: contain;
        }
        
    </style>
</head>

<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid px-3">
            <span class="navbar-brand fw-bold fs-5 mb-0">EVENTLY</span>
            <div class="d-flex align-items-center">
                <span class="admin-username">ADMIN#1</span>
                <a href="#" class="logout-link">
                    LOG OUT
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 col-lg-2 sidebar bg-white p-4">
                <h5 class="fw-bold text-primary-custom mb-4">KELOLA</h5>
                <nav class="nav flex-column">
                    <a href="daftarevent.php" class="nav-link text-primary-custom fw-semibold p-0 mb-3">
                        DAFTAR EVENT
                    </a>
                    <a href="daftaruser.php" class="nav-link text-primary-custom fw-semibold p-0 mb-3">
                        DAFTAR USER
                    </a>
                    <a href="pemesanantiket.php" class="nav-link text-primary-custom fw-semibold p-0 mb-3">
                        PEMESANAN TIKET
                    </a>
                    <a href="pembayaran.php" class="nav-link text-primary-custom fw-semibold p-0 mb-3">
                        PEMBAYARAN USER
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 col-lg-10 p-4">
                <!-- Header with Search -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="text-primary-custom fw-bold">PEMBAYARAN</h2>

                    <!-- Search Form -->
                    <form class="search-container" method="GET" style="width: 250px;">
                        <div class="input-group">
                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                placeholder="Search payment..."
                                value="<?php echo htmlspecialchars($searchTerm); ?>"
                                style="padding-right: 40px;">
                            <button class="search-btn" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($successMessage)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $successMessage; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($errorMessage)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $errorMessage; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Search Results Info -->
                <?php if (!empty($searchTerm)): ?>
                    <div class="alert alert-info d-flex justify-content-between align-items-center" role="alert">
                        <span>
                            <i class="fas fa-info-circle me-2"></i>
                            Showing <?php echo mysqli_num_rows($paymentResult); ?> result(s) for "<?php echo htmlspecialchars($searchTerm); ?>"
                        </span>
                        <a href="?" class="btn btn-sm btn-outline-secondary">Clear Search</a>
                    </div>
                <?php endif; ?>

                <!-- Payments Table -->
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <?php if (!$paymentResult || mysqli_num_rows($paymentResult) === 0): ?>
                            <div class="empty-state">
                                <i class="fas fa-receipt"></i>
                                <h5>No payments found</h5>
                                <p>No payment records match your criteria</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="text-center" style="width: 100px;">ID PEMBAYARAN</th>
                                            <th scope="col" class="text-center" style="width: 100px;">ID PESANAN</th>
                                            <th scope="col">NAMA EVENT</th>
                                            <th scope="col" class="text-center" style="width: 80px;">ID USER</th>
                                            <th scope="col" class="text-center" style="width: 120px;">TOTAL HARGA</th>
                                            <th scope="col" class="text-center" style="width: 100px;">BUKTI BAYAR</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($payment = mysqli_fetch_assoc($paymentResult)): ?>
                                            <tr>
                                                <td class="text-center">
                                                    <span class="badge bg-light text-dark fw-semibold"><?php echo htmlspecialchars($payment['id_pembayaran']); ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($payment['id_pesanan']); ?></span>
                                                </td>
                                                <td class="fw-medium text-dark"><?php echo htmlspecialchars($payment['nama_event']); ?></td>
                                                <td class="text-center text-muted"><?php echo htmlspecialchars($payment['id_user']); ?></td>
                                                <td class="text-center fw-medium"><?php echo formatCurrency($payment['total_harga']); ?></td>
                                                <td class="text-center">
                                                    <?php if (!empty($payment['bukti_bayar'])): ?>
                                                        <img src="uploads/bukti_pembayaran/<?php echo htmlspecialchars($payment['bukti_bayar']); ?>" 
                                                             class="bukti-image" 
                                                             alt="Bukti Pembayaran"
                                                             onclick="showImageModal(this.src, '<?php echo htmlspecialchars($payment['id_pembayaran']); ?>')">
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Payment Statistics -->
                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-primary">Total Pembayaran</h5>
                                <?php
                                $totalQuery = "SELECT COUNT(*) as total FROM pembayaran";
                                $totalResult = mysqli_query($conn, $totalQuery);
                                $totalRow = mysqli_fetch_assoc($totalResult);
                                ?>
                                <h3 class="text-primary"><?= $totalRow['total'] ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Bukti Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" class="modal-image" alt="Bukti Pembayaran">
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Show image modal
        function showImageModal(imageSrc, paymentId) {
            const modal = new bootstrap.Modal(document.getElementById('imageModal'));
            const modalImage = document.getElementById('modalImage');
            const modalTitle = document.getElementById('imageModalLabel');
            
            modalImage.src = imageSrc;
            modalTitle.textContent = `Bukti Pembayaran - ${paymentId}`;
            modal.show();
        }

        // Auto-submit search on input
        let searchTimeout;
        const searchInput = document.querySelector('input[name="search"]');

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (this.value.length >= 2 || this.value.length === 0) {
                        this.form.submit();
                    }
                }, 500);
            });
        }
    </script>
</body>

</html>