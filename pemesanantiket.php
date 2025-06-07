<?php

include 'config/config.php';
// Sample data - Replace this section with database connection

// Function to format currency
function formatCurrency($amount)
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Function to format date
function formatDate($date) {
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
                                        <th scope="col" class="text-center">STATUS</th>
                                        <th scope="col" class="text-center">TGL PESANAN</th>
                                        <th scope="col" class="text-center">TGL BAYAR</th>
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
                                            
                                            // Status with color coding
                                            $status = strtolower($row['status_pesanan']);
                                            $statusClass = '';
                                            switch($status) {
                                                case 'confirmed':
                                                case 'dikonfirmasi':
                                                    $statusClass = 'text-success fw-bold';
                                                    break;
                                                case 'pending':
                                                case 'menunggu':
                                                    $statusClass = 'text-warning fw-bold';
                                                    break;
                                                case 'cancelled':
                                                case 'dibatalkan':
                                                    $statusClass = 'text-danger fw-bold';
                                                    break;
                                                default:
                                                    $statusClass = 'text-secondary';
                                            }
                                            echo "<td class='text-center'><span class='" . $statusClass . "'>" . strtoupper($row['status_pesanan']) . "</span></td>";
                                            
                                            echo "<td class='text-center'>" . formatDate($row['tanggal_pesanan']) . "</td>";
                                            echo "<td class='text-center'>" . formatDate($row['tanggal_bayar']) . "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='9' class='text-center py-4'>Tidak ada data pesanan</td></tr>";
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
                                $confirmed_query = "SELECT COUNT(*) as confirmed FROM pesanan WHERE status_pesanan IN ('confirmed', 'dikonfirmasi')";
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
</body>

</html>