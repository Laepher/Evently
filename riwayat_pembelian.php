<?php
session_start();
include 'config/config.php';
require 'auth/auth.php';
require_role('user');

// Get logged-in user ID
$user_id = $_SESSION['user_id'];

// Query to fetch order history with event details
$query = "SELECT 
    p.id_pesanan,
    p.id_tiket,
    p.id_user,
    p.tanggal_bayar,
    p.metode_bayar,
    p.banyak_tiket,
    p.total_harga,
    p.status_pesanan,
    p.tanggal_pesanan,
    e.nama_event,
    e.tanggal_event,
    e.kategori,
    t.harga_tiket
FROM pesanan p
JOIN tiket t ON p.id_tiket = t.id_tiket
JOIN event e ON t.id_event = e.id_event
WHERE p.id_user = ?
ORDER BY p.tanggal_pesanan DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

$stmt->close();

// Function to format currency
function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Function to format date
function formatDate($date) {
    if (empty($date) || $date == '0000-00-00 00:00:00') {
        return '-';
    }
    return date('d M Y H:i', strtotime($date));
}

// Function to get status class
function getStatusClass($status) {
    switch(strtolower($status)) {
        case 'confirmed':
        case 'dikonfirmasi':
            return 'confirmed';
        case 'pending':
        case 'menunggu':
            return 'pending';
        case 'cancelled':
        case 'dibatalkan':
            return 'cancelled';
        default:
            return 'pending';
    }
}

// Function to get payment method display name
function getPaymentMethodName($method) {
    switch(strtolower($method)) {
        case 'e-money':
            return 'E-Money (QR Code)';
        case 'e-banking':
            return 'E-Banking';
        case 'transfer':
        case 'bank_transfer':
            return 'Bank Transfer';
        default:
            return ucfirst($method);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/main.css"/>
    <link rel="stylesheet" href="style/riwayat_pembelian.css"/>
    <title>Riwayat Pembelian - EVENTLY</title>
</head>
<body>
    <?php include 'komponen/navbar.php'; ?>
    
    <div class="container">
        <div class="header-title">Riwayat Pembelian</div>
        
        <button class="refresh-btn" onclick="location.reload()">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
        
        <div class="orders-container" id="ordersContainer">
            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <h3>Tidak ada pembelian</h3>
                    <p>Anda belum memiliki riwayat pembelian. Mulai jelajahi event menarik!</p>
                    <a href="homepage.php" class="btn-primary">Jelajahi Event</a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-id">Order #<?= htmlspecialchars($order['id_pesanan']) ?></div>
                            <div class="order-status status-<?= getStatusClass($order['status_pesanan']) ?>">
                                <?= strtoupper($order['status_pesanan']) ?>
                            </div>
                        </div>
                        <div class="order-details">
                            <div class="event-info">
                                <h3><?= htmlspecialchars($order['nama_event']) ?></h3>
                                <p><i class="fas fa-calendar"></i> Event: <?= formatDate($order['tanggal_event']) ?></p>
                                <p><i class="fas fa-tag"></i> Kategori: <?= htmlspecialchars($order['kategori']) ?></p>
                                <div class="date-info">
                                    <p class="order-date"><i class="fas fa-clock"></i> Dipesan: <?= formatDate($order['tanggal_pesanan']) ?></p>
                                    <p class="payment-date">
                                        <i class="fas fa-credit-card"></i> 
                                        Dibayar: <?= formatDate($order['tanggal_bayar']) ?>
                                    </p>
                                </div>
                            </div>
                            <div class="ticket-info">
                                <h4>Detail Tiket:</h4>
                                <div class="ticket-item">
                                    <span>Tiket Regular (<?= $order['banyak_tiket'] ?>x)</span>
                                    <span><?= formatCurrency($order['harga_tiket']) ?> per tiket</span>
                                </div>
                                <div class="ticket-item">
                                    <span><strong>Subtotal</strong></span>
                                    <span><strong><?= formatCurrency($order['total_harga']) ?></strong></span>
                                </div>
                            </div>
                            <div class="payment-info">
                                <div class="payment-method">
                                    <?= getPaymentMethodName($order['metode_bayar']) ?>
                                </div>
                                <div class="total-amount">
                                    <?= formatCurrency($order['total_harga']) ?>
                                </div>
                                <div class="order-date">
                                    ID Tiket: <?= htmlspecialchars($order['id_tiket']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            const orderCards = document.querySelectorAll('.order-card');
            
            orderCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Add click effect
                    this.style.transform = 'scale(0.98)';
                    setTimeout(() => {
                        this.style.transform = 'scale(1)';
                    }, 150);
                });
            });
        });

        // Auto refresh every 30 seconds for status updates
        setInterval(function() {
            // Only refresh if there are pending orders
            const pendingOrders = document.querySelectorAll('.status-pending');
            if (pendingOrders.length > 0) {
                console.log('Checking for order updates...');
            }
        }, 30000);
    </script>
    
    <?php include 'komponen/footer.php'; ?>
</body>
</html>