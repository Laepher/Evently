<?php
session_start();
include 'config/config.php';
require 'auth/auth.php';
require_role('user');

// Get logged-in user ID
$user_id = $_SESSION['user_id'];

// Query to fetch order history with event details - Updated to show all relevant statuses
$query = "SELECT 
    p.id_pesanan,
    p.id_tiket,
    p.id_user,
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
    return date('d M Y H:i', strtotime($date));
}

// Function to get status class - Updated to handle all statuses from admin
function getStatusClass($status)
{
    $status_lower = strtolower($status);
    switch ($status_lower) {
        case 'confirmed':
        case 'terbayar':
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

// Function to get status display name
function getStatusDisplayName($status)
{
    $status_lower = strtolower($status);
    switch ($status_lower) {
        case 'confirmed':
        case 'terbayar':
            return 'DIKONFIRMASI';
        case 'pending':
        case 'menunggu':
            return 'MENUNGGU KONFIRMASI';
        case 'cancelled':
        case 'dibatalkan':
            return 'DIBATALKAN';
        default:
            return strtoupper($status);
    }
}

// Function to get payment method display name
function getPaymentMethodName($method)
{
    switch (strtolower($method)) {
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

// Function to check if there are any status updates
function checkStatusUpdates($user_id, $conn) {
    $query = "SELECT COUNT(*) as count FROM pesanan 
              WHERE id_user = ? AND 
              (status_pesanan IN ('terbayar', 'dibatalkan') OR 
               TIMESTAMPDIFF(MINUTE, tanggal_pesanan, NOW()) < 5)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] > 0;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/main.css" />
    <link rel="stylesheet" href="style/riwayat_pembelian.css" />
    <!-- SweetAlert2 untuk notifikasi -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Riwayat Pembelian - EVENTLY</title>
    <style>
        /* Additional styles for better status display */
        .status-confirmed {
            background-color: #d4edda !important;
            color: #155724 !important;
            border: 1px solid #c3e6cb;
        }
        
        .status-pending {
            background-color: #fff3cd !important;
            color: #856404 !important;
            border: 1px solid #ffeaa7;
        }
        
        .status-cancelled {
            background-color: #f8d7da !important;
            color: #721c24 !important;
            border: 1px solid #f5c6cb;
        }

        .order-status {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.8em;
            text-align: center;
            min-width: 120px;
        }

        /* Notification styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 5px;
            z-index: 9999;
            display: none;
            animation: slideIn 0.3s ease-out;
        }

        .notification.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .notification.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Loading indicator */
        .loading-indicator {
            display: none;
            text-align: center;
            padding: 20px;
            color: #6c757d;
        }

        .spinner {
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3498db;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
    <?php include 'komponen/navbar.php'; ?>

    <!-- Notification container -->
    <div id="notification" class="notification"></div>

    <div class="container">
        <div class="header-title">Riwayat Pembelian</div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <button class="refresh-btn" onclick="refreshData()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
            
            <!-- Auto-refresh toggle -->
            <div class="auto-refresh-toggle">
                <label>
                    <input type="checkbox" id="autoRefresh" checked> 
                    Auto-refresh (30s)
                </label>
            </div>
        </div>

        <!-- Loading indicator -->
        <div class="loading-indicator" id="loadingIndicator">
            <div class="spinner"></div>
            Memuat data terbaru...
        </div>

        <div class="orders-container" id="ordersContainer">
            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <h3>Tidak ada pembelian</h3>
                    <p>Anda belum memiliki riwayat pembelian. Mulai jelajahi event menarik!</p>
                    <a href="homepage.php" class="btn-primary">Jelajahi Event</a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card" data-order-id="<?= htmlspecialchars($order['id_pesanan']) ?>">
                        <div class="order-header">
                            <div class="order-id">Order #<?= htmlspecialchars($order['id_pesanan']) ?></div>
                            <div class="order-status status-<?= getStatusClass($order['status_pesanan']) ?>">
                                <?= getStatusDisplayName($order['status_pesanan']) ?>
                            </div>
                        </div>
                        <div class="order-details">
                            <div class="event-info">
                                <h3><?= htmlspecialchars($order['nama_event']) ?></h3>
                                <p><i class="fas fa-calendar"></i> Event: <?= formatDate($order['tanggal_event']) ?></p>
                                <p><i class="fas fa-tag"></i> Kategori: <?= htmlspecialchars($order['kategori']) ?></p>
                                <div class="date-info">
                                    <p class="order-date"><i class="fas fa-clock"></i> Dipesan: <?= formatDate($order['tanggal_pesanan']) ?></p>
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
                                
                                <?php if (getStatusClass($order['status_pesanan']) == 'confirmed'): ?>
                                <div class="payment-status" style="color: #28a745; font-weight: bold; margin-top: 10px;">
                                    <i class="fas fa-check-circle"></i> Pembayaran Berhasil
                                </div>
                                <?php elseif (getStatusClass($order['status_pesanan']) == 'cancelled'): ?>
                                <div class="payment-status" style="color: #dc3545; font-weight: bold; margin-top: 10px;">
                                    <i class="fas fa-times-circle"></i> Pesanan Dibatalkan
                                </div>
                                <?php else: ?>
                                <div class="payment-status" style="color: #ffc107; font-weight: bold; margin-top: 10px;">
                                    <i class="fas fa-clock"></i> Menunggu Konfirmasi
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let autoRefreshInterval;
        let lastUpdateCheck = Date.now();

        // Show notification function
        function showNotification(message, type = 'info') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification ${type}`;
            notification.style.display = 'block';
            
            setTimeout(() => {
                notification.style.display = 'none';
            }, 5000);
        }

        // Refresh data function
        function refreshData() {
            const loadingIndicator = document.getElementById('loadingIndicator');
            loadingIndicator.style.display = 'block';
            
            showNotification('Memuat data terbaru...', 'info');
            
            setTimeout(() => {
                location.reload();
            }, 1000);
        }

        // Check for status updates
        function checkStatusUpdates() {
            fetch('check_status_update.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    last_check: lastUpdateCheck
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.hasUpdates) {
                    showNotification('Status pesanan telah diperbarui! Halaman akan dimuat ulang...', 'success');
                    
                    // Highlight updated orders if available
                    if (data.updatedOrders && data.updatedOrders.length > 0) {
                        data.updatedOrders.forEach(orderId => {
                            const orderCard = document.querySelector(`[data-order-id="${orderId}"]`);
                            if (orderCard) {
                                orderCard.style.border = '2px solid #28a745';
                                orderCard.style.boxShadow = '0 0 10px rgba(40, 167, 69, 0.3)';
                            }
                        });
                    }
                    
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                }
                lastUpdateCheck = Date.now();
            })
            .catch(error => {
                console.log('Status check failed:', error);
            });
        }

        // Auto-refresh functionality
        function startAutoRefresh() {
            const autoRefreshCheckbox = document.getElementById('autoRefresh');
            
            if (autoRefreshCheckbox && autoRefreshCheckbox.checked) {
                autoRefreshInterval = setInterval(checkStatusUpdates, 30000); // Check every 30 seconds
            }
        }

        function stopAutoRefresh() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
            }
        }

        // Toggle auto-refresh
        document.addEventListener('DOMContentLoaded', function() {
            const autoRefreshCheckbox = document.getElementById('autoRefresh');
            const orderCards = document.querySelectorAll('.order-card');

            // Start auto-refresh by default
            startAutoRefresh();

            // Handle auto-refresh toggle
            if (autoRefreshCheckbox) {
                autoRefreshCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        startAutoRefresh();
                        showNotification('Auto-refresh diaktifkan', 'success');
                    } else {
                        stopAutoRefresh();
                        showNotification('Auto-refresh dinonaktifkan', 'info');
                    }
                });
            }

            // Add click effects to order cards
            orderCards.forEach(card => {
                card.addEventListener('click', function() {
                    this.style.transform = 'scale(0.98)';
                    setTimeout(() => {
                        this.style.transform = 'scale(1)';
                    }, 150);
                });
            });

            // Initial status check
            setTimeout(checkStatusUpdates, 2000);
        });

        // Clean up intervals when page is hidden/closed
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                stopAutoRefresh();
            } else {
                const autoRefreshCheckbox = document.getElementById('autoRefresh');
                if (autoRefreshCheckbox && autoRefreshCheckbox.checked) {
                    startAutoRefresh();
                }
            }
        });

        // Handle page unload
        window.addEventListener('beforeunload', function() {
            stopAutoRefresh();
        });
    </script>

    <?php include 'komponen/footer.php'; ?>
</body>

</html>