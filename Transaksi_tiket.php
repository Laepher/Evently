<?php
session_start();
include 'config/config.php';

//perubahan
$id_event = isset($_GET['id_event']) ? $_GET['id_event'] : 11100;

$query = "SELECT e.*, t.harga_tiket FROM event e
        JOIN tiket t ON e.id_event = t.id_event
        WHERE e.id_event = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_event);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();

$posterData = base64_encode($event['poster_event']);
$posterSrc = "data:image/jpeg;base64," . $posterData;

$hargaTiket = (int)$event['harga_tiket'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?= htmlspecialchars($event['nama_event']) ?></title>
    <link rel="stylesheet" href="style/Transaksi.css"/>
    <link rel="stylesheet" href="style/main.css"/>
</head>
<body>
<?php include 'komponen/navbar.php'; ?>

<main class="main">
    <div class="container">
        <div class="event-details">
            <div class="poster">
                <img src="<?= $posterSrc ?>" alt="Poster Tiket"/>
            </div>
            <div class="info">
                <h1 class="event-title"><?= htmlspecialchars($event['nama_event']) ?></h1>
                <h1 class="event-type">Kategori: <?= htmlspecialchars($event['kategori']) ?></h1>
                <br/>
                <div class="date">📅 <?= date('d M Y', strtotime($event['tanggal_event'])) ?></div>
            </div>
        </div>

        <div class="ticket-options">
            <div class="ticket-type">
                <div class="type-name">Harga: </div>
                <div class="price">Rp<?= number_format($hargaTiket, 0, ',', '.') ?></div>
                <div class="quantity-control">
                    <button class="quantity-btn minus" data-type="regular">-</button>
                    <input type="text" class="quantity" id="regular-qty" value="0" readonly/>
                    <button class="quantity-btn plus" data-type="regular">+</button>
                </div>
            </div>
        </div>

        <div class="checkout-section">
            <div class="order-summary">
                <div class="total" id="total-amount">Total: Rp0</div>
                <div id="ticket-summary">
                    <div id="ticket-a"></div>
                </div>
            </div>

            <div class="payment-section">
                <div class="payment-dropdown-container">
                    <div class="payment-dropdown" id="payment-dropdown">
                        <span id="selected-payment">Jenis Pembayaran</span>
                        <div class="dropdown-icon">▼</div>
                    </div>
                    <div class="dropdown-options" id="payment-options">
                        <div class="dropdown-option" data-value="e-banking">E-banking</div>
                        <div class="dropdown-option" data-value="e-money">E-money</div>
                        <div class="dropdown-option" data-value="transfer">Bank Transfer</div>
                    </div>
                </div>

                <button class="pay-button" id="pay-button">BAYAR</button>
            </div>
        </div>

        <div id="confirmModal" class="modal">
            <div class="modal-content">
                <h2>Konfirmasi Pembayaran</h2>
                <p>Apakah Anda yakin ingin melanjutkan ke pembayaran?</p>
                <div class="modal-buttons">
                    <button onclick="confirmBayar()">YA</button>
                    <button onclick="goBack()">TIDAK</button>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'komponen/footer.php'; ?>

<script>
    const ticketTypes = [{
        type: 'regular',
        price: <?= $hargaTiket ?>,
        quantity: 0
    }];

    // Variable to store selected payment method
    let selectedPaymentMethod = '';

    const elements = {
        quantityInputs: {
            regular: document.getElementById('regular-qty')
        },
        ticketSummary: document.getElementById('ticket-a'),
        totalAmount: document.getElementById('total-amount'),
        paymentDropdown: document.getElementById('payment-dropdown'),
        paymentOptions: document.getElementById('payment-options'),
        selectedPayment: document.getElementById('selected-payment'),
        payButton: document.getElementById('pay-button'),
        confirmModal: document.getElementById('confirmModal')
    };

    document.addEventListener('DOMContentLoaded', function () {
        const plusButtons = document.querySelectorAll('.plus');
        const minusButtons = document.querySelectorAll('.minus');

        plusButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const type = btn.getAttribute('data-type');
                const ticket = ticketTypes.find(t => t.type === type);
                if (ticket) {
                    ticket.quantity++;
                    updateQuantityDisplay();
                    updateSummary();
                }
            });
        });

        minusButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const type = btn.getAttribute('data-type');
                const ticket = ticketTypes.find(t => t.type === type);
                if (ticket && ticket.quantity > 0) {
                    ticket.quantity--;
                    updateQuantityDisplay();
                    updateSummary();
                }
            });
        });

        elements.paymentDropdown.addEventListener('click', () => {
            elements.paymentOptions.style.display = elements.paymentOptions.style.display === 'block' ? 'none' : 'block';
        });

        document.addEventListener('click', (event) => {
            if (!elements.paymentDropdown.contains(event.target) && !elements.paymentOptions.contains(event.target)) {
                elements.paymentOptions.style.display = 'none';
            }
        });

        document.querySelectorAll('.dropdown-option').forEach(option => {
            option.addEventListener('click', () => {
                elements.selectedPayment.textContent = option.textContent;
                selectedPaymentMethod = option.getAttribute('data-value'); // Store selected payment method
                elements.paymentOptions.style.display = 'none';
            });
        });

        elements.payButton.addEventListener('click', () => {
            const totalTickets = ticketTypes.reduce((acc, ticket) => acc + ticket.quantity, 0);

            if (totalTickets === 0) {
                alert('Silakan pilih setidaknya satu tiket sebelum melanjutkan pembayaran.');
                return;
            }

            if (elements.selectedPayment.textContent === 'Jenis Pembayaran') {
                alert('Silakan pilih metode pembayaran sebelum melanjutkan.');
                return;
            }

            elements.confirmModal.style.display = 'block';
        });

        updateSummary();
    });

    function updateQuantityDisplay() {
        ticketTypes.forEach(ticket => {
            if (elements.quantityInputs[ticket.type]) {
                elements.quantityInputs[ticket.type].value = ticket.quantity;
            }
        });
    }

    function updateSummary() {
        elements.ticketSummary.textContent = '';

        let totalPrice = 0;
        let totalTickets = 0;

        ticketTypes.forEach(ticket => {
            if (ticket.quantity > 0) {
                const subtotal = ticket.quantity * ticket.price;
                const typeName = ticket.type.charAt(0).toUpperCase() + ticket.type.slice(1).toLowerCase();
                elements.ticketSummary.textContent = `${typeName} (${ticket.quantity}x): Rp${subtotal.toLocaleString()}`;
                totalTickets += ticket.quantity;
                totalPrice += subtotal;
            }
        });

        elements.totalAmount.textContent = totalTickets > 0 ?
            `Total: Rp${totalPrice.toLocaleString()}` : 'Total: Rp0';
    }

