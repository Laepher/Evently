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

        /* Banner Modal styling */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1050;
            display: none;
            justify-content: center;
            align-items: center;
        }

        .modal-overlay.show {
            display: flex;
        }

        .custom-modal {
            background: rgba(0, 0, 0, 0.8);
            border-radius: 1rem;
            width: 500px;
            max-width: 90vw;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .custom-modal-header {
            position: relative;
            padding: 0;
        }

        .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 30px;
            height: 30px;
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            font-weight: bold;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .close-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .tabs {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 1rem 1rem 0 0;
            user-select: none;
            display: flex;
        }

        .tab {
            flex: 1;
            text-align: center;
            padding: 1rem;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .tab.active {
            color: white;
            border-bottom-color: white;
        }

        .tab:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .custom-modal-content {
            padding: 2rem;
            color: white;
        }

        .upload-area {
            border: 2px dashed rgba(255, 255, 255, 0.5);
            border-radius: 12px;
            padding: 40px 20px;
            text-align: center;
            margin-bottom: 25px;
            background: rgba(255, 255, 255, 0.1);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upload-area:hover {
            border-color: rgba(255, 255, 255, 0.8);
            background: rgba(255, 255, 255, 0.15);
        }

        .upload-icon {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.8;
        }

        .upload-text {
            font-size: 16px;
            margin-bottom: 15px;
            font-weight: 500;
        }

        .pengingat {
            font-size: 12px;
            margin-bottom: 15px;
            color: rgb(246, 41, 41);
        }

        .choose-file-btn {
            color: #29b6f6;
            border-radius: 25px;
            font-weight: 600;
            background: white;
            border: none;
            padding: 0.5rem 1.5rem;
        }

        .file-input {
            display: none;
        }

        .file-preview {
            margin-top: 15px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            display: none;
        }

        .file-info {
            font-size: 14px;
            opacity: 0.9;
        }

        .form-input {
            background-color: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            color: white !important;
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.7) !important;
        }

        .form-input:focus {
            background-color: rgba(255, 255, 255, 0.15) !important;
            border-color: rgba(255, 255, 255, 0.5) !important;
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25) !important;
        }

        .apply-btn {
            background-color: #4caf50 !important;
            border-color: #4caf50 !important;
            color: white !important;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            padding: 0.75rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .apply-btn:hover {
            background-color: #45a049 !important;
            border-color: #45a049 !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(76, 175, 80, 0.3);
        }

        /* Atur Banner button styling */
        .btn-banner {
            background: linear-gradient(135deg, rgba(0, 140, 255, 0.46) 0%, rgb(0, 153, 255) 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.625rem 1.25rem;
            border-radius: 0.375rem;
            transition: all 0.15s ease-in-out;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.875rem;
        }

        .btn-banner:hover {
            background: linear-gradient(135deg, rgba(0, 120, 255, 0.6) 0%, rgb(0, 130, 255) 100%);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 153, 255, 0.25);
        }

        .btn-banner:focus {
            box-shadow: 0 0 0 0.2rem rgba(0, 153, 255, 0.5);
        }
    </style>
</head>

<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid px-3">
            <span class="navbar-brand fw-bold fs-5 mb-0">EVENTLY</span>
            <div class="d-flex align-items-center">
                <a href="dashboardadmin.php" class="admin-username">DASHBOARD</a>
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
                <div class="d-flex justify-content-between align-items-center mb-4 header-controls">
                    <h2 class="text-primary-custom fw-bold">DAFTAR EVENT</h2>

                    <div class="d-flex" style="gap: 1rem;">
                        <button class="btn btn-add" onclick="addNewEvent()">
                            <i class="fas fa-plus me-2"></i>
                            TAMBAHKAN
                        </button>

                        <button class="btn btn-banner" onclick="openModal()">
                            Atur Banner
                        </button>
                    </div>
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

    <!-- Atur Banner Modal -->
    <div class="modal-overlay" id="modalOverlay">
        <div class="custom-modal">
            <div class="custom-modal-header">
                <button class="close-btn" onclick="closeModal()">&times;</button>
                <div class="tabs">
                    <div class="tab active" data-tab="1">Banner 1</div>
                    <div class="tab" data-tab="2">Banner 2</div>
                    <div class="tab" data-tab="3">Banner 3</div>
                </div>
            </div>

            <div class="custom-modal-content">
                <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                    <div class="upload-icon">📷</div>
                    <div class="upload-text">Seret atau pilih file</div>
                    <button type="button" class="choose-file-btn">Pilih File</button>
                    <input type="file" id="fileInput" class="file-input" accept="image/*" onchange="handleFileSelect(event)">
                    <div class="file-preview" id="filePreview">
                        <div class="file-info" id="fileInfo"></div>
                    </div>
                </div>

                <div class="form-group mb-4">
                    <label class="form-label fw-semibold" for="description">Masukkan Deskripsi Singkat:</label>
                    <textarea class="form-control form-input" id="description" placeholder="Tulis deskripsi banner di sini..." rows="3" style="resize: vertical; min-height: 80px;"></textarea>
                </div>

                <div class="pengingat">
                    Gunakan resolusi 16:9 agar tampilan tidak hancur!
                </div>

                <button class="btn apply-btn w-100" onclick="applyBanner()">Apply</button>
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

        // Banner Modal variables and functions
        let currentTab = 1;
        let selectedFile = null;

        function openModal() {
            const modalOverlay = document.getElementById('modalOverlay');
            modalOverlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            const modalOverlay = document.getElementById('modalOverlay');
            modalOverlay.classList.remove('show');
            document.body.style.overflow = 'auto';
            resetForm();
            resetTabs();
        }

        // Close modal if clicking outside modal (overlay)
        document.getElementById('modalOverlay').addEventListener('click', function(event) {
            if (event.target === this) {
                closeModal();
            }
        });

        // Tab functionality
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.tab').forEach(t => {
                    t.classList.remove('active');
                });
                this.classList.add('active');
                currentTab = this.getAttribute('data-tab');
                resetForm();
            });
        });

        // File select handler
        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                selectedFile = file;
                const filePreview = document.getElementById('filePreview');
                const fileInfo = document.getElementById('fileInfo');

                fileInfo.innerHTML = `
                    <strong>File dipilih:</strong> ${file.name}<br>
                    <strong>Ukuran:</strong> ${(file.size / 1024 / 1024).toFixed(2)} MB<br>
                    <strong>Tipe:</strong> ${file.type}
                `;

                filePreview.style.display = 'block';

                // Add visual feedback to upload area
                const uploadArea = document.querySelector('.upload-area');
                uploadArea.style.borderColor = '#4caf50';
                uploadArea.style.backgroundColor = 'rgba(76, 175, 80, 0.1)';
            }
        }

        // Reset form inputs inside modal
        function resetForm() {
            selectedFile = null;
            document.getElementById('fileInput').value = '';
            document.getElementById('description').value = '';
            document.getElementById('filePreview').style.display = 'none';

            const uploadArea = document.querySelector('.upload-area');
            uploadArea.style.borderColor = 'rgba(255, 255, 255, 0.5)';
            uploadArea.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
        }

        // Reset tabs styling and set first tab active
        function resetTabs() {
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach((tab, index) => {
                tab.classList.remove('active');
                if (index === 0) {
                    tab.classList.add('active');
                    currentTab = 1;
                }
            });
        }

        // Apply banner function
        function applyBanner() {
            const description = document.getElementById('description').value.trim();
            if (!selectedFile) {
                alert('Silakan pilih file terlebih dahulu!');
                return;
            }
            if (!description) {
                alert('Silakan masukkan deskripsi!');
                return;
            }

            const formData = new FormData();
            formData.append('banner_id', currentTab);
            formData.append('description', description);
            formData.append('banner_image', selectedFile);

            fetch('aturbanner_upload.php', {
                    method: 'POST',
                    body: formData
                })

                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`Banner ${currentTab} berhasil diatur!`);
                        closeModal();
                        // Optionally reload or update homepage carousel preview if you add that feature later
                    } else {
                        alert(`Error: ${data.error || 'Gagal mengatur banner'}`);
                    }
                })
                .catch(() => alert('Terjadi kesalahan dalam mengupload banner.'));
        }

        //Sampe Sini

        // Keyboard shortcuts (Escape to close modal)
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modalOverlay = document.getElementById('modalOverlay');
                if (modalOverlay.classList.contains('show')) {
                    closeModal();
                }
            }
        });

        // Drag and drop for upload area
        const uploadArea = document.querySelector('.upload-area');

        uploadArea.addEventListener('dragover', function(event) {
            event.preventDefault();
            this.style.borderColor = '#4caf50';
            this.style.backgroundColor = 'rgba(76, 175, 80, 0.2)';
        });

        uploadArea.addEventListener('dragleave', function(event) {
            event.preventDefault();
            this.style.borderColor = 'rgba(255, 255, 255, 0.5)';
            this.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
        });

        uploadArea.addEventListener('drop', function(event) {
            event.preventDefault();
            this.style.borderColor = 'rgba(255, 255, 255, 0.5)';
            this.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';

            const files = event.dataTransfer.files;
            if (files.length > 0) {
                const file = files[0];
                if (file.type.startsWith('image/')) {
                    selectedFile = file;

                    const filePreview = document.getElementById('filePreview');
                    const fileInfo = document.getElementById('fileInfo');

                    fileInfo.innerHTML = `
                        <strong>File dipilih:</strong> ${file.name}<br>
                        <strong>Ukuran:</strong> ${(file.size / 1024 / 1024).toFixed(2)} MB<br>
                        <strong>Tipe:</strong> ${file.type}
                    `;

                    filePreview.style.display = 'block';
                    this.style.borderColor = '#4caf50';
                    this.style.backgroundColor = 'rgba(76, 175, 80, 0.1)';
                } else {
                    alert('Silakan pilih file gambar!');
                }
            }
        });
    </script>
</body>

</html>