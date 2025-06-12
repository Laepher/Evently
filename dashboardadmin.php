<?php
include 'config/config.php';
session_start();

// Check database connection
if (!isset($conn) || !$conn) {
    die("Database connection failed");
}

// Function to get booking data with limit
function getDaftarPemesanan($conn, $limit = 3) {
    $bookings = [];
    
    try {
        // Updated query with proper JOIN and formatting
        $query = "SELECT 
                    p.id_pesanan,
                    p.id_tiket,
                    p.id_user,
                    e.nama_event,
                    u.username as buyer_name,
                    p.banyak_tiket as quantity,
                    DATE_FORMAT(p.tanggal_pesanan, '%d/%m/%Y') as order_date,
                    p.status_pesanan,
                    t.harga_tiket,
                    p.total_harga,
                    p.metode_bayar
                  FROM pesanan p
                  LEFT JOIN tiket t ON p.id_tiket = t.id_tiket
                  LEFT JOIN event e ON t.id_event = e.id_event
                  LEFT JOIN user u ON p.id_user = u.id_user
                  ORDER BY p.tanggal_pesanan DESC
                  LIMIT ?";
        
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $limit);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $bookings[] = $row;
                }
            }
            mysqli_stmt_close($stmt);
        }
        
    } catch (Exception $e) {
        // Fallback with sample data if query fails
        error_log("Database error in getDaftarPemesanan: " . $e->getMessage());
        return [];
    }
    
    return $bookings;
}

// Function to get total count of orders
function getTotalOrdersCount($conn) {
    try {
        $query = "SELECT COUNT(*) as total FROM pesanan";
        $result = mysqli_query($conn, $query);
        
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            return (int)$row['total'];
        }
    } catch (Exception $e) {
        error_log("Database error in getTotalOrdersCount: " . $e->getMessage());
    }
    
    return 0;
}

// Function to get event data
function getEventData($conn, $limit = 10) {
    $events = [];
    
    try {
        $query = "SELECT e.id_event, e.nama_event, t.id_tiket, e.tanggal_event
                  FROM event e
                  LEFT JOIN tiket t ON e.id_event = t.id_event
                  ORDER BY e.tanggal_event ASC
                  LIMIT ?";
        
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $limit);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $events[] = $row;
                }
            }
            mysqli_stmt_close($stmt);
        }
    } catch (Exception $e) {
        error_log("Database error in getEventData: " . $e->getMessage());
    }
    
    return $events;
}

// Get data for dashboard
$bookings = getDaftarPemesanan($conn, 3); // Limit to 3 for dashboard
$totalOrders = getTotalOrdersCount($conn);
$events = getEventData($conn, 10);