function confirmBayar() {
    // Debug: Cek data yang akan dikirim
    const totalTickets = ticketTypes.reduce((acc, ticket) => acc + ticket.quantity, 0);
    const totalPrice = ticketTypes.reduce((acc, ticket) => acc + (ticket.quantity * ticket.price), 0);
    
    console.log('Data yang akan dikirim:', {
        id_event: <?= $id_event ?>,
        total_harga: totalPrice,
        banyak_tiket: totalTickets,
        metode_bayar: selectedPaymentMethod
    });
    
    // Kirim data ke server menggunakan POST
    const formData = new FormData();
    formData.append('id_event', <?= $id_event ?>);
    formData.append('total_harga', totalPrice);
    formData.append('banyak_tiket', totalTickets);
    formData.append('metode_bayar', selectedPaymentMethod);
    formData.append('action', 'create_pesanan');
    
    fetch('process_pesanan.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        // Cek apakah response OK
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Cek content-type
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            // Jika bukan JSON, tampilkan response sebagai text untuk debugging
            return response.text().then(text => {
                console.log('Response text:', text);
                throw new Error('Response bukan JSON: ' + text.substring(0, 200));
            });
        }
        
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        
        if (data.success) {
            // Simpan ID pesanan untuk digunakan di halaman berikutnya
            sessionStorage.setItem('id_pesanan', data.id_pesanan);
            
            // Redirect ke halaman pembayaran
            let redirectUrl = '';
            switch(selectedPaymentMethod) {
                case 'e-money':
                    redirectUrl = 'Hasil_pembayaran.php';
                    break;
                case 'e-banking':
                case 'transfer':
                    redirectUrl = 'Hasil_pembayaran2.php';
                    break;
            }
            window.location.href = redirectUrl;
        } else {
            alert('Gagal membuat pesanan: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Detailed Error:', error);
        alert('Terjadi kesalahan saat memproses pesanan: ' + error.message);
    });
}


    function goBack() {
        elements.confirmModal.style.display = 'none';
    }

    window.onclick = function (event) {
        if (event.target === elements.confirmModal) {
            elements.confirmModal.style.display = 'none';
        }
    }
</script>
</body>
</html>