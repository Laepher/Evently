<?php

include 'config/config.php';
// Sample data - Replace this section with database connection

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

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Evently - Pemesanan Tiket</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Admin Styles -->
    <link rel="stylesheet" href="style/admin-styles.css">
    <!-- SweetAlert2 untuk notifikasi -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Additional styles for status dropdown */
        .status-dropdown {
            border: 1px solid #dee2e6;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            min-width: 120px;
        }

        .status-dropdown option[value="Confirmed"] {
            color: #28a745;
        }

        .status-dropdown option[value="Pending"] {
            color: #ffc107;
        }

        .status-dropdown option[value="Cancelled"] {
            color: #dc3545;
        }

        /* Loading overlay styles */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Status badge colors */
        .status-confirmed {
            background-color: #d4edda !important;
            color: #155724 !important;
        }

        .status-pending {
            background-color: #fff3cd !important;
            color: #856404 !important;
        }

        .status-cancelled {
            background-color: #f8d7da !important;
            color: #721c24 !important;
        }
    </style>
</head>

<body class="bg-light">
    <!-- Loading overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

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
                <!-- Header with Search -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="text-primary-custom fw-bold">PEMESANAN TIKET</h2>
                    <button class="btn btn-primary" onclick="refreshPage()">
                        <i class="fas fa-refresh"></i> Refresh
                    </button>
                </div>

                <!-- Orders Table -->
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th scope="col" class="text-center" style="width: 80px;">ID PESANAN</th>
                                        <th scope="col" class="text-center" style="width: 80px;">ID TIKET</th>
                                        <th scope="col" class="text-center" style="width: 80px;">ID USER</th>
                                        <th scope="col" class="text-center">METODE BAYAR</th>
                                        <th scope="col" class="text-center" style="width: 80px;">BANYAK TIKET</th>
                                        <th scope="col" class="text-center">TOTAL HARGA</th>
                                        <th scope="col" class="text-center" style="width: 130px;">STATUS</th>
                                        <th scope="col" class="text-center">TANGGAL PESANAN</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "SELECT * FROM pesanan ORDER BY tanggal_pesanan DESC";
                                    $result = mysqli_query($conn, $query);

                                    if (mysqli_num_rows($result) > 0) {
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            echo "<tr>";
                                            echo "<td class='text-center'>" . htmlspecialchars($row['id_pesanan']) . "</td>";
                                            echo "<td class='text-center'>" . htmlspecialchars($row['id_tiket']) . "</td>";
                                            echo "<td class='text-center'>" . htmlspecialchars($row['id_user']) . "</td>";
                                            echo "<td class='text-center'>" . htmlspecialchars($row['metode_bayar']) . "</td>";
                                            echo "<td class='text-center'>" . htmlspecialchars($row['banyak_tiket']) . "</td>";
                                            echo "<td class='text-center'>Rp " . number_format($row['total_harga'], 0, ',', '.') . "</td>";

                                            // Status dropdown with interactive update functionality
                                            echo "<td class='text-center'>";
                                            echo "<select class='form-select form-select-sm status-dropdown' ";
                                            echo "data-order-id='" . htmlspecialchars($row['id_pesanan']) . "' ";
                                            echo "onchange='updateStatus(this)'>";

                                            $current_status = strtolower($row['status_pesanan']);
                                            $statuses = [
                                                'confirmed' => 'Confirmed',
                                                'terbayar' => 'Confirmed',
                                                'pending' => 'Pending',
                                                'menunggu' => 'Pending',
                                                'cancelled' => 'Cancelled',
                                                'dibatalkan' => 'Cancelled'
                                            ];

                                            $mapped_status = isset($statuses[$current_status]) ? $statuses[$current_status] : 'Pending';

                                            foreach (['Confirmed', 'Pending', 'Cancelled'] as $status) {
                                                $selected = ($mapped_status === $status) ? 'selected' : '';
                                                echo "<option value='$status' $selected>$status</option>";
                                            }
                                            echo "</select>";
                                            echo "</td>";

                                            echo "<td class='text-center'>" . formatDate($row['tanggal_pesanan']) . "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='8' class='text-center py-4'>Tidak ada data pesanan</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Summary Statistics -->
                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-primary">Total Pesanan</h5>
                                <?php
                                $total_query = "SELECT COUNT(*) as total FROM pesanan";
                                $total_result = mysqli_query($conn, $total_query);
                                $total_row = mysqli_fetch_assoc($total_result);
                                ?>
                                <h3 class="text-primary"><?= $total_row['total'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-success">Dikonfirmasi</h5>
                                <?php
                                $confirmed_query = "SELECT COUNT(*) as confirmed FROM pesanan WHERE status_pesanan IN ('confirmed', 'terbayar')";
                                $confirmed_result = mysqli_query($conn, $confirmed_query);
                                $confirmed_row = mysqli_fetch_assoc($confirmed_result);
                                ?>
                                <h3 class="text-success"><?= $confirmed_row['confirmed'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-warning">Pending</h5>
                                <?php
                                $pending_query = "SELECT COUNT(*) as pending FROM pesanan WHERE status_pesanan IN ('pending', 'menunggu')";
                                $pending_result = mysqli_query($conn, $pending_query);
                                $pending_row = mysqli_fetch_assoc($pending_result);
                                ?>
                                <h3 class="text-warning"><?= $pending_row['pending'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-danger">Dibatalkan</h5>
                                <?php
                                $cancelled_query = "SELECT COUNT(*) as cancelled FROM pesanan WHERE status_pesanan IN ('cancelled', 'dibatalkan')";
                                $cancelled_result = mysqli_query($conn, $cancelled_query);
                                $cancelled_row = mysqli_fetch_assoc($cancelled_result);
                                ?>
                                <h3 class="text-danger"><?= $cancelled_row['cancelled'] ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Status Update JavaScript -->
    <script>
        // ===== FIXED JavaScript untuk pemesanantiket.php =====

        // Show/hide loading overlay
        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').style.display = 'none';
        }

        // Handle status update dengan AJAX - FIXED VERSION
        function updateStatus(selectElement) {
            const orderId = selectElement.getAttribute('data-order-id');
            const newStatus = selectElement.value;
            const originalValue = selectElement.getAttribute('data-original-value') || selectElement.value;

            // Set original value jika belum ada
            if (!selectElement.getAttribute('data-original-value')) {
                selectElement.setAttribute('data-original-value', originalValue);
            }

            // Validasi input
            if (!orderId || !newStatus) {
                console.error('Missing order ID or status');
                return;
            }

            console.log('Updating status:', {
                orderId: orderId,
                newStatus: newStatus,
                originalValue: originalValue
            });

            // Tampilkan loading
            showLoading();
            selectElement.disabled = true;

            // Prepare data - Using FormData for better compatibility
            const formData = new FormData();
            formData.append('order_id', orderId);
            formData.append('status', newStatus);

            // Kirim request AJAX ke server
            fetch('update_pesanan.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Response status:', response.status);

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    return response.text(); // Get as text first for debugging
                })
                .then(text => {
                    console.log('Raw response:', text);

                    // Try to parse as JSON
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (parseError) {
                        console.error('JSON parse error:', parseError);
                        throw new Error('Invalid JSON response: ' + text.substring(0, 200));
                    }

                    return data;
                })
                .then(data => {
                    console.log('Parsed response:', data);

                    hideLoading();
                    selectElement.disabled = false;

                    if (data.success) {
                        // Update berhasil
                        selectElement.setAttribute('data-original-value', newStatus);

                        // Tampilkan visual feedback
                        const row = selectElement.closest('tr');

                        // Remove old status classes
                        row.classList.remove('status-confirmed', 'status-pending', 'status-cancelled');

                        // Add appropriate class for new status
                        switch (newStatus.toLowerCase()) {
                            case 'confirmed':
                                row.classList.add('status-confirmed');
                                break;
                            case 'pending':
                                row.classList.add('status-pending');
                                break;
                            case 'cancelled':
                                row.classList.add('status-cancelled');
                                break;
                        }

                        // Success animation
                        const originalBg = row.style.backgroundColor;
                        row.style.backgroundColor = '#d4edda';
                        row.style.transition = 'background-color 0.3s ease';

                        setTimeout(() => {
                            row.style.backgroundColor = originalBg;
                            setTimeout(() => {
                                row.classList.remove('status-confirmed', 'status-pending', 'status-cancelled');
                                row.style.transition = '';
                            }, 300);
                        }, 2000);

                        // Show success notification
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: `Status pesanan #${orderId} berhasil diubah ke ${newStatus}`,
                                timer: 3000,
                                timerProgressBar: true,
                                showConfirmButton: false,
                                toast: true,
                                position: 'top-end'
                            });
                        } else {
                            // Fallback alert
                            alert(`Status pesanan #${orderId} berhasil diubah ke ${newStatus}`);
                        }

                        // Update statistics after delay
                        setTimeout(() => {
                            updateStatistics();
                        }, 1000);

                    } else {
                        // Update failed - revert to original value
                        selectElement.value = originalValue;

                        const errorMessage = data.message || 'Terjadi kesalahan saat mengupdate status';
                        console.error('Update failed:', errorMessage);

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: errorMessage,
                                confirmButtonText: 'OK'
                            });
                        } else {
                            alert('Gagal: ' + errorMessage);
                        }
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);

                    hideLoading();
                    selectElement.disabled = false;
                    selectElement.value = originalValue; // Revert to original value

                    const errorMessage = error.message || 'Terjadi kesalahan koneksi';

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: errorMessage,
                            confirmButtonText: 'OK'
                        });
                    } else {
                        alert('Error: ' + errorMessage);
                    }
                });
        }

        // Function untuk update statistik tanpa refresh halaman
        function updateStatistics() {
            // Fetch updated statistics
            fetch('get_statistics.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update each statistic card
                        const totalElement = document.querySelector('.card-body h3.text-primary');
                        const confirmedElement = document.querySelector('.card-body h3.text-success');
                        const pendingElement = document.querySelector('.card-body h3.text-warning');
                        const cancelledElement = document.querySelector('.card-body h3.text-danger');

                        if (totalElement) totalElement.textContent = data.total || '0';
                        if (confirmedElement) confirmedElement.textContent = data.confirmed || '0';
                        if (pendingElement) pendingElement.textContent = data.pending || '0';
                        if (cancelledElement) cancelledElement.textContent = data.cancelled || '0';
                    }
                })
                .catch(error => {
                    console.log('Statistics update failed:', error);
                    // Optionally refresh page as fallback
                    // setTimeout(() => window.location.reload(), 3000);
                });
        }

        // Function untuk refresh halaman
        function refreshPage() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Refresh Halaman?',
                    text: 'Halaman akan dimuat ulang untuk menampilkan data terbaru',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Refresh',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.reload();
                    }
                });
            } else {
                if (confirm('Refresh halaman untuk menampilkan data terbaru?')) {
                    window.location.reload();
                }
            }
        }

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Initializing status dropdowns...');

            // Set initial original values for all dropdowns
            const dropdowns = document.querySelectorAll('.status-dropdown');
            dropdowns.forEach(dropdown => {
                dropdown.setAttribute('data-original-value', dropdown.value);
                console.log('Initialized dropdown for order:', dropdown.getAttribute('data-order-id'), 'with value:', dropdown.value);
            });

            console.log('Initialized', dropdowns.length, 'status dropdowns');
        });

        // Prevent accidental page leave if there are unsaved changes
        let hasUnsavedChanges = false;

        window.addEventListener('beforeunload', function(e) {
            if (hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = 'Ada perubahan yang belum disimpan. Yakin ingin meninggalkan halaman?';
                return e.returnValue;
            }
        });

        // Track changes
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('status-dropdown')) {
                const originalValue = e.target.getAttribute('data-original-value');
                hasUnsavedChanges = (e.target.value !== originalValue);
            }
        });
    </script>
</body>

</html>