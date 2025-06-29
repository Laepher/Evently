<?php
session_start();
include 'config/config.php';
require 'auth/auth.php';
require_role('user');

// Get logged-in user ID
$user_id = $_SESSION['id_user'];

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
    e.deskripsi_event,
    t.harga_tiket,
    u.nama_user,
    u.email_user
FROM pesanan p
JOIN tiket t ON p.id_tiket = t.id_tiket
JOIN event e ON t.id_event = e.id_event
JOIN user u ON p.id_user = u.id_user
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
function checkStatusUpdates($user_id, $conn)
{
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        /* Receipt button styles */
        .receipt-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 10px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .receipt-btn:hover {
            background: linear-gradient(135deg, #218838, #1ea07a);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 2% auto;
            padding: 0;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            animation: slideIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            background: linear-gradient(135deg, rgb(155, 201, 250) 0%, #0077ff 100%);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            border: none;
            background: none;
            padding: 0;
            line-height: 1;
        }

        .close:hover {
            opacity: 0.7;
        }

        /* Receipt styles */
        .receipt {
            padding: 30px;
            font-family: 'Courier New', monospace;
            background: white;
            color: #333;
        }

        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #333;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .receipt-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
            color: #0077ff;
        }

        .receipt-header p {
            margin: 5px 0;
            color: #666;
        }

        .receipt-info {
            margin-bottom: 20px;
        }

        .receipt-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px 0;
        }

        .receipt-row.border-top {
            border-top: 1px dashed #333;
            padding-top: 10px;
            margin-top: 15px;
        }

        .receipt-row.border-bottom {
            border-bottom: 1px dashed #333;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .receipt-row.total {
            font-weight: bold;
            font-size: 1.1em;
            border-top: 2px solid #333;
            border-bottom: 2px solid #333;
            padding: 10px 0;
            margin: 15px 0;
        }

        .receipt-section {
            margin: 20px 0;
        }

        .receipt-section h3 {
            color: #0077ff;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        .receipt-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px dashed #333;
            color: #666;
        }

        .receipt-qr {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 2px dashed #0077ff;
        }

        .print-btn {
            background: linear-gradient(135deg, rgb(155, 201, 250), #0077ff);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
            margin: 20px auto;
            display: block;
            transition: all 0.3s ease;
        }

        .print-btn:hover {
            background: linear-gradient(135deg, rgb(155, 201, 250), #0077ff);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px #0077ff;
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
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
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
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Print styles */
        @media print {
            body * {
                visibility: hidden;
            }

            .receipt,
            .receipt * {
                visibility: visible;
            }

            .receipt {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                padding: 20px;
            }

            .print-btn {
                display: none !important;
            }

            .modal-header {
                display: none !important;
            }
        }

        /* Action buttons container */
        .order-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .order-actions button {
            flex: 1;
            min-width: 120px;
        }

        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                margin: 5% auto;
            }

            .receipt {
                padding: 20px 15px;
            }

            .receipt-row {
                font-size: 14px;
            }

            .order-actions {
                flex-direction: column;
            }

            .order-actions button {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <?php include 'komponen/navbar.php'; ?>

    <!-- Notification container -->
    <div id="notification" class="notification"></div>

    <!-- Receipt Modal -->
    <div id="receiptModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-receipt"></i> Nota Pembelian</h2>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>
            <div id="receiptContent" class="receipt">
                <!-- Receipt content will be inserted here -->
            </div>
        </div>
    </div>

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

                                    <!-- Receipt button for confirmed orders -->
                                    <div class="order-actions">
                                        <button class="receipt-btn" onclick="showReceipt(<?= htmlspecialchars(json_encode($order)) ?>)">
                                            <i class="fas fa-receipt"></i> Lihat Nota
                                        </button>
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

        // Receipt modal functions
        function showReceipt(orderData) {
            const modal = document.getElementById('receiptModal');
            const receiptContent = document.getElementById('receiptContent');

            // Format currency for JavaScript
            function formatCurrency(amount) {
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
            }

            // Format date for JavaScript
            function formatDate(dateString) {
                if (!dateString || dateString === '0000-00-00 00:00:00') return '-';
                const date = new Date(dateString);
                return date.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }

            // Generate unique receipt number
            const receiptNumber = 'RCP-' + orderData.id_pesanan + '-' + Date.now().toString().slice(-6);

            receiptContent.innerHTML = `
                <div class="receipt-header">
                    <h1>EVENTLY</h1>
                    <p>Platform Event Terpercaya</p>
                    <p>📧 info@evently.com | 📞 +62-21-1234-5678</p>
                    <p>🌐 www.evently.com</p>
                </div>

                <div class="receipt-info">
                    <div class="receipt-section">
                        <h3>INFORMASI NOTA</h3>
                        <div class="receipt-row">
                            <span>No. Nota:</span>
                            <span><strong>${receiptNumber}</strong></span>
                        </div>
                        <div class="receipt-row">
                            <span>No. Pesanan:</span>
                            <span><strong>#${orderData.id_pesanan}</strong></span>
                        </div>
                        <div class="receipt-row">
                            <span>Tanggal Pembelian:</span>
                            <span>${formatDate(orderData.tanggal_pesanan)}</span>
                        </div>
                        <div class="receipt-row">
                            <span>Status:</span>
                            <span style="color: #28a745; font-weight: bold;">✅ TERBAYAR</span>
                        </div>
                    </div>

                    <div class="receipt-section">
                        <h3>INFORMASI PEMBELI</h3>
                        <div class="receipt-row">
                            <span>Nama:</span>
                            <span>${orderData.nama_user}</span>
                        </div>
                        <div class="receipt-row">
                            <span>Email:</span>
                            <span>${orderData.email_user}</span>
                        </div>
                        <div class="receipt-row">
                            <span>ID User:</span>
                            <span>${orderData.id_user}</span>
                        </div>
                    </div>

                    <div class="receipt-section">
                        <h3>DETAIL EVENT</h3>
                        <div class="receipt-row">
                            <span>Nama Event:</span>
                            <span><strong>${orderData.nama_event}</strong></span>
                        </div>
                        <div class="receipt-row">
                            <span>Tanggal Event:</span>
                            <span>${formatDate(orderData.tanggal_event)}</span>
                        </div>
                        <div class="receipt-row">
                            <span>Kategori:</span>
                            <span>${orderData.kategori}</span>
                        </div>
                        <div class="receipt-row">
                            <span>ID Tiket:</span>
                            <span>${orderData.id_tiket}</span>
                        </div>
                    </div>

                    <div class="receipt-section">
                        <h3>DETAIL PEMBELIAN</h3>
                        <div class="receipt-row">
                            <span>Tiket Regular</span>
                            <span>${orderData.banyak_tiket}x</span>
                        </div>
                        <div class="receipt-row">
                            <span>Harga per Tiket:</span>
                            <span>${formatCurrency(orderData.harga_tiket)}</span>
                        </div>
                        <div class="receipt-row border-top">
                            <span>Subtotal:</span>
                            <span>${formatCurrency(orderData.harga_tiket * orderData.banyak_tiket)}</span>
                        </div>
                        <div class="receipt-row">
                            <span>Biaya Admin:</span>
                            <span>Rp 0</span>
                        </div>
                        <div class="receipt-row total">
                            <span>TOTAL PEMBAYARAN:</span>
                            <span>${formatCurrency(orderData.total_harga)}</span>
                        </div>
                    </div>

                    <div class="receipt-section">
                        <h3>METODE PEMBAYARAN</h3>
                        <div class="receipt-row">
                            <span>Metode:</span>
                            <span>${getPaymentMethodName(orderData.metode_bayar)}</span>
                        </div>
                        <div class="receipt-row">
                            <span>Status Pembayaran:</span>
                            <span style="color: #28a745; font-weight: bold;">✅ BERHASIL</span>
                        </div>
                    </div>

                    <div class="receipt-qr">
                        <p><strong>🎫 KODE TIKET DIGITAL</strong></p>
                        <p style="font-size: 24px; letter-spacing: 3px; font-weight: bold; color: #667eea;">
                            ${orderData.id_pesanan.toString().padStart(8, '0')}
                        </p>
                        <p style="font-size: 12px; color: #666;">
                            Tunjukkan kode ini saat masuk event
                        </p>
                    </div>
                </div>

                <div class="receipt-footer">
                    <p><strong>TERIMA KASIH!</strong></p>
                    <p>Simpan nota ini sebagai bukti pembelian yang sah.</p>
                    <p>Untuk bantuan, hubungi customer service kami.</p>
                    <p style="margin-top: 15px; font-size: 12px;">
                        Dicetak pada: ${new Date().toLocaleDateString('id-ID', {
                            day: '2-digit',
                            month: 'long',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        })}
                    </p>
                </div>

            <button class="print-btn" onclick="printReceipt()">
            <i class="fas fa-download"></i> Download Nota
            </button>
            `;

            modal.style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        }

        function closeModal() {
            const modal = document.getElementById('receiptModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto'; // Restore scrolling
        }

        function printReceipt() {
            const receiptContent = document.getElementById('receiptContent');

            showNotification('Menyiapkan download...', 'info');

            // Konfigurasi untuk capture dengan kualitas tinggi
            const options = {
                scale: 3, // Resolusi tinggi
                useCORS: true,
                logging: false,
                width: receiptContent.scrollWidth,
                height: receiptContent.scrollHeight,
                backgroundColor: '#ffffff',
                allowTaint: false,
                removeContainer: false
            };

            html2canvas(receiptContent, options).then(canvas => {
                // Convert canvas to blob
                canvas.toBlob(function(blob) {
                    // Create download link
                    const link = document.createElement('a');

                    // Generate nama file dengan format yang rapi
                    const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-');
                    const orderId = getCurrentOrderId();
                    const filename = `Nota_EVENTLY_${orderId}.png`;

                    link.download = filename;
                    link.href = URL.createObjectURL(blob);

                    // Trigger download
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);

                    // Clean up memory
                    URL.revokeObjectURL(link.href);

                    showNotification('Nota berhasil didownload!', 'success');
                }, 'image/png', 1.0); // Kualitas maksimal

            }).catch(error => {
                console.error('Error generating image:', error);
                showNotification('Gagal membuat nota. Silakan coba lagi.', 'error');
            });
        }

        // Fungsi untuk mendapatkan Order ID dari konten receipt
        function getCurrentOrderId() {
            try {
                const receiptContent = document.getElementById('receiptContent');
                const orderIdElement = receiptContent.querySelector('.receipt-row span strong');
                if (orderIdElement) {
                    return orderIdElement.textContent.replace('#', '').trim();
                }

                // Fallback: cari dari data-order-id di card yang aktif
                const activeCard = document.querySelector('.order-card[style*="border: 2px solid"]');
                if (activeCard) {
                    return activeCard.getAttribute('data-order-id');
                }

                return 'ORDER_' + Date.now().toString().slice(-6);
            } catch (error) {
                console.error('Error getting order ID:', error);
                return 'ORDER_' + Date.now().toString().slice(-6);
            }
        }


        // Get payment method name function (JavaScript version)
        function getPaymentMethodName(method) {
            switch (method.toLowerCase()) {
                case 'e-money':
                    return 'E-Money (QR Code)';
                case 'e-banking':
                    return 'E-Banking';
                case 'transfer':
                case 'bank_transfer':
                    return 'Bank Transfer';
                default:
                    return method.charAt(0).toUpperCase() + method.slice(1);
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('receiptModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });

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