<?php
include 'config/config.php';

// Debug: Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$query = "
    SELECT e.id_event, e.nama_event, e.kategori, e.deskripsi_event, e.tanggal_event, t.harga_tiket, e.poster_event
    FROM event e
    LEFT JOIN tiket t ON e.id_event = t.id_event
";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

$events = mysqli_fetch_all($result, MYSQLI_ASSOC);

function formatCurrency($amount)
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function formatDate($date)
{
    return date('d/m/Y', strtotime($date));
}

// Handle delete action (jika ada)
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $deleteId = $_GET['delete'];
    // Proses delete di database (jangan hanya filter array, harus DELETE query)
    $deleteQuery = "DELETE FROM event WHERE id_event = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("s", $deleteId);
    $stmt->execute();
    $stmt->close();

    header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Evently - Daftar Event</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3B8BFF;
        }

        .navbar-custom {
            background-color: var(--primary-color) !important;
        }

        .text-primary-custom {
            color: var(--primary-color) !important;
        }

        .border-primary-custom {
            border-color: var(--primary-color) !important;
        }

        .btn-outline-primary-custom {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-outline-primary-custom:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .sidebar {
            min-height: calc(100vh - 56px);
        }

        /* Standardized Bootstrap-style table */
        .table {
            width: 100%;
            border-collapse: collapse;
            color: #212529;
            font-size: 0.875rem;
            background-color: #fff;
        }

        .table th,
        .table td {
            padding: 0.75rem;
            vertical-align: middle;
            border-top: 1px solid #dee2e6;
        }

        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #dee2e6;
            border-top: none;
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
            position: sticky;
            top: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.75rem;
        }

        .table tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.075);
        }

        .table tbody tr:nth-child(odd) {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .search-container {
            position: relative;
        }

        .search-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: none;
            color: var(--primary-color);
            padding: 4px 8px;
        }

        .search-btn:hover {
            color: #2a6adf;
        }

        /* Admin username styling */
        .admin-username {
            color: #fff;
            font-weight: 500;
            margin-right: 1rem;
        }

        /* Logout link styling with hover effect */
        .logout-link {
            color: #ffcccb;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .logout-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
            text-decoration: none;
            transform: translateY(-1px);
        }

        /* Remove underlines from sidebar navigation */
        .sidebar .nav-link {
            text-decoration: none !important;
        }

        .sidebar .nav-link:hover {
            text-decoration: none !important;
        }

        /* Category styling */
        .category {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 500;
        }

        /* Delete button styling */
        .btn-delete {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 0.375rem;
            transition: all 0.15s ease-in-out;
        }

        .btn-delete:hover {
            background-color: #c82333;
            border-color: #bd2130;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.25);
        }

        .btn-delete:focus {
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.5);
        }

        /* Add button styling */
        .btn-add {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            font-weight: 600;
            padding: 0.625rem 1.25rem;
            border-radius: 0.375rem;
            transition: all 0.15s ease-in-out;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.875rem;
        }

        .btn-add:hover {
            background-color: #2a6adf;
            border-color: #2a6adf;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(59, 139, 255, 0.25);
        }

        .btn-add:focus {
            box-shadow: 0 0 0 0.2rem rgba(59, 139, 255, 0.5);
        }

        /* View Poster button styling */
        .btn-view-poster {
            background-color: #28a745;
            border-color: #28a745;
            color: white;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 0.375rem;
            transition: all 0.15s ease-in-out;
        }

        .btn-view-poster:hover {
            background-color: #218838;
            border-color: #1e7e34;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(40, 167, 69, 0.25);
        }

        .btn-view-poster:focus {
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.5);
        }

        /* No poster available styling */
        .no-poster {
            color: #6c757d;
            font-style: italic;
            font-size: 0.875rem;
        }

        /* Poster modal styling */
        .poster-modal .modal-dialog {
            max-width: 800px;
        }

        .poster-image {
            max-width: 100%;
            height: auto;
            border-radius: 0.5rem;
        }

        /* Header controls responsive spacing */
        .header-controls {
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .header-controls {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 0.75rem;
            }

            .search-container {
                width: 100% !important;
                max-width: none !important;
            }
        }
    </style>
</head>

