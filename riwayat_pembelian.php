<?php
session_start();
include 'config/config.php';


?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/main.css"/>
    <link rel="stylesheet" href="style/riwayat_pembelian.css"/>
    <title>Riwayat Pesanan - EVENTLY</title>
</head>
<body>
    <?php include 'komponen/navbar.php'; ?>
    
    <div class="container">
        <div class="header-title">Riwayat Pesanan</div>
        
        <div class="orders-container" id="ordersContainer">

        </div>
        
        <div class="empty-state" id="emptyState" style="display: none;">
            <h3>Belum Ada Pesanan</h3>
            <p>Anda belum memiliki riwayat pesanan. Mulai jelajahi event menarik!</p>
            <a href="homepage.php" class="btn-primary">Jelajahi Event</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadOrderHistory();
        });

        function loadOrderHistory() {
            const ordersContainer = document.getElementById('ordersContainer');
            const emptyState = document.getElementById('emptyState');
            
            // Ambil data dari localStorage
            const orders = JSON.parse(localStorage.getItem('orderHistory') || '[]');
            
            if (orders.length === 0) {
                emptyState.style.display = 'block';
                ordersContainer.style.display = 'none';
                return;
            }
            
            // Urutkan berdasarkan tanggal terbaru
            orders.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));
            
            ordersContainer.innerHTML = orders.map(order => createOrderCard(order)).join('');
        }

        function createOrderCard(order) {
            const orderDate = new Date(order.timestamp);
            const formattedDate = orderDate.toLocaleDateString('id-ID', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            const ticketItems = order.tickets.map(ticket => 
                `<div class="ticket-item">
                    <span>${ticket.type.charAt(0).toUpperCase() + ticket.type.slice(1)} (${ticket.quantity}x)</span>
                    <span>Rp${(ticket.quantity * ticket.price).toLocaleString()}</span>
                </div>`
            ).join('');
            
            const paymentMethodText = {
                'e-money': 'E-Money (QR Code)',
                'e-banking': 'E-Banking',
                'transfer': 'Bank Transfer'
            };
            
            return `
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-id">Order #${order.order_id || generateOrderId()}</div>
                        <div class="order-status status-${order.status || 'pending'}">
                            ${(order.status || 'pending').toUpperCase()}
                        </div>
                    </div>
                    <div class="order-details">
                        <div class="event-info">
                            <h3>${order.event_name}</h3>
                            <p>📅 ${order.event_date}</p>
                            <p class="order-date">Dipesan pada: ${formattedDate}</p>
                        </div>
                        <div class="ticket-info">
                            <h4>Detail Tiket:</h4>
                            ${ticketItems}
                        </div>
                        <div class="payment-info">
                            <div class="payment-method">
                                ${paymentMethodText[order.payment_method] || order.payment_method}
                            </div>
                            <div class="total-amount">
                                Rp${order.total_amount.toLocaleString()}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function generateOrderId() {
            return 'EVT' + Date.now().toString().slice(-6);
        }
    </script>
    
    <?php include 'komponen/footer.php'; ?>
</body>
</html>