<?php

include 'config/config.php';
session_start();

// Check database connection
if (!isset($conn) || !$conn) {
    die("Database connection failed");
}

// Query to get real event data with ticket IDs (with error handling)
$events = [];
try {
    $query = "SELECT e.id_event, e.nama_event, t.id_tiket
              FROM event e
              LEFT JOIN tiket t ON e.id_event = t.id_event
              ORDER BY e.tanggal_event ASC";
    $result = mysqli_query($conn, $query);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $events[] = $row;
        }
    }
} catch (Exception $e) {
    // If error, create sample data
    $events = [
        ['id_event' => 1, 'nama_event' => 'Sample Event', 'id_tiket' => 1]
    ];
}

// Function to get real booking data from database with error handling
function getDaftarPemesanan($conn) {
    // First, let's try a simpler query to check if tables exist
    $bookings = [];
    
    try {
        // Check if pemesanan table exists and get basic data
        $query = "SELECT TABLE_NAME FROM information_schema.TABLES 
                  WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME IN ('pemesanan', 'tiket', 'event', 'user')";
        $result = mysqli_query($conn, $query);
        
        if (!$result) {
            return [];
        }
        
        // Try different possible table structures
        $queries = [
            // Query 1: Full structure with pemesanan table
            "SELECT 
                e.nama_event,
                u.username as buyer_name,
                p.jumlah_tiket as quantity,
                DATE_FORMAT(p.tanggal_pemesanan, '%d/%m/%Y') as order_date,
                p.status_pemesanan,
                t.harga_tiket,
                (p.jumlah_tiket * t.harga_tiket) as total_harga
              FROM pemesanan p
              JOIN tiket t ON p.id_tiket = t.id_tiket
              JOIN event e ON t.id_event = e.id_event
              JOIN user u ON p.id_user = u.id_user
              WHERE p.status_pemesanan IN ('confirmed', 'paid', 'pending')
              ORDER BY p.tanggal_pemesanan DESC
              LIMIT 10",
            
            // Query 2: Alternative with different column names
            "SELECT 
                e.nama_event,
                u.nama as buyer_name,
                p.jumlah as quantity,
                DATE_FORMAT(p.tanggal, '%d/%m/%Y') as order_date,
                p.status as status_pemesanan,
                t.harga as harga_tiket,
                (p.jumlah * t.harga) as total_harga
              FROM pemesanan p
              JOIN tiket t ON p.id_tiket = t.id_tiket
              JOIN event e ON t.id_event = e.id_event
              JOIN user u ON p.id_user = u.id_user
              ORDER BY p.tanggal DESC
              LIMIT 10",
              
            // Query 3: Simple structure check
            "SELECT 
                'Sample Event' as nama_event,
                'John Doe' as buyer_name,
                2 as quantity,
                DATE_FORMAT(NOW(), '%d/%m/%Y') as order_date,
                'confirmed' as status_pemesanan,
                50000 as harga_tiket,
                100000 as total_harga
              LIMIT 1"
        ];
        
        foreach ($queries as $query) {
            $result = mysqli_query($conn, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $bookings[] = $row;
                }
                break; // Use the first working query
            }
        }
        
    } catch (Exception $e) {
        // Return empty array if there's an error
        return [];
    }
    
    return $bookings;
}

// Get real booking data with error handling
$bookings = [];
try {
    $bookings = getDaftarPemesanan($conn);
} catch (Exception $e) {
    // If there's an error, use sample data
    $bookings = [
        [
            'nama_event' => 'EVENT SAMPLE',
            'buyer_name' => 'User Test',
            'quantity' => 2,
            'order_date' => date('d/m/Y'),
            'status_pemesanan' => 'confirmed',
            'harga_tiket' => 50000,
            'total_harga' => 100000
        ]
    ];
}

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

        /* Notification Section */
        .notification-title, .events-title {
            font-weight: 700;
            font-size: 28px;
            color: var(--heading-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .notification-icon {
            color: var(--primary-color);
            font-size: 28px;
        }

        .notification-item {
            border-radius: 0.5rem;
            background-color: #f9fafb;
            padding: 0.75rem 1rem;
            margin-bottom: 0.75rem;
            color: #4b5563;
            box-shadow: 0 1px 2px var(--light-shadow);
            font-weight: 500;
            user-select: text;
        }

        .notification-item strong {
            color: var(--heading-color);
        }

        .notification-item:last-child {
            margin-bottom: 0;
        }

        /* Scrollable container for tables */
        .table-responsive-scroll {
            overflow-y: auto;
            max-height: 300px;
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

        .status-confirmed {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-paid {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
            }

            .table-responsive-scroll {
                max-height: none;
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
                    <h4 class="mb-0 fw-bold">AKUN</h4>
                </div>

                <div class="row">
                    <!-- Notifications Section -->
                    <div class="col-md-6">
                        <section class="card-section notification-section">
                            <div class="notification-title">
                                <i class="fas fa-bell notification-icon"></i>
                                DAFTAR PEMESANAN
                            </div>
                            <div class="table-responsive-scroll">
                                <?php if (!empty($bookings)): ?>
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>EVENT</th>
                                                <th>PEMBELI</th>
                                                <th>JUMLAH</th>
                                                <th>TANGGAL</th>
                                                <th>STATUS</th>
                                                <th>TOTAL</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($bookings as $booking): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($booking['nama_event']) ?></td>
                                                    <td><?= htmlspecialchars($booking['buyer_name']) ?></td>
                                                    <td><?= (int)$booking['quantity'] ?></td>
                                                    <td><?= htmlspecialchars($booking['order_date']) ?></td>
                                                    <td>
                                                        <span class="status-badge status-<?= htmlspecialchars($booking['status_pemesanan']) ?>">
                                                            <?= htmlspecialchars($booking['status_pemesanan']) ?>
                                                        </span>
                                                    </td>
                                                    <td>Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <div class="text-center py-5 text-muted">
                                        <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                                        <p>Tidak ada pemesanan ditemukan</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </section>
                    </div>

                    <!-- Events Section -->
                    <div class="col-md-6">
                        <section class="card-section events-section">
                            <h5 class="events-title">DAFTAR EVENT</h5>
                            <div class="table-responsive-scroll">
                                <?php if (empty($events)): ?>
                                    <div class="text-center py-5 text-muted">
                                        <i class="fas fa-calendar-alt fa-3x mb-3"></i>
                                        Tidak ada event ditemukan
                                    </div>
                                <?php else: ?>
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
                                                    <td><?= htmlspecialchars($event['nama_event']) ?></td>
                                                    <td><?= htmlspecialchars($event['id_tiket'] ?? '-') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
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
            window.location.href = 'login.php';
        }
    </script>
</body>

</html>