<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid px-3">
            <span class="navbar-brand fw-bold fs-5 mb-0">EVENTLY</span>
            <div class="d-flex align-items-center">
                <a href="dashboardadmin.php" class="admin-username">ADMIN#1</a>
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
                    <a href="daftarevent.php" class="nav-link text-primary-custom fw-semibold p-0 mb-3" href="#">
                        DAFTAR EVENT
                    </a>
                    <a href="daftaruser.php" class="nav-link text-primary-custom fw-semibold p-0 mb-3" href="#">
                        DAFTAR USER
                    </a>
                    <a href="pemesanantiket.php" class="nav-link text-primary-custom fw-semibold p-0 mb-3" href="#">
                        PEMESANAN TIKET
                    </a>
                    <a href="pembayaran.php" class="nav-link text-primary-custom fw-semibold p-0 mb-3" href="#">
                        PEMBAYARAN USER
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4 header-controls">
                    <h2 class="text-primary-custom fw-bold">DAFTAR EVENT</h2>

                    <button class="btn btn-add" onclick="addNewEvent()">
                        <i class="fas fa-plus me-2"></i>
                        TAMBAHKAN
                    </button>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <?php if (empty($events)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Belum ada event</h5>
                                <p class="text-muted">Klik tombol "TAMBAHKAN" untuk menambah event baru</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th scope="col">ID EVENT</th>
                                            <th scope="col">NAMA EVENT</th>
                                            <th scope="col" class="text-center">KATEGORI</th>
                                            <th scope="col">DESKRIPSI</th>
                                            <th scope="col" class="text-center">TANGGAL</th>
                                            <th scope="col" class="text-end">HARGA</th>
                                            <th scope="col" class="text-center">POSTER</th>
                                            <th scope="col" class="text-center">AKSI</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($events as $event): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($event['id_event']) ?></td>
                                                <td><?= htmlspecialchars($event['nama_event']) ?></td>
                                                <td class="text-center"><?= htmlspecialchars($event['kategori']) ?></td>
                                                <td><?= htmlspecialchars($event['deskripsi_event']) ?></td>
                                                <td class="text-center"><?= formatDate($event['tanggal_event']) ?></td>
                                                <td class="text-end"><?= formatCurrency($event['harga_tiket']) ?></td>
                                                <td class="text-center">
                                                    <?php if (!empty($event['poster_event'])): ?>
                                                        <button class="btn btn-view-poster btn-sm" onclick="viewPoster('<?= htmlspecialchars($event['id_event']) ?>', '<?= htmlspecialchars($event['nama_event']) ?>')">
                                                            <i class="fas fa-image me-1"></i>
                                                            Lihat
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="no-poster">Tidak ada poster</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-danger btn-delete" onclick="confirmDelete('<?= htmlspecialchars($event['id_event']) ?>', '<?= htmlspecialchars($event['nama_event']) ?>')">Delete</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Pagination Info -->
            <?php if (!empty($events)): ?>
                <div class="mt-3 text-muted">
                    <small>Menampilkan <?php echo count($events); ?> event</small>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                        Confirm Delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this event?</p>
                    <div class="alert alert-warning">
                        <strong id="eventNameToDelete"></strong>
                    </div>
                    <p class="text-muted small">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-1"></i>
                        Delete Event
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Poster View Modal -->
    <div class="modal fade poster-modal" id="posterModal" tabindex="-1" aria-labelledby="posterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="posterModalLabel">
                        <i class="fas fa-image text-primary me-2"></i>
                        Poster Event: <span id="posterEventName"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <div id="posterContainer">
                        <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Delete confirmation function
        function confirmDelete(eventId, eventName) {
            document.getElementById('eventNameToDelete').textContent = eventName;
            document.getElementById('confirmDeleteBtn').href = '?delete=' + eventId;

            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }

        function addNewEvent() {
            window.location.href = 'inputevent.php';
        }

        // View poster function
        function viewPoster(eventId, eventName) {
            document.getElementById('posterEventName').textContent = eventName;
            
            // Reset container with loading spinner
            document.getElementById('posterContainer').innerHTML = `
                <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('posterModal'));
            modal.show();

            // Create image element to load poster
            const img = new Image();
            img.onload = function() {
                document.getElementById('posterContainer').innerHTML = `
                    <img src="data:image/jpeg;base64,${this.src}" class="poster-image" alt="Poster ${eventName}">
                `;
            };
            img.onerror = function() {
                document.getElementById('posterContainer').innerHTML = `
                    <div class="text-center py-4">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h6 class="text-muted">Gagal memuat poster</h6>
                        <p class="text-muted small">Poster mungkin rusak atau tidak tersedia</p>
                    </div>
                `;
            };

            // Fetch poster data
            fetch(`get_poster.php?id=${eventId}`)
                .then(response => response.text())
                .then(base64Data => {
                    if (base64Data && base64Data !== 'error') {
                        document.getElementById('posterContainer').innerHTML = `
                            <img src="data:image/jpeg;base64,${base64Data}" class="poster-image" alt="Poster ${eventName}">
                        `;
                    } else {
                        document.getElementById('posterContainer').innerHTML = `
                            <div class="text-center py-4">
                                <i class="fas fa-image fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">Poster tidak tersedia</h6>
                                <p class="text-muted small">Poster untuk event ini tidak ditemukan</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('posterContainer').innerHTML = `
                        <div class="text-center py-4">
                            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                            <h6 class="text-muted">Gagal memuat poster</h6>
                            <p class="text-muted small">Terjadi kesalahan saat memuat poster</p>
                        </div>
                    `;
                });
        }
    </script>
</body>

</html>