// For debugging - remove in production
error_log("Dashboard Debug - Total orders: " . $totalOrders . ", Bookings count: " . count($bookings));

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Evently - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        :root {
            --primary-color: #3B8BFF;
            --text-gray: #6b7280;
            --light-shadow: rgba(0, 0, 0, 0.05);
            --border-radius: 0.75rem;
            --background-white: #ffffff;
            --heading-color: #111827;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            color: var(--text-gray);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .navbar-custom {
            background-color: var(--primary-color) !important;
        }

        .admin-username {
            color: #fff !important;
            font-weight: 500;
            margin-right: 1rem;
            user-select: none;
        }

        .logout-link {
            color: #ffcccb;
            font-weight: 600;
            text-decoration: none;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.3s ease;
            cursor: pointer;
            user-select: none;
        }

        .logout-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
            text-decoration: none;
            transform: translateY(-1px);
        }

        .sidebar {
            min-height: calc(100vh - 56px);
            padding: 2rem 1rem;
            background-color: var(--background-white);
            box-shadow: 2px 0 8px var(--light-shadow);
        }

        .sidebar h5 {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            user-select: none;
        }

        .sidebar .nav-link {
            color: var(--primary-color);
            font-weight: 600;
            padding: 0.5rem 0;
            border-left: 3px solid transparent;
            transition: border-color 0.3s ease;
            user-select: none;
            cursor: pointer;
        }

        .sidebar .nav-link:hover {
            border-color: var(--primary-color);
            text-decoration: none;
        }

        .main-content {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .account-section {
            background-color: var(--primary-color);
            color: white;
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            user-select: none;
        }

        .account-icon {
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        /* Unified card style for sections */
        .card-section {
            background-color: var(--background-white);
            border-radius: var(--border-radius);
            box-shadow: 0 4px 12px var(--light-shadow);
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            display: flex;
            flex-direction: column;
            user-select: none;
        }

        /* Section titles */
        .section-title {
            font-weight: 700;
            font-size: 28px;
            color: var(--heading-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: space-between;
        }

        .section-icon {
            color: var(--primary-color);
            font-size: 28px;
        }

        /* Scrollable container for tables */
        .table-responsive-scroll {
            overflow-y: auto;
            max-height: 400px;
            border-radius: var(--border-radius);
            box-shadow: inset 0 0 6px #ddd;
        }

        /* Unified table style */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.9rem;
            color: #212529;
        }

        thead {
            background-color: #f8f9fa;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        th, td {
            padding: 12px 16px;
            text-align: left;
            vertical-align: middle;
            color: #212529;
            border-bottom: 1px solid #dee2e6;
        }

        tbody tr:hover {
            background-color: rgba(59, 139, 255, 0.1);
            cursor: default;
        }

        tbody tr:nth-child(even) {
            background-color: rgba(0, 0, 0, 0.03);
        }

        /* Status badge styles */
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-confirmed, .status-terbayar {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-paid {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-pending, .status-menunggu {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-cancelled, .status-dibatalkan {
            background-color: #fee2e2;
            color: #991b1b;
        }

        /* View more button */
        .view-more-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .view-more-btn:hover {
            background-color: #2563eb;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }

        /* Order count badge */
        .order-count-badge {
            background-color: rgba(59, 139, 255, 0.1);
            color: var(--primary-color);
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }

        /* Enhanced empty state */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state p {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
            }

            .table-responsive-scroll {
                max-height: none;
            }

            .section-title {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            th, td {
                padding: 8px 12px;
                font-size: 0.8rem;
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
                <a href="dashboardadmin.php" class="admin-username">DASHBOARD</a>
                <a href="#" class="logout-link" onclick="logout()">LOG OUT</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 col-lg-2 sidebar bg-white p-4">
                <h5 class="fw-bold text-primary-custom mb-4">KELOLA</h5>
                <nav class="nav flex-column">
                    <a href="daftarevent.php" class="nav-link text-primary-custom fw-semibold p-0 mb-3">DAFTAR EVENT</a>
                    <a href="daftaruser.php" class="nav-link text-primary-custom fw-semibold p-0 mb-3">DAFTAR USER</a>
                    <a href="pemesanantiket.php" class="nav-link text-primary-custom fw-semibold p-0 mb-3">PEMESANAN TIKET</a>
                    <a href="pembayaran.php" class="nav-link text-primary-custom fw-semibold p-0 mb-3">PEMBAYARAN USER</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 col-lg-10 main-content p-4">
                <!-- Account Section -->
                <div class="account-section">
                    <div class="account-icon">
                        <i class="fas fa-user fa-lg"></i>
                    </div>
                    <h4 class="mb-0 fw-bold">AKUN ADMIN</h4>
                </div>

                <div class="row">
                    <!-- Bookings Section -->
                    <div class="col-md-6">
                        <section class="card-section">
                            <div class="section-title">
                                <span>
                                    <i class="fas fa-bell section-icon"></i>
                                    DAFTAR PEMESANAN
                                    <?php if ($totalOrders > 0): ?>
                                        <span class="order-count-badge">
                                            <?= $totalOrders ?> total
                                        </span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <div class="table-responsive-scroll">
                                <?php if (!empty($bookings)): ?>
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>EVENT</th>
                                                <th>PEMBELI</th>
                                                <th>QTY</th>
                                                <th>STATUS</th>
                                                <th>TOTAL</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($bookings as $booking): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($booking['id_pesanan'] ?? '-') ?></td>
                                                    <td>
                                                        <div style="max-width: 120px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" 
                                                             title="<?= htmlspecialchars($booking['nama_event'] ?? 'Event tidak ditemukan') ?>">
                                                            <?= htmlspecialchars($booking['nama_event'] ?? 'Event tidak ditemukan') ?>
                                                        </div>
                                                    </td>
                                                    <td><?= htmlspecialchars($booking['buyer_name'] ?? 'User #' . ($booking['id_user'] ?? '-')) ?></td>
                                                    <td><?= (int)($booking['quantity'] ?? 0) ?></td>
                                                    <td>
                                                        <span class="status-badge status-<?= htmlspecialchars(strtolower($booking['status_pesanan'] ?? 'pending')) ?>">
                                                            <?= htmlspecialchars($booking['status_pesanan'] ?? 'pending') ?>
                                                        </span>
                                                    </td>
                                                    <td>Rp <?= number_format($booking['total_harga'] ?? 0, 0, ',', '.') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    
                                <?php else: ?>
                                    <div class="empty-state">
                                        <a href="pemesanantiket.php" class="view-more-btn">
                                            <i class="fas fa-list"></i>
                                            Kelola Pemesanan
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </section>
                    </div>

                    <!-- Events Section -->
                    <div class="col-md-6">
                        <section class="card-section">
                            <div class="section-title">
                                <span>
                                    <i class="fas fa-calendar-alt section-icon"></i>
                                    DAFTAR EVENT
                                </span>
                                <a href="daftarevent.php" class="view-more-btn">
                                    <i class="fas fa-eye"></i>
                                    Lihat Semua
                                </a>
                            </div>
                            
                            <div class="table-responsive-scroll">
                                <?php if (!empty($events)): ?>
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>ID EVENT</th>
                                                <th>NAMA EVENT</th>
                                                <th>ID TIKET</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($events as $event): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($event['id_event']) ?></td>
                                                    <td>
                                                        <div style="max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" 
                                                             title="<?= htmlspecialchars($event['nama_event']) ?>">
                                                            <?= htmlspecialchars($event['nama_event']) ?>
                                                        </div>
                                                    </td>
                                                    <td><?= htmlspecialchars($event['id_tiket'] ?? '-') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-calendar-alt"></i>
                                        <p>Belum ada event</p>
                                        <small class="text-muted">Event yang dibuat akan muncul di sini</small>
                                        <br><br>
                                        <a href="daftarevent.php" class="view-more-btn">
                                            <i class="fas fa-plus"></i>
                                            Tambah Event
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function logout() {
            if (confirm('Apakah Anda yakin ingin logout?')) {
                window.location.href = 'login.php';
            }
        }

        // Auto-refresh data setiap 60 detik jika halaman aktif
        let refreshInterval;
        
        function startAutoRefresh() {
            refreshInterval = setInterval(function() {
                if (document.visibilityState === 'visible') {
                    // Soft refresh - hanya refresh bagian data tanpa reload halaman penuh
                    refreshDashboardData();
                }
            }, 60000); // 60 detik
        }

        function stopAutoRefresh() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        }

        // Function untuk refresh data via AJAX (opsional)
        function refreshDashboardData() {
            // Implementasi AJAX refresh bisa ditambahkan di sini
            console.log('Refreshing dashboard data...');
            
            // Contoh implementasi sederhana - bisa dikembangkan lebih lanjut
            fetch('dashboard_refresh.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Dashboard data refreshed successfully');
                        // Update tampilan jika diperlukan
                    }
                })
                .catch(error => {
                    console.log('Auto-refresh failed:', error);
                });
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Dashboard loaded successfully');
            console.log('Total orders:', <?= $totalOrders ?>);
            console.log('Displayed bookings:', <?= count($bookings) ?>);
            
            // Start auto refresh
            startAutoRefresh();
        });

        // Stop auto refresh when page is not visible
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                startAutoRefresh();
            } else {
                stopAutoRefresh();
            }
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            stopAutoRefresh();
        });
    </script>
</body>

</html>


